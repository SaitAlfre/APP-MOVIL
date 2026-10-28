@extends('layouts.admin')
@section('titulo', 'Precio de la leche')
@section('contenido')
<x-ui.page-header title="Precio de la leche">
    <x-slot:actions><x-ui.btn :href="route('admin.liquidaciones.index')" variant="ghost">Ver liquidaciones</x-ui.btn></x-slot:actions>
</x-ui.page-header>
<x-ui.card padding="p-5" class="mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div><p class="text-sm text-eh-text-muted">Vigente hoy · {{ \Carbon\Carbon::parse($hoy)->format('d/m/Y') }}</p><p class="mono mt-2 text-3xl font-bold text-eh-primary">{{ $actual ? 'S/ '.number_format((float) $actual->precio, 3) : 'Sin configurar' }}<span class="text-sm font-normal"> / litro</span></p></div>
        <x-ui.btn icon="pencil" data-modal-open="precio-actual">Cambiar desde hoy</x-ui.btn>
    </div>
</x-ui.card>
<x-ui.modal id="precio-actual" title="Cambiar precio desde hoy">
    <form method="POST" action="{{ route('admin.tarifas.actual') }}" class="space-y-4" data-once>
        @csrf
        <x-ui.field name="precio" id="precio-hoy" label="Nuevo precio por litro" unit="S/" type="number" min="0.001" max="9999999" step="0.001" required :value="$actual?->precio" />
        <x-ui.field name="hasta" id="precio-hoy-hasta" label="Vigente hasta" type="date" required :min="$actual?->hasta->toDateString() ?? $hoy" :value="$actual?->hasta->toDateString() ?? today('America/Lima')->endOfYear()->toDateString()" />
        <div class="rounded-xl bg-eh-primary-soft p-3 text-sm text-eh-primary">Se aplica desde hoy. Los días anteriores conservan su precio.</div>
        <div class="flex justify-end gap-2"><x-ui.btn variant="ghost" data-modal-close>Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar precio de hoy</x-ui.btn></div>
    </form>
</x-ui.modal>
@if(auth('operador')->user()->puede('liquidaciones', 'gestionar'))
<x-ui.card padding="p-6" class="mb-6">
    <h2 class="mb-4 font-semibold">Programar un precio</h2>
    <form autocomplete="off" method="POST" action="{{ route('admin.tarifas.store') }}" class="grid gap-4 sm:grid-cols-3" data-once>
        @csrf
        <x-ui.field name="precio" label="Precio por litro (S/)" inputmode="decimal" type="number" min="0.001" max="9999999" step="0.001" required />
        <x-ui.field name="desde" label="Vigente desde" type="date" required />
        <x-ui.field name="hasta" label="Vigente hasta" type="date" required />
        <x-ui.btn type="submit">Guardar precio</x-ui.btn>
    </form>
</x-ui.card>
@endif
<div class="space-y-4">
@forelse($tarifas as $tarifa)
    @php
        $iniciada = $tarifa->desde->toDateString() <= $hoy;
        $vencida = $tarifa->hasta->toDateString() < $hoy;
    @endphp
    <x-ui.card padding="p-5">
        <div class="flex flex-wrap justify-between gap-2">
            <h2 class="font-semibold">S/ {{ number_format($tarifa->precio, 3) }} por litro</h2>
            <span>{{ $vencida ? 'Finalizado' : ($iniciada ? 'Vigente' : 'Programado') }}</span>
        </div>
        <p class="mt-2 text-sm text-eh-text-muted">{{ $tarifa->desde->format('d/m/Y') }} al {{ $tarifa->hasta->format('d/m/Y') }}</p>
        @if(!$vencida && auth('operador')->user()->puede('liquidaciones', 'gestionar'))
        <details class="mt-4">
            <summary class="cursor-pointer rounded text-sm underline focus-visible:ring-2 focus-visible:ring-eh-primary">{{ $iniciada ? 'Ajustar fecha de fin' : 'Editar precio programado' }}</summary>
            <form autocomplete="off" method="POST" action="{{ route('admin.tarifas.update', $tarifa->id) }}" class="mt-4 grid gap-4 sm:grid-cols-3" data-once>
                @csrf @method('PUT')
                <div><label class="block text-sm" for="precio-{{ $tarifa->id }}">Precio por litro (S/)</label><input class="mt-1 w-full rounded-xl border border-eh-border bg-eh-surface p-2 text-eh-text focus-visible:ring-2 focus-visible:ring-eh-primary" id="precio-{{ $tarifa->id }}" name="precio" inputmode="decimal" type="number" step="0.001" min="0.001" required value="{{ $tarifa->precio }}" @readonly($iniciada)></div>
                <div><label class="block text-sm" for="desde-{{ $tarifa->id }}">Vigente desde</label><input class="mt-1 w-full rounded-xl border border-eh-border bg-eh-surface p-2 text-eh-text focus-visible:ring-2 focus-visible:ring-eh-primary" id="desde-{{ $tarifa->id }}" name="desde" type="date" required value="{{ $tarifa->desde->toDateString() }}" @readonly($iniciada)></div>
                <div><label class="block text-sm" for="hasta-{{ $tarifa->id }}">Vigente hasta</label><input class="mt-1 w-full rounded-xl border border-eh-border bg-eh-surface p-2 text-eh-text focus-visible:ring-2 focus-visible:ring-eh-primary" id="hasta-{{ $tarifa->id }}" name="hasta" type="date" required value="{{ $tarifa->hasta->toDateString() }}" min="{{ $iniciada ? $hoy : $tarifa->desde->toDateString() }}"></div>
                <x-ui.btn type="submit">Guardar ajuste</x-ui.btn>
            </form>
        </details>
        @endif
    </x-ui.card>
@empty
    <x-ui.alert type="info">Aún no hay precios configurados. El proveedor verá «Precio no configurado» hasta recibir una tarifa vigente.</x-ui.alert>
@endforelse
</div>
@endsection
