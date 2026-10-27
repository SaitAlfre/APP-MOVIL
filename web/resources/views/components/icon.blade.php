@props([
    'name',
    'class' => 'w-5 h-5',
])

@php
    // Catálogo Lucide local: el nombre solicitado nunca se utiliza como ruta ni como SVG.
    $catalogo = once(fn () => json_decode(file_get_contents(resource_path('icons.json')), true, 512, JSON_THROW_ON_ERROR));
    $cuerpo = $catalogo['icons'][$name] ?? $catalogo['icons']['info'];
@endphp

<svg {{ $attributes->merge(['class' => $class, 'aria-hidden' => 'true', 'focusable' => 'false']) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" data-icon="{{ array_key_exists($name, $catalogo['icons']) ? $name : 'info' }}">
    {!! $cuerpo !!}
</svg>
