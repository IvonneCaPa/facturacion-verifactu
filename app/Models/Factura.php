<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Factura extends Model
{
    protected $table = 'facturas';

    protected $fillable = [
        'emisor_id',
        'cliente_id',
        'serie_id',
        'factura_rectificada_id',
        'numero_completo',
        'tipo_factura', // F1 (ordinaria), F2 (simplificada), R1..R5 (rectificativa)
        'fecha_expedicion',
        'fecha_operacion',
        'estado', // borrador, emitida, rectificada
        'base_imponible',
        'cuota_iva',
        'porcentaje_irpf',
        'cuota_irpf',
        'total_factura',
        'hash_anterior',
        'hash_actual',
        'fecha_hora_huso_gen',
        'qr_url',
        'xml_verifactu',
        'estado_aeat',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_expedicion' => 'date',
            'fecha_operacion' => 'date',
            'base_imponible' => 'decimal:2',
            'cuota_iva' => 'decimal:2',
            'porcentaje_irpf' => 'decimal:2',
            'cuota_irpf' => 'decimal:2',
            'total_factura' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        // Impide borrar cualquier factura emitida o rectificada
        static::deleting(function (Factura $factura): void {
            if ($factura->estado !== 'borrador') {
                throw new RuntimeException('Normativa Veri*Factu: No está permitido eliminar una factura emitida.');
            }
        });

        // Impide alterar campos fiscales o criptográficos una vez emitida
        static::updating(function (Factura $factura): void {
            if ($factura->getOriginal('estado') !== 'borrador') {
                $camposProtegidos = [
                    'emisor_id',
                    'cliente_id',
                    'serie_id',
                    'numero_completo',
                    'tipo_factura',
                    'fecha_expedicion',
                    'base_imponible',
                    'cuota_iva',
                    'porcentaje_irpf',
                    'cuota_irpf',
                    'total_factura',
                    'hash_anterior',
                    'hash_actual',
                    'fecha_hora_huso_gen',
                ];

                if ($factura->isDirty($camposProtegidos)) {
                    throw new RuntimeException('Normativa Veri*Factu: Los datos fiscales y la huella de una factura emitida son inalterables.');
                }
            }
        });
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function serie(): BelongsTo
    {
        return $this->belongsTo(SerieFacturacion::class, 'serie_id');
    }

    public function facturaRectificada(): BelongsTo
    {
        return $this->belongsTo(self::class, 'factura_rectificada_id');
    }

    public function rectificativas(): HasMany
    {
        return $this->hasMany(self::class, 'factura_rectificada_id');
    }

    public function lineas(): HasMany
    {
        return $this->hasMany(LineaFactura::class);
    }
}