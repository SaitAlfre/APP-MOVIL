@extends('layouts.admin')
@section('titulo', 'Proveedores')
@section('contenido')
    @php
        $usuario = auth('operador')->user();
        $puedeGestionar = $usuario->puede('proveedores', 'editar');
        $puedeCrear = $usuario->puede('proveedores', 'crear');
        $hayFiltros = filled($busqueda) || $zonaId !== null || $estadoFiltro !== null;
    @endphp
    <x-ui.page-header title="Proveedores" description="Gestiona los productores, sus datos y sus cuentas vinculadas."
        :breadcrumbs="[['label'=>'Usuarios y proveedores'],['label'=>'Proveedores']]">
        @if($puedeCrear)
            <x-slot:actions><x-ui.btn :href="route('admin.proveedores.create')" icon="plus">Nuevo proveedor</x-ui.btn></x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <x-ui.status-tabs name="estado" :options="[''=>'Todos','activo'=>'Activos','suspendido'=>'Suspendidos','retirado'=>'Retirados']" :value="$estadoFiltro?->value ?? ''" form-id="filtros-proveedores" />
        <p data-proveedores-contador class="text-xs text-eh-text-muted">{{ $proveedores->total() }} {{ $proveedores->total() === 1 ? 'proveedor' : 'proveedores' }}</p>
    </div>
    <div class="gestion-toolbar mb-4 flex flex-wrap items-center gap-3">
        <form id="filtros-proveedores" method="GET" action="{{ route('admin.proveedores.index') }}" data-proveedores-filtros class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
            <input type="hidden" name="vista" value="{{ $vista }}">
            <input type="hidden" name="estado" value="{{ $estadoFiltro?->value ?? '' }}">
            <div class="min-w-48 flex-1 sm:max-w-sm">
                <x-ui.search name="q" :value="$busqueda" label="Buscar proveedores" maxlength="120" autocomplete="off" placeholder="Buscar por nombre, código o DNI…" />
            </div>
            <x-ui.select name="zona_id" placeholder="Todas las zonas" class="w-full sm:w-44" aria-label="Filtrar por zona" :options="collect($zonasDisponibles)->mapWithKeys(fn($zona)=>[$zona->id=>$zona->nombre])->all()" :selected="$zonaId" />
            <x-ui.btn type="submit" variant="secondary" icon="filter" data-filtros-aplicar>Filtrar</x-ui.btn>
            <a href="{{ route('admin.proveedores.index',['vista'=>$vista]) }}" data-proveedores-limpiar @if(!$hayFiltros) hidden @endif class="inline-flex min-h-9 items-center rounded-lg px-2 text-xs text-eh-text-muted hover:text-eh-text focus-visible:outline-2 focus-visible:outline-eh-primary">Limpiar</a>
        </form>
        <div class="ml-auto flex flex-wrap items-center gap-2">
            <div data-proveedores-columnas @if($vista !== 'tabla') hidden @endif class="flex flex-wrap items-center gap-2">
                <x-ui.table-columns table-id="tabla-proveedores" :columns="['dni'=>'DNI','zona'=>'Zona','estado'=>'Estado','litros'=>'Litros de la semana','ultima'=>'Última entrega','cuenta'=>'Cuenta vinculada']" :hidden-columns="['litros','ultima','cuenta']" />
                <x-ui.table-export table-id="tabla-proveedores" form-id="filtros-proveedores" filename="proveedores" />
            </div>
            <nav aria-label="Vista de proveedores" class="inline-flex rounded-lg border border-eh-border bg-eh-surface p-0.5 text-xs font-medium">
                @foreach(['tabla'=>'Tabla','tarjetas'=>'Tarjetas'] as $modo=>$etiqueta)
                    <a href="{{ route('admin.proveedores.index',array_replace(request()->query(),['vista'=>$modo])) }}" data-proveedores-vista="{{ $modo }}" data-activa="{{ $vista === $modo ? 'true' : 'false' }}" @if($vista === $modo) aria-current="true" @endif aria-controls="resultados-proveedores" class="inline-flex min-h-8 items-center rounded-md px-3 text-eh-text-muted hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary data-[activa=true]:bg-eh-surface-alt data-[activa=true]:text-eh-text">{{ $etiqueta }}</a>
                @endforeach
            </nav>
        </div>
    </div>
    <p data-proveedores-estado role="status" aria-live="polite" class="sr-only"></p>
    <div id="resultados-proveedores" data-proveedores-resultados data-total-resultados="{{ $proveedores->total() }}" data-pagina-cantidad="{{ $proveedores->count() }}" aria-busy="false">
        @if($proveedores->isEmpty())
            <x-ui.card>
                <x-ui.empty icon="proveedores" :title="$proveedores->total() > 0 ? 'No hay proveedores en esta página' : ($hayFiltros ? 'No se encontraron proveedores' : 'Aún no hay proveedores registrados')" :description="$hayFiltros ? 'Prueba con otros filtros o limpia la búsqueda.' : 'Registra un productor con el botón Nuevo proveedor.'">
                    @if($proveedores->total() > 0)
                        <x-slot:action><x-ui.btn :href="route('admin.proveedores.index',array_diff_key(request()->query(),['page'=>true]))" variant="secondary">Volver a la primera página</x-ui.btn></x-slot:action>
                    @endif
                </x-ui.empty>
                <x-ui.table-pagination :paginator="$proveedores" form-id="filtros-proveedores" noun="proveedores" />
            </x-ui.card>
        @elseif($vista === 'tarjetas')
            @include('admin.proveedores.tarjetas')
            <div class="mt-3"><x-ui.table-pagination :bordered="false" :paginator="$proveedores" form-id="filtros-proveedores" noun="proveedores" /></div>
        @else
            <x-ui.card>
                @include('admin.proveedores.tabla')
                <x-ui.table-pagination :paginator="$proveedores" form-id="filtros-proveedores" noun="proveedores" />
            </x-ui.card>
        @endif
    </div>
    @include('admin.proveedores.qr-modal')
    @if(auth('operador')->user()->puede('proveedores', 'editar') || auth('operador')->user()->puede('proveedores', 'eliminar'))
        @include('admin.proveedores.estado-modal')
    @endif
@endsection
