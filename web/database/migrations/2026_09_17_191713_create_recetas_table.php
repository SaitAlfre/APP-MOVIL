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
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre');
            $table->unsignedInteger('version');
            $table->decimal('rendimiento_base', 12, 3);
            $table->string('rendimiento_unidad', 20);
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['borrador', 'activa', 'archivada'])->default('borrador');
            $table->foreignId('creado_por_usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['producto_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recetas');
    }
};
