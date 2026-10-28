<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f7f6">
    @include('partials.theme-init')
    <title>@yield('titulo', 'Centro operativo') · Ecolactea Digital</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-eh-bg font-sans text-eh-text antialiased selection:bg-eh-primary-soft">
    @php
        $operador = auth('operador')->user();
        $rutaInicio = $operador->puede('dashboard') ? 'admin.dashboard.index' : 'admin.mapa-rutas.index';
        $roles = collect($operador->roles)
            ->map(fn ($rol) => \App\Domain\Usuarios\Rol::tryFrom($rol)?->etiqueta())
            ->filter();
        $rolVisible = $roles->implode(', ');
        $iniciales = collect(preg_split('/\s+/u', trim($operador->nombres)))
            ->filter()
            ->take(2)
            ->map(fn ($nombre) => mb_strtoupper(mb_substr($nombre, 0, 1)))
            ->implode('');

        $navGroups = [
            'Inicio' => [
                ['ruta' => 'admin.dashboard.index', 'patron' => 'admin.dashboard.*', 'modulo' => 'dashboard', 'etiqueta' => 'Resumen del día', 'icono' => 'home'],
            ],
            'Acopio' => [
                ['ruta' => 'admin.jornadas.index', 'patron' => ['admin.jornadas.*', 'admin.acopiadores.jornadas.*'], 'modulo' => 'acopiadores', 'etiqueta' => 'Jornadas de acopio', 'icono' => 'jornada'],
                ['ruta' => 'admin.entregas.index', 'patron' => 'admin.entregas.*', 'modulo' => 'entregas', 'etiqueta' => 'Entregas', 'icono' => 'droplets'],
                ['ruta' => 'admin.recepcion.index', 'patron' => 'admin.recepcion.*', 'modulo' => 'recepcion', 'etiqueta' => 'Recepción en planta', 'icono' => 'recepcion'],
            ],
            'Proveedores' => [
                ['ruta' => 'admin.proveedores.index', 'patron' => 'admin.proveedores.*', 'modulo' => 'proveedores', 'etiqueta' => 'Proveedores', 'icono' => 'proveedores'],
            ],
            'Calidad' => [
                ['ruta' => 'admin.calidad.index', 'patron' => 'admin.calidad.*', 'modulo' => 'calidad', 'etiqueta' => 'Calidad', 'icono' => 'beaker'],
                ['ruta' => 'admin.sanciones.index', 'patron' => 'admin.sanciones.*', 'modulo' => 'sanciones', 'etiqueta' => 'Sanciones', 'icono' => 'sancion'],
            ],
            'Liquidaciones y pagos' => [
                ['ruta' => 'admin.liquidaciones.index', 'patron' => 'admin.liquidaciones.*', 'modulo' => 'liquidaciones', 'etiqueta' => 'Liquidaciones y pagos', 'icono' => 'liquidacion'],
                ['ruta' => 'admin.tarifas.index', 'patron' => 'admin.tarifas.*', 'modulo' => 'liquidaciones', 'etiqueta' => 'Precios de leche', 'icono' => 'tarifa'],
            ],
            'Planta' => [
                ['ruta' => 'admin.produccion.index', 'patron' => 'admin.produccion.*', 'modulo' => 'produccion', 'etiqueta' => 'Producción', 'icono' => 'factory'],
                ['ruta' => 'admin.inventario.index', 'patron' => 'admin.inventario.*', 'modulo' => 'inventario', 'etiqueta' => 'Inventario', 'icono' => 'cube'],
                ['ruta' => 'admin.ventas.index', 'patron' => 'admin.ventas.*', 'modulo' => 'ventas', 'etiqueta' => 'Ventas', 'icono' => 'shoppingCart'],
            ],
            'Organización del acopio' => [
                ['ruta' => 'admin.mapa-rutas.index', 'patron' => 'admin.mapa-rutas.*', 'modulo' => 'mapa_rutas', 'etiqueta' => 'Rutas de acopio', 'icono' => 'map'],
                ['ruta' => 'admin.acopiadores.catalogo', 'patron' => 'admin.acopiadores.catalogo', 'modulo' => 'acopiadores', 'etiqueta' => 'Acopiadores', 'icono' => 'acopiador'],
                ['ruta' => 'admin.zonas-vehiculos.index', 'patron' => 'admin.zonas-vehiculos.*', 'modulo' => 'zonas_vehiculos', 'etiqueta' => 'Sectores y vehículos', 'icono' => 'truck'],
            ],
            'Comunicación' => [
                ['ruta' => 'admin.comunicados.index', 'patron' => 'admin.comunicados.*', 'modulo' => 'comunicados', 'etiqueta' => 'Comunicados', 'icono' => 'megaphone'],
            ],
            'Reportes' => [
                ['ruta' => 'admin.reportes.index', 'patron' => 'admin.reportes.*', 'modulo' => 'reportes', 'etiqueta' => 'Reportes', 'icono' => 'chartBar'],
            ],
            'Administración' => [
                ['ruta' => 'admin.usuarios.index', 'patron' => 'admin.usuarios.*', 'modulo' => 'usuarios', 'etiqueta' => 'Usuarios y accesos', 'icono' => 'usuarios'],
                ['ruta' => 'admin.configuracion.index', 'patron' => 'admin.configuracion.*', 'modulo' => 'configuracion', 'etiqueta' => 'Configuración', 'icono' => 'cog'],
                ['ruta' => 'admin.importaciones.index', 'patron' => 'admin.importaciones.*', 'modulo' => 'importaciones', 'etiqueta' => 'Importaciones', 'icono' => 'arrowUpTray'],
                ['ruta' => 'admin.auditoria.index', 'patron' => 'admin.auditoria.*', 'modulo' => 'auditoria', 'etiqueta' => 'Auditoría', 'icono' => 'shield'],
            ],
        ];

        $iconosGrupo = ['Acopio' => 'milk', 'Calidad' => 'beaker', 'Liquidaciones y pagos' => 'liquidacion', 'Planta' => 'factory', 'Organización del acopio' => 'asignaciones', 'Administración' => 'cog'];

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
            class="no-print sidebar-transition fixed left-0 top-0 z-30 flex h-full w-[272px] -translate-x-full flex-col bg-eh-sidebar px-4 py-5 text-eh-text lg:relative lg:z-30 lg:translate-x-0">
            <div class="flex shrink-0 items-center gap-3 px-3 pb-7">
                <a href="{{ route($rutaInicio) }}" aria-label="Ecolactea Digital — Inicio" class="flex min-w-0 items-center gap-3 rounded-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eh-primary">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-eh-primary-soft text-eh-primary ">
                        <x-icon name="milk" class="h-6 w-6" />
                    </span>
                    <span class="sidebar-label min-w-0">
                        <span class="block truncate font-semibold leading-tight tracking-[-.02em] text-eh-text">Ecolactea</span>
                        <span class="block truncate text-[10px] font-medium uppercase tracking-[.2em] text-eh-text-muted">Digital</span>
                    </span>
                </a>
                <button type="button" data-admin-menu-close class="ml-auto grid h-8 w-8 place-items-center rounded-lg text-eh-text-muted hover:bg-eh-surface-alt hover:text-eh-text lg:hidden" aria-label="Cerrar menú">
                    <x-icon name="xMark" class="h-4 w-4" />
                </button>
            </div>

            <nav aria-label="Secciones del panel" class="sidebar-nav -mx-1 flex-1 space-y-2 overflow-y-auto px-1 pb-4 [scrollbar-color:rgba(255,255,255,.12)_transparent]">
                @foreach ($navGroups as $grupo => $items)
                    @php
                        $itemsVisibles = collect($items)->filter(fn ($item) => $operador->puede($item['modulo']));
                        $grupoActivo = $itemsVisibles->contains(fn ($item) => request()->routeIs(...(array) $item['patron']));
                    @endphp
                    @continue($itemsVisibles->isEmpty())
                    @if ($itemsVisibles->count() > 1)
                        <details data-nav-group="{{ $loop->index }}" data-active="{{ $grupoActivo ? 'true' : 'false' }}" @if ($grupoActivo) open @endif>
                            <summary class="sidebar-group flex cursor-pointer items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-eh-text hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary" title="{{ $grupo }}">
                                <x-icon :name="$iconosGrupo[$grupo] ?? $itemsVisibles->first()['icono']" class="h-[18px] w-[18px] shrink-0" />
                                <span class="sidebar-label flex-1">{{ $grupo }}</span>
                                <x-icon name="chevronDown" class="sidebar-chevron h-3.5 w-3.5 shrink-0" />
                            </summary>
                            <div class="sidebar-children ml-5 mt-1 space-y-1 border-l border-eh-border pl-2">
                    @endif
                    @foreach ($itemsVisibles as $item)
                        @php $activo = request()->routeIs(...(array) $item['patron']); @endphp
                        <a href="{{ route($item['ruta']) }}" title="{{ $item['etiqueta'] }}" aria-label="{{ $item['etiqueta'] }}" @class([
                            'sidebar-link flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition-colors focus-visible:outline-2 focus-visible:outline-eh-primary',
                            'bg-eh-sidebar-active font-semibold text-eh-primary' => $activo,
                            'text-eh-sidebar-muted hover:bg-eh-surface-alt hover:text-eh-text' => ! $activo,
                        ]) @if ($activo) aria-current="page" @endif>
                            <x-icon :name="$item['icono']" class="h-[18px] w-[18px] shrink-0" />
                            <span class="sidebar-label flex-1">{{ $item['etiqueta'] }}</span>
                        </a>
                    @endforeach
                    @if ($itemsVisibles->count() > 1)
                            </div>
                        </details>
                    @endif
                @endforeach
            </nav>
            <button type="button" data-sidebar-collapse aria-controls="menu-administracion" aria-expanded="true" aria-label="Contraer menú lateral" title="Contraer menú lateral"
                class="sidebar-edge-toggle absolute -right-4 top-[72px] z-40 hidden h-8 w-8 items-center justify-center rounded-full border border-eh-border-strong bg-eh-sidebar text-eh-text-muted shadow-sm transition-colors hover:border-eh-primary hover:text-eh-primary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-eh-primary lg:flex">
                <x-icon name="chevronRight" class="sidebar-collapse-icon h-3.5 w-3.5 rotate-180" />
            </button>

            <div class="sidebar-account mt-3 shrink-0 overflow-hidden rounded-2xl border border-eh-border bg-eh-surface-alt p-4">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-eh-primary-soft text-xs font-bold text-eh-primary">{{ $iniciales }}</span>
                    <span class="sidebar-label min-w-0">
                        <span class="block truncate text-xs font-semibold text-eh-text">{{ $operador->nombres }}</span>
                        <span class="block truncate text-[11px] text-eh-text-muted">{{ $rolVisible }}</span>
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión" class="flex items-center gap-2 rounded-md text-xs font-medium text-eh-text underline decoration-eh-border-strong underline-offset-4 hover:decoration-eh-text-muted focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eh-primary">
                        <x-icon name="logout" class="h-3.5 w-3.5" />
                        <span class="sidebar-label">Cerrar sesión</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="admin-panel flex min-w-0 flex-1 flex-col overflow-hidden">
            <header class="no-print z-20 flex h-[68px] shrink-0 items-center justify-between gap-2 border-b border-eh-border bg-eh-surface/95 px-3 backdrop-blur-xl sm:px-5 lg:px-8">
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
                        <x-icon name="moon" data-theme-icon="moon" class="h-[17px] w-[17px]" />
                        <x-icon name="sun" data-theme-icon="sun" class="h-[17px] w-[17px]" hidden />
                    </button>
                    <button type="button" data-modal-open="preferencias-interfaz" aria-label="Personalizar apariencia" title="Personalizar apariencia" class="grid h-10 w-10 place-items-center rounded-xl text-eh-text-muted hover:bg-eh-surface-alt hover:text-eh-text">
                        <x-icon name="cog" class="h-[18px] w-[18px]" />
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
                            <span class="hidden text-left xl:block">
                                <span class="block max-w-40 truncate text-xs font-semibold text-eh-text">{{ $operador->nombres }}</span>
                                <span class="block max-w-40 truncate text-[10px] text-eh-text-muted">{{ $roles->first() }}</span>
                            </span>
                        </a>
                    @else
                        <span class="ml-1 flex items-center gap-2 p-1.5 pr-2">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-eh-lime text-xs font-bold text-[#142820]">{{ $iniciales }}</span>
                            <span class="hidden text-left xl:block">
                                <span class="block max-w-40 truncate text-xs font-semibold text-eh-text">{{ $operador->nombres }}</span>
                                <span class="block max-w-40 truncate text-[10px] text-eh-text-muted">{{ $roles->first() }}</span>
                            </span>
                        </span>
                    @endif
                </div>
            </header>

            <main id="contenido-principal" class="admin-content relative flex-1 overflow-y-auto">
                <div class="admin-page mx-auto max-w-[1440px] px-5 py-7 md:px-8 lg:px-8 lg:py-7">
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

    @include('partials.preferences')

    @if (session('estado'))
        <div data-toast role="status" aria-live="polite"
            class="animate-toast fixed bottom-5 right-5 z-50 flex max-w-[calc(100vw-2.5rem)] items-center gap-3 rounded-xl bg-[#142820] px-4 py-3 text-xs font-medium text-white shadow-2xl">
            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-eh-primary-soft text-eh-primary">
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
