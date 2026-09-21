<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Usuarios\Rol;
use App\Domain\Usuarios\Usuario as UsuarioDominio;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Usuario as UsuarioEloquent;
use DateTimeImmutable;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

final class EloquentUsuarioRepository implements UsuarioRepositoryInterface
{
    public function buscarPorId(int $id): ?UsuarioDominio
    {
        $usuario = UsuarioEloquent::query()->find($id);

        return $usuario !== null ? $this->aDominio($usuario) : null;
    }

    public function buscarPorUsername(string $username): ?UsuarioDominio
    {
        $usuario = UsuarioEloquent::query()->where('username', $username)->first();

        return $usuario !== null ? $this->aDominio($usuario) : null;
    }

    public function conRol(Rol $rol): array
    {
        return UsuarioEloquent::query()
            ->whereJsonContains('roles', $rol->value)
            ->orderBy('nombres')
            ->get()
            ->map(fn (UsuarioEloquent $u) => $this->aDominio($u))
            ->all();
    }

    public function paginar(?string $busqueda = null, ?Rol $rol = null, ?bool $activo = null, int $porPagina = 20): LengthAwarePaginator
    {
        return UsuarioEloquent::query()
            ->when($busqueda !== null && trim($busqueda) !== '', function ($query) use ($busqueda) {
                $termino = '%'.trim($busqueda).'%';
                $query->where(fn ($q) => $q->where('nombres', 'like', $termino)
                    ->orWhere('username', 'like', $termino)
                    ->orWhere('dni', 'like', $termino));
            })
            ->when($rol !== null, fn ($query) => $query->whereJsonContains('roles', $rol->value))
            ->when($activo !== null, fn ($query) => $query->where('activo', $activo))
            ->orderBy('nombres')
            ->paginate($porPagina)
            ->through(fn (UsuarioEloquent $u) => $this->aDominio($u));
    }

    public function contarAdminsDisponibles(?int $excluyendoId = null): int
    {
        $ahora = now();

        return UsuarioEloquent::query()
            ->whereJsonContains('roles', Rol::Admin->value)
            ->where('activo', true)
            ->where('bloqueado_manualmente', false)
            ->where(fn ($q) => $q->whereNull('bloqueado_hasta')->orWhere('bloqueado_hasta', '<=', $ahora))
            ->when($excluyendoId !== null, fn ($query) => $query->where('id', '!=', $excluyendoId))
            ->count();
    }

    public function guardar(UsuarioDominio $usuario): UsuarioDominio
    {
        $registro = $usuario->id !== null
            ? UsuarioEloquent::query()->findOrFail($usuario->id)
            : new UsuarioEloquent;

        $registro->username = $usuario->username;
        $registro->nombres = $usuario->nombres;
        $registro->dni = $usuario->dni;
        $registro->activo = $usuario->activo;
        $registro->roles = array_map(fn (Rol $r) => $r->value, $usuario->roles);
        $registro->intentos_fallidos = $usuario->intentosFallidos;
        $registro->bloqueado_hasta = $usuario->bloqueadoHasta;
        $registro->bloqueado_manualmente = $usuario->bloqueadoManualmente;
        $registro->motivo_bloqueo = $usuario->motivoBloqueo;
        $registro->bloqueado_por = $usuario->bloqueadoPorId;
        $registro->bloqueado_manual_en = $usuario->bloqueadoManualEn;

        if ($registro->pin_hash === null || $registro->pin_hash === '') {
            $registro->pin_hash = $usuario->pinHash;
        }

        $registro->save();

        return $this->aDominio($registro->refresh());
    }

    public function restablecerCredenciales(int $id, string $nuevoPinPlano): void
    {
        $registro = UsuarioEloquent::query()->findOrFail($id);
        // Se asigna sobre la instancia (no con un update() masivo) para que el cast 'hashed'
        // del modelo lo convierta a hash antes de guardar.
        $registro->pin_hash = $nuevoPinPlano;
        $registro->save();
    }

    public function invalidarSesiones(int $usuarioId, ?string $exceptoSessionId = null): void
    {
        UsuarioEloquent::query()->whereKey($usuarioId)->increment('version_sesion');
        if ($exceptoSessionId !== null && auth('operador')->id() === $usuarioId && request()->hasSession()) {
            request()->session()->put('version_sesion.'.$usuarioId, (int) UsuarioEloquent::query()->whereKey($usuarioId)->value('version_sesion'));
        }
        DB::table('sessions')
            ->where('user_id', $usuarioId)
            ->when($exceptoSessionId !== null, fn ($query) => $query->where('id', '!=', $exceptoSessionId))
            ->delete();
    }

    private function aDominio(UsuarioEloquent $usuario): UsuarioDominio
    {
        $bloqueadoHasta = $usuario->bloqueado_hasta;
        $bloqueadoManualEn = $usuario->bloqueado_manual_en;

        return UsuarioDominio::reconstruir(
            id: $usuario->id,
            username: $usuario->username,
            nombres: $usuario->nombres,
            dni: $usuario->dni,
            pinHash: $usuario->pin_hash,
            activo: $usuario->activo,
            roles: array_map(fn (string $r) => Rol::from($r), $usuario->roles ?? []),
            intentosFallidos: $usuario->intentos_fallidos,
            bloqueadoHasta: $bloqueadoHasta !== null ? DateTimeImmutable::createFromInterface($bloqueadoHasta) : null,
            bloqueadoManualmente: $usuario->bloqueado_manualmente,
            motivoBloqueo: $usuario->motivo_bloqueo,
            bloqueadoPorId: $usuario->bloqueado_por,
            bloqueadoManualEn: $bloqueadoManualEn !== null ? DateTimeImmutable::createFromInterface($bloqueadoManualEn) : null,
        );
    }
}
