<?php

namespace App\Application\Usuarios;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class ListarDirectorioUsuarios
{
    public function ejecutar(array $filtros): LengthAwarePaginator
    {
        $cuentas = DB::table('usuarios as u')
            ->leftJoin('perfiles_roles as r', 'r.id', '=', 'u.perfil_rol_id')
            ->selectRaw("u.id as usuario_id, u.id, u.nombres as nombre, u.username, u.dni, u.roles, r.nombre as perfil, u.created_at as fecha, CASE WHEN u.activo = 0 OR u.bloqueado_manualmente = 1 THEN 'suspendido' WHEN u.bloqueado_hasta > ? THEN 'bloqueado' ELSE 'activo' END as estado, CASE WHEN u.bloqueado_hasta > ? THEN 1 ELSE 0 END as bloqueo_temporal", [now(), now()]);
        $rol = $filtros['rol'] ?? '';
        if ($rol !== '') {
            if (str_starts_with($rol, 'perfil:')) {
                $cuentas->where('u.perfil_rol_id', (int) substr($rol, 7));
            } else {
                $cuentas->whereNull('u.perfil_rol_id')->whereJsonContains('u.roles', $rol);
            }
        }
        $consulta = DB::query()->fromSub($cuentas, 'cuentas');
        $busqueda = trim($filtros['buscar'] ?? '');
        if ($busqueda !== '') {
            $termino = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $busqueda).'%';
            $consulta->where(function (Builder $query) use ($termino) {
                foreach (['nombre', 'dni', 'username'] as $columna) {
                    $query->orWhereRaw($columna." LIKE ? ESCAPE '!'", [$termino]);
                }
            });
        }
        // «Activos» son las cuentas habilitadas administrativamente, aunque tengan un bloqueo temporal por intentos fallidos.
        match ($filtros['estado'] ?? '') {
            'activo' => $consulta->whereIn('estado', ['activo', 'bloqueado']),
            'suspendido', 'inactivo' => $consulta->where('estado', 'suspendido'),
            'bloqueado' => $consulta->where('bloqueo_temporal', 1),
            default => null,
        };
        if (($filtros['bloqueo'] ?? '') === 'temporal') {
            $consulta->where('bloqueo_temporal', 1);
        }
        match ($filtros['orden'] ?? 'recientes') {
            'nombre_asc' => $consulta->orderBy('nombre'),
            'nombre_desc' => $consulta->orderByDesc('nombre'),
            default => $consulta->orderByDesc('fecha'),
        };

        return $consulta->orderByDesc('id')->paginate((int) ($filtros['por_pagina'] ?? 20))->withQueryString();
    }
}
