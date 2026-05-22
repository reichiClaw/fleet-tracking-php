<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id', 'inspection_id', 'damage_id', 'disk', 'file_path', 'original_name',
        'mime_type', 'size_bytes', 'caption', 'uploaded_by',
    ];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(VehicleInspection::class, 'inspection_id');
    }

    public function damage(): BelongsTo
    {
        return $this->belongsTo(VehicleDamage::class, 'damage_id');
    }
}
