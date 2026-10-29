@extends('layouts.admin')
@section('titulo', 'Formularios')
@section('contenido')
<div class="mx-auto w-full max-w-6xl">
<x-ui.page-header title="Formularios" description="Configura los datos que se solicitan al registrar a cada usuario."><x-slot:actions><x-ui.btn :href="route('admin.roles.create')" variant="secondary" size="sm">Nuevo rol</x-ui.btn></x-slot:actions></x-ui.page-header>
@include('admin.roles.filtros', ['ruta'=>'admin.formularios.index'])
<div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
@forelse($perfiles as $perfil)
@php
    $cantidad = count(json_decode($perfil->campos, true));
    $iniciales = collect(preg_split('/\s+/u', trim($perfil->nombre)))->take(2)->map(fn($palabra)=>mb_strtoupper(mb_substr($palabra,0,1)))->implode('');
@endphp
<x-ui.card padding="p-5">
<div class="flex items-start gap-3"><span class="gestion-avatar" aria-hidden="true">{{ $iniciales }}</span><div class="min-w-0 flex-1"><h2 class="gestion-row-title">{{ $perfil->nombre }}</h2><p class="gestion-row-subtitle">{{ $perfil->base ? 'Heredada: '.\App\Domain\Usuarios\Rol::from($perfil->base)->etiqueta() : 'Solo panel web' }}</p></div><x-ui.estado :estado="$perfil->activo ? 'activo' : 'inactivo'" /></div>
<p class="mt-5 text-sm font-medium">{{ $cantidad }} {{ $cantidad === 1 ? 'campo personalizado' : 'campos personalizados' }}</p>
<p class="mt-1 text-xs text-eh-text-muted">{{ $cantidad ? 'Identidad y acceso incluidos en el registro.' : 'El formulario solicita identidad y acceso.' }}</p>
<div class="mt-5 flex items-center justify-between gap-2 border-t border-eh-border pt-3"><a href="{{ route('admin.roles.edit',$perfil->id) }}" class="text-xs text-eh-text-muted underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-eh-primary">Ver permisos</a><x-ui.btn :href="route('admin.formularios.edit', $perfil->id)" variant="ghost" size="sm">Editar formulario</x-ui.btn></div>
</x-ui.card>
@empty
<div class="md:col-span-2 xl:col-span-3"><x-ui.card padding="p-8"><h2 class="text-lg font-semibold">No hay formularios para mostrar</h2><p class="mt-2 mb-5 max-w-xl text-sm text-eh-text-muted">{{ request()->filled('buscar') || request()->filled('estado') ? 'Cambia los filtros o limpia la búsqueda para ver otros formularios.' : 'Crea un rol en Roles y permisos para configurar sus campos.' }}</p><x-ui.btn :href="request()->filled('buscar') || request()->filled('estado') ? route('admin.formularios.index') : route('admin.roles.create')">{{ request()->filled('buscar') || request()->filled('estado') ? 'Limpiar filtros' : 'Crear un rol' }}</x-ui.btn></x-ui.card></div>
@endforelse
</div>
<x-ui.table-pagination :paginator="$perfiles" form-id="filtros-roles" noun="formularios" :bordered="false" />
</div>
@endsection
