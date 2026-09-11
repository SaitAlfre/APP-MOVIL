<?php

namespace App\Infrastructure\Qr;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;

final class GeneradorQrPng
{
    public static function generar(string $contenido): ResultInterface
    {
        return (new Builder(
            writer: new PngWriter,
            data: $contenido,
            size: 300,
            margin: 10,
        ))->build();
    }
}
