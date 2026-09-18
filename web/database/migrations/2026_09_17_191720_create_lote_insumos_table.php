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
        Schema::create('lote_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lote_produccion_id')->constrained('lotes_produccion')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->restrictOnDelete();
            $table->string('unidad', 20);
            $table->decimal('cantidad_necesaria', 12, 3);
            $table->decimal('cantidad_reservada', 12, 3)->default(0);
            // null = consumo aún no informado (distinto de 0, que es consumo real informado como cero).
            $table->decimal('cantidad_consumida', 12, 3)->nullable();
            $table->timestamps();

            $table->unique(['lote_produccion_id', 'insumo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lote_insumos');
    }
};
