<?php

namespace App\Infrastructure\Auth;

use App\Domain\Auth\AdminAuthenticatorInterface;
use App\Domain\Auth\AdminUsuario;
use App\Domain\Auth\Exceptions\CuentaInactivaException;
use App\Infrastructure\Persistence\Eloquent\AdminUser;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

final class LaravelAdminAuthenticator implements AdminAuthenticatorInterface
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {}

    public function intentar(string $email, string $password, bool $recordar = false): bool
    {
        $admin = AdminUser::query()->where('email', $email)->first();

        if ($admin !== null && ! $admin->activo) {
            throw new CuentaInactivaException;
        }

        return $this->auth->guard('admin')->attempt(
            ['email' => $email, 'password' => $password],
            $recordar,
        );
    }

    public function usuarioActual(): ?AdminUsuario
    {
        /** @var AdminUser|null $admin */
        $admin = $this->auth->guard('admin')->user();

        if ($admin === null) {
            return null;
        }

        return new AdminUsuario(
            id: $admin->id,
            nombres: $admin->nombres,
            email: $admin->email,
        );
    }

    public function cerrarSesion(): void
    {
        $this->auth->guard('admin')->logout();
    }
}
