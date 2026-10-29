<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Usuarios\Rol;
use Database\Factories\UsuarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class Usuario extends Authenticatable
{
    use HasFactory, Notifiable;

    protected static function newFactory(): UsuarioFactory
    {
        return UsuarioFactory::new();
    }

    protected $table = 'usuarios';

    protected $fillable = [
        'perfil_rol_id',
        'datos_formulario',
        'username',
        'nombres',
        'dni',
        'pin_hash',
        'activo',
        'roles',
        'zona_id',
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
            'datos_formulario' => 'array',
            'activo' => 'boolean',
            'roles' => 'array',
            'zona_id' => 'integer',
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
        return Rol::tryFrom($rol) !== null && in_array($rol, $this->roles ?? [], true);
    }

    /** Bloqueado automático (intentos fallidos) o manual (con motivo): cualquiera de los dos impide el acceso. */
    public function estaBloqueada(): bool
    {
        return $this->bloqueado_manualmente || ($this->bloqueado_hasta !== null && $this->bloqueado_hasta->isFuture());
    }

    public function fichaProveedor(): HasOne
    {
        return $this->hasOne(Proveedor::class, 'usuario_id');
    }

    public function suspendidaAdministrativamente(): bool
    {
        return ! $this->activo || $this->bloqueado_manualmente;
    }

    public function estadoAdministrativo(): string
    {
        return $this->suspendidaAdministrativamente() ? 'suspendido' : ($this->estaBloqueada() ? 'bloqueado' : 'activo');
    }

    public function accesoWeb(): bool
    {
        if (! $this->activo || $this->estaBloqueada()) {
            return false;
        }

        if ($this->perfil_rol_id !== null) {
            return $this->puede('dashboard') || collect(array_keys(config('permisos')))->contains(fn ($modulo) => $this->puede($modulo));
        }

        foreach ($this->roles ?? [] as $rolValor) {
            if (Rol::tryFrom($rolValor)?->accesoWeb()) {
                return true;
            }
        }

        return false;
    }

    public function rutaInicioWeb(): string
    {
        if ($this->puede('dashboard')) {
            return 'admin.dashboard.index';
        }
        foreach (array_keys(config('permisos')) as $modulo) {
            $ruta = $modulo === 'productos' ? 'admin.produccion.productos.index' : 'admin.'.str_replace('_', '-', $modulo).'.index';
            if ($this->puede($modulo) && Route::has($ruta)) {
                return $ruta;
            }
        }

        return 'login';
    }

    /** Para mostrar u ocultar enlaces de navegación; el control real está en la matriz `permiso:` de las rutas. */
    public function puede(string $modulo, string $accion = 'ver'): bool
    {
        if ($this->perfil_rol_id !== null) {
            $perfil = DB::table('perfiles_roles')->find($this->perfil_rol_id);
            if (! $perfil || ! $perfil->activo) {
                return false;
            }
            // Solo cuentan las acciones que el módulo admite: un permiso guardado fuera de la matriz no concede nada.
            $acciones = array_intersect(json_decode($perfil->permisos, true)[$modulo] ?? [], config("modulos_roles.{$modulo}.acciones", []));

            return $accion === 'gestionar' ? count(array_intersect($acciones, ['crear', 'editar', 'eliminar'])) > 0 : in_array($accion, $acciones, true);
        }
        $accion = in_array($accion, ['crear', 'editar', 'eliminar'], true) ? 'gestionar' : $accion;
        $rolesPermitidos = config("permisos.{$modulo}.{$accion}", []);

        $oficiales = array_map(fn (Rol $rol) => $rol->value, Rol::cases());

        return collect($this->roles ?? [])->intersect($oficiales)->intersect($rolesPermitidos)->isNotEmpty();
    }
}
