<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Usuarios\Rol;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RolController extends Controller
{
    private const ACCIONES = ['ver', 'crear', 'editar', 'eliminar'];

    /** Categorías de la página de roles: agrupan los módulos como el menú lateral. */
    private const CATEGORIAS = [
        'Inicio y reportes' => ['icono' => 'home', 'modulos' => ['dashboard', 'reportes']],
        'Usuarios y proveedores' => ['icono' => 'usuarios', 'modulos' => ['usuarios', 'proveedores']],
        'Organización del acopio' => ['icono' => 'asignaciones', 'modulos' => ['zonas_vehiculos', 'mapa_rutas']],
        'Acopio' => ['icono' => 'milk', 'modulos' => ['acopiadores', 'entregas', 'recepcion']],
        'Calidad' => ['icono' => 'beaker', 'modulos' => ['calidad', 'sanciones']],
        'Planta' => ['icono' => 'factory', 'modulos' => ['produccion', 'productos', 'recetas', 'inventario', 'ventas']],
        'Liquidaciones y pagos' => ['icono' => 'liquidacion', 'modulos' => ['liquidaciones']],
        'Comunicación y administración' => ['icono' => 'cog', 'modulos' => ['comunicados', 'importaciones', 'configuracion', 'auditoria']],
    ];

    private const DESCRIPCIONES_SISTEMA = [
        'admin' => 'Acceso completo al panel: usuarios, roles, catálogos, acopio, planta y pagos.',
        'acopiador' => 'Recoge la leche con la app móvil y consulta sus rutas asignadas en la web.',
        'calidad' => 'Registra análisis de calidad y propone sanciones desde la web y el celular.',
        'proveedor' => 'Consulta sus entregas, calidad y pagos desde la app móvil del proveedor.',
    ];

    /** Roles a la izquierda y, a la derecha, los permisos del rol elegido agrupados por categoría. */
    public function index(Request $request): View
    {
        $perfiles = $this->listar($request);
        $rolesSistema = collect(Rol::cases())->map(fn (Rol $rol) => (object) [
            'clave' => $rol->value, 'nombre' => $rol->etiqueta(), 'descripcion' => self::DESCRIPCIONES_SISTEMA[$rol->value] ?? '',
            'usuarios' => DB::table('usuarios')->whereNull('perfil_rol_id')->whereJsonContains('roles', $rol->value)->count(),
            'permisos' => $this->permisosDeSistema($rol->value),
        ]);

        $sistema = $request->string('sistema')->toString();
        $id = $request->integer('rol') ?: (int) session('rol');
        $perfil = $id > 0 ? $this->perfilConUsuarios($id) : null;
        $seleccion = match (true) {
            $sistema !== '' && $rolesSistema->contains('clave', $sistema) => ['tipo' => 'sistema', 'rol' => $rolesSistema->firstWhere('clave', $sistema)],
            $perfil !== null => ['tipo' => 'perfil', 'rol' => $perfil],
            $perfiles->isNotEmpty() => ['tipo' => 'perfil', 'rol' => $this->perfilConUsuarios((int) $perfiles->first()->id)],
            default => ['tipo' => 'sistema', 'rol' => $rolesSistema->first()],
        };
        $permisos = $seleccion['tipo'] === 'perfil' ? (json_decode($seleccion['rol']->permisos, true) ?: []) : $seleccion['rol']->permisos;

        return view('admin.roles.index', [
            'perfiles' => $perfiles,
            'rolesSistema' => $rolesSistema,
            'seleccion' => $seleccion,
            'permisos' => $permisos,
            'categorias' => $this->categorias(),
        ]);
    }

    private function perfilConUsuarios(int $id): ?object
    {
        return DB::table('perfiles_roles')->select('perfiles_roles.*')
            ->selectSub(DB::table('usuarios')->selectRaw('count(*)')->whereColumn('perfil_rol_id', 'perfiles_roles.id'), 'usuarios_count')
            ->where('id', $id)->first();
    }

    /** @return array<string, list<string>> Permisos efectivos de un rol oficial según config/permisos.php. */
    private function permisosDeSistema(string $rol): array
    {
        return collect(config('modulos_roles'))->map(function (array $modulo, string $clave) use ($rol): array {
            $ver = $rol === 'admin' || in_array($rol, config("permisos.{$clave}.ver", []), true);
            $gestionar = $rol === 'admin' || in_array($rol, config("permisos.{$clave}.gestionar", []), true);

            return array_values(array_filter($modulo['acciones'], fn (string $accion) => $accion === 'ver' ? $ver : $gestionar));
        })->filter()->all();
    }

    /** @return list<array{nombre: string, icono: string, permisos: list<array{modulo: string, accion: string, titulo: string, descripcion: string}>}> */
    private function categorias(): array
    {
        $modulos = config('modulos_roles');
        $verbos = ['ver' => 'Ver', 'crear' => 'Crear en', 'editar' => 'Editar', 'eliminar' => 'Dar de baja en'];
        $textos = [
            'ver' => 'Consulta los listados y el detalle del módulo.',
            'crear' => 'Registra datos nuevos.',
            'editar' => 'Modifica datos existentes y cambia estados.',
        ];

        return collect(self::CATEGORIAS)->map(fn (array $categoria, string $nombre) => [
            'nombre' => $nombre, 'icono' => $categoria['icono'],
            'permisos' => collect($categoria['modulos'])->filter(fn (string $modulo) => isset($modulos[$modulo]))
                ->flatMap(fn (string $modulo) => collect($modulos[$modulo]['acciones'])->map(fn (string $accion) => [
                    'modulo' => $modulo, 'accion' => $accion,
                    'titulo' => $verbos[$accion].' '.mb_strtolower($modulos[$modulo]['nombre']),
                    'descripcion' => $accion === 'eliminar' ? $modulos[$modulo]['eliminar'] : $textos[$accion],
                ]))->values()->all(),
        ])->values()->all();
    }

    public function formularios(Request $request): View
    {
        return view('admin.roles.formularios', ['perfiles' => $this->listar($request)]);
    }

    private function listar(Request $request): LengthAwarePaginator
    {
        $datos = $request->validate(['buscar' => ['nullable', 'string', 'max:100'], 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])], 'por_pagina' => ['nullable', Rule::in([10, 20, 50])], 'page' => ['nullable', 'integer', 'min:1']]);

        return DB::table('perfiles_roles')
            ->select('perfiles_roles.*')
            ->selectSub(DB::table('usuarios')->selectRaw('count(*)')->whereColumn('perfil_rol_id', 'perfiles_roles.id'), 'usuarios_count')
            ->when(filled($datos['buscar'] ?? null), fn ($query) => $query->where('nombre', 'like', '%'.trim($datos['buscar']).'%'))
            ->when(filled($datos['estado'] ?? null), fn ($query) => $query->where('activo', $datos['estado'] === 'activo'))
            ->orderByDesc('id')->paginate((int) ($datos['por_pagina'] ?? 10))->withQueryString();
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
        $request->validate(['campos' => ['nullable', 'array', 'max:40'], 'campos.*' => ['array']]);
        $request->merge(['campos' => array_map(function (array $campo): array {
            if (is_string($campo['etiqueta'] ?? null)) {
                $campo['etiqueta'] = trim($campo['etiqueta']);
            }

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
        $modulos = config('modulos_roles');
        // Solo se muestran las columnas de acciones que algún módulo admite de verdad (hoy ninguno admite «eliminar»).
        $acciones = array_values(array_filter(self::ACCIONES, fn (string $accion) => collect($modulos)->contains(fn (array $modulo) => in_array($accion, $modulo['acciones'], true))));

        return view('admin.roles.form', ['perfil' => $perfil, 'modulos' => $modulos, 'acciones' => $acciones]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Un rol personalizado nuevo solo define permisos del panel web: no hereda funciones móviles de ningún rol oficial.
        $id = DB::table('perfiles_roles')->insertGetId($this->datos($request) + ['base' => null, 'created_at' => now()]);

        return to_route('admin.roles.index')->with(['estado' => 'Rol creado. Activa sus permisos por categoría.', 'rol' => $id]);
    }

    public function update(Request $request, int $rol): RedirectResponse
    {
        abort_unless(DB::table('perfiles_roles')->where('id', $rol)->exists(), 404);
        // La base de un rol anterior no se edita: conserva las funciones móviles de sus cuentas.
        DB::table('perfiles_roles')->where('id', $rol)->update($this->datos($request, $rol));

        return to_route('admin.roles.index')->with(['estado' => 'Rol actualizado.', 'rol' => $rol]);
    }

    public function destroy(int $rol): RedirectResponse
    {
        abort_unless(DB::table('perfiles_roles')->where('id', $rol)->exists(), 404);
        if (DB::table('usuarios')->where('perfil_rol_id', $rol)->exists()) {
            return back()->withErrors(['rol' => 'Este rol tiene usuarios. Puedes desactivarlo, pero no eliminarlo.']);
        }
        DB::table('perfiles_roles')->where('id', $rol)->delete();

        return to_route('admin.roles.index')->with('estado', 'Rol eliminado.');
    }

    private function datos(Request $request, ?int $id = null): array
    {
        if (is_string($request->input('nombre'))) {
            $request->merge(['nombre' => trim($request->input('nombre'))]);
        }
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('perfiles_roles')->ignore($id)],
            'activo' => ['required', 'boolean'],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['array'],
            'permisos.*.*' => ['string', Rule::in(self::ACCIONES)],
            'campos_texto' => ['nullable', 'string', 'max:10000'],
        ], [
            'nombre.unique' => 'Ya existe un rol con este nombre. Escribe otro nombre.',
        ]);
        $permisos = $datos['permisos'] ?? ($id && ! $request->exists('permisos_presentes') ? json_decode(DB::table('perfiles_roles')->where('id', $id)->value('permisos'), true) : []);
        foreach ($permisos as $modulo => $acciones) {
            if (! array_key_exists($modulo, config('modulos_roles'))) {
                throw ValidationException::withMessages(['permisos' => 'Selecciona un módulo válido.']);
            }
            $acciones = array_values(array_intersect($acciones, config('modulos_roles.'.$modulo.'.acciones')));
            $permisos[$modulo] = $acciones;
            if (array_diff($acciones, ['ver'])) {
                $permisos[$modulo] = array_values(array_unique([...$acciones, 'ver']));
            }
        }
        $campos = $id && ! $request->exists('campos_texto') ? json_decode(DB::table('perfiles_roles')->where('id', $id)->value('campos'), true) : [];
        foreach (preg_split('/\R/', $datos['campos_texto'] ?? '') as $linea) {
            if (trim($linea) === '') {
                continue;
            }
            $partes = explode('|', $linea);
            $clave = trim($partes[0]);
            if (! preg_match('/^[a-z][a-z0-9_]{0,39}$/', $clave) || isset($campos[$clave])) {
                throw ValidationException::withMessages(['campos_texto' => 'Cada campo necesita una clave única: letras minúsculas, números o guion bajo.']);
            }
            $tipo = trim($partes[2] ?? 'texto');
            if (! in_array($tipo, ['texto', 'numero', 'fecha'], true)) {
                throw ValidationException::withMessages(['campos_texto' => 'Tipos admitidos: texto, numero y fecha.']);
            }
            $campos[$clave] = ['etiqueta' => trim($partes[1] ?? $clave), 'tipo' => $tipo, 'obligatorio' => trim($partes[3] ?? '') === 'si'];
        }

        return ['nombre' => $datos['nombre'], 'activo' => $datos['activo'], 'permisos' => json_encode($permisos), 'campos' => json_encode($campos), 'updated_at' => now()];
    }
}
