<?php

namespace App\Application\Usuarios;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Usuarios\Usuario as UsuarioDominio;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class DesbloquearUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function ejecutar(int $id, int $actorId): UsuarioDominio
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        // Desbloquear levanta tanto el bloqueo manual como el automático por intentos fallidos:
        // desde la perspectiva del administrador, "desbloquear" restaura el acceso por completo.
        $guardado = $this->usuarios->guardar($usuario->sinBloqueoManual()->sinIntentosFallidos());

        $this->auditorias->registrar(new Auditoria(
            id: null,
            entidad: 'usuario',
            entidadId: $id,
            accion: AccionAuditoria::Autorizar,
            valorAntes: 'bloqueado_manualmente=1',
            valorDespues: 'bloqueado_manualmente=0',
            motivo: null,
            usuarioId: $actorId,
            ocurridoEn: new DateTimeImmutable,
        ));

        return $guardado;
    }
}
