<?php

namespace App\Application\Movil;

use App\Infrastructure\Persistence\Eloquent\AnalisisCalidad;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Liquidacion;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\SinRecojo;
use App\Infrastructure\Persistence\Eloquent\SolicitudTraslado;
use App\Infrastructure\Persistence\Eloquent\TarifaLeche;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;

/**
 * Lo que el panel web publica hacia el celular: catálogos (zonas, vehículos, cuentas, proveedores) y la
 * operación reciente (jornadas, entregas, calidad, comunicados). La base del panel es la fuente oficial:
 * el celular reemplaza su copia con esto. Cada cuenta recibe solo lo que su rol puede ver; el DNI de otras
 * personas nunca sale salvo para el administrador.
 */
class ExportarDatosMovilQuery
{
    /** Perfiles que existen en la app móvil; los demás roles del panel no se envían. */
    public const ROLES_MOVIL = ['admin', 'acopiador', 'calidad', 'proveedor'];

    public const DIAS_OPERACION = 45;

    public function ejecutar(Usuario $usuario): array
    {
        Comunicado::query()->where('estado', 'programado')->where('publicar_en', '<=', now())
            ->update(['estado' => 'publicado', 'publicado_en' => now()]);
        $admin = $usuario->tieneRol('admin');
        $desde = now()->subDays(self::DIAS_OPERACION)->startOfDay();
        $miFicha = Proveedor::query()->where('usuario_id', $usuario->id)->first();
        $puedeDatosProveedor = $usuario->tieneRol('proveedor');
        $soloProveedor = ! $admin && ! $usuario->tieneRol('acopiador') && ! $usuario->tieneRol('calidad');

        $proveedores = $soloProveedor
            ? collect($miFicha ? [$miFicha] : [])
            : Proveedor::query()->orderBy('id')->get();

        $entregas = Entrega::query()->where('registrado_en', '>=', $desde)->orderBy('id');
        if (! $admin) {
            $entregas->where(function ($q) use ($usuario, $miFicha, $desde) {
                $q->where('usuario_id', $usuario->id);
                if ($usuario->tieneRol('acopiador')) {
                    $q->orWhereIn('zona_id', Jornada::query()->select('zona_id')
                        ->where('usuario_id', $usuario->id)
                        ->where('fecha', '>=', $desde->toDateString()));
                }
                if ($miFicha !== null) {
                    $q->orWhere('proveedor_id', $miFicha->id);
                }
            });
        }
        $entregas = $entregas->get();

        $sinRecojos = SinRecojo::query()->where('fecha', '>=', $desde->toDateString())
            ->when(! $admin, function ($q) use ($usuario, $miFicha, $desde) {
                $q->where(function ($q) use ($usuario, $miFicha, $desde) {
                    $q->where('usuario_id', $usuario->id);
                    if ($usuario->tieneRol('acopiador')) {
                        $q->orWhereIn('zona_id', Jornada::query()->select('zona_id')->where('usuario_id', $usuario->id)
                            ->where('fecha', '>=', $desde->toDateString()));
                    }
                    if ($miFicha !== null) {
                        $q->orWhere('proveedor_id', $miFicha->id);
                    }
                });
            })->orderBy('id')->get();

        $jornadas = Jornada::query()->where(function ($q) use ($entregas, $sinRecojos, $desde, $admin, $usuario) {
            $q->whereIn('id', $entregas->pluck('jornada_id')->merge($sinRecojos->pluck('jornada_id'))->unique());
            if ($admin) {
                $q->orWhere('fecha', '>=', $desde->toDateString());
            } else {
                $q->orWhere(fn ($q) => $q->where('usuario_id', $usuario->id)->where('fecha', '>=', $desde->toDateString()));
            }
        })->orderBy('id')->get();

        // Análisis de calidad (LactoScan): el admin y calidad ven todos; el proveedor, solo los suyos.
        $analisis = collect();
        if ($admin || $usuario->tieneRol('calidad') || $miFicha !== null) {
            $analisis = AnalisisCalidad::query()->where('registrado_en', '>=', $desde)
                ->when(! $admin && ! $usuario->tieneRol('calidad'), fn ($q) => $q->where('proveedor_id', $miFicha->id))
                ->orderBy('id')->get();
        }

        $audiencias = ['todos'];
        foreach (['proveedor' => 'proveedores', 'acopiador' => 'acopiadores', 'calidad' => 'calidad'] as $rol => $audiencia) {
            if ($admin || $usuario->tieneRol($rol)) {
                $audiencias[] = $audiencia;
            }
        }
        $comunicados = Comunicado::query()
            ->where('estado', 'publicado')->whereIn('audiencia', $audiencias)
            ->when(! $admin, function ($q) use ($miFicha) {
                $q->where(function ($destinos) use ($miFicha) {
                    $destinos->where(fn ($general) => $general->whereNull('proveedor_id')->whereNull('zona_id'));
                    if ($miFicha !== null) {
                        $destinos->orWhere('proveedor_id', $miFicha->id);
                        $destinos->orWhere(function ($zona) use ($miFicha) {
                            $zona->whereNull('proveedor_id')->whereNotNull('zona_id')
                                ->where(function ($receptores) use ($miFicha) {
                                    $receptores->whereJsonContains('destinatarios_proveedor', (int) $miFicha->id)
                                        ->orWhere(fn ($sinLista) => $sinLista->whereNull('destinatarios_proveedor')->where('zona_id', $miFicha->zona_id));
                                });
                        });
                    }
                });
            })
            ->orderByDesc('publicado_en')->limit(100)->get();
        $autores = Usuario::query()->whereIn('id', $comunicados->pluck('autor_id')->unique())->pluck('nombres', 'id');

        // Cuentas: el admin ve todas las que usan la app; los demás, la suya y los nombres de quienes
        // aparecen en su operación (sin DNI), para que la app muestre "registrado por".
        $idsReferidos = $jornadas->pluck('usuario_id')->merge($entregas->pluck('usuario_id'))
            ->merge($sinRecojos->pluck('usuario_id'))->merge($proveedores->pluck('usuario_id'))->merge($analisis->pluck('usuario_id'))->filter()->unique();
        $usuarios = Usuario::query()
            ->when(! $admin, fn ($q) => $q->whereIn('id', $idsReferidos->push($usuario->id)))
            ->orderBy('id')->get()
            ->filter(fn (Usuario $u) => $this->rolesMovil($u) !== []);

        $traslados = SolicitudTraslado::query()
            ->when(! $admin, fn ($q) => $q->where('proveedor_id', $puedeDatosProveedor ? ($miFicha?->id ?? 0) : 0))
            ->orderBy('id')->get()->map(fn (SolicitudTraslado $t) => [
                'id' => $t->uuid_movil, 'proveedorId' => $t->proveedor_id, 'tipo' => 'TRASLADO',
                'referenciaId' => null, 'zonaDestinoId' => $t->zona_destino_id, 'litros' => null,
                'motivo' => 'Traslado de zona', 'descripcion' => $t->motivo,
                'creadaEn' => $t->created_at->getTimestampMs(), 'estado' => $t->estado,
                'respuesta' => $t->respuesta, 'resueltaEn' => $t->resuelto_en?->getTimestampMs(),
            ]);

        $cierresAdministrativos = Auditoria::query()->where('entidad', 'jornada')->where('accion', 'cerrar_jornada')->whereIn('entidad_id', $jornadas->pluck('id'))->pluck('entidad_id')->flip();

        return [
            'servidorEn' => now()->getTimestampMs(),
            // Solo la copia del admin trae todo el catálogo: lo que falte en ella se retiró en el panel.
            'completo' => $admin,
            'zonas' => Zona::query()->orderBy('id')->get()->map(fn (Zona $z) => [
                'revision' => RevisionCatalogo::de($z), 'id' => $z->id, 'nombre' => $z->nombre, 'activo' => (bool) $z->activo,
            ])->values(),
            'vehiculos' => Vehiculo::query()->orderBy('id')->get()->map(fn (Vehiculo $v) => [
                'revision' => RevisionCatalogo::de($v), 'id' => $v->id, 'nombre' => $v->nombre, 'placa' => $v->placa, 'activo' => (bool) $v->activo,
            ])->values(),
            'usuarios' => $usuarios->map(fn (Usuario $u) => [
                'revision' => ($admin || $u->id === $usuario->id) ? RevisionCatalogo::de($u) : null, 'id' => $u->id, 'username' => $u->username, 'nombres' => $u->nombres,
                'dni' => ($admin || $u->id === $usuario->id) ? (string) $u->dni : null,
                'roles' => $this->rolesMovil($u),
                'activo' => (bool) $u->activo,
                'bloqueado' => $u->estaBloqueada(),
                'zonaId' => ($admin || $u->id === $usuario->id) ? $u->zona_id : null,
                'asignacionZonaIncluida' => $admin || $u->id === $usuario->id,
            ])->values(),
            'proveedores' => $proveedores->map(fn (Proveedor $p) => [
                'revision' => RevisionCatalogo::de($p), 'id' => $p->id, 'codigo' => $p->codigo, 'nombres' => $p->nombres, 'dni' => (string) $p->dni,
                'telefono' => $p->telefono, 'direccion' => $p->direccion, 'zonaId' => $p->zona_id,
                'tachos' => (int) $p->tachos, 'capacidadTachoL' => (float) $p->capacidad_tacho_l,
                'estado' => strtoupper($p->estado->value), 'usuarioId' => $p->usuario_id,
                'dueno' => $p->dueno, 'responsableIncluido' => true,
            ])->values(),
            'jornadas' => $jornadas->map(fn (Jornada $j) => [
                'id' => $j->id, 'uuidMovil' => $j->uuid_movil, 'usuarioId' => $j->usuario_id,
                'zonaId' => $j->zona_id, 'vehiculoId' => $j->vehiculo_id, 'fecha' => $j->fecha->toDateString(),
                'abiertaEn' => $j->abierta_en?->getTimestampMs() ?? $j->fecha->getTimestampMs(),
                'cerradaEn' => $j->cerrada_en?->getTimestampMs(), 'cierreAdministrativo' => isset($cierresAdministrativos[$j->id]),
            ])->values(),
            'entregas' => $entregas->map(fn (Entrega $e) => [
                'id' => $e->id, 'uuidMovil' => $e->uuid_movil, 'jornadaId' => $e->jornada_id,
                'proveedorId' => $e->proveedor_id, 'usuarioId' => $e->usuario_id, 'zonaId' => $e->zona_id,
                'vehiculoId' => $e->vehiculo_id, 'litros' => (float) $e->litros, 'tachos' => (int) $e->tachos,
                'observaciones' => $e->observaciones, 'registradoEn' => $e->registrado_en->getTimestampMs(),
                'revision' => $e->revisionMovil(), 'anulada' => (bool) $e->anulada, 'actualizadoEn' => $e->updated_at?->getTimestampMs() ?? 0,
            ])->values(),
            'sinRecojos' => $sinRecojos->map(fn (SinRecojo $m) => [
                'id' => $m->uuid_movil, 'jornadaId' => $m->jornada_id, 'proveedorId' => $m->proveedor_id,
                'usuarioId' => $m->usuario_id, 'zonaId' => $m->zona_id, 'fecha' => $m->fecha->toDateString(),
                'motivo' => $m->motivo, 'detalle' => $m->detalle, 'registradaEn' => $m->registrada_en->getTimestampMs(),
                'deshecha' => $m->deshecha, 'deshechaEn' => $m->deshecha_en?->getTimestampMs(), 'actualizadoEn' => $m->actualizado_en,
            ])->values(),
            'analisis' => $analisis->map(fn (AnalisisCalidad $a) => [
                'id' => $a->id, 'uuid' => $a->uuid, 'proveedorId' => $a->proveedor_id, 'usuarioId' => $a->usuario_id,
                'codigoMuestra' => $a->codigo_muestra, 'loteRecipiente' => $a->lote_recipiente, 'volumenL' => $a->volumen_l,
                'origenCaptura' => $a->origen_captura, 'serialAnalizador' => $a->serial_analizador, 'modoAnalizador' => $a->modo_analizador,
                'temperatura' => $a->temperatura, 'grasa' => $a->grasa, 'sng' => $a->sng, 'densidad' => $a->densidad,
                'proteina' => $a->proteina, 'lactosa' => $a->lactosa, 'sales' => $a->sales, 'solidosTotales' => $a->solidos_totales,
                'aguaAnadida' => $a->agua_anadida, 'puntoCongelacion' => $a->punto_congelacion, 'ph' => $a->ph,
                'apariencia' => $a->apariencia, 'observaciones' => $a->observaciones, 'estado' => $a->estado->value,
                'alertas' => $a->alertas ?? [], 'textoComprobante' => $a->texto_comprobante, 'visita' => (object) ($a->visita ?? []),
                'registradoEn' => $a->registrado_en->getTimestampMs(),
            ])->values(),
            'tarifas' => TarifaLeche::orderBy('desde')->get()->map(fn ($t) => [
                'id' => $t->id, 'precio' => (float) $t->precio, 'desde' => $t->desde->toDateString(), 'hasta' => $t->hasta->toDateString(),
            ])->values(),
            'pagos' => Liquidacion::query()
                ->when(! $admin, fn ($q) => $q->where('proveedor_id', $puedeDatosProveedor ? ($miFicha?->id ?? 0) : 0))
                ->orderBy('id')->get()->map(fn (Liquidacion $p) => [
                    'id' => $p->id, 'proveedorId' => $p->proveedor_id,
                    'desde' => $p->periodo_inicio->toDateString(), 'hasta' => $p->periodo_fin->toDateString(),
                    'litros' => (float) $p->litros_totales, 'precio' => (float) $p->precio_litro,
                    'porTemporadas' => count($p->tarifas_aplicadas ?? []) > 1,
                    'bruto' => round((float) $p->monto_total + (float) $p->descuento_sanciones, 2),
                    'descuento' => (float) $p->descuento_sanciones, 'total' => (float) $p->monto_total,
                    'estado' => strtoupper($p->estado->value), 'fechaPago' => $p->pagada_en?->toDateString(),
                ])->values(),
            'solicitudes' => ReclamoProveedor::query()->select([
                'id', 'uuid_movil', 'proveedor_id', 'entrega_id', 'litros_solicitados', 'motivo',
                'descripcion', 'respuesta', 'resuelto_en', 'created_at', 'estado',
            ])->selectRaw('CASE WHEN evidencia IS NULL THEN 0 ELSE 1 END AS evidencia_disponible')->with('entrega')
                ->when(! $admin, fn ($q) => $q->where('proveedor_id', $puedeDatosProveedor ? ($miFicha?->id ?? 0) : 0))
                ->orderBy('id')->get()->map(fn (ReclamoProveedor $r) => [
                    'id' => $r->uuid_movil ?? "web-reclamo-{$r->id}", 'proveedorId' => $r->proveedor_id,
                    'referenciaId' => $r->entrega?->uuid_movil ?? ($r->entrega_id ? "web-entrega-{$r->entrega_id}" : null),
                    'litros' => (float) $r->litros_solicitados, 'motivo' => $r->motivo,
                    'descripcion' => $r->descripcion ?? $r->motivo, 'creadaEn' => $r->created_at->getTimestampMs(),
                    'respuesta' => $r->respuesta, 'resueltaEn' => $r->resuelto_en?->getTimestampMs(),
                    'evidenciaDisponible' => (bool) $r->evidencia_disponible,
                    'estado' => match ($r->estado) {
                        'resuelto' => 'ATENDIDA', 'rechazado' => 'RECHAZADA', default => 'PENDIENTE'
                    },
                ])->concat($traslados)->values(),
            'comunicados' => $comunicados->map(fn (Comunicado $c) => [
                'id' => $c->id, 'codigo' => $c->codigo, 'titulo' => $c->titulo, 'contenido' => $c->contenido,
                'autor' => $autores[$c->autor_id] ?? 'Administración',
                'publicadoEn' => ($c->publicado_en ?? $c->created_at)->getTimestampMs(),
            ])->values(),
        ];
    }

    /** @return list<string> */
    private function rolesMovil(Usuario $usuario): array
    {
        return array_values(array_intersect(self::ROLES_MOVIL, $usuario->roles ?? []));
    }
}
