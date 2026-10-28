<?php

namespace App\Http\Controllers\Admin;

use App\Application\Calidad\CalificarEntregasConAnalisis;
use App\Application\Movil\AplicarCambioMovilUseCase;
use App\Application\Movil\ResolverReclamoProveedorUseCase;
use App\Domain\Movil\CambioMovilRechazadoException;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Sancion;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SancionController extends Controller
{
    public function index(): View
    {
        $this->crearPropuestasDesdeCalidad();

        return view('admin.sanciones.index', [
            'solicitudesTraslado' => SolicitudTraslado::with(['proveedor', 'destino'])->latest()->paginate(15, ['*'], 'traslados'),
            'sanciones' => Sancion::with(['proveedor', 'controlCalidad.entrega'])->latest()->paginate(15, ['*'], 'sanciones'),
            'reclamos' => ReclamoProveedor::with(['proveedor', 'entrega'])->latest()->paginate(15, ['*'], 'reclamos'),
            'entregas' => Entrega::with('proveedor')->where('anulada', false)->latest('registrado_en')->limit(100)->get(),
        ]);
    }

    public function resolver(Request $request, Sancion $sancion): RedirectResponse
    {
        $datos = $request->validate(['estado' => ['required', Rule::in(['aprobada', 'rechazada'])]]);
        abort_if($sancion->estado !== 'pendiente', 409, 'La sanción ya fue resuelta.');
        $sancion->update(['estado' => $datos['estado'], 'resuelto_por' => auth('operador')->id(), 'resuelto_en' => now()]);

        return back()->with('estado', 'Propuesta de sanción '.$datos['estado'].'.');
    }

    public function resolverReclamo(Request $request, ReclamoProveedor $reclamo, ResolverReclamoProveedorUseCase $resolver): RedirectResponse
    {
        abort_unless(auth('operador')->user()->tieneRol('admin'), 403, 'Solo administración puede resolver reclamos de volumen.');
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['resuelto', 'rechazado'])],
            'respuesta' => ['required', 'string', 'max:500'],
            'entrega_id' => ['nullable', 'integer', 'exists:entregas,id'],
        ]);
        try {
            $resolver->ejecutar($reclamo->id, auth('operador')->user(), $datos['estado'], $datos['respuesta'], $datos['entrega_id'] ?? null);
        } catch (CambioMovilRechazadoException $e) {
            return back()->withErrors(['reclamo' => $e->getMessage()])->withInput();
        }

        return back()->with('estado', 'Reclamo actualizado.');
    }

    public function evidencia(ReclamoProveedor $reclamo): Response
    {
        abort_unless($reclamo->evidencia !== null, 404);
        $contenido = base64_decode(substr($reclamo->evidencia, strpos($reclamo->evidencia, ',') + 1), true);
        $info = $contenido !== false ? @getimagesizefromstring($contenido) : false;
        abort_unless($info !== false && in_array($info['mime'], ['image/jpeg', 'image/png'], true), 404);

        return response($contenido, 200, [
            'Content-Type' => $info['mime'], 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function resolverTraslado(Request $request, SolicitudTraslado $traslado, AplicarCambioMovilUseCase $aplicar): RedirectResponse
    {
        $autor = auth('operador')->user();
        abort_unless($autor->tieneRol('admin'), 403);
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['APROBADA', 'RECHAZADA'])],
            'respuesta' => ['required', 'string', 'max:500'],
        ]);
        $traslado->load(['proveedor', 'destino']);
        try {
            $aplicar->ejecutar($autor, 'traslado', [
                'uuid' => $traslado->uuid_movil, 'proveedorId' => $traslado->proveedor_id,
                'proveedorCodigo' => $traslado->proveedor->codigo, 'zonaDestinoId' => $traslado->zona_destino_id,
                'zonaDestinoNombre' => $traslado->destino->nombre, 'motivo' => $traslado->motivo, 'estado' => $datos['estado'], 'respuesta' => $datos['respuesta'],
            ]);
        } catch (CambioMovilRechazadoException $e) {
            return back()->withErrors(['traslado' => $e->getMessage()])->withInput();
        }

        return back()->with('estado', 'Solicitud de traslado actualizada. El proveedor recibirá el resultado al sincronizar.');
    }

    private function crearPropuestasDesdeCalidad(): void
    {
        ControlCalidad::query()->with('entrega')->whereIn('resultado', ['observado', 'rechazado'])->get()
            ->each(fn (ControlCalidad $control) => CalificarEntregasConAnalisis::sincronizarSancion($control));
    }
}
