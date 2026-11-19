<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lineas_factura', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->foreignId('servicio_id')->nullable()->constrained('servicios')->nullOnDelete();

            $table->string('concepto');
            $table->text('descripcion')->nullable();
            $table->decimal('cantidad', 10, 2)->default(1.00);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0.00);
            $table->decimal('tipo_iva', 5, 2)->default(21.00);
            $table->boolean('aplica_irpf')->default(true);
            $table->decimal('subtotal_base', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineas_factura');
    }
};