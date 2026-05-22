<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleInspection;
use App\Services\MediaService;
use App\Services\VehicleWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleInspectionController extends Controller
{
    public function checkInForm(Vehicle $vehicle): View
    {
        return view('vehicles.check-in', ['vehicle' => $vehicle]);
    }

    public function checkIn(Request $request, Vehicle $vehicle, VehicleWorkflowService $workflow, MediaService $media): RedirectResponse
    {
        $data = $this->validatedInspection($request);
        $inspection = $workflow->checkIn($vehicle, $data, $request->user(), VehicleInspection::TYPE_ARRIVAL_CHECKIN);
        $media->storePhotos($request, $vehicle, $inspection);

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Check-in wurde gespeichert.');
    }

    public function manufacturerCheckoutForm(Vehicle $vehicle): View
    {
        return view('vehicles.manufacturer-checkout', ['vehicle' => $vehicle]);
    }

    public function manufacturerCheckout(Request $request, Vehicle $vehicle, VehicleWorkflowService $workflow, MediaService $media): RedirectResponse
    {
        $data = $this->validatedInspection($request);
        $inspection = $workflow->checkoutToManufacturer($vehicle, $data, $request->user());
        $media->storePhotos($request, $vehicle, $inspection);

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Fahrzeug wurde an den Hersteller ausgecheckt.');
    }

    private function validatedInspection(Request $request): array
    {
        return $request->validate([
            'km' => ['required', 'integer', 'min:0'],
            'operating_hours' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:120'],
            'external_partner' => ['nullable', 'string', 'max:160'],
            'condition_notes' => ['nullable', 'string'],
            'damage_description' => ['nullable', 'string'],
            'damage_severity' => ['nullable', 'in:minor,moderate,major,critical'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:12288'],
        ]);
    }
}
