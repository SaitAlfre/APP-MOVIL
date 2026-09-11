@extends('layouts.admin')

@section('titulo', 'Proveedores')

@section('contenido')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-[22px] font-bold text-eh-text">Proveedores</h1>
            <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Registro y estado de los proveedores de leche</p>
        </div>
        <a href="{{ route('admin.proveedores.create') }}"
            class="flex h-11 items-center rounded-xl bg-eh-primary px-4 text-sm font-semibold text-white hover:bg-eh-primary-dark">
            Nuevo proveedor
        </a>
    </div>

    <div class="overflow-hidden rounded-2xl border border-eh-border bg-eh-surface shadow-sm">
        <div class="overflow-x-auto">
        <table class="w-full min-w-[720px] text-sm">
            <thead class="bg-eh-table-head text-left text-[11px] font-semibold uppercase tracking-wide text-eh-text-muted">
                <tr>
                    <th class="px-5 py-3">Código</th>
                    <th class="px-3 py-3">Nombres</th>
                    <th class="px-3 py-3">DNI</th>
                    <th class="px-3 py-3">Zona</th>
                    <th class="px-3 py-3 text-right">Tachos</th>
                    <th class="px-3 py-3">Estado</th>
                    <th class="px-5 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proveedores as $i => $proveedor)
                    <tr @class(['border-t border-eh-border', 'bg-eh-stripe' => $i % 2 === 1])>
                        <td class="px-5 py-3 font-semibold text-eh-text">{{ $proveedor->codigo }}</td>
                        <td class="px-3 py-3 text-eh-text">{{ $proveedor->nombres }}</td>
                        <td class="px-3 py-3 text-eh-text-muted">{{ $proveedor->dni }}</td>
                        <td class="px-3 py-3 text-eh-text-muted">{{ $zonas->get($proveedor->zonaId)?->nombre ?? '—' }}</td>
                        <td class="px-3 py-3 text-right text-eh-text">{{ $proveedor->tachos }}</td>
                        <td class="px-3 py-3">
                            <span @class([
                                'rounded-full px-2.5 py-1 text-[11px] font-semibold',
                                'bg-eh-primary-soft text-eh-primary' => $proveedor->estado->value === 'activo',
                                'bg-eh-gold-soft text-eh-gold' => $proveedor->estado->value === 'suspendido',
                                'bg-eh-surface-alt text-eh-text-muted' => $proveedor->estado->value === 'retirado',
                            ])>
                                {{ $proveedor->estado->etiqueta() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.proveedores.qr', $proveedor->id) }}" target="_blank"
                                class="mr-3 text-[12.5px] font-semibold text-eh-text-muted hover:text-eh-text">QR</a>
                            <a href="{{ route('admin.proveedores.edit', $proveedor->id) }}"
                                class="text-[12.5px] font-semibold text-eh-primary">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-14 text-center">
                            <div class="mx-auto flex max-w-xs flex-col items-center">
                                <span class="mb-3 flex size-11 items-center justify-center rounded-xl bg-eh-surface-alt">
                                    <svg class="size-5 text-eh-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.2"/><path d="M3 20c0-3.6 2.7-6 6-6s6 2.4 6 6"/></svg>
                                </span>
                                <p class="text-[13.5px] font-semibold text-eh-text">Aún no hay proveedores registrados</p>
                                <p class="mt-1 text-[12.5px] text-eh-text-muted">Registra el primero con el botón "Nuevo proveedor".</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $proveedores->links() }}
    </div>
@endsection
