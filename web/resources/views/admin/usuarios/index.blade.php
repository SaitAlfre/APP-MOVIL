@extends('layouts.admin')
@section('titulo', 'Usuarios y accesos')
@section('contenido')
    @php
        $operador = auth('operador')->user();
        $puedeGestionar = $operador->perfil_rol_id ? $operador->puede('usuarios','editar') : $operador->puede('usuarios','gestionar');
        $puedeCrear = $operador->perfil_rol_id ? $operador->puede('usuarios','crear') : $operador->puede('usuarios','gestionar');
        $hayFiltros = filled($filtros['buscar'] ?? null) || filled($filtros['rol'] ?? null) || filled($filtros['estado'] ?? null);
    @endphp
    <x-ui.page-header title="Usuarios y accesos" description="Administra las cuentas, sus roles y el acceso al sistema.">
        @if($puedeCrear)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.usuarios.create')" icon="plus">Nuevo usuario</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card padding="p-3 sm:p-4" class="mb-4">
        <form id="filtros-usuarios" method="GET" action="{{ route('admin.usuarios.index') }}" data-directorio-filtros data-live-filter class="grid gap-3 sm:grid-cols-2 xl:flex xl:items-center">
            <div class="min-w-0 sm:col-span-2 xl:flex-1">
                <x-ui.search name="buscar" label="Buscar usuarios" :value="$filtros['buscar'] ?? ''" placeholder="Buscar por nombre, usuario o DNI…" maxlength="150" autocomplete="off" />
            </div>
            <x-ui.select name="rol" placeholder="Todos los roles" aria-label="Filtrar por rol" class="xl:w-48" :options="$opcionesRol" :selected="$filtros['rol'] ?? ''" />
            <x-ui.select name="estado" placeholder="Todos los estados" aria-label="Filtrar por estado de cuenta" class="xl:w-44" :options="['activo'=>'Activo','inactivo'=>'Inactivo','bloqueado'=>'Bloqueado']" :selected="$filtros['estado'] ?? ''" />
            <input type="hidden" name="orden" value="{{ $filtros['orden'] ?? 'recientes' }}">
            <div class="flex items-center gap-2 sm:col-span-2">
                <x-ui.btn type="submit" variant="secondary" icon="filter">Filtrar</x-ui.btn>
                <x-ui.btn :href="route('admin.usuarios.index')" variant="ghost" icon="xMark">Limpiar</x-ui.btn>
            </div>
        </form>
    </x-ui.card>
    <p data-directorio-estado role="status" aria-live="polite" class="sr-only">{{ $filas->total() }} usuarios encontrados.</p>
    <x-ui.card>
        @if($filas->isEmpty())
            <x-ui.empty icon="users" :title="$hayFiltros ? 'No hay usuarios que coincidan con los filtros' : 'No hay usuarios en esta página'" description="Cambia los filtros o vuelve a la primera página." />
            <div class="px-4 pb-4"><x-ui.btn :href="route('admin.usuarios.index', array_diff_key(request()->query(), ['page'=>true]))" variant="secondary">Volver a la primera página</x-ui.btn></div>
        @else
            <x-ui.table :headers="['Usuario', 'Nombre completo', 'DNI', 'Rol', 'Estado', 'Acceso web', 'Acciones']" caption="Cuentas de usuarios y accesos al sistema">
                @foreach($filas as $fila)
                    @php
                        $usuario = $cuentasPagina->get($fila->usuario_id);
                        $rolesFila = json_decode($fila->roles, true) ?? [];
                        $rolesConocidos = array_filter(array_map(fn($valor)=>\App\Domain\Usuarios\Rol::tryFrom($valor), $rolesFila));
                        $acceso = !$usuario->activo || $usuario->estaBloqueada() ? 'Deshabilitado' : ($usuario->accesoWeb() ? 'Sí' : ($rolesConocidos === [] ? 'Sin perfil asignado' : ($usuario->perfil_rol_id ? 'Sin permisos web' : 'Solo app móvil')));
                    @endphp
                    <tr data-directorio-fila class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3 font-mono text-xs font-medium">{{ $fila->username }}</td>
                        <td class="max-w-64 break-words px-4 py-3 text-sm font-medium">{{ $fila->nombre }}</td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-eh-text-muted">{{ $fila->dni }}</td>
                        <td class="px-4 py-3"><div class="flex flex-wrap gap-1">
                            @if($fila->perfil)
                                <x-ui.badge variant="blue" :label="$fila->perfil" />
                            @else
                                @forelse($rolesConocidos as $rol)
                                    <x-ui.badge variant="blue" :label="$rol->etiqueta()" />
                                @empty
                                    <x-ui.badge variant="yellow" label="Requiere asignación" />
                                @endforelse
                            @endif
                        </div></td>
                        <td class="px-4 py-3"><x-ui.badge :variant="match($fila->estado) {'activo'=>'green','bloqueado'=>'red',default=>'gray'}" :label="match($fila->estado) {'activo'=>'Activo','inactivo'=>'Inactivo',default=>'Bloqueado'}" /></td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $acceso }}</td>
                        <td class="px-4 py-3">
                            @if($puedeGestionar)
                                @include('admin.usuarios.acciones')
                            @else
                                <span class="text-xs text-eh-text-muted">Solo lectura</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        @endif
        <x-ui.table-pagination :paginator="$filas" form-id="filtros-usuarios" noun="usuarios" />
    </x-ui.card>
    @include('admin.usuarios.modales')
@endsection
