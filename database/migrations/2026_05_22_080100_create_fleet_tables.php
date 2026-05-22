<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('drivers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('department')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('inventory_number')->unique();
            $table->string('qr_token', 80)->unique();
            $table->foreignId('vehicle_category_id')->constrained()->cascadeOnUpdate();
            $table->string('manufacturer')->nullable();
            $table->string('model');
            $table->string('serial_number')->nullable()->index();
            $table->string('license_plate')->nullable()->index();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedInteger('current_km')->default(0);
            $table->decimal('current_operating_hours', 10, 1)->default(0);
            $table->string('status')->default('available')->index();
            $table->string('location')->nullable()->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_qr_scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vehicle_inspections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate();
            $table->foreignId('loan_id')->nullable()->index();
            $table->string('type')->index();
            $table->unsignedInteger('km')->default(0);
            $table->decimal('operating_hours', 10, 1)->default(0);
            $table->text('condition_notes')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->string('location')->nullable();
            $table->string('external_partner')->nullable();
            $table->timestamps();
        });

        Schema::create('loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnUpdate();
            $table->string('borrower_type')->index();
            $table->foreignId('driver_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('borrower_name');
            $table->string('phone')->nullable();
            $table->timestamp('planned_return_at');
            $table->timestamp('loaned_at');
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('checkout_inspection_id')->nullable()->constrained('vehicle_inspections')->nullOnDelete();
            $table->foreignId('return_inspection_id')->nullable()->constrained('vehicle_inspections')->nullOnDelete();
            $table->string('signature_disk')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('status')->default('active')->index();
            $table->foreignId('created_by')->constrained('users')->cascadeOnUpdate();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_damages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained('vehicle_inspections')->nullOnDelete();
            $table->text('description');
            $table->string('severity')->default('minor')->index();
            $table->boolean('is_repaired')->default(false)->index();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspection_id')->nullable()->constrained('vehicle_inspections')->nullOnDelete();
            $table->foreignId('damage_id')->nullable()->constrained('vehicle_damages')->nullOnDelete();
            $table->string('disk');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('caption')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vehicle_signatures', function (Blueprint $table): void {
            $table->id();
            $table->string('signable_type');
            $table->unsignedBigInteger('signable_id');
            $table->string('disk');
            $table->string('file_path');
            $table->string('signer_name');
            $table->string('signature_hash', 128);
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('signed_at');
            $table->timestamps();
            $table->index(['signable_type', 'signable_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->string('entity_type')->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('vehicle_signatures');
        Schema::dropIfExists('vehicle_photos');
        Schema::dropIfExists('vehicle_damages');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('vehicle_inspections');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('drivers');
        Schema::dropIfExists('vehicle_categories');
    }
};
