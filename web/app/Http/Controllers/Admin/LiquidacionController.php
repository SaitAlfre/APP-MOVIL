<?php

namespace App\Http\Controllers\Admin;

use App\Application\Liquidaciones\GenerarLiquidacionUseCase;
use App\Application\Liquidaciones\ListarLiquidacionesUseCase;
use App\Application\Liquidaciones\MarcarLiquidacionPagadaUseCase;
use App\Domain\Liquidaciones\Exceptions\LiquidacionInvalidaException;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class LiquidacionController extends Controller
{
    public function index(ListarLiquidacionesUseCase $listar, ProveedorRepositoryInterface $proveedores): View
    {
        $liquidaciones = $listar->ejecutar();

        $filas = collect($liquidaciones->items())->map(fn ($liquidacion) => [
            'liquidacion' => $liquidacion,
            'proveedor' => $proveedores->buscarPorId($liquidacion->proveedorId),
        ]);

        return view('admin.liquidaciones.index', ['filas' => $filas, 'paginador' => $liquidaciones]);
    }

    public function create(ProveedorRepositoryInterface $proveedores): View
    {
        return view('admin.liquidaciones.create', [
            'proveedores' => collect($proveedores->paginar(500)->items()),
        ]);
    }

    public function store(Request $request, GenerarLiquidacionUseCase $generar): RedirectResponse
    {
        $request->validate([
            'proveedor_id' => ['required', 'integer'],
            'periodo_inicio' => ['required', 'date'],
            'periodo_fin' => ['required', 'date'],
            'precio_litro' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $generar->ejecutar(
                proveedorId: $request->integer('proveedor_id'),
                periodoInicio: new DateTimeImmutable($request->string('periodo_inicio')->toString()),
                periodoFin: new DateTimeImmutable($request->string('periodo_fin')->toString()),
                precioLitro: (float) $request->input('precio_litro'),
            );
        } catch (LiquidacionInvalidaException|RuntimeException $e) {
            return back()->withErrors(['proveedor_id' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.liquidaciones.index')->with('estado', 'Liquidación generada correctamente.');
    }

    public function marcarPagada(int $liquidacion, MarcarLiquidacionPagadaUseCase $marcarPagada): RedirectResponse
    {
        try {
            $marcarPagada->ejecutar($liquidacion);
        } catch (LiquidacionInvalidaException|RuntimeException $e) {
            return back()->withErrors(['liquidacion' => $e->getMessage()]);
        }

        return redirect()->route('admin.liquidaciones.index')->with('estado', 'Liquidación marcada como pagada.');
    }
}
