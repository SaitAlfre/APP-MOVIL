@extends('layouts.admin')
@section('titulo', 'Seleccionar rol')
@section('contenido')
<div class="mx-auto w-full max-w-4xl">
    <x-ui.page-header title="¿Qué rol tendrá el usuario?" description="Selecciona primero su función. Abriremos el formulario correspondiente para crear su cuenta." :breadcrumbs="[['label'=>'Usuarios y accesos','url'=>route('admin.usuarios.index')],['label'=>'Nuevo usuario']]" />
    @if(isset($puedeAsignarRol) && !$puedeAsignarRol)<p class="mb-4 rounded-lg border border-eh-border bg-eh-surface px-4 py-3 text-sm text-eh-text-muted">Puedes registrar usuarios con tu mismo rol. Para asignar otra función, contacta al administrador.</p>@endif
    <x-ui.card padding="p-5 sm:p-6">
        @if(count($roles))
        <section aria-labelledby="roles-sistema">
            <div class="mb-4 flex flex-wrap items-center gap-3"><h2 id="roles-sistema" class="text-sm font-semibold">Roles del sistema</h2><span class="text-xs text-eh-text-muted">Funciones predefinidas</span></div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($roles as $rol)
                <a href="{{ $rol->value === 'proveedor' ? route('admin.proveedores.create',['cuenta'=>1,'origen'=>'usuarios']) : route('admin.usuarios.create',['base'=>$rol->value]) }}" class="group flex min-w-0 items-start justify-between gap-3 rounded-xl border border-eh-border p-4 transition-colors hover:border-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary">
                    <div class="min-w-0"><h3 class="text-sm font-semibold">{{ $rol->etiqueta() }}</h3><p class="mt-1.5 text-xs leading-relaxed text-eh-text-muted">{{ match($rol->value) {'admin'=>'Administra el sistema, los usuarios y los permisos.','acopiador'=>'Registra recojos y consulta las rutas asignadas.','calidad'=>'Registra análisis de leche en su zona de trabajo.','proveedor'=>'Registra su ficha productiva y su cuenta móvil en un solo formulario.'} }}</p></div><span aria-hidden="true" class="pt-0.5 text-eh-text-muted group-hover:text-eh-primary">→</span>
                </a>
                @endforeach
            </div>
        </section>
        @endif
        @if($perfiles->isNotEmpty())
        <section aria-labelledby="roles-personalizados" @class(['mt-6 border-t border-eh-border pt-5'=>count($roles)>0]) data-roles-selector>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><h2 id="roles-personalizados" class="text-sm font-semibold">Roles personalizados</h2><x-ui.search data-roles-buscar aria-label="Buscar rol personalizado" placeholder="Buscar un rol…" autocomplete="off" /></div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($perfiles as $perfil)
                @php $cantidad = count(json_decode($perfil->campos, true)); @endphp
                <a data-rol-opcion href="{{ route('admin.usuarios.create',['perfil_rol_id'=>$perfil->id]) }}" class="group flex min-w-0 items-center justify-between gap-3 rounded-xl border border-eh-border p-4 transition-colors hover:border-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary"><div class="min-w-0"><h3 class="break-words text-sm font-semibold">{{ $perfil->nombre }}</h3><p class="mt-1.5 text-xs text-eh-text-muted">{{ \App\Domain\Usuarios\Rol::from($perfil->base)->etiqueta() }} · {{ $cantidad ? $cantidad.' '.($cantidad===1 ? 'campo personalizado' : 'campos personalizados') : 'Formulario básico' }}</p></div><span aria-hidden="true" class="text-eh-text-muted group-hover:text-eh-primary">→</span></a>
                @endforeach
            </div>
            <p data-roles-vacio hidden role="status" class="py-5 text-center text-sm text-eh-text-muted">No hay roles con ese nombre.</p>
        </section>
        @endif
    </x-ui.card>
    <div class="mt-4"><x-ui.btn :href="route('admin.usuarios.index')" variant="ghost">← Volver a Usuarios y accesos</x-ui.btn></div>
</div>
@endsection
