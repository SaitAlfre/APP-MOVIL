@extends('layouts.acopiador')

@section('titulo', 'Registrar lote')

@section('contenido')
    <h1 class="mb-2 text-lg font-bold text-eh-text">Registrar por lote</h1>
    <p class="mb-4 text-[13.5px] text-eh-text-muted">Todas las filas se guardan juntas: si una falla, ninguna queda registrada.</p>

    <form method="POST" action="{{ route('acopiador.lote.store') }}" class="space-y-4">
        @csrf

        <div id="filas-lote" class="space-y-3"></div>

        <button type="button" id="btn-agregar-fila"
            class="h-11 w-full rounded-xl border border-eh-border text-[13.5px] font-semibold text-eh-text hover:bg-eh-surface-alt">
            + Agregar proveedor
        </button>

        <button type="submit" class="h-12 w-full rounded-xl bg-eh-primary text-[14px] font-bold text-white hover:bg-eh-primary-dark">
            Guardar lote
        </button>
        <a href="{{ route('acopiador.home') }}" class="block text-center text-[13.5px] font-medium text-eh-text-muted">Cancelar</a>
    </form>

    <template id="plantilla-fila">
        <div class="fila-lote rounded-xl border border-eh-border bg-eh-surface p-3">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-semibold text-eh-text-muted">Proveedor</span>
                <button type="button" class="btn-quitar-fila text-xs font-semibold text-eh-red">Quitar</button>
            </div>
            <select name="" class="mb-2 block h-11 w-full rounded-lg border border-eh-border bg-eh-bg px-3 text-sm text-eh-text">
                <option value="">Selecciona un proveedor</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}">{{ $proveedor->codigo }} · {{ $proveedor->nombres }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <input type="number" step="0.01" min="0.01" placeholder="Litros" name=""
                    class="h-11 w-1/2 rounded-lg border border-eh-border bg-eh-bg px-3 text-sm text-eh-text">
                <input type="number" min="1" placeholder="Tachos" value="1" name=""
                    class="h-11 w-1/2 rounded-lg border border-eh-border bg-eh-bg px-3 text-sm text-eh-text">
            </div>
        </div>
    </template>

    <script>
        (function () {
            const contenedor = document.getElementById('filas-lote');
            const plantilla = document.getElementById('plantilla-fila');
            let indice = 0;

            function agregarFila() {
                const fragmento = plantilla.content.cloneNode(true);
                const fila = fragmento.querySelector('.fila-lote');
                const [select, litros, tachos] = fila.querySelectorAll('select, input[type="number"]');
                select.name = `items[${indice}][proveedor_id]`;
                litros.name = `items[${indice}][litros]`;
                tachos.name = `items[${indice}][tachos]`;
                fila.querySelector('.btn-quitar-fila').addEventListener('click', () => fila.remove());
                contenedor.appendChild(fila);
                indice++;
            }

            document.getElementById('btn-agregar-fila').addEventListener('click', agregarFila);
            agregarFila();
        })();
    </script>
@endsection
