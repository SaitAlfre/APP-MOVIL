<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f6f1">
    @include('partials.theme-init')
    <title>Ecolactea Digital · Iniciar sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-eh-bg font-sans text-eh-text antialiased">
    <main class="flex min-h-screen flex-col items-center justify-center p-6">
        <div class="w-full max-w-sm rounded-2xl border border-eh-border bg-eh-surface p-8 shadow-sm">
            <div class="mb-8 flex flex-col items-center">
                <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-eh-primary">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 8.5c.5 2 2.5 3.5 3.5 3.5s3-1.5 3.5-3.5"/>
                    </svg>
                </span>
                <div class="text-center">
                    <p class="text-lg font-bold tracking-wide text-eh-text">ECOLACTEA DIGITAL</p>
                    <p class="mt-0.5 text-xs text-eh-text-muted">Sistema de acopio lácteo · Huata, Puno</p>
                </div>
            </div>

            <h1 class="sr-only">Iniciar sesión</h1>

            <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-4" data-once>
                @csrf

                <div class="flex flex-col gap-1">
                    <label for="username" class="text-sm font-medium text-eh-text">Código, usuario o DNI</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                        autocomplete="username" placeholder="ADM-001 o 29876543"
                        @if ($errors->any()) aria-invalid="true" aria-describedby="login-error" @endif
                        class="rounded-xl border border-eh-border bg-eh-surface px-3 py-2.5 text-sm text-eh-text transition-colors focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                </div>

                <div class="flex flex-col gap-1">
                    <label for="pin" class="text-sm font-medium text-eh-text">PIN o contraseña</label>
                    <div class="relative">
                        <input id="pin" name="pin" type="password" inputmode="numeric" required autocomplete="current-password" placeholder="••••••"
                            @if ($errors->any()) aria-invalid="true" aria-describedby="login-error" @endif
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2.5 pr-10 text-sm text-eh-text transition-colors focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        <button type="button" data-password-toggle aria-label="Mostrar PIN" aria-pressed="false"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-eh-text-muted hover:text-eh-text">
                            <x-icon name="eye" class="h-4 w-4" data-password-icon="eye" />
                            <x-icon name="eyeOff" class="h-4 w-4" data-password-icon="eyeOff" hidden />
                        </button>
                    </div>
                </div>

                @if ($errors->any())
                    <div id="login-error" role="alert" aria-live="assertive"
                        class="flex items-center gap-2 rounded-xl border border-eh-red/30 bg-eh-red-soft px-3 py-2.5 text-sm text-eh-red">
                        <x-icon name="exclamation" class="h-4 w-4 shrink-0" />
                        {{ $errors->first() }}
                    </div>
                @endif

                <button type="submit"
                    class="flex items-center justify-center gap-2 rounded-xl bg-eh-primary py-2.5 text-sm font-semibold text-white transition-colors hover:bg-eh-primary-dark disabled:opacity-60">
                    Ingresar
                </button>
            </form>

            <p class="mt-6 text-center text-xs text-eh-text-muted">© {{ now()->year }} Ecolactea Digital · Sistema de acopio y gestión láctea</p>
        </div>
    </main>
</body>
</html>
