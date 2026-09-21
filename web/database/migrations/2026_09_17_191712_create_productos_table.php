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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('presentacion');
            $table->string('unidad_produccion', 20);
            $table->decimal('contenido_por_unidad', 12, 3)->nullable();
            $table->string('unidad_contenido', 20)->nullable();
            $table->decimal('litros_por_unidad', 12, 3);
            $table->text('otros_insumos')->nullable();
            $table->decimal('existencia', 12, 3)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
