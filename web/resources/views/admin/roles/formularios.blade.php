@extends('layouts.admin')
@section('titulo', 'Formularios')
@section('contenido')
<x-ui.page-header title="Formularios" description="Define qué información adicional pedirás según el rol del usuario." />
<x-ui.card padding="p-5" class="mb-6"><h2 class="font-semibold">Un formulario para cada rol personalizado</h2><p class="mt-2 max-w-3xl text-sm text-eh-text-muted">Selecciona un rol, agrega sus campos y revisa la vista previa antes de guardar. Los datos básicos de identidad y acceso ya están incluidos en el registro.</p></x-ui.card>
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse($perfiles as $perfil)
@php $cantidad = count(json_decode($perfil->campos, true)); @endphp
<x-ui.card padding="p-5">
<div class="mb-4 flex flex-wrap items-start justify-between gap-2"><h2 class="break-words font-semibold">{{ $perfil->nombre }}</h2><x-ui.estado :estado="$perfil->activo ? 'activo' : 'inactivo'" /></div>
<p class="mb-1 text-sm">{{ $cantidad }} {{ $cantidad === 1 ? 'campo adicional' : 'campos adicionales' }}</p>
<p class="mb-5 text-sm text-eh-text-muted">{{ $cantidad ? 'Revisa o modifica los campos de este rol.' : 'Solo solicita los datos básicos. Puedes agregar más campos.' }}</p>
<x-ui.btn :href="route('admin.formularios.edit', $perfil->id)" variant="secondary">Editar formulario</x-ui.btn>
</x-ui.card>
@empty
<div class="md:col-span-2 xl:col-span-3"><x-ui.card padding="p-8"><h2 class="text-lg font-semibold">Aún no tienes formularios personalizados</h2><p class="mt-2 mb-5 max-w-xl text-sm text-eh-text-muted">Primero crea un rol en Roles y permisos. Después aparecerá aquí para que configures sus campos adicionales.</p><x-ui.btn :href="route('admin.roles.create')">Crear un rol</x-ui.btn></x-ui.card></div>
@endforelse
</div>
@endsection
