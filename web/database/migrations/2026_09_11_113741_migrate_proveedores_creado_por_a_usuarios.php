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
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropForeign(['creado_por_admin_id']);
            $table->renameColumn('creado_por_admin_id', 'creado_por_usuario_id');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->foreign('creado_por_usuario_id')->references('id')->on('usuarios')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropForeign(['creado_por_usuario_id']);
            $table->renameColumn('creado_por_usuario_id', 'creado_por_admin_id');
        });

        Schema::table('proveedores', function (Blueprint $table) {
            $table->foreign('creado_por_admin_id')->references('id')->on('admin_users')->nullOnDelete();
        });
    }
};
