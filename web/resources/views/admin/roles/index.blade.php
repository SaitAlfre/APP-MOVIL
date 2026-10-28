@extends('layouts.admin')
@section('titulo','Roles y permisos')
@section('contenido')
<x-ui.page-header title="Roles y permisos" description="Define los roles y las acciones permitidas en cada módulo."><x-slot:actions><x-ui.btn :href="route('admin.roles.create')">Nuevo rol</x-ui.btn><x-ui.btn :href="route('admin.usuarios.index')" variant="secondary">Usuarios</x-ui.btn></x-slot:actions></x-ui.page-header>
<x-ui.card padding="p-6"><div class="space-y-4">@forelse($perfiles as $perfil)<div class="flex flex-wrap items-center justify-between gap-3 border-b border-eh-border pb-4"><div><strong>{{ $perfil->nombre }}</strong><p>{{ $perfil->activo ? 'ACTIVO' : 'INACTIVO' }}</p></div><div class="flex gap-3"><x-ui.btn :href="route('admin.roles.edit',$perfil->id)" variant="secondary">Editar rol</x-ui.btn><form method="POST" action="{{ route('admin.roles.destroy',$perfil->id) }}">@csrf @method('DELETE')<x-ui.btn type="submit" variant="ghost">Eliminar</x-ui.btn></form></div></div>@empty<p>No hay roles personalizados.</p>@endforelse</div></x-ui.card>
@endsection
