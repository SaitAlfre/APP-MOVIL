<?php

namespace App\Http\Controllers\Admin;

use App\Application\Produccion\ListarDiasConSaldoDisponibleUseCase;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Material;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto;
use App\Infrastructure\Persistence\Eloquent\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request, ListarDiasConSaldoDisponibleUseCase $leche): View
    {
        return view('admin.inventario.index', [
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'movimientos' => MovimientoProducto::with('producto')->latest('fecha')->paginate(20),
            'tab' => in_array($request->input('tab'), ['movimientos', 'materiales', 'movimientos-materiales']) ? $request->input('tab') : 'resumen',
            'materiales' => Material::orderBy('nombre')->get(),
            'lecheDisponible' => $leche->ejecutar(),
            'movimientosMateriales' => DB::table('movimientos_material as movimiento')
                ->join('materiales as material', 'material.id', '=', 'movimiento.material_id')
                ->orderByDesc('movimiento.id')->select('movimiento.*', 'material.nombre', 'material.unidad')->paginate(20, ['*'], 'material_page'),
        ]);
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:materiales,nombre', 'not_regex:/^leche$/iu'],
            'unidad' => ['required', Rule::in(['kg', 'g', 'L', 'mL', 'unidad'])],
        ], ['nombre.not_regex' => 'La leche ya se administra desde el acopio.']);
        Material::create($datos);

        return redirect()->route('admin.inventario.index', ['tab' => 'materiales'])->with('estado', 'Material creado. Registra una entrada para cargar su stock.');
    }

    public function storeMovimientoMaterial(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'material_id' => ['required', 'integer', 'exists:materiales,id'],
            'operacion' => ['required', Rule::in(['entrada', 'salida'])],
            'cantidad' => ['required', 'numeric', 'min:0.001', 'max:999999999', 'decimal:0,3'],
            'motivo' => ['required', 'string', 'max:200'],
        ]);
        DB::transaction(function () use ($datos): void {
            $material = Material::whereKey($datos['material_id'])->lockForUpdate()->firstOrFail();
            $cantidad = (float) $datos['cantidad'] * ($datos['operacion'] === 'salida' ? -1 : 1);
            if (round((float) $material->existencia + $cantidad, 3) < 0) {
                throw ValidationException::withMessages(['cantidad' => 'Stock insuficiente de '.$material->nombre.'. Disponible: '.$material->existencia.' '.$material->unidad.'.']);
            }
            $material->increment('existencia', $cantidad);
            DB::table('movimientos_material')->insert([
                'material_id' => $material->id, 'usuario_id' => auth('operador')->id(),
                'tipo' => $datos['operacion'], 'cantidad' => $cantidad, 'motivo' => $datos['motivo'], 'fecha' => now(),
            ]);
        });

        return redirect()->route('admin.inventario.index', ['tab' => 'materiales'])->with('estado', 'Movimiento del material registrado.');
    }

    public function storeAjuste(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'operacion' => ['required', Rule::in(['entrada', 'salida'])],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($datos): void {
            $producto = Producto::lockForUpdate()->findOrFail($datos['producto_id']);
            $cantidad = (float) $datos['cantidad'] * ($datos['operacion'] === 'salida' ? -1 : 1);
            abort_if((float) $producto->existencia + $cantidad < 0, 422, 'El ajuste dejaría el inventario en negativo.');
            $producto->increment('existencia', $cantidad);
            MovimientoProducto::create([
                'producto_id' => $producto->id, 'tipo' => 'ajuste', 'cantidad' => $cantidad,
                'unidad' => $producto->unidad_produccion, 'motivo' => ucfirst($datos['operacion']).': '.$datos['motivo'],
                'usuario_id' => auth('operador')->id(), 'fecha' => now(),
            ]);
        });

        return back()->with('estado', 'Ajuste de inventario registrado.');
    }
}
