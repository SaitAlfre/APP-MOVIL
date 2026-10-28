@extends('layouts.admin')
@section('titulo', 'Seleccionar rol')
@section('contenido')
<x-ui.page-header title="Nuevo usuario: elige su rol" description="Selecciona primero el rol para abrir su formulario. Cada cuenta nueva tendrá un solo rol." :breadcrumbs="[['label'=>'Usuarios y accesos','url'=>route('admin.usuarios.index')],['label'=>'Seleccionar rol']]" />
<div class="max-w-4xl space-y-6">
<section><h2 class="mb-3 font-semibold">Roles del sistema</h2><div class="grid gap-3 sm:grid-cols-2">@foreach($roles as $rol)
<a href="{{ route('admin.usuarios.create',['base'=>$rol->value]) }}" class="rounded-xl border border-eh-border bg-eh-surface p-5 hover:border-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary"><h3 class="font-semibold">{{ $rol->etiqueta() }}</h3><p class="mt-2 text-sm text-eh-text-muted">{{ match($rol->value) {'admin'=>'Administra el sistema y las cuentas de acceso.','acopiador'=>'Registra recojos y consulta sus rutas asignadas.','calidad'=>'Trabaja con análisis de leche. Necesita una zona de trabajo.','proveedor'=>'Cuenta de acceso para consultar entregas y pagos en la app. Su ficha se gestiona en Proveedores.'} }}</p></a>
@endforeach</div></section>
@if($perfiles->isNotEmpty())<section><h2 class="mb-3 font-semibold">Roles personalizados</h2><div class="grid gap-3 sm:grid-cols-2">@foreach($perfiles as $perfil)<a href="{{ route('admin.usuarios.create',['perfil_rol_id'=>$perfil->id]) }}" class="rounded-xl border border-eh-border bg-eh-surface p-5 hover:border-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary"><h3 class="font-semibold">{{ $perfil->nombre }}</h3><p class="mt-2 text-sm text-eh-text-muted">Formulario con {{ count(json_decode($perfil->campos,true)) }} campos adicionales.</p></a>@endforeach</div></section>@endif
<x-ui.btn :href="route('admin.usuarios.index')" variant="ghost">Volver a Usuarios y accesos</x-ui.btn>
</div>
@endsection
