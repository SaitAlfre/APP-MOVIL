<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Usuarios\Rol;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function newFactory(): UsuarioFactory
    {
        return UsuarioFactory::new();
    }

    protected $table = 'usuarios';

    protected $fillable = [
        'username',
        'nombres',
        'dni',
        'pin_hash',
        'activo',
        'roles',
        'intentos_fallidos',
        'bloqueado_hasta',
        'bloqueado_manualmente',
        'motivo_bloqueo',
        'bloqueado_por',
        'bloqueado_manual_en',
    ];

    protected $hidden = [
        'pin_hash',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'roles' => 'array',
            'bloqueado_hasta' => 'datetime',
            'bloqueado_manualmente' => 'boolean',
            'bloqueado_manual_en' => 'datetime',
            'pin_hash' => 'hashed',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->pin_hash;
    }

    public function getAuthPasswordName(): string
    {
        return 'pin_hash';
    }

    public function tieneRol(string $rol): bool
    {
        return in_array($rol, $this->roles ?? [], true);
    }

    /** Bloqueado automático (intentos fallidos) o manual (con motivo): cualquiera de los dos impide el acceso. */
    public function estaBloqueada(): bool
    {
        return $this->bloqueado_manualmente || ($this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture());
    }

    public function accesoWeb(): bool
    {
        if (! $this->activo || $this->estaBloqueada()) {
            return false;
        }

        foreach ($this->roles ?? [] as $rolValor) {
            if (Rol::tryFrom($rolValor)?->accesoWeb()) {
                return true;
            }
        }

        return false;
    }

    /** Para mostrar u ocultar enlaces de navegación; el control real está en la matriz `permiso:` de las rutas. */
    public function puede(string $modulo, string $accion = 'ver'): bool
    {
        $rolesPermitidos = config("permisos.{$modulo}.{$accion}", []);

        return collect($this->roles ?? [])->intersect($rolesPermitidos)->isNotEmpty();
    }
}
