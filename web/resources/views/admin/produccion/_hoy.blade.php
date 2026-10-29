@php
    $leche = $resumen['leche'];
    $porcentajeAsignado = $leche['total'] > 0 ? min(100, $leche['asignada'] / $leche['total'] * 100) : 0;
    $etiquetasLote = ['borrador' => ['Reservado', 'bg-eh-blue'], 'en_proceso' => ['En proceso', 'bg-eh-gold'], 'finalizado' => ['Terminado', 'bg-eh-success'], 'cancelado' => ['Cancelado', 'bg-eh-text-muted']];
@endphp

<x-ui.card padding="p-5">
    <h2 class="mb-4 flex items-center gap-2 text-base font-semibold"><x-icon name="droplets" class="h-5 w-5 text-eh-primary" />Leche de hoy</h2>
    @if ($leche['total'] <= 0)
        <p class="rounded-xl bg-eh-surface-alt px-4 py-6 text-center text-sm text-eh-text-muted">Todavía no llega leche acopiada hoy.</p>
    @else
        <div class="flex items-end justify-between">
            <p class="mono text-3xl font-semibold tracking-tight" data-count-up="{{ $leche['disponible'] }}">{{ number_format($leche['disponible'], 1) }}</p>
            <p class="text-xs text-eh-text-muted">L sin asignar de {{ number_format($leche['total'], 1) }} L</p>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-eh-surface-alt" role="img" aria-label="{{ round($porcentajeAsignado) }} % de la leche de hoy ya está en lotes">
            <div class="chart-bar-x h-full rounded-full bg-eh-primary" style="width: {{ $porcentajeAsignado }}%"></div>
        </div>
        <p class="mt-2 text-xs text-eh-text-muted">{{ number_format($leche['asignada'], 1) }} L ya están en lotes. La leche se procesa el mismo día.</p>
    @endif
</x-ui.card>

<x-ui.card padding="p-5">
    <div class="mb-4 flex items-center justify-between gap-2">
        <h2 class="flex items-center gap-2 text-base font-semibold"><x-icon name="clock" class="h-5 w-5 text-eh-primary" />Lotes de hoy</h2>
        <a href="{{ route('admin.produccion.historial.index') }}" class="text-xs font-semibold text-eh-primary hover:underline">Ver todos</a>
    </div>
    @forelse ($resumen['lotes'] as $lote)
        @php [$etiqueta, $punto] = $etiquetasLote[$lote->estado] ?? [$lote->estado, 'bg-eh-text-muted']; @endphp
        <div class="mb-2 flex items-start gap-3 rounded-xl border border-eh-border p-3 last:mb-0">
            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $punto }}" aria-hidden="true"></span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $lote->producto }}</p>
                <p class="text-xs text-eh-text-muted">{{ $etiqueta }} · {{ number_format((float) $lote->litros_asignados, 0) }} L · {{ $lote->unidades_producidas ?? $lote->unidades_estimadas }} u</p>
            </div>
            <span class="mono shrink-0 text-xs text-eh-text-muted">{{ \Illuminate\Support\Carbon::parse($lote->finalizado_en ?? $lote->iniciado_en ?? $lote->created_at)->timezone('America/Lima')->format('H:i') }}</span>
        </div>
    @empty
        <p class="rounded-xl bg-eh-surface-alt px-4 py-6 text-center text-sm text-eh-text-muted">Aún no hay lotes hoy.</p>
    @endforelse
    @if (auth('operador')->user()->puede('produccion', 'gestionar') && $leche['disponible'] > 0)
        <x-ui.btn :href="route('admin.produccion.producir.index')" size="sm" icon="plus" class="mt-3 w-full">Producir con la leche de hoy</x-ui.btn>
    @endif
</x-ui.card>

@if ($resumen['insumosBajos']->isNotEmpty())
    <x-ui.card padding="p-5">
        <h2 class="mb-3 flex items-center gap-2 text-base font-semibold"><x-icon name="exclamation" class="h-5 w-5 text-eh-gold" />Insumos por reponer</h2>
        @foreach ($resumen['insumosBajos'] as $material)
            <div class="flex items-center justify-between gap-3 border-b border-eh-border py-2 text-sm last:border-0">
                <span class="truncate">{{ $material->nombre }}</span>
                <span @class(['mono shrink-0 text-xs font-semibold', 'text-eh-red' => (float) $material->existencia <= 0, 'text-eh-gold' => (float) $material->existencia > 0])>{{ number_format((float) $material->existencia, 2) }} {{ $material->unidad }}</span>
            </div>
        @endforeach
        <a href="{{ route('admin.inventario.index', ['tab' => 'materiales']) }}" class="mt-3 inline-block text-xs font-semibold text-eh-primary hover:underline">Registrar compra</a>
    </x-ui.card>
@endif
