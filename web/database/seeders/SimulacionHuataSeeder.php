<?php

namespace Database\Seeders;

use App\Application\Entregas\AnularEntregaUseCase;
use App\Application\Entregas\CorregirEntregaUseCase;
use App\Application\Liquidaciones\GenerarLiquidacionUseCase;
use App\Application\Liquidaciones\MarcarLiquidacionPagadaUseCase;
use App\Application\Recepcion\RegistrarLlegadaUseCase;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\SinRecojo;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Simulación de acopio en Huata (Puno) con las cuatro zonas reales y datos ficticios: proveedores,
 * acopiadores, calidad, rutas, dos semanas de jornadas con recojos, recepción en planta, reclamos y
 * la liquidación pagada de la semana anterior. Las personas, DNI, teléfonos y coordenadas son de
 * prueba. El PIN de todas las cuentas se lee de SIMULACION_PIN en `.env`; nunca se versiona.
 */
class SimulacionHuataSeeder extends Seeder
{
    private const int DIAS = 14;

    private const float CAPACIDAD_TACHO_L = 20.0;

    /** @var array<string, array{nombre: string, clave: string, centro: array{float, float}, comunidades: list<string>}> */
    private const array ZONAS = [
        // Centros aproximados alrededor de la planta de Huata (-15.6137, -69.9762). Faon y Yasin siguen las
        // escuelas que registra OpenStreetMap; Moro Viejo-Pancha y Collana II son supuestos de prueba.
        'faon' => ['nombre' => 'FAON-MARKAPAJO', 'clave' => 'faon', 'centro' => [-15.6265, -69.9515], 'comunidades' => ['Markapajo', 'Faon', 'Markapajo Alto']],
        'moro' => ['nombre' => 'MORO VIEJO-PANCHA', 'clave' => 'moro', 'centro' => [-15.5985, -69.9620], 'comunidades' => ['Moro Viejo', 'Pancha', 'Pancha Grande']],
        'collana' => ['nombre' => 'COLLANA I-YASIN-HUAN', 'clave' => 'collana', 'centro' => [-15.6430, -69.9560], 'comunidades' => ['Collana I', 'Yasin', 'Huan']],
        'planta' => ['nombre' => 'PLANTA-COLLANA II', 'clave' => 'planta', 'centro' => [-15.6115, -69.9860], 'comunidades' => ['Collana II', 'Sector Planta', 'Collana II Bajo']],
    ];

    /** @var list<array{string, string}> */
    private const array PERSONAL = [
        ['acopiador', 'Wilber Ccama Turpo'], ['acopiador', 'Rubén Quispe Machaca'], ['acopiador', 'Nilda Apaza Yucra'], ['acopiador', 'Hernán Cutipa Luque'],
        ['calidad', 'Miguel Vargas Pari'], ['calidad', 'Yeny Choque Calsin'], ['calidad', 'Edwin Ticona Ramos'], ['calidad', 'Marleni Coaquira Flores'],
    ];

    /** @var list<string> */
    private const array NOMBRES = ['Juana', 'Mario', 'Elena', 'Felipe', 'Rosa', 'Teodoro', 'Valentina', 'Gregorio', 'Lucía', 'Saturnino', 'Hilda', 'Alberto',
        'Martina', 'Eusebio', 'Justina', 'Celestino', 'Felícitas', 'Benito', 'Agripina', 'Leandro', 'Catalina', 'Ignacio', 'Paulina', 'Dionisio'];

    /** @var list<string> */
    private const array APELLIDOS = ['Mamani', 'Quispe', 'Condori', 'Apaza', 'Ccama', 'Huanca', 'Cutipa', 'Choque', 'Pari', 'Ticona', 'Calsin',
        'Coaquira', 'Machaca', 'Turpo', 'Yucra', 'Luque', 'Chambi', 'Velásquez', 'Ramos', 'Flores', 'Huarachi', 'Larico', 'Aguilar', 'Sucasaca'];

    private string $pin;

    private int $dni = 70412000;

    /** @var array<int, float> Litros habituales por proveedor. */
    private array $litrosBase = [];

    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->warn('La simulación solo se carga en el entorno local.');

            return;
        }
        $this->pin = (string) env('SIMULACION_PIN', '');
        if (! preg_match('/^\d{4,6}$/', $this->pin)) {
            throw new RuntimeException('Define SIMULACION_PIN (4 a 6 dígitos) en web/.env antes de cargar la simulación.');
        }
        mt_srand(2026);

        $admin = Usuario::query()->whereJsonContains('roles', 'admin')->whereNull('perfil_rol_id')->orderBy('id')->first()
            ?? $this->cuenta('admin', 'Administración Planta Huata', 'admin', null);
        Configuracion::query()->firstOrCreate(['clave' => 'tarifas_version'], ['valor' => '0']);
        TarifaLeche::query()->firstOrCreate(['desde' => '2026-09-01'], ['precio' => 1.80, 'hasta' => '2026-12-31']);

        $zonas = [];
        foreach (array_values(self::ZONAS) as $indice => $datos) {
            $zona = Zona::query()->firstOrCreate(['nombre' => $datos['nombre']], ['activo' => true]);
            // MORO VIEJO-PANCHA se recoge en motocarga por sus trochas angostas; las demás zonas, en camión.
            $tipo = $datos['clave'] === 'moro' ? 'motocarga' : 'camion';
            $vehiculo = Vehiculo::query()->firstOrCreate(['placa' => ['V1A-123', 'V2B-456', 'V3C-789', 'V4D-012'][$indice]],
                ['nombre' => ($tipo === 'motocarga' ? 'Motocarga ' : 'Camión ').($indice + 1), 'activo' => true, 'tipo' => $tipo]);
            $acopiador = $this->cuenta('acop_'.$datos['clave'], self::PERSONAL[$indice][1], 'acopiador', $zona->id);
            $calidad = $this->cuenta('calidad_'.$datos['clave'], self::PERSONAL[$indice + 4][1], 'calidad', $zona->id);
            $proveedores = $this->proveedores($zona, $datos);
            $this->ruta($zona, $acopiador, $proveedores, $datos, $tipo);
            $zonas[] = compact('zona', 'vehiculo', 'acopiador', 'calidad', 'proveedores');
        }

        $hoy = CarbonImmutable::now('America/Lima')->startOfDay();
        foreach ($zonas as $indice => $contexto) {
            for ($dias = self::DIAS - 1; $dias >= 0; $dias--) {
                $this->jornada($contexto, $hoy->subDays($dias), $dias, $indice, $admin);
            }
        }
        $this->reclamos($zonas, $hoy, $admin);
        $this->liquidacionesSemanaAnterior($zonas, $hoy);

        $this->command?->info('Simulación Huata cargada: 4 zonas reales, '.Proveedor::count().' proveedores, '.Jornada::count().' jornadas y '.Entrega::count().' recojos.');
        $this->command?->info('Cuentas: acop_*, calidad_* y prov_*_01..03 por zona. El PIN es el valor de SIMULACION_PIN en web/.env.');
    }

    private function cuenta(string $username, string $nombres, string $rol, ?int $zonaId): Usuario
    {
        return Usuario::query()->firstOrCreate(['username' => $username], [
            'nombres' => $nombres, 'dni' => (string) $this->dni++, 'pin_hash' => $this->pin,
            'activo' => true, 'roles' => [$rol], 'zona_id' => $zonaId,
        ]);
    }

    /**
     * @param  array{nombre: string, clave: string, centro: array{float, float}, comunidades: list<string>}  $datos
     * @return list<Proveedor>
     */
    private function proveedores(Zona $zona, array $datos): array
    {
        $proveedores = [];
        for ($numero = 1; $numero <= 12; $numero++) {
            $nombre = self::NOMBRES[mt_rand(0, count(self::NOMBRES) - 1)].' '.self::APELLIDOS[mt_rand(0, count(self::APELLIDOS) - 1)].' '.self::APELLIDOS[mt_rand(0, count(self::APELLIDOS) - 1)];
            $cuenta = $numero <= 3 ? $this->cuenta(sprintf('prov_%s_%02d', $datos['clave'], $numero), $nombre, 'proveedor', $zona->id) : null;
            $proveedor = Proveedor::query()->firstOrCreate(['codigo' => sprintf('PRV-%s-%02d', strtoupper($datos['clave']), $numero)], [
                'nombres' => $nombre, 'dni' => $cuenta?->dni ?? (string) $this->dni++, 'telefono' => '9'.mt_rand(51000000, 99999999),
                'direccion' => 'Comunidad '.$datos['comunidades'][$numero % 3].', Huata', 'zona_id' => $zona->id,
                'tachos' => mt_rand(1, 3), 'capacidad_tacho_l' => self::CAPACIDAD_TACHO_L, 'estado' => 'activo', 'usuario_id' => $cuenta?->id,
            ]);
            $this->litrosBase[$proveedor->id] = mt_rand(80, 450) / 10;
            $proveedores[] = $proveedor;
        }

        return $proveedores;
    }

    /**
     * @param  list<Proveedor>  $proveedores
     * @param  array{nombre: string, clave: string, centro: array{float, float}, comunidades: list<string>}  $datos
     */
    private function ruta(Zona $zona, Usuario $acopiador, array $proveedores, array $datos, string $tipoVehiculo): void
    {
        $paradas = collect($proveedores)->values()->map(fn (Proveedor $proveedor, int $i) => [
            'nombre' => $proveedor->nombres,
            'proveedor_id' => $proveedor->id,
            ...self::ubicacionDePrueba($datos['clave'], $i + 1),
            'referencia' => $proveedor->direccion,
        ])->all();

        // Los dos últimos proveedores quedan sin ubicar para practicar la captura desde el mapa o el celular.
        foreach ($paradas as $indice => $parada) {
            if ($indice < count($paradas) - 2) {
                Proveedor::query()->whereKey($parada['proveedor_id'])->update([
                    'latitud' => $parada['latitud'], 'longitud' => $parada['longitud'], 'ubicacion_origen' => 'web', 'ubicacion_en' => now(),
                ]);
            }
        }
        $paradas = array_slice($paradas, 0, count($paradas) - 2);

        Ruta::query()->firstOrCreate(['codigo' => 'RUTA-'.strtoupper($datos['clave'])], [
            'nombre' => 'Ruta '.$datos['nombre'], 'zona_id' => $zona->id, 'acopiador_id' => $acopiador->id, 'tipo_vehiculo' => $tipoVehiculo,
            'activo' => true, 'mapa_version' => 1, 'paradas' => $paradas,
            'trazado' => [],
            'indicaciones' => 'SIMULACIÓN: las paradas siguen el orden de recojo, pero sus coordenadas son aproximadas y no están validadas sobre caminos reales.',
        ]);
    }

    /**
     * Ubicación ficticia del proveedor número N de una zona: dispersa hasta ~1,3 km de su centro y
     * siempre igual para la misma zona y número.
     *
     * @return array{latitud: float, longitud: float}
     */
    public static function ubicacionDePrueba(string $clave, int $numero): array
    {
        [$latitud, $longitud] = self::ZONAS[$clave]['centro'];
        $angulo = (crc32($clave.'-a-'.$numero) % 3600) / 3600 * 2 * M_PI;
        $radio = 0.002 + (crc32($clave.'-r-'.$numero) % 1000) / 1000 * 0.010;

        return ['latitud' => round($latitud + $radio * sin($angulo), 6), 'longitud' => round($longitud + $radio * cos($angulo), 6)];
    }

    /**
     * Recojos de un día. Hoy deja una jornada en cada etapa para revisar Acopio del día; los días
     * anteriores quedan recibidos en planta, casi siempre con una pequeña merma de transporte.
     *
     * @param  array{zona: Zona, vehiculo: Vehiculo, acopiador: Usuario, calidad: Usuario, proveedores: list<Proveedor>}  $contexto
     */
    private function jornada(array $contexto, CarbonImmutable $dia, int $dias, int $indiceZona, Usuario $admin): void
    {
        $etapaHoy = ['recibida', 'recibida_exacta', 'por_recibir', 'en_ruta'][$indiceZona];
        $abierta = $dias === 0 && $etapaHoy === 'en_ruta';
        $salida = $dia->setTime(4, 30 + mt_rand(0, 20));
        $jornada = Jornada::query()->create([
            'usuario_id' => $contexto['acopiador']->id, 'zona_id' => $contexto['zona']->id, 'vehiculo_id' => $contexto['vehiculo']->id,
            'fecha' => $dia->toDateString(), 'abierta_en' => $salida->utc(), 'cerrada_en' => $abierta ? null : $salida->addMinutes(mt_rand(170, 230))->utc(),
            'seguimiento_activo' => false, 'uuid_movil' => (string) Str::uuid(),
        ]);

        $hora = $salida->addMinutes(12);
        $recojos = [];
        foreach ($contexto['proveedores'] as $posicion => $proveedor) {
            if ($abierta && $posicion >= 7) {
                break;
            }
            $hora = $hora->addMinutes(mt_rand(6, 14));
            if (mt_rand(1, 100) <= 6) {
                SinRecojo::query()->create([
                    'uuid_movil' => (string) Str::uuid(), 'jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id,
                    'usuario_id' => $contexto['acopiador']->id, 'zona_id' => $contexto['zona']->id, 'fecha' => $dia->toDateString(),
                    'motivo' => 'SIN_LECHE', 'detalle' => 'El proveedor no tenía leche para entregar', 'registrada_en' => $hora->utc(),
                    'actualizado_en' => $hora->getTimestampMs(),
                ]);

                continue;
            }
            $litros = round($this->litrosBase[$proveedor->id] * mt_rand(85, 115) / 100, 1);
            $desdeWeb = mt_rand(1, 100) <= 8;
            $recojos[] = Entrega::query()->create([
                'jornada_id' => $jornada->id, 'proveedor_id' => $proveedor->id, 'usuario_id' => $contexto['acopiador']->id,
                'zona_id' => $contexto['zona']->id, 'vehiculo_id' => $contexto['vehiculo']->id, 'litros' => $litros,
                'tachos' => max(1, (int) ceil($litros / self::CAPACIDAD_TACHO_L)), 'registrado_en' => $hora->utc(), 'anulada' => false,
                'observaciones' => $desdeWeb ? 'Registrado desde la planta por falta de señal en la ruta' : null,
                'uuid_movil' => $desdeWeb ? null : (string) Str::uuid(),
            ]);
        }

        $this->ajustes($recojos, $admin);
        $this->calidad($recojos, $contexto['calidad']);

        $recibir = $dias > 0 || in_array($etapaHoy, ['recibida', 'recibida_exacta'], true);
        $litrosCampo = (float) Entrega::query()->where('jornada_id', $jornada->id)->where('anulada', false)->sum('litros');
        if ($recibir && $litrosCampo > 0) {
            $diferencia = match (true) {
                $dias === 0 => $etapaHoy === 'recibida' ? -4.5 : 0.0,
                mt_rand(1, 100) <= 20 => 0.0,
                mt_rand(1, 100) <= 8 => 1.0,
                default => -mt_rand(5, 35) / 10,
            };
            app(RegistrarLlegadaUseCase::class)->ejecutar(
                jornadaId: $jornada->id,
                llegadaEn: DateTimeImmutable::createFromInterface($salida->addHours(4)->utc()),
                litrosMedidos: round($litrosCampo + $diferencia, 2),
                motivoDiferencia: match (true) {
                    $diferencia < 0 => ['Derrame al pasar la trocha', 'Leche que quedó en los porongos', 'Se volcó un porongo al cargar el camión', 'Pérdida al trasvasar en la planta'][mt_rand(0, 3)],
                    $diferencia > 0 => 'La medición de campo en un tacho quedó baja',
                    default => null,
                },
                observaciones: null,
                usuarioId: $contexto['calidad']->id,
            );
        }
    }

    /** @param  list<Entrega>  $recojos */
    private function ajustes(array $recojos, Usuario $admin): void
    {
        foreach ($recojos as $recojo) {
            $suerte = mt_rand(1, 1000);
            if ($suerte <= 12) {
                app(AnularEntregaUseCase::class)->ejecutar($recojo->id, 'Recojo registrado dos veces en el celular', $admin->id);
            } elseif ($suerte <= 30) {
                app(CorregirEntregaUseCase::class)->ejecutar($recojo->id, round((float) $recojo->litros + mt_rand(-20, 20) / 10, 1), (int) $recojo->tachos,
                    $recojo->observaciones, 'El acopiador anotó mal la medida del tacho', $admin->id);
            }
        }
    }

    /** @param  list<Entrega>  $recojos */
    private function calidad(array $recojos, Usuario $calidad): void
    {
        foreach ($recojos as $recojo) {
            if (mt_rand(1, 100) > 35) {
                continue;
            }
            $acidez = mt_rand(140, 200) / 10;
            ControlCalidad::query()->create([
                'entrega_id' => $recojo->id, 'usuario_id' => $calidad->id,
                'resultado' => $acidez > 19.0 ? 'rechazado' : ($acidez > 17.5 ? 'observado' : 'aprobado'),
                'temperatura_c' => mt_rand(60, 140) / 10, 'acidez' => $acidez,
                'observaciones' => $acidez > 17.5 ? 'Acidez elevada; revisar enfriamiento en la ruta' : null,
                'evaluado_en' => $recojo->registrado_en->copy()->addHours(4),
            ]);
        }
    }

    /**
     * Reclamos de la semana en curso (pendientes) y uno de la semana pasada ya respondido.
     *
     * @param  list<array{zona: Zona, vehiculo: Vehiculo, acopiador: Usuario, calidad: Usuario, proveedores: list<Proveedor>}>  $zonas
     */
    private function reclamos(array $zonas, CarbonImmutable $hoy, Usuario $admin): void
    {
        $casos = [
            [0, 0, 1, 2.0, 'pendiente', 'El acopiador anotó menos litros de lo que entregué'],
            [1, 1, 2, 1.5, 'pendiente', 'Mi tacho estaba lleno y figura menos'],
            [2, 2, 0, 3.0, 'pendiente', 'No figura lo que entregué el martes completo'],
            [0, 2, 8, 2.5, 'rechazado', 'Creo que midieron mal mi leche'],
        ];
        foreach ($casos as [$zona, $proveedor, $diasAtras, $extra, $estado, $motivo]) {
            $cuenta = $zonas[$zona]['proveedores'][$proveedor];
            $recojo = Entrega::query()->where('proveedor_id', $cuenta->id)->where('anulada', false)
                ->where('registrado_en', '<=', $hoy->subDays($diasAtras)->endOfDay()->utc())->latest('registrado_en')->first();
            if ($recojo === null) {
                continue;
            }
            ReclamoProveedor::query()->create([
                'proveedor_id' => $cuenta->id, 'entrega_id' => $recojo->id, 'litros_originales' => $recojo->litros,
                'litros_solicitados' => (float) $recojo->litros + $extra, 'motivo' => $motivo, 'estado' => $estado,
                'respuesta' => $estado === 'rechazado' ? 'La medición en planta del día coincide con lo registrado en campo.' : null,
                'resuelto_por' => $estado === 'rechazado' ? $admin->id : null, 'resuelto_en' => $estado === 'rechazado' ? now() : null,
                'uuid_movil' => (string) Str::uuid(),
            ]);
        }
    }

    /**
     * Liquidación pagada de la semana anterior (lunes a domingo); la semana en curso se paga el viernes.
     *
     * @param  list<array{zona: Zona, vehiculo: Vehiculo, acopiador: Usuario, calidad: Usuario, proveedores: list<Proveedor>}>  $zonas
     */
    private function liquidacionesSemanaAnterior(array $zonas, CarbonImmutable $hoy): void
    {
        $inicio = $hoy->startOfWeek()->subWeek();
        $fin = $inicio->addDays(6);
        foreach ($zonas as $contexto) {
            foreach ($contexto['proveedores'] as $proveedor) {
                DB::transaction(function () use ($proveedor, $inicio, $fin): void {
                    $liquidacion = app(GenerarLiquidacionUseCase::class)->ejecutar($proveedor->id, $inicio->toDateTimeImmutable(), $fin->toDateTimeImmutable());
                    app(MarcarLiquidacionPagadaUseCase::class)->ejecutar($liquidacion->id);
                });
            }
        }
    }
}
