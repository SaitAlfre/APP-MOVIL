<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Ecolecta') · Acopiador</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-gray-900">
    <div class="mx-auto min-h-full max-w-md bg-white shadow-sm">
        @auth('operador')
            <header class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                <a href="{{ route('acopiador.home') }}" class="font-semibold">Ecolecta · Acopiador</a>
                <form method="POST" action="{{ route('acopiador.logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-red-600 hover:underline">Salir</button>
                </form>
            </header>
        @endauth

        <main class="px-4 py-5">
            @if (session('estado'))
                <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('estado') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @yield('contenido')
        </main>
    </div>
</body>
</html>
