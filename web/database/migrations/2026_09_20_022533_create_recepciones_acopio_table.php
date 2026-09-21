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
        Schema::create('recepciones_acopio', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->decimal('litros_base', 12, 2);
            $table->decimal('merma_litros', 12, 2);
            $table->text('motivo');
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['fecha', 'vehiculo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepciones_acopio');
    }
};
