{{-- Encabezado de tabla con menú para filtrar esa columna sin salir de la tabla. --}}
@props([
    'etiqueta',
    'nombre',
    'opciones' => [],
    'seleccion' => null,
    'todos' => 'Todas',
])

@php
    $base = array_diff_key(request()->query(), ['page' => true, $nombre => true]);
    $enlace = fn (array $parametros) => url()->current().($parametros ? '?'.http_build_query($parametros) : '');
    $actual = filled($seleccion) ? ($opciones[$seleccion] ?? null) : null;
@endphp

<details class="th-filtro" data-th-filtro>
    <summary @class(['th-filtro-activo' => $actual !== null])>
        <span>{{ $etiqueta }}</span>
        @if ($actual !== null)
            <span class="th-filtro-valor">· {{ $actual }}</span>
        @endif
        <x-icon name="chevronDown" class="h-3.5 w-3.5" />
        <span class="sr-only">Filtrar por {{ mb_strtolower($etiqueta) }}</span>
    </summary>
    <ul class="th-filtro-menu">
        <li><a href="{{ $enlace($base) }}" @if ($actual === null) aria-current="true" @endif>{{ $todos }}</a></li>
        @foreach ($opciones as $valor => $texto)
            <li><a href="{{ $enlace([...$base, $nombre => $valor]) }}" @if ((string) $seleccion === (string) $valor) aria-current="true" @endif>{{ $texto }}</a></li>
        @endforeach
    </ul>
</details>
