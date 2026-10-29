<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Origen de la suspensión de una cuenta. `ficha_proveedor` = se suspendió al suspender o retirar su ficha vinculada
 * y se restaura al reactivarla. Nulo = suspensión directa o anterior: nunca se levanta automáticamente.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('suspension_origen', 30)->nullable()->after('bloqueado_manual_en');
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('suspension_origen');
        });
    }
};
