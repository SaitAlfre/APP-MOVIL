<?php

namespace App\Application\Inventario;

use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Inventario\EntradaInsumo;
use App\Domain\Inventario\EntradaInsumoRepositoryInterface;
use App\Domain\Inventario\InsumoRepositoryInterface;
use DateTimeImmutable;

/**
 * Convierte una entrega de leche aprobada u observada por Calidad en una entrada de
 * inventario del insumo "Leche". Se invoca desde RegistrarControlCalidadUseCase.
 *
 * La leche rechazada o sin evaluar nunca llega aquí, por lo que nunca queda disponible
 * para fabricar. La unicidad de entrega_id en entradas_insumo evita que una misma
 * entrega incremente la disponibilidad más de una vez.
 */
final class RegistrarEntradaDesdeEntregaUseCase
{
    public const NOMBRE_INSUMO_LECHE = 'Leche';

    public function __construct(
        private readonly EntradaInsumoRepositoryInterface $entradas,
        private readonly InsumoRepositoryInterface $insumos,
        private readonly EntregaRepositoryInterface $entregas,
    ) {}

    public function ejecutar(int $entregaId, int $usuarioId, DateTimeImmutable $fecha): ?EntradaInsumo
    {
        if ($this->entradas->buscarPorEntregaId($entregaId) !== null) {
            return null;
        }

        $entrega = $this->entregas->buscarPorId($entregaId);
        $lecheInsumo = $this->insumos->buscarPorNombre(self::NOMBRE_INSUMO_LECHE);

        if ($entrega === null || $lecheInsumo === null || $lecheInsumo->id === null) {
            return null;
        }

        $entrada = EntradaInsumo::crear(
            insumoId: $lecheInsumo->id,
            proveedorId: $entrega->proveedorId,
            entregaId: $entregaId,
            cantidad: $entrega->litros,
            unidad: $lecheInsumo->unidad,
            fecha: $fecha,
            costoUnitario: null,
            costoTotal: null,
            documentoReferencia: "Entrega #{$entregaId}",
            loteOrigen: null,
            vencimiento: null,
            observaciones: null,
            usuarioId: $usuarioId,
        );

        return $this->entradas->registrar($entrada);
    }
}
