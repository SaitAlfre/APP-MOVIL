<?php

namespace App\Http\Controllers\Admin;

use App\Application\Produccion\AbrirLoteProduccionUseCase;
use App\Application\Produccion\CerrarLoteProduccionUseCase;
use App\Application\Produccion\ListarLotesProduccionUseCase;
use App\Domain\Produccion\Exceptions\LoteProduccionInvalidoException;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProduccionController extends Controller
{
    public function index(ListarLotesProduccionUseCase $listar, UsuarioRepositoryInterface $usuarios): View
    {
        $lotes = $listar->ejecutar();

        $filas = collect($lotes->items())->map(fn ($lote) => [
            'lote' => $lote,
            'responsable' => $usuarios->buscarPorId($lote->responsableUsuarioId),
        ]);

        return view('admin.produccion.index', ['filas' => $filas, 'paginador' => $lotes]);
    }

    public function create(): View
    {
        return view('admin.produccion.create');
    }

    public function store(Request $request, AbrirLoteProduccionUseCase $abrir): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
            'producto' => ['required', 'string', 'max:100'],
            'litros_utilizados' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $abrir->ejecutar(
                codigo: $request->string('codigo')->toString(),
                producto: $request->string('producto')->toString(),
                litrosUtilizados: (float) $request->input('litros_utilizados'),
                responsableUsuarioId: auth('operador')->id(),
            );
        } catch (LoteProduccionInvalidoException $e) {
            return back()->withErrors(['codigo' => $e->getMessage()])->withInput();
        }

        return redirect()->route('admin.produccion.index')->with('estado', 'Lote de producción abierto correctamente.');
    }

    public function cerrar(int $lote, CerrarLoteProduccionUseCase $cerrar): RedirectResponse
    {
        try {
            $cerrar->ejecutar($lote);
        } catch (LoteProduccionInvalidoException|RuntimeException $e) {
            return back()->withErrors(['lote' => $e->getMessage()]);
        }

        return redirect()->route('admin.produccion.index')->with('estado', 'Lote cerrado correctamente.');
    }
}
