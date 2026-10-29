<?php

namespace App\Http\Controllers\Admin;

use App\Application\Liquidaciones\CalcularImporteTemporadas;
use App\Application\Proveedores\ActorRegistroProveedor;
use App\Application\Proveedores\ActualizarProveedorUseCase;
use App\Application\Proveedores\AnalisisRegistroProveedor;
use App\Application\Proveedores\AnalizarRegistroProveedorUseCase;
use App\Application\Proveedores\CambiarEstadoProveedorUseCase;
use App\Application\Proveedores\ConsultarDniUseCase;
use App\Application\Proveedores\CredencialesCuentaMovil;
use App\Application\Proveedores\DesvincularUsuarioProveedorUseCase;
use App\Application\Proveedores\ListarProveedoresUseCase;
use App\Application\Proveedores\RegistrarProveedorUseCase;
use App\Application\Proveedores\SituacionRegistroProveedor;
use App\Application\Proveedores\VincularUsuarioProveedorUseCase;
use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Calidad\ControlCalidadRepositoryInterface;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Liquidaciones\LiquidacionRepositoryInterface;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Proveedores\Exceptions\ProveedorInvalidoException;
use App\Domain\Proveedores\Exceptions\RegistroProveedorException;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProveedorRequest;
use App\Http\Requests\Admin\UpdateProveedorRequest;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use DateTimeImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    /** Primero los registros internos (fichas y cuentas); RENIEC solo si el DNI no existe en el sistema. */
    public function consultarDni(Request $request, ConsultarDniUseCase $consultar, ProveedorRepositoryInterface $proveedores, AnalizarRegistroProveedorUseCase $analizar): JsonResponse
    {
        $datos = $request->validate(['dni' => ['required', 'string', 'regex:/^[0-9]{8}$/'], 'excluir' => ['nullable', 'integer', 'min:1']]);
        if (! empty($datos['excluir'])) {
            $existente = $proveedores->buscarPorDni($datos['dni']);
            $resultado = $existente !== null && $existente->id !== (int) $datos['excluir']
                ? ['estado' => 'registrado', 'mensaje' => 'Este documento ya tiene una ficha de proveedor.', 'url' => route('admin.proveedores.show', $existente->id)]
                : $consultar->ejecutar($datos['dni']);

            return response()->json($resultado)->header('Cache-Control', 'no-store');
        }
        $analisis = $analizar->ejecutar($datos['dni']);
        $resultado = $analisis->situacion === SituacionRegistroProveedor::Nuevo
            ? $consultar->ejecutar($datos['dni'])
            : ['estado' => 'registrado', 'mensaje' => 'Este DNI ya está en los registros internos; no se consultó RENIEC. Revisa el aviso del registro.', 'registro' => $this->describirRegistro($analisis)];

        return response()->json($resultado)->header('Cache-Control', 'no-store');
    }

    public function comprobarDuplicados(Request $request, ProveedorRepositoryInterface $proveedores, AnalizarRegistroProveedorUseCase $analizar): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => ['nullable', 'string', 'max:50'],
            'dni' => ['nullable', 'string', 'max:20'],
            'excluir' => ['nullable', 'integer', 'min:1'],
        ]);
        $coincidencias = [];

        foreach (['codigo' => 'buscarPorCodigo', 'dni' => 'buscarPorDni'] as $campo => $metodo) {
            $valor = trim($datos[$campo] ?? '');
            $existente = $valor !== '' ? $proveedores->{$metodo}($valor) : null;

            if ($existente !== null && $existente->id !== (int) ($datos['excluir'] ?? 0)) {
                $coincidencias[$campo] = [
                    'nombre' => $existente->nombres,
                    'url' => route('admin.proveedores.show', $existente->id),
                ];
            }
        }

        $dni = trim($datos['dni'] ?? '');
        $registro = empty($datos['excluir']) && preg_match('/\A[0-9]{8}\z/', $dni) ? $this->describirRegistro($analizar->ejecutar($dni)) : null;

        return response()->json(['coincidencias' => (object) $coincidencias, 'registro' => $registro])->header('Cache-Control', 'no-store');
    }

    /**
     * Resumen para el formulario unificado; solo expone nombre, usuario y enlaces, nunca credenciales.
     *
     * `datos` permite recuperar en el formulario la información ya registrada, solo cuando el registro puede continuar.
     *
     * @return array{situacion: string, mensaje: string, accion: ?string, etiqueta_accion: ?string, bloquea: bool, ficha: ?array{codigo: string, nombre: string, url: string}, cuenta: ?array{username: string, nombre: string, url: ?string}, datos: ?array{nombres: string, telefono: ?string, zona_id: ?int}}
     */
    private function describirRegistro(AnalisisRegistroProveedor $analisis): array
    {
        $operador = auth('operador')->user();
        $ficha = $analisis->ficha;
        $cuenta = $analisis->cuenta;

        return [
            'situacion' => $analisis->situacion->value,
            'mensaje' => $analisis->situacion->mensaje(),
            'accion' => $analisis->situacion->accionConfirmable(),
            'etiqueta_accion' => $analisis->situacion->etiquetaAccion(),
            'bloquea' => $analisis->situacion->bloqueaRegistro(),
            'ficha' => $ficha ? ['codigo' => $ficha->codigo, 'nombre' => $ficha->nombres, 'url' => route('admin.proveedores.show', $ficha->id)] : null,
            'cuenta' => $cuenta ? ['username' => $cuenta->username, 'nombre' => $cuenta->nombres, 'url' => $operador->puede('usuarios', 'gestionar') && $this->esAdministradorOficial() ? route('admin.usuarios.edit', $cuenta->id) : null] : null,
            'datos' => $analisis->situacion->bloqueaRegistro() || ($ficha === null && $cuenta === null) ? null : [
                'nombres' => $ficha?->nombres ?? $cuenta->nombres,
                'telefono' => $ficha?->telefono,
                'zona_id' => $ficha?->zonaId,
            ],
        ];
    }

    public function index(
        Request $request,
        ListarProveedoresUseCase $listar,
        ZonaRepositoryInterface $zonas,
        ProveedorRepositoryInterface $proveedores,
        EntregaRepositoryInterface $entregas,
        UsuarioRepositoryInterface $usuarios,
    ): View {
        $validados = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'zona_id' => ['nullable', 'integer', 'exists:zonas,id'],
            'estado' => ['nullable', 'string', 'in:activo,suspendido,retirado'],
            'por_pagina' => ['nullable', 'integer', 'in:10,20,50'],
            'vista' => ['nullable', 'string', 'in:tabla,tarjetas'],
            'page' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ]);

        $busqueda = $validados['q'] ?? null;
        $zonaId = isset($validados['zona_id']) ? (int) $validados['zona_id'] : null;
        $estado = isset($validados['estado']) ? EstadoProveedor::from($validados['estado']) : null;

        $paginador = $listar->ejecutar((int) ($validados['por_pagina'] ?? 20), $busqueda, $zonaId, $estado);
        $vista = $validados['vista'] ?? 'tabla';

        // Litros y última entrega de la semana en curso, resueltos en una sola consulta.
        $inicioSemana = new DateTimeImmutable('monday this week', new \DateTimeZone('America/Lima'));
        $hoy = new DateTimeImmutable('today', new \DateTimeZone('America/Lima'));
        $ids = collect($paginador->items())->map(fn ($proveedor) => $proveedor->id)->filter()->values()->all();
        $resumenSemana = $vista === 'tabla' ? $entregas->resumenPorProveedores($ids, $inicioSemana, $hoy) : [];

        return view('admin.proveedores.index', [
            'vista' => $vista,
            'proveedores' => $paginador,
            'zonas' => collect($zonas->todas())->keyBy('id'),
            'zonasDisponibles' => $zonas->todas(),
            'resumenSemana' => $resumenSemana,
            'cuentas' => collect($usuarios->conRol(Rol::Proveedor))->keyBy('id'),
            'busqueda' => $busqueda,
            'zonaId' => $zonaId,
            'estadoFiltro' => $estado,
        ]);
    }

    public function show(
        int $proveedor,
        Request $request,
        ProveedorRepositoryInterface $proveedores,
        ZonaRepositoryInterface $zonas,
        EntregaRepositoryInterface $entregas,
        ControlCalidadRepositoryInterface $controles,
        LiquidacionRepositoryInterface $liquidaciones,
        AuditoriaRepositoryInterface $auditoria,
        UsuarioRepositoryInterface $usuarios,
    ): View {
        $entidad = $proveedores->buscarPorId($proveedor);
        abort_if($entidad === null, 404);

        $pestanas = ['resumen', 'entregas', 'calidad', 'liquidaciones', 'auditoria'];
        $pestana = $request->string('tab')->toString();
        $operador = auth('operador')->user();
        $puedeVerCalidad = $operador->puede('calidad');
        $puedeVerLiquidaciones = $operador->puede('liquidaciones');
        $puedeVerAuditoria = $operador->puede('auditoria');
        $pestanasPermitidas = ['resumen', 'entregas'];
        foreach (['calidad' => $puedeVerCalidad, 'liquidaciones' => $puedeVerLiquidaciones, 'auditoria' => $puedeVerAuditoria] as $nombre => $permitida) {
            if ($permitida) {
                $pestanasPermitidas[] = $nombre;
            }
        }
        abort_if(in_array($pestana, $pestanas, true) && ! in_array($pestana, $pestanasPermitidas, true), 403);
        $pestana = in_array($pestana, $pestanas, true) ? $pestana : 'resumen';

        $inicioSemana = new DateTimeImmutable('monday this week', new \DateTimeZone('America/Lima'));
        $hoy = new DateTimeImmutable('today', new \DateTimeZone('America/Lima'));
        $litrosSemana = $entregas->litrosPorProveedorEnRango($entidad->id, $inicioSemana, $hoy);
        $tarifaVigente = null;
        $precioLitro = 0.0;
        $totalEstimado = null;
        if ($puedeVerLiquidaciones) {
            $tarifaVigente = TarifaLeche::whereDate('desde', '<=', $hoy->format('Y-m-d'))->whereDate('hasta', '>=', $hoy->format('Y-m-d'))->first();
            $precioLitro = (float) ($tarifaVigente?->precio ?? 0);
            $calcular = app(CalcularImporteTemporadas::class);
            try {
                $totalEstimado = $calcular->calcular($entidad->id, $inicioSemana, $hoy, $calcular->tarifas($inicioSemana, $hoy))['bruto'];
            } catch (\RuntimeException) {
                $totalEstimado = null;
            }
        }

        $ultimas = $entregas->ultimasDelProveedor($entidad->id, 20);
        $controlesPorEntrega = $puedeVerCalidad ? collect($ultimas)
            ->mapWithKeys(fn ($entrega) => [$entrega->id => $controles->buscarPorEntregaId($entrega->id)])
            ->all() : [];

        return view('admin.proveedores.show', [
            'proveedor' => $entidad,
            'zona' => $zonas->buscarPorId($entidad->zonaId),
            'cuenta' => $entidad->usuarioId !== null ? $usuarios->buscarPorId($entidad->usuarioId) : null,
            'pestana' => $pestana,
            'pestanasPermitidas' => $pestanasPermitidas,
            'puedeVerCalidad' => $puedeVerCalidad,
            'puedeVerLiquidaciones' => $puedeVerLiquidaciones,
            'litrosSemana' => $litrosSemana,
            'precioLitro' => $precioLitro, 'tarifaVigente' => $tarifaVigente, 'totalEstimado' => $totalEstimado,
            'entregas' => $ultimas,
            'controles' => $controlesPorEntrega,
            'liquidaciones' => $puedeVerLiquidaciones && $pestana === 'liquidaciones' ? $liquidaciones->ultimasDelProveedor($entidad->id, 10) : [],
            'auditoria' => $puedeVerAuditoria && $pestana === 'auditoria' ? $auditoria->paginar(10, ['entidad' => 'proveedor', 'entidad_id' => $entidad->id]) : null,
        ]);
    }

    public function create(Request $request, ZonaRepositoryInterface $zonas): View
    {
        $datos = $request->validate([
            'cuenta' => ['nullable', 'boolean'],
            'origen' => ['nullable', 'string', 'in:proveedores,usuarios'],
            'dni' => ['nullable', 'string', 'regex:/^[0-9]{8}$/'],
        ]);
        $puedeCrearCuentas = $this->actor()->puedeGestionarCuentas && $this->esAdministradorOficial();
        $origen = $datos['origen'] ?? 'proveedores';

        return view('admin.proveedores.form', [
            'proveedor' => null,
            'zonas' => $zonas->activas(),
            'usuariosProveedor' => [],
            'origen' => $origen,
            'crearCuentaInicial' => $puedeCrearCuentas && ($origen === 'usuarios' || (bool) ($datos['cuenta'] ?? false)),
            'puedeCrearCuentas' => $puedeCrearCuentas,
            'dniInicial' => $datos['dni'] ?? null,
        ]);
    }

    public function store(StoreProveedorRequest $request, RegistrarProveedorUseCase $registrar): RedirectResponse
    {
        try {
            $resultado = $registrar->ejecutar($request->aDatosProveedor(), $request->aCredenciales(), $request->accionRegistro(), $this->actor());
        } catch (RegistroProveedorException|QueryException $e) {
            return back()->withErrors(['dni' => $this->mensajeSeguro($e)])->withInput($request->except('pin', 'pin_confirmation'));
        } catch (UsuarioInvalidoException $e) {
            return back()->withErrors(['username' => $this->mensajeSeguro($e)])->withInput($request->except('pin', 'pin_confirmation'));
        } catch (ProveedorInvalidoException $e) {
            return back()->withErrors(['codigo' => $this->mensajeSeguro($e)])->withInput($request->except('pin', 'pin_confirmation'));
        }

        $destino = $request->origen() === 'usuarios' && $resultado->cuenta !== null ? 'admin.usuarios.index' : 'admin.proveedores.index';

        return redirect()->route($destino)->with('estado', $resultado->mensaje());
    }

    public function crearCuenta(int $proveedor, Request $request, RegistrarProveedorUseCase $registrar): RedirectResponse
    {
        if (is_string($request->input('username'))) {
            $request->merge(['username' => strtolower(trim($request->input('username')))]);
        }
        $datos = $request->validate([
            'username' => ['required', 'string', 'regex:/^[a-z0-9._]{3,30}$/', 'unique:usuarios,username'],
            'pin' => ['required', 'string', 'regex:/\A[0-9]{4}\z/'],
            'pin_confirmation' => ['required', 'same:pin'],
        ], [
            'username.unique' => 'Este nombre de usuario ya está ocupado. Elige otro.',
            'username.regex' => 'Usa entre 3 y 30 caracteres: minúsculas, números, punto o guion bajo.',
            'pin.regex' => 'El PIN debe tener exactamente 4 dígitos numéricos.',
            'pin_confirmation.same' => 'Los PIN no coinciden. Escríbelos de nuevo.',
        ]);

        try {
            $resultado = $registrar->crearCuentaParaFicha($proveedor, new CredencialesCuentaMovil($datos['username'], $datos['pin']), $this->actor());
        } catch (RegistroProveedorException|UsuarioInvalidoException|ProveedorInvalidoException|QueryException $e) {
            return back()->withErrors(['username' => $this->mensajeSeguro($e)])->withInput($request->only('username'));
        }

        return redirect()->route('admin.proveedores.edit', $proveedor)->with('estado', $resultado->mensaje());
    }

    public function edit(int $proveedor, ZonaRepositoryInterface $zonas, ProveedorRepositoryInterface $proveedores, UsuarioRepositoryInterface $usuarios): View
    {
        $entidad = $proveedores->buscarPorId($proveedor);
        abort_if($entidad === null, 404);

        $cuentaVinculada = $entidad->usuarioId !== null ? $usuarios->buscarPorId($entidad->usuarioId) : null;
        // Solo se ofrecen cuentas de proveedor con el mismo DNI y sin otra ficha: la vinculación exige identidad coincidente.
        $candidatas = $cuentaVinculada === null
            ? array_values(array_filter($usuarios->buscarPorDni($entidad->dni), fn ($cuenta) => $cuenta->tieneRol(Rol::Proveedor) && $proveedores->buscarPorUsuarioId($cuenta->id) === null))
            : [];
        $puedeCrearCuentas = $this->actor()->puedeGestionarCuentas && $this->esAdministradorOficial();

        return view('admin.proveedores.form', [
            'proveedor' => $entidad,
            'zonas' => $zonas->activas(),
            'usuariosProveedor' => $cuentaVinculada ? [$cuentaVinculada] : $candidatas,
            'cuentaVinculada' => $cuentaVinculada,
            'cuentasCandidatas' => $candidatas,
            'puedeCrearCuentas' => $puedeCrearCuentas && $candidatas === [] && $usuarios->buscarPorDni($entidad->dni) === [],
            'puedeVerCuentas' => $puedeCrearCuentas,
            'origen' => 'proveedores',
        ]);
    }

    public function update(int $proveedor, UpdateProveedorRequest $request, ActualizarProveedorUseCase $actualizar): RedirectResponse
    {
        $cuentaAntes = $this->cuentaVinculadaSuspendida($proveedor);
        try {
            $actualizar->ejecutar($proveedor, $request->aDatosProveedor(), $request->has('estado') ? EstadoProveedor::from($request->input('estado')) : null,
                $request->input('motivo_estado'), auth('operador')->id());
        } catch (ProveedorInvalidoException $e) {
            return back()->withErrors(['codigo' => $this->mensajeSeguro($e)])->withInput();
        }

        return redirect()->route('admin.proveedores.index')->with('estado', 'Proveedor actualizado correctamente.'.$this->mensajeCuenta($proveedor, $cuentaAntes));
    }

    public function cambiarEstado(int $proveedor, Request $request, CambiarEstadoProveedorUseCase $cambiarEstado): RedirectResponse
    {
        $request->validate([
            'estado' => ['required', 'string', 'in:activo,suspendido,retirado'],
            'motivo_estado' => ['nullable', 'string', 'max:1000'],
        ]);

        $cuentaAntes = $this->cuentaVinculadaSuspendida($proveedor);
        $cambiarEstado->ejecutar($proveedor, EstadoProveedor::from($request->string('estado')->toString()), $request->input('motivo_estado'), auth('operador')->id());

        // Vuelve a la misma página y filtros desde donde se cambió el estado.
        return redirect()->back(fallback: route('admin.proveedores.index'))->with('estado', 'Estado del proveedor actualizado.'.$this->mensajeCuenta($proveedor, $cuentaAntes));
    }

    /** null = sin cuenta vinculada. */
    private function cuentaVinculadaSuspendida(int $proveedorId): ?bool
    {
        $cuenta = Usuario::query()->whereKey(DB::table('proveedores')->where('id', $proveedorId)->value('usuario_id'))->first();

        return $cuenta?->suspendidaAdministrativamente();
    }

    private function mensajeCuenta(int $proveedorId, ?bool $suspendidaAntes): string
    {
        $despues = $this->cuentaVinculadaSuspendida($proveedorId);

        return match (true) {
            $suspendidaAntes === false && $despues === true => ' Su cuenta móvil vinculada también quedó Suspendida y se cerraron sus sesiones.',
            $suspendidaAntes === true && $despues === false => ' Su cuenta móvil vinculada volvió a estar Activa; deberá iniciar sesión de nuevo.',
            default => '',
        };
    }

    public function vincularUsuario(int $proveedor, Request $request, VincularUsuarioProveedorUseCase $vincular): RedirectResponse
    {
        $request->validate(['usuario_id' => ['required', 'integer']]);

        try {
            $vincular->ejecutar($proveedor, $request->integer('usuario_id'));
        } catch (ProveedorInvalidoException $e) {
            return back()->withErrors(['usuario_id' => $this->mensajeSeguro($e)]);
        }

        return redirect()->route('admin.proveedores.edit', $proveedor)->with('estado', 'Usuario vinculado correctamente.');
    }

    public function desvincularUsuario(int $proveedor, DesvincularUsuarioProveedorUseCase $desvincular): RedirectResponse
    {
        $desvincular->ejecutar($proveedor);

        return redirect()->route('admin.proveedores.edit', $proveedor)->with('estado', 'Usuario desvinculado.');
    }

    private function actor(): ActorRegistroProveedor
    {
        $operador = auth('operador')->user();

        return new ActorRegistroProveedor(
            id: $operador->id,
            esAdministradorOficial: $this->esAdministradorOficial(),
            puedeGestionarCuentas: $operador->puede('usuarios', 'gestionar'),
            puedeVincularCuentas: $operador->puede('proveedores', 'editar'),
        );
    }

    private function esAdministradorOficial(): bool
    {
        $operador = auth('operador')->user();

        return $operador->perfil_rol_id === null && $operador->tieneRol('admin');
    }
}
