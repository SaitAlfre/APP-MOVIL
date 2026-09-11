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
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombres');
            $table->string('dni')->unique();
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->foreignId('zona_id')->constrained('zonas')->restrictOnDelete();
            $table->unsignedInteger('tachos')->default(1);
            $table->decimal('capacidad_tacho_l', 8, 2)->default(40);
            $table->enum('estado', ['activo', 'suspendido', 'retirado'])->default('activo');
            $table->foreignId('creado_por_admin_id')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
