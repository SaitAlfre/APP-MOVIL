@extends('layouts.admin')
@section('titulo', $perfil ? 'Editar rol' : 'Nuevo rol')
@section('contenido')
@php
    $permisos = session()->hasOldInput('permisos_presentes') ? old('permisos', []) : json_decode($perfil?->permisos ?? '{}', true);
@endphp
<div class="mx-auto w-full max-w-6xl">
    <x-ui.page-header :title="$perfil ? 'Editar rol' : 'Nuevo rol'" description="Define qué puede consultar y gestionar cada usuario con este rol." :breadcrumbs="[['label'=>'Roles y permisos','url'=>route('admin.roles.index')],['label'=>$perfil?->nombre ?? 'Nuevo rol']]" />
    <form method="POST" action="{{ $perfil ? route('admin.roles.update', $perfil->id) : route('admin.roles.store') }}" data-rol-editor data-once class="gestion-form-panel space-y-6">
        @csrf
        @if($perfil) @method('PUT') @endif
        <input type="hidden" name="permisos_presentes" value="1">
        @if($errors->any())<div role="alert" tabindex="-1" data-form-errors class="rounded-xl border border-eh-red bg-eh-red-soft p-4 text-sm text-eh-red">Revisa los campos marcados antes de guardar.</div>@endif
        <section class="max-w-3xl">
            <h2 class="mb-5 font-semibold">Información del rol</h2>
            <div class="grid gap-6">
                <x-ui.field name="nombre" label="Nombre del rol" required :value="$perfil?->nombre" maxlength="100" autocomplete="off" placeholder="Ej. Asistente de acopio…"  />
                @if($asignado)
                    <input type="hidden" name="base" value="{{ $perfil->base }}">
                    <x-ui.select name="base_bloqueada" label="Perfil base" :options="collect($bases)->mapWithKeys(fn($r)=>[$r->value=>$r->etiqueta()])->all()" :selected="$perfil->base" disabled hint="Se conserva porque este rol ya tiene usuarios." />
                    @error('base')<p class="text-xs text-eh-red">{{ $message }}</p>@enderror
                @else
                    <x-ui.select name="base" label="Perfil base" :options="collect($bases)->mapWithKeys(fn($r)=>[$r->value=>$r->etiqueta()])->all()" :selected="$perfil?->base" required hint="Define el trabajo en campo y las funciones de la app móvil." />
                @endif
                <x-ui.select name="activo" label="Estado" :options="[1=>'Activo',0=>'Inactivo']" :selected="$perfil?->activo ?? 1" required hint="Un rol inactivo no permite entrar al panel ni asignar nuevas cuentas." />
            </div>
        </section>
        <section data-role-permissions aria-labelledby="permisos-titulo" class="min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-eh-border p-5 sm:p-6">
                <div class="max-w-xl"><h2 id="permisos-titulo" class="font-semibold">Permisos del panel web</h2><p id="permisos-dependencias" class="mt-1 text-sm text-eh-text-muted">Crear, editar y eliminar requieren Ver. Al quitar Ver, también se quitan esas acciones para el mismo módulo. El guion indica una acción que el módulo no ofrece.</p></div>
                <p data-permisos-resumen role="status" class="rounded-lg bg-eh-primary-soft px-3 py-2 text-xs font-medium text-eh-primary"></p>
            </div>
            @error('permisos')<p id="permisos-error" role="alert" class="px-5 pt-4 text-sm text-eh-red">{{ $message }}</p>@enderror
            <div class="space-y-3 border-b border-eh-border p-4 sm:px-6">
                <div class="gestion-toolbar">
                    <div class="w-full sm:max-w-sm"><x-ui.search data-permisos-buscar aria-label="Buscar un módulo" placeholder="Buscar módulo…" autocomplete="off" /></div>
                    <div data-permisos-js hidden class="flex flex-wrap items-center gap-1">
                        <button type="button" data-permisos-consultar class="min-h-9 rounded-lg border border-eh-border px-3 text-xs font-medium hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary">Consultar todos</button>
                        <button type="button" data-permisos-limpiar class="min-h-9 rounded-lg px-3 text-xs font-medium text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red focus-visible:outline-2 focus-visible:outline-eh-primary">Limpiar permisos</button>
                    </div>
                </div>
                <div data-permisos-js hidden class="gestion-status-tabs" role="group" aria-label="Filtrar módulos por acceso">
                    <button type="button" data-permisos-filtro="todos" aria-pressed="true" class="gestion-status-tab">Todos los módulos</button>
                    <button type="button" data-permisos-filtro="habilitados" aria-pressed="false" class="gestion-status-tab">Habilitados</button>
                    <button type="button" data-permisos-filtro="sin-acceso" aria-pressed="false" class="gestion-status-tab">Sin acceso</button>
                </div>
            </div>
            <p id="permisos-descripcion" class="px-5 py-4 text-xs text-eh-text-muted">Cada botón de columna aplica a todos los módulos, incluidos los ocultos por los filtros o al desplazarte.</p>
            @php($accionesPermiso = ['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'])
            <div data-permisos-tabla tabindex="0" aria-label="Matriz de permisos; desplázate horizontalmente en pantallas pequeñas" aria-describedby="permisos-descripcion" class="overflow-x-auto rounded-xl border border-eh-border focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eh-primary">
                <table class="w-full min-w-[58rem] border-collapse text-sm">
                    <thead class="bg-eh-surface-alt text-left">
                        <tr>
                            <th scope="col" class="w-56 px-4 py-3 font-semibold">Módulo</th>
                            @foreach($accionesPermiso as $accion => $etiqueta)
                                <th scope="col" class="min-w-44 px-3 py-3 text-center font-semibold">
                                    <div class="flex flex-col items-center gap-2">
                                        <span>{{ $etiqueta }}</span>
                                        <x-ui.btn type="button" variant="outline" size="sm"
                                            data-permisos-js hidden data-permission-toggle="{{ $accion }}" data-all-selected="false"
                                            aria-controls="permisos-matriz-cuerpo" aria-pressed="false"
                                            aria-label="Seleccionar todos los permisos de {{ $etiqueta }} en todos los módulos"
                                            class="min-h-10 px-2.5 text-[0.7rem] data-[all-selected=true]:border-eh-primary data-[all-selected=true]:bg-eh-primary-soft data-[all-selected=true]:text-eh-primary"
                                        >Seleccionar todo</x-ui.btn>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody id="permisos-matriz-cuerpo" class="divide-y divide-eh-border">
                        @foreach($modulos as $modulo => $config)
                            <tr data-permiso-fila class="odd:bg-eh-surface even:bg-eh-surface-alt/40">
                                <th scope="row" class="px-4 py-3 text-left font-medium">
                                    <span data-permiso-nombre>{{ $config['nombre'] }}</span>
                                    <button type="button" data-permisos-js hidden data-permiso-todos aria-pressed="false" class="mt-2 block min-h-9 rounded-lg px-2 text-xs text-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary">Todos</button>
                                </th>
                                @foreach($accionesPermiso as $accion => $etiqueta)
                                    <td class="px-3 py-2 text-center">
                                        @if(in_array($accion, $config['acciones'], true))
                                            @php($idPermiso = 'permiso-'.$modulo.'-'.$accion)
                                            <label for="{{ $idPermiso }}" class="inline-flex min-h-11 min-w-12 cursor-pointer items-center justify-center rounded-lg focus-within:ring-2 focus-within:ring-eh-primary">
                                                <input id="{{ $idPermiso }}" type="checkbox" name="permisos[{{ $modulo }}][]" value="{{ $accion }}"
                                                    data-permission-module="{{ $modulo }}" data-permission-action="{{ $accion }}"
                                                    aria-describedby="permisos-descripcion permisos-dependencias"
                                                    class="size-4 rounded border-eh-border-strong accent-eh-primary focus-visible:outline-none"
                                                    @checked(in_array($accion, $permisos[$modulo] ?? [], true))>
                                                <span class="sr-only">{{ $etiqueta }}: {{ $config['nombre'] }}</span>
                                            </label>
                                        @else
                                            <span aria-label="{{ $etiqueta }} no disponible en {{ $config['nombre'] }}" class="text-eh-text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p data-permisos-vacio hidden class="p-6 text-center text-sm text-eh-text-muted">Ningún módulo coincide con estos filtros.</p>
            <p class="border-t border-eh-border px-5 py-4 text-xs text-eh-text-muted">Sin módulos seleccionados no habrá acceso al panel web. El perfil base determina las funciones de la app móvil. Solo el administrador gestiona roles y formularios.</p>
        </section>
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-eh-border bg-eh-surface p-4 sm:px-6">
            <p class="max-w-md text-xs text-eh-text-muted">{{ $perfil ? 'Los cambios de permisos se aplican a todos los usuarios de este rol.' : 'Después de guardar podrás configurar su formulario y registrar usuarios.' }}</p>
            <div class="flex gap-3"><x-ui.btn type="submit">{{ $perfil ? 'Guardar cambios' : 'Crear rol' }}</x-ui.btn><x-ui.btn :href="route('admin.roles.index')" variant="outline">Cancelar</x-ui.btn></div>
        </div>
    </form>
</div>
@endsection
