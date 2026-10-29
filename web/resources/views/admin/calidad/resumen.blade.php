@extends('layouts.admin')
@section('titulo', 'Resumen de calidad')
@section('contenido')
<x-ui.page-header title="Resumen de calidad" description="Supervisa los controles recibidos desde la app y los casos que requieren una decisión. Totales del historial disponible." />
<div class="mb-5 grid gap-4 sm:grid-cols-3">
    <x-ui.kpi label="Análisis recibidos" :value="$recibidos" icon="beaker" dark />
    <x-ui.kpi label="Casos por revisar" :value="$pendientes" icon="exclamation" color="yellow" />
    <x-ui.kpi label="Personal con cuenta activa" :value="$personal" icon="users" color="blue" />
</div>
<div class="mb-5 flex flex-wrap gap-3">
    <x-ui.btn :href="route('admin.calidad.index')">Ver análisis recibidos</x-ui.btn>
    <x-ui.btn :href="route('admin.calidad.casos')" variant="secondary">Revisar casos</x-ui.btn>
</div>
<x-ui.card>
    <div class="p-4"><h2 class="font-semibold">Cobertura por zona</h2><p class="mt-1 text-sm text-eh-text-muted">Agrupada por la zona actual del proveedor. Los controles sin sincronizar todavía no aparecen.</p></div>
    @if ($zonas->isEmpty())
        <x-ui.empty icon="beaker" title="Aún no hay controles recibidos" description="Los trabajadores registran los resultados en la app móvil." />
    @else
        <x-ui.table :headers="['Zona', 'Proveedores controlados', 'Análisis recibidos', '']" caption="Cobertura del historial por zona">
            @foreach ($zonas as $zona)
            <tr class="border-b border-eh-border"><td class="px-4 py-3">{{ $zona->nombre }}</td><td class="px-4 py-3">{{ $zona->proveedores }}</td><td class="px-4 py-3">{{ $zona->controles }}</td><td class="px-4 py-3"><a class="text-eh-primary underline" href="{{ route('admin.calidad.index', ['zona' => $zona->id]) }}">Ver análisis</a></td></tr>
            @endforeach
        </x-ui.table>
    @endif
</x-ui.card>
@endsection
