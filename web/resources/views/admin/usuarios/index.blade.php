@extends('layouts.admin')
@section('titulo', 'Usuarios y accesos')
@section('contenido')
@php
    $operador = auth('operador')->user();
    $puedeGestionar = $operador->puede('usuarios','editar');
    $puedeCrear = $operador->puede('usuarios','crear');
    $hayFiltros = filled($filtros['buscar'] ?? null) || filled($filtros['rol'] ?? null) || filled($filtros['estado'] ?? null);
    $orden = $filtros['orden'] ?? 'recientes';
@endphp
<x-ui.page-header title="Usuarios y accesos" description="Gestiona las cuentas del equipo, sus roles y el acceso al sistema." :breadcrumbs="[['label'=>'Usuarios y proveedores'],['label'=>'Usuarios y accesos']]">
    @if($puedeCrear)
        <x-slot:actions><x-ui.btn :href="route('admin.usuarios.create')" icon="plus">Nuevo usuario</x-ui.btn></x-slot:actions>
    @endif
</x-ui.page-header>
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <x-ui.status-tabs name="estado" :options="[''=>'Todos','activo'=>'Activos','inactivo'=>'Inactivos','bloqueado'=>'Bloqueados']" :value="$filtros['estado'] ?? ''" form-id="filtros-usuarios" />
    <p class="text-xs text-eh-text-muted">{{ $filas->total() }} {{ $filas->total() === 1 ? 'usuario' : 'usuarios' }}</p>
</div>
<div class="gestion-toolbar mb-4">
    <form id="filtros-usuarios" method="GET" action="{{ route('admin.usuarios.index') }}" data-directorio-filtros data-live-filter class="flex flex-1 flex-wrap items-center gap-3">
        <input type="hidden" name="estado" value="{{ $filtros['estado'] ?? '' }}">
        <div class="w-full sm:max-w-sm"><x-ui.search name="buscar" label="Buscar usuarios" :value="$filtros['buscar'] ?? ''" placeholder="Buscar por nombre, usuario o DNI…" maxlength="150" autocomplete="off" /></div>
        <details class="gestion-columns gestion-filters"><summary>Filtros{{ filled($filtros['rol'] ?? null) ? ' · 1' : '' }}</summary><div>
            <x-ui.select name="rol" label="Rol del usuario" placeholder="Todos los roles" :options="$opcionesRol" :selected="$filtros['rol'] ?? ''" />
            <x-ui.select name="orden" label="Ordenar por" :options="['recientes'=>'Más recientes','nombre_asc'=>'Nombre: A a Z','nombre_desc'=>'Nombre: Z a A']" :selected="$orden" />
            <x-ui.btn type="submit" variant="secondary" size="sm">Filtrar</x-ui.btn>
        </div></details>
        @if($hayFiltros)<x-ui.btn :href="route('admin.usuarios.index')" variant="ghost" size="sm">Limpiar</x-ui.btn>@endif
    </form>
    <div class="ml-auto flex flex-wrap items-center gap-2">
        <x-ui.table-columns table-id="tabla-usuarios" :columns="['dni'=>'DNI','rol'=>'Rol','estado'=>'Estado','acceso'=>'Acceso web']" />
        <x-ui.table-export table-id="tabla-usuarios" form-id="filtros-usuarios" filename="usuarios" />
    </div>
</div>
<p data-directorio-estado role="status" aria-live="polite" class="sr-only">{{ $filas->total() }} {{ $filas->total() === 1 ? 'usuario encontrado' : 'usuarios encontrados' }}.</p>
<x-ui.card>
    @if($filas->isEmpty())
        <x-ui.empty icon="users" :title="$hayFiltros ? 'No hay usuarios que coincidan con los filtros' : 'No hay usuarios en esta página'" description="Cambia los filtros o vuelve a la primera página." />
        <div class="px-4 pb-4"><x-ui.btn :href="route('admin.usuarios.index', array_diff_key(request()->query(), ['page'=>true]))" variant="secondary">Volver a la primera página</x-ui.btn></div>
    @else
    <div tabindex="0" role="region" aria-label="Cuentas de usuarios y accesos al sistema" class="overflow-x-auto focus-visible:outline-2 focus-visible:outline-eh-primary">
        <table id="tabla-usuarios" data-export-total="{{ $filas->total() }}" class="gestion-table">
            <caption class="sr-only">Cuentas de usuarios y accesos al sistema</caption>
            <thead><tr><th scope="col" data-export-value="Nombre" aria-sort="{{ match($orden) {'nombre_asc'=>'ascending','nombre_desc'=>'descending',default=>'none'} }}"><a data-table-sort="nombre" data-sort-form="filtros-usuarios" href="{{ route('admin.usuarios.index', array_replace(array_diff_key(request()->query(),['page'=>true]),['orden'=>$orden==='nombre_asc' ? 'nombre_desc' : 'nombre_asc'])) }}" class="inline-flex items-center gap-1 rounded focus-visible:outline-2 focus-visible:outline-eh-primary">Nombre <span aria-hidden="true">{{ $orden==='nombre_asc' ? '↑' : ($orden==='nombre_desc' ? '↓' : '↕') }}</span></a></th><th scope="col" data-column="dni">DNI</th><th scope="col" data-column="rol">Rol</th><th scope="col" data-column="estado">Estado</th><th scope="col" data-column="acceso">Acceso web</th><th scope="col" data-column="acciones"><span class="sr-only">Acciones</span></th></tr></thead>
            <tbody>
            @foreach($filas as $fila)
                @php
                    $usuario = $cuentasPagina->get($fila->usuario_id);
                    $rolesFila = json_decode($fila->roles, true) ?? [];
                    $rolesConocidos = array_filter(array_map(fn($valor)=>\App\Domain\Usuarios\Rol::tryFrom($valor), $rolesFila));
                    $acceso = !$usuario->activo || $usuario->estaBloqueada() ? 'Deshabilitado' : ($usuario->accesoWeb() ? 'Sí' : ($rolesConocidos === [] ? 'Sin perfil asignado' : ($usuario->perfil_rol_id ? 'Sin permisos web' : 'Solo app móvil')));
                    $iniciales = collect(preg_split('/\s+/u',trim($fila->nombre)))->filter()->take(2)->map(fn($nombre)=>mb_strtoupper(mb_substr($nombre,0,1)))->implode('');
                    $gestionable = $puedeGestionar && ($operador->perfil_rol_id === null || ($usuario->perfil_rol_id === $operador->perfil_rol_id && !$usuario->tieneRol('admin')));
                @endphp
                <tr data-directorio-fila>
                    <th scope="row" data-export-value="{{ $fila->nombre }} · {{ $fila->username }}"><div class="flex min-w-48 items-center gap-3"><span aria-hidden="true" class="gestion-avatar">{{ $iniciales }}</span><div class="min-w-0">
                        @if($gestionable)<a href="{{ route('admin.usuarios.edit',$usuario->id) }}" class="gestion-row-title">{{ $fila->nombre }}</a>@else<span class="gestion-row-title">{{ $fila->nombre }}</span>@endif
                        <p class="gestion-row-subtitle">{{ $fila->username }}</p></div></div></th>
                    <td data-column="dni" class="tabular-nums">{{ $fila->dni }}</td>
                    <td data-column="rol"><div class="flex flex-wrap gap-1">
                        @if($fila->perfil)<x-ui.badge variant="blue" :label="$fila->perfil" />
                        @else @forelse($rolesConocidos as $rol)<x-ui.badge :variant="match($rol->value) {'admin'=>'red','calidad'=>'yellow','proveedor'=>'gray',default=>'blue'}" :label="$rol->etiqueta()" />@empty<x-ui.badge variant="yellow" label="Requiere asignación" />@endforelse @endif
                    </div></td>
                    <td data-column="estado"><x-ui.badge :variant="match($fila->estado) {'activo'=>'green','bloqueado'=>'red',default=>'gray'}" :label="match($fila->estado) {'activo'=>'Activo','inactivo'=>'Inactivo',default=>'Bloqueado'}" /></td>
                    <td data-column="acceso" class="text-eh-text-muted">{{ $acceso }}</td>
                    <td data-column="acciones">@if($gestionable) @include('admin.usuarios.acciones') @else<span class="text-xs text-eh-text-muted">{{ $puedeGestionar ? 'Cuenta protegida' : 'Solo lectura' }}</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <x-ui.table-pagination :paginator="$filas" form-id="filtros-usuarios" noun="usuarios" />
</x-ui.card>
@include('admin.usuarios.modales')
@endsection
