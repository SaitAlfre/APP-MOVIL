<?php

namespace App\Application\Usuarios;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Suspender / Restaurar acceso. Si la cuenta tiene el rol Proveedor, su ficha vinculada por `usuario_id`
 * se suspende en la misma transacción, salvo que esté Retirada o ya Suspendida por otra causa. Al restaurar,
 * solo se reactiva la ficha cuya suspensión originó esta misma acción; las demás restricciones se conservan.
 */
final class GestionarAccesoUsuarioUseCase
{
    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    public function ejecutar(int $id, bool $suspender, ?string $motivo, int $actorId): ResultadoAccesoUsuario
    {
        return DB::transaction(function () use ($id, $suspender, $motivo, $actorId): ResultadoAccesoUsuario {
            DB::table('usuarios')->orderBy('id')->lockForUpdate()->get(['id']);
            $usuario = $this->usuarios->buscarPorId($id) ?? throw new RuntimeException('Usuario no encontrado.');
            if ($suspender && $usuario->esAdmin() && $this->usuarios->contarAdminsDisponibles($id) === 0) {
                throw UsuarioInvalidoException::sinAdministradoresActivos();
            }
            $ahora = new DateTimeImmutable;
            $motivo = trim($motivo ?? '');
            $origenCuenta = $this->usuarios->origenSuspension($id);
            if (! $suspender && $usuario->roles === [Rol::Proveedor] && $this->proveedores->buscarPorUsuarioId($id)?->estado === EstadoProveedor::Retirado) {
                throw new UsuarioInvalidoException('La ficha de proveedor de esta cuenta está Retirada. Reactívala en Proveedores; la cuenta se restaurará junto con ella.');
            }
            $actualizado = $suspender
                ? $usuario->conBloqueoManual($motivo, $actorId, $ahora)
                : $usuario->conEstado(true)->sinBloqueoManual();
            $guardado = $this->usuarios->guardar($actualizado);
            // Una suspensión o restauración directa reemplaza cualquier origen anterior.
            $this->usuarios->marcarOrigenSuspension($id, null);
            $this->usuarios->invalidarSesiones($id);
            $ficha = $usuario->tieneRol(Rol::Proveedor) ? $this->ficha($usuario, $suspender, $motivo, $actorId, $ahora, $origenCuenta) : null;
            $this->auditorias->registrar(new Auditoria(
                id: null, entidad: 'usuario', entidadId: $id,
                accion: $suspender ? AccionAuditoria::Anular : AccionAuditoria::Autorizar,
                valorAntes: json_encode(['activo' => $usuario->activo, 'suspendido' => $usuario->bloqueadoManualmente]),
                valorDespues: json_encode(['activo' => $guardado->activo, 'suspendido' => $guardado->bloqueadoManualmente, 'ficha_productiva' => $ficha]),
                motivo: $suspender ? $motivo : 'Restauración administrativa; se conservan los bloqueos automáticos y las restricciones productivas independientes.',
                usuarioId: $actorId, ocurridoEn: $ahora,
            ));

            return new ResultadoAccesoUsuario($guardado, $ficha);
        });
    }

    /** @return ResultadoAccesoUsuario::* */
    private function ficha(Usuario $usuario, bool $suspender, string $motivo, int $actorId, DateTimeImmutable $ahora, ?string $origenCuenta): string
    {
        $ficha = $this->proveedores->buscarPorUsuarioId($usuario->id);
        if ($ficha === null) {
            return ResultadoAccesoUsuario::SIN_FICHA;
        }
        DB::table('proveedores')->where('id', $ficha->id)->lockForUpdate()->first();
        $origen = $this->proveedores->origenSuspension($ficha->id);

        if ($suspender) {
            return match ($ficha->estado) {
                EstadoProveedor::Retirado => ResultadoAccesoUsuario::FICHA_RETIRADA,
                EstadoProveedor::Suspendido => $origen === ProveedorRepositoryInterface::ORIGEN_ACCESO_CUENTA
                    ? ResultadoAccesoUsuario::FICHA_SUSPENDIDA : ResultadoAccesoUsuario::FICHA_YA_SUSPENDIDA,
                EstadoProveedor::Activo => $this->cambiarFicha($ficha, EstadoProveedor::Suspendido, $origen,
                    "Suspensión del acceso de la cuenta vinculada «{$usuario->username}»: {$motivo}", $actorId, $ahora),
            };
        }

        return match (true) {
            $ficha->estado === EstadoProveedor::Suspendido && ($origen === ProveedorRepositoryInterface::ORIGEN_ACCESO_CUENTA
                || $origenCuenta === UsuarioRepositoryInterface::ORIGEN_FICHA_PROVEEDOR) => $this->cambiarFicha($ficha, EstadoProveedor::Activo, $origen,
                    "Restauración del acceso de la cuenta vinculada «{$usuario->username}»; la ficha y la cuenta estaban suspendidas por el mismo enlace.", $actorId, $ahora),
            $ficha->estado === EstadoProveedor::Suspendido => ResultadoAccesoUsuario::FICHA_SUSPENSION_INDEPENDIENTE,
            $ficha->estado === EstadoProveedor::Retirado => ResultadoAccesoUsuario::FICHA_RETIRADA,
            default => ResultadoAccesoUsuario::FICHA_ACTIVA,
        };
    }

    /** @return ResultadoAccesoUsuario::* */
    private function cambiarFicha(Proveedor $ficha, EstadoProveedor $estado, ?string $origenAnterior, string $motivo, int $actorId, DateTimeImmutable $ahora): string
    {
        $this->proveedores->cambiarEstadoPorAccesoDeCuenta($ficha->id, $estado);
        $this->auditorias->registrar(new Auditoria(
            id: null, entidad: 'proveedor', entidadId: $ficha->id, accion: AccionAuditoria::Actualizar,
            valorAntes: json_encode(['estado' => $ficha->estado->value, 'suspension_origen' => $origenAnterior], JSON_UNESCAPED_UNICODE),
            valorDespues: json_encode(['estado' => $estado->value, 'suspension_origen' => $estado === EstadoProveedor::Suspendido ? ProveedorRepositoryInterface::ORIGEN_ACCESO_CUENTA : null], JSON_UNESCAPED_UNICODE),
            motivo: $motivo, usuarioId: $actorId, ocurridoEn: $ahora,
        ));

        return $estado === EstadoProveedor::Suspendido ? ResultadoAccesoUsuario::FICHA_SUSPENDIDA : ResultadoAccesoUsuario::FICHA_RESTAURADA;
    }
}
