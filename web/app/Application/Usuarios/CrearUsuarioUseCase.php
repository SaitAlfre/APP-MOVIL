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

final class CrearUsuarioUseCase
{
    use ValidaDatosUsuario;

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    /** @param list<Rol> $roles */
    public function ejecutar(string $username, string $nombres, string $dni, string $pinPlano, array $roles, bool $actorEsAdmin, int $actorId): UsuarioDominio
    {
        if ($this->usuarios->buscarPorUsername(trim($username)) !== null) {
            throw UsuarioInvalidoException::usernameDuplicado();
        }

        $this->validarAsignacionDeAdmin($roles, $actorEsAdmin);
        $this->validarPin($pinPlano);

        $usuario = UsuarioDominio::crear($username, $nombres, $dni, $pinPlano, $roles);
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
