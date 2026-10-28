<?php

namespace App\Application\Proveedores;

use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Revisa fichas y cuentas de proveedor históricas. Solo se proponen como fiables las parejas
 * con el mismo DNI, una única cuenta con rol Proveedor y ninguna de las dos vinculada.
 * Los conflictos nunca se vinculan automáticamente.
 */
final class ConciliarCuentasProveedoresUseCase
{
    public function __construct(
        private readonly ProveedorRepositoryInterface $proveedores,
        private readonly UsuarioRepositoryInterface $usuarios,
        private readonly AnalizarRegistroProveedorUseCase $analizar,
        private readonly VincularUsuarioProveedorUseCase $vincular,
    ) {}

    /**
     * @return array{vinculados: int, fichas_sin_cuenta: list<array{codigo: string, dni: string, nombre: string}>, cuentas_sin_ficha: list<array{username: string, dni: string, nombre: string}>, fiables: list<array{proveedor_id: int, usuario_id: int, codigo: string, username: string, dni: string}>, conflictos: list<array{dni: string, situacion: string, mensaje: string}>}
     */
    public function revisar(): array
    {
        $informe = ['vinculados' => 0, 'fichas_sin_cuenta' => [], 'cuentas_sin_ficha' => [], 'fiables' => [], 'conflictos' => []];
        $sinCuenta = $this->proveedores->sinCuentaVinculada();
        $informe['vinculados'] = $this->proveedores->contarTodos() - count($sinCuenta);
        $revisados = [];

        foreach ($sinCuenta as $ficha) {
            $revisados[trim($ficha->dni)] = true;
            $analisis = $this->analizar->ejecutar($ficha->dni);
            match ($analisis->situacion) {
                SituacionRegistroProveedor::FichaSinCuenta => $informe['fichas_sin_cuenta'][] = ['codigo' => $ficha->codigo, 'dni' => $ficha->dni, 'nombre' => $ficha->nombres],
                SituacionRegistroProveedor::FichaYCuentaSinVincular => $informe['fiables'][] = ['proveedor_id' => $ficha->id, 'usuario_id' => $analisis->cuenta->id, 'codigo' => $ficha->codigo, 'username' => $analisis->cuenta->username, 'dni' => $ficha->dni],
                default => $informe['conflictos'][] = ['dni' => $ficha->dni, 'situacion' => $analisis->situacion->value, 'mensaje' => $analisis->situacion->mensaje()],
            };
        }

        foreach ($this->usuarios->conRol(Rol::Proveedor) as $cuenta) {
            if (isset($revisados[trim($cuenta->dni)]) || $this->proveedores->buscarPorUsuarioId($cuenta->id) !== null) {
                continue;
            }
            $revisados[trim($cuenta->dni)] = true;
            $analisis = $this->analizar->ejecutar($cuenta->dni);
            if ($analisis->situacion === SituacionRegistroProveedor::CuentaSinFicha) {
                $informe['cuentas_sin_ficha'][] = ['username' => $cuenta->username, 'dni' => $cuenta->dni, 'nombre' => $cuenta->nombres];
            } else {
                $informe['conflictos'][] = ['dni' => $cuenta->dni, 'situacion' => $analisis->situacion->value, 'mensaje' => $analisis->situacion->mensaje()];
            }
        }

        return $informe;
    }

    /**
     * Vincula solo las parejas fiables, revalidando cada una en el momento de aplicarla.
     *
     * @return int cantidad de vinculaciones realizadas
     */
    public function vincularFiables(): int
    {
        return DB::transaction(function (): int {
            $vinculadas = 0;
            foreach ($this->revisar()['fiables'] as $pareja) {
                $this->vincular->ejecutar($pareja['proveedor_id'], $pareja['usuario_id']);
                $vinculadas++;
            }

            return $vinculadas;
        });
    }
}
