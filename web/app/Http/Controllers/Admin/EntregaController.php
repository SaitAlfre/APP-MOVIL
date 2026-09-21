<?php

namespace App\Http\Controllers\Admin;

use App\Application\Entregas\RegistrarEntregaUseCase;
use App\Domain\Entregas\Exceptions\EntregaInvalidaException;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class EntregaController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:120'],
            'fecha' => ['nullable', 'date'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'estado' => ['nullable', 'string', 'in:registrada,anulada'],
        ]);

        $filtrada = fn ($query) => $query
            ->when($request->filled('buscar'), function ($interno) use ($request) {
                $termino = '%'.$request->string('buscar').'%';

                $interno->where(function ($condiciones) use ($termino) {
                    $condiciones->whereHas('proveedor', fn ($proveedores) => $proveedores->where('nombres', 'like', $termino)->orWhere('codigo', 'like', $termino))
                        ->orWhereHas('jornada.usuario', fn ($usuarios) => $usuarios->where('nombres', 'like', $termino));
                });
            })
            ->when($request->filled('fecha'), fn ($interno) => $interno->whereDate('registrado_en', $request->date('fecha')))
            ->when($request->filled('zona_id'), fn ($interno) => $interno->where('zona_id', $request->integer('zona_id')))
            ->when($request->filled('estado'), fn ($interno) => $interno->where('anulada', $request->string('estado')->toString() === 'anulada'));

        $entregas = $filtrada(Entrega::query()->with(['proveedor.zona', 'jornada.usuario']))
            ->latest('registrado_en')
            ->paginate(20)
            ->withQueryString();

        return view('admin.entregas.index', [
            'entregas' => $entregas,
            'litrosFiltrados' => (float) $filtrada(Entrega::query())->where('anulada', false)->sum('litros'),
            'litrosHoy' => (float) Entrega::whereDate('registrado_en', today())->where('anulada', false)->sum('litros'),
            'entregasHoy' => Entrega::whereDate('registrado_en', today())->where('anulada', false)->count(),
            'jornadas' => Jornada::with(['zona', 'usuario'])->whereNull('cerrada_en')->latest('abierta_en')->get(),
            'proveedores' => Proveedor::with('zona')->where('estado', 'activo')->orderBy('nombres')->get(),
            'zonasDisponibles' => Zona::query()->orderBy('nombre')->get(),
        ]);
    }

    public function storeBatch(Request $request, RegistrarEntregaUseCase $registrar): RedirectResponse
    {
        $request->merge([
            'entregas' => collect($request->input('entregas', []))
                ->filter(fn (array $fila) => filled($fila['proveedor_id'] ?? null) || filled($fila['litros'] ?? null) || filled($fila['tachos'] ?? null))
                ->values()->all(),
        ]);
        $datos = $request->validate([
            'jornada_id' => ['required', 'integer', 'exists:jornadas,id'],
            'entregas' => ['required', 'array', 'min:1', 'max:50'],
            'entregas.*.proveedor_id' => ['required', 'integer', 'distinct', 'exists:proveedores,id'],
            'entregas.*.litros' => ['required', 'numeric', 'gt:0'],
            'entregas.*.tachos' => ['required', 'integer', 'min:1'],
        ]);
        $jornada = Jornada::whereKey($datos['jornada_id'])->whereNull('cerrada_en')->firstOrFail();
        $loteId = (string) Str::uuid();

        try {
            DB::transaction(function () use ($datos, $jornada, $loteId, $registrar): void {
                foreach ($datos['entregas'] as $fila) {
                    $registrar->ejecutar($jornada->id, (int) $fila['proveedor_id'], $jornada->usuario_id, $jornada->zona_id, $jornada->vehiculo_id, (float) $fila['litros'], (int) $fila['tachos'], 'Registro por lote desde el panel web.', $loteId);
                }
            });
        } catch (EntregaInvalidaException|RuntimeException $exception) {
            return back()->withErrors(['entregas' => $this->mensajeSeguro($exception)])->withInput();
        }

        return redirect()->route('admin.entregas.index')->with('estado', count($datos['entregas']).' entregas registradas en el lote.');
    }
}
