<?php

namespace App\Application\Reportes;

use App\Domain\Entregas\EntregaRepositoryInterface;
use DateTimeImmutable;

final class ObtenerResumenGeneralUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
    ) {}

    /**
     * @return array{
     *     resumen: array{litros: float, entregas: int, promedio_litros: float, proveedores: int, tachos: int},
     *     variacion_litros: float|null,
     *     tendencia: list<array{fecha: string, litros: float, entregas: int}>,
     *     zonas: list<array{zona: string, litros: float, entregas: int}>,
     *     entregas: list<array{fecha: string, proveedor_codigo: string, proveedor: string, zona: string, vehiculo: string, placa: string, litros: float, tachos: int}>
     * }
     */
    public function ejecutar(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null, ?int $limite = 200): array
    {
        $resumen = $this->entregas->resumenParaReporte($desde, $hasta, $zonaId);
        $dias = $desde->diff($hasta)->days + 1;
        $hastaAnterior = $desde->modify('-1 day');
        $desdeAnterior = $hastaAnterior->modify('-'.($dias - 1).' days');
        $resumenAnterior = $this->entregas->resumenParaReporte($desdeAnterior, $hastaAnterior, $zonaId);
        $tendenciaPorFecha = collect($this->entregas->tendenciaParaReporte($desde, $hasta, $zonaId))->keyBy('fecha');

        $tendencia = [];
        for ($fecha = $desde; $fecha <= $hasta; $fecha = $fecha->modify('+1 day')) {
            $clave = $fecha->format('Y-m-d');
            $fila = $tendenciaPorFecha->get($clave, ['litros' => 0.0, 'entregas' => 0]);
            $tendencia[] = [
                'fecha' => $clave,
                'litros' => (float) $fila['litros'],
                'entregas' => (int) $fila['entregas'],
            ];
        }

        $variacionLitros = $resumenAnterior['litros'] > 0
            ? (($resumen['litros'] - $resumenAnterior['litros']) / $resumenAnterior['litros']) * 100
            : null;

        return [
            'resumen' => $resumen,
            'variacion_litros' => $variacionLitros,
            'tendencia' => $tendencia,
            'zonas' => $this->entregas->zonasParaReporte($desde, $hasta, $zonaId),
            'entregas' => $this->entregas->entregasParaReporte($desde, $hasta, $zonaId, $limite),
        ];
    }
}
