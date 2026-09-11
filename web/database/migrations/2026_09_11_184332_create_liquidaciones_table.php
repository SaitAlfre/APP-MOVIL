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
        Schema::create('liquidaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->decimal('litros_totales', 10, 2);
            $table->decimal('precio_litro', 8, 3);
            $table->decimal('monto_total', 10, 2);
            $table->enum('estado', ['pendiente', 'pagada'])->default('pendiente');
            $table->timestamp('generada_en');
            $table->timestamp('pagada_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('liquidaciones');
    }
};
