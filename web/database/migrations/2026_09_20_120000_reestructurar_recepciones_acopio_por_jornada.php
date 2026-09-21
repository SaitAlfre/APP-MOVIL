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
     * La recepción dejaba de identificar el viaje por (fecha, vehiculo_id) y ahora se ata a la
     * jornada real, para no mezclar dos viajes del mismo camión en un solo registro. Se recrea la
     * tabla en vez de usar ALTER...MODIFY para que la migración funcione igual en MySQL y en el
     * sqlite en memoria que usan las pruebas.
     */
    public function up(): void
    {
        Schema::rename('recepciones_acopio', 'recepciones_acopio_legado');

        // Renombrar una tabla no renombra sus constraints de foreign key en MySQL (siguen
        // llamándose "recepciones_acopio_*_foreign"): sin este paso, MySQL rechaza la tabla nueva
        // más abajo porque ese nombre ya existe (los nombres de constraint son únicos por base de
        // datos, no por tabla). Se usa el nombre original explícito porque dropForeign(['columna'])
        // adivina el nombre a partir del nombre ACTUAL de la tabla, que ya cambió. SQLite no tiene
        // este espacio de nombres global ni soporta dropForeign por nombre, así que no aplica.
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('recepciones_acopio_legado', function (Blueprint $table) {
                $table->dropForeign('recepciones_acopio_vehiculo_id_foreign');
                $table->dropForeign('recepciones_acopio_usuario_id_foreign');
            });
        }

        Schema::create('recepciones_acopio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jornada_id')->unique()->constrained('jornadas')->restrictOnDelete();
            $table->timestamp('llegada_en');
            $table->decimal('litros_recolectados', 12, 2);
            $table->decimal('litros_medidos', 12, 2);
            $table->text('motivo_diferencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
        });

        // Cada recepción existente identificaba el viaje solo por (fecha, vehiculo_id). Antes de
        // exigir jornada_id, se resuelve la jornada real de cada fila; si hay 0 o más de 1 jornada
        // candidata la migración se detiene para revisión manual en vez de adivinar.
        foreach (DB::table('recepciones_acopio_legado')->get() as $fila) {
            $jornadas = DB::table('jornadas')
                ->where('vehiculo_id', $fila->vehiculo_id)
                ->whereDate('fecha', $fila->fecha)
                ->pluck('id');

            if ($jornadas->count() !== 1) {
                throw new RuntimeException(
                    "No se puede migrar recepciones_acopio#{$fila->id} (vehiculo_id={$fila->vehiculo_id}, fecha={$fila->fecha}): ".
                    "se encontraron {$jornadas->count()} jornadas candidatas en vez de 1. Revisar manualmente antes de reintentar la migración."
                );
            }

            DB::table('recepciones_acopio')->insert([
                'jornada_id' => $jornadas->first(),
                'llegada_en' => $fila->updated_at,
                'litros_recolectados' => $fila->litros_base,
                'litros_medidos' => round($fila->litros_base - $fila->merma_litros, 2),
                'motivo_diferencia' => $fila->motivo,
                'observaciones' => null,
                'usuario_id' => $fila->usuario_id,
                'created_at' => $fila->created_at,
                'updated_at' => $fila->updated_at,
            ]);
        }

        Schema::dropIfExists('recepciones_acopio_legado');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('recepciones_acopio', 'recepciones_acopio_nuevo');

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('recepciones_acopio_nuevo', function (Blueprint $table) {
                $table->dropForeign('recepciones_acopio_jornada_id_foreign');
                $table->dropForeign('recepciones_acopio_usuario_id_foreign');
            });
        }

        Schema::create('recepciones_acopio', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('vehiculo_id')->constrained('vehiculos')->restrictOnDelete();
            $table->decimal('litros_base', 12, 2);
            $table->decimal('merma_litros', 12, 2);
            $table->text('motivo');
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['fecha', 'vehiculo_id']);
        });

        foreach (DB::table('recepciones_acopio_nuevo')->get() as $fila) {
            $jornada = DB::table('jornadas')->where('id', $fila->jornada_id)->first();

            DB::table('recepciones_acopio')->insert([
                'fecha' => $jornada->fecha,
                'vehiculo_id' => $jornada->vehiculo_id,
                'litros_base' => $fila->litros_recolectados,
                'merma_litros' => round($fila->litros_recolectados - $fila->litros_medidos, 2),
                'motivo' => $fila->motivo_diferencia,
                'usuario_id' => $fila->usuario_id,
                'created_at' => $fila->created_at,
                'updated_at' => $fila->updated_at,
            ]);
        }

        Schema::dropIfExists('recepciones_acopio_nuevo');
    }
};
