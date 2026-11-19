<?php

declare(strict_types=1);

namespace App\Services;

final class VerifactuHashService
{
    /**
     * Genera la huella SHA-256 en mayúsculas según las especificaciones técnicas de Veri*Factu.
     */
    public function generateInvoiceHash(
        string $nifEmisor,
        string $numSerieFactura,
        string $fechaExpedicion, // Formato obligatorio: DD-MM-YYYY
        string $tipoFactura,     // F1, F2, R1, R2, R3, R4, R5
        float $cuotaTotal,       // Cuota IVA total
        float $importeTotal,     // Total factura
        ?string $huellaAnterior, // Hash de la factura anterior del mismo emisor (vacío si es la primera)
        string $fechaHoraHusoGen // Formato ISO 8601 con huso horario (ej. 2026-11-19T19:40:00+01:00)
    ): string {
        $cadena = sprintf(
            'IDEmisorFactura=%s&NumSerieFactura=%s&FechaExpedicionFactura=%s&TipoFactura=%s&CuotaTotal=%.2f&ImporteTotal=%.2f&Huella=%s&FechaHoraHusoGenRegistro=%s',
            strtoupper(trim($nifEmisor)),
            trim($numSerieFactura),
            trim($fechaExpedicion),
            strtoupper(trim($tipoFactura)),
            $cuotaTotal,
            $importeTotal,
            trim($huellaAnterior ?? ''),
            trim($fechaHoraHusoGen)
        );

        return strtoupper(hash('sha256', $cadena));
    }

    /**
     * Genera la huella de auditoría para la tabla registro_eventos.
     */
    public function generateEventHash(
        string $nifEmisor,
        string $tipoEvento,
        string $fechaHoraHuso,
        ?string $hashEventoAnterior
    ): string {
        $cadena = sprintf(
            'NIF=%s&Evento=%s&Fecha=%s&Anterior=%s',
            strtoupper(trim($nifEmisor)),
            strtoupper(trim($tipoEvento)),
            trim($fechaHoraHuso),
            trim($hashEventoAnterior ?? '')
        );

        return strtoupper(hash('sha256', $cadena));
    }
}