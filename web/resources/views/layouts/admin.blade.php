<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f6f1">
    @include('partials.theme-init')
    <title>@yield('titulo', 'Centro operativo') · Ecolactea Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-eh-bg font-sans text-eh-text antialiased">
    @php
        $operador = auth('operador')->user();
        $roles = collect($operador->roles)
            ->map(fn ($rol) => \App\Domain\Usuarios\Rol::tryFrom($rol)?->etiqueta())
            ->filter();
        $rolVisible = $roles->implode(', ');
        $iniciales = collect(preg_split('/\s+/u', trim($operador->nombres)))
            ->filter()
            ->take(2)
            ->map(fn ($nombre) => mb_strtoupper(mb_substr($nombre, 0, 1)))
            ->implode('');

        /**
         * Navegación del panel: mismos grupos, orden, etiquetas e iconos que el
         * diseño Ecolactea Digital. Cada ítem se muestra solo si el rol autenticado
         * tiene permiso de lectura sobre el módulo.
         */
        $navGroups = [
            'Principal' => [
                ['ruta' => 'admin.dashboard.index', 'patron' => 'admin.dashboard.*', 'modulo' => 'dashboard', 'etiqueta' => 'Centro operativo', 'icono' => 'home'],
            ],
            'Administración' => [
                ['ruta' => 'admin.usuarios.index', 'patron' => 'admin.usuarios.*', 'modulo' => 'usuarios', 'etiqueta' => 'Usuarios y roles', 'icono' => 'users'],
                ['ruta' => 'admin.acopiadores.catalogo', 'patron' => 'admin.acopiadores.catalogo', 'modulo' => 'acopiadores', 'etiqueta' => 'Acopiadores', 'icono' => 'truck'],
                ['ruta' => 'admin.proveedores.index', 'patron' => 'admin.proveedores.*', 'modulo' => 'proveedores', 'etiqueta' => 'Proveedores', 'icono' => 'userCircle'],
                ['ruta' => 'admin.zonas-vehiculos.index', 'patron' => 'admin.zonas-vehiculos.*', 'modulo' => 'zonas_vehiculos', 'etiqueta' => 'Zonas, rutas y vehículos', 'icono' => 'map'],
            ],
            'Operaciones' => [
                ['ruta' => 'admin.jornadas.index', 'patron' => 'admin.jornadas.*', 'modulo' => 'acopiadores', 'etiqueta' => 'Jornadas de acopio', 'icono' => 'play'],
                ['ruta' => 'admin.recepcion.index', 'patron' => 'admin.recepcion.*', 'modulo' => 'recepcion', 'etiqueta' => 'Recepción en planta', 'icono' => 'arrowPath'],
                ['ruta' => 'admin.entregas.index', 'patron' => 'admin.entregas.*', 'modulo' => 'entregas', 'etiqueta' => 'Entregas', 'icono' => 'droplets'],
                ['ruta' => 'admin.calidad.index', 'patron' => 'admin.calidad.*', 'modulo' => 'calidad', 'etiqueta' => 'Calidad', 'icono' => 'beaker'],
                ['ruta' => 'admin.sanciones.index', 'patron' => 'admin.sanciones.*', 'modulo' => 'sanciones', 'etiqueta' => 'Sanciones', 'icono' => 'exclamation'],
            ],
            'Finanzas' => [
                ['ruta' => 'admin.liquidaciones.index', 'patron' => 'admin.liquidaciones.*', 'modulo' => 'liquidaciones', 'etiqueta' => 'Liquidaciones y pagos', 'icono' => 'banknotes'],
            ],
            'Planta' => [
                ['ruta' => 'admin.produccion.index', 'patron' => 'admin.produccion.*', 'modulo' => 'produccion', 'etiqueta' => 'Producción', 'icono' => 'factory'],
                ['ruta' => 'admin.inventario.index', 'patron' => 'admin.inventario.*', 'modulo' => 'inventario', 'etiqueta' => 'Inventario', 'icono' => 'cube'],
                ['ruta' => 'admin.ventas.index', 'patron' => 'admin.ventas.*', 'modulo' => 'ventas', 'etiqueta' => 'Ventas', 'icono' => 'shoppingCart'],
            ],
            'Comunicación' => [
                ['ruta' => 'admin.comunicados.index', 'patron' => 'admin.comunicados.*', 'modulo' => 'comunicados', 'etiqueta' => 'Comunicados', 'icono' => 'megaphone'],
            ],
            'Análisis' => [
                ['ruta' => 'admin.reportes.index', 'patron' => 'admin.reportes.*', 'modulo' => 'reportes', 'etiqueta' => 'Reportes', 'icono' => 'chartBar'],
            ],
            'Sistema' => [
                ['ruta' => 'admin.importaciones.index', 'patron' => 'admin.importaciones.*', 'modulo' => 'importaciones', 'etiqueta' => 'Importaciones', 'icono' => 'arrowUpTray'],
                ['ruta' => 'admin.auditoria.index', 'patron' => 'admin.auditoria.*', 'modulo' => 'auditoria', 'etiqueta' => 'Auditoría', 'icono' => 'shield'],
                ['ruta' => 'admin.configuracion.index', 'patron' => 'admin.configuracion.*', 'modulo' => 'configuracion', 'etiqueta' => 'Configuración', 'icono' => 'cog'],
                ['ruta' => 'admin.design-system.index', 'patron' => 'admin.design-system.*', 'modulo' => 'configuracion', 'etiqueta' => 'Design System', 'icono' => 'tableCells'],
            ],
        ];

        $notificaciones = $operador->puede('comunicados')
            ? \App\Infrastructure\Persistence\Eloquent\Comunicado::where('estado', 'publicado')
                ->where('publicado_en', '>=', now()->subDays(7))
                ->count()
            : 0;
    @endphp

    <a href="#contenido-principal" class="sr-only z-[70] rounded-xl bg-eh-surface px-4 py-2 font-bold text-eh-primary shadow-lg focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Saltar al contenido principal</a>

    <div class="admin-shell flex h-screen overflow-hidden bg-eh-bg">
        <div data-admin-menu-overlay hidden class="fixed inset-0 z-20 bg-black/40 lg:hidden"></div>

        <aside id="menu-administracion" data-admin-menu aria-label="Navegación principal"
            class="no-print sidebar-transition fixed left-0 top-0 z-30 flex h-full w-64 -translate-x-full flex-col bg-eh-sidebar lg:static lg:z-auto lg:translate-x-0">
            <div class="flex shrink-0 items-center gap-3 border-b border-white/10 px-5 py-5">
                <a href="{{ route('admin.dashboard.index') }}" class="flex min-w-0 items-center gap-3 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-eh-sidebar-active">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 8.5c.5 2 2.5 3.5 3.5 3.5s3-1.5 3.5-3.5"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-bold leading-tight tracking-wide text-white">ECOLACTEA</span>
                        <span class="block truncate text-xs font-medium text-eh-sidebar-label">DIGITAL</span>
                    </span>
                </a>
                <button type="button" data-admin-menu-close class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg text-white/80 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar menú">
                    <x-icon name="xMark" class="h-4 w-4" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-3">
                @foreach ($navGroups as $grupo => $items)
                    @php $itemsVisibles = collect($items)->filter(fn ($item) => $operador->puede($item['modulo'])); @endphp
                    @continue($itemsVisibles->isEmpty())
                    <div class="mb-2">
                        <p class="px-2 py-1.5 text-[10px] font-semibold uppercase tracking-widest text-eh-sidebar-label">{{ $grupo }}</p>
                        @foreach ($itemsVisibles as $item)
                            @php $activo = request()->routeIs($item['patron']); @endphp
                            <a href="{{ route($item['ruta']) }}" @class([
                                'mb-0.5 flex w-full items-center gap-2.5 rounded-lg px-3 py-2 text-left text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80',
                                'bg-eh-sidebar-active font-medium text-white' => $activo,
                                'text-eh-sidebar-muted hover:bg-white/10 hover:text-white' => ! $activo,
                            ]) @if ($activo) aria-current="page" @endif>
                                <x-icon :name="$item['icono']" class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ $item['etiqueta'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="shrink-0 border-t border-white/10 px-4 py-3">
                <div class="mb-2 flex items-center gap-3">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-eh-sidebar-active text-xs font-bold text-white">{{ $iniciales }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-white">{{ $operador->nombres }}</span>
                        <span class="block truncate text-xs text-eh-sidebar-label">{{ $rolVisible }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-eh-sidebar-muted transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <x-icon name="logout" class="h-4 w-4" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-panel flex min-w-0 flex-1 flex-col overflow-hidden">
            <header class="no-print flex shrink-0 items-center gap-3 border-b border-eh-border bg-eh-surface px-4 py-3">
                <button type="button" data-admin-menu-toggle aria-controls="menu-administracion" aria-expanded="false"
                    class="rounded-lg p-2 text-eh-text-muted hover:bg-eh-surface-alt lg:hidden" aria-label="Abrir menú de administración">
                    <x-icon name="bars3" class="h-5 w-5" />
                </button>
                <span class="truncate text-sm font-medium text-eh-text">@yield('titulo', 'Centro operativo')</span>
                <div class="ml-auto flex items-center gap-2">
                    <button type="button" data-theme-toggle class="rounded-lg p-2 text-eh-text-muted hover:bg-eh-surface-alt" aria-label="Cambiar tema">
                        <svg data-theme-icon="moon" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg>
                        <svg data-theme-icon="sun" hidden class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path stroke-linecap="round" d="M12 2.5v2.3M12 19.2v2.3M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg>
                    </button>
                    @if ($operador->puede('comunicados'))
                        <a href="{{ route('admin.comunicados.index') }}" class="relative rounded-lg p-2 text-eh-text-muted hover:bg-eh-surface-alt"
                            aria-label="Comunicados{{ $notificaciones > 0 ? ' ('.$notificaciones.' publicados esta semana)' : '' }}">
                            <x-icon name="bell" class="h-5 w-5" />
                            @if ($notificaciones > 0)
                                <span class="absolute right-1.5 top-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-eh-red text-[9px] font-bold text-white">{{ min($notificaciones, 99) }}</span>
                            @endif
                        </a>
                    @endif
                    @if ($operador->puede('configuracion'))
                        <a href="{{ route('admin.configuracion.index') }}" class="flex items-center gap-2 rounded-xl px-3 py-1.5 transition-colors hover:bg-eh-surface-alt">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-eh-primary text-xs font-bold text-white">{{ $iniciales }}</span>
                            <span class="hidden text-sm font-medium text-eh-text sm:block">{{ $operador->nombres }}</span>
                        </a>
                    @else
                        <span class="flex items-center gap-2 px-3 py-1.5">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-eh-primary text-xs font-bold text-white">{{ $iniciales }}</span>
                            <span class="hidden text-sm font-medium text-eh-text sm:block">{{ $operador->nombres }}</span>
                        </span>
                    @endif
                </div>
            </header>

            <main id="contenido-principal" class="admin-content flex-1 overflow-y-auto p-4 sm:p-6">
                @if (session('estado'))
                    <x-ui.alert type="success" class="mb-5" aria-live="polite">{{ session('estado') }}</x-ui.alert>
                @endif

                @if (session('error'))
                    <x-ui.alert type="error" class="mb-5">{{ session('error') }}</x-ui.alert>
                @endif

                @if ($errors->any())
                    <x-ui.alert type="error" title="Revisa los datos ingresados." class="mb-5">
                        <ul class="mt-1 list-disc space-y-0.5 pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                @endif

                @yield('contenido')
            </main>
        </div>
    </div>
</body>
</html>
