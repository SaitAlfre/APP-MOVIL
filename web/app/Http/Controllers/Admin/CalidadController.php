<?php

namespace App\Http\Controllers\Admin;

use App\Application\Calidad\GuardarAnalisisCalidadUseCase;
use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Calidad\ParametrosCalidad;
use App\Domain\Proveedores\EstadoProveedor;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
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
    public function index(Request $request): View
    {
        $filtro = strtoupper($request->string('resultado')->toString());
        $estado = EstadoAnalisis::tryFrom($filtro);
        $busqueda = trim($request->string('q')->toString());
        $zonaId = $request->integer('zona') ?: null;

        $consulta = AnalisisCalidad::query()->with(['proveedor.zona', 'usuario'])
            ->when($estado === EstadoAnalisis::Observado, fn ($q) => $q->whereIn('estado', [EstadoAnalisis::Observado, EstadoAnalisis::Repetir]))
            ->when($estado !== null && $estado !== EstadoAnalisis::Observado, fn ($q) => $q->where('estado', $estado))
            ->when($zonaId !== null, fn ($q) => $q->whereHas('proveedor', fn ($p) => $p->where('zona_id', $zonaId)))
            ->when($busqueda !== '', fn ($q) => $q->where(fn ($w) => $w->where('codigo_muestra', 'like', "%{$busqueda}%")
                ->orWhereHas('proveedor', fn ($p) => $p->where('nombres', 'like', "%{$busqueda}%")->orWhere('codigo', 'like', "%{$busqueda}%"))))
            ->latest('registrado_en');

        $conteos = AnalisisCalidad::query()->selectRaw('estado, COUNT(*) as total')->groupBy('estado')->pluck('total', 'estado');
        $hoy = now('America/Lima');
        $pestana = $request->string('tab')->toString() === 'reglas' ? 'reglas' : 'analisis';

        return view('admin.calidad.index', [
            'analisis' => $consulta->paginate(15)->withQueryString(),
            'conteos' => [
                'aprobado' => (int) ($conteos[EstadoAnalisis::Aprobado->value] ?? 0),
                'observado' => (int) ($conteos[EstadoAnalisis::Observado->value] ?? 0) + (int) ($conteos[EstadoAnalisis::Repetir->value] ?? 0),
                'rechazado' => (int) ($conteos[EstadoAnalisis::Rechazado->value] ?? 0),
            ],
            'deHoy' => AnalisisCalidad::query()->whereBetween('registrado_en', [$hoy->copy()->startOfDay()->utc(), $hoy->copy()->endOfDay()->utc()])->count(),
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
        $registro = AnalisisCalidad::query()->with(['proveedor.zona', 'usuario', 'controles.entrega'])->where('uuid', $analisis)->firstOrFail();

        return view('admin.calidad.show', ['analisis' => $registro]);
    }
}
