<?php

namespace App\Http\Controllers\Admin;

use App\Application\Proveedores\ActualizarProveedorUseCase;
use App\Application\Proveedores\CambiarEstadoProveedorUseCase;
use App\Application\Proveedores\CrearProveedorUseCase;
use App\Application\Proveedores\DesvincularUsuarioProveedorUseCase;
use App\Application\Proveedores\ListarProveedoresUseCase;
use App\Application\Proveedores\VincularUsuarioProveedorUseCase;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProveedorRequest;
use App\Http\Requests\Admin\UpdateProveedorRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(ListarProveedoresUseCase $listar, ZonaRepositoryInterface $zonas): View
    {
        return view('admin.proveedores.index', [
            'proveedores' => $listar->ejecutar(),
            'zonas' => collect($zonas->todas())->keyBy('id'),
        ]);
    }

    public function create(ZonaRepositoryInterface $zonas): View
    {
        return view('admin.proveedores.form', [
            'proveedor' => null,
            'zonas' => $zonas->activas(),
            'usuariosProveedor' => [],
        ]);
    }

    public function store(StoreProveedorRequest $request, CrearProveedorUseCase $crear): RedirectResponse
    {
        try {
            $crear->ejecutar($request->aDatosProveedor(), auth('operador')->id());
        } catch (ProveedorInvalidoException $e) {
            return back()->withErrors(['codigo' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.proveedores.index')->with('estado', 'Proveedor registrado correctamente.');
    }

    public function edit(int $proveedor, ZonaRepositoryInterface $zonas, ProveedorRepositoryInterface $proveedores, UsuarioRepositoryInterface $usuarios): View
    {
        $entidad = $proveedores->buscarPorId($proveedor);
        abort_if($entidad === null, 404);

        return view('admin.proveedores.form', [
            'proveedor' => $entidad,
            'zonas' => $zonas->activas(),
            'usuariosProveedor' => $usuarios->conRol(Rol::Proveedor),
        ]);
    }

    public function update(int $proveedor, UpdateProveedorRequest $request, ActualizarProveedorUseCase $actualizar): RedirectResponse
    {
        try {
            $actualizar->ejecutar($proveedor, $request->aDatosProveedor());
        } catch (ProveedorInvalidoException $e) {
            return back()->withErrors(['codigo' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.proveedores.index')->with('estado', 'Proveedor actualizado correctamente.');
    }

    public function cambiarEstado(int $proveedor, Request $request, CambiarEstadoProveedorUseCase $cambiarEstado): RedirectResponse
    {
        $request->validate([
            'estado' => ['required', 'string', 'in:activo,suspendido,retirado'],
        ]);

        $cambiarEstado->ejecutar($proveedor, EstadoProveedor::from($request->string('estado')->toString()));

        return redirect()->route('admin.proveedores.index')->with('estado', 'Estado del proveedor actualizado.');
    }

    public function vincularUsuario(int $proveedor, Request $request, VincularUsuarioProveedorUseCase $vincular): RedirectResponse
    {
        $request->validate(['usuario_id' => ['required', 'integer']]);

        try {
            $vincular->ejecutar($proveedor, $request->integer('usuario_id'));
        } catch (ProveedorInvalidoException $e) {
            return back()->withErrors(['usuario_id' => $e->getMessage()]);
        }

        return redirect()->route('admin.proveedores.edit', $proveedor)->with('estado', 'Usuario vinculado correctamente.');
    }

    public function desvincularUsuario(int $proveedor, DesvincularUsuarioProveedorUseCase $desvincular): RedirectResponse
    {
        $desvincular->ejecutar($proveedor);

        return redirect()->route('admin.proveedores.edit', $proveedor)->with('estado', 'Usuario desvinculado.');
    }
}
