@extends('layouts.admin')

@section('titulo', 'Jornada')

@php
    $inputClass = 'block h-11 w-full rounded-xl border border-eh-border bg-eh-bg px-3.5 text-[13.5px] text-eh-text focus:border-eh-primary focus:ring-eh-primary';
    $labelClass = 'mb-1.5 block text-[12.5px] font-semibold text-eh-text';
@endphp

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">{{ $acopiador?->nombres ?? 'Acopiador' }}</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">{{ $zona?->nombre ?? '—' }} · {{ $vehiculo?->nombre ?? '—' }} ({{ $vehiculo?->placa }}) · {{ $jornada->fecha->format('d/m/Y') }}</p>
        </div>
        <span @class([
            'rounded-full px-2.5 py-1 text-[11px] font-semibold',
            'bg-eh-blue-soft text-eh-blue' => $jornada->estaAbierta(),
            'bg-eh-surface-alt text-eh-text-muted' => ! $jornada->estaAbierta(),
        ])>
            {{ $jornada->estaAbierta() ? 'Jornada abierta' : 'Jornada cerrada' }}
        </span>
    </div>

    @if (session('estado'))
        <div class="mb-4 rounded-xl bg-eh-primary-soft px-4 py-3 text-[13px] font-medium text-eh-primary">{{ session('estado') }}</div>
    @endif

    <div class="mb-6 grid grid-cols-2 gap-4">
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Litros</p>
            <p class="mt-1 text-2xl font-bold text-eh-text">{{ number_format($litros, 1) }} <span class="text-sm font-semibold text-eh-text-muted">L</span></p>
        </div>
        <div class="rounded-2xl border border-eh-border bg-eh-surface p-4 shadow-sm">
            <p class="text-[11.5px] font-semibold uppercase tracking-wide text-eh-text-muted">Entregas</p>
            <p class="mt-1 text-2xl font-bold text-eh-text">{{ $entregas }}</p>
        </div>
    </div>

    @if ($jornada->estaAbierta())
        @if (session('duplicado'))
            @php($duplicado = session('duplicado'))
            <div class="mb-6 rounded-xl border border-eh-gold/40 bg-eh-gold-soft p-4 text-sm text-eh-text">
                <p class="mb-3">Este proveedor ya tiene una entrega registrada en esta jornada
                    ({{ number_format($duplicado['litros_existentes'], 1) }} L · {{ $duplicado['tachos_existentes'] }} tachos).
                    ¿Quieres sumar esta cantidad a la existente o registrarla aparte?</p>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.acopiadores.entregas.sumar') }}">
                        @csrf
                        <input type="hidden" name="entrega_id" value="{{ $duplicado['entrega_id'] }}">
                        <input type="hidden" name="litros" value="{{ $duplicado['litros_existentes'] + $duplicado['litros_nuevos'] }}">
                        <input type="hidden" name="tachos" value="{{ $duplicado['tachos_existentes'] + $duplicado['tachos_nuevos'] }}">
                        <button type="submit" class="rounded-lg bg-eh-primary px-3 py-2 text-xs font-semibold text-white">Sumar</button>
                    </form>
                    <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada->id) }}">
                        @csrf
                        <input type="hidden" name="proveedor_id" value="{{ old('proveedor_id') }}">
                        <input type="hidden" name="litros" value="{{ $duplicado['litros_nuevos'] }}">
                        <input type="hidden" name="tachos" value="{{ $duplicado['tachos_nuevos'] }}">
                        <input type="hidden" name="observaciones" value="{{ old('observaciones') }}">
                        <input type="hidden" name="forzar" value="1">
                        <button type="submit" class="rounded-lg border border-eh-border bg-eh-surface px-3 py-2 text-xs font-semibold text-eh-text">Registrar aparte</button>
                    </form>
                </div>
            </div>
        @endif

        <div class="mb-6 rounded-2xl border border-eh-border bg-eh-surface p-5 shadow-sm">
            <h2 class="mb-4 text-[14px] font-bold text-eh-text">Registrar entrega</h2>

            @if ($errors->any())
                <div class="mb-4 rounded-xl bg-eh-red-soft px-4 py-3 text-[13px] font-medium text-eh-red">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('admin.acopiadores.entregas.store', $jornada->id) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="proveedor_id" class="{{ $labelClass }}">Proveedor</label>
                    <select id="proveedor_id" name="proveedor_id" required class="{{ $inputClass }}">
                        <option value="">Selecciona un proveedor</option>
                        @foreach ($proveedores as $proveedor)
                            <option value="{{ $proveedor->id }}" @selected(old('proveedor_id') == $proveedor->id)>{{ $proveedor->codigo }} · {{ $proveedor->nombres }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="litros" class="{{ $labelClass }}">Litros</label>
                        <input id="litros" name="litros" type="number" step="0.01" min="0.01" value="{{ old('litros') }}" required class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label for="tachos" class="{{ $labelClass }}">Tachos</label>
                        <input id="tachos" name="tachos" type="number" min="1" value="{{ old('tachos', 1) }}" required class="{{ $inputClass }}">
                    </div>
                </div>

                <div>
                    <label for="observaciones" class="{{ $labelClass }}">Observaciones</label>
                    <input id="observaciones" name="observaciones" type="text" value="{{ old('observaciones') }}" class="{{ $inputClass }}">
                </div>

                <button type="submit" class="h-11 w-full rounded-xl bg-eh-primary text-[13.5px] font-bold text-white hover:bg-eh-primary-dark">
                    Guardar entrega
                </button>
            </form>
        </div>
    @endif

    <h2 class="mb-2 text-[13px] font-bold text-eh-text">Entregas recientes</h2>
    <div class="mb-6 divide-y divide-eh-border rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        @forelse ($recientes as $entrega)
            <div class="px-4 py-3 text-sm {{ $entrega->anulada ? 'opacity-50' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-eh-text">{{ $proveedores->get($entrega->proveedorId)?->nombres ?? 'Proveedor #'.$entrega->proveedorId }}</span>
                    <span class="text-eh-text-muted">{{ number_format($entrega->litros, 1) }} L · {{ $entrega->tachos }} tachos</span>
                </div>
                @if ($entrega->anulada)
                    <p class="mt-1 text-xs font-semibold text-eh-red">Anulada</p>
                @elseif ($jornada->estaAbierta())
                    <form method="POST" action="{{ route('admin.acopiadores.entregas.anular', $entrega->id) }}" class="mt-2 flex gap-2">
                        @csrf
                        <input type="text" name="motivo" placeholder="Motivo de anulación" required
                            class="h-9 flex-1 rounded-lg border border-eh-border bg-eh-bg px-3 text-xs text-eh-text focus:border-eh-red focus:ring-eh-red">
                        <button type="submit" class="text-xs font-semibold text-eh-red">Anular</button>
                    </form>
                @endif
            </div>
        @empty
            <p class="px-4 py-8 text-center text-[13px] text-eh-text-muted">Aún no hay entregas en esta jornada.</p>
        @endforelse
    </div>

    @if ($jornada->estaAbierta())
        <form method="POST" action="{{ route('admin.acopiadores.jornadas.cerrar', $jornada->id) }}"
            onsubmit="return confirm('¿Seguro que quieres cerrar la jornada?');">
            @csrf
            @method('PATCH')
            <button type="submit" class="h-11 w-full rounded-xl border border-eh-red/40 text-[13.5px] font-semibold text-eh-red hover:bg-eh-red-soft">
                Cerrar jornada
            </button>
        </form>
    @endif

    <a href="{{ route('admin.acopiadores.index') }}" class="mt-4 block text-center text-[13.5px] font-medium text-eh-text-muted hover:text-eh-text">Volver al listado</a>
@endsection
