<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->string('unidad_base', 20)->default('unidad');
            $table->decimal('litros_por_unidad', 12, 3);
            $table->text('otros_insumos')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
        Schema::create('receta_materiales', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('materiales')->restrictOnDelete();
            $table->decimal('cantidad', 12, 3);
            $table->unique(['receta_id', 'material_id']);
        });
        Schema::table('productos', function (Blueprint $table): void {
            $table->foreignId('receta_id')->nullable()->constrained('recetas')->restrictOnDelete();
            $table->decimal('cantidad_receta', 12, 3)->default(1);
        });
        DB::table('productos')->whereNotNull('litros_por_unidad')->orderBy('id')->get()->each(function ($producto): void {
            $id = DB::table('recetas')->insertGetId([
                'nombre' => $producto->nombre, 'unidad_base' => $producto->unidad_produccion,
                'litros_por_unidad' => $producto->litros_por_unidad, 'otros_insumos' => $producto->otros_insumos,
                'activo' => $producto->receta_activa, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('productos')->where('id', $producto->id)->update(['receta_id' => $id]);
            foreach (DB::table('receta_ingredientes')->where('producto_id', $producto->id)->get() as $ingrediente) {
                DB::table('receta_materiales')->insert(['receta_id' => $id, 'material_id' => $ingrediente->material_id, 'cantidad' => $ingrediente->cantidad]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('receta_id');
            $table->dropColumn('cantidad_receta');
        });
        Schema::dropIfExists('receta_materiales');
        Schema::dropIfExists('recetas');
    }
};
