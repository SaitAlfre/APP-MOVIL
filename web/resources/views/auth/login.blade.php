<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.theme-init')
    <title>EcolectaHuata · Iniciar sesión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-eh-text bg-eh-bg">
    <div class="flex min-h-full items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">
            <div class="mb-7 flex flex-col items-center">
                <span class="mb-3 flex size-11 items-center justify-center rounded-xl bg-eh-primary-soft">
                    <svg class="size-6 text-eh-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c3.2 3.6 5 6.7 5 9.2A5 5 0 0 1 7 12.2C7 9.7 8.8 6.6 12 3Z"/><path d="M9.5 20c2-1.8 3-3 3-5"/></svg>
                </span>
                <span class="text-[13px] font-bold tracking-[0.08em] text-eh-primary">ECOLECTAHUATA</span>
            </div>

            <div class="rounded-2xl border border-eh-border bg-eh-surface p-8 shadow-sm">
                <h1 class="mb-1.5 text-center text-[23px] font-bold text-eh-text">Iniciar sesión</h1>
                <p class="mb-7 text-center text-[13.5px] leading-relaxed text-eh-text-muted">
                    Acopio, calidad, pagos y producción en una sola plataforma
                </p>

                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-eh-red-soft px-4 py-3 text-[12.5px] font-medium text-eh-red">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="username" class="mb-1.5 block text-[13px] font-semibold text-eh-text">Código, usuario o DNI</label>
                        <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                            placeholder="Ej. admin"
                            class="block h-12 w-full rounded-xl border border-eh-border bg-eh-bg px-4 text-[14.5px] text-eh-text focus:border-eh-primary focus:ring-eh-primary">
                    </div>

                    <div>
                        <label for="pin" class="mb-1.5 block text-[13px] font-semibold text-eh-text">PIN o contraseña</label>
                        <input id="pin" name="pin" type="password" inputmode="numeric" required placeholder="••••"
                            class="block h-12 w-full rounded-xl border border-eh-border bg-eh-bg px-4 text-[14.5px] text-eh-text focus:border-eh-primary focus:ring-eh-primary">
                    </div>

                    <button type="submit"
                        class="h-12 w-full rounded-xl bg-eh-primary text-[14.5px] font-bold text-white hover:bg-eh-primary-dark">
                        Ingresar
                    </button>
                </form>
            </div>

            <p class="mt-5 text-center text-xs text-eh-text-muted">EcolectaHuata · Sistema de acopio y gestión láctea</p>
        </div>
    </div>
</body>
</html>
