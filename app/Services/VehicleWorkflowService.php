<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleDamage;
use App\Models\VehicleInspection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VehicleWorkflowService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function checkIn(Vehicle $vehicle, array $data, User $user, string $type = VehicleInspection::TYPE_ARRIVAL_CHECKIN): VehicleInspection
    {
        return DB::transaction(function () use ($vehicle, $data, $user, $type): VehicleInspection {
            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
                'type' => $type,
                'km' => (int) $data['km'],
                'operating_hours' => (float) $data['operating_hours'],
                'condition_notes' => Arr::get($data, 'condition_notes'),
                'occurred_at' => now(),
                'location' => Arr::get($data, 'location', $vehicle->location),
                'external_partner' => Arr::get($data, 'external_partner'),
            ]);

            $this->storeDamageIfPresent($vehicle, $inspection, $data, $user);

            $vehicle->forceFill([
                'current_km' => max($vehicle->current_km, (int) $data['km']),
                'current_operating_hours' => max((float) $vehicle->current_operating_hours, (float) $data['operating_hours']),
                'location' => Arr::get($data, 'location', $vehicle->location),
                'status' => Vehicle::STATUS_AVAILABLE,
                'is_active' => true,
            ])->save();

            $this->auditLogger->log('vehicle.check_in', $vehicle, ['inspection_id' => $inspection->id]);

            return $inspection;
        });
    }

    public function checkoutToManufacturer(Vehicle $vehicle, array $data, User $user): VehicleInspection
    {
        if ($vehicle->status === Vehicle::STATUS_LOANED) {
            throw new RuntimeException('Verliehene Fahrzeuge koennen nicht an den Hersteller ausgecheckt werden.');
        }

        return DB::transaction(function () use ($vehicle, $data, $user): VehicleInspection {
            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
                'type' => VehicleInspection::TYPE_MANUFACTURER_CHECKOUT,
                'km' => (int) $data['km'],
                'operating_hours' => (float) $data['operating_hours'],
                'condition_notes' => Arr::get($data, 'condition_notes'),
                'occurred_at' => now(),
                'location' => Arr::get($data, 'location', $vehicle->location),
                'external_partner' => Arr::get($data, 'external_partner'),
            ]);

            $this->storeDamageIfPresent($vehicle, $inspection, $data, $user);

            $vehicle->forceFill([
                'current_km' => max($vehicle->current_km, (int) $data['km']),
                'current_operating_hours' => max((float) $vehicle->current_operating_hours, (float) $data['operating_hours']),
                'status' => Vehicle::STATUS_CHECKED_OUT,
                'is_active' => false,
            ])->save();

            $this->auditLogger->log('vehicle.manufacturer_checkout', $vehicle, ['inspection_id' => $inspection->id]);

            return $inspection;
        });
    }

    public function loanOut(Vehicle $vehicle, array $data, User $user): array
    {
        if (! $vehicle->isAvailable()) {
            throw new RuntimeException('Dieses Fahrzeug ist aktuell nicht verfuegbar.');
        }

        return DB::transaction(function () use ($vehicle, $data, $user): array {
            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
                'type' => VehicleInspection::TYPE_LOAN_CHECKOUT,
                'km' => (int) $data['km'],
                'operating_hours' => (float) $data['operating_hours'],
                'condition_notes' => Arr::get($data, 'condition_notes'),
                'occurred_at' => now(),
                'location' => Arr::get($data, 'location', $vehicle->location),
            ]);

            $loan = Loan::create([
                'vehicle_id' => $vehicle->id,
                'borrower_type' => $data['borrower_type'],
                'driver_id' => Arr::get($data, 'driver_id'),
                'company_name' => Arr::get($data, 'company_name'),
                'borrower_name' => $data['borrower_name'],
                'phone' => Arr::get($data, 'phone'),
                'planned_return_at' => $data['planned_return_at'],
                'loaned_at' => now(),
                'checkout_inspection_id' => $inspection->id,
                'status' => Loan::STATUS_ACTIVE,
                'created_by' => $user->id,
            ]);

            $inspection->forceFill(['loan_id' => $loan->id])->save();
            $this->storeDamageIfPresent($vehicle, $inspection, $data, $user);

            $vehicle->forceFill([
                'current_km' => max($vehicle->current_km, (int) $data['km']),
                'current_operating_hours' => max((float) $vehicle->current_operating_hours, (float) $data['operating_hours']),
                'status' => Vehicle::STATUS_LOANED,
            ])->save();

            $this->auditLogger->log('loan.created', $loan, ['vehicle_id' => $vehicle->id]);

            return [$loan, $inspection];
        });
    }

    public function returnLoan(Loan $loan, array $data, User $user): VehicleInspection
    {
        if ($loan->status !== Loan::STATUS_ACTIVE) {
            throw new RuntimeException('Dieser Verleih ist nicht aktiv.');
        }

        return DB::transaction(function () use ($loan, $data, $user): VehicleInspection {
            $vehicle = $loan->vehicle()->lockForUpdate()->firstOrFail();

            $inspection = VehicleInspection::create([
                'vehicle_id' => $vehicle->id,
                'user_id' => $user->id,
                'loan_id' => $loan->id,
                'type' => VehicleInspection::TYPE_LOAN_RETURN,
                'km' => (int) $data['km'],
                'operating_hours' => (float) $data['operating_hours'],
                'condition_notes' => Arr::get($data, 'condition_notes'),
                'occurred_at' => now(),
                'location' => Arr::get($data, 'location', $vehicle->location),
            ]);

            $damage = $this->storeDamageIfPresent($vehicle, $inspection, $data, $user);

            $loan->forceFill([
                'returned_at' => now(),
                'return_inspection_id' => $inspection->id,
                'returned_by' => $user->id,
                'status' => Loan::STATUS_RETURNED,
            ])->save();

            $vehicle->forceFill([
                'current_km' => max($vehicle->current_km, (int) $data['km']),
                'current_operating_hours' => max((float) $vehicle->current_operating_hours, (float) $data['operating_hours']),
                'location' => Arr::get($data, 'location', $vehicle->location),
                'status' => $damage ? Vehicle::STATUS_DAMAGED : Vehicle::STATUS_AVAILABLE,
            ])->save();

            $this->auditLogger->log('loan.returned', $loan, ['vehicle_id' => $vehicle->id, 'inspection_id' => $inspection->id]);

            return $inspection;
        });
    }

    private function storeDamageIfPresent(Vehicle $vehicle, VehicleInspection $inspection, array $data, User $user): ?VehicleDamage
    {
        $description = trim((string) Arr::get($data, 'damage_description', ''));
        if ($description === '') {
            return null;
        }

        return VehicleDamage::create([
            'vehicle_id' => $vehicle->id,
            'inspection_id' => $inspection->id,
            'description' => $description,
            'severity' => Arr::get($data, 'damage_severity', 'minor'),
            'reported_by' => $user->id,
        ]);
    }
}
