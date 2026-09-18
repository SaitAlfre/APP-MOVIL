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
        Schema::create('movimientos_insumo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->enum('tipo', ['entrada', 'reserva', 'consumo', 'liberacion', 'ajuste']);
            // Cantidad con signo: positiva incrementa el saldo relevante del tipo,
            // negativa lo reduce (usado sobre todo en ajustes manuales).
            $table->decimal('cantidad', 12, 3);
            $table->string('unidad', 20);
            $table->foreignId('lote_produccion_id')->nullable()->constrained('lotes_produccion')->nullOnDelete();
            $table->foreignId('entrada_insumo_id')->nullable()->constrained('entradas_insumo')->nullOnDelete();
            $table->string('motivo')->nullable();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamp('fecha');
            $table->timestamps();

            $table->index(['insumo_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_insumo');
    }
};
