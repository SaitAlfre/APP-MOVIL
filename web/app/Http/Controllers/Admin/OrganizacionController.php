<?php

namespace App\Http\Controllers\Admin;

use App\Application\Movil\AplicarCambioMovilUseCase;
use App\Application\Proveedores\TrasladoZonaProveedor;
use App\Domain\Movil\CambioMovilRechazadoException;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Organización del acopio: quién recoge, con qué vehículo, en qué zona y en qué orden. */
class OrganizacionController extends Controller
{
    public function index(TrasladoZonaProveedor $traslados): View
    {
        $traslados->aplicarProgramados();
        $zonas = Zona::query()->where('activo', true)->orderBy('nombre')->get();
        $rutas = Ruta::query()->with('acopiador')->whereIn('zona_id', $zonas->pluck('id'))->orderBy('nombre')->get();
        $proveedores = Proveedor::query()->where('estado', 'activo')->whereIn('zona_id', $zonas->pluck('id'))
            ->orderBy('nombres')->get(['id', 'codigo', 'nombres', 'zona_id', 'latitud', 'longitud', 'ubicacion_origen']);
        $enRuta = $rutas->where('activo', true)->flatMap(fn (Ruta $ruta) => collect($ruta->paradas ?? [])->pluck('proveedor_id'))->filter()->unique();
        $acopiadores = Usuario::query()->where('activo', true)->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get(['id', 'nombres', 'zona_id']);
        $trasladosPendientes = SolicitudTraslado::query()->where('estado', 'PENDIENTE')->count();

        $resumen = $zonas->map(function (Zona $zona) use ($rutas, $proveedores, $enRuta, $acopiadores): array {
            $suyos = $proveedores->where('zona_id', $zona->id);
            $rutasZona = $rutas->where('zona_id', $zona->id)->values();

            return [
                'zona' => $zona,
                'proveedores' => $suyos->count(),
                'ubicados' => $suyos->whereNotNull('latitud')->count(),
                'enRuta' => $suyos->whereIn('id', $enRuta)->count(),
                'rutas' => $rutasZona,
                'acopiadores' => $rutasZona->pluck('acopiador.nombres')->filter()
                    ->merge($acopiadores->where('zona_id', $zona->id)->pluck('nombres'))->unique()->values(),
            ];
        });

        return view('admin.organizacion.index', [
            'resumen' => $resumen,
            'pendientes' => [
                'sinUbicacion' => $proveedores->whereNull('latitud')->values(),
                'sinRuta' => $proveedores->whereNotIn('id', $enRuta)->values(),
                'rutasSinAcopiador' => $rutas->where('activo', true)->whereNull('acopiador_id')->values(),
                'rutasSinCamino' => $rutas->where('activo', true)->filter(fn (Ruta $ruta) => ($ruta->resumen_recorrido['fuente'] ?? null) === null && count($ruta->paradas ?? []) > 1)->values(),
                'traslados' => $trasladosPendientes,
            ],
            'zonasPorId' => $zonas->pluck('nombre', 'id'),
        ]);
    }

    public function traslados(Request $request, TrasladoZonaProveedor $traslados): View
    {
        $traslados->aplicarProgramados();
        $datos = $request->validate(['estado' => ['nullable', Rule::in(['pendiente', 'programado', 'aplicado', 'rechazado'])]]);
        $estado = $datos['estado'] ?? 'pendiente';
        $filtro = fn ($query, string $clave) => match ($clave) {
            'pendiente' => $query->where('estado', 'PENDIENTE'),
            'programado' => $query->where('estado', 'APROBADA')->whereNull('aplicado_en'),
            'aplicado' => $query->where('estado', 'APROBADA')->whereNotNull('aplicado_en'),
            'rechazado' => $query->where('estado', 'RECHAZADA'),
        };

        return view('admin.organizacion.traslados', [
            'estado' => $estado,
            'conteos' => collect(['pendiente', 'programado', 'aplicado', 'rechazado'])
                ->mapWithKeys(fn (string $clave) => [$clave => $filtro(SolicitudTraslado::query(), $clave)->count()])->all(),
            'solicitudes' => $filtro(SolicitudTraslado::query()->with(['proveedor.zona', 'destino']), $estado)
                ->orderBy($estado === 'pendiente' ? 'created_at' : 'updated_at', $estado === 'pendiente' ? 'asc' : 'desc')->paginate(20)->withQueryString(),
        ]);
    }

    public function resolverTraslado(Request $request, SolicitudTraslado $traslado, AplicarCambioMovilUseCase $aplicar): RedirectResponse
    {
        $autor = auth('operador')->user();
        abort_unless($autor->tieneRol('admin'), 403, 'Solo administración puede autorizar traslados de zona.');
        $minima = TrasladoZonaProveedor::fechaMinima($traslado);
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['APROBADA', 'RECHAZADA'])],
            'respuesta' => ['required', 'string', 'max:500'],
            'fecha_efectiva' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:'.$minima->toDateString()],
        ], ['fecha_efectiva.after_or_equal' => 'El traslado necesita 3 días de aviso: elige el '.$minima->format('d/m/Y').' o una fecha posterior.']);
        $traslado->load(['proveedor', 'destino']);

        try {
            $aplicar->ejecutar($autor, 'traslado', [
                'uuid' => $traslado->uuid_movil, 'proveedorId' => $traslado->proveedor_id,
                'proveedorCodigo' => $traslado->proveedor->codigo, 'zonaDestinoId' => $traslado->zona_destino_id,
                'zonaDestinoNombre' => $traslado->destino->nombre, 'motivo' => $traslado->motivo,
                'estado' => $datos['estado'], 'respuesta' => $datos['respuesta'], 'fechaEfectiva' => $datos['fecha_efectiva'] ?? null,
            ]);
        } catch (CambioMovilRechazadoException $e) {
            return back()->withErrors(['traslado' => $e->getMessage()])->withInput();
        }
        $traslado->refresh();
        $mensaje = match (true) {
            $traslado->estado === 'RECHAZADA' => 'Traslado rechazado. El proveedor verá la respuesta al sincronizar.',
            $traslado->aplicado_en !== null => 'Traslado aplicado: el proveedor ya pertenece a '.$traslado->destino->nombre.'.',
            default => 'Traslado aprobado. Se aplicará el '.$traslado->fecha_efectiva->format('d/m/Y').'; hasta entonces sigue en su ruta actual.',
        };

        return back()->with('estado', $mensaje);
    }
}
