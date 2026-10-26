<?php

namespace App\Http\Controllers\Admin;

use App\Application\Jornadas\ConsultarHojaRutaQuery;
use App\Domain\Recepcion\RecepcionAcopioRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JornadaController extends Controller
{
    public function index(Request $request, RecepcionAcopioRepositoryInterface $recepciones): View
    {
        $datos = $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'], 'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'estado' => ['nullable', 'in:abierta,cerrada']]);
        $fecha = $request->has('fecha') ? ($datos['fecha'] ?? null) : now('America/Lima')->toDateString();
        $query = Jornada::query()->when($fecha, fn ($q) => $q->whereDate('fecha', $fecha))
            ->when($datos['usuario_id'] ?? null, fn ($q, $id) => $q->where('usuario_id', $id))
            ->when($datos['zona_id'] ?? null, fn ($q, $id) => $q->where('zona_id', $id))
            ->when($datos['estado'] ?? null, fn ($q, $estado) => $estado === 'abierta' ? $q->whereNull('cerrada_en') : $q->whereNotNull('cerrada_en'));
        $paginador = (clone $query)->with(['usuario', 'zona', 'vehiculo'])
            ->withSum(['entregas as litros' => fn ($q) => $q->where('anulada', false)], 'litros')
            ->withCount(['entregas as recojos_count' => fn ($q) => $q->where('anulada', false)])
            ->orderByDesc('abierta_en')->paginate(20)->withQueryString();

        return view('admin.jornadas.index', [
            'paginador' => $paginador, 'fecha' => $fecha, 'total' => (clone $query)->count(),
            'abiertas' => (clone $query)->whereNull('cerrada_en')->count(),
            'recepciones' => $recepciones->porJornadas($paginador->pluck('id')->all()),
            'zonas' => Zona::query()->orderBy('nombre')->get(),
            'acopiadores' => Usuario::query()->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.jornadas.create', [
            'acopiadores' => Usuario::query()->where('activo', true)->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get()->all(),
            'zonas' => Zona::query()->where('activo', true)->orderBy('nombre')->get()->all(),
            'vehiculos' => Vehiculo::query()->where('activo', true)->orderBy('nombre')->get()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate(['usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'zona_id' => ['required', 'integer', Rule::exists('zonas', 'id')->where('activo', true)],
            'vehiculo_id' => ['required', 'integer', Rule::exists('vehiculos', 'id')->where('activo', true)]]);

        return DB::transaction(function () use ($datos): RedirectResponse {
            $usuario = Usuario::query()->lockForUpdate()->findOrFail($datos['usuario_id']);
            if (! $usuario->activo || ! $usuario->tieneRol('acopiador') || $usuario->estaBloqueada()) {
                return back()->withErrors(['usuario_id' => 'Selecciona un acopiador activo y habilitado.'])->withInput();
            }
            $existente = Jornada::query()->where('usuario_id', $usuario->id)->whereNull('cerrada_en')->orderBy('abierta_en')->first();
            if ($existente !== null) {
                return redirect()->route('admin.acopiadores.jornadas.show', $existente)->with('estado', 'El acopiador ya tiene una jornada abierta. Se conserva su zona y vehículo.');
            }
            $cerrada = Jornada::query()->where('usuario_id', $usuario->id)->whereDate('fecha', now('America/Lima')->toDateString())->first();
            if ($cerrada !== null) {
                return redirect()->route('admin.acopiadores.jornadas.show', $cerrada)->with('estado', 'La jornada de hoy ya terminó. Puedes revisar sus registros.');
            }
            $jornada = Jornada::query()->create([...$datos, 'fecha' => now('America/Lima')->toDateString(), 'abierta_en' => now(), 'seguimiento_activo' => false]);

            return redirect()->route('admin.acopiadores.jornadas.show', $jornada)->with('estado', 'Jornada iniciada. El acopiador la recibirá al sincronizar.');
        });
    }

    public function show(int $jornada, ConsultarHojaRutaQuery $hoja): View
    {
        $entidad = Jornada::query()->with(['usuario', 'zona', 'vehiculo'])->findOrFail($jornada);

        return view('admin.jornadas.show', ['jornada' => $entidad, 'hoja' => $hoja->ejecutar($entidad),
            'recientes' => $entidad->entregas()->with('proveedor')->latest('registrado_en')->paginate(20, ['*'], 'entregas_page')->withQueryString(),
            'litros' => (float) $entidad->entregas()->where('anulada', false)->sum('litros'),
            'entregas' => $entidad->entregas()->where('anulada', false)->count(),
            'proveedores' => Proveedor::query()->where('zona_id', $entidad->zona_id)->where('estado', 'activo')->orderBy('nombres')->get(),
        ]);
    }

    public function cerrar(Request $request, int $jornada): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:255']]);
        $registro = Jornada::query()->lockForUpdate()->findOrFail($jornada);
        if ($registro->cerrada_en === null) {
            $registro->update(['cerrada_en' => now(), 'seguimiento_activo' => false]);
            Auditoria::query()->create(['entidad' => 'jornada', 'entidad_id' => $registro->id, 'accion' => 'cerrar_jornada',
                'motivo' => $datos['motivo'], 'usuario_id' => auth('operador')->id(), 'ocurrido_en' => now(),
                'valor_antes' => 'cerradaEn=null', 'valor_despues' => 'Cerrada desde administración']);
        }

        return redirect()->route('admin.acopiadores.jornadas.show', $registro)->with('estado', 'Jornada terminada. Los registros recibidos se conservan.');
    }
}
