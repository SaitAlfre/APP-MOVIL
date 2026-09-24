<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Id local del celular en los controles de calidad y reclamos registrados en la app: reenviar el mismo
 * registro actualiza la misma fila y nunca la duplica. Solo aditiva.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('controles_calidad', function (Blueprint $table) {
            $table->string('uuid_movil', 64)->nullable()->unique();
        });
        Schema::table('reclamos_proveedor', function (Blueprint $table) {
            $table->string('uuid_movil', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('controles_calidad', fn (Blueprint $table) => $table->dropColumn('uuid_movil'));
        Schema::table('reclamos_proveedor', fn (Blueprint $table) => $table->dropColumn('uuid_movil'));
    }
};
