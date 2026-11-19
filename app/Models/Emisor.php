<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Emisor extends Model
{
    protected $table = 'emisores';

    protected $fillable = [
        'user_id',
        'nif',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'codigo_postal',
        'municipio',
        'provincia',
        'email',
        'telefono',
        'logo_path',
        'iban',
        'irpf_por_defecto',
        'verifactu_activo',
        'certificado_path',
    ];

    protected function casts(): array
    {
        return [
            'irpf_por_defecto' => 'decimal:2',
            'verifactu_activo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(SerieFacturacion::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(RegistroEvento::class);
    }
}