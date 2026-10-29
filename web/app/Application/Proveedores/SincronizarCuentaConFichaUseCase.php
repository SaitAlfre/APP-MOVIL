<?php

namespace App\Application\Proveedores;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\Auditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use DateTimeImmutable;

/**
 * Lleva el estado de una ficha a su cuenta vinculada (dirección Proveedores → Usuarios). Solo actúa sobre cuentas
 * cuyo único rol es Proveedor: suspender o retirar la ficha suspende la cuenta; reactivarla restaura la cuenta si
 * ambas se suspendieron por este enlace. Una suspensión directa de la cuenta se conserva.
 */
final class SincronizarCuentaConFichaUseCase
{
    public const SIN_CUENTA = 'sin_cuenta';

    public const OTROS_ROLES = 'otros_roles';

    public const CUENTA_SUSPENDIDA = 'cuenta_suspendida';

    public const CUENTA_RESTAURADA = 'cuenta_restaurada';

    public const CUENTA_YA_SUSPENDIDA = 'cuenta_ya_suspendida';

    public const SIN_CAMBIO = 'sin_cambio';

    public function __construct(
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AuditoriaRepositoryInterface $auditorias,
    ) {}

    /** @return self::* */
    public function ejecutar(Proveedor $ficha, EstadoProveedor $nuevo, ?string $origenFichaAnterior, ?string $motivo, ?int $actorId): string
    {
        $cuenta = $ficha->usuarioId !== null ? $this->usuarios->buscarPorId($ficha->usuarioId) : null;
        if ($cuenta === null) {
            return self::SIN_CUENTA;
        }
        if ($cuenta->roles !== [Rol::Proveedor]) {
            return self::OTROS_ROLES;
        }
        $actorId ??= (int) auth('operador')->id();
        $ahora = new DateTimeImmutable;
        $suspendida = $cuenta->bloqueadoManualmente || ! $cuenta->activo;

        if ($nuevo !== EstadoProveedor::Activo) {
            if ($suspendida) {
                return self::CUENTA_YA_SUSPENDIDA;
            }
            $texto = "Ficha de proveedor {$ficha->codigo} ".($nuevo === EstadoProveedor::Retirado ? 'retirada' : 'suspendida').($motivo ? ": {$motivo}" : '.');
            $this->usuarios->guardar($cuenta->conBloqueoManual($texto, $actorId, $ahora));
            $this->usuarios->marcarOrigenSuspension($cuenta->id, UsuarioRepositoryInterface::ORIGEN_FICHA_PROVEEDOR);
            $this->usuarios->invalidarSesiones($cuenta->id);
            $this->auditar($cuenta->id, AccionAuditoria::Anular, true, $texto, $actorId, $ahora);

            return self::CUENTA_SUSPENDIDA;
        }

        if (! $suspendida) {
            return self::SIN_CAMBIO;
        }
        $enlazada = $this->usuarios->origenSuspension($cuenta->id) === UsuarioRepositoryInterface::ORIGEN_FICHA_PROVEEDOR
            || $origenFichaAnterior === ProveedorRepositoryInterface::ORIGEN_ACCESO_CUENTA;
        if (! $enlazada) {
            return self::CUENTA_YA_SUSPENDIDA;
        }
        $this->usuarios->guardar($cuenta->conEstado(true)->sinBloqueoManual());
        $this->usuarios->marcarOrigenSuspension($cuenta->id, null);
        $this->usuarios->invalidarSesiones($cuenta->id);
        $this->auditar($cuenta->id, AccionAuditoria::Autorizar, false, "Ficha de proveedor {$ficha->codigo} reactivada; se restaura su cuenta. Los bloqueos temporales se conservan.", $actorId, $ahora);

        return self::CUENTA_RESTAURADA;
    }

    private function auditar(int $usuarioId, AccionAuditoria $accion, bool $suspendida, string $motivo, int $actorId, DateTimeImmutable $ahora): void
    {
        $this->auditorias->registrar(new Auditoria(
            id: null, entidad: 'usuario', entidadId: $usuarioId, accion: $accion,
            valorAntes: json_encode(['suspendido' => ! $suspendida]),
            valorDespues: json_encode(['suspendido' => $suspendida, 'origen' => 'ficha_proveedor']),
            motivo: $motivo, usuarioId: $actorId, ocurridoEn: $ahora,
        ));
    }
}
