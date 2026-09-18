<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Application\Inventario\AjustarInsumoUseCase;
use App\Application\Inventario\CrearInsumoUseCase;
use App\Domain\Inventario\EntradaInsumoRepositoryInterface;
use App\Domain\Inventario\Exceptions\InsumoInvalidoException;
use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class InsumoController extends Controller
{
    public function create(): View
    {
        return view('admin.produccion.inventario.insumos.create');
    }

    public function store(Request $request, CrearInsumoUseCase $crear): RedirectResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'unidad' => ['required', 'string', 'max:20'],
            'stock_minimo' => ['nullable', 'numeric', 'gte:0'],
        ]);

        try {
            $crear->ejecutar(
                nombre: $request->string('nombre')->toString(),
                unidad: $request->string('unidad')->toString(),
                stockMinimo: (float) $request->input('stock_minimo', 0),
            );
        } catch (InsumoInvalidoException $e) {
            return back()->withErrors(['nombre' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.produccion.inventario.index')->with('estado', 'Insumo creado correctamente.');
    }

    public function show(int $insumo, InsumoRepositoryInterface $insumos, EntradaInsumoRepositoryInterface $entradas): View
    {
        $insumoDominio = $insumos->buscarPorId($insumo);

        abort_if($insumoDominio === null, 404);

        return view('admin.produccion.inventario.insumos.show', [
            'insumo' => $insumoDominio,
            'entradas' => $entradas->paginarPorInsumo($insumo, 10),
            'historial' => $entradas->historialPorInsumo($insumo, 15),
        ]);
    }

    public function ajustar(int $insumo, Request $request, AjustarInsumoUseCase $ajustar): RedirectResponse
    {
        $request->validate([
            'delta' => ['required', 'numeric', 'not_in:0'],
            'motivo' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $ajustar->ejecutar(
                insumoId: $insumo,
                delta: (float) $request->input('delta'),
                motivo: $request->string('motivo')->toString(),
                usuarioId: auth('operador')->id(),
                observaciones: $request->string('observaciones')->toString() ?: null,
            );
        } catch (InsumoInvalidoException|RuntimeException $e) {
            return back()->withErrors(['delta' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.produccion.inventario.insumos.show', $insumo)->with('estado', 'Ajuste registrado correctamente.');
    }
}
