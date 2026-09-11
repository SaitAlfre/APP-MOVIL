@extends('layouts.acopiador')

@section('titulo', 'Registrar lote')

@section('contenido')
    <h1 class="mb-4 text-lg font-semibold">Registrar por lote</h1>
    <p class="mb-4 text-sm text-gray-600">Todas las filas se guardan juntas: si una falla, ninguna queda registrada.</p>

    <form method="POST" action="{{ route('acopiador.lote.store') }}" class="space-y-4">
        @csrf

        <div id="filas-lote" class="space-y-3"></div>

        <button type="button" id="btn-agregar-fila"
            class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">
            + Agregar proveedor
        </button>

        <button type="submit" class="w-full rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
            Guardar lote
        </button>
        <a href="{{ route('acopiador.home') }}" class="block text-center text-sm text-gray-600 hover:underline">Cancelar</a>
    </form>

    <template id="plantilla-fila">
        <div class="fila-lote rounded-md border border-gray-200 p-3">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Proveedor</span>
                <button type="button" class="btn-quitar-fila text-xs text-red-600 hover:underline">Quitar</button>
            </div>
            <select name="" class="mb-2 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                <option value="">Selecciona un proveedor</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}">{{ $proveedor->codigo }} · {{ $proveedor->nombres }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <input type="number" step="0.01" min="0.01" placeholder="Litros" name=""
                    class="w-1/2 rounded-md border-gray-300 text-sm shadow-sm">
                <input type="number" min="1" placeholder="Tachos" value="1" name=""
                    class="w-1/2 rounded-md border-gray-300 text-sm shadow-sm">
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
