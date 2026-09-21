<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ComunicadoController extends Controller
{
    public function index(): View
    {
        Comunicado::where('estado', 'programado')->where('publicar_en', '<=', now())->update(['estado' => 'publicado', 'publicado_en' => now()]);

        return view('admin.comunicados.index', ['comunicados' => Comunicado::latest()->paginate(20)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $siguiente = ((int) Comunicado::max('id')) + 1;
        $datos['codigo'] = 'COM-'.str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT);
        $datos['autor_id'] = auth('operador')->id();
        $datos['publicado_en'] = $datos['estado'] === 'publicado' ? now() : null;
        Comunicado::create($datos);

        return back()->with('estado', 'Comunicado guardado.');
    }

    public function update(Request $request, Comunicado $comunicado): RedirectResponse
    {
        $datos = $this->validar($request);
        $datos['publicado_en'] = $datos['estado'] === 'publicado' ? ($comunicado->publicado_en ?? now()) : null;
        $comunicado->update($datos);

        return back()->with('estado', 'Comunicado actualizado.');
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:180'],
            'contenido' => ['required', 'string', 'max:5000'],
            'audiencia' => ['required', Rule::in(['todos', 'proveedores', 'acopiadores', 'calidad', 'produccion'])],
            'estado' => ['required', Rule::in(['borrador', 'programado', 'publicado'])],
            'publicar_en' => ['nullable', 'date', 'required_if:estado,programado'],
        ]);
    }
}
