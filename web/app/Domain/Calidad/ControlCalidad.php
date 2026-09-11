<?php

namespace App\Domain\Calidad;

use App\Domain\Calidad\Exceptions\ControlCalidadInvalidoException;
use DateTimeImmutable;

final class ControlCalidad
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $entregaId,
        public readonly int $usuarioId,
        public readonly EstadoCalidad $resultado,
        public readonly ?float $temperaturaC,
        public readonly ?float $acidez,
        public readonly ?string $observaciones,
        public readonly DateTimeImmutable $evaluadoEn,
    ) {}

    public static function crear(
        int $entregaId,
        int $usuarioId,
        EstadoCalidad $resultado,
        ?float $temperaturaC,
        ?float $acidez,
        ?string $observaciones,
        DateTimeImmutable $evaluadoEn,
    ): self {
        self::validar($temperaturaC, $acidez);

        return new self(
            id: null,
            entregaId: $entregaId,
            usuarioId: $usuarioId,
            resultado: $resultado,
            temperaturaC: $temperaturaC,
            acidez: $acidez,
            observaciones: $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null,
            evaluadoEn: $evaluadoEn,
        );
    }

    public static function reconstruir(
        int $id,
        int $entregaId,
        int $usuarioId,
        EstadoCalidad $resultado,
        ?float $temperaturaC,
        ?float $acidez,
        ?string $observaciones,
        DateTimeImmutable $evaluadoEn,
    ): self {
        return new self($id, $entregaId, $usuarioId, $resultado, $temperaturaC, $acidez, $observaciones, $evaluadoEn);
    }

    /**
     * Sugerencia informativa basada en los rangos estándar de control lechero
     * (cadena de frío ≤ 4°C, acidez normal 14-18°D). No es vinculante: el
     * evaluador siempre puede registrar el resultado que considere correcto.
     */
    public function sugerenciaPorValores(): ?EstadoCalidad
    {
        return self::sugerirPorValores($this->temperaturaC, $this->acidez);
    }

    public static function sugerirPorValores(?float $temperaturaC, ?float $acidez): ?EstadoCalidad
    {
        if ($temperaturaC === null && $acidez === null) {
            return null;
        }

        if (($temperaturaC !== null && $temperaturaC > 8) || ($acidez !== null && ($acidez < 12 || $acidez > 20))) {
            return EstadoCalidad::Rechazado;
        }

        if (($temperaturaC !== null && $temperaturaC > 4) || ($acidez !== null && ($acidez < 14 || $acidez > 18))) {
            return EstadoCalidad::Observado;
        }

        return EstadoCalidad::Aprobado;
    }

    private static function validar(?float $temperaturaC, ?float $acidez): void
    {
        if ($temperaturaC !== null && ($temperaturaC < -5 || $temperaturaC > 60)) {
            throw ControlCalidadInvalidoException::temperaturaFueraDeRango();
        }

        if ($acidez !== null && ($acidez < 0 || $acidez > 50)) {
            throw ControlCalidadInvalidoException::acidezFueraDeRango();
        }
    }
}
