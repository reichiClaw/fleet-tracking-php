<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleDamage extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'inspection_id', 'description', 'severity', 'is_repaired', 'reported_by',
    ];

    protected function casts(): array
    {
        return ['is_repaired' => 'boolean'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'inspection_id');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(VehiclePhoto::class, 'damage_id');
    }
}
