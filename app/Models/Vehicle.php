<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Vehicle extends Model
{
    use HasFactory;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_LOANED = 'loaned';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_CHECKED_OUT = 'checked_out_to_manufacturer';
    public const STATUS_INACTIVE = 'inactive';

    public const STATUSES = [
        self::STATUS_AVAILABLE => 'Verfuegbar',
        self::STATUS_LOANED => 'Verliehen',
        self::STATUS_DAMAGED => 'Beschaedigt',
        self::STATUS_MAINTENANCE => 'Wartung',
        self::STATUS_CHECKED_OUT => 'An Hersteller ausgecheckt',
        self::STATUS_INACTIVE => 'Inaktiv',
    ];

    protected $fillable = [
        'inventory_number', 'qr_token', 'vehicle_category_id', 'manufacturer', 'model',
        'serial_number', 'license_plate', 'year', 'current_km', 'current_operating_hours',
        'status', 'location', 'notes', 'is_active', 'last_qr_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_qr_scanned_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Vehicle $vehicle): void {
            $vehicle->qr_token = $vehicle->qr_token ?: Str::random(40);
            $vehicle->status = $vehicle->status ?: self::STATUS_AVAILABLE;
            $vehicle->is_active = $vehicle->is_active ?? true;
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(VehicleInspection::class)->latest('occurred_at');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class)->latest('loaned_at');
    }

    public function activeLoan()
    {
        return $this->hasOne(Loan::class)->where('status', Loan::STATUS_ACTIVE)->latestOfMany();
    }

    public function damages(): HasMany
    {
        return $this->hasMany(VehicleDamage::class)->latest();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class)->latest();
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->is_active;
    }

    public function qrUrl(): string
    {
        return route('vehicles.scan', $this->qr_token);
    }
}
