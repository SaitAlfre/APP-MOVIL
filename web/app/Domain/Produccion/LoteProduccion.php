<?php

namespace App\Domain\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use DateTimeImmutable;

final class LoteProduccion
{
    /** @param array<int, array<string, mixed>> $origenAcopio */
    private function __construct(
        public readonly ?int $id,
        public readonly ?string $codigo,
        public readonly int $productoId,
        public readonly DateTimeImmutable $fecha,
        public readonly float $litrosPorUnidadSnapshot,
        public readonly float $litrosAsignados,
        public readonly ?float $litrosUsados,
        public readonly ?float $litrosMermaProceso,
        public readonly ?float $litrosSobrantes,
        public readonly int $unidadesEstimadas,
        public readonly ?int $unidadesProducidas,
        public readonly EstadoLoteProduccion $estado,
        public readonly array $origenAcopio,
        public readonly int $responsableId,
        public readonly ?DateTimeImmutable $iniciadoEn,
        public readonly ?DateTimeImmutable $finalizadoEn,
        public readonly ?DateTimeImmutable $canceladoEn,
        public readonly ?string $motivoCancelacion,
    ) {}

    /** @param array<int, array<string, mixed>> $origenAcopio Desglose informativo del acopio del día (leche fungible: no se reparte físicamente por lote). */
    public static function crear(
        int $productoId,
        DateTimeImmutable $fecha,
        float $litrosPorUnidadSnapshot,
        float $litrosAsignados,
        array $origenAcopio,
        int $responsableId,
    ): self {
        self::validarAsignacion($litrosAsignados);

        return new self(
            id: null,
            codigo: null,
            productoId: $productoId,
            fecha: $fecha,
            litrosPorUnidadSnapshot: $litrosPorUnidadSnapshot,
            litrosAsignados: $litrosAsignados,
            litrosUsados: null,
            litrosMermaProceso: null,
            litrosSobrantes: null,
            unidadesEstimadas: (int) floor($litrosAsignados / $litrosPorUnidadSnapshot),
            unidadesProducidas: null,
            estado: EstadoLoteProduccion::Borrador,
            origenAcopio: $origenAcopio,
            responsableId: $responsableId,
            iniciadoEn: null,
            finalizadoEn: null,
            canceladoEn: null,
            motivoCancelacion: null,
        );
    }

    /** @param array<int, array<string, mixed>> $origenAcopio */
    public static function reconstruir(
        int $id,
        string $codigo,
        int $productoId,
        DateTimeImmutable $fecha,
        float $litrosPorUnidadSnapshot,
        float $litrosAsignados,
        ?float $litrosUsados,
        ?float $litrosMermaProceso,
        ?float $litrosSobrantes,
        int $unidadesEstimadas,
        ?int $unidadesProducidas,
        EstadoLoteProduccion $estado,
        array $origenAcopio,
        int $responsableId,
        ?DateTimeImmutable $iniciadoEn,
        ?DateTimeImmutable $finalizadoEn,
        ?DateTimeImmutable $canceladoEn,
        ?string $motivoCancelacion,
    ): self {
        return new self($id, $codigo, $productoId, $fecha, $litrosPorUnidadSnapshot, $litrosAsignados, $litrosUsados, $litrosMermaProceso, $litrosSobrantes, $unidadesEstimadas, $unidadesProducidas, $estado, $origenAcopio, $responsableId, $iniciadoEn, $finalizadoEn, $canceladoEn, $motivoCancelacion);
    }

    public function puedeIniciar(): bool
    {
        return $this->estado === EstadoLoteProduccion::Borrador;
    }

    public function puedeFinalizar(): bool
    {
        return $this->estado === EstadoLoteProduccion::EnProceso;
    }

    public function puedeCancelar(): bool
    {
        return in_array($this->estado, [EstadoLoteProduccion::Borrador, EstadoLoteProduccion::EnProceso], true);
    }

    public static function validarAsignacion(float $litrosAsignados): void
    {
        if ($litrosAsignados <= 0.0) {
            throw LoteProduccionInvalidoException::litrosAsignadosInvalidos();
        }
    }

    public static function validarFinalizacion(float $litrosAsignados, float $litrosUsados, float $litrosMermaProceso): void
    {
        if ($litrosUsados < 0.0 || $litrosMermaProceso < 0.0) {
            throw LoteProduccionInvalidoException::litrosUsadosInvalidos();
        }

        if (round($litrosUsados + $litrosMermaProceso, 3) > round($litrosAsignados, 3)) {
            throw LoteProduccionInvalidoException::consumoSuperaAsignado($litrosAsignados);
        }
    }
}
