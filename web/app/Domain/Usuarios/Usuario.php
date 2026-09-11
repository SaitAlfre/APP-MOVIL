<?php

namespace App\Domain\Usuarios;

use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use DateTimeImmutable;

final class Usuario
{
    private const int MAX_INTENTOS_FALLIDOS = 5;

    private const int MINUTOS_BLOQUEO = 15;

    private function __construct(
        public readonly ?int $id,
        public readonly string $username,
        public readonly string $nombres,
        public readonly string $dni,
        public readonly string $pinHash,
        public readonly bool $activo,
        /** @var list<Rol> */
        public readonly array $roles,
        public readonly int $intentosFallidos,
        public readonly ?DateTimeImmutable $bloqueadoHasta,
    ) {}

    /** @param list<Rol> $roles */
    public static function crear(
        string $username,
        string $nombres,
        string $dni,
        string $pinHash,
        array $roles,
        bool $activo = true,
    ): self {
        self::validar($username, $nombres, $dni, $roles);

        return new self(
            id: null,
            username: trim($username),
            nombres: trim($nombres),
            dni: trim($dni),
            pinHash: $pinHash,
            activo: $activo,
            roles: $roles,
            intentosFallidos: 0,
            bloqueadoHasta: null,
        );
    }

    /** @param list<Rol> $roles */
    public static function reconstruir(
        int $id,
        string $username,
        string $nombres,
        string $dni,
        string $pinHash,
        bool $activo,
        array $roles,
        int $intentosFallidos,
        ?DateTimeImmutable $bloqueadoHasta,
    ): self {
        return new self($id, $username, $nombres, $dni, $pinHash, $activo, $roles, $intentosFallidos, $bloqueadoHasta);
    }

    public function tieneRol(Rol $rol): bool
    {
        return in_array($rol, $this->roles, true);
    }

    public function estaBloqueado(DateTimeImmutable $ahora): bool
    {
        return $this->bloqueadoHasta !== null && $this->bloqueadoHasta > $ahora;
    }

    public function conIntentoFallido(DateTimeImmutable $ahora): self
    {
        $intentos = $this->intentosFallidos + 1;
        $bloqueadoHasta = $intentos >= self::MAX_INTENTOS_FALLIDOS
            ? $ahora->modify('+'.self::MINUTOS_BLOQUEO.' minutes')
            : $this->bloqueadoHasta;

        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $this->activo, $this->roles, $intentos, $bloqueadoHasta);
    }

    public function sinIntentosFallidos(): self
    {
        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $this->activo, $this->roles, 0, null);
    }

    /** @param list<Rol> $roles */
    private static function validar(string $username, string $nombres, string $dni, array $roles): void
    {
        if (trim($username) === '') {
            throw UsuarioInvalidoException::usernameVacio();
        }

        if (trim($nombres) === '') {
            throw UsuarioInvalidoException::nombresVacios();
        }

        if (trim($dni) === '') {
            throw UsuarioInvalidoException::dniVacio();
        }

        if ($roles === []) {
            throw UsuarioInvalidoException::sinRoles();
        }
    }
}
