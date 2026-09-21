<?php

namespace App\Domain\Usuarios;

use Illuminate\Pagination\LengthAwarePaginator;

interface UsuarioRepositoryInterface
{
    public function buscarPorId(int $id): ?Usuario;

    public function buscarPorUsername(string $username): ?Usuario;

    /** @return list<Usuario> */
    public function conRol(Rol $rol): array;

    /** @return LengthAwarePaginator<int, Usuario> */
    public function paginar(?string $busqueda = null, ?Rol $rol = null, ?bool $activo = null, int $porPagina = 20): LengthAwarePaginator;

    /** Administradores activos y no bloqueados; usado para no dejar el sistema sin ninguno. */
    public function contarAdminsDisponibles(?int $excluyendoId = null): int;

    public function guardar(Usuario $usuario): Usuario;

    /** Guarda el PIN nuevo ya hasheado por el cast del modelo; nunca recibe ni devuelve el hash. */
    public function restablecerCredenciales(int $id, string $nuevoPinPlano): void;

    /** Borra las sesiones activas del usuario (driver de sesión en base de datos), opcionalmente conservando una (p. ej. la de quien ejecuta la acción sobre su propia cuenta). */
    public function invalidarSesiones(int $usuarioId, ?string $exceptoSessionId = null): void;
}
