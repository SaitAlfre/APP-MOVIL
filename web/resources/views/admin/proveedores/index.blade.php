@extends('layouts.admin')
@section('titulo', 'Proveedores')
@section('contenido')
    @php
        $usuario = auth('operador')->user();
        $puedeGestionar = $usuario->perfil_rol_id ? $usuario->puede('proveedores', 'editar') : $usuario->puede('proveedores', 'gestionar');
        $puedeCrear = $usuario->perfil_rol_id ? $usuario->puede('proveedores', 'crear') : $usuario->puede('proveedores', 'gestionar');
        $hayFiltros = filled($busqueda) || $zonaId !== null || $estadoFiltro !== null;
    @endphp
    <x-ui.page-header title="Proveedores" description="Consulta y gestiona los productores registrados.">
        @if($puedeCrear)
            <x-slot:actions>
                <x-ui.btn :href="route('admin.proveedores.create')" icon="plus">Nuevo proveedor</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <div class="mb-4 space-y-3">
        <form id="filtros-proveedores" method="GET" action="{{ route('admin.proveedores.index') }}" data-proveedores-filtros class="grid gap-3 sm:grid-cols-2 xl:flex xl:items-center">
            <input type="hidden" name="vista" value="{{ $vista }}">
            <div class="min-w-0 sm:col-span-2 xl:flex-1">
                <x-ui.search name="q" :value="$busqueda" label="Buscar proveedores" maxlength="120" autocomplete="off" placeholder="Buscar por nombre, código o DNI…" />
            </div>
            <x-ui.select name="zona_id" placeholder="Todas las zonas" class="xl:w-48" aria-label="Filtrar por zona" :options="collect($zonasDisponibles)->mapWithKeys(fn($zona)=>[$zona->id=>$zona->nombre])->all()" :selected="$zonaId" />
            <x-ui.select name="estado" placeholder="Todos los estados" class="xl:w-44" aria-label="Filtrar por estado" :options="['activo'=>'Activo','suspendido'=>'Suspendido','retirado'=>'Retirado']" :selected="$estadoFiltro?->value" />
            <x-ui.btn type="submit" variant="secondary" icon="filter" data-filtros-aplicar>Filtrar</x-ui.btn>
        </form>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.proveedores.index',['vista'=>$vista]) }}" data-proveedores-limpiar @if(!$hayFiltros) hidden @endif class="rounded-lg py-2 text-xs text-eh-text-muted underline focus-visible:outline-2 focus-visible:outline-eh-primary">Limpiar filtros</a>
            <nav aria-label="Vista de proveedores" class="ml-auto inline-flex rounded-xl border border-eh-border bg-eh-surface p-1 text-xs font-medium">
                @foreach(['tabla'=>'Tabla','tarjetas'=>'Tarjetas'] as $modo=>$etiqueta)
                    <a href="{{ route('admin.proveedores.index',array_replace(request()->query(),['vista'=>$modo])) }}" data-proveedores-vista="{{ $modo }}" data-activa="{{ $vista === $modo ? 'true' : 'false' }}" @if($vista === $modo) aria-current="true" @endif aria-controls="resultados-proveedores" class="rounded-lg px-4 py-2 text-eh-text-muted hover:bg-eh-surface-alt focus-visible:outline-2 focus-visible:outline-eh-primary data-[activa=true]:bg-eh-primary-soft data-[activa=true]:text-eh-primary">{{ $etiqueta }}</a>
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
                        <x-slot:action>
                            <x-ui.btn :href="route('admin.proveedores.index',array_diff_key(request()->query(),['page'=>true]))" variant="secondary">Volver a la primera página</x-ui.btn>
                        </x-slot:action>
                    @endif
                </x-ui.empty>
                <x-ui.table-pagination :paginator="$proveedores" form-id="filtros-proveedores" noun="proveedores" />
            </x-ui.card>
        @elseif($vista === 'tarjetas')
            @include('admin.proveedores.tarjetas')
            <x-ui.card class="mt-4">
                <x-ui.table-pagination :bordered="false" :paginator="$proveedores" form-id="filtros-proveedores" noun="proveedores" />
            </x-ui.card>
        @else
            <x-ui.card>
                @include('admin.proveedores.tabla')
                <x-ui.table-pagination :paginator="$proveedores" form-id="filtros-proveedores" noun="proveedores" />
            </x-ui.card>
        @endif
    </div>
@endsection
