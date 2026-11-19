<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emisor_id')->constrained('emisores')->restrictOnDelete();
            $table->string('nif', 25);
            $table->string('nombre_razon_social');
            $table->string('direccion');
            $table->string('codigo_postal', 15);
            $table->string('municipio', 100);
            $table->string('provincia', 100)->nullable();
            $table->string('pais', 2)->default('ES'); // Código ISO 3166-1 alfa-2
            $table->string('email')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('es_profesional_o_empresa')->default(true);
            $table->timestamps();

            $table->index(['emisor_id', 'nif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};