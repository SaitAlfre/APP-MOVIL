<?php

namespace App\Http\Controllers\Admin;

use App\Application\Produccion\ListarDiasConSaldoDisponibleUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Material;
use App\Infrastructure\Persistence\Eloquent\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request, ListarDiasConSaldoDisponibleUseCase $leche): View
    {
        $request->validate(['producto_id' => ['nullable', 'integer', 'exists:productos,id']]);
        $producto = $request->filled('producto_id') ? Producto::with('receta')->findOrFail($request->integer('producto_id')) : null;
        $ids = $producto !== null ? DB::table('receta_materiales')->where('receta_id', $producto->receta_id)->pluck('material_id') : null;
        $tab = in_array($request->input('tab'), ['materiales', 'movimientos-materiales']) ? $request->input('tab') : 'resumen';

        return view('admin.inventario.index', [
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'productoFiltro' => $producto,
            'tab' => $tab,
            'materiales' => Material::when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('nombre')->get(),
            'catalogoMateriales' => Material::orderBy('nombre')->get(),
            'lecheDisponible' => $leche->ejecutar(1),
            'movimientosMateriales' => DB::table('movimientos_material as movimiento')
                ->join('materiales as material', 'material.id', '=', 'movimiento.material_id')
                ->where('movimiento.tipo', 'entrada')->when($ids !== null, fn ($q) => $q->whereIn('material.id', $ids))
                ->orderByDesc('movimiento.id')->select('movimiento.*', 'material.nombre', 'material.unidad')->paginate(20, ['*'], 'material_page'),
        ]);
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:materiales,nombre', 'not_regex:/^leche$/iu'],
            'unidad' => ['required', Rule::in(['kg', 'g', 'L', 'mL', 'unidad'])],
            'stock_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3'],
        ], ['nombre.not_regex' => 'La leche ya se administra desde el acopio.']);
        $request->validate(['cantidad_inicial' => ['nullable', 'numeric', 'min:0.001', 'max:999999999', 'decimal:0,3']]);
        DB::transaction(function () use ($datos, $request): void {
            $material = Material::create($datos);
            if ($request->filled('cantidad_inicial')) {
                $cantidad = (float) $request->input('cantidad_inicial');
                $material->increment('existencia', $cantidad);
                DB::table('movimientos_material')->insert(['material_id' => $material->id, 'usuario_id' => auth('operador')->id(),
                    'tipo' => 'entrada', 'cantidad' => $cantidad, 'motivo' => 'Ingreso inicial de ingrediente', 'fecha' => now()]);
            }
        });

        return redirect()->route('admin.inventario.index', ['tab' => 'materiales'])->with('estado', 'Ingrediente registrado.');
    }

    public function storeMovimientoMaterial(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'material_id' => ['required', 'integer', 'exists:materiales,id'],
            'operacion' => ['required', Rule::in(['entrada'])],
            'cantidad' => ['required', 'numeric', 'min:0.001', 'max:999999999', 'decimal:0,3'],
            'motivo' => ['required', 'string', 'max:200'],
        ]);
        DB::transaction(function () use ($datos): void {
            $material = Material::whereKey($datos['material_id'])->lockForUpdate()->firstOrFail();
            $cantidad = (float) $datos['cantidad'];
            $material->increment('existencia', $cantidad);
            DB::table('movimientos_material')->insert([
                'material_id' => $material->id, 'usuario_id' => auth('operador')->id(),
                'tipo' => $datos['operacion'], 'cantidad' => $cantidad, 'motivo' => $datos['motivo'], 'fecha' => now(),
            ]);
        });

        return redirect()->route('admin.inventario.index', ['tab' => 'materiales'])->with('estado', 'Movimiento del material registrado.');
    }

    public function updateMinimoMaterial(Request $request, Material $material): RedirectResponse|JsonResponse
    {
        $datos = $request->validate(['stock_minimo' => ['nullable', 'numeric', 'min:0', 'max:999999999', 'decimal:0,3']],
            ['stock_minimo.decimal' => 'Usa como máximo 3 decimales.', 'stock_minimo.min' => 'El mínimo no puede ser negativo.']);
        $material->update(['stock_minimo' => $datos['stock_minimo'] ?? null]);
        $mensaje = $material->stock_minimo !== null ? "Aviso guardado: {$material->nombre} se marcará al llegar a ".(float) $material->stock_minimo." {$material->unidad}." : "Aviso quitado para {$material->nombre}.";

        return $request->expectsJson()
            ? response()->json(['mensaje' => $mensaje, 'stock_minimo' => $material->stock_minimo !== null ? (float) $material->stock_minimo : null])
            : redirect()->route('admin.inventario.index', ['tab' => 'materiales'])->with('estado', $mensaje);
    }
}
