<?php

namespace App\Http\Controllers\Admin;

use App\Application\Auditoria\ListarAuditoriaUseCase;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(ListarAuditoriaUseCase $listar, UsuarioRepositoryInterface $usuarios): View
    {
        $registros = $listar->ejecutar();

        $filas = collect($registros->items())->map(fn ($registro) => [
            'registro' => $registro,
            'usuario' => $usuarios->buscarPorId($registro->usuarioId),
        ]);

        return view('admin.auditoria.index', [
            'filas' => $filas,
            'paginador' => $registros,
        ]);
    }
}
