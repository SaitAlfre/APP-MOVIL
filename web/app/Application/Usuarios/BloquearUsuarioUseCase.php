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

final class BloquearUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function ejecutar(int $id, string $motivo, int $bloqueadoPorId): UsuarioDominio
    {
        $usuario = $this->usuarios->buscarPorId($id);

        if ($usuario === null) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        if ($usuario->esAdmin() && $this->usuarios->contarAdminsDisponibles($id) === 0) {
            throw UsuarioInvalidoException::sinAdministradoresActivos();
        }

        $ahora = new DateTimeImmutable;
        $guardado = $this->usuarios->guardar($usuario->conBloqueoManual($motivo, $bloqueadoPorId, $ahora));
        $this->usuarios->invalidarSesiones($id);

        $this->auditorias->registrar(new Auditoria(
            id: null,
            entidad: 'usuario',
            entidadId: $id,
            accion: AccionAuditoria::Anular,
            valorAntes: 'bloqueado_manualmente=0',
            valorDespues: 'bloqueado_manualmente=1',
            motivo: $motivo,
            usuarioId: $bloqueadoPorId,
            ocurridoEn: $ahora,
        ));

        return $guardado;
    }
}
