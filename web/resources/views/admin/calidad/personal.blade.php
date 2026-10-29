@extends('layouts.admin')
@section('titulo', 'Personal de calidad')
@section('contenido')
<x-ui.page-header title="Personal de calidad" description="Consulta al equipo y su actividad recibida. Las cuentas y sus datos se administran en Usuarios y accesos." />
@if (auth('operador')->user()->puede('usuarios'))
<div class="mb-4"><x-ui.btn :href="route('admin.usuarios.index', ['rol' => 'calidad'])" variant="secondary">Administrar cuentas de calidad</x-ui.btn></div>
@endif
<x-ui.card>
@if ($trabajadores->isEmpty())
    <x-ui.empty icon="users" title="No hay personal de calidad" description="Asigna el rol Calidad a una cuenta desde Usuarios y accesos." />
@else
    <x-ui.table :headers="['Trabajador', 'Cuenta', 'Zona de la cuenta', 'Controles / proveedores', 'Última recepción', '']" caption="Personal de calidad y actividad acumulada">
    @foreach ($trabajadores as $trabajador)
        @php $datos = $actividad->get($trabajador->id); @endphp
        <tr class="border-b border-eh-border">
            <td class="px-4 py-3"><p class="font-medium">{{ $trabajador->nombres }}</p><p class="text-xs text-eh-text-muted">{{ $trabajador->username }}</p></td>
            <td class="px-4 py-3">{{ ['activo' => 'Activo', 'suspendido' => 'Suspendido', 'bloqueado' => 'Bloqueado temporalmente'][$trabajador->estadoAdministrativo()] }}</td>
            <td class="px-4 py-3">{{ $zonas[$trabajador->zona_id] ?? 'Sin zona en la cuenta' }}</td>
            <td class="px-4 py-3">{{ $datos?->controles ?? 0 }} / {{ $datos?->proveedores ?? 0 }}</td>
            <td class="px-4 py-3">{{ $datos?->ultima_recepcion ? \Carbon\CarbonImmutable::parse($datos->ultima_recepcion, 'UTC')->setTimezone('America/Lima')->format('d/m/Y H:i') : 'Sin controles recibidos' }}</td>
            <td class="px-4 py-3"><a class="text-eh-primary underline" href="{{ route('admin.calidad.index', ['tecnico' => $trabajador->id]) }}">Ver controles</a></td>
        </tr>
    @endforeach
    </x-ui.table>
    <div class="p-4">{{ $trabajadores->links() }}</div>
@endif
</x-ui.card>
@endsection
