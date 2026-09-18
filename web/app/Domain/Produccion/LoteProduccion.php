<?php

namespace App\Domain\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use DateTimeImmutable;

final class LoteProduccion
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $codigo,
        public readonly int $productoId,
        public readonly int $recetaId,
        public readonly float $cantidadPlanificada,
        public readonly ?float $cantidadObtenida,
        public readonly string $unidad,
        public readonly EstadoLoteProduccion $estado,
        public readonly int $responsableUsuarioId,
        public readonly DateTimeImmutable $fechaPlanificada,
        public readonly ?string $observaciones,
        public readonly DateTimeImmutable $creadoEn,
        public readonly ?DateTimeImmutable $iniciadoEn,
        public readonly ?DateTimeImmutable $finalizadoEn,
        public readonly ?DateTimeImmutable $canceladoEn,
        public readonly ?string $motivoCancelacion,
    ) {}

    public static function crear(
        string $codigo,
        int $productoId,
        int $recetaId,
        float $cantidadPlanificada,
        string $unidad,
        int $responsableUsuarioId,
        DateTimeImmutable $fechaPlanificada,
        ?string $observaciones,
    ): self {
        if (trim($codigo) === '') {
            throw LoteProduccionInvalidoException::codigoVacio();
        }

        if ($cantidadPlanificada <= 0.0) {
            throw LoteProduccionInvalidoException::cantidadInvalida();
        }

        return new self(
            id: null,
            codigo: trim($codigo),
            productoId: $productoId,
            recetaId: $recetaId,
            cantidadPlanificada: $cantidadPlanificada,
            cantidadObtenida: null,
            unidad: $unidad,
            estado: EstadoLoteProduccion::Borrador,
            responsableUsuarioId: $responsableUsuarioId,
            fechaPlanificada: $fechaPlanificada,
            observaciones: $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null,
            creadoEn: new DateTimeImmutable,
            iniciadoEn: null,
            finalizadoEn: null,
            canceladoEn: null,
            motivoCancelacion: null,
        );
    }

    public static function reconstruir(
        int $id,
        string $codigo,
        int $productoId,
        int $recetaId,
        float $cantidadPlanificada,
        ?float $cantidadObtenida,
        string $unidad,
        EstadoLoteProduccion $estado,
        int $responsableUsuarioId,
        DateTimeImmutable $fechaPlanificada,
        ?string $observaciones,
        DateTimeImmutable $creadoEn,
        ?DateTimeImmutable $iniciadoEn,
        ?DateTimeImmutable $finalizadoEn,
        ?DateTimeImmutable $canceladoEn,
        ?string $motivoCancelacion,
    ): self {
        return new self($id, $codigo, $productoId, $recetaId, $cantidadPlanificada, $cantidadObtenida, $unidad, $estado, $responsableUsuarioId, $fechaPlanificada, $observaciones, $creadoEn, $iniciadoEn, $finalizadoEn, $canceladoEn, $motivoCancelacion);
    }

    public function puedeIniciar(): bool
    {
        return $this->estado === EstadoLoteProduccion::Borrador;
    }

    public function puedeRegistrarConsumo(): bool
    {
        return $this->estado === EstadoLoteProduccion::EnProceso;
    }

    public function puedeFinalizar(): bool
    {
        return $this->estado === EstadoLoteProduccion::EnProceso;
    }

    public function puedeCancelar(): bool
    {
        return in_array($this->estado, [EstadoLoteProduccion::Borrador, EstadoLoteProduccion::EnProceso], true);
    }
}
