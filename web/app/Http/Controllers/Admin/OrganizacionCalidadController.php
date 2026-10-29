<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\InfraccionCalidad;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrganizacionCalidadController extends Controller
{
    public function resumen(): View
    {
        return view('admin.calidad.resumen', [
            'recibidos' => AnalisisCalidad::count(),
            'pendientes' => InfraccionCalidad::where('estado', 'pendiente')->count(),
            'personal' => Usuario::whereJsonContains('roles', 'calidad')->where('activo', true)->count(),
            'zonas' => DB::table('analisis_calidad as a')->join('proveedores as p', 'p.id', '=', 'a.proveedor_id')
                ->join('zonas as z', 'z.id', '=', 'p.zona_id')->select('z.id', 'z.nombre')
                ->selectRaw('COUNT(*) as controles, COUNT(DISTINCT p.id) as proveedores')->groupBy('z.id', 'z.nombre')->orderBy('z.nombre')->get(),
        ]);
    }

    public function personal(): View
    {
        return view('admin.calidad.personal', [
            'trabajadores' => Usuario::whereJsonContains('roles', 'calidad')->orderBy('nombres')->paginate(20),
            'actividad' => AnalisisCalidad::select('usuario_id')->selectRaw('COUNT(*) as controles, COUNT(DISTINCT proveedor_id) as proveedores, MAX(created_at) as ultima_recepcion')->groupBy('usuario_id')->get()->keyBy('usuario_id'),
            'zonas' => Zona::pluck('nombre', 'id'),
        ]);
    }

    private function autorizarPlan(Request $request): void
    {
        $usuario = $request->user('operador');
        abort_unless($usuario->perfil_rol_id === null && ($usuario->tieneRol('admin') || ($usuario->tieneRol('calidad') && ! $usuario->tieneRol('acopiador') && ! $usuario->tieneRol('proveedor'))), 403);
    }

    public function planes(Request $request): View
    {
        $this->autorizarPlan($request);
        $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d']]);
        $fecha = $request->input('fecha') ?: now('America/Lima')->toDateString();
        $admin = $request->user('operador')->tieneRol('admin');

        return view('admin.calidad.planes', [
            'fecha' => $fecha, 'admin' => $admin,
            'planes' => DB::table('planes_control_calidad as p')->join('usuarios as u', 'u.id', '=', 'p.usuario_id')
                ->join('zonas as z', 'z.id', '=', 'p.zona_id')->join('proveedores as pr', 'pr.id', '=', 'p.proveedor_id')
                ->where('p.fecha', $fecha)->when(! $admin, fn ($q) => $q->where('p.usuario_id', $request->user('operador')->id))
                ->select('p.*', 'u.nombres as trabajador', 'z.nombre as zona', 'pr.nombres as proveedor')->orderBy('p.id')->paginate(20)->withQueryString(),
            'trabajadores' => $admin ? Usuario::whereJsonContains('roles', 'calidad')->where('activo', true)->orderBy('nombres')->pluck('nombres', 'id')->all() : [],
            'zonas' => $admin ? Zona::where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all() : [],
            'proveedores' => $admin ? Proveedor::where('estado', 'activo')->orderBy('nombres')->get()->mapWithKeys(fn ($p) => [$p->id => $p->codigo.' · '.$p->nombres])->all() : [],
        ]);
    }

    public function guardarPlan(Request $request): RedirectResponse
    {
        $this->autorizarPlan($request);
        abort_unless($request->user('operador')->tieneRol('admin'), 403);
        $datos = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('America/Lima')->toDateString()],
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
            'zona_id' => ['required', 'integer', Rule::exists('zonas', 'id')->where('activo', true)],
            'proveedor_id' => ['required', 'integer', Rule::exists('proveedores', 'id')->where('zona_id', $request->input('zona_id'))->where('estado', 'activo')],
            'lugar' => ['required', Rule::in(['campo', 'planta'])],
            'motivo' => ['required', Rule::in(['sorpresa', 'repeticion', 'seguimiento'])],
        ]);
        $tecnico = Usuario::findOrFail($datos['usuario_id']);
        if (! $tecnico->tieneRol('calidad') || ! $tecnico->activo || $tecnico->estaBloqueada() || $tecnico->tieneRol('acopiador') || $tecnico->tieneRol('proveedor') || $tecnico->perfil_rol_id !== null) {
            throw ValidationException::withMessages(['usuario_id' => 'Selecciona una cuenta activa de Calidad sin rol de acopiador o proveedor.']);
        }
        try {
            DB::table('planes_control_calidad')->insert([...$datos, 'creado_por' => $request->user('operador')->id, 'created_at' => now(), 'updated_at' => now()]);
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages(['proveedor_id' => 'Este trabajador ya tiene un control para ese proveedor y fecha.']);
        }

        return redirect()->route('admin.calidad.planes', ['fecha' => $datos['fecha']])->with('exito', 'Control programado. No se enviaron avisos al proveedor ni al acopiador.');
    }

    public function actualizarPlan(Request $request, int $plan): RedirectResponse
    {
        $this->autorizarPlan($request);
        abort_unless($request->user('operador')->tieneRol('admin'), 403);
        $datos = $request->validate([
            'estado' => ['required', Rule::in(['programado', 'en_ejecucion', 'completado', 'cancelado'])],
            'observacion' => ['nullable', 'required_if:estado,cancelado', 'string', 'max:1000'],
        ]);
        $registro = DB::table('planes_control_calidad')->find($plan);
        abort_if($registro === null, 404);
        DB::table('planes_control_calidad')->where('id', $plan)->update([...$datos, 'actualizado_por' => $request->user('operador')->id, 'updated_at' => now()]);

        return redirect()->route('admin.calidad.planes', ['fecha' => $registro->fecha])->with('exito', 'Estado del control actualizado.');
    }
}
