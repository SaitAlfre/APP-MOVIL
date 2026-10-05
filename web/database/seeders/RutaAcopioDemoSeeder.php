<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RutaAcopioDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('La ruta de demostración solo se crea en el entorno local.');

            return;
        }

        $ruta = DB::transaction(function (): Ruta {
            $zona = Zona::query()->firstOrCreate(['nombre' => 'Zona Norte'], ['activo' => true]);
            $acopiador = Usuario::query()->where('username', 'acopiador1')->where('activo', true)
                ->whereJsonContains('roles', 'acopiador')->firstOrFail();

            return Ruta::query()->firstOrCreate(['codigo' => 'DEMO-NORTE-01'], [
                'nombre' => 'Ruta demo · Zona Norte',
                'zona_id' => $zona->id,
                'acopiador_id' => $acopiador->id,
                'activo' => true,
                'mapa_version' => 1,
                'indicaciones' => 'DEMOSTRACIÓN: las ubicaciones, paradas y el trazado son de ejemplo. Sigue los números 1 → 2 → 3 para conocer el mapa. El camino ilustrativo no está validado sobre vías reales; reemplaza estos puntos antes de utilizar la ruta para recoger leche.',
                'paradas' => [
                    [
                        'nombre' => 'Demo 1 · Punto de salida',
                        'proveedor_id' => null,
                        'latitud' => -15.610000,
                        'longitud' => -69.970000,
                        'referencia' => 'Ubicación de ejemplo para iniciar el recorrido. Selecciona Ver parada para centrar el mapa.',
                    ],
                    [
                        'nombre' => 'Demo 2 · Primer punto de recojo',
                        'proveedor_id' => null,
                        'latitud' => -15.608500,
                        'longitud' => -69.968000,
                        'referencia' => 'Parada ficticia para recoger leche. El botón Cómo llegar permite consultar indicaciones hacia este punto.',
                    ],
                    [
                        'nombre' => 'Demo 3 · Segundo punto de recojo',
                        'proveedor_id' => null,
                        'latitud' => -15.606800,
                        'longitud' => -69.970400,
                        'referencia' => 'Última parada del ejemplo. No corresponde a un proveedor registrado.',
                    ],
                ],
                'trazado' => [
                    ['latitud' => -15.610000, 'longitud' => -69.970000],
                    ['latitud' => -15.609300, 'longitud' => -69.969200],
                    ['latitud' => -15.608500, 'longitud' => -69.968000],
                    ['latitud' => -15.607600, 'longitud' => -69.969000],
                    ['latitud' => -15.606800, 'longitud' => -69.970400],
                ],
            ]);
        });

        $this->command?->info('Ruta demo disponible: '.route('admin.mapa-rutas.show', $ruta));
        $this->command?->info('Asignada al acopiador1; '.count($ruta->paradas ?? []).' paradas.');
    }
}
