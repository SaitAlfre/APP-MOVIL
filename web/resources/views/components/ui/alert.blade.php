@props([
    'type' => 'info',
    'title' => null,
])

@php
    $estilos = [
        'success' => ['border-eh-success/20 bg-eh-success-soft text-eh-success', 'check'],
        'error' => ['border-eh-red/20 bg-eh-red-soft text-eh-red', 'exclamation'],
        'warning' => ['border-eh-gold/20 bg-eh-gold-soft text-eh-gold', 'exclamation'],
        'info' => ['border-eh-blue/15 bg-eh-blue-soft text-eh-blue', 'info'],
    ];
    [$clases, $icono] = $estilos[$type] ?? $estilos['info'];
@endphp

<div role="{{ $type === 'error' ? 'alert' : 'status' }}" {{ $attributes->class(['flex items-start gap-3 rounded-2xl border px-4 py-3 text-sm', $clases]) }}>
    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-current/10">
        <x-icon :name="$icono" class="h-3.5 w-3.5" />
    </span>
    <div class="min-w-0 flex-1 pt-0.5">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => (bool) $title])>{{ $slot }}</div>
    </div>
</div>
