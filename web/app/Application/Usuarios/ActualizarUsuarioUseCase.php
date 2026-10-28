<?php

namespace App\Application\Usuarios;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario as UsuarioDominio;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use DateTimeImmutable;
use RuntimeException;

final class ActualizarUsuarioUseCase
{
    use ValidaDatosUsuario;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
        private readonly ZonaRepositoryInterface $zonas,
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    /** @param list<Rol> $roles */
    public function ejecutar(int $id, string $nombres, string $dni, array $roles, bool $actorEsAdmin, int $actorId, ?int $zonaId = null, bool $actualizarZona = false): UsuarioDominio
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $dni = trim($dni);
        $this->validarIdentidad($usuario->username, $dni, $usuario);
        $this->validarRolesAsignables($roles, $usuario->roles);
        $this->validarFichaVinculada($usuario, $dni, $roles);
        $rolesSinCambio = collect($roles)->map(fn (Rol $rol) => $rol->value)->sort()->values()->all()
            === collect($usuario->roles)->map(fn (Rol $rol) => $rol->value)->sort()->values()->all();
        $zonaEfectiva = $actualizarZona ? $zonaId : $usuario->zonaId;
        if (! $rolesSinCambio || $zonaEfectiva !== $usuario->zonaId) {
            $this->validarZonaAsignada($zonaEfectiva, $roles);
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
        if ($actualizarZona) {
            $actualizado = $actualizado->conZonaAsignada($zonaId);
        }
        $guardado = $this->usuarios->guardar($actualizado);

        $cambioZona = $usuario->zonaId !== $guardado->zonaId;
        if ($cambiaronRoles || $cambioZona) {
            // Los permisos cambiaron: se fuerza a iniciar sesión de nuevo para que se apliquen.
            $this->usuarios->invalidarSesiones($id);
        }

        if ($cambiaronRoles || $cambioZona || $usuario->nombres !== $nombres || $usuario->dni !== $dni) {
            $this->auditorias->registrar(new Auditoria(
                id: null,
                entidad: 'usuario',
                entidadId: $id,
                accion: AccionAuditoria::Actualizar,
                valorAntes: json_encode(['nombres' => $usuario->nombres, 'roles' => $rolesAnteriores, 'zona_id' => $usuario->zonaId]),
                valorDespues: json_encode(['nombres' => $guardado->nombres, 'roles' => $rolesNuevos, 'zona_id' => $guardado->zonaId, 'dni_modificado' => $usuario->dni !== $dni]),
                motivo: null,
                usuarioId: $actorId,
                ocurridoEn: new DateTimeImmutable,
            ));
        }

        return $guardado;
    }

    /**
     * Una cuenta vinculada a una ficha productiva comparte su identidad: no se cambia su DNI ni se le quita
     * el rol Proveedor sin antes desvincularla, para no dejar relaciones inconsistentes.
     *
     * @param  list<Rol>  $roles
     */
    private function validarFichaVinculada(UsuarioDominio $usuario, string $dni, array $roles): void
    {
        if ($usuario->id === null || $this->proveedores->buscarPorUsuarioId($usuario->id) === null) {
            return;
        }
        if ($dni !== trim($usuario->dni)) {
            throw new UsuarioInvalidoException('Esta cuenta está vinculada a una ficha de proveedor. Para cambiar el DNI, desvincúlala desde la ficha o corrige ambos registros.');
        }
        if (! in_array(Rol::Proveedor, $roles, true)) {
            throw new UsuarioInvalidoException('Esta cuenta está vinculada a una ficha de proveedor. Desvincúlala desde la ficha antes de quitarle el rol Proveedor.');
        }
    }
}
