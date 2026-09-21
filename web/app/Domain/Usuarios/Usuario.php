<?php

namespace App\Domain\Usuarios;

use App\Domain\Usuarios\Exceptions\UsuarioInvalidoException;
use DateTimeImmutable;

final class Usuario
{
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
        public readonly bool $bloqueadoManualmente,
        public readonly ?string $motivoBloqueo,
        public readonly ?int $bloqueadoPorId,
        public readonly ?DateTimeImmutable $bloqueadoManualEn,
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
            bloqueadoManualmente: false,
            motivoBloqueo: null,
            bloqueadoPorId: null,
            bloqueadoManualEn: null,
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
        bool $bloqueadoManualmente = false,
        ?string $motivoBloqueo = null,
        ?int $bloqueadoPorId = null,
        ?DateTimeImmutable $bloqueadoManualEn = null,
    ): self {
        return new self($id, $username, $nombres, $dni, $pinHash, $activo, $roles, $intentosFallidos, $bloqueadoHasta, $bloqueadoManualmente, $motivoBloqueo, $bloqueadoPorId, $bloqueadoManualEn);
    }

    public function tieneRol(Rol $rol): bool
    {
        return in_array($rol, $this->roles, true);
    }

    public function esAdmin(): bool
    {
        return $this->tieneRol(Rol::Admin);
    }

    /** Bloqueado automáticamente (intentos fallidos) o manualmente por un administrador: cualquiera de los dos impide el acceso. */
    public function estaBloqueado(DateTimeImmutable $ahora): bool
    {
        return $this->bloqueadoManualmente || ($this->bloqueadoHasta !== null && $this->bloqueadoHasta > $ahora);
    }

    public function puedeUsarPanelWeb(DateTimeImmutable $ahora): bool
    {
        if (! $this->activo || $this->estaBloqueado($ahora)) {
            return false;
        }

        foreach ($this->roles as $rol) {
            if ($rol->accesoWeb()) {
                return true;
            }
        }

        return false;
    }

    public function conIntentoFallido(DateTimeImmutable $ahora, int $maxIntentos = 5, int $minutosBloqueo = 15): self
    {
        $intentos = $this->intentosFallidos + 1;
        $bloqueadoHasta = $intentos >= $maxIntentos
            ? $ahora->modify('+'.$minutosBloqueo.' minutes')
            : $this->bloqueadoHasta;

        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $this->activo, $this->roles, $intentos, $bloqueadoHasta, $this->bloqueadoManualmente, $this->motivoBloqueo, $this->bloqueadoPorId, $this->bloqueadoManualEn);
    }

    public function sinIntentosFallidos(): self
    {
        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $this->activo, $this->roles, 0, null, $this->bloqueadoManualmente, $this->motivoBloqueo, $this->bloqueadoPorId, $this->bloqueadoManualEn);
    }

    /** @param list<Rol> $roles */
    public function conRoles(array $roles): self
    {
        self::validar($this->username, $this->nombres, $this->dni, $roles);

        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $this->activo, $roles, $this->intentosFallidos, $this->bloqueadoHasta, $this->bloqueadoManualmente, $this->motivoBloqueo, $this->bloqueadoPorId, $this->bloqueadoManualEn);
    }

    public function conDatosDePerfil(string $nombres, string $dni): self
    {
        self::validar($this->username, $nombres, $dni, $this->roles);

        return new self($this->id, $this->username, trim($nombres), trim($dni), $this->pinHash, $this->activo, $this->roles, $this->intentosFallidos, $this->bloqueadoHasta, $this->bloqueadoManualmente, $this->motivoBloqueo, $this->bloqueadoPorId, $this->bloqueadoManualEn);
    }

    public function conEstado(bool $activo): self
    {
        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $activo, $this->roles, $this->intentosFallidos, $this->bloqueadoHasta, $this->bloqueadoManualmente, $this->motivoBloqueo, $this->bloqueadoPorId, $this->bloqueadoManualEn);
    }

    public function conBloqueoManual(string $motivo, int $bloqueadoPorId, DateTimeImmutable $ahora): self
    {
        if (trim($motivo) === '') {
            throw UsuarioInvalidoException::motivoBloqueoObligatorio();
        }

        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $this->activo, $this->roles, $this->intentosFallidos, $this->bloqueadoHasta, true, trim($motivo), $bloqueadoPorId, $ahora);
    }

    public function sinBloqueoManual(): self
    {
        return new self($this->id, $this->username, $this->nombres, $this->dni, $this->pinHash, $this->activo, $this->roles, $this->intentosFallidos, $this->bloqueadoHasta, false, null, null, null);
    }

    public function conPinHash(string $pinHash): self
    {
        return new self($this->id, $this->username, $this->nombres, $this->dni, $pinHash, $this->activo, $this->roles, $this->intentosFallidos, $this->bloqueadoHasta, $this->bloqueadoManualmente, $this->motivoBloqueo, $this->bloqueadoPorId, $this->bloqueadoManualEn);
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
