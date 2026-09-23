<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f4ef">
    @include('partials.theme-init')
    <title>@yield('titulo', 'Centro operativo') · Ecolactea Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-eh-bg font-sans text-eh-text antialiased selection:bg-eh-lime">
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

    <div class="admin-shell relative flex h-dvh overflow-hidden bg-eh-bg">
        <div data-admin-menu-overlay hidden class="fixed inset-0 z-20 bg-[#0c1612]/45 backdrop-blur-sm lg:hidden"></div>

        <aside id="menu-administracion" data-admin-menu aria-label="Navegación principal"
            class="no-print sidebar-transition fixed left-0 top-0 z-30 flex h-full w-[244px] -translate-x-full flex-col bg-eh-sidebar px-4 py-5 text-white lg:static lg:z-auto lg:translate-x-0">
            <div class="flex shrink-0 items-center gap-3 px-3 pb-7">
                <a href="{{ route('admin.dashboard.index') }}" class="flex min-w-0 items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-eh-lime text-[#142820] shadow-[0_6px_20px_rgba(216,255,87,.16)]">
                        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-2.12-.74-4.07-1.97-5.61"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 8.5c.5 2 2.5 3.5 3.5 3.5s3-1.5 3.5-3.5"/>
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate font-semibold leading-tight tracking-[-.02em] text-white">Ecolactea</span>
                        <span class="block truncate text-[10px] font-medium uppercase tracking-[.2em] text-white/40">Digital</span>
                    </span>
                </a>
                <button type="button" data-admin-menu-close class="ml-auto grid h-8 w-8 place-items-center rounded-lg text-white/70 hover:bg-white/10 hover:text-white lg:hidden" aria-label="Cerrar menú">
                    <x-icon name="xMark" class="h-4 w-4" />
                </button>
            </div>

            <nav class="-mx-1 flex-1 space-y-5 overflow-y-auto px-1 pb-4 [scrollbar-color:rgba(255,255,255,.12)_transparent]">
                @foreach ($navGroups as $grupo => $items)
                    @php $itemsVisibles = collect($items)->filter(fn ($item) => $operador->puede($item['modulo'])); @endphp
                    @continue($itemsVisibles->isEmpty())
                    <div class="space-y-1">
                        <p class="px-3 pb-1 text-[10px] font-semibold uppercase tracking-[.17em] text-eh-sidebar-label">{{ $grupo }}</p>
                        @foreach ($itemsVisibles as $item)
                            @php $activo = request()->routeIs($item['patron']); @endphp
                            <a href="{{ route($item['ruta']) }}" @class([
                                'group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition-all duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80',
                                'bg-eh-sidebar-active font-medium text-[#142820] shadow-lg' => $activo,
                                'text-eh-sidebar-muted hover:bg-white/[.07] hover:text-white' => ! $activo,
                            ]) @if ($activo) aria-current="page" @endif>
                                <x-icon :name="$item['icono']" class="h-[17px] w-[17px] shrink-0" />
                                <span class="flex-1 truncate">{{ $item['etiqueta'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="mt-3 shrink-0 overflow-hidden rounded-2xl border border-white/10 bg-white/[.055] p-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-eh-lime text-xs font-bold text-[#142820]">{{ $iniciales }}</span>
                    <span class="min-w-0">
                        <span class="block truncate text-xs font-semibold text-white">{{ $operador->nombres }}</span>
                        <span class="block truncate text-[11px] text-white/40">{{ $rolVisible }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 rounded-md text-xs font-medium text-white underline decoration-white/20 underline-offset-4 hover:decoration-white/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80">
                        <x-icon name="logout" class="h-3.5 w-3.5" />
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-panel flex min-w-0 flex-1 flex-col overflow-hidden">
            <header class="no-print z-20 flex h-[76px] shrink-0 items-center justify-between gap-3 border-b border-eh-border bg-eh-bg/90 px-5 backdrop-blur-xl md:px-8 lg:px-10">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" data-admin-menu-toggle aria-controls="menu-administracion" aria-expanded="false"
                        class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-eh-ink text-white lg:hidden" aria-label="Abrir menú de administración">
                        <x-icon name="bars3" class="h-5 w-5" />
                    </button>
                    <p class="hidden min-w-0 truncate text-sm text-eh-text-muted sm:block">
                        Panel <span class="mx-2">/</span> <span class="font-medium text-eh-text">@yield('titulo', 'Centro operativo')</span>
                    </p>
                    <span class="truncate text-sm font-medium text-eh-text sm:hidden">@yield('titulo', 'Centro operativo')</span>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" data-theme-toggle class="grid h-10 w-10 place-items-center rounded-xl border border-eh-border-strong bg-eh-surface/65 text-eh-text-muted hover:bg-eh-surface hover:text-eh-text" aria-label="Cambiar tema">
                        <svg data-theme-icon="moon" class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z"/></svg>
                        <svg data-theme-icon="sun" hidden class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="4.2"/><path stroke-linecap="round" d="M12 2.5v2.3M12 19.2v2.3M4.6 4.6l1.6 1.6M17.8 17.8l1.6 1.6M2.5 12h2.3M19.2 12h2.3M4.6 19.4l1.6-1.6M17.8 6.2l1.6-1.6"/></svg>
                    </button>
                    @if ($operador->puede('comunicados'))
                        <a href="{{ route('admin.comunicados.index') }}" class="relative grid h-10 w-10 place-items-center rounded-xl border border-eh-border-strong bg-eh-surface/65 text-eh-text hover:bg-eh-surface"
                            aria-label="Comunicados{{ $notificaciones > 0 ? ' ('.$notificaciones.' publicados esta semana)' : '' }}">
                            <x-icon name="bell" class="h-[17px] w-[17px]" />
                            @if ($notificaciones > 0)
                                <span class="absolute -right-1 -top-1 grid h-4 min-w-4 place-items-center rounded-full bg-eh-coral px-1 text-[9px] font-bold text-white ring-2 ring-eh-bg">{{ min($notificaciones, 99) }}</span>
                            @endif
                        </a>
                    @endif
                    @if ($operador->puede('configuracion'))
                        <a href="{{ route('admin.configuracion.index') }}" class="ml-1 flex items-center gap-2 rounded-xl p-1.5 pr-2 hover:bg-eh-surface">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-eh-lime text-xs font-bold text-[#142820]">{{ $iniciales }}</span>
                            <span class="hidden text-left md:block">
                                <span class="block max-w-40 truncate text-xs font-semibold text-eh-text">{{ $operador->nombres }}</span>
                                <span class="block max-w-40 truncate text-[10px] text-eh-text-muted">{{ $roles->first() }}</span>
                            </span>
                        </a>
                    @else
                        <span class="ml-1 flex items-center gap-2 p-1.5 pr-2">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-eh-lime text-xs font-bold text-[#142820]">{{ $iniciales }}</span>
                            <span class="hidden text-left md:block">
                                <span class="block max-w-40 truncate text-xs font-semibold text-eh-text">{{ $operador->nombres }}</span>
                                <span class="block max-w-40 truncate text-[10px] text-eh-text-muted">{{ $roles->first() }}</span>
                            </span>
                        </span>
                    @endif
                </div>
            </header>

            <main id="contenido-principal" class="admin-content relative flex-1 overflow-y-auto">
                <div class="admin-page mx-auto max-w-[1440px] px-5 py-7 md:px-8 lg:px-10 lg:py-9">
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
                </div>
            </main>
        </div>
    </div>

    @if (session('estado'))
        <div data-toast role="status" aria-live="polite"
            class="animate-toast fixed bottom-5 right-5 z-50 flex max-w-[calc(100vw-2.5rem)] items-center gap-3 rounded-xl bg-[#142820] px-4 py-3 text-xs font-medium text-white shadow-2xl">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-eh-lime text-[#142820]">
                <x-icon name="check" class="h-3.5 w-3.5" />
            </span>
            <span>{{ session('estado') }}</span>
            <button type="button" data-toast-close class="-mr-1 ml-1 grid h-6 w-6 shrink-0 place-items-center rounded-md text-white/50 hover:bg-white/10 hover:text-white" aria-label="Cerrar aviso">
                <x-icon name="xMark" class="h-3.5 w-3.5" />
            </button>
        </div>
    @endif
</body>
</html>
