<?php

namespace App\Application\Reportes;

use App\Application\Produccion\ObtenerAcopioDelDiaUseCase;
use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Domain\Produccion\LoteProduccionRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Recepcion\RecepcionAcopioRepositoryInterface;
use DateTimeImmutable;

/**
 * Agrega los indicadores operativos del negocio (acopio, recepción, calidad, producción,
 * liquidaciones) para la página principal del administrador. Cada número se calcula sobre su
 * propia fuente (nunca se deriva sumando otro indicador ya agregado) para no duplicar cantidades
 * por relaciones entre tablas.
 */
final class ObtenerDashboardOperativoUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
        private readonly RecepcionAcopioRepositoryInterface $recepciones,
        private readonly LoteProduccionRepositoryInterface $lotes,
        private readonly LiquidacionRepositoryInterface $liquidaciones,
        private readonly ObtenerAcopioDelDiaUseCase $obtenerAcopioDelDia,
        private readonly JornadaRepositoryInterface $jornadas,
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly ControlCalidadRepositoryInterface $controlesCalidad,
    ) {}

    public function ejecutar(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null, string $agrupacion = 'dia'): array
    {
        $dias = $desde->diff($hasta)->days + 1;
        $hastaAnterior = $desde->modify('-1 day');
        $desdeAnterior = $hastaAnterior->modify('-'.($dias - 1).' days');

        $recolectado = $this->entregas->resumenParaReporte($desde, $hasta, $zonaId);
        $recolectadoAnterior = $this->entregas->resumenParaReporte($desdeAnterior, $hastaAnterior, $zonaId);

        $recepcion = $this->recepciones->resumenEnRango($desde, $hasta, $zonaId);
        $recepcionAnterior = $this->recepciones->resumenEnRango($desdeAnterior, $hastaAnterior, $zonaId);

        $pendienteCalidad = $this->entregas->pendientesCalidadParaReporte($desde, $hasta, $zonaId);

        // La leche habilitada/disponible se calcula día a día con el mismo cálculo que usa
        // Producción para reservar litros (neto de la merma de transporte), no solo lo aprobado
        // por Calidad: son dos cosas distintas y aquí interesa lo que de verdad se puede asignar.
        // Es un total global (no se puede filtrar por zona): la leche se acumula en una sola pila
        // una vez recepcionada.
        $habilitadoPorDia = [];
        for ($fecha = $desde; $fecha <= $hasta; $fecha = $fecha->modify('+1 day')) {
            $habilitadoPorDia[$fecha->format('Y-m-d')] = $this->obtenerAcopioDelDia->ejecutar($fecha)->litrosTotal();
        }
        $asignadoPorDia = collect($this->lotes->litrosAsignadosPorDiaEnRango($desde, $hasta))->keyBy('fecha');
        $habilitadoTotal = array_sum($habilitadoPorDia);
        $disponibleTotal = 0.0;
        foreach ($habilitadoPorDia as $fechaClave => $litros) {
            $disponibleTotal += $litros - (float) ($asignadoPorDia->get($fechaClave)['litros'] ?? 0.0);
        }

        $estadosLotes = $this->lotes->contarPorEstadoEnRango($desde, $hasta);
        $litrosConsumidosProduccion = $this->lotes->sumLitrosUsadosEnRango($desde, $hasta);

        $liquidacionesPendientes = $this->liquidaciones->contarPendientes();
        $recepcionesPendientes = count($this->recepciones->listarJornadas($desde, $hasta, 'pendiente'));

        $umbralMerma = (float) config('ecolecta.umbral_merma_porcentaje');
        $mermasPorVehiculo = $this->recepciones->mermasPorVehiculoEnRango($desde, $hasta, $zonaId);
        $vehiculosSobreUmbral = array_values(array_filter(
            $mermasPorVehiculo,
            fn (array $fila) => $fila['mermaPorcentaje'] > $umbralMerma,
        ));

        $tendenciaDiaria = $this->tendenciaConHuecos($desde, $hasta, $zonaId);

        // Bloque del centro operativo: fotografía del día de hoy y colas de trabajo
        // pendientes. Va aparte de los indicadores del periodo filtrado para que el
        // usuario distinga "lo que pasó en el rango" de "lo que tengo delante ahora".
        $hoy = new DateTimeImmutable('today');
        $ayer = $hoy->modify('-1 day');
        $recolectadoHoy = $this->entregas->resumenParaReporte($hoy, $hoy, $zonaId);
        $recolectadoAyer = $this->entregas->resumenParaReporte($ayer, $ayer, $zonaId);
        $calidadEnRango = $this->controlesCalidad->contarPorResultadoEnRango($desde, $hasta);
        $jornadasAbiertas = $this->jornadas->abiertas(5);

        return [
            'indicadores' => [
                'litros_recolectados' => $recolectado['litros'],
                'litros_recolectados_variacion' => $this->variacion($recolectado['litros'], $recolectadoAnterior['litros']),
                'litros_recibidos' => $recepcion['litrosMedidos'],
                'litros_recibidos_variacion' => $this->variacion($recepcion['litrosMedidos'], $recepcionAnterior['litrosMedidos']),
                'merma_litros' => $recepcion['merma'],
                'merma_porcentaje' => $recepcion['litrosRecolectados'] > 0.0
                    ? round(($recepcion['merma'] / $recepcion['litrosRecolectados']) * 100, 1)
                    : null,
                'pendiente_calidad_litros' => $pendienteCalidad['litros'],
                'pendiente_calidad_entregas' => $pendienteCalidad['entregas'],
                'habilitado_litros' => $habilitadoTotal,
                'disponible_litros' => max(0.0, round($disponibleTotal, 2)),
                'consumido_produccion_litros' => $litrosConsumidosProduccion,
                'lotes_en_proceso' => $estadosLotes['en_proceso'],
                'lotes_finalizados' => $estadosLotes['finalizado'],
                'liquidaciones_pendientes' => $liquidacionesPendientes,
            ],
            'alertas' => [
                'recepciones_pendientes' => $recepcionesPendientes,
                'calidad_pendientes' => $pendienteCalidad['entregas'],
                'mermas_sobre_umbral' => $vehiculosSobreUmbral,
                'umbral_merma_porcentaje' => $umbralMerma,
                'lotes_en_proceso' => $estadosLotes['en_proceso'],
                'liquidaciones_pendientes' => $liquidacionesPendientes,
            ],
            'panel' => [
                'litros_hoy' => $recolectadoHoy['litros'],
                'litros_hoy_variacion' => $this->variacion($recolectadoHoy['litros'], $recolectadoAyer['litros']),
                'proveedores_atendidos' => $recolectado['proveedores'],
                'proveedores_activos' => $this->proveedores->contarActivos(),
                'entregas_registradas' => $recolectado['entregas'],
                'jornadas_abiertas' => $jornadasAbiertas,
                'lotes_abiertos' => $estadosLotes['borrador'] + $estadosLotes['en_proceso'],
                'calidad' => $calidadEnRango,
                'calidad_alertas' => $calidadEnRango['observado'] + $calidadEnRango['rechazado'],
                'ultimas_entregas' => $this->entregas->entregasParaReporte($desde, $hasta, $zonaId, 5),
                'entregas_sin_calidad' => $this->entregas->sinControlCalidad(5),
            ],
            'graficos' => [
                'tendencia' => $this->agregarTendencia($tendenciaDiaria, $agrupacion),
                'agrupacion' => $agrupacion,
                'zonas' => $this->entregas->zonasParaReporte($desde, $hasta, $zonaId),
                'acopiadores' => $this->entregas->acopiadoresParaReporte($desde, $hasta, $zonaId),
                'mermas_por_vehiculo' => $mermasPorVehiculo,
            ],
        ];
    }

    /** @return list<array{fecha: string, litros: float, entregas: int}> */
    private function tendenciaConHuecos(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId): array
    {
        $porFecha = collect($this->entregas->tendenciaParaReporte($desde, $hasta, $zonaId))->keyBy('fecha');

        $tendencia = [];
        for ($fecha = $desde; $fecha <= $hasta; $fecha = $fecha->modify('+1 day')) {
            $clave = $fecha->format('Y-m-d');
            $fila = $porFecha->get($clave, ['litros' => 0.0, 'entregas' => 0]);
            $tendencia[] = [
                'fecha' => $clave,
                'litros' => (float) $fila['litros'],
                'entregas' => (int) $fila['entregas'],
            ];
        }

        return $tendencia;
    }

    /**
     * Reagrupa en PHP (no con funciones de fecha del motor de base de datos) para que día,
     * semana y mes funcionen igual en MySQL y en el sqlite de las pruebas.
     *
     * @param  list<array{fecha: string, litros: float, entregas: int}>  $diario
     * @return list<array{fecha: string, litros: float, entregas: int}>
     */
    private function agregarTendencia(array $diario, string $agrupacion): array
    {
        if ($agrupacion === 'dia') {
            return $diario;
        }

        $grupos = [];
        foreach ($diario as $dia) {
            $fecha = new DateTimeImmutable($dia['fecha']);
            $clave = $agrupacion === 'semana' ? $fecha->format('o-\SW') : $fecha->format('Y-m');

            $grupos[$clave]['fecha'] ??= $clave;
            $grupos[$clave]['litros'] = ($grupos[$clave]['litros'] ?? 0.0) + $dia['litros'];
            $grupos[$clave]['entregas'] = ($grupos[$clave]['entregas'] ?? 0) + $dia['entregas'];
        }

        return array_values($grupos);
    }

    private function variacion(float $actual, float $anterior): ?float
    {
        return $anterior > 0.0 ? round((($actual - $anterior) / $anterior) * 100, 1) : null;
    }
}
