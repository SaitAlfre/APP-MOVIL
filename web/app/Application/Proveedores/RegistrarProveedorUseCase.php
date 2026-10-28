<?php

namespace App\Application\Proveedores;

use App\Application\Usuarios\CrearUsuarioUseCase;
use App\Domain\Proveedores\Exceptions\RegistroProveedorException;
use App\Domain\Proveedores\Proveedor;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Registro único de proveedores, usado desde Proveedores y desde Usuarios y accesos.
 * Crea la ficha productiva y, opcionalmente, su cuenta móvil, o vincula registros existentes
 * del mismo DNI tras una confirmación explícita. Todo ocurre en una sola transacción.
 */
final class RegistrarProveedorUseCase
{
    public function __construct(
        private readonly AnalizarRegistroProveedorUseCase $analizar,
        private readonly CrearProveedorUseCase $crearProveedor,
        private readonly CrearUsuarioUseCase $crearUsuario,
        private readonly VincularUsuarioProveedorUseCase $vincular,
        private readonly ProveedorRepositoryInterface $proveedores,
    ) {}

    public function ejecutar(DatosProveedor $datos, ?CredencialesCuentaMovil $credenciales, ?string $accionConfirmada, ActorRegistroProveedor $actor): ResultadoRegistroProveedor
    {
        return DB::transaction(function () use ($datos, $credenciales, $accionConfirmada, $actor): ResultadoRegistroProveedor {
            $analisis = $this->analizar->ejecutar($datos->dni);
            $situacion = $analisis->situacion;

            if ($situacion->bloqueaRegistro()) {
                throw RegistroProveedorException::situacion($situacion->mensaje());
            }
            if ($situacion === SituacionRegistroProveedor::FichaSinCuenta && $credenciales === null) {
                throw RegistroProveedorException::nadaQueRegistrar();
            }
            if ($accionConfirmada !== $situacion->accionConfirmable()) {
                throw RegistroProveedorException::confirmacionRequerida($situacion->mensaje());
            }

            return match ($situacion) {
                SituacionRegistroProveedor::Nuevo => $this->registrarNuevo($datos, $credenciales, $actor),
                SituacionRegistroProveedor::FichaSinCuenta => $this->crearCuentaParaFichaExistente($analisis->ficha, $credenciales, $actor),
                SituacionRegistroProveedor::CuentaSinFicha => $this->registrarFichaParaCuenta($datos, $analisis->cuenta, $actor),
                SituacionRegistroProveedor::FichaYCuentaSinVincular => $this->vincularExistentes($analisis->ficha, $analisis->cuenta, $actor),
            };
        });
    }

    /** Alta de la cuenta móvil desde la edición de una ficha que aún no tiene acceso. */
    public function crearCuentaParaFicha(int $proveedorId, CredencialesCuentaMovil $credenciales, ActorRegistroProveedor $actor): ResultadoRegistroProveedor
    {
        return DB::transaction(function () use ($proveedorId, $credenciales, $actor): ResultadoRegistroProveedor {
            $ficha = $this->proveedores->buscarPorId($proveedorId) ?? throw new RuntimeException('El proveedor no existe.');
            $analisis = $this->analizar->ejecutar($ficha->dni);

            if ($analisis->situacion !== SituacionRegistroProveedor::FichaSinCuenta || $analisis->ficha?->id !== $ficha->id) {
                throw RegistroProveedorException::situacion($analisis->situacion->mensaje());
            }

            return $this->crearCuentaParaFichaExistente($ficha, $credenciales, $actor);
        });
    }

    private function registrarNuevo(DatosProveedor $datos, ?CredencialesCuentaMovil $credenciales, ActorRegistroProveedor $actor): ResultadoRegistroProveedor
    {
        if ($credenciales !== null) {
            $this->exigirPermisoCuentas($actor);
        }

        $ficha = $this->crearProveedor->ejecutar($datos, $actor->id);
        if ($credenciales === null) {
            return new ResultadoRegistroProveedor($ficha, null, SituacionRegistroProveedor::Nuevo, false);
        }

        $cuenta = $this->crearCuenta($ficha, $credenciales, $actor);

        return new ResultadoRegistroProveedor($this->vincular->ejecutar($ficha->id, $cuenta->id), $cuenta, SituacionRegistroProveedor::Nuevo, true);
    }

    private function crearCuentaParaFichaExistente(Proveedor $ficha, ?CredencialesCuentaMovil $credenciales, ActorRegistroProveedor $actor): ResultadoRegistroProveedor
    {
        if ($credenciales === null) {
            throw RegistroProveedorException::credencialesRequeridas();
        }
        $this->exigirPermisoCuentas($actor);
        $this->exigirPermisoVincular($actor);

        $cuenta = $this->crearCuenta($ficha, $credenciales, $actor);

        return new ResultadoRegistroProveedor($this->vincular->ejecutar($ficha->id, $cuenta->id), $cuenta, SituacionRegistroProveedor::FichaSinCuenta, true);
    }

    private function registrarFichaParaCuenta(DatosProveedor $datos, Usuario $cuenta, ActorRegistroProveedor $actor): ResultadoRegistroProveedor
    {
        $this->exigirPermisoVincular($actor);
        $ficha = $this->crearProveedor->ejecutar($datos, $actor->id);

        return new ResultadoRegistroProveedor($this->vincular->ejecutar($ficha->id, $cuenta->id), $cuenta, SituacionRegistroProveedor::CuentaSinFicha, false);
    }

    private function vincularExistentes(Proveedor $ficha, Usuario $cuenta, ActorRegistroProveedor $actor): ResultadoRegistroProveedor
    {
        $this->exigirPermisoVincular($actor);

        return new ResultadoRegistroProveedor($this->vincular->ejecutar($ficha->id, $cuenta->id), $cuenta, SituacionRegistroProveedor::FichaYCuentaSinVincular, false);
    }

    /** La identidad de la cuenta se toma de la ficha para que DNI y nombre nazcan consistentes. */
    private function crearCuenta(Proveedor $ficha, CredencialesCuentaMovil $credenciales, ActorRegistroProveedor $actor): Usuario
    {
        return $this->crearUsuario->ejecutar(
            username: $credenciales->username,
            nombres: $ficha->nombres,
            dni: $ficha->dni,
            pinPlano: $credenciales->pin,
            roles: [Rol::Proveedor],
            actorEsAdmin: $actor->esAdministradorOficial,
            actorId: $actor->id,
        );
    }

    private function exigirPermisoCuentas(ActorRegistroProveedor $actor): void
    {
        if (! $actor->esAdministradorOficial || ! $actor->puedeGestionarCuentas) {
            throw RegistroProveedorException::sinPermisoCuentas();
        }
    }

    private function exigirPermisoVincular(ActorRegistroProveedor $actor): void
    {
        if (! $actor->puedeVincularCuentas) {
            throw RegistroProveedorException::sinPermisoVincular();
        }
    }
}
