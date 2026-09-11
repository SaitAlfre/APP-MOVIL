<?php

namespace App\Providers;

use App\Domain\Auditoria\AuditoriaRepositoryInterface;
use App\Domain\Auth\OperadorAuthenticatorInterface;
use App\Domain\Entregas\EntregaRepositoryInterface;
use App\Domain\Jornadas\JornadaRepositoryInterface;
use App\Domain\Proveedores\ProveedorRepositoryInterface;
use App\Domain\Seguimiento\SeguimientoRepositoryInterface;
use App\Domain\Usuarios\UsuarioRepositoryInterface;
use App\Domain\Vehiculos\VehiculoRepositoryInterface;
use App\Domain\Zonas\ZonaRepositoryInterface;
use App\Infrastructure\Auth\LaravelOperadorAuthenticator;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentAuditoriaRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentEntregaRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentJornadaRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentProveedorRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentSeguimientoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentUsuarioRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentVehiculoRepository;
use App\Infrastructure\Persistence\Eloquent\Repositories\EloquentZonaRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProveedorRepositoryInterface::class, EloquentProveedorRepository::class);
        $this->app->bind(ZonaRepositoryInterface::class, EloquentZonaRepository::class);

        $this->app->bind(UsuarioRepositoryInterface::class, EloquentUsuarioRepository::class);
        $this->app->bind(VehiculoRepositoryInterface::class, EloquentVehiculoRepository::class);
        $this->app->bind(JornadaRepositoryInterface::class, EloquentJornadaRepository::class);
        $this->app->bind(EntregaRepositoryInterface::class, EloquentEntregaRepository::class);
        $this->app->bind(AuditoriaRepositoryInterface::class, EloquentAuditoriaRepository::class);
        $this->app->bind(SeguimientoRepositoryInterface::class, EloquentSeguimientoRepository::class);
        $this->app->bind(OperadorAuthenticatorInterface::class, LaravelOperadorAuthenticator::class);
    }
}
