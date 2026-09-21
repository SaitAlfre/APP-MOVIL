@props([
    'type' => 'info',
    'title' => null,
])

@php
    $estilos = [
        'success' => ['bg-eh-primary-soft border-eh-primary/40 text-eh-primary-dark', 'check'],
        'error' => ['bg-eh-red-soft border-eh-red/40 text-eh-red', 'exclamation'],
        'warning' => ['bg-eh-gold-soft border-eh-gold/40 text-eh-gold', 'exclamation'],
        'info' => ['bg-eh-blue-soft border-eh-blue/40 text-eh-blue', 'info'],
    ];
    [$clases, $icono] = $estilos[$type] ?? $estilos['info'];
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3 text-sm', $clases]) }}>
    <x-icon :name="$icono" class="mt-0.5 h-4 w-4 shrink-0" />
    <div class="min-w-0 flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => (bool) $title])>{{ $slot }}</div>
    </div>
</div>
