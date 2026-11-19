<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class PdfInvoiceService
{
    public function __construct(
        private readonly AeatQrService $qrService,
    ) {}

    /**
     * Construye la instancia PDF de la factura lista para stream() o download().
     */
    public function buildPdf(Factura $factura): DomPdfWrapper
    {
        if ($factura->estado === 'borrador') {
            throw new RuntimeException('Normativa Veri*Factu: No se puede generar un PDF oficial de una factura en estado borrador.');
        }

        $factura->loadMissing(['emisor', 'cliente', 'serie', 'lineas', 'facturaRectificada']);

        // 1. Generar el QR oficial de la AEAT en SVG y codificarlo en Base64 para Dompdf
        $qrSvg = $this->qrService->generateSvgQr((string) $factura->qr_url, 135);
        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        // 2. Resolver logotipo dinámico del autónomo en Base64 si existe en storage
        $logoBase64 = $this->resolveLogoBase64($factura->emisor->logo_path);

        // 3. Determinar leyenda legal según modalidad (Veri*Factu envío vs. local)
        $leyendaVerifactu = $factura->emisor->verifactu_activo
            ? 'Factura verificable en la sede electrónica de la AEAT · VERI*FACTU'
            : 'Registro de facturación generado mediante sistema informático certificado (RD 1007/2023)';

        return Pdf::loadView('pdf.factura', [
            'factura' => $factura,
            'emisor' => $factura->emisor,
            'cliente' => $factura->cliente,
            'lineas' => $factura->lineas,
            'qrBase64' => $qrBase64,
            'logoBase64' => $logoBase64,
            'leyendaVerifactu' => $leyendaVerifactu,
        ])->setPaper('a4', 'portrait');
    }

    /**
     * Descarga el archivo PDF con nombre normalizado (ej. Factura_F2026-0001.pdf).
     */
    public function download(Factura $factura)
    {
        $filename = sprintf('Factura_%s.pdf', preg_replace('/[^A-Za-z0-9\-_]/', '_', $factura->numero_completo));

        return $this->buildPdf($factura)->download($filename);
    }

    /**
     * Muestra el PDF directamente en el navegador.
     */
    public function stream(Factura $factura)
    {
        $filename = sprintf('Factura_%s.pdf', preg_replace('/[^A-Za-z0-9\-_]/', '_', $factura->numero_completo));

        return $this->buildPdf($factura)->stream($filename);
    }

    private function resolveLogoBase64(?string $logoPath): ?string
    {
        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $contenido = Storage::disk('public')->get($logoPath);
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));

        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };

        return "data:{$mime};base64," . base64_encode((string) $contenido);
    }
}