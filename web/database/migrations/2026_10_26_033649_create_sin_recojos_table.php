<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sin_recojos', function (Blueprint $table) {
            $table->id();
            $table->string('uuid_movil', 64)->unique();
            $table->foreignId('jornada_id')->constrained('jornadas')->restrictOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->foreignId('zona_id')->constrained('zonas')->restrictOnDelete();
            $table->date('fecha')->index();
            $table->string('motivo', 40);
            $table->text('detalle')->nullable();
            $table->timestamp('registrada_en');
            $table->boolean('deshecha')->default(false);
            $table->timestamp('deshecha_en')->nullable();
            $table->unsignedBigInteger('actualizado_en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sin_recojos');
    }
};
