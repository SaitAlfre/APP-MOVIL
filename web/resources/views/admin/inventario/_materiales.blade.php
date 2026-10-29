@php
    $estadoMaterial = fn ($material) => match (true) {
        (float) $material->existencia <= 0 => 'agotado',
        $material->stock_minimo !== null && (float) $material->existencia <= (float) $material->stock_minimo => 'bajo',
        default => 'disponible',
    };
    $etiquetasMaterial = ['disponible' => ['green', 'Disponible', 'bg-eh-success'], 'bajo' => ['yellow', 'Stock bajo', 'bg-eh-gold'], 'agotado' => ['red', 'Sin stock', 'bg-eh-red']];
    $conteosMaterial = $materiales->countBy($estadoMaterial);
@endphp

<x-ui.card padding="p-5" class="mb-4">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-eh-primary-soft text-eh-primary"><x-icon name="droplets" class="h-6 w-6" /></span>
            <div><h2 class="text-sm font-medium text-eh-text-muted">Leche de hoy sin asignar</h2><p class="mono mt-1 text-2xl font-bold text-eh-primary">{{ number_format(collect($lecheDisponible)->sum('litrosDisponibles'), 3) }} L</p></div>
        </div>
        <span class="text-xs text-eh-text-muted">La leche se procesa el mismo día</span>
    </div>
    @if ($productoFiltro && $productoFiltro->litros_por_unidad !== null)<p class="mt-4 border-t border-eh-border pt-3 text-sm text-eh-text-muted">{{ $productoFiltro->nombre }}: <strong>{{ number_format((float) $productoFiltro->litros_por_unidad, 3) }} L</strong> de leche por {{ $productoFiltro->unidad_produccion }}</p>@endif
</x-ui.card>

<div data-live-filter="insumos">
    <div class="mb-4 flex flex-wrap items-center gap-3">
        <nav aria-label="Filtrar insumos" class="inline-flex max-w-full flex-wrap gap-1 rounded-xl border border-eh-border bg-eh-surface p-1">
            @foreach (['todos' => ['Todos', $materiales->count()], 'disponible' => ['Disponibles', $conteosMaterial['disponible'] ?? 0], 'bajo' => ['Stock bajo', $conteosMaterial['bajo'] ?? 0], 'agotado' => ['Sin stock', $conteosMaterial['agotado'] ?? 0]] as $clave => [$etiqueta, $total])
                <button type="button" data-live-tab="{{ $clave }}" aria-pressed="{{ $clave === 'todos' ? 'true' : 'false' }}"
                    class="inline-flex min-h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium text-eh-text-muted transition hover:text-eh-text aria-pressed:bg-eh-surface-alt aria-pressed:text-eh-text aria-pressed:shadow-sm">
                    {{ $etiqueta }} <span class="mono rounded-md bg-eh-surface-alt px-1.5 text-[11px]" data-live-count="{{ $clave }}">{{ $total }}</span>
                </button>
            @endforeach
        </nav>
        <div class="relative min-w-56 flex-1 sm:max-w-xs">
            <label for="buscar-insumos" class="sr-only">Buscar insumos</label>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-eh-text-muted" />
            <input id="buscar-insumos" type="search" data-live-search="insumos" placeholder="Buscar insumo…" autocomplete="off"
                class="min-h-11 w-full rounded-xl border border-eh-border-strong bg-eh-surface pl-9 pr-3 text-sm focus:border-eh-sage focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15">
        </div>
        @if ($puedeGestionar)<x-ui.btn icon="plus" data-modal-open="nuevo-ingrediente" class="ml-auto">Nuevo ingrediente</x-ui.btn>@endif
    </div>

    @if ($materiales->isEmpty())
        <x-ui.card>
            <x-ui.empty icon="cube" :title="$productoFiltro ? 'Este producto no tiene ingredientes configurados' : 'Aún no hay ingredientes'">
                @if ($puedeGestionar)
                    <x-slot:action>
                        <x-ui.btn icon="plus" data-modal-open="nuevo-ingrediente">Agregar ingrediente</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        </x-ui.card>
    @else
        <ul class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3" aria-label="Ingredientes disponibles">
            @foreach ($materiales as $material)
                @php
                    $estado = $estadoMaterial($material);
                    [$color, $etiqueta, $barra] = $etiquetasMaterial[$estado];
                    $referencia = max((float) ($material->stock_minimo ?? 0) * 3, (float) $material->existencia, 0.001);
                @endphp
                <li data-live-item data-live-group="{{ $estado }}" data-live-text="{{ mb_strtolower($material->nombre) }}" data-stock-card class="flex flex-col rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <x-ui.avatar :name="$material->nombre" size="h-10 w-10 text-xs" />
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold">{{ $material->nombre }}</p>
                            <p class="text-xs text-eh-text-muted">Se mide en {{ $material->unidad }}</p>
                        </div>
                        <x-ui.badge :variant="$color" :label="$etiqueta" data-stock-badge data-label-agotado="Sin stock" />
                    </div>
                    <p class="mono mt-4 text-2xl font-semibold">{{ number_format($material->existencia, 3) }} <span class="text-xs font-normal text-eh-text-muted">{{ $material->unidad }}</span></p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-eh-surface-alt" aria-hidden="true">
                        <div class="chart-bar-x h-full rounded-full {{ $barra }}" style="width: {{ min(100, (float) $material->existencia / $referencia * 100) }}%"></div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-start justify-between gap-3 border-t border-eh-border pt-3">
                        <x-ui.stock-minimo :action="route('admin.inventario.materiales.minimo', $material->id)" :id="'minimo-'.$material->id" :value="$material->stock_minimo"
                            :unit="$material->unidad" :existencia="$material->existencia" :nombre="$material->nombre" :editable="$puedeGestionar" />
                        @if ($puedeGestionar)
                            <x-ui.btn size="sm" variant="secondary" data-modal-open="movimiento-ingrediente" :data-material-id="$material->id" data-stock-operation="entrada" :aria-label="'Registrar entrada de '.$material->nombre">+ Entrada</x-ui.btn>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
        <div data-live-empty hidden class="mt-4">
            <x-ui.card><x-ui.empty icon="search" title="Ningún insumo coincide" description="Prueba con otra palabra o elige otra pestaña." /></x-ui.card>
        </div>
    @endif
</div>
