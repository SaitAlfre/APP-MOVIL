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
        Schema::create('infracciones_calidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analisis_calidad_id')->unique()->constrained('analisis_calidad')->restrictOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->restrictOnDelete();
            $table->foreignId('registrado_por')->constrained('usuarios')->restrictOnDelete();
            $table->string('estado', 20)->default('pendiente');
            $table->text('motivo');
            $table->text('respuesta')->nullable();
            $table->decimal('descuento_litro', 4, 2)->nullable();
            $table->date('semana_inicio')->nullable();
            $table->date('semana_fin')->nullable();
            $table->unsignedInteger('numero_infraccion')->nullable();
            $table->foreignId('confirmado_por')->nullable()->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('confirmado_en')->nullable();
            $table->timestamps();
            $table->index(['proveedor_id', 'estado', 'semana_inicio', 'semana_fin'], 'infracciones_calidad_periodo');
        });
        Schema::table('comunicados', function (Blueprint $table) {
            $table->foreignId('infraccion_calidad_id')->nullable()->constrained('infracciones_calidad')->restrictOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('zona_id')->nullable()->constrained('zonas')->restrictOnDelete();
            $table->json('destinatarios_proveedor')->nullable();
        });
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->json('detalle_castigo_calidad')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('liquidaciones', function (Blueprint $table) {
            $table->dropColumn('detalle_castigo_calidad');
        });
        Schema::table('comunicados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('infraccion_calidad_id');
            $table->dropConstrainedForeignId('proveedor_id');
            $table->dropConstrainedForeignId('zona_id');
            $table->dropColumn('destinatarios_proveedor');
        });
        Schema::dropIfExists('infracciones_calidad');
    }
};
