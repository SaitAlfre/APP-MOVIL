<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Amplía lotes_produccion para el flujo completo de Producción (producto + receta
     * versionada + estados borrador/en_proceso/finalizado/cancelado). Las columnas del
     * modelo anterior (producto libre, litros_utilizados, estado abierto/cerrado) se
     * conservan renombradas a *_legacy para no perder datos ya registrados.
     */
    public function up(): void
    {
        Schema::table('lotes_produccion', function (Blueprint $table) {
            $table->renameColumn('producto', 'producto_legacy');
            $table->renameColumn('litros_utilizados', 'litros_utilizados_legacy');
            $table->renameColumn('estado', 'estado_legacy');
        });

        // Las columnas heredadas ya no se completan al crear un lote nuevo: se vuelven
        // opcionales para no bloquear los inserts del flujo nuevo con datos legados vacíos.
        Schema::table('lotes_produccion', function (Blueprint $table) {
            $table->string('producto_legacy')->nullable()->change();
            $table->decimal('litros_utilizados_legacy', 10, 2)->nullable()->change();
        });

        Schema::table('lotes_produccion', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('codigo')->constrained('productos')->restrictOnDelete();
            $table->foreignId('receta_id')->nullable()->after('producto_id')->constrained('recetas')->restrictOnDelete();
            $table->decimal('cantidad_planificada', 12, 3)->nullable()->after('receta_id');
            $table->decimal('cantidad_obtenida', 12, 3)->nullable()->after('cantidad_planificada');
            $table->string('unidad', 20)->nullable()->after('cantidad_obtenida');
            $table->date('fecha_planificada')->nullable()->after('unidad');
            $table->text('observaciones')->nullable()->after('fecha_planificada');
            $table->string('estado', 20)->default('borrador')->after('estado_legacy');
            $table->timestamp('iniciado_en')->nullable()->after('abierto_en');
            $table->timestamp('finalizado_en')->nullable()->after('cerrado_en');
            $table->timestamp('cancelado_en')->nullable()->after('finalizado_en');
            $table->text('motivo_cancelacion')->nullable()->after('cancelado_en');
        });

        // Backfill: los lotes creados con el modelo anterior no tienen producto/receta
        // del catálogo nuevo, pero se conserva su estado aproximado en el ciclo nuevo.
        DB::table('lotes_produccion')->where('estado_legacy', 'abierto')->update(['estado' => 'en_proceso']);
        DB::table('lotes_produccion')->where('estado_legacy', 'cerrado')->update(['estado' => 'finalizado']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lotes_produccion', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropForeign(['receta_id']);
            $table->dropColumn([
                'producto_id', 'receta_id', 'cantidad_planificada', 'cantidad_obtenida',
                'unidad', 'fecha_planificada', 'observaciones', 'estado',
                'iniciado_en', 'finalizado_en', 'cancelado_en', 'motivo_cancelacion',
            ]);
        });

        Schema::table('lotes_produccion', function (Blueprint $table) {
            $table->renameColumn('producto_legacy', 'producto');
            $table->renameColumn('litros_utilizados_legacy', 'litros_utilizados');
            $table->renameColumn('estado_legacy', 'estado');
        });
    }
};
