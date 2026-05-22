<?php

namespace App\Services;

use App\Models\Vehicle;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    public function svgForVehicle(Vehicle $vehicle): string
    {
        $options = new QROptions([
            'outputBase64' => false,
            'scale' => 6,
            'quietzoneSize' => 2,
        ]);

        return (new QRCode($options))->render($vehicle->qrUrl());
    }
}
