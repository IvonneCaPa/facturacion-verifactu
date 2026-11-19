<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

class RegistroEvento extends Model
{
    protected $table = 'registro_eventos';

    public $timestamps = false;

    protected $fillable = [
        'emisor_id',
        'tipo_evento',
        'descripcion',
        'datos_contexto',
        'fecha_hora_huso',
        'hash_evento',
    ];

    protected function casts(): array
    {
        return [
            'datos_contexto' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new RuntimeException('Normativa Veri*Factu: El registro de eventos del sistema es inmutable.');
        });

        static::deleting(function (): void {
            throw new RuntimeException('Normativa Veri*Factu: No se pueden eliminar registros de auditoría.');
        });
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class);
    }
}