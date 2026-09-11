@extends('layouts.admin')

@section('titulo', 'Iniciar sesión')

@section('contenido')
    <div class="flex min-h-screen items-center justify-center px-4">
        <div class="w-full max-w-sm rounded-lg border border-gray-200 bg-white p-8 shadow-sm">
            <h1 class="mb-6 text-lg font-semibold">Ecolecta · Panel administrativo</h1>

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Correo</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                    <input id="password" name="password" type="password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-600 focus:ring-green-600 sm:text-sm">
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="recordar" value="1" class="rounded border-gray-300">
                    Recordarme
                </label>

                <button type="submit"
                    class="w-full rounded-md bg-green-700 px-4 py-2 text-sm font-medium text-white hover:bg-green-800">
                    Ingresar
                </button>
            </form>
        </div>
    </div>
@endsection
