<?php

namespace App\Http\Controllers\Acopiador;

use App\Application\Entregas\ItemLote;
use App\Application\Entregas\RegistrarLoteUseCase;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use App\Domain\Jornadas\Jornada;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class LoteController extends Controller
{
    public function create(Request $request, ProveedorRepositoryInterface $proveedores): View
    {
        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');

        return view('acopiador.lote.create', [
            'proveedores' => $proveedores->activosPorZona($jornada->zonaId),
        ]);
    }

    public function store(Request $request, RegistrarLoteUseCase $registrarLote): RedirectResponse
    {
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.proveedor_id' => ['required', 'integer'],
            'items.*.litros' => ['required', 'numeric', 'gt:0'],
            'items.*.tachos' => ['required', 'integer', 'min:1'],
        ]);

        /** @var Jornada $jornada */
        $jornada = $request->attributes->get('jornada');

        $items = array_map(
            fn (array $fila) => new ItemLote(
                proveedorId: (int) $fila['proveedor_id'],
                litros: (float) $fila['litros'],
                tachos: (int) $fila['tachos'],
            ),
            $request->array('items'),
        );

        try {
            $registrarLote->ejecutar($jornada->id, auth('operador')->id(), $jornada->zonaId, $jornada->vehiculoId, $items);
        } catch (EntregaInvalidaException|RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return redirect()->route('acopiador.home')->with('estado', 'Lote registrado correctamente.');
    }
}
