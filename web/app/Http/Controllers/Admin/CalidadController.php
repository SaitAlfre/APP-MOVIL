<?php

namespace App\Http\Controllers\Admin;

use App\Application\Calidad\GestionarInfraccionCalidad;
use App\Application\Calidad\GuardarAnalisisCalidadUseCase;
use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Calidad\ParametrosCalidad;
use App\Domain\Proveedores\EstadoProveedor;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\InfraccionCalidad;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * Control de calidad igual al de la app móvil: análisis LactoScan por proveedor (11 parámetros con sus
 * referencias), historial con filtros y detalle. Los análisis del celular y del panel son los mismos
 * registros (se sincronizan en ambos sentidos por `uuid`).
 */
class CalidadController extends Controller
{
    public function casos(Request $request): View
    {
        $request->merge(['tab' => 'incidencias']);

        return $this->index($request);
    }

    public function index(Request $request): View
    {
        $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
            'tecnico' => ['nullable', 'integer', 'exists:usuarios,id'],
            'zona' => ['nullable', 'integer', 'exists:zonas,id'],
            'resultado' => ['nullable', 'string', 'in:aprobado,observado,rechazado,repetir,APROBADO,OBSERVADO,RECHAZADO,REPETIR'],
            'q' => ['nullable', 'string', 'max:180'],
        ]);
        $filtro = strtoupper($request->string('resultado')->toString());
        $estado = EstadoAnalisis::tryFrom($filtro);
        $busqueda = trim($request->string('q')->toString());
        $zonaId = $request->integer('zona') ?: null;
        $tecnicoId = $request->integer('tecnico') ?: null;
        $desde = $request->filled('desde') ? $request->string('desde')->toString() : null;
        $hasta = $request->filled('hasta') ? $request->string('hasta')->toString() : null;

        $base = AnalisisCalidad::query()
            ->when($tecnicoId !== null, fn ($q) => $q->where('usuario_id', $tecnicoId))
            ->when($desde !== null, fn ($q) => $q->where('registrado_en', '>=', CarbonImmutable::parse($desde, 'America/Lima')->startOfDay()->utc()))
            ->when($hasta !== null, fn ($q) => $q->where('registrado_en', '<=', CarbonImmutable::parse($hasta, 'America/Lima')->endOfDay()->utc()))
            ->when($zonaId !== null, fn ($q) => $q->whereHas('proveedor', fn ($p) => $p->where('zona_id', $zonaId)))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($w) => $w->where('codigo_muestra', 'like', "%{$busqueda}%")
                ->orWhereHas('proveedor', fn ($p) => $p->where('nombres', 'like', "%{$busqueda}%")->orWhere('codigo', 'like', "%{$busqueda}%"))));
        $consulta = (clone $base)->with(['proveedor.zona', 'usuario'])
            ->when($estado !== null, fn ($q) => $q->where('estado', $estado))->latest('registrado_en');

        $conteos = (clone $base)->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $hoy = now('America/Lima');
        $pestana = in_array($request->input('tab'), ['reglas', 'incidencias'], true) ? $request->input('tab') : 'analisis';
        $incidencias = InfraccionCalidad::query()->whereIn('analisis_calidad_id', (clone $consulta)->reorder()->select('id'));

        return view('admin.calidad.index', [
            'analisis' => $consulta->paginate(15)->withQueryString(),
            'conteos' => [
                'aprobado' => (int) ($conteos[EstadoAnalisis::Aprobado->value] ?? 0),
                'observado' => (int) ($conteos[EstadoAnalisis::Observado->value] ?? 0),
                'repetir' => (int) ($conteos[EstadoAnalisis::Repetir->value] ?? 0),
                'rechazado' => (int) ($conteos[EstadoAnalisis::Rechazado->value] ?? 0),
            ],
            'deHoy' => (clone $base)->whereBetween('registrado_en', [$hoy->copy()->startOfDay()->utc(), $hoy->copy()->endOfDay()->utc()])->count(),
            'desde' => $desde, 'hasta' => $hasta, 'tecnicoActual' => $tecnicoId,
            'tecnicos' => Usuario::whereIn('id', AnalisisCalidad::select('usuario_id'))->orderBy('nombres')->pluck('nombres', 'id')->all(),
            'incidencias' => (clone $incidencias)->with(['proveedor.zona', 'zona', 'analisis', 'registradoPor', 'confirmadoPor'])->latest()->paginate(15, ['*'], 'casos')->withQueryString(),
            'resumenIncidencias' => [
                'pendientes' => (clone $incidencias)->where('estado', 'pendiente')->count(),
                'confirmadas' => (clone $incidencias)->where('estado', 'confirmada')->count(),
                'retirados' => (clone $incidencias)->where('estado', 'confirmada')->where('numero_infraccion', '>=', 3)
                    ->whereHas('proveedor', fn ($q) => $q->where('estado', 'retirado'))->distinct()->count('proveedor_id'),
            ],
            'zonas' => Zona::query()->orderBy('nombre')->pluck('nombre', 'id')->all(),
            'filtroActual' => $estado,
            'busqueda' => $busqueda,
            'zonaActual' => $zonaId,
            'pestana' => $pestana,
        ]);
    }

    public function create(): View
    {
        $ahora = now('America/Lima');

        return view('admin.calidad.create', [
            'zonas' => Zona::query()->where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
            'proveedores' => Proveedor::query()->where('estado', EstadoProveedor::Activo)->orderBy('nombres')->get(['id', 'codigo', 'nombres', 'zona_id']),
            'fecha' => $ahora->toDateString(),
            'hora' => $ahora->format('H:i'),
        ]);
    }

    public function store(Request $request, GuardarAnalisisCalidadUseCase $guardar): RedirectResponse
    {
        $reglas = [
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'fecha' => ['required', 'date_format:Y-m-d'],
            'hora' => ['required', 'date_format:H:i'],
            'serial' => ['nullable', 'string', 'max:60'],
            'modo' => ['nullable', 'string', 'max:60'],
            'unidad_congelacion' => ['required', 'in:°C,°H'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
        // Como en la app: se acepta coma o punto decimal.
        $request->merge(['valores' => array_map(fn ($v) => is_string($v) ? str_replace(',', '.', trim($v)) : $v, (array) $request->input('valores', []))]);
        $valores = $request->input('valores', []);
        if (isset($valores['congelacion']) && is_numeric($valores['congelacion'])) {
            $valores['congelacion'] = ParametrosCalidad::normalizar('congelacion', (float) $valores['congelacion']);
            $request->merge(['valores' => $valores]);
        }
        foreach (array_keys(ParametrosCalidad::PARAMETROS) as $clave) {
            $reglas["valores.{$clave}"] = ['nullable', 'numeric', ParametrosCalidad::permiteNegativo($clave) ? 'between:-5,5' : 'min:0'];
        }
        $datos = $request->validate($reglas, [
            'valores.*.numeric' => 'Usa solo números, con punto decimal.',
            'valores.*.min' => 'El valor no puede ser negativo.',
        ]);

        $proveedor = Proveedor::query()->findOrFail($datos['proveedor_id']);
        if ($proveedor->estado !== EstadoProveedor::Activo) {
            return back()->withErrors(['proveedor_id' => 'El proveedor ya no está activo.'])->withInput();
        }

        try {
            $analisis = $guardar->ejecutar([
                'proveedor_id' => $proveedor->id,
                'registrado_en' => CarbonImmutable::createFromFormat('Y-m-d H:i', $datos['fecha'].' '.$datos['hora'], 'America/Lima')->utc(),
                'valores' => collect($datos['valores'] ?? [])->map(fn ($v) => $v === null || $v === '' ? null : (float) $v)->all(),
                'unidad_congelacion' => $datos['unidad_congelacion'],
                'serial' => $datos['serial'] ?? null,
                'modo' => $datos['modo'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ], auth('operador')->user());
        } catch (InvalidArgumentException|RuntimeException|QueryException $e) {
            return back()->withErrors(['valores' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.calidad.show', $analisis->uuid)
            ->with('estado', "Análisis {$analisis->codigo_muestra} guardado: {$analisis->estado->etiqueta()}.");
    }

    public function show(string $analisis): View
    {
        $registro = AnalisisCalidad::query()->with(['proveedor.zona', 'usuario', 'controles.entrega', 'controles.sanciones'])->where('uuid', $analisis)->firstOrFail();
        $incidencia = InfraccionCalidad::with(['zona', 'registradoPor', 'confirmadoPor'])->where('analisis_calidad_id', $registro->id)->first();

        return view('admin.calidad.show', [
            'analisis' => $registro,
            'incidencia' => $incidencia,
            'infraccionesConfirmadas' => InfraccionCalidad::where('proveedor_id', $registro->proveedor_id)->where('estado', 'confirmada')->count(),
            'semanaCastigo' => $incidencia?->semana_inicio && $incidencia?->semana_fin
                ? ['inicio' => $incidencia->semana_inicio->toDateString(), 'fin' => $incidencia->semana_fin->toDateString()]
                : GestionarInfraccionCalidad::semana($registro),
            'puedeResolverIncidencias' => auth('operador')->user()->tieneRol('admin'),
        ]);
    }
}
