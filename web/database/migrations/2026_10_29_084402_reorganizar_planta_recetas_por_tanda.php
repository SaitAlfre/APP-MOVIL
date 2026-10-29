<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * La receta pasa a describir una tanda (leche + insumos → unidades que rinde) y cada producto
 * queda amarrado a una receta propia. Las recetas compartidas o escaladas con cantidad_receta
 * se copian por producto para conservar exactamente la leche e insumos que ya usaban.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recetas', function (Blueprint $table): void {
            $table->decimal('litros_tanda', 12, 3)->nullable();
            $table->decimal('rinde_unidades', 12, 3)->default(1);
            $table->decimal('litros_por_unidad', 15, 6)->change();
        });
        Schema::table('receta_ingredientes', function (Blueprint $table): void {
            $table->decimal('cantidad', 15, 6)->change();
        });
        Schema::table('productos', function (Blueprint $table): void {
            $table->decimal('litros_por_unidad', 15, 6)->nullable()->change();
            $table->string('codigo', 30)->nullable()->unique();
            $table->string('descripcion', 500)->nullable();
            $table->string('imagen')->nullable();
            $table->decimal('stock_minimo', 12, 3)->nullable();
        });
        Schema::table('materiales', function (Blueprint $table): void {
            $table->decimal('stock_minimo', 15, 3)->nullable();
        });
        Schema::table('lotes_produccion', function (Blueprint $table): void {
            $table->decimal('litros_por_unidad_snapshot', 15, 6)->change();
        });

        DB::table('recetas')->update(['litros_tanda' => DB::raw('litros_por_unidad'), 'rinde_unidades' => 1]);

        $recetasUsadas = [];
        foreach (DB::table('productos')->whereNotNull('receta_id')->orderBy('id')->get() as $producto) {
            $factor = (float) $producto->cantidad_receta;
            if (! isset($recetasUsadas[$producto->receta_id]) && abs($factor - 1) < 0.0005) {
                $recetasUsadas[$producto->receta_id] = true;

                continue;
            }
            $receta = DB::table('recetas')->find($producto->receta_id);
            $nombre = Str::limit($receta->nombre.' · '.$producto->nombre, 140, '');
            while (DB::table('recetas')->where('nombre', $nombre)->exists()) {
                $nombre = Str::limit($nombre, 135, '').' '.Str::random(3);
            }
            $copiaId = DB::table('recetas')->insertGetId([
                'nombre' => $nombre, 'unidad_base' => $receta->unidad_base,
                'litros_tanda' => round((float) $receta->litros_tanda * $factor, 3),
                'litros_por_unidad' => round((float) $receta->litros_tanda * $factor, 6),
                'rinde_unidades' => 1, 'otros_insumos' => $receta->otros_insumos, 'activo' => $receta->activo,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (DB::table('receta_materiales')->where('receta_id', $receta->id)->get() as $material) {
                DB::table('receta_materiales')->insert(['receta_id' => $copiaId, 'material_id' => $material->material_id, 'cantidad' => round((float) $material->cantidad * $factor, 3)]);
            }
            $recetasUsadas[$copiaId] = true;
            DB::table('productos')->where('id', $producto->id)->update(['receta_id' => $copiaId]);
        }

        Schema::table('productos', function (Blueprint $table): void {
            $table->dropColumn('cantidad_receta');
            $table->unique('receta_id');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table): void {
            $table->dropUnique(['receta_id']);
            $table->dropUnique(['codigo']);
            $table->dropColumn(['codigo', 'descripcion', 'imagen', 'stock_minimo']);
            $table->decimal('cantidad_receta', 12, 3)->default(1);
        });
        Schema::table('materiales', function (Blueprint $table): void {
            $table->dropColumn('stock_minimo');
        });
        Schema::table('recetas', function (Blueprint $table): void {
            $table->dropColumn(['litros_tanda', 'rinde_unidades']);
        });
    }
};
