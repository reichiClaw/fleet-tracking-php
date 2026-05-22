<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Loan extends Model
{
    use HasFactory;

    public const BORROWER_INTERNAL = 'internal_driver';
    public const BORROWER_EXTERNAL = 'external_company';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'vehicle_id', 'borrower_type', 'driver_id', 'company_name', 'borrower_name', 'phone',
        'planned_return_at', 'loaned_at', 'returned_at', 'checkout_inspection_id',
        'return_inspection_id', 'signature_path', 'signature_disk', 'status', 'created_by', 'returned_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_return_at' => 'datetime',
            'loaned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function checkoutInspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'checkout_inspection_id');
    }

    public function returnInspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'return_inspection_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->planned_return_at?->isPast();
    }
}
