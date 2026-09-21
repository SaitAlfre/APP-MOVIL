@props([
    'tabs' => [],
    'active' => null,
])

<div {{ $attributes->class('mb-6 flex items-center gap-1 overflow-x-auto border-b border-eh-border') }}>
    @foreach ($tabs as $tab)
        @php $esActivo = ($tab['key'] ?? $tab['label']) === $active; @endphp
        <a href="{{ $tab['url'] }}" @class([
            '-mb-px whitespace-nowrap border-b-2 px-4 py-2.5 text-sm font-medium transition-colors',
            'border-eh-primary text-eh-primary' => $esActivo,
            'border-transparent text-eh-text-muted hover:text-eh-text' => ! $esActivo,
        ]) @if ($esActivo) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
</div>
