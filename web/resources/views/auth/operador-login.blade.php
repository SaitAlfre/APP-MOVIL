@extends('layouts.acopiador')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <h1 class="mb-6 text-lg font-semibold">Ingreso de acopiador</h1>

    <form method="POST" action="{{ route('acopiador.login.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="username" class="block text-sm font-medium text-gray-700">Usuario</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <div>
            <label for="pin" class="block text-sm font-medium text-gray-700">PIN</label>
            <input id="pin" name="pin" type="password" inputmode="numeric" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
        </div>

        <button type="submit"
            class="w-full rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
            Ingresar
        </button>
    </form>
@endsection
