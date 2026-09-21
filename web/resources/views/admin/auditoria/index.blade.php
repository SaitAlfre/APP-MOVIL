@extends('layouts.admin')

@section('titulo', 'Auditoría')

@section('contenido')
    @php
        $variantesAccion = [
            'crear' => 'green',
            'autorizar' => 'green',
            'iniciar_sesion' => 'green',
            'corregir' => 'blue',
            'actualizar' => 'blue',
            'cerrar_sesion' => 'gray',
            'desactivar' => 'yellow',
            'anular' => 'red',
            'rechazar' => 'red',
            'acceso_fallido' => 'red',
        ];
        $etiquetasAccion = [
            'crear' => 'Creación',
            'corregir' => 'Corrección',
            'anular' => 'Anulación',
            'actualizar' => 'Actualización',
            'desactivar' => 'Desactivación',
            'autorizar' => 'Autorización',
            'rechazar' => 'Rechazo',
            'iniciar_sesion' => 'Inicio de sesión',
            'cerrar_sesion' => 'Cierre de sesión',
            'acceso_fallido' => 'Acceso fallido',
        ];
        $hayFiltros = collect($filtros)->filter()->isNotEmpty();
    @endphp

    <x-ui.page-header title="Auditoría" description="Historial de acciones sensibles del negocio, separado de los logs técnicos" />

    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.auditoria.index') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            <x-ui.select name="usuario_id" label="Responsable o cuenta" placeholder="Todos" :selected="$filtros['usuario_id'] ?? null"
                :options="$usuariosFiltro->mapWithKeys(fn ($usuario) => [$usuario->id => $usuario->nombres])->all()" />
            <x-ui.select name="entidad" label="Módulo o entidad" placeholder="Todos" :selected="$filtros['entidad'] ?? null"
                :options="$entidades->mapWithKeys(fn ($entidad) => [$entidad => ucfirst(str_replace('_', ' ', $entidad))])->all()" />
            <x-ui.select name="accion" label="Acción" placeholder="Todas" :selected="$filtros['accion'] ?? null"
                :options="collect($acciones)->mapWithKeys(fn ($accion) => [$accion->value => $etiquetasAccion[$accion->value] ?? ucfirst($accion->value)])->all()" />
            <x-ui.field name="desde" label="Desde" type="date" :value="$filtros['desde'] ?? null" />
            <x-ui.field name="hasta" label="Hasta" type="date" :value="$filtros['hasta'] ?? null" />
            <div class="flex items-end gap-2">
                <x-ui.btn type="submit" variant="secondary" icon="filter">Filtrar</x-ui.btn>
                @if ($hayFiltros)
                    <x-ui.btn :href="route('admin.auditoria.index')" variant="ghost" icon="xMark">Limpiar</x-ui.btn>
                @endif
            </div>
        </form>
    </x-ui.card>

    <x-ui.card>
        @if ($filas->isEmpty())
            <x-ui.empty icon="shield" title="No hay registros para estos filtros"
                description="Aquí aparecen creaciones, correcciones, anulaciones y demás acciones sensibles del negocio." />
        @else
            <x-ui.table :headers="['Fecha y hora', 'Entidad', 'Acción', 'Responsable', 'Motivo', '']" caption="Historial de auditoría">
                @foreach ($filas as $fila)
                    @php $registro = $fila['registro']; @endphp
                    <tr class="border-b border-eh-border align-top last:border-0 hover:bg-eh-surface-alt">
                        <td class="mono whitespace-nowrap px-4 py-3 text-xs text-eh-text">{{ $registro->ocurridoEn->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3 text-sm font-medium text-eh-text">
                            {{ ucfirst(str_replace('_', ' ', $registro->entidad)) }}
                            <span class="mono text-xs text-eh-text-muted">#{{ $registro->entidadId }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$variantesAccion[$registro->accion->value] ?? 'gray'"
                                :label="$etiquetasAccion[$registro->accion->value] ?? ucfirst($registro->accion->value)" />
                        </td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $fila['usuario']?->nombres ?? '—' }}</td>
                        <td class="max-w-xs px-4 py-3 text-xs text-eh-text-muted">{{ $registro->motivo ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.auditoria.show', ['auditoria' => $registro->id] + $filtros) }}" aria-label="Ver cambios del registro #{{ $registro->id }}"
                                class="inline-flex rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                <x-icon name="eye" class="h-4 w-4" />
                            </a>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $paginador->count() }} de {{ $paginador->total() }} registros</span>
                <div>{{ $paginador->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>
@endsection
