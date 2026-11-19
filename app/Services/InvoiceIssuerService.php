<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Factura;
use App\Models\RegistroEvento;
use App\Models\SerieFacturacion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InvoiceIssuerService
{
    public function __construct(
        private readonly VerifactuHashService $hashService,
        private readonly AeatQrService $qrService,
    ) {}

    /**
     * Emite una factura en borrador de forma atómica e inmutable según Veri*Factu.
     */
    public function issue(Factura $factura, bool $entornoPruebas = true): Factura
    {
        if ($factura->estado !== 'borrador') {
            throw new RuntimeException('Solo se pueden emitir facturas que estén en estado borrador.');
        }

        $factura->loadMissing(['emisor', 'cliente', 'lineas']);

        if ($factura->lineas->isEmpty()) {
            throw new RuntimeException('No se puede emitir una factura sin líneas de detalle.');
        }

        return DB::transaction(function () use ($factura, $entornoPruebas): Factura {
            // 1. Bloquear la serie de facturación para evitar duplicados o huecos por concurrencia
            /** @var SerieFacturacion $serie */
            $serie = SerieFacturacion::where('id', $factura->serie_id)
                ->where('emisor_id', $factura->emisor_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $serie->activa) {
                throw new RuntimeException("La serie de facturación {$serie->prefijo} está inactiva.");
            }

            // 2. Calcular totales fiscales a partir de las líneas
            $totales = $this->calculateTotals($factura);

            // 3. Incrementar contador de serie y formatear número (ej. F2026-0001)
            $nuevoNumero = $serie->ultimo_numero + 1;
            $numeroCompleto = $serie->prefijo . str_pad((string) $nuevoNumero, 4, '0', STR_PAD_LEFT);

            // 4. Obtener el hash de la última factura emitida por este mismo autónomo (bloqueando lectura)
            $ultimaFacturaEmitida = Factura::where('emisor_id', $factura->emisor_id)
                ->whereIn('estado', ['emitida', 'rectificada'])
                ->whereNotNull('hash_actual')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $hashAnterior = $ultimaFacturaEmitida?->hash_actual;

            // 5. Generar sello de tiempo ISO 8601 con huso horario de España
            $ahora = CarbonImmutable::now('Europe/Madrid');
            $fechaHoraHusoGen = $ahora->toIso8601String();
            $fechaExpedicion = $factura->fecha_expedicion ?? $ahora;

            // 6. Calcular huella SHA-256 encadenada
            $hashActual = $this->hashService->generateInvoiceHash(
                nifEmisor: $factura->emisor->nif,
                numSerieFactura: $numeroCompleto,
                fechaExpedicion: $fechaExpedicion->format('d-m-Y'),
                tipoFactura: $factura->tipo_factura,
                cuotaTotal: $totales['cuota_iva'],
                importeTotal: $totales['total_factura'],
                huellaAnterior: $hashAnterior,
                fechaHoraHusoGen: $fechaHoraHusoGen
            );

            // 7. Actualizar datos fiscales de la factura (aún en estado borrador para pasar la validación del modelo)
            $factura->fill([
                'numero_completo' => $numeroCompleto,
                'fecha_expedicion' => $fechaExpedicion->toDateString(),
                'base_imponible' => $totales['base_imponible'],
                'cuota_iva' => $totales['cuota_iva'],
                'porcentaje_irpf' => $totales['porcentaje_irpf'],
                'cuota_irpf' => $totales['cuota_irpf'],
                'total_factura' => $totales['total_factura'],
                'hash_anterior' => $hashAnterior,
                'hash_actual' => $hashActual,
                'fecha_hora_huso_gen' => $fechaHoraHusoGen,
                'estado_aeat' => $factura->emisor->verifactu_activo ? 'pendiente' : 'local',
            ]);

            // 8. Construir URL oficial del QR verificable de la AEAT
            $factura->qr_url = $this->qrService->buildVerificationUrl($factura, $entornoPruebas);
            $factura->estado = 'emitida';
            $factura->save();

            // 9. Persistir el nuevo contador en la serie
            $serie->update(['ultimo_numero' => $nuevoNumero]);

            // 10. Registrar evento inmutable en el log de auditoría del sistema
            $this->logIssuanceEvent($factura, $fechaHoraHusoGen);

            return $factura->fresh(['emisor', 'cliente', 'serie', 'lineas']);
        });
    }

    /**
     * Calcula base imponible, cuota IVA, retención IRPF y total a pagar.
     *
     * @return array{base_imponible: float, cuota_iva: float, porcentaje_irpf: float, cuota_irpf: float, total_factura: float}
     */
    private function calculateTotals(Factura $factura): array
    {
        $baseTotal = 0.0;
        $ivaTotal = 0.0;
        $baseSujetaIrpf = 0.0;

        $clienteConRetencion = (bool) $factura->cliente->es_profesional_o_empresa;
        $porcentajeIrpf = $clienteConRetencion
            ? (float) ($factura->porcentaje_irpf > 0 ? $factura->porcentaje_irpf : $factura->emisor->irpf_por_defecto)
            : 0.0;

        foreach ($factura->lineas as $linea) {
            $brutoLinea = (float) $linea->cantidad * (float) $linea->precio_unitario;
            $descuento = $brutoLinea * ((float) $linea->descuento_porcentaje / 100);
            $subtotalLinea = round($brutoLinea - $descuento, 2);

            // Actualizar subtotal congelado de la línea si difiere
            if ((float) $linea->subtotal_base !== $subtotalLinea) {
                $linea->update(['subtotal_base' => $subtotalLinea]);
            }

            $ivaLinea = round($subtotalLinea * ((float) $linea->tipo_iva / 100), 2);

            $baseTotal += $subtotalLinea;
            $ivaTotal += $ivaLinea;

            if ($clienteConRetencion && $linea->aplica_irpf) {
                $baseSujetaIrpf += $subtotalLinea;
            }
        }

        $baseTotal = round($baseTotal, 2);
        $ivaTotal = round($ivaTotal, 2);
        $cuotaIrpf = round($baseSujetaIrpf * ($porcentajeIrpf / 100), 2);
        $totalFactura = round($baseTotal + $ivaTotal - $cuotaIrpf, 2);

        return [
            'base_imponible' => $baseTotal,
            'cuota_iva' => $ivaTotal,
            'porcentaje_irpf' => round($porcentajeIrpf, 2),
            'cuota_irpf' => $cuotaIrpf,
            'total_factura' => $totalFactura,
        ];
    }

    /**
     * Graba el evento de emisión en la tabla de auditoría encadenada.
     */
    private function logIssuanceEvent(Factura $factura, string $fechaHoraHuso): void
    {
        $ultimoEvento = RegistroEvento::where('emisor_id', $factura->emisor_id)
            ->orderByDesc('id')
            ->first();

        $hashEvento = $this->hashService->generateEventHash(
            nifEmisor: $factura->emisor->nif,
            tipoEvento: 'EMISION_FACTURA',
            fechaHoraHuso: $fechaHoraHuso,
            hashEventoAnterior: $ultimoEvento?->hash_evento
        );

        RegistroEvento::create([
            'emisor_id' => $factura->emisor_id,
            'tipo_evento' => 'EMISION_FACTURA',
            'descripcion' => "Emisión de factura {$factura->numero_completo} con huella {$factura->hash_actual}",
            'datos_contexto' => [
                'factura_id' => $factura->id,
                'numero_completo' => $factura->numero_completo,
                'total_factura' => $factura->total_factura,
                'hash_actual' => $factura->hash_actual,
                'hash_anterior' => $factura->hash_anterior,
            ],
            'fecha_hora_huso' => $fechaHoraHuso,
            'hash_evento' => $hashEvento,
        ]);
    }
}