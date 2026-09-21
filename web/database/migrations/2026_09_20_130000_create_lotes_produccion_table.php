<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reemplaza el modelo de "una corrida por fecha que consume el 100% del acopio" por lotes:
     * varios productos y varias asignaciones parciales de litros por día. Las corridas existentes
     * se conservan como lotes Finalizado (mismas cantidades, sin volver a tocar existencia ni
     * descontar leche), y produccion_corridas se retira porque su información ya vive en
     * lotes_produccion.
     */
    public function up(): void
    {
        Schema::create('lotes_produccion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('litros_por_unidad_snapshot', 10, 3);
            $table->decimal('litros_asignados', 12, 3);
            $table->decimal('litros_usados', 12, 3)->nullable();
            $table->decimal('litros_merma_proceso', 12, 3)->nullable();
            $table->decimal('litros_sobrantes', 12, 3)->nullable();
            $table->unsignedInteger('unidades_estimadas');
            $table->unsignedInteger('unidades_producidas')->nullable();
            $table->enum('estado', ['borrador', 'en_proceso', 'finalizado', 'cancelado'])->default('borrador');
            $table->json('origen_acopio');
            $table->foreignId('responsable_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('finalizado_en')->nullable();
            $table->timestamp('cancelado_en')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();

            $table->index(['fecha', 'estado']);
        });

        if (! Schema::hasTable('produccion_corridas')) {
            return;
        }

        $adminFallback = DB::table('usuarios')->where('roles', 'like', '%admin%')->value('id');

        foreach (DB::table('produccion_corridas')->orderBy('id')->get() as $corrida) {
            $litrosPorUnidad = $corrida->unidades_producidas > 0
                ? round($corrida->litros_usados / $corrida->unidades_producidas, 3)
                : (float) (DB::table('productos')->where('id', $corrida->producto_id)->value('litros_por_unidad') ?? 1);

            // La corrida no guardaba responsable; se infiere del movimiento de inventario que la
            // finalización de la corrida generó en su momento (mismo producto, misma cantidad,
            // más cercano en el tiempo). Si no hay coincidencia, se usa un admin como respaldo.
            // El cálculo de cercanía se hace en PHP (no con funciones SQL de fecha) para que esta
            // migración funcione igual en MySQL y en el sqlite en memoria de las pruebas.
            $candidatoMasCercano = DB::table('movimientos_producto')
                ->where('producto_id', $corrida->producto_id)
                ->where('tipo', 'produccion')
                ->where('cantidad', $corrida->unidades_producidas)
                ->get(['usuario_id', 'fecha'])
                ->sortBy(fn ($movimiento) => abs(strtotime($movimiento->fecha) - strtotime($corrida->created_at)))
                ->first();
            $responsableId = $candidatoMasCercano->usuario_id ?? $adminFallback;

            DB::table('lotes_produccion')->insert([
                'codigo' => 'L-'.Carbon::parse($corrida->fecha)->format('Ymd').'-LEGACY-'.$corrida->id,
                'producto_id' => $corrida->producto_id,
                'fecha' => $corrida->fecha,
                'litros_por_unidad_snapshot' => $litrosPorUnidad,
                'litros_asignados' => round($corrida->litros_usados + $corrida->litros_sobrantes, 3),
                'litros_usados' => $corrida->litros_usados,
                'litros_merma_proceso' => 0,
                'litros_sobrantes' => $corrida->litros_sobrantes,
                'unidades_estimadas' => $corrida->unidades_producidas,
                'unidades_producidas' => $corrida->unidades_producidas,
                'estado' => 'finalizado',
                'origen_acopio' => $corrida->desglose_vehiculos,
                'responsable_id' => $responsableId,
                'finalizado_en' => $corrida->created_at,
                'created_at' => $corrida->created_at,
                'updated_at' => $corrida->updated_at,
            ]);
        }

        Schema::dropIfExists('produccion_corridas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('produccion_corridas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->date('fecha')->unique();
            $table->decimal('litros_usados', 10, 3);
            $table->unsignedInteger('unidades_producidas');
            $table->decimal('litros_sobrantes', 10, 3);
            $table->json('desglose_vehiculos');
            $table->timestamps();
        });

        // Solo los lotes heredados de una corrida (código "L-*-LEGACY-*") vuelven a esa tabla; los
        // lotes creados después de esta migración no tienen equivalente en el modelo antiguo.
        foreach (DB::table('lotes_produccion')->where('codigo', 'like', 'L-%-LEGACY-%')->orderBy('id')->get() as $lote) {
            DB::table('produccion_corridas')->insert([
                'producto_id' => $lote->producto_id,
                'fecha' => $lote->fecha,
                'litros_usados' => $lote->litros_usados,
                'unidades_producidas' => $lote->unidades_producidas,
                'litros_sobrantes' => $lote->litros_sobrantes,
                'desglose_vehiculos' => $lote->origen_acopio,
                'created_at' => $lote->created_at,
                'updated_at' => $lote->updated_at,
            ]);
        }

        Schema::dropIfExists('lotes_produccion');
    }
};
