<?php

namespace App\Application\Movil;

use App\Application\Calidad\RegistrarControlCalidadUseCase;
use App\Application\Liquidaciones\GenerarLiquidacionUseCase;
use App\Application\Liquidaciones\MarcarLiquidacionPagadaUseCase;
use App\Application\Proveedores\ActualizarProveedorUseCase;
use App\Application\Proveedores\CambiarEstadoProveedorUseCase;
use App\Application\Proveedores\CrearProveedorUseCase;
use App\Application\Proveedores\DatosProveedor;
use App\Application\Proveedores\DesvincularUsuarioProveedorUseCase;
use App\Application\Proveedores\VincularUsuarioProveedorUseCase;
use App\Application\Usuarios\ActualizarUsuarioUseCase;
use App\Application\Usuarios\CambiarEstadoUsuarioUseCase;
use App\Application\Usuarios\CrearUsuarioUseCase;
use App\Application\Usuarios\RestablecerCredencialesUsuarioUseCase;
use App\Application\Vehiculos\ActualizarVehiculoUseCase;
use App\Application\Vehiculos\CambiarEstadoVehiculoUseCase;
use App\Application\Vehiculos\CrearVehiculoUseCase;
use App\Application\Zonas\ActualizarZonaUseCase;
use App\Application\Zonas\CambiarEstadoZonaUseCase;
use App\Application\Zonas\CrearZonaUseCase;
use App\Domain\Calidad\EstadoCalidad;
use App\Domain\Liquidaciones\EstadoLiquidacion;
use App\Domain\Movil\CambioMovilRechazadoException;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Usuarios\Rol;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Celular -> panel: aplica en la base del panel un cambio hecho en la app. El celular envía el estado ACTUAL
 * de la fila (no la operación), así que reenviar es idempotente. Las reglas y la auditoría son las mismas del
 * panel porque se usan sus casos de uso; el autor es la cuenta del token.
 *
 * Cada fila se reconoce por su id del panel si el celular ya lo conoce (`servidorId`), si no por su nombre,
 * placa, usuario, código o id del celular (`uuid`). Devuelve el id del panel para que el celular lo recuerde.
 */
class AplicarCambioMovilUseCase
{
    public const ENTIDADES = ['zona', 'vehiculo', 'usuario', 'proveedor', 'jornada', 'comunicado', 'calidad', 'reclamo', 'liquidacion'];

    /** @param  array<string, mixed>  $datos */
    public function ejecutar(Usuario $autor, string $entidad, array $datos): ?int
    {
        $this->autorizar($autor, $entidad);

        return DB::transaction(fn () => match ($entidad) {
            'zona' => $this->zona($datos),
            'vehiculo' => $this->vehiculo($datos),
            'usuario' => $this->usuario($autor, $datos),
            'proveedor' => $this->proveedor($autor, $datos),
            'jornada' => $this->jornada($autor, $datos),
            'comunicado' => $this->comunicado($autor, $datos),
            'calidad' => $this->calidad($autor, $datos),
            'reclamo' => $this->reclamo($autor, $datos),
            'liquidacion' => $this->liquidacion($datos),
        });
    }

    private function autorizar(Usuario $autor, string $entidad): void
    {
        $permitidos = match ($entidad) {
            'jornada' => ['admin', 'acopiador'],
            'calidad' => ['admin', 'calidad'],
            'reclamo' => ['admin', 'proveedor'],
            default => ['admin'],
        };
        if (collect($permitidos)->every(fn (string $rol) => ! $autor->tieneRol($rol))) {
            throw CambioMovilRechazadoException::sinPermiso();
        }
    }

    /** @param  array<string, mixed>  $datos */
    private function zona(array $datos): int
    {
        $zona = $this->porServidorId(Zona::class, $datos)
            ?? Zona::query()->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($datos['nombre'])])->first();
        $id = $zona?->id ?? app(CrearZonaUseCase::class)->ejecutar($datos['nombre'])->id;
        if ($zona !== null && $zona->nombre !== $datos['nombre']) {
            app(ActualizarZonaUseCase::class)->ejecutar($id, $datos['nombre']);
        }
        if ((bool) Zona::query()->findOrFail($id)->activo !== (bool) $datos['activo']) {
            app(CambiarEstadoZonaUseCase::class)->ejecutar($id, (bool) $datos['activo']);
        }

        return $id;
    }

    /** @param  array<string, mixed>  $datos */
    private function vehiculo(array $datos): int
    {
        $placa = mb_strtoupper(trim($datos['placa']));
        $vehiculo = $this->porServidorId(Vehiculo::class, $datos)
            ?? Vehiculo::query()->whereRaw('UPPER(placa) = ?', [$placa])->first();
        $id = $vehiculo?->id ?? app(CrearVehiculoUseCase::class)->ejecutar($datos['nombre'], $placa)->id;
        if ($vehiculo !== null && ($vehiculo->nombre !== $datos['nombre'] || mb_strtoupper($vehiculo->placa) !== $placa)) {
            app(ActualizarVehiculoUseCase::class)->ejecutar($id, $datos['nombre'], $placa);
        }
        if ((bool) Vehiculo::query()->findOrFail($id)->activo !== (bool) $datos['activo']) {
            app(CambiarEstadoVehiculoUseCase::class)->ejecutar($id, (bool) $datos['activo']);
        }

        return $id;
    }

    /**
     * Los roles que solo existen en el panel (producción, recepción...) se conservan: el celular solo
     * decide sobre los perfiles que conoce.
     *
     * @param  array<string, mixed>  $datos
     */
    private function usuario(Usuario $autor, array $datos): int
    {
        $usuario = $this->porServidorId(Usuario::class, $datos) ?? Usuario::query()->where('username', $datos['username'])->first();
        $rolesMovil = array_values(array_intersect(ExportarDatosMovilQuery::ROLES_MOVIL, array_map('strtolower', $datos['roles'])));
        $pin = $datos['pin'] ?? null;

        if ($usuario === null) {
            if ($pin === null) {
                throw new CambioMovilRechazadoException('Para crear la cuenta en el panel hace falta su PIN: vuelve a asignarle un PIN en la app.', 'pin_requerido');
            }
            $id = app(CrearUsuarioUseCase::class)->ejecutar(
                $datos['username'], $datos['nombres'], $datos['dni'], $pin, $this->roles($rolesMovil), true, $autor->id,
            )->id;
        } else {
            $id = $usuario->id;
            $roles = array_values(array_unique([...array_diff($usuario->roles ?? [], ExportarDatosMovilQuery::ROLES_MOVIL), ...$rolesMovil]));
            if ($usuario->nombres !== $datos['nombres'] || (string) $usuario->dni !== $datos['dni'] || collect($usuario->roles)->sort()->values()->all() !== collect($roles)->sort()->values()->all()) {
                app(ActualizarUsuarioUseCase::class)->ejecutar($id, $datos['nombres'], $datos['dni'], $this->roles($roles), true, $autor->id);
            }
            if ($pin !== null) {
                app(RestablecerCredencialesUsuarioUseCase::class)->ejecutar($id, $pin, $autor->id);
            }
        }
        if ((bool) Usuario::query()->findOrFail($id)->activo !== (bool) $datos['activo']) {
            app(CambiarEstadoUsuarioUseCase::class)->ejecutar($id, (bool) $datos['activo'], $autor->id);
        }

        return $id;
    }

    /** @param  array<string, mixed>  $datos */
    private function proveedor(Usuario $autor, array $datos): int
    {
        $zona = $this->zonaDe($datos);
        $proveedor = $this->porServidorId(Proveedor::class, $datos) ?? Proveedor::query()->where('codigo', $datos['codigo'])->first();
        $ficha = new DatosProveedor(
            codigo: $datos['codigo'], nombres: $datos['nombres'], dni: $datos['dni'], telefono: $datos['telefono'] ?? null,
            direccion: $datos['direccion'] ?? null, zonaId: $zona->id, tachos: (int) $datos['tachos'],
            capacidadTachoL: (float) $datos['capacidadTachoL'],
        );
        $id = $proveedor === null
            ? app(CrearProveedorUseCase::class)->ejecutar($ficha, $autor->id)->id
            : app(ActualizarProveedorUseCase::class)->ejecutar($proveedor->id, $ficha)->id;

        $estado = EstadoProveedor::from(strtolower($datos['estado']));
        $actual = Proveedor::query()->findOrFail($id);
        if ($actual->estado !== $estado) {
            app(CambiarEstadoProveedorUseCase::class)->ejecutar($id, $estado);
        }
        $cuenta = isset($datos['usuarioUsername']) ? Usuario::query()->where('username', $datos['usuarioUsername'])->first() : null;
        if ($cuenta !== null && $actual->usuario_id !== $cuenta->id) {
            app(VincularUsuarioProveedorUseCase::class)->ejecutar($id, $cuenta->id);
        } elseif (array_key_exists('usuarioUsername', $datos) && $datos['usuarioUsername'] === null && $actual->usuario_id !== null) {
            app(DesvincularUsuarioProveedorUseCase::class)->ejecutar($id);
        }

        return $id;
    }

    /** Jornada abierta, cerrada o reabierta en el celular, aunque aún no tenga entregas. */
    private function jornada(Usuario $autor, array $datos): int
    {
        $zona = $this->zonaDe($datos);
        $vehiculo = $this->porServidorId(Vehiculo::class, $datos, 'vehiculoId')
            ?? Vehiculo::query()->whereRaw('UPPER(placa) = ?', [mb_strtoupper($datos['vehiculoPlaca'])])->first()
            ?? throw CambioMovilRechazadoException::noExiste('El vehículo', $datos['vehiculoPlaca']);
        $abiertaEn = $this->instante($datos['abiertaEn']);
        $cerradaEn = isset($datos['cerradaEn']) ? $this->instante($datos['cerradaEn']) : null;

        $jornada = (preg_match('/^web-jornada-(\d+)$/', $datos['uuid'], $m) === 1
            ? Jornada::query()->whereKey((int) $m[1])
            : Jornada::query()->where('uuid_movil', $datos['uuid']))->lockForUpdate()->first();
        if ($jornada !== null && $jornada->usuario_id !== $autor->id && ! $autor->tieneRol('admin')) {
            throw new CambioMovilRechazadoException('Esta jornada la abrió otra cuenta: debe enviarla su propia cuenta.', 'autor_distinto', 403);
        }
        $valores = [
            'zona_id' => $zona->id, 'vehiculo_id' => $vehiculo->id, 'abierta_en' => $abiertaEn, 'cerrada_en' => $cerradaEn,
            'fecha' => $abiertaEn->setTimezone('America/Lima')->toDateString(),
        ];
        if ($jornada === null) {
            return Jornada::query()->create([...$valores, 'uuid_movil' => $datos['uuid'], 'usuario_id' => $autor->id])->id;
        }
        $jornada->update($valores);

        return $jornada->id;
    }

    /** Comunicado publicado (o retirado) desde la app: su código es el id del celular. */
    private function comunicado(Usuario $autor, array $datos): ?int
    {
        $comunicado = $this->porServidorId(Comunicado::class, $datos)
            ?? Comunicado::query()->where('codigo', 'MOV-'.$datos['uuid'])->first();
        if ($datos['eliminado'] ?? false) {
            $comunicado?->update(['estado' => 'borrador', 'publicado_en' => null]);

            return $comunicado?->id;
        }
        $mensaje = trim($datos['mensaje']);
        $valores = [
            'titulo' => mb_strimwidth(strtok($mensaje, "\n") ?: $mensaje, 0, 120, '…'),
            'contenido' => $mensaje, 'estado' => 'publicado', 'publicado_en' => $this->instante($datos['publicadoEn']),
        ];
        if ($comunicado !== null) {
            $comunicado->update($valores);

            return $comunicado->id;
        }

        return Comunicado::query()->create([...$valores, 'codigo' => 'MOV-'.$datos['uuid'], 'audiencia' => 'todos', 'autor_id' => $autor->id])->id;
    }

    /**
     * El análisis del celular es por proveedor; en el panel la calidad se registra por entrega, así que se
     * asocia a la entrega de ese proveedor del mismo día (hora de Perú) que aún no tenga evaluación.
     */
    private function calidad(Usuario $autor, array $datos): int
    {
        $resultado = EstadoCalidad::from(match (strtoupper($datos['estado'])) {
            'APROBADO' => 'aprobado', 'RECHAZADO' => 'rechazado', default => 'observado',
        });
        $valores = [
            'resultado' => $resultado, 'temperatura_c' => $datos['temperatura'] ?? null, 'acidez' => $datos['acidez'] ?? null,
            'observaciones' => isset($datos['observaciones']) ? mb_strimwidth($datos['observaciones'], 0, 255, '…') : null,
        ];
        $control = ControlCalidad::query()->where('uuid_movil', $datos['uuid'])->first();
        if ($control !== null) {
            $control->update($valores);

            return $control->id;
        }

        $proveedor = $this->porServidorId(Proveedor::class, $datos, 'proveedorId')
            ?? Proveedor::query()->where('codigo', $datos['proveedorCodigo'])->first()
            ?? throw CambioMovilRechazadoException::noExiste('El proveedor', $datos['proveedorCodigo']);
        $registradoEn = $this->instante($datos['registradoEn']);
        $dia = $registradoEn->setTimezone('America/Lima');
        $entrega = Entrega::query()->where('proveedor_id', $proveedor->id)->where('anulada', false)
            ->whereBetween('registrado_en', [$dia->startOfDay()->utc(), $dia->endOfDay()->utc()])
            ->whereNotIn('id', ControlCalidad::query()->select('entrega_id'))
            ->orderByDesc('registrado_en')->first()
            ?? throw CambioMovilRechazadoException::esperando("El análisis de {$proveedor->codigo} se enviará cuando el panel tenga su entrega del {$dia->toDateString()}.");

        $control = app(RegistrarControlCalidadUseCase::class)->ejecutar(
            $entrega->id, $autor->id, $resultado, $valores['temperatura_c'], $valores['acidez'], $valores['observaciones'],
        );
        ControlCalidad::query()->whereKey($control->id)->update(['uuid_movil' => $datos['uuid'], 'evaluado_en' => $registradoEn]);

        return $control->id;
    }

    /** Reclamo de una entrega hecho por el proveedor en el portal; el admin lo resuelve desde la app o la web. */
    private function reclamo(Usuario $autor, array $datos): int
    {
        $reclamo = ReclamoProveedor::query()->where('uuid_movil', $datos['uuid'])->first();
        $estado = match (strtoupper($datos['estado'] ?? '')) {
            'APROBADA', 'ATENDIDA' => 'resuelto', 'RECHAZADA' => 'rechazado', default => 'pendiente',
        };
        if ($reclamo !== null) {
            if ($reclamo->estado === 'pendiente' && $estado !== 'pendiente' && $autor->tieneRol('admin')) {
                $reclamo->update(['estado' => $estado, 'resuelto_por' => $autor->id, 'resuelto_en' => now()]);
            }

            return $reclamo->id;
        }

        $entrega = $this->entregaDe((string) ($datos['entregaUuid'] ?? ''))
            ?? throw CambioMovilRechazadoException::esperando('El reclamo se enviará cuando el panel tenga la entrega reclamada.');
        $proveedor = Proveedor::query()->findOrFail($entrega->proveedor_id);
        if (! $autor->tieneRol('admin') && $proveedor->usuario_id !== $autor->id) {
            throw CambioMovilRechazadoException::sinPermiso();
        }

        return ReclamoProveedor::query()->create([
            'uuid_movil' => $datos['uuid'], 'proveedor_id' => $proveedor->id, 'entrega_id' => $entrega->id,
            'litros_originales' => $entrega->litros, 'litros_solicitados' => $datos['litros'] ?? $entrega->litros,
            'motivo' => mb_strimwidth(trim($datos['motivo']) ?: 'Reclamo desde la app', 0, 255, '…'), 'estado' => $estado,
        ])->id;
    }

    /** Liquidación semanal aprobada (y luego pagada) desde la app: el panel recalcula los litros con sus entregas. */
    private function liquidacion(array $datos): int
    {
        $proveedor = $this->porServidorId(Proveedor::class, $datos, 'proveedorId')
            ?? Proveedor::query()->where('codigo', $datos['proveedorCodigo'])->first()
            ?? throw CambioMovilRechazadoException::noExiste('El proveedor', $datos['proveedorCodigo']);
        $liquidacion = Liquidacion::query()->where('proveedor_id', $proveedor->id)->whereDate('periodo_inicio', $datos['desde'])->first();
        $id = $liquidacion?->id ?? app(GenerarLiquidacionUseCase::class)->ejecutar(
            $proveedor->id, new DateTimeImmutable($datos['desde']), new DateTimeImmutable($datos['hasta']), (float) $datos['precio'],
        )->id;
        if (strtoupper($datos['estado']) === 'PAGADA' && Liquidacion::query()->findOrFail($id)->estado !== EstadoLiquidacion::Pagada) {
            app(MarcarLiquidacionPagadaUseCase::class)->ejecutar($id);
        }

        return $id;
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<T>  $modelo
     * @return T|null
     */
    private function porServidorId(string $modelo, array $datos, string $campo = 'servidorId'): mixed
    {
        return isset($datos[$campo]) ? $modelo::query()->find($datos[$campo]) : null;
    }

    private function zonaDe(array $datos): Zona
    {
        return $this->porServidorId(Zona::class, $datos, 'zonaId')
            ?? Zona::query()->whereRaw('UPPER(nombre) = ?', [mb_strtoupper((string) ($datos['zonaNombre'] ?? ''))])->first()
            ?? throw CambioMovilRechazadoException::noExiste('La zona', (string) ($datos['zonaNombre'] ?? ''));
    }

    /** Entrega por id del celular, o `web-entrega-N` si nació en el panel. */
    private function entregaDe(string $uuid): ?Entrega
    {
        if (preg_match('/^web-entrega-(\d+)$/', $uuid, $m) === 1) {
            return Entrega::query()->find((int) $m[1]);
        }

        return $uuid === '' ? null : Entrega::query()->where('uuid_movil', $uuid)->first();
    }

    /** @param  list<string>  $roles
     * @return list<Rol>
     */
    private function roles(array $roles): array
    {
        return array_map(fn (string $rol) => Rol::from($rol), $roles);
    }

    private function instante(int|float $epochMs): CarbonImmutable
    {
        return CarbonImmutable::createFromTimestampMs((int) $epochMs)->utc();
    }
}
