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
    'controlClass' => '',
    'translateOptions' => false,
])

@php
    $campoId = $id ?? $name;
    $mensaje = $error ?? ($name ? $errors->first($name) : null);
    $actual = old($name, $selected);
@endphp

<div {{ $attributes->only('class')->class('ui-field flex min-w-0 flex-col gap-1.5') }}>
    @if ($label)
        <label data-i18n for="{{ $campoId }}" class="text-xs font-medium text-eh-text">
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
        @if ($mensaje) aria-invalid="true" aria-describedby="{{ $campoId }}-error" @elseif ($hint) aria-describedby="{{ $campoId }}-hint" @endif
        {{ $attributes->except('class')->class([
                $controlClass,
            'min-h-11 min-w-0 w-full rounded-lg border bg-eh-surface px-3.5 py-2.5 text-[13px] text-eh-text transition focus:outline-none focus:ring-[3px]',
            'border-eh-red focus:border-eh-red focus:ring-eh-red/15' => (bool) $mensaje,
            'border-eh-border-strong focus:border-eh-sage focus:ring-eh-sage/15' => ! $mensaje,
        ]) }}>
        @if ($placeholder !== null)
            <option data-i18n value="">{{ $placeholder }}</option>
        @endif
        @if (! empty($options))
            @foreach ($options as $valor => $etiqueta)
                <option @if ($translateOptions) data-i18n @endif value="{{ $valor }}" @selected((string) $actual === (string) $valor)>{{ $etiqueta }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if ($mensaje)
        <p id="{{ $campoId }}-error" class="text-xs text-eh-red">{{ $mensaje }}</p>
    @elseif ($hint)
        <p data-i18n id="{{ $campoId }}-hint" class="text-xs text-eh-text-muted">{{ $hint }}</p>
    @endif
</div>
