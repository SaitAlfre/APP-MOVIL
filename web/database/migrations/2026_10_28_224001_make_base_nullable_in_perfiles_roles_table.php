<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los roles personalizados nuevos solo definen permisos del panel web: ya no tienen «perfil base».
 * Los roles existentes conservan su `base`, que mantiene las funciones móviles de sus cuentas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfiles_roles', function (Blueprint $table) {
            $table->string('base')->nullable()->change();
        });
    }

    /** No se inventa un rol oficial para los roles creados sin base: si existen, la reversión se detiene. */
    public function down(): void
    {
        if (DB::table('perfiles_roles')->whereNull('base')->exists()) {
            throw new RuntimeException('Hay roles personalizados sin perfil base. Asigna su base manualmente antes de revertir esta migración.');
        }
        Schema::table('perfiles_roles', function (Blueprint $table) {
            $table->string('base')->nullable(false)->change();
        });
    }
};
