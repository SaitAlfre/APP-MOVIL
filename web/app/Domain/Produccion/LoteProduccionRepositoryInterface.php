<?php

namespace App\Domain\Produccion;

use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

interface LoteProduccionRepositoryInterface
{
    public function buscarPorId(int $id): ?LoteProduccion;

    /** @return LengthAwarePaginator<int, LoteProduccion> */
    public function paginar(int $porPagina = 20): LengthAwarePaginator;

    /** Suma de litros_asignados de lotes no cancelados para la fecha (lo ya reservado del saldo del día). */
    public function litrosAsignadosEnFecha(DateTimeImmutable $fecha): float;

    public function existeAsignacionEnFecha(DateTimeImmutable $fecha): bool;

    /** Valida el saldo disponible bajo bloqueo y crea el lote en una sola transacción; lanza LoteProduccionInvalidoException si supera el saldo. */
    public function crear(LoteProduccion $lote): LoteProduccion;

    public function iniciar(int $id, DateTimeImmutable $ahora, int $usuarioId): LoteProduccion;

    public function finalizar(int $id, float $litrosUsados, float $litrosMermaProceso, DateTimeImmutable $ahora, int $usuarioId): LoteProduccion;

    public function cancelar(int $id, string $motivo, DateTimeImmutable $ahora, int $usuarioId): LoteProduccion;

    public function sumLitrosUsados(): float;

    public function sumUnidadesProducidas(): int;

    /** @return array{borrador: int, en_proceso: int, finalizado: int, cancelado: int} */
    public function contarPorEstadoEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array;

    public function sumLitrosUsadosEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): float;

    /** @return list<array{fecha: string, litros: float}> Litros asignados (no cancelados) por día, para calcular el saldo disponible de un rango. */
    public function litrosAsignadosPorDiaEnRango(DateTimeImmutable $desde, DateTimeImmutable $hasta): array;
}
