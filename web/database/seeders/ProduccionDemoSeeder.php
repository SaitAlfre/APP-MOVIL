<?php

namespace Database\Seeders;

use App\Domain\Calidad\EstadoCalidad;
use App\Domain\Produccion\EstadoLoteProduccion;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Seeder;

/**
 * Datos de ejemplo del módulo de Producción: entregas de hoy ya evaluadas por Calidad
 * (así el "acopio del día" se ve poblado de inmediato) y una corrida histórica de ayer.
 */
class ProduccionDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        if (Entrega::query()->whereDate('registrado_en', now()->toDateString())->exists()) {
            return;
        }

        $zona = Zona::query()->first() ?? Zona::factory()->create();
        $vehiculos = Vehiculo::query()->get();

        if ($vehiculos->isEmpty()) {
            return;
        }

        $proveedores = collect(range(1, 4))->map(fn () => Proveedor::factory()->create(['zona_id' => $zona->id]));

        $litrosPorEntrega = [28.5, 34.0, 19.5, 22.0, 31.5, 16.0, 26.0, 38.0];
        $resultados = [
            EstadoCalidad::Aprobado, EstadoCalidad::Aprobado, EstadoCalidad::Observado, EstadoCalidad::Aprobado,
            EstadoCalidad::Aprobado, EstadoCalidad::Rechazado, EstadoCalidad::Aprobado, EstadoCalidad::Aprobado,
        ];

        $entregaIndice = 0;

        foreach ($vehiculos as $vehiculo) {
            $acopiador = Usuario::factory()->create(['roles' => ['acopiador']]);

            $jornada = Jornada::query()->create([
                'usuario_id' => $acopiador->id,
                'zona_id' => $zona->id,
                'vehiculo_id' => $vehiculo->id,
                'fecha' => now()->toDateString(),
                'abierta_en' => now()->startOfDay()->addHours(6),
                'cerrada_en' => null,
                'seguimiento_activo' => false,
            ]);

            for ($i = 0; $i < 2 && $entregaIndice < count($litrosPorEntrega); $i++) {
                $proveedor = $proveedores[$entregaIndice % $proveedores->count()];

                $entrega = Entrega::query()->create([
                    'jornada_id' => $jornada->id,
                    'proveedor_id' => $proveedor->id,
                    'usuario_id' => $acopiador->id,
                    'zona_id' => $zona->id,
                    'vehiculo_id' => $vehiculo->id,
                    'litros' => $litrosPorEntrega[$entregaIndice],
                    'tachos' => 1,
                    'observaciones' => null,
                    'registrado_en' => now(),
                    'lote_id' => null,
                    'anulada' => false,
                ]);

                ControlCalidad::query()->create([
                    'entrega_id' => $entrega->id,
                    'usuario_id' => $acopiador->id,
                    'resultado' => $resultados[$entregaIndice]->value,
                    'temperatura_c' => 4.0,
                    'acidez' => 16.0,
                    'observaciones' => null,
                    'evaluado_en' => now(),
                ]);

                $entregaIndice++;
            }
        }

        $quesoFresco = Producto::query()->where('nombre', 'Queso Fresco 1kg')->first();
        $primerVehiculo = $vehiculos->first();
        $responsable = Usuario::query()->where('roles', 'like', '%admin%')->first();

        if ($quesoFresco !== null && $responsable !== null && LoteProduccion::query()->count() === 0) {
            $fecha = now()->subDay()->toDateString();

            LoteProduccion::query()->create([
                'codigo' => 'L-'.now()->subDay()->format('Ymd').'-DEMO',
                'producto_id' => $quesoFresco->id,
                'fecha' => $fecha,
                'litros_por_unidad_snapshot' => $quesoFresco->litros_por_unidad,
                'litros_asignados' => 80,
                'litros_usados' => 80,
                'litros_merma_proceso' => 0,
                'litros_sobrantes' => 0,
                'unidades_estimadas' => 10,
                'unidades_producidas' => 10,
                'estado' => EstadoLoteProduccion::Finalizado->value,
                'origen_acopio' => [[
                    'vehiculoId' => $primerVehiculo->id,
                    'vehiculoNombre' => $primerVehiculo->nombre,
                    'litros' => 80.0,
                    'unidades' => 10,
                ]],
                'responsable_id' => $responsable->id,
                'finalizado_en' => now()->subDay(),
            ]);

            $quesoFresco->update(['existencia' => (float) $quesoFresco->existencia + 10]);
        }
    }
}
