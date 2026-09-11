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
        Schema::create('lotes_produccion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('producto');
            $table->decimal('litros_utilizados', 10, 2);
            $table->enum('estado', ['abierto', 'cerrado'])->default('abierto');
            $table->foreignId('responsable_usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('abierto_en');
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_produccion');
    }
};
