<?php

namespace Database\Seeders;

use App\Application\Produccion\GestionRecetas;
use App\Infrastructure\Persistence\Eloquent as M;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProduccionCasosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        DB::transaction(function (): void {
            $admin = M\Usuario::query()->where('activo', true)->whereJsonContains('roles', 'admin')->firstOrFail();
            $zona = M\Zona::firstOrCreate(['nombre' => 'DEMO Producción'], ['activo' => true]);
            $vehiculo = M\Vehiculo::firstOrCreate(['placa' => 'DPR-001'], ['nombre' => 'Camión DEMO Producción', 'activo' => true]);
            $acopiador = M\Usuario::firstOrCreate(['username' => 'demo_produccion_acopio'], [
                'nombres' => 'Acopiador DEMO Producción', 'dni' => '99009501',
                'pin_hash' => Str::random(32), 'roles' => ['acopiador'], 'activo' => true, 'zona_id' => $zona->id,
            ]);

            $materiales = [];
            foreach ([['Sal DEMO Producción', 'g', 1000], ['Cuajo DEMO Producción', 'mL', 100], ['Azúcar DEMO Producción', 'kg', 0]] as [$nombre, $unidad, $cantidad]) {
                $material = M\Material::firstOrCreate(['nombre' => $nombre], ['unidad' => $unidad, 'existencia' => $cantidad]);
                $materiales[] = $material;
                if ($material->wasRecentlyCreated && $cantidad > 0) {
                    DB::table('movimientos_material')->insert([
                        'material_id' => $material->id, 'usuario_id' => $admin->id, 'tipo' => 'entrada',
                        'cantidad' => $cantidad, 'motivo' => 'DEMO: insumos para pruebas de producción', 'fecha' => now(),
                    ]);
                }
            }

            foreach ([
                ['Queso DEMO Producción', '1 kg', 10, [0 => 20, 1 => 2]],
                ['Yogur DEMO sin azúcar', '1 L', 1, [2 => 0.1]],
            ] as [$nombre, $presentacion, $litros, $ingredientes]) {
                $producto = M\Producto::firstOrCreate(['nombre' => $nombre], [
                    'presentacion' => $presentacion, 'unidad_produccion' => 'unidad', 'litros_por_unidad' => $litros,
                    'existencia' => 0, 'activo' => true, 'otros_insumos' => 'DEMO: receta ficticia para practicar',
                ]);
                if ($producto->wasRecentlyCreated) {
                    $filas = [];
                    foreach ($ingredientes as $indice => $cantidad) {
                        $filas[] = ['material_id' => $materiales[$indice]->id, 'cantidad' => $cantidad];
                    }
                    $gestion = app(GestionRecetas::class);
                    $receta = $gestion->actualizar(new M\Receta, ['nombre' => $nombre, 'unidad_base' => 'unidad', 'litros_tanda' => $litros, 'rinde_unidades' => 1,
                        'otros_insumos' => 'DEMO: receta ficticia para practicar'], $filas);
                    $gestion->vincular($producto->id, $receta->id);
                }
            }

            if (M\Jornada::where('usuario_id', $acopiador->id)->whereDate('fecha', today())->exists()) {
                return;
            }
            $jornada = M\Jornada::create([
                'usuario_id' => $acopiador->id, 'fecha' => today()->toDateString(),
                'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id, 'abierta_en' => today()->addHours(6),
                'cerrada_en' => today()->addHours(9), 'seguimiento_activo' => false,
            ]);

            foreach ([['aprobado', 500], ['rechazado', 50], ['pendiente', 50]] as $indice => [$resultado, $litros]) {
                $proveedor = M\Proveedor::firstOrCreate(['codigo' => 'DEMO-PROD-'.($indice + 1)], [
                    'nombres' => 'Proveedor DEMO Producción '.$resultado, 'dni' => '9900951'.$indice,
                    'zona_id' => $zona->id, 'tachos' => 13, 'capacidad_tacho_l' => 40, 'estado' => 'activo',
                ]);
                $entrega = M\Entrega::create([
                    'jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id, 'usuario_id' => $acopiador->id,
                    'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id, 'litros' => $litros,
                    'tachos' => (int) ceil($litros / 40), 'registrado_en' => today()->addHours(8),
                    'observaciones' => 'DEMO: producción, calidad '.$resultado, 'anulada' => false,
                ]);
                if ($resultado !== 'pendiente') {
                    M\ControlCalidad::create([
                        'entrega_id' => $entrega->id, 'usuario_id' => $admin->id, 'resultado' => $resultado,
                        'temperatura_c' => $resultado === 'aprobado' ? 4 : 12,
                        'acidez' => $resultado === 'aprobado' ? 16 : 23,
                        'observaciones' => 'DEMO: evaluación ficticia', 'evaluado_en' => today()->addHours(9),
                    ]);
                }
            }

            M\RecepcionAcopio::create([
                'jornada_id' => $jornada->id, 'llegada_en' => today()->addHours(9),
                'litros_recolectados' => 600, 'litros_medidos' => 595,
                'motivo_diferencia' => 'DEMO: merma de traslado de 5 L', 'usuario_id' => $admin->id,
            ]);
        });

        $this->command?->info('Casos DEMO disponibles: queso con insumos, yogur sin azúcar, leche rechazada y calidad pendiente. Repetir conserva los datos y consumos existentes.');
    }
}
