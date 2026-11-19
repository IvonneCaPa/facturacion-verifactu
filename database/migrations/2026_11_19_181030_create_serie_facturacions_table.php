<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series_facturacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emisor_id')->constrained('emisores')->restrictOnDelete();
            $table->string('prefijo', 20); // Ej: F2026-, R2026-
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->enum('tipo', ['ordinaria', 'rectificativa', 'simplificada'])->default('ordinaria');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['emisor_id', 'prefijo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series_facturacion');
    }
};