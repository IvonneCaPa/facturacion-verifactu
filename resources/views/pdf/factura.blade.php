<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $factura->numero_completo }}</title>
    <style>
        @page {
            margin: 32px 36px;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10.5px;
            color: #1f2937;
            line-height: 1.45;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #6b7280; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        /* Cabecera Veri*Factu + Emisor */
        .header-table {
            margin-bottom: 24px;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
        }
        .qr-box {
            width: 145px;
            vertical-align: top;
        }
        .qr-legend {
            font-size: 7.5px;
            font-weight: bold;
            color: #111827;
            margin-top: 4px;
            line-height: 1.25;
            max-width: 140px;
        }
        .emisor-box {
            vertical-align: top;
            text-align: right;
        }
        .emisor-logo {
            max-height: 58px;
            max-width: 180px;
            margin-bottom: 8px;
        }
        .emisor-title {
            font-size: 14px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 2px 0;
        }

        /* Metadatos de factura y Cliente */
        .meta-section {
            margin-bottom: 22px;
        }
        .box-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px 14px;
            vertical-align: top;
        }
        .section-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 6px;
        }
        .invoice-number {
            font-size: 15px;
            font-weight: bold;
            color: #111827;
            margin-bottom: 4px;
        }

        /* Alerta rectificativa */
        .rectificativa-banner {
            background-color: #fef3c7;
            border-left: 3px solid #d97706;
            padding: 8px 12px;
            margin-bottom: 18px;
            font-size: 9.5px;
            color: #92400e;
        }

        /* Tabla de líneas */
        .items-table {
            margin-bottom: 22px;
        }
        .items-table th {
            background-color: #111827;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .item-desc {
            font-size: 9px;
            color: #4b5563;
            margin-top: 2px;
        }

        /* Totales e IBAN */
        .totals-section {
            margin-top: 10px;
        }
        .payment-box {
            width: 54%;
            vertical-align: top;
            padding-right: 18px;
        }
        .totals-box {
            width: 46%;
            vertical-align: top;
        }
        .totals-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f3f4f6;
        }
        .totals-table tr.grand-total td {
            background-color: #111827;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 9px 10px;
            border-bottom: none;
        }

        /* Pie legal y huella */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            font-size: 7.5px;
            color: #6b7280;
        }
        .hash-mono {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 7px;
            color: #4b5563;
        }
    </style>
</head>
<body>

    {{-- CABECERA: QR AEAT + DATOS DEL AUTÓNOMO EMISOR --}}
    <table class="header-table">
        <tr>
            <td class="qr-box">
                <div style="font-size: 8px; font-weight: bold; margin-bottom: 3px; letter-spacing: 0.4px;">
                    QR TRIBUTARIO:
                </div>
                <img src="{{ $qrBase64 }}" alt="QR Verificación AEAT" style="width: 125px; height: 125px;">
                <div class="qr-legend">
                    {{ $leyendaVerifactu }}
                </div>
            </td>
            <td class="emisor-box">
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" alt="Logo {{ $emisor->nombre_comercial ?? $emisor->razon_social }}" class="emisor-logo"><br>
                @endif

                <div class="emisor-title">
                    {{ $emisor->nombre_comercial ?: $emisor->razon_social }}
                </div>
                @if($emisor->nombre_comercial && $emisor->nombre_comercial !== $emisor->razon_social)
                    <div class="text-muted">{{ $emisor->razon_social }}</div>
                @endif
                <div><strong>NIF:</strong> {{ $emisor->nif }}</div>
                <div>{{ $emisor->direccion }}</div>
                <div>{{ $emisor->codigo_postal }} {{ $emisor->municipio }} ({{ $emisor->provincia }})</div>
                <div>{{ $emisor->email }} @if($emisor->telefono) · {{ $emisor->telefono }} @endif</div>
            </td>
        </tr>
    </table>

    {{-- AVISO SI ES FACTURA RECTIFICATIVA --}}
    @if($factura->facturaRectificada)
        <div class="rectificativa-banner">
            <strong>FACTURA RECTIFICATIVA ({{ $factura->tipo_factura }}):</strong>
            Este documento rectifica a la factura nº <strong>{{ $factura->facturaRectificada->numero_completo }}</strong>
            expedida con fecha {{ $factura->facturaRectificada->fecha_expedicion->format('d/m/Y') }}.
        </div>
    @endif

    {{-- DATOS DE LA FACTURA Y DEL CLIENTE --}}
    <table class="meta-section">
        <tr>
            <td class="box-card" style="width: 48%;">
                <div class="section-label">Datos de Expedición</div>
                <div class="invoice-number">FACTURA {{ $factura->numero_completo }}</div>
                <div><strong>Fecha de expedición:</strong> {{ $factura->fecha_expedicion->format('d/m/Y') }}</div>
                @if($factura->fecha_operacion)
                    <div><strong>Fecha de operación:</strong> {{ $factura->fecha_operacion->format('d/m/Y') }}</div>
                @endif
                <div><strong>Tipo de factura:</strong> {{ $factura->tipo_factura }}</div>
            </td>
            <td style="width: 4%;"></td>
            <td class="box-card" style="width: 48%;">
                <div class="section-label">Facturar a (Cliente)</div>
                <div class="font-bold" style="font-size: 11.5px; margin-bottom: 2px;">
                    {{ $cliente->nombre_razon_social }}
                </div>
                <div><strong>NIF / CIF:</strong> {{ $cliente->nif }}</div>
                <div>{{ $cliente->direccion }}</div>
                <div>{{ $cliente->codigo_postal }} {{ $cliente->municipio }} @if($cliente->provincia) ({{ $cliente->provincia }}) @endif</div>
                @if($cliente->email)
                    <div class="text-muted">{{ $cliente->email }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- DETALLE DE SERVICIOS / CONCEPTOS --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 44%;">Concepto / Servicio</th>
                <th class="text-right" style="width: 10%;">Cant.</th>
                <th class="text-right" style="width: 15%;">Precio</th>
                <th class="text-right" style="width: 10%;">Dto.</th>
                <th class="text-right" style="width: 9%;">IVA</th>
                <th class="text-right" style="width: 12%;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineas as $linea)
                <tr>
                    <td>
                        <div class="font-bold">{{ $linea->concepto }}</div>
                        @if($linea->descripcion)
                            <div class="item-desc">{{ $linea->descripcion }}</div>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float) $linea->cantidad, 2, ',', '.') }}</td>
                    <td class="text-right">{{ number_format((float) $linea->precio_unitario, 2, ',', '.') }} €</td>
                    <td class="text-right">
                        {{ (float) $linea->descuento_porcentaje > 0 ? number_format((float) $linea->descuento_porcentaje, 1, ',', '.') . '%' : '—' }}
                    </td>
                    <td class="text-right">{{ number_format((float) $linea->tipo_iva, 0) }}%</td>
                    <td class="text-right font-bold">{{ number_format((float) $linea->subtotal_base, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- FORMA DE PAGO Y DESGLOSE DE TOTALES --}}
    <table class="totals-section">
        <tr>
            <td class="payment-box">
                @if($emisor->iban)
                    <div class="box-card">
                        <div class="section-label">Datos para el pago (Transferencia)</div>
                        <div><strong>Titular:</strong> {{ $emisor->razon_social }}</div>
                        <div><strong>IBAN:</strong> <span style="font-family: 'DejaVu Sans Mono', monospace;">{{ $emisor->iban }}</span></div>
                        <div class="text-muted" style="font-size: 9px; margin-top: 3px;">
                            Indique el número <strong>{{ $factura->numero_completo }}</strong> en el concepto.
                        </div>
                    </div>
                @endif

                @if($factura->notas)
                    <div style="margin-top: 10px; font-size: 9.5px;" class="text-muted">
                        <strong>Observaciones:</strong><br>
                        {{ $factura->notas }}
                    </div>
                @endif
            </td>

            <td class="totals-box">
                <table class="totals-table">
                    <tr>
                        <td>Base Imponible</td>
                        <td class="text-right font-bold">{{ number_format((float) $factura->base_imponible, 2, ',', '.') }} €</td>
                    </tr>
                    <tr>
                        <td>Cuota IVA</td>
                        <td class="text-right">+ {{ number_format((float) $factura->cuota_iva, 2, ',', '.') }} €</td>
                    </tr>
                    @if((float) $factura->cuota_irpf > 0)
                        <tr>
                            <td>Retención IRPF ({{ number_format((float) $factura->porcentaje_irpf, 0) }}%)</td>
                            <td class="text-right" style="color: #b91c1c;">- {{ number_format((float) $factura->cuota_irpf, 2, ',', '.') }} €</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td>TOTAL FACTURA</td>
                        <td class="text-right">{{ number_format((float) $factura->total_factura, 2, ',', '.') }} €</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- PIE DE PÁGINA CON TRAZABILIDAD CRIPTOGRÁFICA --}}
    <div class="footer">
        <table>
            <tr>
                <td style="width: 75%;">
                    Huella registro (SHA-256): <span class="hash-mono">{{ $factura->hash_actual }}</span><br>
                    Generado el {{ $factura->fecha_hora_huso_gen }} · {{ $leyendaVerifactu }}
                </td>
                <td class="text-right" style="width: 25%; vertical-align: bottom;">
                    Documento inalterable
                </td>
            </tr>
        </table>
    </div>

</body>
</html>