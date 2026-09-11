<?php

namespace App\Infrastructure\Persistence\Eloquent;

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
}
