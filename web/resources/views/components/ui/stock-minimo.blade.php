@props([
    'action',
    'id',
    'value' => null,
    'unit',
    'existencia',
    'nombre',
    'editable' => true,
])

@php
    $valor = $value !== null ? rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.') : '';
    $texto = $value !== null ? $valor.' '.$unit : 'Sin aviso';
@endphp

@if ($editable)
    {{-- Muestra el mínimo guardado; «Editar stock mínimo» abre el campo con Guardar y Cancelar. --}}
    <div {{ $attributes->class('min-w-0') }} data-min-editor data-existencia="{{ (float) $existencia }}" data-unit="{{ $unit }}">
        <div class="flex items-center gap-2" data-min-view>
            <div class="min-w-0">
                <p class="text-xs font-medium text-eh-text-muted">Avisar cuando queden</p>
                <p class="mono mt-0.5 text-sm font-semibold" data-min-value>{{ $texto }}</p>
            </div>
            <details class="gestion-action-menu">
                <summary aria-label="Opciones del stock mínimo de {{ $nombre }}" title="Opciones del stock mínimo"><x-icon name="ellipsis" class="h-4 w-4" /></summary>
                <div class="gestion-estado-opciones">
                    <button type="button" data-min-open>
                        <x-icon name="pencil" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span><span class="block font-medium">Editar stock mínimo</span><span class="block text-[11px] text-eh-text-muted">Cambia la cantidad que activa el aviso.</span></span>
                    </button>
                    <button type="button" data-min-clear @if ($value === null) hidden @endif>
                        <x-icon name="xMark" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span><span class="block font-medium">Quitar aviso</span><span class="block text-[11px] text-eh-text-muted">Ya no se marcará «Stock bajo».</span></span>
                    </button>
                </div>
            </details>
        </div>

        <form method="POST" action="{{ $action }}" data-min-form hidden>
            @csrf
            @method('PATCH')
            <label for="{{ $id }}" class="mb-1 block text-xs font-medium text-eh-text-muted">Avisar cuando queden</label>
            <div class="flex flex-wrap items-center gap-2">
                <div class="relative w-32">
                    <input id="{{ $id }}" name="stock_minimo" type="number" min="0" max="999999999" step="any" inputmode="decimal" value="{{ $valor }}" placeholder="Sin aviso"
                        aria-describedby="{{ $id }}-estado" data-saved="{{ $valor }}"
                        class="min-h-10 w-full rounded-lg border border-eh-border-strong bg-eh-surface py-2 pl-3 pr-10 text-sm font-semibold focus:border-eh-sage focus:outline-none focus:ring-[3px] focus:ring-eh-sage/15">
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-eh-text-muted">{{ $unit }}</span>
                </div>
                <x-ui.btn type="submit" size="sm">Guardar</x-ui.btn>
                <x-ui.btn type="button" size="sm" variant="ghost" data-min-cancel>Cancelar</x-ui.btn>
            </div>
        </form>
        <p id="{{ $id }}-estado" data-min-status class="mt-1 min-h-4 text-[11px] text-eh-text-muted" aria-live="polite"></p>
    </div>
@else
    <div {{ $attributes->class('min-w-0') }}>
        <p class="text-xs font-medium text-eh-text-muted">Avisar cuando queden</p>
        <p class="mono mt-0.5 text-sm font-semibold">{{ $texto }}</p>
    </div>
@endif
