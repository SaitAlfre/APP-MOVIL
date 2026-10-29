@props(['usuario' => null, 'productivo' => null])
@php
    $manual = $usuario && (! $usuario->activo || ($usuario->bloqueado_manualmente ?? $usuario->bloqueadoManualmente ?? false));
    $hasta = $usuario?->bloqueado_hasta ?? $usuario?->bloqueadoHasta;
    $temporal = $hasta && \Illuminate\Support\Carbon::parse($hasta)->isFuture();
@endphp
<span class="inline-flex flex-wrap items-center gap-1">
    @if (! $usuario)
        <x-ui.badge variant="gray" label="Sin cuenta móvil" />
    @else
        <x-ui.badge :variant="$manual ? 'red' : 'green'" :label="$manual ? 'Cuenta suspendida' : 'Cuenta activa'" />
        @if ($temporal)<x-ui.badge variant="yellow" label="Bloqueo temporal" />@endif
        @if ($productivo === 'retirado')<x-ui.badge variant="yellow" label="Acceso operativo restringido por retiro" />
        @elseif ($productivo === 'suspendido')<x-ui.badge variant="yellow" label="Operaciones productivas suspendidas" />@endif
    @endif
</span>
