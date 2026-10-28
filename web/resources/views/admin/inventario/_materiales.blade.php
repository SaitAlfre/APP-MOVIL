<x-ui.card padding="p-5" class="mb-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-eh-primary-soft text-eh-primary"><x-icon name="droplets" class="h-6 w-6" /></span>
            <div><h2 class="text-sm font-medium text-eh-text-muted">Leche disponible</h2><p class="mono mt-1 text-2xl font-bold text-eh-primary">{{ number_format(collect($lecheDisponible)->sum('litrosDisponibles'), 3) }} <span class="text-sm font-medium">L</span></p></div>
        </div>
        <div class="flex flex-wrap gap-2">
            @forelse ($lecheDisponible as $dia)
                <span class="rounded-xl bg-eh-surface-alt px-3 py-2 text-xs"><span class="text-eh-text-muted">{{ $dia['fecha']->format('d/m') }}</span><strong class="mono ml-2">{{ number_format($dia['litrosDisponibles'], 3) }} L</strong></span>
            @empty
                <span class="text-sm text-eh-text-muted">Sin leche disponible</span>
            @endforelse
            <span class="self-center px-2 text-xs text-eh-text-muted">Últimos 14 días</span>
        </div>
    </div>
    @if ($productoFiltro && $productoFiltro->litros_por_unidad !== null)<p class="mt-4 border-t border-eh-border pt-3 text-sm text-eh-text-muted">{{ $productoFiltro->nombre }}: <strong>{{ number_format((float) $productoFiltro->litros_por_unidad, 3) }} L</strong> de leche por {{ $productoFiltro->unidad_produccion }}</p>@endif
</x-ui.card>

<x-ui.card class="overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-eh-border p-5">
        <h2 class="text-base font-semibold">Ingredientes <span class="ml-1 text-sm font-normal text-eh-text-muted">{{ $materiales->count() }}</span></h2>
        @if ($puedeGestionar)<x-ui.btn icon="plus" data-modal-open="nuevo-ingrediente">Nuevo ingrediente</x-ui.btn>@endif
    </div>
    @if ($materiales->isEmpty())
        <x-ui.empty icon="cube" :title="$productoFiltro ? 'Este producto no tiene ingredientes configurados' : 'Aún no hay ingredientes'">
            @if ($puedeGestionar)
                <x-slot:action>
                    <x-ui.btn icon="plus" data-modal-open="nuevo-ingrediente">Agregar ingrediente</x-ui.btn>
                </x-slot:action>
            @endif
        </x-ui.empty>
    @else
        <x-ui.table :headers="['Ingrediente', 'Disponible', 'Estado', '']" caption="Ingredientes disponibles">
            @foreach ($materiales as $material)
                <tr class="border-b border-eh-border last:border-0">
                    <td class="px-5 py-4 text-sm font-semibold">{{ $material->nombre }}</td>
                    <td class="mono px-5 py-4 text-base font-semibold">{{ number_format($material->existencia, 3) }} <span class="text-xs font-normal text-eh-text-muted">{{ $material->unidad }}</span></td>
                    <td class="px-5 py-4"><x-ui.badge :variant="$material->existencia > 0 ? 'green' : 'yellow'" :label="$material->existencia > 0 ? 'Disponible' : 'Sin stock'" /></td>
                    <td class="px-5 py-4">
                        @if ($puedeGestionar)
                            <div class="flex justify-end gap-2">
                                <x-ui.btn size="sm" variant="secondary" data-modal-open="movimiento-ingrediente" :data-material-id="$material->id" data-stock-operation="entrada" :aria-label="'Registrar entrada de '.$material->nombre">+ Entrada</x-ui.btn>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </x-ui.table>
    @endif
</x-ui.card>
