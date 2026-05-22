<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password'), 'role' => User::ROLE_ADMIN, 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'verwaltung@example.com'],
            ['name' => 'Verwaltung', 'password' => Hash::make('password'), 'role' => User::ROLE_MANAGER, 'is_active' => true]
        );

        foreach (['Steiger', 'Golf Car', 'Lader', 'Teleskoplader', 'Hebebuehne', 'Sonstige'] as $index => $name) {
            VehicleCategory::firstOrCreate(['name' => $name], ['slug' => str($name)->slug(), 'sort_order' => $index + 1]);
        }

        Driver::firstOrCreate(
            ['email' => 'max.mustermann@example.com'],
            ['name' => 'Max Mustermann', 'company' => 'Intern', 'department' => 'Betrieb', 'phone' => '+49 000 000000']
        );

        $category = VehicleCategory::where('name', 'Golf Car')->first();
        if ($category) {
            Vehicle::firstOrCreate(
                ['inventory_number' => 'GC-001'],
                [
                    'vehicle_category_id' => $category->id,
                    'manufacturer' => 'Club Car',
                    'model' => 'Tempo',
                    'serial_number' => 'DEMO-GC-001',
                    'current_km' => 0,
                    'current_operating_hours' => 0,
                    'status' => Vehicle::STATUS_AVAILABLE,
                    'location' => 'Hauptlager',
                    'is_active' => true,
                ]
            );
        }
    }
}
