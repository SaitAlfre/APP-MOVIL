<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\View\View;

class AcopiadorController extends Controller
{
    public function index(): View
    {
        $acopiadores = Usuario::query()->whereJsonContains('roles', 'acopiador')->orderBy('nombres')->get();
        $jornadasActivas = Jornada::with(['zona', 'vehiculo'])->whereNull('cerrada_en')->get()->keyBy('usuario_id');

        return view('admin.acopiadores.index', compact('acopiadores', 'jornadasActivas'));
    }
}
