<?php

namespace App\Http\Controllers\Admin;

use App\Application\Calidad\ListarControlesCalidadUseCase;
use App\Application\Calidad\RegistrarControlCalidadUseCase;
use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Calidad\EstadoCalidad;
use App\Domain\Calidad\Exceptions\ControlCalidadInvalidoException;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class CalidadController extends Controller
{
    public function index(
        Request $request,
        ListarControlesCalidadUseCase $listar,
        ControlCalidadRepositoryInterface $controlCalidad,
        EntregaRepositoryInterface $entregas,
        ProveedorRepositoryInterface $proveedores,
        UsuarioRepositoryInterface $usuarios,
    ): View {
        $filtro = $request->string('resultado')->toString();
        $resultado = $filtro !== '' ? EstadoCalidad::tryFrom($filtro) : null;

        $controles = $listar->ejecutar(resultado: $resultado);

        $filas = collect($controles->items())->map(function ($control) use ($entregas, $proveedores, $usuarios) {
            $entrega = $entregas->buscarPorId($control->entregaId);

            return [
                'control' => $control,
                'proveedor' => $entrega !== null ? $proveedores->buscarPorId($entrega->proveedorId) : null,
                'usuario' => $usuarios->buscarPorId($control->usuarioId),
            ];
        });

        $conteos = $controlCalidad->contarPorResultado();
        $totalEvaluadas = array_sum($conteos);

        $pestanas = ['controles', 'reglas'];
        $pestana = $request->string('tab')->toString();

        return view('admin.calidad.index', [
            'filas' => $filas,
            'paginador' => $controles,
            'pendientes' => count($entregas->sinControlCalidad()),
            'conteos' => $conteos,
            'tasaAprobacion' => $totalEvaluadas > 0 ? round(($conteos['aprobado'] / $totalEvaluadas) * 100, 1) : null,
            'filtroActual' => $resultado,
            'pestana' => in_array($pestana, $pestanas, true) ? $pestana : 'controles',
        ]);
    }

    public function create(EntregaRepositoryInterface $entregas, ProveedorRepositoryInterface $proveedores): View
    {
        $pendientes = collect($entregas->sinControlCalidad())->map(fn ($entrega) => [
            'entrega' => $entrega,
            'proveedor' => $proveedores->buscarPorId($entrega->proveedorId),
        ]);

        return view('admin.calidad.create', ['pendientes' => $pendientes]);
    }

    public function store(Request $request, RegistrarControlCalidadUseCase $registrar): RedirectResponse
    {
        $request->validate([
            'entrega_id' => ['required', 'integer'],
            'resultado' => ['required', 'string', 'in:aprobado,observado,rechazado'],
            'temperatura_c' => ['nullable', 'numeric', 'between:-5,60'],
            'acidez' => ['nullable', 'numeric', 'between:0,50'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ], [
            'temperatura_c.between' => 'La temperatura debe estar entre -5°C y 60°C.',
            'acidez.between' => 'La acidez debe estar entre 0°D y 50°D.',
        ]);

        try {
            $registrar->ejecutar(
                entregaId: $request->integer('entrega_id'),
                usuarioId: auth('operador')->id(),
                resultado: EstadoCalidad::from($request->string('resultado')->toString()),
                temperaturaC: $request->filled('temperatura_c') ? (float) $request->input('temperatura_c') : null,
                acidez: $request->filled('acidez') ? (float) $request->input('acidez') : null,
                observaciones: $request->string('observaciones')->toString() ?: null,
            );
        } catch (ControlCalidadInvalidoException|RuntimeException $e) {
            return back()->withErrors(['entrega_id' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.calidad.index')->with('estado', 'Control de calidad registrado correctamente.');
    }
}
