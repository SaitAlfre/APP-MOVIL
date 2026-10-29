@props([
    'label',
    'variant' => 'gray',
    'nombre' => '',
    'detalle' => null,
    /** @var list<array{etiqueta: string, descripcion: string, icono: string, modal?: string, datos?: array<string, string>}> */
    'opciones' => [],
])

{{-- Insignia de estado que, si hay cambios permitidos, abre un menú; cada opción abre su confirmación. --}}
@if ($opciones === [])
    <x-ui.badge :variant="$variant" :title="$detalle">{{ $label }}@if ($detalle)<span class="sr-only">. {{ $detalle }}</span>@endif</x-ui.badge>
@else
    <details class="gestion-action-menu gestion-estado-menu">
        <summary aria-label="Estado de {{ $nombre }}: {{ $label }}. Cambiar estado" title="{{ $detalle ? $detalle.' · ' : '' }}Cambiar estado">
            <x-ui.badge :variant="$variant">{{ $label }}<x-icon name="chevronDown" class="h-3 w-3" /></x-ui.badge>
        </summary>
        <div class="gestion-estado-opciones">
            @foreach ($opciones as $opcion)
                <button type="button" @isset($opcion['modal']) data-modal-open="{{ $opcion['modal'] }}" @endisset {{ new \Illuminate\View\ComponentAttributeBag($opcion['datos'] ?? []) }}>
                    <x-icon :name="$opcion['icono']" class="mt-0.5 h-4 w-4 shrink-0" />
                    <span><span class="block font-medium">{{ $opcion['etiqueta'] }}</span><span class="block text-[11px] text-eh-text-muted">{{ $opcion['descripcion'] }}</span></span>
                </button>
            @endforeach
        </div>
    </details>
@endif
