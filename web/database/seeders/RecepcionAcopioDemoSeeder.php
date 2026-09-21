<?php

namespace Database\Seeders;

use App\Application\Recepcion\RegistrarLlegadaUseCase;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecepcionAcopioDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function () {
            $calidad = Usuario::query()->firstOrCreate(['username' => 'demo_calidad_recepcion'], [
                'nombres' => 'Elena Quispe · DEMO', 'dni' => '99009000', 'pin_hash' => '8642', 'roles' => ['calidad'], 'activo' => true,
            ]);
            $escenarios = [
                ['Juan Mamani', 'Norte', 120, 5, 'aprobado'],
                ['Rosa Quispe', 'Sur', 90, 0, 'aprobado'],
                ['Pedro Flores', 'Centro', 80, 3, 'observado'],
                ['Ana Apaza', 'Este', 60, 0, null],
            ];

            foreach ($escenarios as $indice => [$nombre, $sector, $litros, $merma, $resultado]) {
                $numero = $indice + 1;
                $zona = Zona::query()->firstOrCreate(['nombre' => 'DEMO Recepción '.$sector], ['activo' => true]);
                $camion = Vehiculo::query()->firstOrCreate(['placa' => 'DMO-10'.$numero], ['nombre' => 'Camión DEMO '.$numero, 'activo' => true]);
                $acopiador = Usuario::query()->firstOrCreate(['username' => 'demo_recepcion_'.$numero], [
                    'nombres' => $nombre.' · DEMO', 'dni' => '9900910'.$numero, 'pin_hash' => '2468', 'roles' => ['acopiador'], 'activo' => true,
                ]);
                $proveedor = Proveedor::query()->firstOrCreate(['codigo' => 'DEMO-REC-'.$numero], [
                    'nombres' => 'Proveedor de prueba '.$sector, 'dni' => '9900920'.$numero,
                    'zona_id' => $zona->id, 'tachos' => 3, 'capacidad_tacho_l' => 40, 'estado' => 'activo',
                ]);

                foreach ([0, 2, 3] as $diasAtras) {
                    $fecha = now()->subDays($diasAtras)->startOfDay();
                    if (LoteProduccion::query()->whereDate('fecha', $fecha)->where('estado', '!=', 'cancelado')->exists()) {
                        continue;
                    }
                    $jornada = Jornada::query()->where('usuario_id', $acopiador->id)->whereDate('fecha', $fecha)->first();
                    if ($jornada === null) {
                        $jornada = Jornada::query()->create([
                            'usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'vehiculo_id' => $camion->id,
                            'fecha' => $fecha->toDateString(), 'abierta_en' => $fecha, 'cerrada_en' => $diasAtras > 0 ? $fecha->copy()->addHours(10) : null,
                            'seguimiento_activo' => false,
                        ]);
                    }
                    $cantidad = $litros - $diasAtras * 5;
                    $entrega = Entrega::query()->firstOrCreate(['jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id], [
                        'usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'vehiculo_id' => $camion->id,
                        'litros' => $cantidad, 'tachos' => (int) ceil($cantidad / 40),
                        'observaciones' => 'DEMO: datos de prueba de recepción en planta', 'registrado_en' => $diasAtras === 0 ? now() : $fecha->copy()->addHours(8), 'anulada' => false,
                    ]);
                    if ($resultado !== null && $entrega->wasRecentlyCreated) {
                        ControlCalidad::query()->create([
                            'entrega_id' => $entrega->id, 'usuario_id' => $calidad->id, 'resultado' => $resultado,
                            'temperatura_c' => 4, 'acidez' => 16, 'observaciones' => 'DEMO: control de ejemplo', 'evaluado_en' => $entrega->registrado_en,
                        ]);
                        if ($merma > 0) {
                            app(RegistrarLlegadaUseCase::class)->ejecutar(
                                jornadaId: $jornada->id,
                                llegadaEn: DateTimeImmutable::createFromInterface($entrega->registrado_en),
                                litrosMedidos: $cantidad - $merma,
                                motivoDiferencia: 'DEMO: pérdida de '.$merma.' L durante el traslado',
                                observaciones: null,
                                usuarioId: $calidad->id,
                            );
                        }
                    }
                }
            }
        });

        $this->command?->info('Datos DEMO de recepción disponibles: cuatro camiones, acopiadores y entregas de hoy y dos días anteriores. Los días procesados se omiten y los registros existentes se conservan.');
    }
}
