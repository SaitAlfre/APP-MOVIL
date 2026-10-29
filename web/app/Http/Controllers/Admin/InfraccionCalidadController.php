<?php

namespace App\Http\Controllers\Admin;

use App\Application\Calidad\GestionarInfraccionCalidad;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\InfraccionCalidad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InfraccionCalidadController extends Controller
{
    public function store(Request $request, string $analisis, GestionarInfraccionCalidad $gestionar): RedirectResponse
    {
        $datos = $request->validate(['motivo' => ['required', 'string', 'min:10', 'max:1000']]);
        $registro = AnalisisCalidad::where('uuid', $analisis)->firstOrFail();
        $gestionar->registrar($registro, auth('operador')->user(), $datos['motivo']);

        return redirect()->route('admin.calidad.show', $analisis)->with('estado', 'Caso registrado para revisión administrativa. No se ha aplicado ningún castigo.');
    }

    public function resolver(Request $request, InfraccionCalidad $incidencia, GestionarInfraccionCalidad $gestionar): RedirectResponse
    {
        abort_unless(auth('operador')->user()->tieneRol('admin'), 403);
        $datos = $request->validate([
            'decision' => ['required', Rule::in(['confirmar', 'descartar'])],
            'respuesta' => ['required', 'string', 'min:10', 'max:1000'],
            'descuento_litro' => ['nullable', 'required_if:decision,confirmar', 'numeric', 'between:0.20,0.50', 'decimal:0,2'],
        ]);
        $resuelta = $gestionar->resolver($incidencia, auth('operador')->user(), $datos['decision'], $datos['respuesta'], isset($datos['descuento_litro']) ? (float) $datos['descuento_litro'] : null);

        return redirect()->route('admin.calidad.show', $resuelta->analisis->uuid)->with('estado', $resuelta->estado === 'confirmada'
            ? 'Infracción confirmada. Castigo semanal registrado y avisos publicados en la app; la publicación no acredita su lectura.'
            : 'Caso descartado. No cuenta como infracción ni genera castigo o avisos.');
    }
}
