<?php

namespace App\Http\Controllers\Admin;

use App\Application\Jornadas\ConsultarHojaRutaQuery;
use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Jornadas\EtapaJornada;
use App\Domain\Recepcion\RecepcionAcopioRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JornadaController extends Controller
{
    /** Acopio del día: una tarjeta por jornada con su etapa y la acción que le toca a administración. */
    public function index(Request $request, RecepcionAcopioRepositoryInterface $recepciones): View
    {
        $datos = $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d'],
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'], 'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'etapa' => ['nullable', Rule::enum(EtapaJornada::class)]]);
        $hoy = now('America/Lima')->toDateString();
        $fecha = $request->has('fecha') ? ($datos['fecha'] ?? null) : $hoy;
        $etapa = isset($datos['etapa']) ? EtapaJornada::from($datos['etapa']) : null;
        $base = Jornada::query()->when($fecha, fn ($q) => $q->whereDate('fecha', $fecha))
            ->when($datos['usuario_id'] ?? null, fn ($q, $id) => $q->where('usuario_id', $id))
            ->when($datos['zona_id'] ?? null, fn ($q, $id) => $q->where('zona_id', $id));
        $paginador = $this->conEtapa(clone $base, $etapa)->with(['usuario', 'zona', 'vehiculo'])
            ->withSum(['entregas as litros' => fn ($q) => $q->where('anulada', false)], 'litros')
            ->withCount(['entregas as recojos_count' => fn ($q) => $q->where('anulada', false)])
            ->orderByDesc('abierta_en')->paginate(24)->withQueryString();
        $recepcionesPagina = $recepciones->porJornadas($paginador->pluck('id')->all());

        return view('admin.jornadas.index', [
            'paginador' => $paginador, 'fecha' => $fecha, 'hoy' => $hoy, 'etapa' => $etapa,
            'recepciones' => $recepcionesPagina,
            'etapas' => $paginador->mapWithKeys(fn (Jornada $j) => [$j->id => EtapaJornada::de($j->cerrada_en === null, $recepcionesPagina[$j->id] ?? null)])->all(),
            'resumen' => [
                'litros' => (float) Entrega::query()->whereIn('jornada_id', (clone $base)->select('id'))->where('anulada', false)->sum('litros'),
                'enRuta' => $this->conEtapa(clone $base, EtapaJornada::EnRuta)->count(),
                'porRecibir' => $this->conEtapa(clone $base, EtapaJornada::PorRecibir)->count(),
                'diferencia' => round((float) DB::table('recepciones_acopio')->whereIn('jornada_id', (clone $base)->select('id'))
                    ->sum(DB::raw('litros_medidos - litros_recolectados')), 2),
            ],
            'pendientesAnteriores' => $this->conEtapa(Jornada::query()->whereDate('fecha', '<', $fecha ?? $hoy), EtapaJornada::PorRecibir)->count(),
            'zonas' => Zona::query()->orderBy('nombre')->get(),
            'acopiadores' => Usuario::query()->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get(),
        ]);
    }

    /**
     * @param  Builder<Jornada>  $query
     * @return Builder<Jornada>
     */
    private function conEtapa(Builder $query, ?EtapaJornada $etapa): Builder
    {
        $recepcion = fn ($sub) => $sub->select(DB::raw(1))->from('recepciones_acopio')->whereColumn('recepciones_acopio.jornada_id', 'jornadas.id');

        return match ($etapa) {
            null => $query,
            EtapaJornada::EnRuta => $query->whereNull('cerrada_en')->whereNotExists($recepcion),
            EtapaJornada::PorRecibir => $query->whereNotNull('cerrada_en')->whereNotExists($recepcion),
            EtapaJornada::RecibidaConDiferencia => $query->whereExists(fn ($sub) => $recepcion($sub)->whereColumn('litros_medidos', '!=', 'litros_recolectados')),
            EtapaJornada::RecibidaSinDiferencia => $query->whereExists(fn ($sub) => $recepcion($sub)->whereColumn('litros_medidos', '=', 'litros_recolectados')),
        };
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

    /** Detalle de jornada: recojos, recepción en planta y reclamos en una sola página. */
    public function show(int $jornada, ConsultarHojaRutaQuery $hoja, RecepcionAcopioRepositoryInterface $recepciones): View
    {
        $entidad = Jornada::query()->with(['usuario', 'zona', 'vehiculo'])->findOrFail($jornada);
        $recientes = $entidad->entregas()->with('proveedor')->latest('registrado_en')->paginate(20, ['*'], 'entregas_page')->withQueryString();
        $recepcion = $recepciones->porJornada($entidad->id);
        $cambios = Auditoria::query()->where('entidad', 'entrega')->whereIn('entidad_id', $recientes->pluck('id'))
            ->whereIn('accion', [AccionAuditoria::Corregir->value, AccionAuditoria::Anular->value])
            ->orderBy('ocurrido_en')->get()->groupBy('entidad_id');

        return view('admin.jornadas.show', ['jornada' => $entidad, 'hoja' => $hoja->ejecutar($entidad),
            'recientes' => $recientes,
            'cambios' => $cambios,
            'recepcion' => $recepcion,
            'etapa' => EtapaJornada::de($entidad->cerrada_en === null, $recepcion),
            'reclamos' => ReclamoProveedor::query()->with(['proveedor', 'entrega'])
                ->whereIn('entrega_id', $entidad->entregas()->select('id'))->latest()->get(),
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
