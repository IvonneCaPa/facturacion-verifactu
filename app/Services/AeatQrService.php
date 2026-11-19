<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Factura;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

final class AeatQrService
{
    /**
     * Construye la URL oficial de cotejo exigida por el Reglamento Veri*Factu.
     */
    public function buildVerificationUrl(Factura $factura, bool $entornoPruebas = true): string
    {
        $baseUrl = $entornoPruebas
            ? 'https://prewww2.aeat.es/wlpl/TIKE-CONT/ValidarQR'
            : 'https://www2.aeat.es/wlpl/TIKE-CONT/ValidarQR';

        $params = http_build_query([
            'nif' => strtoupper(trim($factura->emisor->nif)),
            'numserie' => trim($factura->numero_completo),
            'fecha' => $factura->fecha_expedicion->format('d-m-Y'),
            'importe' => number_format((float) $factura->total_factura, 2, '.', ''),
        ]);

        return "{$baseUrl}?{$params}";
    }

    /**
     * Renderiza el código QR en formato SVG (ideal para Blade y Dompdf sin perder nitidez).
     */
    public function generateSvgQr(string $qrUrl, int $size = 140): string
    {
        return (string) QrCode::format('svg')
            ->size($size)
            ->margin(1)
            ->errorCorrection('M')
            ->generate($qrUrl);
    }
}