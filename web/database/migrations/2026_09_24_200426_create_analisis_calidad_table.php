<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Análisis de calidad igual al de la app móvil: una prueba LactoScan por proveedor con sus 11 parámetros,
 * referencias, alertas y datos de la visita. `uuid` es el mismo id en el celular y en el panel.
 *
 * Los controles por entrega (`controles_calidad`) se conservan: producción, recepción y sanciones los usan.
 * Desde ahora los genera cada análisis para las entregas de ese proveedor del mismo día. Los controles ya
 * registrados se pasan a análisis (con sus valores reales, sin inventar los que no se midieron).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analisis_calidad', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 64)->unique();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('usuarios')->restrictOnDelete();
            $table->string('codigo_muestra', 40)->unique();
            $table->string('lote_recipiente', 60)->nullable();
            $table->double('volumen_l')->nullable();
            $table->string('origen_captura', 10)->default('MANUAL');
            $table->string('serial_analizador', 60)->nullable();
            $table->string('modo_analizador', 60)->nullable();
            foreach (['temperatura', 'grasa', 'sng', 'densidad', 'proteina', 'lactosa', 'sales', 'solidos_totales', 'agua_anadida', 'punto_congelacion', 'ph'] as $parametro) {
                $table->double($parametro)->nullable();
            }
            $table->string('apariencia')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 15);
            $table->json('alertas');
            $table->text('texto_comprobante')->nullable();
            $table->json('visita');
            $table->timestamp('registrado_en');
            $table->timestamps();
            $table->index(['proveedor_id', 'registrado_en']);
            $table->index(['estado', 'registrado_en']);
        });

        Schema::table('controles_calidad', function (Blueprint $table) {
            $table->foreignId('analisis_calidad_id')->nullable()->constrained('analisis_calidad')->nullOnDelete();
        });

        $this->migrarControlesExistentes();
    }

    public function down(): void
    {
        Schema::table('controles_calidad', function (Blueprint $table) {
            $table->dropConstrainedForeignId('analisis_calidad_id');
        });
        Schema::dropIfExists('analisis_calidad');
    }

    private function migrarControlesExistentes(): void
    {
        DB::table('controles_calidad')
            ->join('entregas', 'entregas.id', '=', 'controles_calidad.entrega_id')
            ->leftJoin('proveedores', 'proveedores.id', '=', 'entregas.proveedor_id')
            ->leftJoin('zonas', 'zonas.id', '=', 'proveedores.zona_id')
            ->leftJoin('usuarios', 'usuarios.id', '=', 'controles_calidad.usuario_id')
            ->orderBy('controles_calidad.id')
            ->select([
                'controles_calidad.*', 'entregas.proveedor_id', 'proveedores.nombres as proveedor_nombre', 'proveedores.codigo as proveedor_codigo',
                'proveedores.zona_id', 'zonas.nombre as zona_nombre', 'usuarios.nombres as tecnico_nombre',
            ])
            ->get()
            ->each(function (object $control): void {
                $temperaturaFuera = $control->temperatura_c !== null && ($control->temperatura_c < 0 || $control->temperatura_c > 8);
                $observaciones = collect([
                    $control->observaciones,
                    $control->acidez !== null ? 'Acidez: '.(float) $control->acidez.' °D' : null,
                    'Registrado como control de la entrega #'.$control->entrega_id.'.',
                ])->filter()->implode(' · ');
                $id = DB::table('analisis_calidad')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'proveedor_id' => $control->proveedor_id,
                    'usuario_id' => $control->usuario_id,
                    'codigo_muestra' => 'AN-CTRL-'.$control->id,
                    'origen_captura' => 'MANUAL',
                    'temperatura' => $control->temperatura_c,
                    'observaciones' => $observaciones,
                    'estado' => strtoupper($control->resultado),
                    'alertas' => json_encode($temperaturaFuera ? ['Temperatura: '.(float) $control->temperatura_c.' · Referencia: 0.0 a 8.0 °C'] : []),
                    'visita' => json_encode([
                        'proveedorNombre' => (string) $control->proveedor_nombre, 'proveedorCodigo' => (string) $control->proveedor_codigo,
                        'zonaNombre' => (string) $control->zona_nombre, 'tecnicoNombre' => (string) $control->tecnico_nombre,
                        'unidadCongelacion' => '°C', 'parametrosAlertados' => $temperaturaFuera ? ['temperatura'] : [],
                    ]),
                    'registrado_en' => $control->evaluado_en,
                    'created_at' => $control->created_at,
                    'updated_at' => $control->updated_at,
                ]);
                DB::table('controles_calidad')->where('id', $control->id)->update(['analisis_calidad_id' => $id]);
            });
    }
};
