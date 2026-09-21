<?php

namespace App\Http\Controllers\Admin;

use App\Application\Auditoria\ListarAuditoriaUseCase;
use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request, ListarAuditoriaUseCase $listar): View
    {
        $filtros = $request->validate([
            'usuario_id' => ['nullable', 'integer', 'exists:usuarios,id'],
            'entidad' => ['nullable', 'string', 'max:80'],
            'accion' => ['nullable', Rule::enum(AccionAuditoria::class)],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', ...($request->filled('desde') ? ['after_or_equal:desde'] : [])],
        ]);
        $registros = $listar->ejecutar(filtros: $filtros);
        $usuarios = Usuario::query()->whereIn('id', collect($registros->items())->pluck('usuarioId'))->get(['id', 'nombres'])->keyBy('id');

        $filas = collect($registros->items())->map(fn ($registro) => [
            'registro' => $registro,
            'usuario' => $usuarios->get($registro->usuarioId),
        ]);

        return view('admin.auditoria.index', [
            'filas' => $filas,
            'paginador' => $registros,
            'filtros' => $filtros,
            'acciones' => AccionAuditoria::cases(),
            'entidades' => Auditoria::query()->distinct()->orderBy('entidad')->pluck('entidad'),
            'usuariosFiltro' => Usuario::query()->whereIn('id', Auditoria::query()->select('usuario_id'))->orderBy('nombres')->get(['id', 'nombres']),
        ]);
    }

    public function show(int $auditoria, AuditoriaRepositoryInterface $auditorias, UsuarioRepositoryInterface $usuarios): View
    {
        $registro = $auditorias->buscarPorId($auditoria);
        abort_if($registro === null, 404);

        return view('admin.auditoria.show', [
            'registro' => $registro,
            'usuario' => $usuarios->buscarPorId($registro->usuarioId),
        ]);
    }
}
