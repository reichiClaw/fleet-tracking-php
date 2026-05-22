<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleSignature extends Model
{
    use HasFactory;

    protected $fillable = [
        'signable_type', 'signable_id', 'disk', 'file_path', 'signer_name', 'signature_hash',
        'ip_address', 'user_agent', 'signed_at',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }
}
