@extends('layouts.admin')

@section('titulo', 'Detalle de auditoría')

@section('contenido')
    @php
        $etiquetasAccion = [
            'crear' => 'Creación',
            'corregir' => 'Corrección',
            'anular' => 'Anulación',
            'actualizar' => 'Actualización',
            'desactivar' => 'Desactivación',
            'autorizar' => 'Autorización',
            'rechazar' => 'Rechazo',
            'iniciar_sesion' => 'Inicio de sesión',
            'cerrar_sesion' => 'Cierre de sesión',
            'acceso_fallido' => 'Acceso fallido',
        ];
        $filtrosPrevios = request()->only('usuario_id', 'entidad', 'accion', 'desde', 'hasta');
    @endphp

    <x-ui.page-header :title="'Detalle de auditoría #'.$registro->id"
        :description="ucfirst(str_replace('_', ' ', $registro->entidad)).' #'.$registro->entidadId.' · '.$registro->ocurridoEn->format('d/m/Y H:i:s')"
        :breadcrumbs="[['label' => 'Auditoría', 'url' => route('admin.auditoria.index', $filtrosPrevios)], ['label' => 'Registro #'.$registro->id]]">
        <x-slot:actions>
            <x-ui.btn :href="route('admin.auditoria.index', $filtrosPrevios)" variant="ghost" size="sm" icon="arrowLeft">Volver</x-ui.btn>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card padding="p-5" class="mb-4">
        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ([
                'Responsable o cuenta' => $usuario?->nombres ?? 'Usuario no disponible',
                'Fecha y hora ('.config('app.timezone').')' => $registro->ocurridoEn->format('d/m/Y H:i:s'),
                'Registro de origen' => ucfirst(str_replace('_', ' ', $registro->entidad)).' #'.$registro->entidadId,
                'Acción' => $etiquetasAccion[$registro->accion->value] ?? ucfirst($registro->accion->value),
            ] as $clave => $valor)
                <div>
                    <dt class="text-xs text-eh-text-muted">{{ $clave }}</dt>
                    <dd class="mt-0.5 text-sm font-medium text-eh-text">{{ $valor }}</dd>
                </div>
            @endforeach
            <div class="sm:col-span-2">
                <dt class="text-xs text-eh-text-muted">Motivo</dt>
                <dd class="mt-0.5 whitespace-pre-wrap break-words text-sm text-eh-text">{{ $registro->motivo ?? 'Sin motivo registrado' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    @if ($registro->accion->value === 'acceso_fallido')
        <x-ui.alert type="warning" class="mb-4">
            La cuenta identifica el destino del intento de acceso. No demuestra quién intentó entrar.
        </x-ui.alert>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ([
            ['titulo' => 'Información anterior', 'valor' => $registro->valorAntes, 'clases' => 'border-eh-red/20 bg-eh-red-soft', 'texto' => 'text-eh-red'],
            ['titulo' => 'Información posterior', 'valor' => $registro->valorDespues, 'clases' => 'border-eh-primary/20 bg-eh-primary-soft', 'texto' => 'text-eh-primary-dark'],
        ] as $bloque)
            <x-ui.card padding="p-5" class="min-w-0">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-wide text-eh-text-muted">{{ $bloque['titulo'] }}</h2>
                @php $datos = $bloque['valor'] !== null ? json_decode($bloque['valor'], true) : null; @endphp
                @if (is_array($datos))
                    <dl class="space-y-2">
                        @foreach ($datos as $campo => $dato)
                            <div class="flex justify-between gap-4 border-b border-eh-surface-alt pb-2 text-sm">
                                <dt class="text-eh-text-muted">{{ ucfirst(str_replace('_', ' ', $campo)) }}</dt>
                                <dd class="mono break-words text-right font-medium text-eh-text">
                                    {{ is_array($dato) ? json_encode($dato, JSON_UNESCAPED_UNICODE) : (is_bool($dato) ? ($dato ? 'Sí' : 'No') : ($dato ?? '—')) }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                @elseif ($bloque['valor'] !== null)
                    <div class="mono rounded-xl border {{ $bloque['clases'] }} p-3 text-xs {{ $bloque['texto'] }}">
                        <p class="whitespace-pre-wrap break-words">{{ $bloque['valor'] }}</p>
                    </div>
                @else
                    <p class="rounded-xl bg-eh-surface-alt px-4 py-6 text-center text-sm text-eh-text-muted">Sin valor registrado</p>
                @endif
            </x-ui.card>
        @endforeach
    </div>

    <p class="mt-4 text-xs text-eh-text-muted">
        Los datos sensibles (PIN, hashes y credenciales) se filtran antes de guardarse: nunca se exponen en esta pantalla.
    </p>
@endsection
