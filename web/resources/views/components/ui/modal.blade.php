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
    class="w-full {{ $anchos[$size] ?? $anchos['md'] }} max-h-[90vh] rounded-2xl border border-eh-border bg-eh-surface p-0 text-eh-text shadow-xl backdrop:bg-black/40">
    <div class="flex items-center justify-between border-b border-eh-border px-6 py-4">
        <h2 id="{{ $id }}-titulo" class="text-base font-semibold text-eh-text">{{ $title }}</h2>
        <button type="button" data-modal-close class="flex h-7 w-7 items-center justify-center rounded-lg text-eh-text-muted transition-colors hover:bg-eh-surface-alt" aria-label="Cerrar">
            <x-icon name="xMark" class="h-4 w-4" />
        </button>
    </div>
    <div class="max-h-[calc(90vh-4rem)] overflow-y-auto p-6">
        {{ $slot }}
    </div>
</dialog>
