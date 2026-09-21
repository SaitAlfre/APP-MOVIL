@extends('layouts.admin')

@section('titulo', 'Proveedores')

@section('contenido')
    @php
        $usuario = auth('operador')->user();
        $puedeGestionar = $usuario->puede('proveedores', 'gestionar');
    @endphp

    <x-ui.page-header title="Proveedores" description="Gestión de productores de leche registrados">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.proveedores.create')" icon="plus">Nuevo proveedor</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card padding="p-4" class="mb-4">
        <form method="GET" action="{{ route('admin.proveedores.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <div class="flex-1">
                <x-ui.search name="q" :value="$busqueda" label="Buscar proveedores" placeholder="Buscar por nombre, código o DNI…" />
            </div>
            <x-ui.select name="zona_id" placeholder="Todas las zonas" class="sm:w-52" aria-label="Filtrar por zona"
                :options="collect($zonasDisponibles)->mapWithKeys(fn ($zona) => [$zona->id => $zona->nombre])->all()" :selected="$zonaId" />
            <x-ui.select name="estado" placeholder="Todos los estados" class="sm:w-48" aria-label="Filtrar por estado"
                :options="['activo' => 'Activo', 'suspendido' => 'Suspendido', 'retirado' => 'Retirado']" :selected="$estadoFiltro?->value" />
            <x-ui.btn type="submit" variant="secondary" icon="filter">Filtrar</x-ui.btn>
            @if ($busqueda || $zonaId || $estadoFiltro)
                <x-ui.btn :href="route('admin.proveedores.index')" variant="ghost" icon="xMark">Limpiar</x-ui.btn>
            @endif
        </form>
    </x-ui.card>

    <x-ui.card>
        @if ($proveedores->total() === 0)
            <x-ui.empty icon="userCircle"
                :title="$busqueda || $zonaId || $estadoFiltro ? 'No se encontraron proveedores' : 'Aún no hay proveedores registrados'"
                :description="$busqueda || $zonaId || $estadoFiltro ? 'Prueba con otros filtros o limpia la búsqueda.' : 'Registra el primero con el botón «Nuevo proveedor».'">
                @if ($puedeGestionar && ! ($busqueda || $zonaId || $estadoFiltro))
                    <x-slot:action>
                        <x-ui.btn :href="route('admin.proveedores.create')" icon="plus" size="sm">Nuevo proveedor</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Código', 'Nombre', 'DNI', 'Zona', 'Tachos', 'Estado', 'Litros sem.', 'Última entrega', 'Cuenta', '']"
                caption="Listado de proveedores registrados">
                @foreach ($proveedores as $proveedor)
                    @php
                        $resumen = $resumenSemana[$proveedor->id] ?? ['litros' => 0.0, 'ultima' => null];
                        $cuenta = $proveedor->usuarioId !== null ? $cuentas->get($proveedor->usuarioId) : null;
                    @endphp
                    <tr class="border-b border-eh-border last:border-0 hover:bg-eh-surface-alt">
                        <td class="px-4 py-3"><span class="mono text-xs font-medium text-eh-text">{{ $proveedor->codigo }}</span></td>
                        <td class="px-4 py-3 font-medium text-eh-text">
                            <a href="{{ route('admin.proveedores.show', $proveedor->id) }}" class="hover:text-eh-primary hover:underline">{{ $proveedor->nombres }}</a>
                        </td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $proveedor->dni }}</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">{{ $zonas->get($proveedor->zonaId)?->nombre ?? '—' }}</td>
                        <td class="mono px-4 py-3 text-xs text-eh-text-muted">{{ $proveedor->tachos }} × {{ number_format($proveedor->capacidadTachoL, 0) }} L</td>
                        <td class="px-4 py-3"><x-ui.estado :estado="$proveedor->estado->value" /></td>
                        <td class="mono px-4 py-3 font-medium text-eh-text">{{ number_format($resumen['litros'], 1) }} L</td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">
                            {{ $resumen['ultima'] ? \Illuminate\Support\Carbon::parse($resumen['ultima'])->format('d/m/Y H:i') : 'Sin entregas' }}
                        </td>
                        <td class="px-4 py-3 text-xs">
                            @if ($cuenta)
                                <span class="mono text-eh-primary">{{ $cuenta->username }}</span>
                            @else
                                <span class="text-eh-text-muted">Sin cuenta</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.proveedores.show', $proveedor->id) }}" aria-label="Ver ficha de {{ $proveedor->nombres }}"
                                    class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                    <x-icon name="eye" class="h-4 w-4" />
                                </a>
                                <a href="{{ route('admin.proveedores.qr', $proveedor->id) }}" target="_blank" rel="noopener" aria-label="Código QR de {{ $proveedor->nombres }}"
                                    class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                    <x-icon name="qrCode" class="h-4 w-4" />
                                </a>
                                @if ($puedeGestionar)
                                    <a href="{{ route('admin.proveedores.edit', $proveedor->id) }}" aria-label="Editar {{ $proveedor->nombres }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $proveedores->count() }} de {{ $proveedores->total() }} proveedores{{ $proveedores->total() !== $totalProveedores ? ' (de '.$totalProveedores.' en total)' : '' }}</span>
                <div>{{ $proveedores->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>
@endsection
