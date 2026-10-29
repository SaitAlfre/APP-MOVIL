<?php

namespace Database\Seeders;

use App\Application\Calidad\GestionarInfraccionCalidad;
use App\Application\Calidad\GuardarAnalisisCalidadUseCase;
use App\Application\Liquidaciones\GenerarLiquidacionUseCase;
use App\Application\Liquidaciones\MarcarLiquidacionPagadaUseCase;
use App\Application\Produccion\CancelarLoteProduccionUseCase;
use App\Application\Produccion\CrearLoteProduccionUseCase;
use App\Application\Produccion\FinalizarLoteProduccionUseCase;
use App\Application\Produccion\GestionRecetas;
use App\Application\Produccion\IniciarLoteProduccionUseCase;
use App\Application\Proveedores\CambiarEstadoProveedorUseCase;
use App\Application\Recepcion\RegistrarLlegadaUseCase;
use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Proveedores\EstadoProveedor;
use App\Infrastructure\Persistence\Eloquent as M;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PaginacionDemoSeeder extends Seeder
{
    private string $hashPin;

    private int $dni = 99010000;

    private const NOMBRES = ['Rosa', 'Mario', 'Elena', 'Juan', 'Lucía', 'Pedro', 'Ana', 'Luis', 'Juana', 'Víctor', 'María', 'Edwin', 'Nilda', 'Rubén', 'Teresa', 'Miguel'];

    private const APELLIDOS = ['Mamani', 'Quispe', 'Condori', 'Apaza', 'Huanca', 'Choque', 'Ccama', 'Yucra', 'Ramos', 'Flores', 'Pari', 'Ticona', 'Cutipa', 'Turpo', 'Luque', 'Chambi'];

    /** Carga explícita sobre SQLite local vacía. No borra ni combina bases existentes. */
    public function run(): void
    {
        if (! app()->environment('local') || DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Este escenario solo admite SQLite en el entorno local.');
        }
        if (M\Usuario::exists() || M\Proveedor::exists() || M\Producto::exists()) {
            throw new RuntimeException('Carga el escenario en una base local vacía para evitar duplicados.');
        }
        $this->hashPin = Hash::make('1234');
        DB::transaction(function (): void {
            $admin = M\Usuario::create(['username' => 'admin', 'nombres' => 'Administrador DEMO Ecolactea',
                'dni' => '99000001', 'pin_hash' => Hash::make('0000'), 'roles' => ['admin'], 'activo' => true]);
            auth('operador')->setUser($admin);
            try {
                $hoy = CarbonImmutable::today('America/Lima');
                foreach (['tarifas_version' => '1', 'inicio_semana' => 'jueves', 'precio_base_litro' => '1.80'] as $clave => $valor) {
                    M\Configuracion::updateOrCreate(['clave' => $clave], ['valor' => $valor, 'actualizado_por' => $admin->id]);
                }
                M\TarifaLeche::create(['desde' => $hoy->subDays(60), 'hasta' => $hoy->addDays(60), 'precio' => 1.80]);
                M\PrecioLitro::create(['vigente_desde' => $hoy->subDays(60), 'precio' => 1.80]);
                $grupos = $this->catalogos($admin);
                $this->command?->info('Catálogos: 128 proveedores, 257 cuentas, 64 rutas y 64 vehículos.');
                $this->acopio($grupos, $hoy, $admin);
                $this->incidencias($admin, $hoy);
                $this->liquidaciones($hoy);
                $this->planta($admin);
                $this->comercial($admin, $hoy);
                $this->organizacion($admin, $hoy);
                $this->rolesYEstados($admin);
                M\Configuracion::create(['clave' => 'demo_paginacion', 'valor' => $hoy->toDateString(), 'actualizado_por' => $admin->id]);
            } finally {
                auth('operador')->forgetUser();
            }
        });
        $this->command?->info('Escenario DEMO cargado. Admin: admin / 0000. Otras cuentas DEMO: PIN 1234.');
    }

    private function nombre(int $numero): string
    {
        return self::NOMBRES[$numero % 16].' '.self::APELLIDOS[intdiv($numero, 4) % 16].' '.self::APELLIDOS[($numero + 7) % 16].' · DEMO '.sprintf('%03d', $numero);
    }

    private function cuenta(string $username, string $nombre, string $rol, int $zonaId): M\Usuario
    {
        return M\Usuario::create(['username' => $username, 'nombres' => $nombre, 'dni' => (string) $this->dni++,
            'pin_hash' => $this->hashPin, 'roles' => [$rol], 'zona_id' => $zonaId, 'activo' => true]);
    }

    /** @return list<array{zona: M\Zona, vehiculo: M\Vehiculo, acopiador: M\Usuario, calidad: M\Usuario, proveedores: array}> */
    private function catalogos(M\Usuario $admin): array
    {
        $grupos = [];
        foreach (['FAON-MARKAPAJO' => 'faon', 'MORO VIEJO-PANCHA' => 'moro', 'COLLANA I-YASIN-HUAN' => 'collana', 'PLANTA-COLLANA II' => 'planta'] as $nombre => $clave) {
            $zona = M\Zona::create(['nombre' => $nombre, 'activo' => true]);
            for ($sector = 1; $sector <= 16; $sector++) {
                $numero = count($grupos) + 1;
                $tipo = $clave === 'moro' ? 'motocarga' : 'camion';
                $vehiculo = M\Vehiculo::create(['nombre' => ($tipo === 'camion' ? 'Camión' : 'Motocarga').' DEMO '.$numero,
                    'placa' => sprintf('DMO-%03d', $numero), 'tipo' => $tipo, 'activo' => true]);
                $acopiador = $this->cuenta(sprintf('acopio_demo_%03d', $numero), $this->nombre($numero), 'acopiador', $zona->id);
                $calidad = $this->cuenta(sprintf('calidad_demo_%03d', $numero), $this->nombre($numero + 64), 'calidad', $zona->id);
                $proveedores = [];
                for ($p = 1; $p <= 2; $p++) {
                    $indice = ($numero - 1) * 2 + $p;
                    $cuenta = $this->cuenta(sprintf('proveedor_demo_%03d', $indice), $this->nombre($indice + 128), 'proveedor', $zona->id);
                    $ubicacion = SimulacionHuataSeeder::ubicacionDePrueba($clave, ($sector - 1) * 2 + $p);
                    $proveedores[] = M\Proveedor::create(['codigo' => sprintf('DEMO-PRV-%03d', $indice),
                        'nombres' => $cuenta->nombres, 'dni' => $cuenta->dni, 'telefono' => (string) (900100000 + $indice),
                        'direccion' => 'Parcela DEMO '.$indice.', sector '.$sector.', '.$nombre.', Huata, Puno',
                        'zona_id' => $zona->id, 'tachos' => 3, 'capacidad_tacho_l' => 20, 'estado' => 'activo',
                        'usuario_id' => $cuenta->id, 'creado_por_usuario_id' => $admin->id, 'dueno' => $cuenta->nombres,
                        ...($indice % 16 === 0 ? [] : $ubicacion + ['ubicacion_origen' => 'web', 'ubicacion_por' => $admin->id, 'ubicacion_en' => now()])]);
                }
                M\Ruta::create(['codigo' => sprintf('DEMO-RUT-%03d', $numero), 'nombre' => 'Sector DEMO '.$sector.' · '.$nombre,
                    'zona_id' => $zona->id, 'acopiador_id' => $acopiador->id, 'tipo_vehiculo' => $tipo, 'activo' => true,
                    'paradas' => collect($proveedores)->filter(fn ($p) => $p->latitud !== null)->map(fn ($p) => [
                        'nombre' => $p->nombres, 'proveedor_id' => $p->id, 'latitud' => $p->latitud, 'longitud' => $p->longitud, 'referencia' => $p->direccion,
                    ])->values()->all(), 'trazado' => [], 'mapa_version' => 1,
                    'indicaciones' => 'DEMO: coordenadas aproximadas; traza el recorrido para practicar.']);
                $grupos[] = compact('zona', 'vehiculo', 'acopiador', 'calidad', 'proveedores');
            }
        }

        return $grupos;
    }

    private function acopio(array $grupos, CarbonImmutable $hoy, M\Usuario $admin): void
    {
        $guardar = app(GuardarAnalisisCalidadUseCase::class);
        for ($dias = 20; $dias >= 0; $dias--) {
            $dia = $hoy->subDays($dias);
            foreach ($grupos as $n => $grupo) {
                $salida = $dia->setTime(5, $n % 60)->utc();
                $abierta = $dias === 0 && $n % 4 === 0;
                $jornada = M\Jornada::create(['usuario_id' => $grupo['acopiador']->id, 'zona_id' => $grupo['zona']->id,
                    'vehiculo_id' => $grupo['vehiculo']->id, 'fecha' => $dia, 'abierta_en' => $salida,
                    'cerrada_en' => $abierta ? null : $salida->addHours(3), 'uuid_movil' => (string) Str::uuid()]);
                foreach ($grupo['proveedores'] as $p => $proveedor) {
                    $hora = $salida->addMinutes(20 + $p * 20);
                    if ($dias > 0 && ($proveedor->id + $dias) % 23 === 0) {
                        M\SinRecojo::create(['uuid_movil' => (string) Str::uuid(), 'jornada_id' => $jornada->id,
                            'proveedor_id' => $proveedor->id, 'usuario_id' => $grupo['acopiador']->id, 'zona_id' => $grupo['zona']->id,
                            'fecha' => $dia, 'motivo' => 'SIN_LECHE', 'detalle' => 'DEMO: sin producción de leche ese día.',
                            'registrada_en' => $hora, 'actualizado_en' => $hora->getTimestampMs()]);

                        continue;
                    }
                    $litros = round(18 + ($proveedor->id % 27) + ($dias % 5) * 0.7, 1);
                    M\Entrega::create(['jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id,
                        'usuario_id' => $grupo['acopiador']->id, 'zona_id' => $grupo['zona']->id, 'vehiculo_id' => $grupo['vehiculo']->id,
                        'litros' => $litros, 'tachos' => (int) ceil($litros / 20), 'registrado_en' => $hora,
                        'observaciones' => 'DEMO: recojo ficticio para comprobar filtros, totales y paginación.',
                        'anulada' => false, 'uuid_movil' => (string) Str::uuid()]);
                    $caso = ($proveedor->id + $dias) % 10;
                    $valores = ['temperatura' => $caso === 8 ? 10.5 : 5.0 + ($n % 10) / 10,
                        'grasa' => 3.5 + ($p % 3) / 10, 'sng' => 8.7, 'densidad' => 1.030, 'proteina' => 3.2,
                        'lactosa' => 4.7, 'sales' => 0.7, 'solidos' => 12.2, 'agua' => $caso === 9 ? 3.0 : 0.0,
                        'congelacion' => -0.530, 'ph' => 6.7];
                    $guardar->ejecutar(['proveedor_id' => $proveedor->id, 'registrado_en' => $hora->addMinutes(10),
                        'valores' => $valores, 'volumen_l' => $litros, 'codigo_muestra' => 'DEMO-'.$dia->format('ymd').'-'.$proveedor->id,
                        'lote_recipiente' => 'TACHO-'.$proveedor->codigo, 'serial' => 'LACTO-DEMO-'.($n + 1),
                        'origen' => $caso % 2 === 0 ? 'ESCANER' : 'MANUAL', 'apariencia' => 'Blanca homogénea',
                        'observaciones' => 'DEMO: medición ficticia; no representa una certificación de calidad.',
                        ...($caso === 7 ? ['estado' => EstadoAnalisis::Repetir, 'alertas' => ['DEMO: repetir la muestra por lectura inestable.']] : [])], $grupo['calidad']);
                }
                $total = (float) M\Entrega::where('jornada_id', $jornada->id)->sum('litros');
                if (($dias > 0 || $n % 4 >= 2) && $total > 0) {
                    $merma = $n % 3 === 0 ? 0.5 : 0.0;
                    app(RegistrarLlegadaUseCase::class)->ejecutar($jornada->id, $salida->addHours(4)->toDateTimeImmutable(),
                        $total - $merma, $merma ? 'DEMO: merma de traslado medida en planta.' : null, 'Recepción DEMO', $admin->id);
                }
            }
        }
        $this->command?->info('Acopio: 21 días de jornadas, entregas, análisis LactoScan y recepciones relacionados.');
    }

    private function incidencias(M\Usuario $admin, CarbonImmutable $hoy): void
    {
        $gestor = app(GestionarInfraccionCalidad::class);
        foreach (M\Proveedor::orderBy('id')->get() as $n => $proveedor) {
            $analisis = M\AnalisisCalidad::where('proveedor_id', $proveedor->id)->where('estado', '!=', 'APROBADO')
                ->where('registrado_en', '<', $hoy->utc())->latest('registrado_en')->first();
            if ($analisis === null) {
                continue;
            }
            $incidencia = $gestor->registrar($analisis, $admin, 'DEMO: revisar los parámetros alertados en la muestra '.$analisis->codigo_muestra);
            if ($n >= 64) {
                $gestor->resolver($incidencia, $admin, $n < 96 ? 'confirmar' : 'descartar',
                    'DEMO: decisión de prueba sustentada en la revisión del análisis y la contramuestra.', $n < 96 ? 0.20 : null);

                continue;
            }
            $entrega = M\Entrega::where('proveedor_id', $proveedor->id)->latest('registrado_en')->firstOrFail();
            M\ReclamoProveedor::create(['proveedor_id' => $proveedor->id, 'entrega_id' => $entrega->id,
                'litros_originales' => $entrega->litros, 'litros_solicitados' => (float) $entrega->litros + 1.5,
                'motivo' => 'DEMO: solicito revisar la medida de mis tachos.', 'estado' => $n % 5 === 0 ? 'rechazado' : 'pendiente',
                'respuesta' => $n % 5 === 0 ? 'DEMO: se verificó la medida original con la recepción en planta.' : null,
                'resuelto_por' => $n % 5 === 0 ? $admin->id : null, 'resuelto_en' => $n % 5 === 0 ? now() : null,
                'uuid_movil' => (string) Str::uuid()]);
            $control = M\ControlCalidad::where('entrega_id', $entrega->id)->firstOrFail();
            $estado = ['pendiente', 'aprobada', 'rechazada'][$n % 3];
            M\Sancion::create(['control_calidad_id' => $control->id, 'proveedor_id' => $proveedor->id, 'tipo' => 'calidad',
                'severidad' => 'leve', 'descuento' => 5, 'motivo' => 'DEMO: propuesta por incumplimiento de limpieza de tachos.',
                'estado' => $estado, 'resuelto_por' => $estado === 'pendiente' ? null : $admin->id, 'resuelto_en' => $estado === 'pendiente' ? null : now()]);
        }
    }

    private function planta(M\Usuario $admin): void
    {
        $materiales = [];
        $gestion = app(GestionRecetas::class);
        for ($n = 1; $n <= 64; $n++) {
            $material = M\Material::create(['nombre' => ['Sal', 'Cuajo', 'Cultivo láctico', 'Azúcar', 'Envase', 'Tapa', 'Etiqueta', 'Ácido cítrico'][($n - 1) % 8].sprintf(' DEMO %03d', $n),
                'unidad' => ['g', 'mL', 'mL', 'g', 'unidad', 'unidad', 'unidad', 'g'][($n - 1) % 8], 'existencia' => 10000, 'stock_minimo' => 100]);
            $materiales[] = $material;
            DB::table('movimientos_material')->insert(['material_id' => $material->id, 'usuario_id' => $admin->id,
                'tipo' => 'entrada', 'cantidad' => 10000, 'motivo' => 'DEMO: inventario inicial ficticio.', 'fecha' => now()]);
            $litros = $n % 4 === 0 ? 1.0 : 5.0;
            $receta = $gestion->actualizar(new M\Receta, ['nombre' => sprintf('Receta láctea DEMO %03d', $n),
                'unidad_base' => 'unidad', 'litros_tanda' => $litros * 10, 'rinde_unidades' => 10,
                'otros_insumos' => 'DEMO: tanda ficticia de diez unidades.'], [['material_id' => $material->id, 'cantidad' => 20]]);
            $stock = $n <= 48 ? 100 + $n : ($n <= 56 ? 5 : 0);
            $producto = M\Producto::create(['codigo' => sprintf('DEMO-PROD-%03d', $n),
                'nombre' => ['Queso fresco', 'Queso paria', 'Queso andino', 'Yogur natural', 'Queso mozzarella', 'Queso madurado', 'Queso con hierbas', 'Yogur de fresa'][($n - 1) % 8].sprintf(' DEMO %03d', $n),
                'descripcion' => 'Producto ficticio para recorrer catálogo, stock, ventas y producción.',
                'presentacion' => $n % 4 === 0 ? '1 L' : '1 kg', 'unidad_produccion' => 'unidad',
                'contenido_por_unidad' => 1, 'unidad_contenido' => $n % 4 === 0 ? 'L' : 'kg',
                'precio_proveedor' => 12 + $n % 10, 'precio_cliente' => 16 + $n % 10, 'precio_mayorista' => 14 + $n % 10,
                'existencia' => $stock, 'stock_minimo' => 20, 'activo' => true]);
            $gestion->vincular($producto->id, $receta->id);
            if ($stock > 0) {
                M\MovimientoProducto::create(['producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => $stock,
                    'unidad' => 'unidad', 'motivo' => 'DEMO: stock inicial ficticio.', 'usuario_id' => $admin->id, 'fecha' => now()]);
            }
        }
        $productos = M\Producto::orderBy('id')->get();
        for ($n = 0; $n < 80; $n++) {
            $producto = $productos[$n % 64];
            $litros = (float) $producto->litros_por_unidad * 2;
            $lote = app(CrearLoteProduccionUseCase::class)->ejecutar(new DateTimeImmutable('today'), $producto->id, $litros, $admin->id);
            if ($n % 10 === 0) {
                continue;
            }
            if ($n % 10 === 2) {
                app(CancelarLoteProduccionUseCase::class)->ejecutar($lote->id, 'DEMO: pedido cancelado antes de producir.', $admin->id);

                continue;
            }
            app(IniciarLoteProduccionUseCase::class)->ejecutar($lote->id, $admin->id);
            if ($n % 10 !== 1) {
                app(FinalizarLoteProduccionUseCase::class)->ejecutar($lote->id, $litros, 0, $admin->id, 2);
            }
        }
        foreach (array_slice($materiales, -8) as $n => $material) {
            $saldo = $n < 4 ? 50 : 0;
            DB::table('movimientos_material')->insert(['material_id' => $material->id, 'usuario_id' => $admin->id,
                'tipo' => 'ajuste', 'cantidad' => $saldo - (float) $material->fresh()->existencia,
                'motivo' => 'DEMO: salida histórica para practicar alertas de stock.', 'fecha' => now()]);
            $material->update(['existencia' => $saldo]);
        }
        $this->command?->info('Planta: 64 productos, recetas e insumos; 80 lotes con movimientos y estados.');
    }

    private function comercial(M\Usuario $admin, CarbonImmutable $hoy): void
    {
        $clientes = [];
        for ($n = 1; $n <= 128; $n++) {
            $clientes[] = M\Cliente::create(['codigo' => sprintf('DEMO-CLI-%03d', $n), 'tipo' => $n <= 64 ? 'minorista' : 'mayorista',
                'nombre' => ($n <= 64 ? 'Bodega' : 'Distribuidora').' DEMO '.$n.' '.$this->nombre($n),
                'documento' => (string) ($n <= 64 ? 99100000 + $n : 20990000000 + $n), 'celular' => (string) (901000000 + $n),
                'ciudad' => ['Huata', 'Puno', 'Juliaca', 'Arequipa', 'Lima', 'Cusco'][$n % 6], 'activo' => true]);
        }
        $productos = M\Producto::where('id', '<=', 48)->orderBy('id')->get();
        $proveedores = M\Proveedor::orderBy('id')->get();
        for ($n = 0; $n < 192; $n++) {
            $cliente = $clientes[$n % 128];
            $proveedor = $n % 6 === 0 ? $proveedores[$n % 128] : null;
            $tipo = $proveedor !== null ? 'proveedor' : ($cliente->tipo === 'mayorista' ? 'mayorista' : 'cliente');
            $estado = $n < 128 ? 'completada' : ($n < 176 ? 'pendiente' : 'anulada');
            $fecha = $hoy->subDays($n % 10)->setTime(11, $n % 60)->utc();
            $lineas = [];
            for ($p = 0; $p < 2; $p++) {
                $producto = $productos[($n * 2 + $p) % 48];
                $cantidad = 1 + $n % 3;
                $precio = (float) $producto->{'precio_'.$tipo};
                $lineas[] = compact('producto', 'cantidad', 'precio');
            }
            $subtotal = array_sum(array_map(fn ($linea) => $linea['cantidad'] * $linea['precio'], $lineas));
            $venta = M\Venta::create(['codigo' => sprintf('DEMO-VTA-%04d', $n + 1), 'cliente_id' => $proveedor === null ? $cliente->id : null,
                'proveedor_id' => $proveedor?->id, 'tipo_comprador' => $tipo, 'usuario_id' => $admin->id,
                'subtotal' => $subtotal, 'descuento' => $n % 5 === 0 ? 2 : 0, 'total' => $subtotal - ($n % 5 === 0 ? 2 : 0),
                'estado' => $estado, 'vendida_en' => $fecha, 'observaciones' => 'DEMO: venta ficticia para practicar el despacho.']);
            foreach ($lineas as $linea) {
                $producto = $linea['producto'];
                M\VentaDetalle::create(['venta_id' => $venta->id, 'producto_id' => $producto->id,
                    'cantidad' => $linea['cantidad'], 'precio_unitario' => $linea['precio'], 'subtotal' => $linea['cantidad'] * $linea['precio']]);
                if ($estado === 'completada') {
                    $producto->decrement('existencia', $linea['cantidad']);
                    M\MovimientoProducto::create(['producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => -$linea['cantidad'],
                        'unidad' => $producto->unidad_produccion, 'motivo' => 'Despacho '.$venta->codigo,
                        'usuario_id' => $admin->id, 'fecha' => $fecha]);
                }
            }
        }
    }

    private function organizacion(M\Usuario $admin, CarbonImmutable $hoy): void
    {
        $calidad = M\Usuario::whereJsonContains('roles', 'calidad')->orderBy('id')->get();
        $zonas = M\Zona::orderBy('id')->get();
        foreach (M\Proveedor::orderBy('id')->get() as $n => $proveedor) {
            $tecnico = $calidad[intdiv($n, 2)];
            DB::table('planes_control_calidad')->insert(['fecha' => $hoy->toDateString(), 'usuario_id' => $tecnico->id,
                'zona_id' => $proveedor->zona_id, 'proveedor_id' => $proveedor->id, 'lugar' => $n % 2 === 0 ? 'campo' : 'planta',
                'motivo' => ['sorpresa', 'repeticion', 'seguimiento'][$n % 3],
                'estado' => ['programado', 'en_ejecucion', 'completado', 'cancelado'][$n % 4],
                'observacion' => 'DEMO: programación ficticia para recorrer el calendario.', 'creado_por' => $admin->id,
                'created_at' => now(), 'updated_at' => now()]);
            $estado = $n < 80 ? 'PENDIENTE' : ($n < 104 ? 'APROBADA' : 'RECHAZADA');
            M\SolicitudTraslado::create(['uuid_movil' => (string) Str::uuid(), 'proveedor_id' => $proveedor->id,
                'zona_destino_id' => $zonas[($zonas->search(fn ($z) => $z->id === $proveedor->zona_id) + 1) % 4]->id,
                'motivo' => 'DEMO: cambio de parcela solicitado por el proveedor.', 'estado' => $estado,
                'fecha_efectiva' => $hoy->addDays(4 + $n % 5), 'resuelto_por' => $estado === 'PENDIENTE' ? null : $admin->id,
                'resuelto_en' => $estado === 'PENDIENTE' ? null : now(),
                'respuesta' => $estado === 'PENDIENTE' ? null : 'DEMO: decisión administrativa de prueba.']);
        }
        for ($n = 1; $n <= 64; $n++) {
            $estado = ['publicado', 'borrador', 'programado', 'publicado'][$n % 4];
            M\Comunicado::create(['codigo' => sprintf('DEMO-COM-%03d', $n),
                'titulo' => ['Limpieza de tachos', 'Horario de recojo', 'Capacitación en frío', 'Recepción en planta', 'Liquidación semanal', 'Campaña sanitaria'][$n % 6].' · DEMO '.$n,
                'contenido' => 'Comunicado ficticio de Ecolactea Digital para recorrer el panel. Confirma los horarios de tu zona, mantén limpios los recipientes y consulta tus entregas y pagos. Referencia de prueba '.$n.'.',
                'audiencia' => ['todos', 'proveedores', 'acopiadores', 'calidad'][$n % 4], 'estado' => $estado,
                'publicar_en' => $estado === 'programado' ? now()->addDays(2) : null,
                'publicado_en' => $estado === 'publicado' ? now()->subDays($n % 7) : null, 'autor_id' => $admin->id]);
        }
    }

    private function rolesYEstados(M\Usuario $admin): void
    {
        $modulos = array_keys(config('modulos_roles'));
        for ($n = 1; $n <= 60; $n++) {
            DB::table('perfiles_roles')->insert(['nombre' => sprintf('Consulta DEMO sector %03d', $n), 'base' => null,
                'activo' => $n <= 52, 'permisos' => json_encode([$modulos[($n - 1) % count($modulos)] => ['ver']]),
                'campos' => '[]', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (M\Proveedor::orderBy('id')->get() as $n => $proveedor) {
            if ($n % 20 === 19 || $n % 13 === 12) {
                app(CambiarEstadoProveedorUseCase::class)->ejecutar($proveedor->id,
                    $n % 20 === 19 ? EstadoProveedor::Retirado : EstadoProveedor::Suspendido,
                    'DEMO: estado de ejemplo para practicar filtros y restauración de acceso.', $admin->id);
            }
        }
        app(GestionRecetas::class)->cambiarEstado(M\Receta::orderByDesc('id')->firstOrFail(), false);
        M\Producto::orderByDesc('id')->limit(2)->get()->each(fn ($p) => $p->update(['activo' => false]));
    }

    private function liquidaciones(CarbonImmutable $hoy): void
    {
        for ($semana = 2; $semana >= 1; $semana--) {
            $inicio = $hoy->startOfWeek(4)->subWeeks($semana);
            $fin = $inicio->addDays(6);
            M\SemanaOperativa::create(['inicio' => $inicio, 'fin' => $fin, 'estado' => $semana === 2 ? 'cerrada' : 'abierta']);
            foreach (M\Proveedor::orderBy('id')->get() as $n => $proveedor) {
                $liquidacion = app(GenerarLiquidacionUseCase::class)->ejecutar($proveedor->id, $inicio->toDateTimeImmutable(), $fin->toDateTimeImmutable());
                if ($semana === 2 || $n % 3 === 0) {
                    app(MarcarLiquidacionPagadaUseCase::class)->ejecutar($liquidacion->id);
                }
            }
        }
    }
}
