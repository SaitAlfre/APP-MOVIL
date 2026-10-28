@extends('layouts.admin')
@section('titulo','Roles y permisos')
@section('contenido')
<div class="mx-auto w-full max-w-6xl">
    <x-ui.page-header title="Roles y permisos" description="Organiza las funciones y los accesos del equipo."><x-slot:actions><x-ui.btn :href="route('admin.roles.create')">Nuevo rol</x-ui.btn></x-slot:actions></x-ui.page-header>
    @include('admin.roles.filtros', ['ruta'=>'admin.roles.index'])
    <x-ui.card>
        @if($perfiles->isEmpty())
            <x-ui.empty title="No hay roles para mostrar" description="Crea un rol o cambia los filtros de búsqueda." />
        @else
        <div class="overflow-x-auto"><table data-roles-table class="gestion-table"><caption class="sr-only">Roles personalizados y usuarios asignados</caption>
            <thead><tr>@foreach(['Rol','Perfil base','Estado','Usuarios','Acciones'] as $columna)<th scope="col">{{ $columna }}</th>@endforeach</tr></thead>
            <tbody>@foreach($perfiles as $perfil)
                @php
                    $modulosCantidad = collect(json_decode($perfil->permisos,true))->filter(fn($acciones)=>in_array('ver',$acciones,true))->count();
                    $iniciales = collect(preg_split('/\s+/u', trim($perfil->nombre)))->take(2)->map(fn($palabra)=>mb_strtoupper(mb_substr($palabra,0,1)))->implode('');
                @endphp
                <tr><th scope="row"><div class="flex min-w-0 items-center gap-3"><span class="gestion-avatar" aria-hidden="true">{{ $iniciales }}</span><div class="min-w-0"><a href="{{ route('admin.roles.edit',$perfil->id) }}" class="gestion-row-title">{{ $perfil->nombre }}</a><p class="gestion-row-subtitle">{{ $modulosCantidad }} {{ $modulosCantidad === 1 ? 'módulo habilitado' : 'módulos habilitados' }}</p></div></div></th><td>{{ \App\Domain\Usuarios\Rol::from($perfil->base)->etiqueta() }}</td><td><x-ui.estado :estado="$perfil->activo ? 'activo' : 'inactivo'" /></td><td class="tabular-nums">{{ $perfil->usuarios_count }}</td><td><div class="flex flex-wrap items-center justify-end gap-1">
                    <x-ui.btn :href="route('admin.roles.edit',$perfil->id)" variant="ghost" size="sm" :aria-label="'Editar rol '.$perfil->nombre">Editar rol</x-ui.btn>
                    <details class="gestion-action-menu"><summary aria-label="Más acciones para {{ $perfil->nombre }}" title="Más acciones">•••</summary><div>
                        <a href="{{ route('admin.formularios.edit',$perfil->id) }}">Editar formulario</a>
                        @if(!$perfil->usuarios_count)<form method="POST" action="{{ route('admin.roles.destroy',$perfil->id) }}" data-confirm="¿Eliminar el rol {{ $perfil->nombre }} y su formulario? Esta acción no se puede deshacer." data-once>@csrf @method('DELETE')<button type="submit" class="text-eh-red">Eliminar rol</button></form>@else<span class="block px-3 py-2 text-xs text-eh-text-muted">Tiene usuarios asignados</span>@endif
                    </div></details>
                </div></td></tr>
            @endforeach</tbody>
        </table></div>
        @endif
        <x-ui.table-pagination :paginator="$perfiles" form-id="filtros-roles" noun="roles" />
    </x-ui.card>
    <details class="mt-5 rounded-xl border border-eh-border bg-eh-surface px-4 py-3">
        <summary class="cursor-pointer text-sm font-medium focus-visible:outline-2 focus-visible:outline-eh-primary">Roles del sistema <span class="ml-2 text-xs font-normal text-eh-text-muted">Predefinidos · no editables</span></summary>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">@foreach($rolesSistema as $rol)<div class="rounded-lg bg-eh-surface-alt px-3 py-3"><h2 class="text-sm font-medium">{{ $rol->etiqueta() }}</h2><p class="mt-1 text-xs text-eh-text-muted">{{ $rol->accesoWeb() ? 'Panel web y app móvil' : 'App móvil del proveedor' }}</p></div>@endforeach</div>
    </details>
</div>
@endsection
