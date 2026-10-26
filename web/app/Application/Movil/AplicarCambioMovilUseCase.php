<?php

namespace App\Application\Movil;

use App\Application\Calidad\GuardarAnalisisCalidadUseCase;
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
use App\Domain\Calidad\EstadoAnalisis;
use App\Domain\Liquidaciones\EstadoLiquidacion;
use App\Domain\Movil\CambioMovilRechazadoException;
use App\Domain\Proveedores\EstadoProveedor;
use App\Domain\Usuarios\Rol;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\SinRecojo;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
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
    public const ENTIDADES = ['zona', 'vehiculo', 'usuario', 'proveedor', 'jornada', 'comunicado', 'calidad', 'reclamo', 'liquidacion', 'traslado', 'sin_recojo'];

    /** @param  array<string, mixed>  $datos */
    public function ejecutar(Usuario $autor, string $entidad, array $datos): ?int
    {
        $this->autorizar($autor, $entidad);

        return DB::transaction(function () use ($autor, $entidad, $datos) {
            if (in_array($entidad, ['zona', 'vehiculo', 'usuario', 'proveedor'], true) && ($datos['controlVersion'] ?? false)) {
                $confirmado = app(RevisionCatalogo::class)->comprobar($entidad, $datos);
                if ($confirmado !== null) {
                    return $confirmado;
                }
            }

            return match ($entidad) {
                'zona' => $this->zona($datos),
                'vehiculo' => $this->vehiculo($datos),
                'usuario' => $this->usuario($autor, $datos),
                'proveedor' => $this->proveedor($autor, $datos),
                'jornada' => $this->jornada($autor, $datos),
                'sin_recojo' => $this->sinRecojo($autor, $datos),
                'comunicado' => $this->comunicado($autor, $datos),
                'calidad' => $this->calidad($autor, $datos),
                'reclamo' => $this->reclamo($autor, $datos),
                'traslado' => $this->traslado($autor, $datos),
                'liquidacion' => $this->liquidacion($datos),
            };
        });
        });
    }

    private function autorizar(Usuario $autor, string $entidad): void
    {
        $permitidos = match ($entidad) {
            'jornada', 'sin_recojo' => ['admin', 'acopiador'],
            'calidad' => ['admin', 'calidad'],
            'reclamo', 'traslado' => ['admin', 'proveedor'],
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
     * Solo cuatro roles oficiales; las combinaciones heredadas no cambian sin revisión manual.
     * La validación de altas y cambios se comparte con el panel web.
     *
     * @param  array<string, mixed>  $datos
     */
    private function usuario(Usuario $autor, array $datos): int
    {
        $usuario = $this->porServidorId(Usuario::class, $datos) ?? Usuario::query()->where('username', $datos['username'])->first();
        $rolesMovil = array_values(array_intersect(ExportarDatosMovilQuery::ROLES_MOVIL, array_map('strtolower', $datos['roles'])));
        $pin = $datos['pin'] ?? null;
        $incluyeZona = (bool) ($datos['asignacionZonaIncluida'] ?? false);
        $zonaId = null;
        if ($incluyeZona && (isset($datos['zonaId']) || isset($datos['zonaNombre']))) {
            $zona = $this->porServidorId(Zona::class, $datos, 'zonaId')
                ?? Zona::query()->where('nombre', $datos['zonaNombre'] ?? '')->first()
                ?? throw CambioMovilRechazadoException::noExiste('La zona', $datos['zonaNombre'] ?? (string) $datos['zonaId']);
            $zonaId = $zona->id;
        }

        if ($usuario === null) {
            if ($pin === null) {
                throw new CambioMovilRechazadoException('Para crear la cuenta en el panel hace falta su PIN: vuelve a asignarle un PIN en la app.', 'pin_requerido');
            }
            $id = app(CrearUsuarioUseCase::class)->ejecutar(
                $datos['username'], $datos['nombres'], $datos['dni'], $pin, $this->roles($rolesMovil), true, $autor->id, $zonaId,
            )->id;
        } else {
            $id = $usuario->id;
            $roles = array_values(array_unique([...array_diff($usuario->roles ?? [], ExportarDatosMovilQuery::ROLES_MOVIL), ...$rolesMovil]));
            if ($usuario->nombres !== $datos['nombres'] || (string) $usuario->dni !== $datos['dni'] || collect($usuario->roles)->sort()->values()->all() !== collect($roles)->sort()->values()->all() || ($incluyeZona && $usuario->zona_id !== $zonaId)) {
                app(ActualizarUsuarioUseCase::class)->ejecutar($id, $datos['nombres'], $datos['dni'], $this->roles($roles), true, $autor->id, $zonaId, $incluyeZona);
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
            direccion: $datos['direccion'] ?? null, zonaId: $zona->id, tachos: (int) ($datos['tachos'] ?? $proveedor?->tachos ?? 1),
            capacidadTachoL: (float) ($datos['capacidadTachoL'] ?? $proveedor?->capacidad_tacho_l ?? 40),
            dueno: $datos['dueno'] ?? null, responsableIncluido: array_key_exists('dueno', $datos),
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
        if ($jornada?->cerrada_en !== null && Auditoria::query()->where('entidad', 'jornada')->where('entidad_id', $jornada->id)->where('accion', 'cerrar_jornada')->exists()) {
            $cerradaEn = $jornada->cerrada_en;
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

    private function sinRecojo(Usuario $autor, array $datos): int
    {
        $marca = SinRecojo::query()->where('uuid_movil', $datos['uuid'])->lockForUpdate()->first();
        if ($marca !== null && $marca->usuario_id !== $autor->id && ! $autor->tieneRol('admin')) {
            throw CambioMovilRechazadoException::sinPermiso();
        }
        if ($marca !== null && $marca->actualizado_en > $datos['actualizadoEn']) {
            return $marca->id;
        }
        $proveedor = Proveedor::query()->where('codigo', $datos['proveedorCodigo'])->first()
            ?? throw CambioMovilRechazadoException::noExiste('El proveedor', $datos['proveedorCodigo']);
        if ($marca !== null && $marca->proveedor_id !== $proveedor->id) {
            throw new CambioMovilRechazadoException('No se puede cambiar el proveedor de un registro existente.');
        }
        $jornadaId = $this->jornada($autor, [...$datos, 'uuid' => $datos['jornadaId']]);
        $jornada = Jornada::query()->findOrFail($jornadaId);
        $valores = [
            'jornada_id' => $jornadaId, 'proveedor_id' => $proveedor->id,
            'usuario_id' => $jornada->usuario_id, 'zona_id' => $jornada->zona_id,
            'fecha' => $datos['fecha'], 'motivo' => $datos['motivo'], 'detalle' => $datos['detalle'] ?? null,
            'registrada_en' => $this->instante($datos['registradoEn']),
            'deshecha' => $datos['deshecha'],
            'deshecha_en' => isset($datos['deshechaEn']) ? $this->instante($datos['deshechaEn']) : null,
            'actualizado_en' => $datos['actualizadoEn'],
        ];
        if ($marca !== null) {
            if ($marca->actualizado_en === $datos['actualizadoEn']) {
                if ($marca->deshecha !== $datos['deshecha'] || $marca->motivo !== $datos['motivo'] || $marca->detalle !== ($datos['detalle'] ?? null)) {
                    throw new CambioMovilRechazadoException('El registro cambió: descarga su versión actual.', 'conflicto', 409);
                }

                return $marca->id;
            }
            $marca->update($valores);

            return $marca->id;
        }

        return SinRecojo::query()->create([...$valores, 'uuid_movil' => $datos['uuid']])->id;
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
     * Análisis LactoScan hecho en la app: se guarda completo (mismos 11 parámetros, estado, alertas y datos de la
     * visita) con el id del celular, y califica las entregas de ese proveedor del mismo día.
     *
     * @param  array<string, mixed>  $datos
     */
    private function calidad(Usuario $autor, array $datos): int
    {
        $proveedor = $this->porServidorId(Proveedor::class, $datos, 'proveedorId')
            ?? Proveedor::query()->where('codigo', $datos['proveedorCodigo'])->first()
            ?? throw CambioMovilRechazadoException::noExiste('El proveedor', $datos['proveedorCodigo']);
        $columnas = [
            'temperatura' => 'temperatura', 'grasa' => 'grasa', 'sng' => 'sng', 'densidad' => 'densidad', 'proteina' => 'proteina',
            'lactosa' => 'lactosa', 'sales' => 'sales', 'solidos' => 'solidosTotales', 'agua' => 'aguaAnadida',
            'congelacion' => 'puntoCongelacion', 'ph' => 'ph',
        ];
        $valores = collect($columnas)->map(fn (string $campo) => isset($datos[$campo]) ? (float) $datos[$campo] : null)->all();
        $visita = is_array($datos['visita'] ?? null) ? $datos['visita'] : [];

        try {
            return app(GuardarAnalisisCalidadUseCase::class)->ejecutar([
                'uuid' => $datos['uuid'], 'proveedor_id' => $proveedor->id, 'registrado_en' => $this->instante($datos['registradoEn']),
                'valores' => $valores, 'unidad_congelacion' => $visita['unidadCongelacion'] ?? '°C',
                'serial' => $datos['serialAnalizador'] ?? null, 'modo' => $datos['modoAnalizador'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null, 'origen' => $datos['origenCaptura'] ?? 'MANUAL',
                'texto_comprobante' => $datos['textoComprobante'] ?? null, 'codigo_muestra' => $datos['codigoMuestra'] ?? null,
                'lote_recipiente' => $datos['loteRecipiente'] ?? null, 'volumen_l' => $datos['volumenL'] ?? null,
                'apariencia' => $datos['apariencia'] ?? null, 'estado' => EstadoAnalisis::from(strtoupper($datos['estado'])),
                'alertas' => array_values($datos['alertas'] ?? []), 'visita' => $visita,
            ], $autor)->id;
        } catch (\InvalidArgumentException $e) {
            throw new CambioMovilRechazadoException($e->getMessage());
        }
    }

    /** Reclamo de una entrega hecho por el proveedor en el portal; el admin lo resuelve desde la app o la web. */
    private function reclamo(Usuario $autor, array $datos): int
    {
        $reclamo = preg_match('/^web-reclamo-(\d+)$/', $datos['uuid'], $m) === 1
            ? ReclamoProveedor::query()->whereKey((int) $m[1])->lockForUpdate()->first()
            : ReclamoProveedor::query()->where('uuid_movil', $datos['uuid'])->lockForUpdate()->first();
        $estado = match (strtoupper($datos['estado'] ?? '')) {
            'APROBADA', 'ATENDIDA' => 'resuelto', 'RECHAZADA' => 'rechazado', default => 'pendiente',
        };
        if ($reclamo !== null) {
            $proveedor = Proveedor::query()->findOrFail($reclamo->proveedor_id);
            if (! $autor->tieneRol('admin') && $proveedor->usuario_id !== $autor->id) {
                throw CambioMovilRechazadoException::sinPermiso();
            }
            if ($estado !== 'pendiente') {
                if (! $autor->tieneRol('admin')) {
                    throw CambioMovilRechazadoException::sinPermiso();
                }
                app(ResolverReclamoProveedorUseCase::class)->ejecutar(
                    $reclamo->id, $autor, $estado, $datos['respuesta'] ?? '',
                    isset($datos['entregaUuid']) ? $this->entregaDe($datos['entregaUuid'])?->id : null,
                );
            }

            return $reclamo->id;
        }

        if ($estado !== 'pendiente') {
            throw new CambioMovilRechazadoException('Primero envía el reclamo pendiente antes de resolverlo.', 'esperando', 409, false);
        }
        $entrega = $this->entregaDe((string) ($datos['entregaUuid'] ?? ''));
        if (! empty($datos['entregaUuid']) && $entrega === null) {
            throw CambioMovilRechazadoException::esperando('El reclamo se enviará cuando el panel tenga la entrega reclamada.');
        }
        $proveedor = $entrega?->proveedor ?? ($this->porServidorId(Proveedor::class, $datos, 'proveedorId')
            ?? Proveedor::query()->where('codigo', $datos['proveedorCodigo'] ?? '')->first());
        if ($proveedor === null) {
            throw CambioMovilRechazadoException::esperando('El proveedor del reclamo aún no está registrado en el panel.');
        }
        if (! $autor->tieneRol('admin') && $proveedor->usuario_id !== $autor->id) {
            throw CambioMovilRechazadoException::sinPermiso();
        }

        $evidencia = $datos['evidencia'] ?? null;
        if ($evidencia !== null) {
            $contenido = preg_match('/^data:image\/(jpeg|png);base64,([A-Za-z0-9+\/=]+)$/', $evidencia, $imagen) === 1
                ? base64_decode($imagen[2], true) : false;
            $info = $contenido !== false ? @getimagesizefromstring($contenido) : false;
            if ($contenido === false || strlen($contenido) > 750000 || $info === false
                || ! in_array($info['mime'], ['image/jpeg', 'image/png'], true) || $info[0] > 1600 || $info[1] > 1600) {
                throw new CambioMovilRechazadoException('La evidencia debe ser una imagen JPG o PNG de hasta 750 KB y 1600 píxeles.');
            }
        }

        return ReclamoProveedor::query()->create([
            'uuid_movil' => $datos['uuid'], 'proveedor_id' => $proveedor->id, 'entrega_id' => $entrega?->id,
            'litros_originales' => $entrega?->litros ?? 0, 'litros_solicitados' => $datos['litros'] ?? $entrega?->litros ?? 0,
            'motivo' => mb_strimwidth(trim($datos['motivo'] ?? '') ?: 'Reclamo desde la app', 0, 255, '…'),
            'descripcion' => $datos['descripcion'] ?? null, 'evidencia' => $evidencia, 'estado' => 'pendiente',
        ])->id;
    }

    /** @param array<string, mixed> $datos */
    private function traslado(Usuario $autor, array $datos): int
    {
        $proveedor = $this->porServidorId(Proveedor::class, $datos, 'proveedorId')
            ?? Proveedor::query()->where('codigo', $datos['proveedorCodigo'])->first()
            ?? throw CambioMovilRechazadoException::esperando('El proveedor todavía no está registrado en el panel.');
        if (! $autor->tieneRol('admin') && $proveedor->usuario_id !== $autor->id) {
            throw CambioMovilRechazadoException::sinPermiso();
        }
        $destino = isset($datos['zonaDestinoId']) ? Zona::query()->find($datos['zonaDestinoId']) : null;
        $destino ??= Zona::query()->where('nombre', $datos['zonaDestinoNombre'])->first();
        if ($destino === null || ! $destino->activo) {
            throw CambioMovilRechazadoException::esperando('La zona de destino no existe o no está activa en el panel.');
        }
        $estado = strtoupper($datos['estado']);
        $resolver = in_array($estado, ['APROBADA', 'RECHAZADA'], true);
        if ($resolver && ! $autor->tieneRol('admin')) {
            throw CambioMovilRechazadoException::sinPermiso();
        }
        $solicitud = SolicitudTraslado::query()->where('uuid_movil', $datos['uuid'])->lockForUpdate()->first();
        if ($solicitud !== null && $solicitud->proveedor_id !== $proveedor->id) {
            throw CambioMovilRechazadoException::sinPermiso();
        }
        if ($solicitud === null && $destino->id === $proveedor->zona_id) {
            throw new CambioMovilRechazadoException('Selecciona una zona diferente a la zona actual del proveedor.');
        }
        if ($solicitud === null && SolicitudTraslado::query()->where('proveedor_id', $proveedor->id)->where('estado', 'PENDIENTE')->exists()) {
            throw new CambioMovilRechazadoException('El proveedor ya tiene un traslado pendiente de revisión.', 'traslado_pendiente', 409);
        }
        $solicitud ??= SolicitudTraslado::query()->create([
            'uuid_movil' => $datos['uuid'], 'proveedor_id' => $proveedor->id,
            'zona_destino_id' => $destino->id, 'motivo' => $datos['motivo'], 'estado' => 'PENDIENTE',
        ]);
        if ($resolver && $solicitud->estado !== 'PENDIENTE' && $solicitud->estado !== $estado) {
            throw new CambioMovilRechazadoException('El traslado ya tiene una decisión distinta. Sincroniza para ver el resultado.', 'solicitud_resuelta', 409);
        }
        if ($resolver && $solicitud->estado === 'PENDIENTE') {
            if ($estado === 'APROBADA') {
                $anterior = $proveedor->zona_id;
                app(ActualizarProveedorUseCase::class)->ejecutar($proveedor->id, new DatosProveedor(
                    codigo: $proveedor->codigo, nombres: $proveedor->nombres, dni: $proveedor->dni,
                    telefono: $proveedor->telefono, direccion: $proveedor->direccion, zonaId: $solicitud->zona_destino_id,
                    tachos: $proveedor->tachos, capacidadTachoL: (float) $proveedor->capacidad_tacho_l,
                ));
                Auditoria::query()->create([
                    'entidad' => 'proveedor', 'entidad_id' => $proveedor->id, 'usuario_id' => $autor->id,
                    'accion' => 'autorizar', 'ocurrido_en' => now(), 'motivo' => $solicitud->motivo,
                    'valor_antes' => "zona={$anterior}", 'valor_despues' => "zona={$solicitud->zona_destino_id}",
                ]);
            }
            $solicitud->update(['estado' => $estado, 'respuesta' => trim($datos['respuesta'] ?? '') ?: ($estado === 'APROBADA' ? 'Traslado autorizado por administración.' : 'Traslado rechazado por administración.'), 'resuelto_por' => $autor->id, 'resuelto_en' => now()]);
        }

        return $solicitud->id;
    }

    /** Liquidación semanal aprobada (y luego pagada) desde la app: el panel recalcula los litros con sus entregas. */
    private function liquidacion(array $datos): int
    {
        $proveedor = $this->porServidorId(Proveedor::class, $datos, 'proveedorId')
            ?? Proveedor::query()->where('codigo', $datos['proveedorCodigo'])->first()
            ?? throw CambioMovilRechazadoException::noExiste('El proveedor', $datos['proveedorCodigo']);
        $liquidacion = Liquidacion::query()->where('proveedor_id', $proveedor->id)
            ->whereDate('periodo_inicio', $datos['desde'])->whereDate('periodo_fin', $datos['hasta'])->lockForUpdate()->first();
        if ($liquidacion !== null && abs((float) $liquidacion->precio_litro - (float) $datos['precio']) > 0.0005) {
            throw new CambioMovilRechazadoException('El periodo ya está liquidado con otro precio. Sincroniza y revisa el importe oficial antes de registrar un pago.', 'liquidacion_distinta', 409);
        }
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
