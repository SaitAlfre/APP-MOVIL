<div class="mb-4 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-ui.status-tabs name="estado" :options="[''=>'Todos','activo'=>'Activos','inactivo'=>'Inactivos']" :value="request('estado')" form-id="filtros-roles" />
        <p class="text-xs text-eh-text-muted" role="status">{{ $perfiles->total() }} {{ $perfiles->total() === 1 ? 'resultado' : 'resultados' }}</p>
    </div>
    <form id="filtros-roles" action="{{ route($ruta) }}" method="GET" data-directorio-filtros data-live-filter class="gestion-toolbar">
        <input type="hidden" name="estado" value="{{ request('estado') }}">
        <div class="w-full sm:max-w-sm"><x-ui.search name="buscar" label="Buscar roles" :value="request('buscar')" placeholder="Buscar un rol…" maxlength="100" autocomplete="off" /></div>
        <div class="flex flex-wrap items-center gap-2"><x-ui.btn type="submit" variant="secondary" size="sm">Buscar</x-ui.btn>
            @if(request()->filled('buscar') || request()->filled('estado'))<x-ui.btn :href="route($ruta)" variant="ghost" size="sm">Limpiar</x-ui.btn>@endif
        </div>
    </form>
</div>
