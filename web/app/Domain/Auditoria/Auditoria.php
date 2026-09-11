<?php

namespace App\Domain\Auditoria;

use DateTimeImmutable;

final class Auditoria
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $entidad,
        public readonly int $entidadId,
        public readonly AccionAuditoria $accion,
        public readonly ?string $valorAntes,
        public readonly ?string $valorDespues,
        public readonly ?string $motivo,
        public readonly int $usuarioId,
        public readonly DateTimeImmutable $ocurridoEn,
    ) {}
}
