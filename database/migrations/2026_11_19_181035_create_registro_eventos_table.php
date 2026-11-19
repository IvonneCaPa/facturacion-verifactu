<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emisor_id')->constrained('emisores')->restrictOnDelete();
            $table->string('tipo_evento', 50); // Ej: EMISION_FACTURA, RECTIFICACION, ERROR_AEAT, EXPORTACION
            $table->text('descripcion');
            $table->json('datos_contexto')->nullable();
            $table->string('fecha_hora_huso', 35);
            $table->string('hash_evento', 64);

            $table->index(['emisor_id', 'tipo_evento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_eventos');
    }
};