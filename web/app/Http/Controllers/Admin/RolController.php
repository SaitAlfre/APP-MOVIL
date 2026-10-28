<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Usuarios\Rol;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RolController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', ['perfiles' => DB::table('perfiles_roles')->orderByDesc('id')->get()]);
    }

    public function formularios(): View
    {
        return view('admin.roles.formularios', ['perfiles' => DB::table('perfiles_roles')->orderByDesc('id')->get()]);
    }

    public function editarFormulario(int $rol): View
    {
        $perfil = DB::table('perfiles_roles')->find($rol);
        abort_unless($perfil, 404);

        $camposEnUso = $this->camposEnUso($rol);

        return view('admin.roles.formulario', ['perfil' => $perfil, 'camposEnUso' => $camposEnUso]);
    }

    public function guardarFormulario(Request $request, int $rol): RedirectResponse
    {
        $perfil = DB::table('perfiles_roles')->find($rol);
        abort_unless($perfil, 404);
        $request->merge(['campos' => array_map(function (array $campo): array {
            $campo['etiqueta'] = trim($campo['etiqueta'] ?? '');

            return $campo;
        }, array_filter((array) $request->input('campos', []), 'is_array'))]);
        $datos = $request->validate([
            'campos' => ['array', 'max:40'],
            'campos.*.clave' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_]{0,39}$/', 'distinct'],
            'campos.*.etiqueta' => ['required', 'string', 'max:100'],
            'campos.*.tipo' => ['required', Rule::in(['texto', 'numero', 'fecha'])],
            'campos.*.obligatorio' => ['nullable', 'boolean'],
        ], [
            'campos.*.etiqueta.required' => 'Escribe el nombre de este campo.',
            'campos.*.etiqueta.max' => 'El nombre puede tener hasta 100 caracteres.',
            'campos.*.clave.distinct' => 'Hay dos campos con el mismo identificador. Vuelve a agregar uno de ellos.',
            'campos.max' => 'Puedes agregar hasta 40 campos adicionales.',
        ]);
        $anteriores = json_decode($perfil->campos, true);
        $enUso = $this->camposEnUso($rol);
        $campos = [];
        foreach ($datos['campos'] as $indice => $campo) {
            $clave = $campo['clave'] ?? null;
            if (! $clave) {
                $base = substr(str_replace('-', '_', Str::slug($campo['etiqueta'])), 0, 30);
                if (! preg_match('/^[a-z]/', $base)) {
                    $base = 'campo_'.$base;
                }
                $clave = $base;
                $sufijo = 2;
                while (isset($campos[$clave]) || isset($anteriores[$clave]) || in_array($clave, $enUso, true) || in_array($clave, array_column($datos['campos'], 'clave'), true)) {
                    $clave = $base.'_'.($sufijo++);
                }
            }
            if (isset($campos[$clave])) {
                throw ValidationException::withMessages(['campos.'.$indice.'.etiqueta' => 'Este campo está repetido.']);
            }
            if (in_array($clave, $enUso, true) && isset($anteriores[$clave]) && $anteriores[$clave]['tipo'] !== $campo['tipo']) {
                throw ValidationException::withMessages(['campos.'.$indice.'.tipo' => 'Este campo ya tiene datos. Conserva su tipo y agrega otro campo si necesitas un formato diferente.']);
            }
            $campos[$clave] = ['etiqueta' => $campo['etiqueta'], 'tipo' => $campo['tipo'], 'obligatorio' => (bool) ($campo['obligatorio'] ?? false)];
        }
        DB::table('perfiles_roles')->where('id', $rol)->update(['campos' => json_encode($campos), 'updated_at' => now()]);

        return to_route('admin.formularios.index')->with('estado', 'Formulario actualizado. Los datos registrados se conservan.');
    }

    private function camposEnUso(int $rol): array
    {
        $claves = [];
        foreach (DB::table('usuarios')->where('perfil_rol_id', $rol)->whereNotNull('datos_formulario')->select('datos_formulario')->cursor() as $usuario) {
            foreach (json_decode($usuario->datos_formulario, true) ?? [] as $clave => $valor) {
                if ($valor !== null && $valor !== '') {
                    $claves[$clave] = true;
                }
            }
        }

        return array_keys($claves);
    }

    public function create(): View
    {
        return $this->formulario(null);
    }

    public function edit(int $rol): View
    {
        $perfil = DB::table('perfiles_roles')->find($rol);
        abort_unless($perfil, 404);

        return $this->formulario($perfil);
    }

    private function formulario(?object $perfil): View
    {
        return view('admin.roles.form', ['perfil' => $perfil, 'bases' => array_values(array_filter(Rol::cases(), fn (Rol $rol) => $rol !== Rol::Admin)), 'modulos' => array_keys(config('permisos'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        DB::table('perfiles_roles')->insert($this->datos($request) + ['created_at' => now()]);

        return to_route('admin.roles.index')->with('estado', 'Rol creado.');
    }

    public function update(Request $request, int $rol): RedirectResponse
    {
        abort_unless(DB::table('perfiles_roles')->where('id', $rol)->exists(), 404);
        $datos = $this->datos($request, $rol);
        $anterior = DB::table('perfiles_roles')->find($rol);
        abort_if($anterior->base !== $datos['base'] && DB::table('usuarios')->where('perfil_rol_id', $rol)->exists(), 422, 'No cambies el perfil base de un rol asignado.');
        DB::table('perfiles_roles')->where('id', $rol)->update($datos);

        return to_route('admin.roles.index')->with('estado', 'Rol actualizado.');
    }

    public function destroy(int $rol): RedirectResponse
    {
        abort_unless(DB::table('perfiles_roles')->where('id', $rol)->exists(), 404);
        if (DB::table('usuarios')->where('perfil_rol_id', $rol)->exists()) {
            return back()->withErrors(['rol' => 'Este rol tiene usuarios. Puedes desactivarlo, pero no eliminarlo.']);
        } DB::table('perfiles_roles')->where('id', $rol)->delete();

        return to_route('admin.roles.index')->with('estado', 'Rol eliminado.');
    }

    private function datos(Request $request, ?int $id = null): array
    {
        $datos = $request->validate(['nombre' => ['required', 'string', 'max:100', Rule::unique('perfiles_roles')->ignore($id)], 'base' => ['required', Rule::in(array_map(fn (Rol $r) => $r->value, array_filter(Rol::cases(), fn (Rol $rol) => $rol !== Rol::Admin)))], 'activo' => ['required', 'boolean'], 'permisos' => ['nullable', 'array'], 'permisos.*' => ['array'], 'permisos.*.*' => [Rule::in(['ver', 'crear', 'editar', 'eliminar'])], 'campos_texto' => ['nullable', 'string', 'max:10000']]);
        $permisos = $datos['permisos'] ?? [];
        foreach ($permisos as $modulo => $acciones) {
            abort_unless(array_key_exists($modulo, config('permisos')), 422);
            if (array_diff($acciones, ['ver'])) {
                $permisos[$modulo] = array_values(array_unique([...$acciones, 'ver']));
            }
        }
        $campos = $id && ! $request->exists('campos_texto') ? json_decode(DB::table('perfiles_roles')->where('id', $id)->value('campos'), true) : [];
        foreach (preg_split('/\R/', $datos['campos_texto'] ?? '') as $linea) {
            if (trim($linea) === '') {
                continue;
            } $partes = explode('|', $linea);
            $clave = trim($partes[0]);
            if (! preg_match('/^[a-z][a-z0-9_]{0,39}$/', $clave) || isset($campos[$clave])) {
                throw ValidationException::withMessages(['campos_texto' => 'Cada campo necesita una clave única: letras minúsculas, números o guion bajo.']);
            } $tipo = trim($partes[2] ?? 'texto');
            if (! in_array($tipo, ['texto', 'numero', 'fecha'], true)) {
                throw ValidationException::withMessages(['campos_texto' => 'Tipos admitidos: texto, numero y fecha.']);
            } $campos[$clave] = ['etiqueta' => trim($partes[1] ?? $clave), 'tipo' => $tipo, 'obligatorio' => trim($partes[3] ?? '') === 'si'];
        }

        return ['nombre' => $datos['nombre'], 'base' => $datos['base'], 'activo' => $datos['activo'], 'permisos' => json_encode($permisos), 'campos' => json_encode($campos), 'updated_at' => now()];
    }
}
