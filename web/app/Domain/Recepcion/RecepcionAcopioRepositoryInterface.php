<?php

namespace App\Domain\Recepcion;

use App\Domain\Auditoria\Auditoria;
use DateTimeImmutable;

interface RecepcionAcopioRepositoryInterface
{
    public function porJornada(int $jornadaId): ?RecepcionAcopio;

    /**
     * Recepciones ya registradas de un conjunto de jornadas, indexadas por jornada.
     *
     * @param  list<int>  $jornadaIds
     * @return array<int, RecepcionAcopio>
     */
    public function porJornadas(array $jornadaIds): array;

    /**
     * Jornadas con entregas en el rango, para la pantalla de Recepción de leche.
     *
     * @return list<array{
     *     jornadaId: int,
     *     fecha: DateTimeImmutable,
     *     acopiador: string,
     *     vehiculoNombre: string,
     *     placa: string,
     *     zona: string,
     *     litrosRecolectados: float,
     *     recepcion: ?RecepcionAcopio,
     *     calidad: array{pendientes: int, aprobadas: int, observadas: int, rechazadas: int},
     * }>
     */
    public function listarJornadas(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?string $estado = null): array;

    public function registrarLlegada(RecepcionAcopio $recepcion, Auditoria $auditoria): RecepcionAcopio;

    /** @return array{litrosRecolectados: float, litrosMedidos: float, merma: float} Suma de las recepciones ya registradas en el rango (jornadas sin recepción no cuentan aquí). */
    public function resumenEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array;

    /** @return list<array{vehiculo: string, placa: string, litrosRecolectados: float, merma: float, mermaPorcentaje: float}> */
    public function mermasPorVehiculoEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta, ?int $zonaId = null): array;
}
