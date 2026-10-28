@extends('layouts.admin')
@section('titulo', 'Editar formulario')
@section('contenido')
<x-ui.page-header :title="'Formulario de '.$perfil->nombre" description="Personaliza los datos que pedirás al registrar un usuario con este rol." :breadcrumbs="[['label'=>'Formularios','url'=>route('admin.formularios.index')],['label'=>$perfil->nombre]]" />
@php
$campos = session()->hasOldInput('editor_formulario') ? old('campos', []) : collect(json_decode($perfil->campos, true))->map(fn($campo, $clave) => ['clave' => $clave] + $campo)->values()->all();
@endphp
<form method="POST" action="{{ route('admin.formularios.update', $perfil->id) }}" data-editor-formulario class="space-y-5">
@csrf @method('PUT')
<input type="hidden" name="editor_formulario" value="1">
@if($errors->any())<div role="alert" tabindex="-1" data-editor-errores class="rounded-xl border border-eh-red p-4 text-eh-red"><p class="font-semibold">Revisa los campos marcados antes de guardar.</p>@error('campos')<p>{{ $message }}</p>@enderror</div>@endif
<div class="grid items-start gap-6 xl:grid-cols-2">
<div class="space-y-5">
<x-ui.card padding="p-5"><h2 class="font-semibold">Datos básicos incluidos</h2><p class="mt-2 text-sm text-eh-text-muted">Nombre completo, DNI y datos de acceso ya forman parte del registro. Aquí configuras únicamente los campos adicionales del rol.</p></x-ui.card>
<x-ui.card padding="p-5">
<div class="mb-4 flex items-center justify-between gap-3"><h2 class="font-semibold">Campos adicionales <span data-campos-contador class="text-eh-text-muted"></span></h2><span class="text-xs text-eh-text-muted">Máximo 40</span></div>
<div data-campos-vacio @if(count($campos)) hidden @endif class="rounded-xl border border-dashed border-eh-border p-6 text-center"><h3 class="font-medium">Empieza con el primer campo</h3><p class="mt-2 text-sm text-eh-text-muted">Por ejemplo: teléfono de contacto o fecha de ingreso. También puedes guardar sin campos adicionales.</p></div>
<div data-campos-lista class="space-y-4">@foreach($campos as $i=>$campo)@include('admin.roles.campo', ['indice'=>$i, 'campo'=>$campo])@endforeach</div>
<template data-campo-template>@include('admin.roles.campo', ['indice'=>'__INDEX__', 'campo'=>[]])</template>
<button type="button" data-agregar-campo class="mt-4 w-full rounded-xl border border-eh-primary px-4 py-3 font-semibold text-eh-primary hover:bg-eh-primary-soft focus-visible:outline-2 focus-visible:outline-eh-primary disabled:opacity-50">Agregar campo</button>
<p data-editor-estado aria-live="polite" class="mt-3 text-sm text-eh-text-muted">Los cambios se aplican al guardar.</p>
</x-ui.card>
</div>
<x-ui.card padding="p-5" class="xl:sticky xl:top-4">
<h2 class="font-semibold">Vista previa</h2><p class="mt-1 mb-5 text-sm text-eh-text-muted">Así se verán los campos adicionales. Esta vista no guarda respuestas.</p>
<fieldset disabled class="space-y-4" data-formulario-preview><legend class="sr-only">Vista previa de campos adicionales</legend></fieldset>
<p class="mt-5 border-t border-eh-border pt-4 text-xs text-eh-text-muted">Los campos con * son obligatorios.</p>
</x-ui.card>
</div>
<div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-eh-border bg-eh-surface p-4"><p class="max-w-xl text-sm text-eh-text-muted">Quitar un campo del formulario conserva las respuestas anteriores. Los cambios se usarán en el registro y la edición de usuarios de este rol.</p><div class="flex gap-3"><x-ui.btn :href="route('admin.formularios.index')" variant="ghost">Cancelar</x-ui.btn><x-ui.btn type="submit">Guardar formulario</x-ui.btn></div></div>
</form>
@endsection
