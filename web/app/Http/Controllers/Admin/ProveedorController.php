<?php

namespace App\Http\Controllers\Admin;

use App\Application\Proveedores\ActualizarProveedorUseCase;
use App\Application\Proveedores\CambiarEstadoProveedorUseCase;
use App\Application\Proveedores\CrearProveedorUseCase;
use App\Application\Proveedores\DesvincularUsuarioProveedorUseCase;
use App\Application\Proveedores\ListarProveedoresUseCase;
use App\Application\Proveedores\VincularUsuarioProveedorUseCase;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProveedorRequest;
use App\Http\Requests\Admin\UpdateProveedorRequest;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function index(
        Request $request,
        ListarProveedoresUseCase $listar,
        ZonaRepositoryInterface $zonas,
        ProveedorRepositoryInterface $proveedores,
        EntregaRepositoryInterface $entregas,
        UsuarioRepositoryInterface $usuarios,
    ): View {
        $validados = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'estado' => ['nullable', 'string', 'in:activo,suspendido,retirado'],
        ]);

        $busqueda = $validados['q'] ?? null;
        $zonaId = isset($validados['zona_id']) ? (int) $validados['zona_id'] : null;
        $estado = isset($validados['estado']) ? EstadoProveedor::from($validados['estado']) : null;

        $paginador = $listar->ejecutar(15, $busqueda, $zonaId, $estado);

        // Litros y última entrega de la semana en curso, resueltos en una sola consulta.
        $inicioSemana = new DateTimeImmutable('monday this week');
        $hoy = new DateTimeImmutable('today');
        $ids = collect($paginador->items())->map(fn ($proveedor) => $proveedor->id)->filter()->values()->all();
        $resumenSemana = $entregas->resumenPorProveedores($ids, $inicioSemana, $hoy);

        return view('admin.proveedores.index', [
            'proveedores' => $paginador,
            'zonas' => collect($zonas->todas())->keyBy('id'),
            'zonasDisponibles' => $zonas->todas(),
            'resumenSemana' => $resumenSemana,
            'cuentas' => collect($usuarios->conRol(Rol::Proveedor))->keyBy('id'),
            'totalProveedores' => $proveedores->contarTodos(),
            'busqueda' => $busqueda,
            'zonaId' => $zonaId,
            'estadoFiltro' => $estado,
        ]);
    }

    public function show(
        int $proveedor,
        Request $request,
        ProveedorRepositoryInterface $proveedores,
        ZonaRepositoryInterface $zonas,
        EntregaRepositoryInterface $entregas,
        ControlCalidadRepositoryInterface $controles,
        LiquidacionRepositoryInterface $liquidaciones,
        AuditoriaRepositoryInterface $auditoria,
        UsuarioRepositoryInterface $usuarios,
    ): View {
        $entidad = $proveedores->buscarPorId($proveedor);
        abort_if($entidad === null, 404);

        $pestanas = ['resumen', 'entregas', 'calidad', 'liquidaciones', 'auditoria'];
        $pestana = $request->string('tab')->toString();
        $pestana = in_array($pestana, $pestanas, true) ? $pestana : 'resumen';

        $inicioSemana = new DateTimeImmutable('monday this week');
        $hoy = new DateTimeImmutable('today');
        $litrosSemana = $entregas->litrosPorProveedorEnRango($entidad->id, $inicioSemana, $hoy);
        $precioLitro = (float) (Configuracion::find('precio_base_litro')?->valor ?: 0);

        $ultimas = $entregas->ultimasDelProveedor($entidad->id, 20);
        $controlesPorEntrega = collect($ultimas)
            ->mapWithKeys(fn ($entrega) => [$entrega->id => $controles->buscarPorEntregaId($entrega->id)])
            ->all();

        return view('admin.proveedores.show', [
            'proveedor' => $entidad,
            'zona' => $zonas->buscarPorId($entidad->zonaId),
            'cuenta' => $entidad->usuarioId !== null ? $usuarios->buscarPorId($entidad->usuarioId) : null,
            'pestana' => $pestana,
            'litrosSemana' => $litrosSemana,
            'precioLitro' => $precioLitro,
            'entregas' => $ultimas,
            'controles' => $controlesPorEntrega,
            'liquidaciones' => $liquidaciones->ultimasDelProveedor($entidad->id, 10),
            'auditoria' => $auditoria->paginar(10, ['entidad' => 'proveedor', 'entidad_id' => $entidad->id]),
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
            return back()->withErrors(['codigo' => $this->mensajeSeguro($e)])->withInput();
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
            return back()->withErrors(['codigo' => $this->mensajeSeguro($e)])->withInput();
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
            return back()->withErrors(['usuario_id' => $this->mensajeSeguro($e)]);
        }

        return redirect()->route('admin.proveedores.edit', $proveedor)->with('estado', 'Usuario vinculado correctamente.');
    }

    public function desvincularUsuario(int $proveedor, DesvincularUsuarioProveedorUseCase $desvincular): RedirectResponse
    {
        $desvincular->ejecutar($proveedor);

        return redirect()->route('admin.proveedores.edit', $proveedor)->with('estado', 'Usuario desvinculado.');
    }
}
