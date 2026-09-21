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
        Schema::table('auditorias', function (Blueprint $table) {
            $table->longText('valor_antes')->nullable()->change();
            $table->longText('valor_despues')->nullable()->change();
            $table->text('motivo')->nullable()->change();
            $table->index(['ocurrido_en', 'id']);
            $table->index(['usuario_id', 'ocurrido_en']);
            $table->index(['accion', 'ocurrido_en']);
        });
        Schema::table('usuarios', function (Blueprint $table) {
            $table->unsignedInteger('version_sesion')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', fn (Blueprint $table) => $table->dropColumn('version_sesion'));
        Schema::table('auditorias', function (Blueprint $table) {
            $table->dropIndex(['ocurrido_en', 'id']);
            $table->dropIndex(['usuario_id', 'ocurrido_en']);
            $table->dropIndex(['accion', 'ocurrido_en']);
        });
        // Se conserva la capacidad de texto al revertir para no truncar el historial.
    }
};
