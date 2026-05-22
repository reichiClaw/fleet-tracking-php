<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use App\Services\VehicleWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vehicles = Vehicle::with('category')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $query->where('inventory_number', 'like', $term)->orWhere('model', 'like', $term);
            })
            ->orderBy('inventory_number')
            ->paginate($request->integer('per_page', 25));

        return response()->json($vehicles);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return response()->json($vehicle->load(['category', 'activeLoan', 'inspections', 'damages']));
    }

    public function scan(string $token): JsonResponse
    {
        $vehicle = Vehicle::with('category')->where('qr_token', $token)->firstOrFail();
        $vehicle->forceFill(['last_qr_scanned_at' => now()])->save();

        return response()->json([
            'vehicle' => $vehicle,
            'available_actions' => $vehicle->isAvailable()
                ? ['loan', 'check_in', 'manufacturer_checkout']
                : ['show_history', 'return_if_active', 'damage_report'],
        ]);
    }

    public function checkIn(Request $request, Vehicle $vehicle, VehicleWorkflowService $workflow): JsonResponse
    {
        $data = $request->validate($this->inspectionRules());
        $inspection = $workflow->checkIn($vehicle, $data, $request->user(), VehicleInspection::TYPE_ARRIVAL_CHECKIN);

        return response()->json(['inspection' => $inspection, 'vehicle' => $vehicle->fresh()], 201);
    }

    public function loan(Request $request, Vehicle $vehicle, VehicleWorkflowService $workflow): JsonResponse
    {
        $data = $request->validate([
            'borrower_type' => ['required', Rule::in([Loan::BORROWER_INTERNAL, Loan::BORROWER_EXTERNAL])],
            'driver_id' => ['nullable', 'exists:drivers,id'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'borrower_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:80'],
            'planned_return_at' => ['required', 'date', 'after:now'],
            ...$this->inspectionRules(),
        ]);

        [$loan, $inspection] = $workflow->loanOut($vehicle, $data, $request->user());

        return response()->json(['loan' => $loan, 'inspection' => $inspection, 'vehicle' => $vehicle->fresh()], 201);
    }

    public function returnLoan(Request $request, Loan $loan, VehicleWorkflowService $workflow): JsonResponse
    {
        $data = $request->validate($this->inspectionRules());
        $inspection = $workflow->returnLoan($loan, $data, $request->user());

        return response()->json(['loan' => $loan->fresh(), 'inspection' => $inspection, 'vehicle' => $loan->vehicle->fresh()]);
    }

    private function inspectionRules(): array
    {
        return [
            'km' => ['required', 'integer', 'min:0'],
            'operating_hours' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:120'],
            'external_partner' => ['nullable', 'string', 'max:160'],
            'condition_notes' => ['nullable', 'string'],
            'damage_description' => ['nullable', 'string'],
            'damage_severity' => ['nullable', 'in:minor,moderate,major,critical'],
        ];
    }
}
