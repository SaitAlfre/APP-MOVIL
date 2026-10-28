<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Cliente;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompradorController extends Controller
{
    public function index(Request $request): View
    {
        $datos = $request->validate(['tipo' => ['nullable', Rule::in(['clientes', 'mayoristas', 'proveedores'])],
            'q' => ['nullable', 'string', 'max:120'], 'estado' => ['nullable', Rule::in(['todos', 'activos', 'inactivos'])]]);
        $tipo = $datos['tipo'] ?? 'clientes';
        $busqueda = trim($datos['q'] ?? '');
        $estado = $datos['estado'] ?? 'todos';
        $proveedores = $tipo === 'proveedores';
        $query = $proveedores ? Proveedor::query() : Cliente::where('tipo', $tipo === 'mayoristas' ? '=' : '!=', 'mayorista');
        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda, $proveedores): void {
                $q->where($proveedores ? 'nombres' : 'nombre', 'like', '%'.$busqueda.'%')->orWhere('codigo', 'like', '%'.$busqueda.'%')
                    ->orWhere($proveedores ? 'dni' : 'documento', 'like', '%'.$busqueda.'%');
            });
        }
        if ($estado !== 'todos') {
            if ($proveedores) {
                $query->where('estado', $estado === 'activos' ? '=' : '!=', 'activo');
            } else {
                $query->where('activo', $estado === 'activos');
            }
        }

        return view('admin.compradores.index', ['tipo' => $tipo, 'busqueda' => $busqueda, 'estadoFiltro' => $estado,
            'compradores' => $query->orderBy($proveedores ? 'nombres' : 'nombre')->paginate(20)->withQueryString()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $cliente = Cliente::create($datos + ['codigo' => 'CLI-'.strtoupper(bin2hex(random_bytes(4)))]);

        return redirect()->route('admin.compradores.index', ['tipo' => $cliente->tipo === 'mayorista' ? 'mayoristas' : 'clientes'])->with('estado', 'Comprador registrado.');
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $cliente->update($this->validar($request, $cliente));

        return redirect()->route('admin.compradores.index', ['tipo' => $cliente->tipo === 'mayorista' ? 'mayoristas' : 'clientes'])->with('estado', 'Comprador actualizado.');
    }

    public function estado(Request $request, Cliente $cliente): RedirectResponse
    {
        $datos = $request->validate(['activo' => ['required', 'boolean']]);
        $cliente->update($datos);

        return back()->with('estado', 'Estado del comprador actualizado.');
    }

    private function validar(Request $request, ?Cliente $cliente = null): array
    {
        return $request->validate([
            'tipo' => ['required', Rule::in(['mayorista', 'minorista', 'restaurante', 'institucional'])],
            'nombre' => ['required', 'string', 'max:150'],
            'documento' => ['required', 'string', 'max:20', Rule::unique('clientes')->ignore($cliente?->id)],
            'celular' => ['nullable', 'string', 'max:20'], 'ciudad' => ['nullable', 'string', 'max:80'],
        ]);
    }
}
