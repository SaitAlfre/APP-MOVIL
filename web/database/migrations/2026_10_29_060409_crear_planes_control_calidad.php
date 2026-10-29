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
        Schema::create('planes_control_calidad', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('zona_id')->constrained('zonas');
            $table->foreignId('proveedor_id')->constrained('proveedores');
            $table->string('lugar', 20);
            $table->string('motivo', 30);
            $table->string('estado', 20)->default('programado');
            $table->text('observacion')->nullable();
            $table->foreignId('creado_por')->constrained('usuarios');
            $table->foreignId('actualizado_por')->nullable()->constrained('usuarios');
            $table->timestamps();
            $table->unique(['fecha', 'usuario_id', 'proveedor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planes_control_calidad');
    }
};
