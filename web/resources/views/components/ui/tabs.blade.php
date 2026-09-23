@props([
    'tabs' => [],
    'active' => null,
])

<div {{ $attributes->class('mb-6 max-w-full overflow-x-auto') }}>
    <div class="inline-flex min-w-max gap-1 rounded-xl bg-eh-surface-alt p-1">
        @foreach ($tabs as $tab)
            @php $esActivo = ($tab['key'] ?? $tab['label']) === $active; @endphp
            <a href="{{ $tab['url'] }}" @class([
                'whitespace-nowrap rounded-lg px-3.5 py-2 text-xs font-semibold transition-all duration-200',
                'border-eh-primary text-eh-primary bg-eh-surface shadow-sm' => $esActivo,
                'text-eh-text-muted hover:text-eh-text' => ! $esActivo,
            ]) @if ($esActivo) aria-current="page" @endif>{{ $tab['label'] }}</a>
        @endforeach
    </div>
</div>
