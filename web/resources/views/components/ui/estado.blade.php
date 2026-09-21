@props(['estado'])

@php
    /**
     * Mapa estado → insignia del diseño. El texto siempre acompaña al color.
     *
     * @var array<string, array{0: string, 1: string}> $mapa
     */
    $mapa = [
        'activo' => ['Activo', 'green'],
        'activa' => ['Activa', 'green'],
        'inactivo' => ['Inactivo', 'gray'],
        'inactiva' => ['Inactiva', 'gray'],
        'observado' => ['Observado', 'yellow'],
        'aprobado' => ['Aprobado', 'green'],
        'aceptable' => ['Aceptable', 'green'],
        'rechazado' => ['Rechazado', 'red'],
        'abierta' => ['Abierta', 'green'],
        'cerrada' => ['Cerrada', 'gray'],
        'abierto' => ['Abierto', 'green'],
        'cerrado' => ['Cerrado', 'gray'],
        'borrador' => ['Borrador', 'gray'],
        'revisada' => ['Revisada', 'blue'],
        'aprobada' => ['Aprobada', 'green'],
        'pagada' => ['Pagada', 'dark'],
        'anulada' => ['Anulada', 'red'],
        'anulado' => ['Anulado', 'red'],
        'registrada' => ['Registrada', 'green'],
        'revision' => ['En revisión', 'yellow'],
        'completada' => ['Completada', 'green'],
        'pendiente' => ['Pendiente', 'yellow'],
        'sincronizada' => ['Sincronizada', 'green'],
        'normal' => ['Normal', 'green'],
        'bajo' => ['Stock bajo', 'yellow'],
        'planificado' => ['Planificado', 'blue'],
        'en_proceso' => ['En proceso', 'yellow'],
        'finalizado' => ['Finalizado', 'green'],
        'cancelado' => ['Cancelado', 'red'],
        'bloqueado' => ['Bloqueado', 'red'],
        'suspendido' => ['Suspendido', 'red'],
    ];

    $clave = \Illuminate\Support\Str::of((string) $estado)->lower()->ascii()->replace([' ', '-'], '_')->toString();
    [$texto, $variante] = $mapa[$clave] ?? [\Illuminate\Support\Str::of((string) $estado)->replace('_', ' ')->ucfirst()->toString(), 'gray'];
@endphp

<x-ui.badge :variant="$variante" :label="$texto" {{ $attributes }} />
