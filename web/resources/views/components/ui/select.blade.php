@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'error' => null,
    'hint' => null,
    'required' => false,
])

@php
    $campoId = $id ?? $name;
    $mensaje = $error ?? ($name ? $errors->first($name) : null);
    $actual = old($name, $selected);
@endphp

<div class="flex flex-col gap-1">
    @if ($label)
        <label for="{{ $campoId }}" class="text-[11px] font-semibold text-eh-text">
            {{ $label }}
            @if ($required)
                <span class="text-eh-red" aria-hidden="true">*</span>
                <span class="sr-only">(obligatorio)</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $campoId }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($mensaje) aria-invalid="true" aria-describedby="{{ $campoId }}-error" @endif
        {{ $attributes->class([
            'w-full rounded-xl border bg-eh-surface px-3.5 py-2.5 text-[13px] text-eh-text transition focus:outline-none focus:ring-[3px]',
            'border-eh-red focus:border-eh-red focus:ring-eh-red/15' => (bool) $mensaje,
            'border-eh-border-strong focus:border-eh-sage focus:ring-eh-sage/15' => ! $mensaje,
        ]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @if (! empty($options))
            @foreach ($options as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if ($mensaje)
        <p id="{{ $campoId }}-error" class="text-xs text-eh-red">{{ $mensaje }}</p>
    @elseif ($hint)
        <p class="text-xs text-eh-text-muted">{{ $hint }}</p>
    @endif
</div>
