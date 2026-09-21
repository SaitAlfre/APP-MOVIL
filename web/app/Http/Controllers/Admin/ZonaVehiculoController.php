<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Ruta;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ZonaVehiculoController extends Controller
{
    public function index(Request $request, ZonaRepositoryInterface $zonas, VehiculoRepositoryInterface $vehiculos, ProveedorRepositoryInterface $proveedores): View
    {
        $filasZonas = collect($zonas->todas())->map(fn ($zona) => [
            'zona' => $zona,
            'proveedores' => count($proveedores->activosPorZona($zona->id)),
            'rutas' => Ruta::query()->where('zona_id', $zona->id)->count(),
        ]);

        $pestanas = ['zonas', 'rutas', 'vehiculos'];
        $pestana = $request->string('tab')->toString();

        return view('admin.zonas-vehiculos.index', [
            'filasZonas' => $filasZonas,
            'vehiculos' => $vehiculos->todos(),
            'rutas' => Ruta::with('zona')->orderBy('nombre')->get(),
            'zonasCatalogo' => Zona::where('activo', true)->orderBy('nombre')->get(),
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
        Ruta::create($datos + ['activo' => true]);

        return back()->with('estado', 'Ruta registrada.');
    }

    public function updateRuta(Request $request, Ruta $ruta): RedirectResponse
    {
        $datos = $request->validate([
            'codigo' => ['required', 'string', 'max:30', Rule::unique('rutas', 'codigo')->ignore($ruta)],
            'nombre' => ['required', 'string', 'max:120'],
            'zona_id' => ['required', 'integer', 'exists:zonas,id'],
            'activo' => ['required', 'boolean'],
        ]);
        $ruta->update($datos);

        return back()->with('estado', 'Ruta actualizada.');
    }
}
