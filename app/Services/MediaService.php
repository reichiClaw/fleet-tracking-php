<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Vehicle;
use App\Models\VehicleInspection;
use App\Models\VehiclePhoto;
use App\Models\VehicleSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MediaService
{
    public function disk(): string
    {
        return config('filesystems.fleet_disk', env('FLEET_STORAGE_DISK', 'fleet_private'));
    }

    public function storePhotos(Request $request, Vehicle $vehicle, ?VehicleInspection $inspection = null): void
    {
        if (! $request->hasFile('photos')) {
            return;
        }

        foreach ($request->file('photos') as $photo) {
            $path = $photo->store('fleet/photos/'.$vehicle->id, $this->disk());

            VehiclePhoto::create([
                'vehicle_id' => $vehicle->id,
                'inspection_id' => $inspection?->id,
                'disk' => $this->disk(),
                'file_path' => $path,
                'original_name' => $photo->getClientOriginalName(),
                'mime_type' => $photo->getMimeType(),
                'size_bytes' => $photo->getSize(),
                'caption' => $request->input('photo_caption'),
                'uploaded_by' => $request->user()?->id,
            ]);
        }
    }

    public function storeSignatureDataUrl(Request $request, Loan $loan, string $field = 'signature_data'): ?VehicleSignature
    {
        $dataUrl = (string) $request->input($field, '');
        if ($dataUrl === '') {
            return null;
        }

        if (! str_starts_with($dataUrl, 'data:image/png;base64,')) {
            throw new InvalidArgumentException('Die Signatur muss als PNG Data-URL uebergeben werden.');
        }

        $binary = base64_decode(substr($dataUrl, strlen('data:image/png;base64,')), true);
        if ($binary === false) {
            throw new InvalidArgumentException('Die Signatur konnte nicht gelesen werden.');
        }

        $path = 'fleet/signatures/'.$loan->id.'/'.Str::uuid().'.png';
        Storage::disk($this->disk())->put($path, $binary);
        $hash = hash('sha256', $binary);

        $loan->forceFill([
            'signature_disk' => $this->disk(),
            'signature_path' => $path,
        ])->save();

        return VehicleSignature::create([
            'signable_type' => Loan::class,
            'signable_id' => $loan->id,
            'disk' => $this->disk(),
            'file_path' => $path,
            'signer_name' => $request->input('borrower_name', $loan->borrower_name),
            'signature_hash' => $hash,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'signed_at' => now(),
        ]);
    }
}
