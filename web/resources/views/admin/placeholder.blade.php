@extends('layouts.admin')

@section('titulo', $titulo)

@section('contenido')
    <div class="mb-6">
        <h1 class="text-[22px] font-bold text-eh-text">{{ $titulo }}</h1>
        <p class="mt-0.5 text-[13.5px] text-eh-text-muted">Módulo del panel EcolectaHuata</p>
    </div>

    <div class="flex flex-col items-center rounded-2xl border border-dashed border-eh-border bg-eh-surface px-8 py-16 text-center">
        <span class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-eh-surface-alt">
            <svg class="size-6 text-eh-text-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="6" width="17" height="13" rx="2"/><path d="M3.5 9.5h17"/></svg>
        </span>
        <p class="mb-1.5 text-[15px] font-bold text-eh-text">Todavía no hay información aquí</p>
        <p class="max-w-sm text-[13px] text-eh-text-muted">{{ $descripcion }}</p>
    </div>
@endsection
