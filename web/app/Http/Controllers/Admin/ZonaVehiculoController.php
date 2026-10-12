<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ZonaVehiculoController extends Controller
{
    public function index(Request $request, ZonaRepositoryInterface $zonas, VehiculoRepositoryInterface $vehiculos, ProveedorRepositoryInterface $proveedores): View|RedirectResponse
    {
        if ($request->string('tab')->toString() === 'rutas') {
            return redirect()->route('admin.mapa-rutas.index');
        }
        $filasZonas = collect($zonas->todas())->map(fn ($zona) => [
            'zona' => $zona,
            'proveedores' => count($proveedores->activosPorZona($zona->id)),
            'rutas' => Ruta::query()->where('zona_id', $zona->id)->count(),
        ]);

        $pestanas = ['zonas', 'vehiculos'];
        $pestana = $request->string('tab')->toString();

        return view('admin.zonas-vehiculos.index', [
            'filasZonas' => $filasZonas,
            'vehiculos' => $vehiculos->todos(),
            'pestana' => in_array($pestana, $pestanas, true) ? $pestana : 'zonas',
        ]);
    }

    public function storeRuta(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', 'unique:rutas,codigo'],
            'nombre' => ['required', 'string', 'max:120'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
        ]);
        $ruta = Ruta::create($datos + ['activo' => true]);

        return redirect()->route('admin.mapa-rutas.show', $ruta)->with('estado', 'Ruta registrada. Marca sus paradas y asigna un acopiador.');
    }

    public function updateRuta(Request $request, Ruta $ruta): RedirectResponse
    {
        $ruta = Ruta::whereKey($ruta->id)->lockForUpdate()->firstOrFail();
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('rutas', 'codigo')->ignore($ruta)],
            'nombre' => ['required', 'string', 'max:120'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'activo' => ['required', 'boolean'],
        ]);
        if ((int) $datos['zona_id'] !== (int) $ruta->zona_id && (count($ruta->paradas ?? []) > 0 || count($ruta->trazado ?? []) > 0)) {
            throw ValidationException::withMessages(['zona_id' => 'La ruta tiene un recorrido guardado. Crea otra ruta para el nuevo sector o elimina primero sus paradas y trazado.']);
        }
        $ruta->update($datos);

        return back()->with('estado', 'Ruta actualizada.');
    }
}
