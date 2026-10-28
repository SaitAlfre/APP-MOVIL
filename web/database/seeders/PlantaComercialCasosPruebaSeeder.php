<?php

namespace Database\Seeders;

use App\Application\Produccion\GestionRecetas;
use App\Infrastructure\Persistence\Eloquent as M;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlantaComercialCasosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        DB::transaction(function (): void {
            $zona = M\Zona::firstOrCreate(['nombre' => 'Huata DEMO Comercial'], ['activo' => true]);
            M\Proveedor::firstOrCreate(['codigo' => 'DEMO-COM-001'], ['nombres' => 'Proveedor Huata DEMO comprador',
                'dni' => '99009601', 'zona_id' => $zona->id, 'tachos' => 2, 'capacidad_tacho_l' => 40, 'estado' => 'activo']);
            foreach ([['DEMO-COM-AQP', 'mayorista', 'Distribuidor Arequipa DEMO', '99009600001', 'Arequipa'],
                ['DEMO-COM-LIM', 'mayorista', 'Distribuidor Lima DEMO', '99009600002', 'Lima'],
                ['DEMO-COM-HUA', 'minorista', 'Cliente Huata DEMO', '99009602', 'Huata']] as [$codigo, $tipo, $nombre, $documento, $ciudad]) {
                M\Cliente::firstOrCreate(compact('codigo'), compact('tipo', 'nombre', 'documento', 'ciudad') + ['activo' => true]);
            }
            foreach ([['Queso paria fresco DEMO Comercial', '1 kg', 9], ['Queso paria pasteurizado DEMO Comercial', '1 kg', 9.5], ['Yogur natural DEMO sin receta', '1 L', null]] as [$nombre, $presentacion, $litros]) {
                $producto = M\Producto::firstOrCreate(compact('nombre'), ['presentacion' => $presentacion, 'unidad_produccion' => 'unidad',
                    'litros_por_unidad' => $litros, 'precio_proveedor' => 15, 'precio_cliente' => 20, 'precio_mayorista' => 17,
                    'existencia' => 0, 'activo' => true, 'otros_insumos' => $litros !== null ? 'DEMO: valores ficticios para probar el sistema.' : null]);
                if ($producto->wasRecentlyCreated && $litros !== null) {
                    $gestion = app(GestionRecetas::class);
                    $receta = $gestion->actualizar(new M\Receta, ['nombre' => $nombre, 'unidad_base' => 'unidad', 'litros_por_unidad' => $litros,
                        'otros_insumos' => 'DEMO: valores ficticios para probar el sistema.'], []);
                    $gestion->vincular($producto->id, $receta->id);
                }
            }
        });
        $this->command?->info('DEMO Comercial: proveedor de Huata, clientes de Huata, Arequipa y Lima; productos y precios ficticios. No se generan ventas ni existencias.');
    }
}
