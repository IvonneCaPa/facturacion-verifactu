<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'emisor_id',
        'nombre',
        'descripcion',
        'precio_unitario',
        'tipo_iva',
        'aplica_irpf',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'tipo_iva' => 'decimal:2',
            'aplica_irpf' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class);
    }
}