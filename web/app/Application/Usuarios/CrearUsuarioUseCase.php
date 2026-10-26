<?php

namespace App\Application\Usuarios;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario as UsuarioDominio;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use DateTimeImmutable;

final class CrearUsuarioUseCase
{
    use ValidaDatosUsuario;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
        private readonly ZonaRepositoryInterface $zonas,
    ) {}

    /** @param list<Rol> $roles */
    public function ejecutar(string $username, string $nombres, string $dni, string $pinPlano, array $roles, bool $actorEsAdmin, int $actorId, ?int $zonaId = null): UsuarioDominio
    {
        $username = strtolower(trim($username));
        $dni = trim($dni);
        $this->validarIdentidad($username, $dni);
        if ($this->usuarios->buscarPorUsername($username) !== null) {
            throw UsuarioInvalidoException::usernameDuplicado();
        }

        $this->validarAsignacionDeAdmin($roles, $actorEsAdmin);
        $this->validarRolesAsignables($roles);
        $this->validarZonaAsignada($zonaId, $roles);
        $this->validarPin($pinPlano);

        $usuario = UsuarioDominio::crear($username, $nombres, $dni, $pinPlano, $roles)->conZonaAsignada($zonaId);
        $guardado = $this->usuarios->guardar($usuario);

        $this->auditorias->registrar(new Auditoria(
            id: null,
            entidad: 'usuario',
            entidadId: $guardado->id,
            accion: AccionAuditoria::Crear,
            valorAntes: null,
            valorDespues: 'roles='.implode(',', array_map(fn (Rol $r) => $r->value, $roles)),
            motivo: null,
            usuarioId: $actorId,
            ocurridoEn: new DateTimeImmutable,
        ));

        return $guardado;
    }
}
