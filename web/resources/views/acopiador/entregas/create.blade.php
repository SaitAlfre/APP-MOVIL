@extends('layouts.acopiador')

@section('titulo', 'Registrar entrega')

@section('contenido')
    <h1 class="mb-4 text-lg font-semibold">Registrar entrega</h1>

    @if (session('duplicado'))
        @php($duplicado = session('duplicado'))
        <div class="mb-4 rounded-md border border-yellow-300 bg-yellow-50 p-4 text-sm">
            <p class="mb-3">Este proveedor ya tiene una entrega registrada en esta jornada
                ({{ number_format($duplicado['litros_existentes'], 1) }} L · {{ $duplicado['tachos_existentes'] }} tachos).
                ¿Quieres sumar esta cantidad a la existente o registrarla aparte?</p>
            <div class="flex gap-2">
                <form method="POST" action="{{ route('acopiador.entregas.sumar') }}">
                    @csrf
                    <input type="hidden" name="entrega_id" value="{{ $duplicado['entrega_id'] }}">
                    <input type="hidden" name="litros" value="{{ $duplicado['litros_existentes'] + $duplicado['litros_nuevos'] }}">
                    <input type="hidden" name="tachos" value="{{ $duplicado['tachos_existentes'] + $duplicado['tachos_nuevos'] }}">
                    <button type="submit" class="rounded-md bg-green-700 px-3 py-1.5 text-xs font-medium text-white">Sumar</button>
                </form>
                <form method="POST" action="{{ route('acopiador.entregas.store') }}">
                    @csrf
                    <input type="hidden" name="proveedor_id" value="{{ old('proveedor_id') }}">
                    <input type="hidden" name="litros" value="{{ $duplicado['litros_nuevos'] }}">
                    <input type="hidden" name="tachos" value="{{ $duplicado['tachos_nuevos'] }}">
                    <input type="hidden" name="observaciones" value="{{ old('observaciones') }}">
                    <input type="hidden" name="forzar" value="1">
                    <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium">Registrar aparte</button>
                </form>
            </div>
        </div>
    @endif

    <div class="mb-4">
        <button id="btn-qr" type="button" data-url-resolver="{{ route('acopiador.qr.resolver') }}"
            class="w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">
            Escanear QR del proveedor
        </button>
        <div id="qr-reader" class="mt-3 hidden"></div>
        <p id="qr-estado" class="mt-2 text-xs text-gray-500"></p>
    </div>

    <form method="POST" action="{{ route('acopiador.entregas.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="proveedor_id" class="block text-sm font-medium text-gray-700">Proveedor</label>
            <select id="proveedor_id" name="proveedor_id" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
                <option value="">Selecciona un proveedor</option>
                @foreach ($proveedores as $proveedor)
                    <option value="{{ $proveedor->id }}" @selected(old('proveedor_id', $proveedorPreseleccionado) == $proveedor->id)>
                        {{ $proveedor->codigo }} · {{ $proveedor->nombres }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Litros</label>
            <div class="mt-1 mb-2 flex gap-2">
                @foreach ([10, 20, 40] as $preset)
                    <button type="button" onclick="document.getElementById('litros').value = {{ $preset }}"
                        class="rounded-md border border-gray-300 px-3 py-1 text-xs hover:bg-gray-50">{{ $preset }} L</button>
                @endforeach
            </div>
            <input id="litros" name="litros" type="number" step="0.01" min="0.01" value="{{ old('litros') }}" required
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <div>
            <label for="tachos" class="block text-sm font-medium text-gray-700">Tachos</label>
            <input id="tachos" name="tachos" type="number" min="1" value="{{ old('tachos', 1) }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <div>
            <label for="observaciones" class="block text-sm font-medium text-gray-700">Observaciones</label>
            <input id="observaciones" name="observaciones" type="text" value="{{ old('observaciones') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <button type="submit" class="w-full rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
            Guardar
        </button>
        <a href="{{ route('acopiador.home') }}" class="block text-center text-sm text-gray-600 hover:underline">Cancelar</a>
    </form>
@endsection
