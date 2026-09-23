@props([
    'headers' => [],
    'caption' => null,
])

<div {{ $attributes->class('overflow-x-auto') }}>
    <table class="w-full text-xs">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        @if (! empty($headers))
            <thead>
                <tr class="border-b border-eh-border">
                    @foreach ($headers as $encabezado)
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-left text-[10px] font-semibold uppercase tracking-[.12em] text-eh-text-muted">{{ $encabezado }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
