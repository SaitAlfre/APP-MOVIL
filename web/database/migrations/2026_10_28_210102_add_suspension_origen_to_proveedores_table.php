<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Origen de la suspensión productiva. `acceso_cuenta` = la ficha se suspendió al suspender el acceso
 * de su cuenta vinculada y puede restaurarse junto con ella. Nulo = decisión independiente o histórica
 * sin origen conocido: nunca se reactiva automáticamente. Columna aditiva; no altera datos existentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->string('suspension_origen', 30)->nullable()->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropColumn('suspension_origen');
        });
    }
};
