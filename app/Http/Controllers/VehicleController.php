<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Services\QrCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::with(['category', 'activeLoan'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('vehicle_category_id', $request->integer('category')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $term = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($q) => $q->where('inventory_number', 'like', $term)
                    ->orWhere('manufacturer', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('serial_number', 'like', $term)
                    ->orWhere('license_plate', 'like', $term));
            })
            ->orderBy('inventory_number')
            ->paginate(25)
            ->withQueryString();

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'categories' => VehicleCategory::where('is_active', true)->orderBy('name')->get(),
            'statuses' => Vehicle::STATUSES,
        ]);
    }

    public function create(): View
    {
        return view('vehicles.form', ['vehicle' => new Vehicle(), 'categories' => VehicleCategory::orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $vehicle = Vehicle::create($this->validatedVehicleData($request));

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Fahrzeug wurde angelegt.');
    }

    public function show(Vehicle $vehicle, QrCodeService $qrCodeService): View
    {
        $vehicle->load(['category', 'inspections.user', 'loans.driver', 'activeLoan', 'damages', 'photos']);

        return view('vehicles.show', [
            'vehicle' => $vehicle,
            'qrSvg' => $qrCodeService->svgForVehicle($vehicle),
        ]);
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('vehicles.form', ['vehicle' => $vehicle, 'categories' => VehicleCategory::orderBy('name')->get()]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validatedVehicleData($request, $vehicle));

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'Fahrzeug wurde aktualisiert.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->forceFill(['is_active' => false, 'status' => Vehicle::STATUS_INACTIVE])->save();

        return redirect()->route('vehicles.index')->with('status', 'Fahrzeug wurde deaktiviert.');
    }

    public function importForm(): View
    {
        return view('vehicles.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt']]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $header = array_map(fn ($value) => strtolower(trim((string) $value)), array_shift($rows) ?: []);
        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            $mapped = [];
            foreach ($header as $column => $name) {
                $mapped[$name] = trim((string) ($row[$column] ?? ''));
            }

            if (($mapped['inventory_number'] ?? '') === '') {
                continue;
            }

            try {
                $categoryName = $mapped['category'] ?? 'Sonstige';
                $category = VehicleCategory::firstOrCreate(['name' => $categoryName], ['slug' => str($categoryName)->slug()]);

                $vehicle = Vehicle::updateOrCreate(
                    ['inventory_number' => $mapped['inventory_number']],
                    [
                        'vehicle_category_id' => $category->id,
                        'manufacturer' => $mapped['manufacturer'] ?? null,
                        'model' => $mapped['model'] ?: 'Unbekannt',
                        'serial_number' => $mapped['serial_number'] ?? null,
                        'license_plate' => $mapped['license_plate'] ?? null,
                        'year' => $mapped['year'] !== '' ? (int) $mapped['year'] : null,
                        'location' => $mapped['location'] ?? null,
                        'current_km' => (int) ($mapped['current_km'] ?? 0),
                        'current_operating_hours' => (float) ($mapped['current_operating_hours'] ?? 0),
                        'status' => Vehicle::STATUS_AVAILABLE,
                        'is_active' => true,
                    ]
                );

                $vehicle->wasRecentlyCreated ? $created++ : $updated++;
            } catch (\Throwable $exception) {
                $errors[] = 'Zeile '.($rowNumber + 2).': '.$exception->getMessage();
            }
        }

        return redirect()->route('vehicles.index')->with('status', "Import abgeschlossen: {$created} neu, {$updated} aktualisiert.")->with('import_errors', $errors);
    }

    public function qrLabel(Vehicle $vehicle, QrCodeService $qrCodeService): View
    {
        return view('vehicles.qr-label', ['vehicle' => $vehicle, 'qrSvg' => $qrCodeService->svgForVehicle($vehicle)]);
    }

    public function scan(string $token): RedirectResponse
    {
        $vehicle = Vehicle::where('qr_token', $token)->firstOrFail();
        $vehicle->forceFill(['last_qr_scanned_at' => now()])->save();

        return redirect()->route('vehicles.show', $vehicle)->with('status', 'QR-Code gescannt.');
    }

    private function validatedVehicleData(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'inventory_number' => ['required', 'string', 'max:80', Rule::unique('vehicles')->ignore($vehicle)],
            'vehicle_category_id' => ['required', 'exists:vehicle_categories,id'],
            'manufacturer' => ['nullable', 'string', 'max:120'],
            'model' => ['required', 'string', 'max:120'],
            'serial_number' => ['nullable', 'string', 'max:120'],
            'license_plate' => ['nullable', 'string', 'max:50'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'current_km' => ['required', 'integer', 'min:0'],
            'current_operating_hours' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(array_keys(Vehicle::STATUSES))],
            'location' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
