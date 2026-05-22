<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleInspection extends Model
{
    use HasFactory;

    public const TYPE_ARRIVAL_CHECKIN = 'arrival_checkin';
    public const TYPE_LOAN_CHECKOUT = 'loan_checkout';
    public const TYPE_LOAN_RETURN = 'loan_return';
    public const TYPE_MANUFACTURER_CHECKOUT = 'manufacturer_checkout';
    public const TYPE_DAMAGE_REPORT = 'damage_report';

    protected $fillable = [
        'vehicle_id', 'user_id', 'loan_id', 'type', 'km', 'operating_hours', 'condition_notes',
        'occurred_at', 'location', 'external_partner',
    ];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function damages(): HasMany
    {
        return $this->hasMany(VehicleDamage::class, 'inspection_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class, 'inspection_id');
    }
}
