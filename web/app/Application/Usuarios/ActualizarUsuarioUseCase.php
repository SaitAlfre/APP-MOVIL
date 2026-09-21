<?php

namespace App\Application\Usuarios;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario as UsuarioDominio;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class ActualizarUsuarioUseCase
{
    use ValidaDatosUsuario;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    /** @param list<Rol> $roles */
    public function ejecutar(int $id, string $nombres, string $dni, array $roles, bool $actorEsAdmin, int $actorId): UsuarioDominio
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $perdioAdmin = $usuario->esAdmin() && ! in_array(Rol::Admin, $roles, true);
        $ganoAdmin = ! $usuario->esAdmin() && in_array(Rol::Admin, $roles, true);

        if ($perdioAdmin || $ganoAdmin) {
            if (! $actorEsAdmin) {
                throw UsuarioInvalidoException::noPuedeAsignarRolQueNoTiene();
            }
        }

        if ($perdioAdmin && $this->usuarios->contarAdminsDisponibles($id) === 0) {
            throw UsuarioInvalidoException::sinAdministradoresActivos();
        }

        $rolesAnteriores = array_map(fn (Rol $r) => $r->value, $usuario->roles);
        $rolesNuevos = array_map(fn (Rol $r) => $r->value, $roles);
        $cambiaronRoles = $rolesAnteriores !== $rolesNuevos;

        $actualizado = $usuario->conDatosDePerfil($nombres, $dni)->conRoles($roles);
        $guardado = $this->usuarios->guardar($actualizado);

        if ($cambiaronRoles) {
            // Los permisos cambiaron: se fuerza a iniciar sesión de nuevo para que se apliquen.
            $this->usuarios->invalidarSesiones($id);
        }

        if ($cambiaronRoles || $usuario->nombres !== $nombres || $usuario->dni !== $dni) {
            $this->auditorias->registrar(new Auditoria(
                id: null,
                entidad: 'usuario',
                entidadId: $id,
                accion: AccionAuditoria::Actualizar,
                valorAntes: json_encode(['nombres' => $usuario->nombres, 'roles' => $rolesAnteriores]),
                valorDespues: json_encode(['nombres' => $guardado->nombres, 'roles' => $rolesNuevos, 'dni_modificado' => $usuario->dni !== $dni]),
                motivo: null,
                usuarioId: $actorId,
                ocurridoEn: new DateTimeImmutable,
            ));
        }

        return $guardado;
    }
}
