<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class LineaFactura extends Model
{
    protected $table = 'lineas_factura';

    protected $fillable = [
        'factura_id',
        'servicio_id',
        'concepto',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'descuento_porcentaje',
        'tipo_iva',
        'aplica_irpf',
        'subtotal_base',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'descuento_porcentaje' => 'decimal:2',
            'tipo_iva' => 'decimal:2',
            'aplica_irpf' => 'boolean',
            'subtotal_base' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (LineaFactura $linea): void {
            if ($linea->factura && $linea->factura->estado !== 'borrador') {
                throw new RuntimeException('Normativa Veri*Factu: No se pueden añadir ni modificar líneas de una factura ya emitida.');
            }
        });

        static::deleting(function (LineaFactura $linea): void {
            if ($linea->factura && $linea->factura->estado !== 'borrador') {
                throw new RuntimeException('Normativa Veri*Factu: No se pueden eliminar líneas de una factura ya emitida.');
            }
        });
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class);
    }
}