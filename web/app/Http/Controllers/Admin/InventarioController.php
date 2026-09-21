<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\MovimientoProducto;
use App\Infrastructure\Persistence\Eloquent\Producto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.inventario.index', [
            'productos' => Producto::where('activo', true)->orderBy('nombre')->get(),
            'movimientos' => MovimientoProducto::with('producto')->latest('fecha')->paginate(20),
            'tab' => $request->string('tab')->value() === 'movimientos' ? 'movimientos' : 'resumen',
        ]);
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
