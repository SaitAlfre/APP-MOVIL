@extends('layouts.admin')
@section('titulo', 'Roles y permisos')
@section('contenido')
@php
    $esPerfil = $seleccion['tipo'] === 'perfil';
    $rol = $seleccion['rol'];
    $totalPermisos = collect($categorias)->sum(fn ($categoria) => count($categoria['permisos']));
    $activos = collect($categorias)->flatMap(fn ($categoria) => $categoria['permisos'])
        ->filter(fn ($permiso) => in_array($permiso['accion'], $permisos[$permiso['modulo']] ?? [], true))->count();
    $usuariosRol = $esPerfil ? (int) $rol->usuarios_count : $rol->usuarios;
    $totalRoles = $rolesSistema->count() + $perfiles->total();
    $filtros = request()->only(['buscar', 'estado', 'page', 'por_pagina']);
    $iniciales = fn (string $nombre) => collect(preg_split('/\s+/u', trim($nombre)))->filter()->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('');
@endphp

<x-ui.page-header title="Roles y permisos" :description="$totalRoles.' roles — activa los permisos por categoría para cada rol.'">
    <x-slot:actions>
        <x-ui.btn :href="route('admin.formularios.index')" variant="secondary" icon="documentText">Formularios</x-ui.btn>
        <x-ui.btn data-modal-open="nuevo-rol" icon="plus">Añadir rol</x-ui.btn>
    </x-slot:actions>
</x-ui.page-header>

@error('rol')<x-ui.alert type="error" class="mb-4">{{ $message }}</x-ui.alert>@enderror

<div class="grid gap-5 lg:grid-cols-[320px_minmax(0,1fr)]">
    <aside class="space-y-4" aria-label="Roles">
        <form id="filtros-roles" action="{{ route('admin.roles.index') }}" method="GET" data-live-filter class="space-y-2">
            <input type="hidden" name="estado" value="{{ request('estado') }}">
            <x-ui.search name="buscar" label="Buscar roles" :value="request('buscar')" placeholder="Buscar un rol…" maxlength="100" autocomplete="off" />
            <x-ui.status-tabs name="estado" :options="['' => 'Todos', 'activo' => 'Activos', 'inactivo' => 'Inactivos']" :value="request('estado')" form-id="filtros-roles" />
        </form>

        <section aria-labelledby="titulo-personalizados">
            <h2 id="titulo-personalizados" class="mb-2 text-xs font-semibold uppercase tracking-wide text-eh-text-muted">Roles personalizados · {{ $perfiles->total() }}</h2>
            @if ($perfiles->isEmpty())
                <p class="rounded-xl border border-dashed border-eh-border p-4 text-sm text-eh-text-muted">No hay roles para mostrar. Crea uno con «Añadir rol» o cambia la búsqueda.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($perfiles as $perfil)
                        @php
                            $elegido = $esPerfil && (int) $rol->id === (int) $perfil->id;
                            $modulosCantidad = collect(json_decode($perfil->permisos, true))->filter(fn ($acciones) => in_array('ver', $acciones, true))->count();
                        @endphp
                        <li @class(['group relative rounded-2xl border bg-eh-surface p-4 transition', 'border-eh-primary ring-1 ring-eh-primary' => $elegido, 'border-eh-border hover:border-eh-border-strong' => ! $elegido])>
                            <a href="{{ route('admin.roles.index', [...$filtros, 'rol' => $perfil->id]) }}" class="block pr-8 focus-visible:outline-none" @if ($elegido) aria-current="true" @endif>
                                <span class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-eh-text">{{ $perfil->nombre }}</span>
                                    @unless ($perfil->activo)<x-ui.badge variant="gray" label="Inactivo" />@endunless
                                </span>
                                <span class="mt-1 block text-xs text-eh-text-muted">{{ $perfil->base ? 'Heredada: '.\App\Domain\Usuarios\Rol::from($perfil->base)->etiqueta() : 'Solo panel web' }} · {{ $modulosCantidad }} {{ $modulosCantidad === 1 ? 'módulo' : 'módulos' }}</span>
                                <span class="mt-2 flex items-center gap-1 text-xs text-eh-text-muted"><x-icon name="users" class="h-3.5 w-3.5" /> {{ $perfil->usuarios_count }} {{ (int) $perfil->usuarios_count === 1 ? 'usuario' : 'usuarios' }}</span>
                            </a>
                            <span class="absolute right-3 top-3">
                                @if ($elegido)
                                    <span class="grid h-6 w-6 place-items-center rounded-full bg-eh-primary text-white" aria-hidden="true"><x-icon name="check" class="h-3.5 w-3.5" /></span>
                                @elseif (! $perfil->usuarios_count)
                                    <form method="POST" action="{{ route('admin.roles.destroy', $perfil->id) }}" data-confirm="¿Eliminar el rol {{ $perfil->nombre }} y su formulario? Esta acción no se puede deshacer." data-once>
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="grid h-7 w-7 place-items-center rounded-lg text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red" aria-label="Eliminar rol {{ $perfil->nombre }}"><x-icon name="trash" class="h-4 w-4" /></button>
                                    </form>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
                @if ($perfiles->hasPages())
                    <div class="mt-3">{{ $perfiles->onEachSide(0)->links() }}</div>
                @endif
            @endif
        </section>

        <section aria-labelledby="titulo-sistema">
            <h2 id="titulo-sistema" class="mb-2 text-xs font-semibold uppercase tracking-wide text-eh-text-muted">Roles del sistema · predefinidos</h2>
            <ul class="space-y-2">
                @foreach ($rolesSistema as $oficial)
                    @php $elegido = ! $esPerfil && $rol->clave === $oficial->clave; @endphp
                    <li @class(['relative rounded-2xl border bg-eh-surface p-4 transition', 'border-eh-primary ring-1 ring-eh-primary' => $elegido, 'border-eh-border hover:border-eh-border-strong' => ! $elegido])>
                        <a href="{{ route('admin.roles.index', [...$filtros, 'sistema' => $oficial->clave]) }}" class="block pr-8 focus-visible:outline-none" @if ($elegido) aria-current="true" @endif>
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-eh-text">{{ $oficial->nombre }}</span>
                                <span class="inline-flex items-center gap-1 rounded-md border border-eh-border px-1.5 py-0.5 text-[10px] font-medium text-eh-text-muted"><x-icon name="shield" class="h-3 w-3" /> Rol del sistema</span>
                            </span>
                            <span class="mt-1 block text-xs text-eh-text-muted">{{ $oficial->descripcion }}</span>
                            <span class="mt-2 flex items-center gap-1 text-xs text-eh-text-muted"><x-icon name="users" class="h-3.5 w-3.5" /> {{ $oficial->usuarios }} {{ $oficial->usuarios === 1 ? 'usuario' : 'usuarios' }}</span>
                        </a>
                        @if ($elegido)
                            <span class="absolute right-3 top-3 grid h-6 w-6 place-items-center rounded-full bg-eh-primary text-white" aria-hidden="true"><x-icon name="check" class="h-3.5 w-3.5" /></span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    </aside>

    <section class="min-w-0 space-y-4" aria-label="Permisos del rol elegido">
        <div class="flex flex-wrap items-start justify-between gap-4 rounded-2xl border border-eh-border bg-eh-surface p-5">
            <div class="flex min-w-0 flex-1 items-start gap-3">
                <span aria-hidden="true" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-eh-primary-soft text-sm font-semibold text-eh-primary">{{ $iniciales($rol->nombre) }}</span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-semibold">{{ $rol->nombre }}</h2>
                        @if ($esPerfil)
                            <x-ui.badge :variant="$rol->activo ? 'green' : 'gray'" :label="$rol->activo ? 'Activo' : 'Inactivo'" />
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md border border-eh-border px-1.5 py-0.5 text-[10px] font-medium text-eh-text-muted"><x-icon name="shield" class="h-3 w-3" /> Rol del sistema</span>
                        @endif
                    </div>
                    <p class="mt-1 text-sm text-eh-text-muted">
                        {{ $esPerfil ? 'Rol personalizado del panel web. Los cambios se aplican a todos sus usuarios; las funciones de la app móvil se asignan en cada cuenta.' : $rol->descripcion }}
                    </p>
                    @if ($esPerfil)
                        <div class="mt-3 flex flex-wrap gap-2">
                            <x-ui.btn size="sm" variant="outline" icon="pencil" data-modal-open="datos-rol">Nombre y estado</x-ui.btn>
                            <x-ui.btn size="sm" variant="ghost" icon="documentText" :href="route('admin.formularios.edit', $rol->id)">Formulario del rol</x-ui.btn>
                        </div>
                    @endif
                </div>
            </div>
            <div class="shrink-0 text-right">
                <p class="mono text-2xl font-semibold"><span data-permisos-total>{{ $activos }}</span><span class="text-base font-medium text-eh-text-muted"> / {{ $totalPermisos }}</span></p>
                <p class="text-xs text-eh-text-muted">permisos · {{ $usuariosRol }} {{ $usuariosRol === 1 ? 'usuario' : 'usuarios' }}</p>
            </div>
        </div>

        @unless ($esPerfil)
            <x-ui.alert type="info">Los roles del sistema son fijos: puedes revisar sus permisos, pero no cambiarlos. Para un acceso distinto, crea un rol personalizado.</x-ui.alert>
        @endunless

        <form method="POST" action="{{ $esPerfil ? route('admin.roles.update', $rol->id) : '#' }}" data-permisos-rol data-once class="space-y-4" @unless ($esPerfil) onsubmit="return false" @endunless>
            @if ($esPerfil)
                @csrf
                @method('PUT')
                <input type="hidden" name="nombre" value="{{ $rol->nombre }}">
                <input type="hidden" name="activo" value="{{ $rol->activo ? 1 : 0 }}">
                <input type="hidden" name="permisos_presentes" value="1">
            @endif
            @foreach ($categorias as $categoria)
                @php $activosCategoria = collect($categoria['permisos'])->filter(fn ($permiso) => in_array($permiso['accion'], $permisos[$permiso['modulo']] ?? [], true))->count(); @endphp
                <div data-categoria class="rounded-2xl border border-eh-border bg-eh-surface">
                    <div class="flex items-center justify-between gap-3 border-b border-eh-border px-5 py-4">
                        <h3 class="flex items-center gap-3 font-semibold">
                            <span aria-hidden="true" class="grid h-9 w-9 place-items-center rounded-xl bg-eh-surface-alt text-eh-primary"><x-icon :name="$categoria['icono']" class="h-4 w-4" /></span>
                            {{ $categoria['nombre'] }}
                        </h3>
                        <div class="flex items-center gap-3">
                            <span data-categoria-conteo class="mono text-xs text-eh-text-muted">{{ $activosCategoria }} / {{ count($categoria['permisos']) }}</span>
                            @if ($esPerfil)
                                <label class="relative inline-flex cursor-pointer items-center" title="Activar o quitar todos los permisos de {{ $categoria['nombre'] }}">
                                    <input type="checkbox" data-categoria-todo class="peer sr-only" @checked($activosCategoria === count($categoria['permisos']))>
                                    <span class="sr-only">Todos los permisos de {{ $categoria['nombre'] }}</span>
                                    <span aria-hidden="true" class="relative h-5 w-9 rounded-full bg-eh-border-strong transition after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-eh-primary peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-eh-primary"></span>
                                </label>
                            @endif
                        </div>
                    </div>
                    <ul class="divide-y divide-eh-border">
                        @foreach ($categoria['permisos'] as $permiso)
                            @php $id = 'permiso-'.$permiso['modulo'].'-'.$permiso['accion']; @endphp
                            <li class="flex items-center justify-between gap-4 px-5 py-3">
                                <label for="{{ $id }}" class="min-w-0 cursor-pointer">
                                    <span class="block text-sm font-medium first-letter:uppercase">{{ $permiso['titulo'] }}</span>
                                    <span class="block text-xs text-eh-text-muted">{{ $permiso['descripcion'] }}</span>
                                </label>
                                <span class="relative inline-flex items-center">
                                    <input id="{{ $id }}" type="checkbox" class="peer sr-only"
                                        @if ($esPerfil) name="permisos[{{ $permiso['modulo'] }}][]" @else disabled @endif
                                        value="{{ $permiso['accion'] }}" data-modulo="{{ $permiso['modulo'] }}" data-accion="{{ $permiso['accion'] }}"
                                        @checked(in_array($permiso['accion'], $permisos[$permiso['modulo']] ?? [], true))>
                                    <label for="{{ $id }}" aria-hidden="true" @class(['relative h-6 w-11 rounded-full bg-eh-border-strong transition after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:bg-eh-primary peer-checked:after:translate-x-5 peer-focus-visible:ring-2 peer-focus-visible:ring-eh-primary', 'cursor-pointer' => $esPerfil, 'cursor-not-allowed opacity-70' => ! $esPerfil])></label>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            @if ($esPerfil)
                <div data-guardar-barra hidden class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-eh-primary bg-eh-surface p-4 shadow-lg">
                    <p class="text-sm"><span class="font-semibold">Cambios sin guardar.</span> <span class="text-eh-text-muted">Crear, Editar y Dar de baja activan Ver; quitar Ver quita el resto del módulo.</span></p>
                    <div class="flex gap-2">
                        <x-ui.btn :href="route('admin.roles.index', [...$filtros, 'rol' => $rol->id])" variant="ghost">Descartar</x-ui.btn>
                        <x-ui.btn type="submit">Guardar permisos</x-ui.btn>
                    </div>
                </div>
            @endif
        </form>
    </section>
</div>

<x-ui.modal id="nuevo-rol" title="Añadir rol">
    <form method="POST" action="{{ route('admin.roles.store') }}" class="space-y-4" data-once>
        @csrf
        <input type="hidden" name="permisos_presentes" value="1">
        <x-ui.field name="nombre" id="nombre-nuevo-rol" label="Nombre del rol" required maxlength="100" autocomplete="off" placeholder="Ej. Asistente de acopio" />
        <x-ui.select name="activo" id="activo-nuevo-rol" label="Estado" :options="[1 => 'Activo', 0 => 'Inactivo']" :selected="1" required />
        <p class="text-xs text-eh-text-muted">Se crea sin permisos. Después actívalos por categoría y guarda.</p>
        <div class="flex justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Crear rol</x-ui.btn></div>
    </form>
</x-ui.modal>

@if ($esPerfil)
    <x-ui.modal id="datos-rol" title="Nombre y estado del rol">
        <form method="POST" action="{{ route('admin.roles.update', $rol->id) }}" class="space-y-4" data-once>
            @csrf
            @method('PUT')
            <x-ui.field name="nombre" id="nombre-rol" label="Nombre del rol" required maxlength="100" :value="$rol->nombre" autocomplete="off" />
            <x-ui.select name="activo" id="activo-rol" label="Estado" :options="[1 => 'Activo', 0 => 'Inactivo']" :selected="$rol->activo ? 1 : 0" required hint="Un rol inactivo no permite entrar al panel." />
            <div class="flex justify-end gap-2"><x-ui.btn data-modal-close variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar</x-ui.btn></div>
        </form>
    </x-ui.modal>
@endif

<script>
    (function () {
        const form = document.querySelector('[data-permisos-rol]');
        if (!form || !form.querySelector('[data-guardar-barra]')) return;
        const permisos = [...form.querySelectorAll('input[data-modulo]')];
        const actualizar = () => {
            form.querySelectorAll('[data-categoria]').forEach((categoria) => {
                const propios = [...categoria.querySelectorAll('input[data-modulo]')];
                const encendidos = propios.filter((input) => input.checked).length;
                categoria.querySelector('[data-categoria-conteo]').textContent = encendidos + ' / ' + propios.length;
                const todo = categoria.querySelector('[data-categoria-todo]');
                if (todo) todo.checked = encendidos === propios.length;
            });
            document.querySelectorAll('[data-permisos-total]').forEach((total) => { total.textContent = String(permisos.filter((input) => input.checked).length); });
        };
        form.addEventListener('change', (event) => {
            const input = event.target;
            if (input.matches('[data-categoria-todo]')) {
                input.closest('[data-categoria]').querySelectorAll('input[data-modulo]').forEach((permiso) => { permiso.checked = input.checked; });
            } else if (input.matches('input[data-modulo]')) {
                const mismoModulo = permisos.filter((permiso) => permiso.dataset.modulo === input.dataset.modulo);
                if (input.dataset.accion === 'ver' && !input.checked) mismoModulo.forEach((permiso) => { permiso.checked = false; });
                if (input.dataset.accion !== 'ver' && input.checked) mismoModulo.filter((permiso) => permiso.dataset.accion === 'ver').forEach((permiso) => { permiso.checked = true; });
            }
            form.querySelector('[data-guardar-barra]').hidden = false;
            actualizar();
        });
        actualizar();
    })();
</script>
@endsection
