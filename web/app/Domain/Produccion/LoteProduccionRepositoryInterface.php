<?php

namespace App\Domain\Produccion;

use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;

interface LoteProduccionRepositoryInterface
{
    public function paginar(int $porPagina = 20, ?EstadoLoteProduccion $estado = null, ?int $productoId = null): LengthAwarePaginator;

    public function buscarPorId(int $id): ?LoteProduccion;

    public function buscarPorCodigo(string $codigo): ?LoteProduccion;

    /** @return list<LoteInsumo> */
    public function insumosDelLote(int $loteId): array;

    /** @param list<NecesidadInsumo> $necesidades */
    public function crear(LoteProduccion $lote, array $necesidades): LoteProduccion;

    /**
     * Reserva atómicamente los insumos necesarios (bloqueando sus filas) y pasa el lote a "en_proceso".
     * Lanza LoteProduccionInvalidoException si el lote ya no está en borrador o si falta disponibilidad.
     */
    public function iniciar(int $loteId): LoteProduccion;

    /**
     * Suma el consumo real informado para cada insumo (delta sobre lo ya consumido) y lo descuenta
     * de la existencia y la reserva del insumo. $consumos = [insumoId => cantidadTotalInformada].
     *
     * @param  array<int, float>  $consumos
     */
    public function registrarConsumo(int $loteId, array $consumos, int $usuarioId): LoteProduccion;

    /**
     * Confirma el consumo final (si se pasa), libera la reserva sobrante, ingresa la cantidad
     * obtenida al inventario de producto terminado y cierra el lote como finalizado.
     *
     * @param  array<int, float>|null  $consumosFinales
     */
    public function finalizar(int $loteId, float $cantidadObtenida, ?array $consumosFinales, int $usuarioId): LoteProduccion;

    public function cancelar(int $loteId, string $motivo, int $usuarioId): LoteProduccion;

    /** @return array{borradores: int, en_proceso: int, finalizados_periodo: int, cantidad_producida_periodo: float} */
    public function resumen(DateTimeImmutable $desde, DateTimeImmutable $hasta): array;
}
