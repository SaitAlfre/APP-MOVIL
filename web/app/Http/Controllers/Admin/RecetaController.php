<?php

namespace App\Http\Controllers\Admin;

use App\Application\Produccion\GestionRecetas;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Material;
use App\Infrastructure\Persistence\Eloquent\Producto;
use App\Infrastructure\Persistence\Eloquent\Receta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecetaController extends Controller
{
    public function index(): View
    {
        return view('admin.recetas.index', ['recetas' => Receta::withCount('productos')->orderBy('nombre')->paginate(20)]);
    }

    public function create(Request $request): View
    {
        $request->validate(['producto_id' => ['nullable', 'integer', 'exists:productos,id']]);

        return view('admin.recetas.form', ['receta' => null, 'producto' => $request->filled('producto_id') ? Producto::findOrFail($request->integer('producto_id')) : null,
            'materiales' => Material::orderBy('nombre')->get(), 'ingredientes' => []]);
    }

    public function edit(Receta $receta): View
    {
        return view('admin.recetas.form', ['receta' => $receta, 'producto' => null,
            'materiales' => Material::orderBy('nombre')->get(), 'ingredientes' => DB::table('receta_materiales')->where('receta_id', $receta->id)->get()->map(fn ($i) => (array) $i)->all()]);
    }

    public function store(Request $request, GestionRecetas $gestion): RedirectResponse
    {
        $request->validate(['producto_id' => ['nullable', 'integer', 'exists:productos,id']]);
        $producto = $request->filled('producto_id') ? Producto::findOrFail($request->integer('producto_id')) : null;
        if ($producto !== null) {
            if ($producto->receta_id !== null || ! $producto->activo) {
                throw ValidationException::withMessages(['producto_id' => 'El producto ya tiene una receta o está inactivo.']);
            }
            $request->merge(['nombre' => $request->input('nombre', $producto->nombre), 'unidad_base' => $request->input('unidad_base', $producto->unidad_produccion)]);
        }
        $datos = $this->validar($request);
        DB::transaction(function () use ($datos, $gestion, $producto): void {
            $receta = $gestion->actualizar(new Receta, $datos, $datos['ingredientes'] ?? []);
            if ($producto !== null) {
                $gestion->vincular($producto->id, $receta->id);
            }
        });

        return redirect()->route('admin.recetas.index')->with('estado', 'Receta creada. Ya puedes vincularla a tus productos.');
    }

    public function update(Request $request, Receta $receta, GestionRecetas $gestion): RedirectResponse
    {
        $datos = $this->validar($request, $receta);
        $gestion->actualizar($receta, $datos, $datos['ingredientes'] ?? []);

        return redirect()->route('admin.recetas.index')->with('estado', 'Receta actualizada en sus productos vinculados.');
    }

    public function estado(Request $request, Receta $receta, GestionRecetas $gestion): RedirectResponse
    {
        $datos = $request->validate(['receta_activa' => ['required', 'boolean']]);
        $gestion->cambiarEstado($receta, (bool) $datos['receta_activa']);

        return back()->with('estado', 'Estado de la receta actualizado.');
    }

    private function validar(Request $request, ?Receta $receta = null): array
    {
        return $request->validate([
            'nombre' => [$receta ? 'sometimes' : 'required', 'string', 'max:150', Rule::unique('recetas')->ignore($receta?->id)],
            'unidad_base' => ['sometimes', 'string', 'max:20'],
            'litros_por_unidad' => ['required', 'numeric', 'min:0.001', 'max:999999', 'decimal:0,3'],
            'otros_insumos' => ['nullable', 'string', 'max:500'],
            'ingredientes' => ['sometimes', 'array', 'max:100'],
            'ingredientes.*.material_id' => ['required', 'integer', 'distinct', 'exists:materiales,id'],
            'ingredientes.*.cantidad' => ['required', 'numeric', 'min:0.001', 'max:999999', 'decimal:0,3'],
        ]);
    }
}
