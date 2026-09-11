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
        Schema::create('controles_calidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entrega_id')->unique()->constrained('entregas')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->enum('resultado', ['aprobado', 'observado', 'rechazado']);
            $table->decimal('temperatura_c', 5, 2)->nullable();
            $table->decimal('acidez', 5, 2)->nullable();
            $table->string('observaciones')->nullable();
            $table->timestamp('evaluado_en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('controles_calidad');
    }
};
