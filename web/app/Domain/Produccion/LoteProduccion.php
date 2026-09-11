<?php

namespace App\Domain\Produccion;

use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use DateTimeImmutable;

final class LoteProduccion
{
    private function __construct(
        public readonly ?int $id,
        public readonly string $codigo,
        public readonly string $producto,
        public readonly float $litrosUtilizados,
        public readonly EstadoLoteProduccion $estado,
        public readonly int $responsableUsuarioId,
        public readonly DateTimeImmutable $abiertoEn,
        public readonly ?DateTimeImmutable $cerradoEn,
    ) {}

    public static function abrir(string $codigo, string $producto, float $litrosUtilizados, int $responsableUsuarioId, DateTimeImmutable $abiertoEn): self
    {
        if (trim($codigo) === '') {
            throw LoteProduccionInvalidoException::codigoVacio();
        }

        if (trim($producto) === '') {
            throw LoteProduccionInvalidoException::productoVacio();
        }

        if ($litrosUtilizados <= 0.0) {
            throw LoteProduccionInvalidoException::litrosInvalidos();
        }

        return new self(
            id: null,
            codigo: trim($codigo),
            producto: trim($producto),
            litrosUtilizados: $litrosUtilizados,
            estado: EstadoLoteProduccion::Abierto,
            responsableUsuarioId: $responsableUsuarioId,
            abiertoEn: $abiertoEn,
            cerradoEn: null,
        );
    }

    public static function reconstruir(
        int $id,
        string $codigo,
        string $producto,
        float $litrosUtilizados,
        EstadoLoteProduccion $estado,
        int $responsableUsuarioId,
        DateTimeImmutable $abiertoEn,
        ?DateTimeImmutable $cerradoEn,
    ): self {
        return new self($id, $codigo, $producto, $litrosUtilizados, $estado, $responsableUsuarioId, $abiertoEn, $cerradoEn);
    }

    public function cerrar(DateTimeImmutable $cerradoEn): self
    {
        if ($this->estado === EstadoLoteProduccion::Cerrado) {
            throw LoteProduccionInvalidoException::yaCerrado();
        }

        return new self($this->id, $this->codigo, $this->producto, $this->litrosUtilizados, EstadoLoteProduccion::Cerrado, $this->responsableUsuarioId, $this->abiertoEn, $cerradoEn);
    }
}
