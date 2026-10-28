<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->string('base');
            $table->boolean('activo')->default(true);
            $table->json('permisos');
            $table->json('campos');
            $table->timestamps();
        });
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreignId('perfil_rol_id')->nullable()->constrained('perfiles_roles')->restrictOnDelete();
            $table->json('datos_formulario')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('perfil_rol_id');
            $table->dropColumn('datos_formulario');
        });
        Schema::dropIfExists('perfiles_roles');
    }
};
