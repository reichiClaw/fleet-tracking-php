<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Loan;
use App\Models\Vehicle;
use App\Services\MediaService;
use App\Services\VehicleWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class LoanController extends Controller
{
    public function index(): View
    {
        return view('loans.index', [
            'loans' => Loan::with(['vehicle', 'driver'])->latest('loaned_at')->paginate(30),
        ]);
    }

    public function create(Request $request, ?Vehicle $vehicle = null): View
    {
        return view('loans.create', [
            'vehicle' => $vehicle,
            'vehicles' => Vehicle::where('status', Vehicle::STATUS_AVAILABLE)->orderBy('inventory_number')->get(),
            'drivers' => Driver::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, VehicleWorkflowService $workflow, MediaService $media): RedirectResponse
    {
        $data = $request->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'borrower_type' => ['required', Rule::in([Loan::BORROWER_INTERNAL, Loan::BORROWER_EXTERNAL])],
            'driver_id' => ['nullable', 'required_if:borrower_type,'.Loan::BORROWER_INTERNAL, 'exists:drivers,id'],
            'company_name' => ['nullable', 'required_if:borrower_type,'.Loan::BORROWER_EXTERNAL, 'string', 'max:160'],
            'borrower_name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:80'],
            'planned_return_at' => ['required', 'date', 'after:now'],
            'km' => ['required', 'integer', 'min:0'],
            'operating_hours' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:120'],
            'condition_notes' => ['nullable', 'string'],
            'damage_description' => ['nullable', 'string'],
            'damage_severity' => ['nullable', 'in:minor,moderate,major,critical'],
            'signature_data' => ['nullable', 'string'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ]);

        if ($data['borrower_type'] === Loan::BORROWER_INTERNAL && ! empty($data['driver_id'])) {
            $driver = Driver::find($data['driver_id']);
            $data['borrower_name'] = $driver?->name ?? $data['borrower_name'];
            $data['company_name'] = $driver?->company;
            $data['phone'] = $driver?->phone;
        }

        try {
            [$loan, $inspection] = $workflow->loanOut(Vehicle::findOrFail($data['vehicle_id']), $data, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['vehicle_id' => $exception->getMessage()])->withInput();
        }

        $media->storePhotos($request, $loan->vehicle, $inspection);
        $media->storeSignatureDataUrl($request, $loan);

        return redirect()->route('vehicles.show', $loan->vehicle)->with('status', 'Fahrzeug wurde verliehen.');
    }

    public function returnForm(Loan $loan): View
    {
        $loan->load('vehicle');
        return view('loans.return', ['loan' => $loan]);
    }

    public function return(Request $request, Loan $loan, VehicleWorkflowService $workflow, MediaService $media): RedirectResponse
    {
        $data = $request->validate([
            'km' => ['required', 'integer', 'min:0'],
            'operating_hours' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:120'],
            'condition_notes' => ['nullable', 'string'],
            'damage_description' => ['nullable', 'string'],
            'damage_severity' => ['nullable', 'in:minor,moderate,major,critical'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ]);

        try {
            $inspection = $workflow->returnLoan($loan, $data, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['loan' => $exception->getMessage()])->withInput();
        }

        $media->storePhotos($request, $loan->vehicle, $inspection);

        return redirect()->route('vehicles.show', $loan->vehicle)->with('status', 'Rueckgabe wurde gespeichert.');
    }
}
