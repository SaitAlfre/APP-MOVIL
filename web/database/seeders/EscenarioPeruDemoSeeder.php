<?php

namespace Database\Seeders;

use App\Application\Produccion\CrearLoteProduccionUseCase;
use App\Application\Produccion\FinalizarLoteProduccionUseCase;
use App\Application\Produccion\IniciarLoteProduccionUseCase;
use App\Infrastructure\Persistence\Eloquent as M;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EscenarioPeruDemoSeeder extends Seeder
{
    /** Carga explícita, aditiva y transaccional; repetir no duplica ni sobrescribe. */
    public function run(): void
    {
        DB::transaction(function (): void {
            if (M\Configuracion::where('clave', 'demo_peru_v1')->exists()) {
                $this->command?->info('El escenario ya está cargado. No se duplicaron datos.');

                return;
            }
            $admin = M\Usuario::where('username', 'admin')->firstOrFail();
            $anterior = auth('operador')->user();
            auth('operador')->setUser($admin);
            try {
                $this->cargar($admin);
                M\Configuracion::create(['clave' => 'demo_peru_v1', 'valor' => now()->toIso8601String(), 'actualizado_por' => $admin->id]);
            } finally {
                if ($anterior !== null) {
                    auth('operador')->setUser($anterior);
                } else {
                    auth('operador')->forgetUser();
                }
            }
        });
    }

    private function cargar(M\Usuario $admin): void
    {
        $personal = ['recepcion' => 'María Elena Quispe Mamani', 'calidad' => 'Rosa Luz Apaza Huanca',
            'produccion' => 'José Antonio Condori Choque', 'liquidaciones' => 'Carmen Rosa Flores Ccama',
            'consulta' => 'Luis Alberto Huamán Paredes', 'asistente' => 'Ana Lucía Ramos Chura', 'despacho' => 'Pedro Julián Yucra Vilca'];
        foreach ($personal as $rol => $nombre) {
            $this->usuario('demo_'.$rol, $nombre, 99001000 + array_search($rol, array_keys($personal)), $rol);
        }
        $zonas = ['COLLANA I-YASIN-HUAN', 'FAON-MARKAPAJO', 'MORO VIEJO-PANCHA', 'PLANTA-COLLANA II'];
        $acopiadores = ['Juan Carlos Mamani Quispe', 'Víctor Hugo Apaza Condori', 'Julio César Huanca Flores', 'Miguel Ángel Choque Vilca'];
        $nombres = ['Juana Rosa Quispe Apaza', 'Roberto Mamani Huanca', 'Elena Condori Flores', 'Pedro Luis Choque Ccama', 'María Isabel Chura Yucra', 'Andrés Huamán Paredes', 'Lucía Ramos Mamani', 'Francisco Apaza Vilca', 'Teresa Huanca Condori', 'José Santos Ccama Quispe', 'Rosa Alicia Yucra Flores', 'Edwin Choque Chura'];
        $camiones = ['Isuzu NQR · DEMO', 'Hino 300 · DEMO', 'Mitsubishi Canter · DEMO', 'Hyundai HD78 · DEMO'];
        $base = today();
        $proveedores = [];
        foreach ($zonas as $z => $nombreZona) {
            $zona = M\Zona::firstOrCreate(['nombre' => $nombreZona], ['activo' => true]);
            $vehiculo = M\Vehiculo::firstOrCreate(['placa' => 'D4M-10'.($z + 1)], ['nombre' => $camiones[$z], 'activo' => true]);
            $acopiador = $this->usuario('demo_acopio_'.($z + 1), $acopiadores[$z], 99002001 + $z, 'acopiador');
            M\Ruta::create(['codigo' => 'DEMO-R-'.($z + 1), 'nombre' => 'Ruta de prueba '.$nombreZona, 'zona_id' => $zona->id, 'activo' => true]);
            $grupo = [];
            for ($p = 0; $p < 3; $p++) {
                $n = $z * 3 + $p;
                $cuenta = $this->usuario('demo_prov_'.sprintf('%02d', $n + 1), $nombres[$n], 99003001 + $n, 'proveedor');
                $grupo[] = $proveedores[] = M\Proveedor::create([
                    'codigo' => 'DEMO-PE-'.sprintf('%02d', $n + 1), 'nombres' => $nombres[$n], 'dni' => (string) (99003001 + $n),
                    'direccion' => 'Parcela de prueba '.($p + 1).', '.$nombreZona, 'zona_id' => $zona->id,
                    'tachos' => 3, 'capacidad_tacho_l' => 40, 'estado' => 'activo', 'usuario_id' => $cuenta->id,
                ]);
            }
            foreach ([6, 5, 4, 3, 2, 1, 0] as $dias) {
                $fecha = $base->copy()->subDays($dias);
                $jornada = M\Jornada::create(['usuario_id' => $acopiador->id, 'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id,
                    'fecha' => $fecha->toDateString(), 'abierta_en' => $fecha->copy()->addHours(5),
                    'cerrada_en' => $dias > 0 ? $fecha->copy()->addHours(10) : null, 'seguimiento_activo' => false]);
                $total = 0;
                foreach ($grupo as $p => $proveedor) {
                    $litros = 45 + $z * 7 + $p * 5 + $dias * 2;
                    $total += $litros;
                    $entrega = M\Entrega::create(['jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id, 'usuario_id' => $acopiador->id,
                        'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id, 'litros' => $litros, 'tachos' => (int) ceil($litros / 40),
                        'observaciones' => 'DEMO PERÚ: entrega ficticia', 'registrado_en' => $fecha->copy()->addHours(7)->addMinutes($p * 15), 'anulada' => false]);
                    if ($dias === 0 && $z === 3) {
                        continue;
                    }
                    $resultado = $dias === 0 && $p === 2 ? ($z === 0 ? 'rechazado' : 'observado') : 'aprobado';
                    $control = M\ControlCalidad::create(['entrega_id' => $entrega->id, 'usuario_id' => $admin->id, 'resultado' => $resultado,
                        'temperatura_c' => $resultado === 'rechazado' ? 12 : 4, 'acidez' => $resultado === 'rechazado' ? 23 : 16,
                        'observaciones' => 'DEMO: evaluación ficticia', 'evaluado_en' => $fecha->copy()->addHours(8)]);
                    if ($resultado === 'rechazado') {
                        M\Sancion::firstOrCreate(['control_calidad_id' => $control->id], ['proveedor_id' => $proveedor->id,
                            'tipo' => 'calidad', 'severidad' => 'grave', 'descuento' => 10, 'motivo' => 'DEMO: acidez fuera de rango', 'estado' => 'pendiente']);
                        M\ReclamoProveedor::create(['proveedor_id' => $proveedor->id, 'entrega_id' => $entrega->id,
                            'litros_originales' => $litros, 'litros_solicitados' => $litros + 2, 'motivo' => 'DEMO: revisión del volumen', 'estado' => 'pendiente']);
                    }
                }
                if ($dias > 0 || $z < 3) {
                    M\RecepcionAcopio::create(['jornada_id' => $jornada->id, 'llegada_en' => $fecha->copy()->addHours(9),
                        'litros_recolectados' => $total, 'litros_medidos' => $total - 1, 'motivo_diferencia' => 'DEMO: merma de traslado de 1 litro',
                        'observaciones' => 'Recepción ficticia', 'usuario_id' => $admin->id]);
                }
            }
        }
        foreach ($proveedores as $n => $proveedor) {
            $litros = M\Entrega::where('proveedor_id', $proveedor->id)->whereDate('registrado_en', '<', $base)->sum('litros');
            M\Liquidacion::create(['proveedor_id' => $proveedor->id, 'periodo_inicio' => $base->copy()->subDays(6), 'periodo_fin' => $base->copy()->subDay(),
                'litros_totales' => $litros, 'precio_litro' => 1.8, 'monto_total' => round($litros * 1.8, 2),
                'estado' => $n % 2 === 0 ? 'pagada' : 'pendiente', 'generada_en' => now(), 'pagada_en' => $n % 2 === 0 ? now() : null]);
        }
        $this->produccionYVentas($admin);
        foreach (['publicado', 'borrador'] as $estado) {
            M\Comunicado::create(['codigo' => 'DEMO-COM-'.$estado, 'titulo' => 'DEMO: coordinación del acopio',
                'contenido' => 'Datos ficticios para practicar. Revisar limpieza de tachos y horarios de recepción.',
                'audiencia' => 'todos', 'estado' => $estado, 'autor_id' => $admin->id, 'publicado_en' => $estado === 'publicado' ? now() : null]);
        }
        M\PrecioLitro::firstOrCreate(['vigente_desde' => $base->copy()->subDays(6)->toDateString()], ['precio' => 1.8]);
        M\SemanaOperativa::firstOrCreate(['inicio' => $base->copy()->subDays(6)->toDateString()], ['fin' => $base->toDateString(), 'estado' => 'abierta']);
        M\Configuracion::firstOrCreate(['clave' => 'precio_base_litro'], ['valor' => '1.80', 'actualizado_por' => $admin->id]);
        $filas = M\Ruta::where('codigo', 'like', 'DEMO-R-%')->get(['codigo', 'nombre', 'zona_id'])->toArray();
        $csv = "codigo,nombre,zona_id\n";
        foreach ($filas as $fila) {
            $csv .= implode(',', $fila)."\n";
        }
        Storage::disk('local')->put('importaciones/demo-peru-rutas.csv', $csv);
        M\Importacion::create([
            'token' => 'd3e00000-0000-4000-8000-000000000001', 'tipo' => 'rutas',
            'archivo_original' => 'DEMO-rutas-peru.csv', 'ruta_archivo' => 'importaciones/demo-peru-rutas.csv',
            'estado' => 'procesada', 'filas_total' => 4, 'filas_procesadas' => 4, 'filas_error' => 0,
            'vista_previa' => $filas, 'errores' => [], 'usuario_id' => $admin->id, 'procesada_en' => now(),
        ]);
        $this->command?->info('DEMO PERÚ cargado: 4 zonas, 4 camiones, 23 cuentas, 12 proveedores, 28 jornadas y 84 entregas.');
    }

    private function usuario(string $username, string $nombre, int $dni, string $rol): M\Usuario
    {
        return M\Usuario::firstOrCreate(['username' => $username], ['nombres' => $nombre, 'dni' => (string) $dni,
            'pin_hash' => '1234', 'roles' => [$rol], 'activo' => true]);
    }

    private function produccionYVentas(M\Usuario $admin): void
    {
        $materiales = [];
        foreach ([['Cuajo DEMO', 'ml', 10000], ['Sal DEMO', 'g', 50000], ['Cultivo láctico DEMO', 'ml', 10000]] as [$nombre, $unidad, $stock]) {
            $material = M\Material::create(['nombre' => $nombre, 'unidad' => $unidad, 'existencia' => $stock]);
            $materiales[] = $material;
            DB::table('movimientos_material')->insert(['material_id' => $material->id, 'usuario_id' => $admin->id,
                'tipo' => 'entrada', 'cantidad' => $stock, 'motivo' => 'DEMO: inventario inicial', 'fecha' => now()]);
        }
        foreach ([['Queso fresco DEMO', '1 kg', 8], ['Queso andino DEMO', '900 g', 10], ['Queso mozzarella DEMO', '500 g', 6]] as $n => [$nombre, $presentacion, $litros]) {
            $producto = M\Producto::create(['nombre' => $nombre, 'presentacion' => $presentacion, 'unidad_produccion' => 'unidad',
                'litros_por_unidad' => $litros, 'existencia' => 0, 'activo' => true, 'otros_insumos' => 'Receta ficticia para probar el sistema']);
            foreach ($materiales as $m => $material) {
                DB::table('receta_ingredientes')->insert(['producto_id' => $producto->id, 'material_id' => $material->id, 'cantidad' => [2, 20, 5][$m]]);
            }
            $lote = app(CrearLoteProduccionUseCase::class)->ejecutar(new DateTimeImmutable(today()->subDay()->toDateString()), $producto->id, $litros * 10, $admin->id);
            M\LoteProduccion::whereKey($lote->id)->update(['codigo' => 'DEMO-PE-LOTE-'.($n + 1)]);
            app(IniciarLoteProduccionUseCase::class)->ejecutar($lote->id, $admin->id);
            app(FinalizarLoteProduccionUseCase::class)->ejecutar($lote->id, $litros * 10, 0, $admin->id, 10);
            $cliente = M\Cliente::create(['codigo' => 'DEMO-CLI-'.($n + 1), 'tipo' => ['minorista', 'restaurante', 'mayorista'][$n],
                'nombre' => ['Patricia Quispe Huanca', 'Renato Mamani Apaza', 'Diana Condori Ramos'][$n],
                'documento' => (string) (99004001 + $n), 'ciudad' => ['Puno', 'Juliaca', 'Huata'][$n], 'activo' => true]);
            $venta = M\Venta::create(['codigo' => 'DEMO-VTA-'.($n + 1), 'cliente_id' => $cliente->id, 'usuario_id' => $admin->id,
                'subtotal' => 60, 'descuento' => 0, 'total' => 60, 'estado' => $n === 0 ? 'completada' : 'pendiente', 'vendida_en' => now(), 'observaciones' => 'DEMO: venta ficticia']);
            M\VentaDetalle::create(['venta_id' => $venta->id, 'producto_id' => $producto->id, 'cantidad' => 3, 'precio_unitario' => 20, 'subtotal' => 60]);
            if ($n === 0) {
                $producto->decrement('existencia', 3);
                M\MovimientoProducto::create(['producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => -3, 'unidad' => 'unidad',
                    'motivo' => 'Despacho '.$venta->codigo, 'usuario_id' => $admin->id, 'fecha' => now()]);
            }
        }
    }
}
