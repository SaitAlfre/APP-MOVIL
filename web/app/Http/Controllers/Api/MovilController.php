<?php

namespace App\Http\Controllers\Api;

use App\Application\Auth\AutenticarOperadorUseCase;
use App\Application\Entregas\SincronizarEntregaMovilUseCase;
use App\Application\Movil\AplicarCambioMovilUseCase;
use App\Application\Movil\ExportarDatosMovilQuery;
use App\Domain\Auth\Exceptions\CuentaBloqueadaException;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Domain\Entregas\Exceptions\EntregaMovilRechazadaException;
use App\Domain\Liquidaciones\EstadoLiquidacion;
use App\Domain\Movil\CambioMovilRechazadoException;
use App\Http\Controllers\Controller;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\TokenMovil;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * API de sincronización con la app móvil. La base de datos del panel es la fuente oficial de entregas y
 * liquidaciones: el celular envía aquí sus entregas (y sus correcciones o anulaciones) y el proveedor
 * consulta aquí sus liquidaciones publicadas. No hay credenciales administrativas en el celular: cada
 * usuario obtiene su propio token con su usuario y PIN.
 */
class MovilController extends Controller
{
    public function iniciarSesion(Request $request, AutenticarOperadorUseCase $autenticar): JsonResponse
    {
        $datos = $request->validate([
            'username' => ['required', 'string', 'max:60'],
            'pin' => ['required', 'string', 'max:60'],
            'dispositivo' => ['nullable', 'string', 'max:120'],
        ]);

        $rechazo = response()->json(['message' => 'Usuario o PIN no válidos en el servidor.', 'codigo' => 'credenciales'], 401);

        try {
            if (! $autenticar->verificarSinSesion($datos['username'], $datos['pin'])) {
                return $rechazo;
            }
        } catch (CuentaInactivaException|CuentaBloqueadaException) {
            return response()->json(['message' => 'Tu cuenta está inactiva o bloqueada en el servidor.', 'codigo' => 'cuenta_bloqueada'], 403);
        }

        $usuario = Usuario::query()->where('username', $datos['username'])->firstOrFail();
        [$registro, $token] = TokenMovil::emitir($usuario, $datos['dispositivo'] ?? null);

        Auditoria::query()->create([
            'entidad' => 'sesion', 'entidad_id' => $usuario->id, 'usuario_id' => $usuario->id,
            'accion' => 'iniciar_sesion', 'ocurrido_en' => now(), 'motivo' => 'Vinculación de la app móvil.',
        ]);

        return response()->json([
            'token' => $token,
            'expiraEn' => $registro->expira_en->getTimestampMs(),
            // El celular crea o actualiza con esto la cuenta local (p. ej. una cuenta creada en el panel).
            'usuario' => [
                'id' => $usuario->id, 'username' => $usuario->username, 'nombres' => $usuario->nombres,
                'dni' => (string) $usuario->dni, 'activo' => (bool) $usuario->activo,
                'roles' => array_values(array_intersect(ExportarDatosMovilQuery::ROLES_MOVIL, $usuario->roles ?? [])),
            ],
        ]);
    }

    public function cerrarSesion(Request $request): JsonResponse
    {
        TokenMovil::query()->where('token_hash', TokenMovil::hash((string) $request->bearerToken()))->delete();

        return response()->json(['message' => 'Sesión del celular cerrada.']);
    }

    /** Lo que el panel publica para la cuenta del token (catálogos y operación reciente), ver ExportarDatosMovilQuery. */
    public function datos(Request $request, ExportarDatosMovilQuery $exportar): JsonResponse
    {
        return response()->json($exportar->ejecutar($request->user()));
    }

    /**
     * Estado actual de una fila cambiada en la app (ver AplicarCambioMovilUseCase). Responde el id del panel
     * para que el celular lo recuerde; un rechazo indica si es definitivo o si conviene reintentar.
     */
    public function aplicarCambio(Request $request, string $entidad, AplicarCambioMovilUseCase $aplicar): JsonResponse
    {
        $datos = $request->validate($this->reglasCambio($entidad));
        $autor = $request->user();
        $anterior = auth('operador')->user();
        // Los casos de uso del panel auditan con la cuenta del operador: aquí es la del token.
        auth('operador')->setUser($autor);

        try {
            $id = $aplicar->ejecutar($autor, $entidad, $datos);
        } catch (CambioMovilRechazadoException $e) {
            return response()->json(['message' => $e->getMessage(), 'codigo' => $e->codigo, 'definitivo' => $e->definitivo], $e->estadoHttp);
        } catch (\DomainException|RuntimeException|\InvalidArgumentException|\ValueError $e) {
            return response()->json(['message' => $this->mensajeSeguro($e), 'codigo' => 'invalido', 'definitivo' => true], 422);
        } finally {
            if ($anterior !== null) {
                auth('operador')->setUser($anterior);
            } else {
                auth('operador')->forgetUser();
            }
        }

        return response()->json(['id' => $id]);
    }

    /** @return array<string, list<string>> */
    private function reglasCambio(string $entidad): array
    {
        $servidor = ['servidorId' => ['nullable', 'integer']];

        return match ($entidad) {
            'zona' => [...$servidor, 'nombre' => ['required', 'string', 'max:100'], 'activo' => ['required', 'boolean']],
            'vehiculo' => [...$servidor, 'nombre' => ['required', 'string', 'max:100'], 'placa' => ['required', 'string', 'max:20'], 'activo' => ['required', 'boolean']],
            'usuario' => [...$servidor, 'username' => ['required', 'string', 'max:60'], 'nombres' => ['required', 'string', 'max:150'],
                'dni' => ['required', 'string', 'max:20'], 'roles' => ['required', 'array', 'min:1'], 'roles.*' => ['string'],
                'activo' => ['required', 'boolean'], 'pin' => ['nullable', 'string', 'max:8']],
            'proveedor' => [...$servidor, 'codigo' => ['required', 'string', 'max:60'], 'nombres' => ['required', 'string', 'max:150'],
                'dni' => ['required', 'string', 'max:20'], 'telefono' => ['nullable', 'string', 'max:30'], 'direccion' => ['nullable', 'string', 'max:255'],
                'zonaId' => ['nullable', 'integer'], 'zonaNombre' => ['required', 'string', 'max:120'], 'tachos' => ['required', 'integer', 'min:1', 'max:1000'],
                'capacidadTachoL' => ['required', 'numeric', 'gt:0'], 'estado' => ['required', 'string', 'in:ACTIVO,SUSPENDIDO,RETIRADO'],
                'usuarioUsername' => ['present', 'nullable', 'string', 'max:60']],
            'jornada' => ['uuid' => ['required', 'string', 'max:64'], 'zonaId' => ['nullable', 'integer'], 'zonaNombre' => ['required', 'string', 'max:120'],
                'vehiculoId' => ['nullable', 'integer'], 'vehiculoPlaca' => ['required', 'string', 'max:20'],
                'abiertaEn' => ['required', 'integer', 'min:0'], 'cerradaEn' => ['nullable', 'integer', 'min:0']],
            'comunicado' => [...$servidor, 'uuid' => ['required', 'string', 'max:64'], 'mensaje' => ['nullable', 'string', 'max:2000'],
                'publicadoEn' => ['required', 'integer', 'min:0'], 'eliminado' => ['required', 'boolean']],
            'calidad' => ['uuid' => ['required', 'string', 'max:64'], 'proveedorId' => ['nullable', 'integer'], 'proveedorCodigo' => ['required', 'string', 'max:60'],
                'estado' => ['required', 'string', 'in:APROBADO,OBSERVADO,RECHAZADO,REPETIR'], 'temperatura' => ['nullable', 'numeric', 'between:-5,60'],
                'acidez' => ['nullable', 'numeric', 'between:0,50'], 'observaciones' => ['nullable', 'string', 'max:2000'],
                'registradoEn' => ['required', 'integer', 'min:0']],
            'reclamo' => ['uuid' => ['required', 'string', 'max:64'], 'entregaUuid' => ['nullable', 'string', 'max:64'],
                'litros' => ['nullable', 'numeric', 'gt:0', 'max:18500'], 'motivo' => ['nullable', 'string', 'max:500'], 'estado' => ['nullable', 'string', 'max:30']],
            'liquidacion' => ['proveedorId' => ['nullable', 'integer'], 'proveedorCodigo' => ['required', 'string', 'max:60'],
                'desde' => ['required', 'date_format:Y-m-d'], 'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
                'precio' => ['required', 'numeric', 'gt:0', 'max:20'], 'estado' => ['required', 'string', 'in:APROBADA,PAGADA']],
        };
    }

    public function sincronizarEntrega(Request $request, string $uuid, SincronizarEntregaMovilUseCase $sincronizar): JsonResponse
    {
        $request->merge(['id' => $uuid]);
        $datos = $request->validate([
            'id' => ['required', 'string', 'max:64'],
            'jornadaId' => ['required', 'string', 'max:64'],
            'jornadaAbiertaEn' => ['required', 'integer', 'min:0'],
            'jornadaCerradaEn' => ['nullable', 'integer', 'min:0'],
            'proveedorCodigo' => ['required', 'string', 'max:60'],
            'zonaNombre' => ['required', 'string', 'max:120'],
            'vehiculoPlaca' => ['required', 'string', 'max:20'],
            'acopiadorUsername' => ['required', 'string', 'max:60'],
            'registradoEn' => ['required', 'integer', 'min:0'],
            'litros' => ['required', 'numeric', 'gt:0', 'max:18500'],
            'tachos' => ['required', 'integer', 'min:1', 'max:1000'],
            'observaciones' => ['nullable', 'string', 'max:255'],
            'anulada' => ['required', 'boolean'],
            'motivo' => ['nullable', 'string', 'max:500'],
            'actualizadoEn' => ['required', 'integer', 'min:0'],
        ]);
        $datos['jornadaCerradaEn'] ??= null;
        $datos['observaciones'] ??= null;
        $datos['motivo'] ??= null;

        try {
            $resultado = $sincronizar->ejecutar($request->user(), $datos);
        } catch (EntregaMovilRechazadaException $e) {
            return response()->json(['message' => $e->getMessage(), 'codigo' => $e->codigo], $e->estadoHttp);
        }

        return response()->json([
            'estado' => $resultado['estado'],
            'entregaId' => $resultado['entrega']->id,
            'version' => (int) $resultado['entrega']->version_movil,
        ], $resultado['estado'] === SincronizarEntregaMovilUseCase::CREADA ? 201 : 200);
    }

    /**
     * Solo las liquidaciones del proveedor vinculado a la cuenta del token: nunca las de otro. Se envían
     * todas las generadas en el panel (pendientes de pago y pagadas) con su estado real.
     */
    public function liquidacionesProveedor(Request $request): JsonResponse
    {
        $proveedor = Proveedor::query()->where('usuario_id', $request->user()->id)->first();

        if ($proveedor === null) {
            return response()->json(['message' => 'Tu cuenta no tiene una ficha de proveedor vinculada en el servidor.', 'codigo' => 'sin_ficha'], 404);
        }

        $liquidaciones = Liquidacion::query()
            ->where('proveedor_id', $proveedor->id)
            ->orderByDesc('periodo_inicio')
            ->limit(52)
            ->get()
            ->map(fn (Liquidacion $l) => [
                'id' => $l->id,
                'desde' => $l->periodo_inicio->toDateString(),
                'hasta' => $l->periodo_fin->toDateString(),
                'litros' => (float) $l->litros_totales,
                'precio' => (float) $l->precio_litro,
                'bruto' => round((float) $l->litros_totales * (float) $l->precio_litro, 2),
                'descuento' => (float) $l->descuento_sanciones,
                'total' => (float) $l->monto_total,
                // Estado real del panel, sin traducirlo a otro: `pendiente` = calculada con monto
                // definitivo (no editable ni anulable) y aún no pagada; `pagada` = pago registrado.
                // El panel no tiene un paso de "aprobación" ni de "publicación" separado.
                'estado' => $l->estado === EstadoLiquidacion::Pagada ? 'PAGADA' : 'PENDIENTE',
                'fechaPago' => $l->pagada_en?->setTimezone('America/Lima')->toDateString(),
            ]);

        return response()->json(['proveedorCodigo' => $proveedor->codigo, 'liquidaciones' => $liquidaciones]);
    }
}
