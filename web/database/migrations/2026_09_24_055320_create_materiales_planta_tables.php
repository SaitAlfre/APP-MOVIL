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
        Schema::create('materiales', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('unidad', 10);
            $table->decimal('existencia', 15, 3)->default(0);
            $table->timestamps();
        });
        Schema::create('receta_ingredientes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiales');
            $table->decimal('cantidad', 12, 3);
            $table->unique(['producto_id', 'material_id']);
        });
        Schema::table('lotes_produccion', function (Blueprint $table): void {
            $table->json('ingredientes_snapshot')->nullable();
        });
        Schema::create('movimientos_material', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('material_id')->constrained('materiales');
            $table->foreignId('lote_id')->nullable()->constrained('lotes_produccion');
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->string('tipo', 20);
            $table->decimal('cantidad', 15, 3);
            $table->string('motivo', 255);
            $table->timestamp('fecha');
            $table->unique(['lote_id', 'material_id', 'tipo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_material');
        Schema::table('lotes_produccion', fn (Blueprint $table) => $table->dropColumn('ingredientes_snapshot'));
        Schema::dropIfExists('receta_ingredientes');
        Schema::dropIfExists('materiales');
    }
};
