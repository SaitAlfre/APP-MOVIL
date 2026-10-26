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
        Schema::table('reclamos_proveedor', function (Blueprint $table) {
            $table->unsignedBigInteger('entrega_id')->nullable()->change();
            $table->text('descripcion')->nullable();
            $table->longText('evidencia')->nullable();
        });
        Schema::table('solicitudes_traslado', function (Blueprint $table) {
            $table->text('respuesta')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('reclamos_proveedor')->whereNull('entrega_id')->exists()) {
            throw new RuntimeException('Vincula los reclamos sin entrega antes de revertir esta migración.');
        }
        Schema::table('solicitudes_traslado', function (Blueprint $table) {
            $table->dropColumn('respuesta');
        });
        Schema::table('reclamos_proveedor', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'evidencia']);
            $table->unsignedBigInteger('entrega_id')->nullable(false)->change();
        });
    }
};
