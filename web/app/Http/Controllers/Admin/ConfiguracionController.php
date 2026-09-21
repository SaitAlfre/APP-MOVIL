<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Configuracion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    private const VALORES_PREDETERMINADOS = [
        'organizacion_nombre' => 'Ecolactea Digital',
        'organizacion_ruc' => '',
        'organizacion_direccion' => 'Huata, Puno',
        'organizacion_telefono' => '',
        'moneda' => 'PEN',
        'inicio_semana' => 'jueves',
        'dia_pago' => 'miércoles',
        'precio_base_litro' => '1.80',
        'login_intentos_maximos' => '5',
        'login_bloqueo_minutos' => '15',
        'requerir_cambio_pin' => '1',
    ];

    public function index(): View
    {
        return view('admin.configuracion.index', ['configuracion' => array_replace(self::VALORES_PREDETERMINADOS, Configuracion::pluck('valor', 'clave')->all())]);
    }

    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'organizacion_nombre' => ['required', 'string', 'max:150'],
            'organizacion_ruc' => ['nullable', 'string', 'max:20'],
            'organizacion_direccion' => ['nullable', 'string', 'max:255'],
            'organizacion_telefono' => ['nullable', 'string', 'max:30'],
            'moneda' => ['required', 'in:PEN'],
            'inicio_semana' => ['required', 'in:lunes,martes,miércoles,jueves,viernes,sábado,domingo'],
            'dia_pago' => ['required', 'in:lunes,martes,miércoles,jueves,viernes,sábado,domingo'],
            'precio_base_litro' => ['required', 'numeric', 'gt:0'],
            'login_intentos_maximos' => ['required', 'integer', 'between:1,20'],
            'login_bloqueo_minutos' => ['required', 'integer', 'between:1,1440'],
            'requerir_cambio_pin' => ['nullable', 'boolean'],
        ]);
        $datos['requerir_cambio_pin'] = $request->boolean('requerir_cambio_pin') ? '1' : '0';

        foreach ($datos as $clave => $valor) {
            Configuracion::updateOrCreate(['clave' => $clave], ['valor' => (string) ($valor ?? ''), 'actualizado_por' => auth('operador')->id()]);
        }

        return back()->with('estado', 'Configuración guardada.');
    }
}
