<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('partials.theme-init')
    <title>@yield('titulo', 'Ecolecta') · Acopiador</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-eh-text bg-eh-bg">
    <div class="mx-auto min-h-full max-w-md bg-eh-surface shadow-sm">
        <header class="flex items-center justify-between border-b border-eh-border px-4 py-3">
            <a href="{{ route('acopiador.home') }}" class="flex items-center gap-2 font-bold text-eh-text">
                <svg class="size-[18px] text-eh-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c3.2 3.6 5 6.7 5 9.2A5 5 0 0 1 7 12.2C7 9.7 8.8 6.6 12 3Z"/></svg>
                EcolectaHuata
            </a>
            <div class="flex items-center gap-3">
                <button type="button" data-theme-toggle class="flex size-8 items-center justify-center rounded-full text-eh-text-muted hover:bg-eh-surface-alt">
                    <svg data-theme-icon="moon" class="size-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg>
                    <svg data-theme-icon="sun" hidden class="size-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.3M12 19.2v2.3M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg>
                    <span data-theme-label class="sr-only">Cambiar tema</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-eh-red">Salir</button>
                </form>
            </div>
        </header>

        <main class="px-4 py-5">
            @if (session('estado'))
                <div class="mb-4 rounded-xl bg-eh-primary-soft px-4 py-3 text-sm font-medium text-eh-primary">
                    {{ session('estado') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-eh-red-soft px-4 py-3 text-sm font-medium text-eh-red">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>
</body>
</html>
