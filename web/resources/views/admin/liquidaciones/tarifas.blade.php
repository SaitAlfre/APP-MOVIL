@extends('layouts.admin')
@section('titulo', 'Precios por temporada')
@section('contenido')
<x-ui.page-header title="Precios por temporada" description="Precio general para todos los proveedores, en soles por litro. Las fechas de inicio y fin están incluidas y se interpretan en hora de Perú.">
    <x-slot:actions><x-ui.btn :href="route('admin.liquidaciones.index')" variant="ghost">Ver liquidaciones</x-ui.btn></x-slot:actions>
</x-ui.page-header>
@if(auth('operador')->user()->puede('liquidaciones', 'gestionar'))
<x-ui.card padding="p-6" class="mb-6">
    <h2 class="mb-4 font-semibold">Programar un precio</h2>
    <form autocomplete="off" method="POST" action="{{ route('admin.tarifas.store') }}" class="grid gap-4 sm:grid-cols-3" data-once>
        @csrf
        <x-ui.field name="precio" label="Precio por litro (S/)" inputmode="decimal" type="number" min="0.001" max="9999999" step="0.001" required hint="Hasta tres decimales. Ejemplo: 1.850" />
        <x-ui.field name="desde" label="Vigente desde" type="date" required />
        <x-ui.field name="hasta" label="Vigente hasta" type="date" required />
        <p class="text-sm text-eh-text-muted sm:col-span-3">No se asigna un precio automáticamente. Si cambia a mitad de una liquidación, cada entrega usa el precio de su fecha.</p>
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
