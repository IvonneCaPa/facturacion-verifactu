<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('emisor_id')->constrained('emisores')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('serie_id')->constrained('series_facturacion')->restrictOnDelete();
            $table->foreignId('factura_rectificada_id')->nullable()->constrained('facturas')->restrictOnDelete();

            $table->string('numero_completo', 50); // Ej: F2026-0001
            $table->string('tipo_factura', 5)->default('F1'); // F1, F2, R1, R2, R3, R4, R5
            $table->date('fecha_expedicion');
            $table->date('fecha_operacion')->nullable();
            $table->enum('estado', ['borrador', 'emitida', 'rectificada'])->default('borrador');

            // Importes fiscales
            $table->decimal('base_imponible', 12, 2)->default(0.00);
            $table->decimal('cuota_iva', 12, 2)->default(0.00);
            $table->decimal('porcentaje_irpf', 5, 2)->default(0.00);
            $table->decimal('cuota_irpf', 12, 2)->default(0.00);
            $table->decimal('total_factura', 12, 2)->default(0.00);

            // Campos obligatorios Veri*Factu
            $table->string('hash_anterior', 64)->nullable();
            $table->string('hash_actual', 64)->nullable();
            $table->string('fecha_hora_huso_gen', 35)->nullable(); // Ej: 2026-11-19T19:15:00+01:00
            $table->text('qr_url')->nullable();
            $table->longText('xml_verifactu')->nullable();
            $table->string('estado_aeat', 30)->default('pendiente'); // pendiente, aceptada, rechazada, local
            $table->text('notas')->nullable();

            $table->timestamps();

            $table->unique(['emisor_id', 'numero_completo']);
            $table->index(['emisor_id', 'estado', 'fecha_expedicion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};