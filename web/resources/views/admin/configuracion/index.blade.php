@extends('layouts.admin')

@section('titulo', 'Configuración')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('configuracion', 'gestionar');
        $dias = collect(['lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'])
            ->mapWithKeys(fn ($dia) => [$dia => ucfirst($dia)])
            ->all();
    @endphp

    <x-ui.page-header title="Configuración" description="Parámetros generales del sistema, la semana operativa y la seguridad de acceso" />

    <form method="POST" action="{{ route('admin.configuracion.update') }}" class="max-w-4xl space-y-4" data-once>
        @csrf
        @method('PUT')

        <x-ui.card padding="p-6">
            <h2 class="mb-4 text-sm font-semibold text-eh-text">Organización</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field name="organizacion_nombre" label="Nombre de la organización" required maxlength="150"
                    :value="$configuracion['organizacion_nombre']" :disabled="! $puedeGestionar" />
                <x-ui.field name="organizacion_ruc" label="RUC" maxlength="20"
                    :value="$configuracion['organizacion_ruc']" :disabled="! $puedeGestionar" />
                <x-ui.field name="organizacion_direccion" label="Dirección" maxlength="255"
                    :value="$configuracion['organizacion_direccion']" :disabled="! $puedeGestionar" />
                <x-ui.field name="organizacion_telefono" label="Teléfono" maxlength="30"
                    :value="$configuracion['organizacion_telefono']" :disabled="! $puedeGestionar" />
            </div>
        </x-ui.card>

        <x-ui.card padding="p-6">
            <h2 class="mb-4 text-sm font-semibold text-eh-text">Parámetros operativos</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.select name="moneda" label="Moneda" required :options="['PEN' => 'PEN (S/)']"
                    :selected="$configuracion['moneda']" :disabled="! $puedeGestionar" />
                <x-ui.field name="precio_base_litro" label="Precio base por litro (S/)" type="number" step="0.0001" min="0.0001" required
                    :value="$configuracion['precio_base_litro']" :disabled="! $puedeGestionar"
                    hint="Se usa como valor inicial al generar una liquidación." />
                <x-ui.select name="inicio_semana" label="Inicio de la semana operativa" required :options="$dias"
                    :selected="$configuracion['inicio_semana']" :disabled="! $puedeGestionar" />
                <x-ui.select name="dia_pago" label="Día de pago" required :options="$dias"
                    :selected="$configuracion['dia_pago']" :disabled="! $puedeGestionar" />
            </div>
        </x-ui.card>

        <x-ui.card padding="p-6">
            <h2 class="mb-4 text-sm font-semibold text-eh-text">Seguridad de acceso</h2>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-ui.field name="login_intentos_maximos" label="Intentos máximos de inicio de sesión" type="number" min="1" max="20" required
                    :value="$configuracion['login_intentos_maximos']" :disabled="! $puedeGestionar" />
                <x-ui.field name="login_bloqueo_minutos" label="Bloqueo temporal (minutos)" type="number" min="1" max="1440" required
                    :value="$configuracion['login_bloqueo_minutos']" :disabled="! $puedeGestionar" />
                <label class="flex items-center gap-3 rounded-xl border border-eh-border p-3 md:col-span-2">
                    <input type="checkbox" name="requerir_cambio_pin" value="1" @checked($configuracion['requerir_cambio_pin'] === '1')
                        @disabled(! $puedeGestionar) class="rounded border-eh-border text-eh-primary focus:ring-eh-primary">
                    <span class="text-sm text-eh-text">Requerir cambio de PIN en el primer inicio de sesión</span>
                </label>
            </div>
        </x-ui.card>

        @if ($puedeGestionar)
            <div class="flex justify-end gap-2">
                <x-ui.btn :href="route('admin.configuracion.index')" variant="ghost">Cancelar</x-ui.btn>
                <x-ui.btn type="submit">Guardar configuración</x-ui.btn>
            </div>
        @else
            <x-ui.alert type="info">Tu rol permite consultar la configuración, pero no modificarla.</x-ui.alert>
        @endif
    </form>
@endsection
