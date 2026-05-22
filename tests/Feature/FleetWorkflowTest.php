<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_can_be_loaned_and_returned(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $category = VehicleCategory::create([
            'name' => 'Golf Car',
            'slug' => 'golf-car',
        ]);

        $vehicle = Vehicle::create([
            'inventory_number' => 'GC-T-001',
            'vehicle_category_id' => $category->id,
            'manufacturer' => 'Club Car',
            'model' => 'Tempo',
            'current_km' => 10,
            'current_operating_hours' => 5,
            'status' => Vehicle::STATUS_AVAILABLE,
            'location' => 'Lager',
        ]);

        $this->actingAs($manager)->post(route('loans.store'), [
            'vehicle_id' => $vehicle->id,
            'borrower_type' => Loan::BORROWER_EXTERNAL,
            'company_name' => 'Subfirma Test',
            'borrower_name' => 'Erika Muster',
            'phone' => '+49 123',
            'planned_return_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'km' => 12,
            'operating_hours' => 6,
            'location' => 'Ausgabe',
            'condition_notes' => 'Ohne neue Schaeden.',
        ])->assertRedirect();

        $loan = Loan::firstOrFail();
        $this->assertSame(Loan::STATUS_ACTIVE, $loan->status);
        $this->assertSame(Vehicle::STATUS_LOANED, $vehicle->fresh()->status);

        $this->actingAs($manager)->post(route('loans.return', $loan), [
            'km' => 15,
            'operating_hours' => 8,
            'location' => 'Lager',
            'condition_notes' => 'Rueckgabe ohne Schaeden.',
        ])->assertRedirect();

        $this->assertSame(Loan::STATUS_RETURNED, $loan->fresh()->status);
        $this->assertSame(Vehicle::STATUS_AVAILABLE, $vehicle->fresh()->status);
        $this->assertSame(15, $vehicle->fresh()->current_km);
    }

    public function test_unavailable_vehicle_cannot_be_loaned_twice(): void
    {
        $manager = User::factory()->create([
            'role' => User::ROLE_MANAGER,
            'is_active' => true,
        ]);

        $category = VehicleCategory::create(['name' => 'Steiger', 'slug' => 'steiger']);
        $vehicle = Vehicle::create([
            'inventory_number' => 'ST-T-001',
            'vehicle_category_id' => $category->id,
            'model' => 'Teststeiger',
            'status' => Vehicle::STATUS_LOANED,
        ]);

        $this->actingAs($manager)->post(route('loans.store'), [
            'vehicle_id' => $vehicle->id,
            'borrower_type' => Loan::BORROWER_EXTERNAL,
            'company_name' => 'Subfirma Test',
            'borrower_name' => 'Erika Muster',
            'planned_return_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'km' => 0,
            'operating_hours' => 0,
        ])->assertSessionHasErrors('vehicle_id');
    }
}
