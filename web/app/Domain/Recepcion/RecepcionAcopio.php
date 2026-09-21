<?php

namespace App\Domain\Recepcion;

use App\Domain\Recepcion\Exceptions\RecepcionInvalidaException;
use DateTimeImmutable;

final class RecepcionAcopio
{
    private function __construct(
        public readonly ?int $id,
        public readonly int $jornadaId,
        public readonly DateTimeImmutable $llegadaEn,
        public readonly float $litrosRecolectados,
        public readonly float $litrosMedidos,
        public readonly ?string $motivoDiferencia,
        public readonly ?string $observaciones,
        public readonly int $usuarioId,
    ) {}

    public static function crear(
        int $jornadaId,
        DateTimeImmutable $llegadaEn,
        float $litrosRecolectados,
        float $litrosMedidos,
        ?string $motivoDiferencia,
        ?string $observaciones,
        int $usuarioId,
    ): self {
        self::validar($litrosRecolectados, $litrosMedidos, $motivoDiferencia);

        return new self(
            id: null,
            jornadaId: $jornadaId,
            llegadaEn: $llegadaEn,
            litrosRecolectados: $litrosRecolectados,
            litrosMedidos: $litrosMedidos,
            motivoDiferencia: $motivoDiferencia !== null && trim($motivoDiferencia) !== '' ? trim($motivoDiferencia) : null,
            observaciones: $observaciones !== null && trim($observaciones) !== '' ? trim($observaciones) : null,
            usuarioId: $usuarioId,
        );
    }

    public static function reconstruir(
        int $id,
        int $jornadaId,
        DateTimeImmutable $llegadaEn,
        float $litrosRecolectados,
        float $litrosMedidos,
        ?string $motivoDiferencia,
        ?string $observaciones,
        int $usuarioId,
    ): self {
        return new self($id, $jornadaId, $llegadaEn, $litrosRecolectados, $litrosMedidos, $motivoDiferencia, $observaciones, $usuarioId);
    }

    public function diferencia(): float
    {
        return round($this->litrosMedidos - $this->litrosRecolectados, 2);
    }

    public function esMerma(): bool
    {
        return $this->diferencia() < 0.0;
    }

    public function esExcedente(): bool
    {
        return $this->diferencia() > 0.0;
    }

    private static function validar(float $litrosRecolectados, float $litrosMedidos, ?string $motivoDiferencia): void
    {
        if ($litrosRecolectados <= 0.0) {
            throw RecepcionInvalidaException::litrosRecolectadosInvalidos();
        }

        if ($litrosMedidos < 0.0) {
            throw RecepcionInvalidaException::litrosMedidosNegativos();
        }

        if (round($litrosMedidos - $litrosRecolectados, 2) !== 0.0 && ($motivoDiferencia === null || trim($motivoDiferencia) === '')) {
            throw RecepcionInvalidaException::motivoDiferenciaObligatorio();
        }
    }
}
