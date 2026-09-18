<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('unidad', 20);
            $table->decimal('stock_minimo', 12, 3)->default(0);
            $table->decimal('existencia', 12, 3)->default(0);
            $table->decimal('reservado', 12, 3)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // La leche es el único insumo cuya existencia se alimenta automáticamente desde
        // las entregas aprobadas/observadas por Calidad (ver RegistrarControlCalidadUseCase).
        // Se crea aquí para garantizar que exista un único registro canónico desde el inicio.
        DB::table('insumos')->insert([
            'nombre' => 'Leche',
            'unidad' => 'L',
            'stock_minimo' => 0,
            'existencia' => 0,
            'reservado' => 0,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};
