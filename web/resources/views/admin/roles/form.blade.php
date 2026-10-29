@extends('layouts.admin')
@section('titulo', $perfil ? 'Editar rol' : 'Nuevo rol')
@section('contenido')
@php
    $permisos = session()->hasOldInput('permisos_presentes') ? old('permisos', []) : json_decode($perfil?->permisos ?? '{}', true);
@endphp
<div class="mx-auto w-full max-w-6xl">
    <x-ui.page-header :title="$perfil ? 'Editar rol' : 'Nuevo rol'" description="Define qué puede consultar y gestionar en el panel web cada usuario con este rol." :breadcrumbs="[['label'=>'Roles y permisos','url'=>route('admin.roles.index')],['label'=>$perfil?->nombre ?? 'Nuevo rol']]" />
    <form method="POST" action="{{ $perfil ? route('admin.roles.update', $perfil->id) : route('admin.roles.store') }}" data-rol-editor data-once class="gestion-form-panel space-y-6">
        @csrf
        @if($perfil) @method('PUT') @endif
        <input type="hidden" name="permisos_presentes" value="1">
        @if($errors->any())<div role="alert" tabindex="-1" data-form-errors class="rounded-xl border border-eh-red bg-eh-red-soft p-4 text-sm text-eh-red">Revisa los campos marcados antes de guardar.</div>@endif
        <section class="max-w-3xl">
            <h2 class="mb-5 font-semibold">Información del rol</h2>
            <div class="grid gap-6">
                <x-ui.field name="nombre" label="Nombre del rol" required :value="$perfil?->nombre" maxlength="100" autocomplete="off" placeholder="Ej. Asistente de acopio…"  />
                <x-ui.select name="activo" label="Estado" :options="[1=>'Activo',0=>'Inactivo']" :selected="$perfil?->activo ?? 1" required hint="Un rol inactivo no permite entrar al panel ni asignar nuevas cuentas." />
            </div>
        </section>
        <section data-role-permissions aria-labelledby="permisos-titulo" class="min-w-0">
            <div class="flex flex-wrap items-start justify-between gap-4 border-b border-eh-border p-5 sm:p-6">
                <div class="max-w-xl"><h2 id="permisos-titulo" class="font-semibold">Permisos del panel web</h2><p id="permisos-dependencias" class="mt-1 text-sm text-eh-text-muted">Crear, Editar y Eliminar requieren Ver: se marca solo. Al quitar Ver, también se quitan las demás acciones del módulo. El guion indica una acción que el módulo no ofrece.</p></div>
                <p data-permisos-resumen role="status" class="rounded-lg bg-eh-primary-soft px-3 py-2 text-xs font-medium text-eh-primary"></p>
            </div>
            @error('permisos')<p id="permisos-error" role="alert" class="px-5 pt-4 text-sm text-eh-red">{{ $message }}</p>@enderror
            <div class="space-y-3 border-b border-eh-border p-4 sm:px-6">
                <div class="gestion-toolbar">
                    <div class="w-full sm:max-w-sm"><x-ui.search data-permisos-buscar aria-label="Buscar un módulo" placeholder="Buscar módulo…" autocomplete="off" /></div>
                    <div data-permisos-js hidden class="flex flex-wrap items-center gap-1">
                        <button type="button" data-permisos-limpiar class="min-h-9 rounded-lg px-3 text-xs font-medium text-eh-text-muted hover:bg-eh-red-soft hover:text-eh-red focus-visible:outline-2 focus-visible:outline-eh-primary">Limpiar permisos</button>
                    </div>
                </div>
                <div data-permisos-js hidden class="gestion-status-tabs" role="group" aria-label="Filtrar módulos por acceso">
                    <button type="button" data-permisos-filtro="todos" aria-pressed="true" class="gestion-status-tab">Todos los módulos</button>
                    <button type="button" data-permisos-filtro="habilitados" aria-pressed="false" class="gestion-status-tab">Habilitados</button>
                    <button type="button" data-permisos-filtro="sin-acceso" aria-pressed="false" class="gestion-status-tab">Sin acceso</button>
                </div>
            </div>
            @php($conEliminar = collect($modulos)->filter(fn ($modulo) => in_array('eliminar', $modulo['acciones'], true))->count())
            @if(in_array('eliminar', $acciones, true))<p data-permisos-eliminar-resumen class="px-5 pt-4 text-xs text-eh-text-muted"><strong class="font-semibold text-eh-text">Eliminar</strong> es una baja lógica (anular, cancelar, retirar o desactivar) que conserva el historial. Disponible en {{ $conEliminar }} de {{ count($modulos) }} módulos; los demás están protegidos. Pasa el cursor sobre una casilla o un guion para ver qué autoriza o por qué está protegido.</p>@endif
            <p id="permisos-descripcion" class="px-5 py-4 text-xs text-eh-text-muted"><strong class="font-semibold text-eh-text">Los botones de columna aplican a todos los módulos</strong>, también a los ocultos por la búsqueda o el filtro. Revisa la pestaña Habilitados antes de guardar.</p>
            @php($accionesPermiso = collect(['ver' => 'Ver', 'crear' => 'Crear', 'editar' => 'Editar', 'eliminar' => 'Eliminar'])->only($acciones)->all())
            <div data-permisos-tabla tabindex="0" aria-label="Matriz de permisos; desplázate horizontalmente en pantallas pequeñas" aria-describedby="permisos-descripcion" class="overflow-x-auto rounded-xl border border-eh-border focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-eh-primary">
                <table class="w-full min-w-[50rem] border-collapse text-sm">
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
                                    <button type="button" data-permisos-js hidden data-permiso-todos aria-pressed="false" class="mt-2 block min-h-9 rounded-lg px-2 text-xs text-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary">Todos los permisos</button>
                                </th>
                                @foreach($accionesPermiso as $accion => $etiqueta)
                                    <td class="px-3 py-2 text-center">
                                        @if(in_array($accion, $config['acciones'], true))
                                            @php($idPermiso = 'permiso-'.$modulo.'-'.$accion)
                                            <label for="{{ $idPermiso }}" @if($accion === 'eliminar') title="{{ $config['eliminar'] }}" @endif class="inline-flex min-h-11 min-w-12 cursor-pointer items-center justify-center rounded-lg focus-within:ring-2 focus-within:ring-eh-primary">
                                                <input id="{{ $idPermiso }}" type="checkbox" name="permisos[{{ $modulo }}][]" value="{{ $accion }}"
                                                    data-permission-module="{{ $modulo }}" data-permission-action="{{ $accion }}"
                                                    aria-describedby="permisos-descripcion permisos-dependencias"
                                                    class="size-4 rounded border-eh-border-strong accent-eh-primary focus-visible:outline-none"
                                                    @checked(in_array($accion, $permisos[$modulo] ?? [], true))>
                                                <span class="sr-only">{{ $etiqueta }}: {{ $config['nombre'] }}{{ $accion === 'eliminar' ? '. '.$config['eliminar'] : '' }}</span>
                                            </label>
                                        @else
                                            <span aria-label="{{ $etiqueta }} no disponible en {{ $config['nombre'] }}{{ $accion === 'eliminar' && isset($config['protegido']) ? '. '.$config['protegido'] : '' }}" @if($accion === 'eliminar' && isset($config['protegido'])) title="Protegido: {{ $config['protegido'] }}" @endif class="text-eh-text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div data-permisos-vacio hidden role="status" class="space-y-3 p-6 text-center text-sm text-eh-text-muted">
                <p data-permisos-vacio-texto>Ningún módulo coincide con estos filtros.</p>
                <button type="button" data-permisos-ver-todos class="min-h-10 rounded-lg border border-eh-border px-4 text-xs font-semibold text-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary">Ver todos los módulos</button>
            </div>
            <p class="border-t border-eh-border px-5 py-4 text-xs text-eh-text-muted">Sin módulos seleccionados no habrá acceso al panel web. Este rol no otorga funciones de la app móvil: se asignan a cada cuenta en Usuarios y accesos. Solo el administrador gestiona roles y formularios.</p>
        </section>
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-eh-border bg-eh-surface p-4 sm:px-6">
            <p class="max-w-md text-xs text-eh-text-muted">{{ $perfil ? 'Los cambios de permisos se aplican a todos los usuarios de este rol.' : 'Después de guardar podrás configurar su formulario y registrar usuarios.' }}</p>
            <div class="flex gap-3"><x-ui.btn type="submit">{{ $perfil ? 'Guardar cambios' : 'Crear rol' }}</x-ui.btn><x-ui.btn :href="route('admin.roles.index')" variant="outline">Cancelar</x-ui.btn></div>
        </div>
    </form>
</div>
@endsection
