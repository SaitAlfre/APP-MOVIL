<?php

namespace App\Application\Inventario;

use App\Domain\Inventario\EntradaInsumo;
use App\Domain\Inventario\EntradaInsumoRepositoryInterface;
use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\InsumoRepositoryInterface;
use DateTimeImmutable;

final class RegistrarEntradaInsumoUseCase
{
    public function __construct(
        private readonly EntradaInsumoRepositoryInterface $entradas,
        private readonly InsumoRepositoryInterface $insumos,
    ) {}

    public function ejecutar(
        int $insumoId,
        ?int $proveedorId,
        float $cantidad,
        string $unidad,
        DateTimeImmutable $fecha,
        ?float $costoUnitario,
        ?float $costoTotal,
        ?string $documentoReferencia,
        ?string $loteOrigen,
        ?DateTimeImmutable $vencimiento,
        ?string $observaciones,
        int $usuarioId,
    ): EntradaInsumo {
        $insumo = $this->insumos->buscarPorId($insumoId);

        if ($insumo === null) {
            throw InsumoInvalidoException::noExiste();
        }

        $entrada = EntradaInsumo::crear(
            insumoId: $insumoId,
            proveedorId: $proveedorId,
            entregaId: null,
            cantidad: $cantidad,
            unidad: $unidad,
            fecha: $fecha,
            costoUnitario: $costoUnitario,
            costoTotal: $costoTotal,
            documentoReferencia: $documentoReferencia,
            loteOrigen: $loteOrigen,
            vencimiento: $vencimiento,
            observaciones: $observaciones,
            usuarioId: $usuarioId,
        );

        return $this->entradas->registrar($entrada);
    }
}
