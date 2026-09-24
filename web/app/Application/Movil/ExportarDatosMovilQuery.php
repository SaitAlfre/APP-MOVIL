<?php

namespace App\Application\Movil;

use App\Infrastructure\Persistence\Eloquent\Comunicado;
use App\Infrastructure\Persistence\Eloquent\ControlCalidad;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
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
        $admin = $usuario->tieneRol('admin');
        $desde = now()->subDays(self::DIAS_OPERACION)->startOfDay();
        $miFicha = Proveedor::query()->where('usuario_id', $usuario->id)->first();
        $soloProveedor = ! $admin && ! $usuario->tieneRol('acopiador') && ! $usuario->tieneRol('calidad');

        $proveedores = $soloProveedor
            ? collect($miFicha ? [$miFicha] : [])
            : Proveedor::query()->orderBy('id')->get();

        $entregas = Entrega::query()->where('registrado_en', '>=', $desde)->orderBy('id');
        if (! $admin) {
            $entregas->where(function ($q) use ($usuario, $miFicha) {
                $q->where('usuario_id', $usuario->id);
                if ($miFicha !== null) {
                    $q->orWhere('proveedor_id', $miFicha->id);
                }
            });
        }
        $entregas = $entregas->get();

        $jornadas = Jornada::query()->where(function ($q) use ($entregas, $desde, $admin, $usuario) {
            $q->whereIn('id', $entregas->pluck('jornada_id')->unique());
            if ($admin) {
                $q->orWhere('fecha', '>=', $desde->toDateString());
            } else {
                $q->orWhere(fn ($q) => $q->where('usuario_id', $usuario->id)->where('fecha', '>=', $desde->toDateString()));
            }
        })->orderBy('id')->get();

        $controles = collect();
        if ($admin || $usuario->tieneRol('calidad') || $miFicha !== null) {
            $controles = ControlCalidad::query()->with('entrega:id,proveedor_id')
                ->where('evaluado_en', '>=', $desde)
                ->when(! $admin && ! $usuario->tieneRol('calidad'), fn ($q) => $q->whereHas('entrega', fn ($e) => $e->where('proveedor_id', $miFicha->id)))
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
            ->orderByDesc('publicado_en')->limit(100)->get();
        $autores = Usuario::query()->whereIn('id', $comunicados->pluck('autor_id')->unique())->pluck('nombres', 'id');

        // Cuentas: el admin ve todas las que usan la app; los demás, la suya y los nombres de quienes
        // aparecen en su operación (sin DNI), para que la app muestre "registrado por".
        $idsReferidos = $jornadas->pluck('usuario_id')->merge($entregas->pluck('usuario_id'))
            ->merge($proveedores->pluck('usuario_id'))->filter()->unique();
        $usuarios = Usuario::query()
            ->when(! $admin, fn ($q) => $q->whereIn('id', $idsReferidos->push($usuario->id)))
            ->orderBy('id')->get()
            ->filter(fn (Usuario $u) => $this->rolesMovil($u) !== []);

        return [
            'servidorEn' => now()->getTimestampMs(),
            // Solo la copia del admin trae todo el catálogo: lo que falte en ella se retiró en el panel.
            'completo' => $admin,
            'zonas' => Zona::query()->orderBy('id')->get()->map(fn (Zona $z) => [
                'id' => $z->id, 'nombre' => $z->nombre, 'activo' => (bool) $z->activo,
            ])->values(),
            'vehiculos' => Vehiculo::query()->orderBy('id')->get()->map(fn (Vehiculo $v) => [
                'id' => $v->id, 'nombre' => $v->nombre, 'placa' => $v->placa, 'activo' => (bool) $v->activo,
            ])->values(),
            'usuarios' => $usuarios->map(fn (Usuario $u) => [
                'id' => $u->id, 'username' => $u->username, 'nombres' => $u->nombres,
                'dni' => ($admin || $u->id === $usuario->id) ? (string) $u->dni : null,
                'roles' => $this->rolesMovil($u),
                'activo' => (bool) $u->activo,
            ])->values(),
            'proveedores' => $proveedores->map(fn (Proveedor $p) => [
                'id' => $p->id, 'codigo' => $p->codigo, 'nombres' => $p->nombres, 'dni' => (string) $p->dni,
                'telefono' => $p->telefono, 'direccion' => $p->direccion, 'zonaId' => $p->zona_id,
                'tachos' => (int) $p->tachos, 'capacidadTachoL' => (float) $p->capacidad_tacho_l,
                'estado' => strtoupper($p->estado->value), 'usuarioId' => $p->usuario_id,
            ])->values(),
            'jornadas' => $jornadas->map(fn (Jornada $j) => [
                'id' => $j->id, 'uuidMovil' => $j->uuid_movil, 'usuarioId' => $j->usuario_id,
                'zonaId' => $j->zona_id, 'vehiculoId' => $j->vehiculo_id, 'fecha' => $j->fecha->toDateString(),
                'abiertaEn' => $j->abierta_en?->getTimestampMs() ?? $j->fecha->getTimestampMs(),
                'cerradaEn' => $j->cerrada_en?->getTimestampMs(),
            ])->values(),
            'entregas' => $entregas->map(fn (Entrega $e) => [
                'id' => $e->id, 'uuidMovil' => $e->uuid_movil, 'jornadaId' => $e->jornada_id,
                'proveedorId' => $e->proveedor_id, 'usuarioId' => $e->usuario_id, 'zonaId' => $e->zona_id,
                'vehiculoId' => $e->vehiculo_id, 'litros' => (float) $e->litros, 'tachos' => (int) $e->tachos,
                'observaciones' => $e->observaciones, 'registradoEn' => $e->registrado_en->getTimestampMs(),
                'anulada' => (bool) $e->anulada, 'actualizadoEn' => $e->updated_at?->getTimestampMs() ?? 0,
            ])->values(),
            'controles' => $controles->filter(fn (ControlCalidad $c) => $c->entrega !== null)->map(fn (ControlCalidad $c) => [
                'id' => $c->id, 'proveedorId' => $c->entrega->proveedor_id, 'usuarioId' => $c->usuario_id,
                'resultado' => strtoupper($c->resultado->value),
                'temperatura' => $c->temperatura_c !== null ? (float) $c->temperatura_c : null,
                'acidez' => $c->acidez !== null ? (float) $c->acidez : null,
                'observaciones' => $c->observaciones, 'evaluadoEn' => $c->evaluado_en->getTimestampMs(),
            ])->values(),
            'comunicados' => $comunicados->map(fn (Comunicado $c) => [
                'id' => $c->id, 'titulo' => $c->titulo, 'contenido' => $c->contenido,
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
