{{-- Barra única de filtros de acopio: búsqueda, rango de fechas, estado y periodo rápido se aplican solos. --}}
@props([
    'formId',
    'action',
    'estados' => [],
    'estado' => null,
    'periodos' => [],
    'periodo' => null,
    'buscar' => null,
    'placeholder' => 'Buscar…',
    'desde' => null,
    'hasta' => null,
    'acciones' => null,
])

<div class="mb-4 space-y-2 rounded-2xl border border-eh-border bg-eh-surface p-2">
    <div class="flex flex-wrap items-center gap-2">
        <form id="{{ $formId }}" method="GET" action="{{ $action }}" data-live-filter class="contents">
            <input type="hidden" name="estado" value="{{ $estado ?? '' }}">
            <input type="hidden" name="periodo" value="{{ $periodo ?? '' }}">
            <div class="min-w-56 flex-1"><x-ui.search name="buscar" :value="$buscar" label="Buscar" :placeholder="$placeholder" maxlength="120" autocomplete="off" /></div>
            <div class="flex items-center gap-1">
                <x-ui.field name="desde" type="date" :value="$desde" aria-label="Desde" data-limpia="periodo" class="w-38" />
                <span class="text-xs text-eh-text-muted" aria-hidden="true">–</span>
                <x-ui.field name="hasta" type="date" :value="$hasta" aria-label="Hasta" data-limpia="periodo" class="w-38" />
            </div>
            {{ $slot }}
        </form>
        @if ($acciones)
            {{ $acciones }}
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <x-ui.status-tabs name="estado" :form-id="$formId" :value="$estado ?? ''" :options="$estados" />
        <x-ui.status-tabs name="periodo" :form-id="$formId" :value="$periodo ?? ''" :options="$periodos" label="Filtrar por periodo" />
    </div>
</div>
