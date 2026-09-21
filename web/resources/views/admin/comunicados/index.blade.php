@extends('layouts.admin')

@section('titulo', 'Comunicados')

@section('contenido')
    @php
        $puedeGestionar = auth('operador')->user()->puede('comunicados', 'gestionar');
        $audiencias = ['todos' => 'dark', 'proveedores' => 'green', 'acopiadores' => 'blue', 'calidad' => 'yellow', 'produccion' => 'yellow'];
        $opcionesAudiencia = ['todos' => 'Todos', 'proveedores' => 'Proveedores', 'acopiadores' => 'Acopiadores', 'calidad' => 'Calidad', 'produccion' => 'Producción'];
        $opcionesEstado = ['borrador' => 'Borrador', 'programado' => 'Programado', 'publicado' => 'Publicado'];
    @endphp

    <x-ui.page-header title="Comunicados" description="Avisos y notificaciones para usuarios del sistema">
        @if ($puedeGestionar)
            <x-slot:actions>
                <x-ui.btn type="button" icon="plus" data-modal-open="nuevo-comunicado">Nuevo comunicado</x-ui.btn>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        @if ($comunicados->total() === 0)
            <x-ui.empty icon="megaphone" title="Aún no hay comunicados"
                description="Publica avisos para proveedores, acopiadores o el equipo de planta.">
                @if ($puedeGestionar)
                    <x-slot:action>
                        <x-ui.btn type="button" icon="plus" size="sm" data-modal-open="nuevo-comunicado">Nuevo comunicado</x-ui.btn>
                    </x-slot:action>
                @endif
            </x-ui.empty>
        @else
            <x-ui.table :headers="['Código', 'Título', 'Audiencia', 'Publicación', 'Estado', '']" caption="Comunicados registrados">
                @foreach ($comunicados as $comunicado)
                    <tr class="border-b border-eh-border align-top last:border-0 hover:bg-eh-surface-alt">
                        <td class="mono px-4 py-3 text-xs font-medium text-eh-text">{{ $comunicado->codigo }}</td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-eh-text">{{ $comunicado->titulo }}</p>
                            <p class="mt-0.5 max-w-md truncate text-xs text-eh-text-muted">{{ \Illuminate\Support\Str::limit($comunicado->contenido, 90) }}</p>
                        </td>
                        <td class="px-4 py-3"><x-ui.badge :variant="$audiencias[$comunicado->audiencia] ?? 'gray'" :label="$opcionesAudiencia[$comunicado->audiencia] ?? ucfirst($comunicado->audiencia)" /></td>
                        <td class="px-4 py-3 text-xs text-eh-text-muted">
                            {{ $comunicado->publicado_en?->format('d/m/Y H:i') ?? ($comunicado->publicar_en?->format('d/m/Y H:i') ?? '—') }}
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$comunicado->estado === 'publicado' ? 'green' : ($comunicado->estado === 'programado' ? 'blue' : 'gray')"
                                :label="$opcionesEstado[$comunicado->estado] ?? ucfirst($comunicado->estado)" />
                        </td>
                        <td class="px-4 py-3">
                            @if ($puedeGestionar)
                                <div class="flex items-center justify-end">
                                    <button type="button" data-modal-open="comunicado-{{ $comunicado->id }}" aria-label="Editar comunicado {{ $comunicado->titulo }}"
                                        class="rounded-lg p-1.5 text-eh-text-muted hover:bg-eh-primary-soft hover:text-eh-primary">
                                        <x-icon name="pencil" class="h-4 w-4" />
                                    </button>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-eh-border px-4 py-3 text-xs text-eh-text-muted">
                <span>Mostrando {{ $comunicados->count() }} de {{ $comunicados->total() }} comunicados</span>
                <div>{{ $comunicados->onEachSide(1)->links() }}</div>
            </div>
        @endif
    </x-ui.card>

    @if ($puedeGestionar)
        <x-ui.modal id="nuevo-comunicado" title="Nuevo comunicado" size="lg">
            <form method="POST" action="{{ route('admin.comunicados.store') }}" class="space-y-4" data-once>
                @csrf
                <x-ui.field name="titulo" label="Título" required maxlength="180" placeholder="Asunto del comunicado" />
                <div class="flex flex-col gap-1">
                    <label for="contenido-nuevo" class="text-sm font-medium text-eh-text">
                        Contenido <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                    </label>
                    <textarea id="contenido-nuevo" name="contenido" required maxlength="5000" rows="5" placeholder="Texto del comunicado…"
                        class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">{{ old('contenido') }}</textarea>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <x-ui.select name="audiencia" label="Audiencia" required :options="$opcionesAudiencia" />
                    <x-ui.select name="estado" label="Estado" required :options="$opcionesEstado" />
                    <x-ui.field name="publicar_en" label="Publicar el" type="datetime-local"
                        hint="Obligatorio si el estado es «Programado»." />
                </div>
                <div class="flex justify-end gap-2">
                    <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                    <x-ui.btn type="submit">Guardar comunicado</x-ui.btn>
                </div>
            </form>
        </x-ui.modal>

        @foreach ($comunicados as $comunicado)
            <x-ui.modal :id="'comunicado-'.$comunicado->id" title="Editar comunicado" size="lg">
                <form method="POST" action="{{ route('admin.comunicados.update', $comunicado) }}" class="space-y-4" data-once>
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-1">
                        <label for="titulo-{{ $comunicado->id }}" class="text-sm font-medium text-eh-text">
                            Título <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                        </label>
                        <input id="titulo-{{ $comunicado->id }}" name="titulo" value="{{ $comunicado->titulo }}" required maxlength="180"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label for="contenido-{{ $comunicado->id }}" class="text-sm font-medium text-eh-text">
                            Contenido <span class="text-eh-red" aria-hidden="true">*</span><span class="sr-only">(obligatorio)</span>
                        </label>
                        <textarea id="contenido-{{ $comunicado->id }}" name="contenido" required maxlength="5000" rows="5"
                            class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">{{ $comunicado->contenido }}</textarea>
                    </div>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex flex-col gap-1">
                            <label for="audiencia-{{ $comunicado->id }}" class="text-sm font-medium text-eh-text">Audiencia</label>
                            <select id="audiencia-{{ $comunicado->id }}" name="audiencia" required
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                                @foreach ($opcionesAudiencia as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($comunicado->audiencia === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="estado-{{ $comunicado->id }}" class="text-sm font-medium text-eh-text">Estado</label>
                            <select id="estado-{{ $comunicado->id }}" name="estado" required
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                                @foreach ($opcionesEstado as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($comunicado->estado === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label for="publicar-{{ $comunicado->id }}" class="text-sm font-medium text-eh-text">Publicar el</label>
                            <input id="publicar-{{ $comunicado->id }}" name="publicar_en" type="datetime-local"
                                value="{{ $comunicado->publicar_en?->format('Y-m-d\TH:i') }}"
                                class="w-full rounded-xl border border-eh-border bg-eh-surface px-3 py-2 text-sm text-eh-text focus:border-eh-primary focus:outline-none focus:ring-1 focus:ring-eh-primary">
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <x-ui.btn type="button" variant="ghost" data-modal-close>Cancelar</x-ui.btn>
                        <x-ui.btn type="submit">Guardar cambios</x-ui.btn>
                    </div>
                </form>
            </x-ui.modal>
        @endforeach
    @endif
@endsection
