<?php

namespace App\Application\Usuarios;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class RestablecerCredencialesUsuarioUseCase
{
    use ValidaDatosUsuario;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    /** @param  string|null  $exceptoSessionId  Conserva la sesión actual si el propio actor se restablece el PIN a sí mismo. */
    public function ejecutar(int $id, string $nuevoPinPlano, int $actorId, ?string $exceptoSessionId = null): void
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $this->validarPin($nuevoPinPlano);

        $this->usuarios->restablecerCredenciales($id, $nuevoPinPlano);
        $this->usuarios->invalidarSesiones($id, $exceptoSessionId);

        $this->auditorias->registrar(new Auditoria(
            id: null,
            entidad: 'usuario',
            entidadId: $id,
            accion: AccionAuditoria::Corregir,
            // Nunca se guarda el PIN ni su hash en la auditoría, solo que ocurrió el restablecimiento.
            valorAntes: null,
            valorDespues: 'credenciales restablecidas',
            motivo: null,
            usuarioId: $actorId,
            ocurridoEn: new DateTimeImmutable,
        ));
    }
}
