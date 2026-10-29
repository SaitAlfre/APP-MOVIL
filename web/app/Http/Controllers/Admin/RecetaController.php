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
    /** Unidades en que se expresa lo que rinde una tanda. */
    public const UNIDADES = ['unidad' => 'unidades', 'kg' => 'kilogramos', 'L' => 'litros'];

    public function index(): View
    {
        $recetas = Receta::with('productos:id,receta_id,nombre')->withCount('productos')->orderByDesc('activo')->orderBy('nombre')->get();
        $ingredientes = DB::table('receta_materiales as receta')->join('materiales as material', 'material.id', '=', 'receta.material_id')
            ->orderBy('material.nombre')->get(['receta.receta_id', 'material.nombre', 'material.unidad', 'receta.cantidad'])->groupBy('receta_id');

        return view('admin.recetas.index', ['recetas' => $recetas, 'ingredientes' => $ingredientes, 'totales' => [
            'total' => $recetas->count(), 'activas' => $recetas->where('activo', true)->count(), 'sinProducto' => $recetas->where('productos_count', 0)->count(),
        ]]);
    }

    public function create(Request $request): View
    {
        $request->validate(['producto_id' => ['nullable', 'integer', 'exists:productos,id']]);

        return view('admin.recetas.form', ['receta' => null, 'producto' => $request->filled('producto_id') ? Producto::findOrFail($request->integer('producto_id')) : null,
            'materiales' => Material::orderBy('nombre')->get(), 'ingredientes' => []]);
    }

    public function edit(Receta $receta): View
    {
        return view('admin.recetas.form', ['receta' => $receta->load('productos'), 'producto' => null,
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
            $request->merge(['nombre' => $request->input('nombre', $producto->nombre)]);
        }
        $datos = $this->validar($request);
        DB::transaction(function () use ($datos, $gestion, $producto): void {
            $receta = $gestion->actualizar(new Receta, $datos, $datos['ingredientes'] ?? []);
            if ($producto !== null) {
                $gestion->vincular($producto->id, $receta->id);
            }
        });

        return redirect()->route('admin.recetas.index')->with('estado', $producto !== null ? 'Receta creada y vinculada a '.$producto->nombre.'.' : 'Receta creada. Ya puedes crear su producto.');
    }

    public function update(Request $request, Receta $receta, GestionRecetas $gestion): RedirectResponse
    {
        $datos = $this->validar($request, $receta);
        $gestion->actualizar($receta, $datos, $datos['ingredientes'] ?? []);

        return redirect()->route('admin.recetas.index')->with('estado', 'Receta actualizada. Los lotes nuevos usarán estas cantidades.');
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
            'unidad_base' => ['sometimes', Rule::in(array_keys(self::UNIDADES))],
            'litros_tanda' => ['required', 'numeric', 'min:0.001', 'max:999999', 'decimal:0,3'],
            'rinde_unidades' => ['required', 'numeric', 'min:0.001', 'max:999999', 'decimal:0,3'],
            'litros_por_unidad' => ['prohibited'],
            'otros_insumos' => ['nullable', 'string', 'max:500'],
            'ingredientes' => ['sometimes', 'array', 'max:100'],
            'ingredientes.*.material_id' => ['required', 'integer', 'distinct', 'exists:materiales,id'],
            'ingredientes.*.cantidad' => ['required', 'numeric', 'min:0.001', 'max:999999', 'decimal:0,3'],
        ]);
    }
}
