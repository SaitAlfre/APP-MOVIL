<?php

namespace App\Application\Entregas;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use DateTimeImmutable;
use RuntimeException;

/** UPDATE entrega + INSERT auditoría en una sola transacción; el motivo nunca es opcional. */
final class CorregirEntregaUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
    ) {}

    public function ejecutar(int $entregaId, float $litros, int $tachos, ?string $observaciones, string $motivo, int $usuarioId): void
    {
        if (trim($motivo) === '') {
            throw EntregaInvalidaException::motivoObligatorio();
        }

        if ($litros <= 0.0) {
            throw EntregaInvalidaException::litrosNoPositivos();
        }

        if ($tachos <= 0) {
            throw EntregaInvalidaException::tachosInvalidos();
        }

        $entrega = $this->entregas->buscarPorId($entregaId);

        if ($entrega === null) {
            throw new RuntimeException('Entrega no encontrada.');
        }

        $ahora = new DateTimeImmutable;

        $auditoria = new Auditoria(
            id: null,
            entidad: 'entrega',
            entidadId: $entregaId,
            accion: AccionAuditoria::Corregir,
            valorAntes: "litros={$entrega->litros};tachos={$entrega->tachos}",
            valorDespues: "litros={$litros};tachos={$tachos}",
            motivo: $motivo,
            usuarioId: $usuarioId,
            ocurridoEn: $ahora,
        );

        $this->entregas->corregir($entregaId, $litros, $tachos, $observaciones, $ahora, $auditoria);
    }
}
