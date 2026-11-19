<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerieFacturacion extends Model
{
    protected $table = 'series_facturacion';

    protected $fillable = [
        'emisor_id',
        'prefijo',
        'ultimo_numero',
        'tipo', // ordinaria, rectificativa, simplificada
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_numero' => 'integer',
            'activa' => 'boolean',
        ];
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'serie_id');
    }
}