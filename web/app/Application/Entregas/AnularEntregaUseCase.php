<?php

namespace App\Application\Entregas;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use DateTimeImmutable;
use RuntimeException;

/** anulada=true, nunca DELETE físico; UPDATE + INSERT auditoría en una sola transacción. */
final class AnularEntregaUseCase
{
    public function __construct(
        private readonly EntregaRepositoryInterface $entregas,
    ) {}

    public function ejecutar(int $entregaId, string $motivo, int $usuarioId): void
    {
        if (trim($motivo) === '') {
            throw EntregaInvalidaException::motivoObligatorio();
        }

        if ($this->entregas->buscarPorId($entregaId) === null) {
            throw new RuntimeException('Entrega no encontrada.');
        }

        $ahora = new DateTimeImmutable;

        $auditoria = new Auditoria(
            id: null,
            entidad: 'entrega',
            entidadId: $entregaId,
            accion: AccionAuditoria::Anular,
            valorAntes: null,
            valorDespues: null,
            motivo: $motivo,
            usuarioId: $usuarioId,
            ocurridoEn: $ahora,
        );

        $this->entregas->anular($entregaId, $ahora, $auditoria);
    }
}
