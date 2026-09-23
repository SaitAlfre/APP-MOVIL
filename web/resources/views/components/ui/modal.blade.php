@props([
    'id',
    'title',
    'size' => 'md',
])

@php
    $anchos = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
    ];
@endphp

<dialog id="{{ $id }}" data-modal aria-labelledby="{{ $id }}-titulo"
    class="w-full {{ $anchos[$size] ?? $anchos['md'] }} max-h-[90vh] rounded-2xl border border-eh-border bg-eh-surface p-0 text-eh-text shadow-2xl backdrop:bg-[#0c1612]/45 backdrop:backdrop-blur-sm open:animate-pop">
    <div class="flex items-center justify-between border-b border-eh-border px-6 py-5">
        <h2 id="{{ $id }}-titulo" class="text-base font-semibold tracking-[-.02em] text-eh-text">{{ $title }}</h2>
        <button type="button" data-modal-close class="grid h-8 w-8 place-items-center rounded-lg text-eh-text-muted transition-colors hover:bg-eh-surface-alt hover:text-eh-text" aria-label="Cerrar">
            <x-icon name="xMark" class="h-4 w-4" />
        </button>
    </div>
    <div class="max-h-[calc(90vh-4rem)] overflow-y-auto p-6">
        {{ $slot }}
    </div>
</dialog>
