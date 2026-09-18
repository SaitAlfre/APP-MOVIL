<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entradas_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            // Vincula una entrada automática con la entrega de Acopio que la originó.
            // Único: una misma entrega solo puede generar una entrada de inventario.
            $table->foreignId('entrega_id')->nullable()->unique()->constrained('entregas')->nullOnDelete();
            $table->decimal('cantidad', 12, 3);
            $table->string('unidad', 20);
            $table->date('fecha');
            $table->decimal('costo_unitario', 12, 4)->nullable();
            $table->decimal('costo_total', 12, 2)->nullable();
            $table->string('documento_referencia')->nullable();
            $table->string('lote_origen')->nullable();
            $table->date('vencimiento')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas_insumo');
    }
};
