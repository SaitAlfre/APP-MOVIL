<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Bloqueo manual (administrativo, con motivo) separado del bloqueo automático por intentos
     * fallidos (`bloqueado_hasta`/`intentos_fallidos`, ya existentes): son dos mecanismos
     * distintos y no deben pisarse — desbloquear uno no debe levantar el otro.
     */
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('bloqueado_manualmente')->default(false)->after('bloqueado_hasta');
            $table->text('motivo_bloqueo')->nullable()->after('bloqueado_manualmente');
            $table->foreignId('bloqueado_por')->nullable()->after('motivo_bloqueo')->constrained('usuarios')->nullOnDelete();
            $table->timestamp('bloqueado_manual_en')->nullable()->after('bloqueado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['bloqueado_por']);
            $table->dropColumn(['bloqueado_manualmente', 'motivo_bloqueo', 'bloqueado_por', 'bloqueado_manual_en']);
        });
    }
};
