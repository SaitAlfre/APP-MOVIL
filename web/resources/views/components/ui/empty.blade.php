@props([
    'icon' => 'info',
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex flex-col items-center justify-center px-6 py-12 text-center') }}>
    <span class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-eh-surface-alt text-eh-text-muted">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="text-sm font-semibold text-eh-text">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-sm text-xs text-eh-text-muted">{{ $description }}</p>
    @endif
    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
