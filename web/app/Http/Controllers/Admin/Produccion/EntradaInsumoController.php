<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Inventario\RegistrarEntradaInsumoUseCase;
use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntradaInsumoController extends Controller
{
    public function create(InsumoRepositoryInterface $insumos, ProveedorRepositoryInterface $proveedores): View
    {
        return view('admin.produccion.inventario.entradas.create', [
            'insumos' => $insumos->todosActivos(),
            'proveedores' => $proveedores->paginar(200)->items(),
        ]);
    }

    public function store(Request $request, RegistrarEntradaInsumoUseCase $registrar): RedirectResponse
    {
        $request->validate([
            'insumo_id' => ['required', 'integer'],
            'proveedor_id' => ['nullable', 'integer'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'unidad' => ['required', 'string', 'max:20'],
            'fecha' => ['required', 'date'],
            'costo_unitario' => ['nullable', 'numeric', 'gte:0'],
            'costo_total' => ['nullable', 'numeric', 'gte:0'],
            'documento_referencia' => ['nullable', 'string', 'max:100'],
            'lote_origen' => ['nullable', 'string', 'max:100'],
            'vencimiento' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $registrar->ejecutar(
                insumoId: $request->integer('insumo_id'),
                proveedorId: $request->filled('proveedor_id') ? $request->integer('proveedor_id') : null,
                cantidad: (float) $request->input('cantidad'),
                unidad: $request->string('unidad')->toString(),
                fecha: new DateTimeImmutable($request->string('fecha')->toString()),
                costoUnitario: $request->filled('costo_unitario') ? (float) $request->input('costo_unitario') : null,
                costoTotal: $request->filled('costo_total') ? (float) $request->input('costo_total') : null,
                documentoReferencia: $request->string('documento_referencia')->toString() ?: null,
                loteOrigen: $request->string('lote_origen')->toString() ?: null,
                vencimiento: $request->filled('vencimiento') ? new DateTimeImmutable($request->string('vencimiento')->toString()) : null,
                observaciones: $request->string('observaciones')->toString() ?: null,
                usuarioId: auth('operador')->id(),
            );
        } catch (InsumoInvalidoException $e) {
            return back()->withErrors(['insumo_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.produccion.inventario.index')->with('estado', 'Entrada de inventario registrada correctamente.');
    }
}
