<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.theme-init')
    <title>@yield('titulo', 'Ecolecta') · Panel administrativo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-eh-text bg-eh-bg">
    <div class="flex min-h-full">
        <aside class="hidden w-64 shrink-0 flex-col border-r border-eh-border bg-eh-surface p-4 md:flex">
            <div class="mb-5 flex items-center gap-2.5 px-2 pt-1">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-[10px] bg-eh-primary-soft">
                    <svg class="size-4.5 text-eh-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3c3.2 3.6 5 6.7 5 9.2A5 5 0 0 1 7 12.2C7 9.7 8.8 6.6 12 3Z"/><path d="M9.5 20c2-1.8 3-3 3-5"/></svg>
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-[15px] font-bold text-eh-text">EcolectaHuata</span>
                    <span class="text-[10.5px] text-eh-text-muted">Ecolactea Digital</span>
                </span>
            </div>

            <div class="mb-3 flex items-center gap-2.5 rounded-xl bg-eh-surface-alt px-2.5 py-2.5">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-eh-primary text-[12px] font-bold text-white">
                    {{ strtoupper(substr(auth('operador')->user()->nombres, 0, 2)) }}
                </span>
                <span class="flex flex-col overflow-hidden leading-tight">
                    <span class="truncate text-[12.5px] font-semibold text-eh-text">{{ auth('operador')->user()->nombres }}</span>
                    <span class="text-[11px] text-eh-text-muted">Administrador</span>
                </span>
            </div>

            <nav class="flex flex-1 flex-col gap-0.5 overflow-y-auto text-[13px]">
                <a href="{{ route('admin.proveedores.index') }}"
                    @class([
                        'flex items-center gap-3 rounded-[10px] px-3 py-2.5',
                        'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.proveedores.*'),
                        'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.proveedores.*'),
                    ])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.6 2.7-6 6-6s6 2.4 6 6"/><circle cx="17.5" cy="8.5" r="2.6"/><path d="M15.5 20c.2-2.6 1.7-4.6 3.8-5.2"/></svg>
                    Proveedores
                </a>

                <a href="{{ route('admin.acopiadores.index') }}"
                    @class(['flex items-center gap-3 rounded-[10px] px-3 py-2.5', 'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.acopiadores.*'), 'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.acopiadores.*')])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="7" width="12" height="9" rx="1.4"/><path d="M14.5 10h4l3 3v3h-7z"/><circle cx="7" cy="18" r="1.8"/><circle cx="17.5" cy="18" r="1.8"/></svg>
                    Acopiadores
                </a>
                <a href="{{ route('admin.zonas-vehiculos.index') }}"
                    @class(['flex items-center gap-3 rounded-[10px] px-3 py-2.5', 'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.zonas-vehiculos.*'), 'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.zonas-vehiculos.*')])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-6.5 7-11.5a7 7 0 0 0-14 0C5 14.5 12 21 12 21Z"/><circle cx="12" cy="9.4" r="2.4"/></svg>
                    Zonas y vehículos
                </a>
                <a href="{{ route('admin.calidad.index') }}"
                    @class(['flex items-center gap-3 rounded-[10px] px-3 py-2.5', 'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.calidad.*'), 'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.calidad.*')])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 3h5"/><path d="M10.5 3v5.5L5.8 17a2 2 0 0 0 1.8 3h8.8a2 2 0 0 0 1.8-3l-4.7-8.5V3"/><path d="M8 15h8"/></svg>
                    Calidad
                </a>
                <a href="{{ route('admin.liquidaciones.index') }}"
                    @class(['flex items-center gap-3 rounded-[10px] px-3 py-2.5', 'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.liquidaciones.*'), 'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.liquidaciones.*')])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="6" width="19" height="12" rx="2"/><circle cx="12" cy="12" r="2.6"/><path d="M6 6v12M18 6v12"/></svg>
                    Liquidaciones
                </a>
                <a href="{{ route('admin.produccion.index') }}"
                    @class(['flex items-center gap-3 rounded-[10px] px-3 py-2.5', 'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.produccion.*'), 'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.produccion.*')])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20V10l5 3.5V10l5 3.5V10l5 3.5V20Z"/><path d="M3 20h18"/></svg>
                    Producción
                </a>
                <a href="{{ route('admin.reportes.index') }}"
                    @class(['flex items-center gap-3 rounded-[10px] px-3 py-2.5', 'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.reportes.*'), 'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.reportes.*')])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V9M11 20V4M18 20v-7"/><path d="M2.5 20h19"/></svg>
                    Reportes
                </a>
                <a href="{{ route('admin.auditoria.index') }}"
                    @class(['flex items-center gap-3 rounded-[10px] px-3 py-2.5', 'bg-eh-primary-soft font-bold text-eh-primary' => request()->routeIs('admin.auditoria.*'), 'font-medium text-eh-text-muted hover:bg-eh-surface-alt' => ! request()->routeIs('admin.auditoria.*')])>
                    <svg class="size-[19px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5 4.5 6.5v5.2c0 4.8 3.1 7.7 7.5 9 4.4-1.3 7.5-4.2 7.5-9V6.5Z"/><path d="m9 12 2 2 4-4"/></svg>
                    Auditoría
                </a>
            </nav>

            <div class="mt-3 flex flex-col gap-1 border-t border-eh-border pt-3">
                <button type="button" data-theme-toggle
                    class="flex items-center gap-3 rounded-[10px] px-3 py-2 text-[13px] font-medium text-eh-text-muted hover:bg-eh-surface-alt">
                    <svg data-theme-icon="moon" class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg>
                    <svg data-theme-icon="sun" hidden class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2.3M12 19.2v2.3M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg>
                    <span data-theme-label>Modo oscuro</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-[10px] px-3 py-2 text-[13px] font-medium text-eh-text-muted hover:bg-eh-surface-alt">
                        <svg class="size-[18px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 17v1a2.5 2.5 0 0 1-2.5 2.5h-6A2.5 2.5 0 0 1 4 18V6a2.5 2.5 0 0 1 2.5-2.5h6A2.5 2.5 0 0 1 15 6v1"/><path d="M9.5 12h11m0 0-3.2-3.2M20.5 12l-3.2 3.2"/></svg>
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-eh-border bg-eh-surface px-4 py-3 md:hidden">
                <span class="text-[15px] font-bold text-eh-text">EcolectaHuata</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-eh-red">Salir</button>
                </form>
            </header>

            <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8 md:px-10">
                @if (session('estado'))
                    <div class="mb-4 rounded-xl bg-eh-primary-soft px-4 py-3 text-sm font-medium text-eh-primary">
                        {{ session('estado') }}
                    </div>
                @endif

                @yield('contenido')
            </main>
        </div>
    </div>
</body>
</html>
