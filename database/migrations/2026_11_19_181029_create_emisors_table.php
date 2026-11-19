<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emisores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nif', 20);
            $table->string('razon_social');
            $table->string('nombre_comercial')->nullable();
            $table->string('direccion');
            $table->string('codigo_postal', 10);
            $table->string('municipio', 100);
            $table->string('provincia', 100);
            $table->string('email');
            $table->string('telefono', 30)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('iban', 34)->nullable();
            $table->decimal('irpf_por_defecto', 5, 2)->default(15.00);
            $table->boolean('verifactu_activo')->default(true);
            $table->string('certificado_path')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'nif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emisores');
    }
};