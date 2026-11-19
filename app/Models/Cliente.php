<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $fillable = [
        'emisor_id',
        'nif',
        'nombre_razon_social',
        'direccion',
        'codigo_postal',
        'municipio',
        'provincia',
        'pais',
        'email',
        'telefono',
        'es_profesional_o_empresa',
    ];

    protected function casts(): array
    {
        return [
            'es_profesional_o_empresa' => 'boolean',
        ];
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }
}