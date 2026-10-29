@props([
    'name',
    'src' => null,
    'size' => 'h-12 w-12 text-sm',
])

@php
    $iniciales = collect(preg_split('/\s+/u', trim($name)))->filter(fn ($parte) => preg_match('/^\pL/u', $parte))
        ->take(2)->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))->implode('') ?: '·';
    $tonos = ['bg-eh-red text-white', 'bg-eh-blue text-white', 'bg-eh-success text-white', 'bg-eh-gold text-white', 'bg-eh-sage text-white'];
    $tono = $tonos[crc32($name) % count($tonos)];
@endphp

@if ($src)
    <img src="{{ $src }}" alt="" {{ $attributes->class([$size, 'shrink-0 rounded-full object-cover ring-2 ring-eh-surface']) }} loading="lazy">
@else
    <span aria-hidden="true" {{ $attributes->class([$size, $tono, 'grid shrink-0 place-items-center rounded-full font-bold tracking-wide']) }}>{{ $iniciales }}</span>
@endif
