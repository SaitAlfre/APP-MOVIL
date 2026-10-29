<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Una receta puede reutilizarse en varios productos (por ejemplo, el mismo queso en moldes de 1 kg
 * y de 500 g). La leche e insumos de cada producto se derivan de su peso o volumen por unidad.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->index('receta_id');
        });
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique(['receta_id']);
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropIndex(['receta_id']);
            $table->unique('receta_id');
        });
    }
};
