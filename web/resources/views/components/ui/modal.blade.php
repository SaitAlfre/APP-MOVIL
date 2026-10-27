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
    class="ui-modal {{ $anchos[$size] ?? $anchos['md'] }} rounded-2xl border border-eh-border bg-eh-surface p-0 text-eh-text shadow-2xl backdrop:bg-black/45 backdrop:backdrop-blur-sm">
    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-eh-border px-4 py-3 sm:px-6 sm:py-4">
        <h2 id="{{ $id }}-titulo" tabindex="-1" class="min-w-0 break-words text-base font-semibold tracking-[-.02em] text-eh-text">{{ $title }}</h2>
        <button type="button" data-modal-close class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-eh-text-muted transition-colors hover:bg-eh-surface-alt hover:text-eh-text" aria-label="Cerrar">
            <x-icon name="xMark" class="h-4 w-4" />
        </button>
    </div>
    <div class="ui-modal-body min-h-0 overflow-y-auto p-4 sm:p-6">
        {{ $slot }}
    </div>
</dialog>
