@extends('layouts.admin')
@section('titulo','Configurar rol')
@section('contenido')
<x-ui.page-header title="Configurar rol" description="Define el nombre, el estado y los permisos del rol. Configura sus campos en Formularios." />
<x-ui.card padding="p-6"><form method="POST" action="{{ $perfil ? route('admin.roles.update',$perfil->id) : route('admin.roles.store') }}" class="space-y-6">@csrf @if($perfil) @method('PUT') @endif
<x-ui.field name="nombre" label="Nombre del rol" required :value="$perfil?->nombre" />
<x-ui.select name="base" label="Perfil base" :options="collect($bases)->mapWithKeys(fn($r)=>[$r->value=>$r->etiqueta()])->all()" :selected="$perfil?->base" required />
<x-ui.select name="activo" label="Estado" :options="[1=>'ACTIVO',0=>'INACTIVO']" :selected="$perfil?->activo ?? 1" required />
<fieldset><legend>Permisos por módulo</legend><div class="space-y-3">@foreach($modulos as $modulo)<div class="flex flex-wrap gap-4"><strong class="w-40">{{ str_replace('_',' ',$modulo) }}</strong>@foreach(['ver','crear','editar','eliminar'] as $accion)<label><input type="checkbox" name="permisos[{{ $modulo }}][]" value="{{ $accion }}" @checked(in_array($accion,old('permisos',json_decode($perfil?->permisos ?? '{}',true))[$modulo] ?? []))> {{ ucfirst($accion) }}</label>@endforeach</div>@endforeach</div></fieldset>
<x-ui.btn type="submit">Guardar rol</x-ui.btn><x-ui.btn :href="route('admin.roles.index')" variant="ghost">Cancelar</x-ui.btn></form></x-ui.card>
@endsection
