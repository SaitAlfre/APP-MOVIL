<?php

namespace App\Application\Usuarios;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Usuario as UsuarioDominio;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class CambiarEstadoUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function ejecutar(int $id, bool $activo, int $actorId): UsuarioDominio
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        if (! $activo && $usuario->esAdmin() && $this->usuarios->contarAdminsDisponibles($id) === 0) {
            throw UsuarioInvalidoException::sinAdministradoresActivos();
        }

        $guardado = $this->usuarios->guardar($usuario->conEstado($activo));

        if (! $activo) {
            // Una cuenta desactivada pierde el acceso de inmediato, aunque tuviera sesión abierta.
            $this->usuarios->invalidarSesiones($id);
        }

        $this->auditorias->registrar(new Auditoria(
            id: null,
            entidad: 'usuario',
            entidadId: $id,
            accion: $activo ? AccionAuditoria::Actualizar : AccionAuditoria::Desactivar,
            valorAntes: 'activo='.($usuario->activo ? '1' : '0'),
            valorDespues: 'activo='.($activo ? '1' : '0'),
            motivo: null,
            usuarioId: $actorId,
            ocurridoEn: new DateTimeImmutable,
        ));

        return $guardado;
    }
}
