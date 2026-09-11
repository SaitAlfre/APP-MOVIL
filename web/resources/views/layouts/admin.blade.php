<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Ecolecta') · Panel administrativo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans text-gray-900">
    @auth('admin')
        <div class="min-h-full">
            <nav class="border-b border-gray-200 bg-white">
                <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                    <a href="{{ route('admin.proveedores.index') }}" class="font-semibold">Ecolecta · Admin</a>
                    <div class="flex items-center gap-4 text-sm text-gray-600">
                        <span>{{ auth('admin')->user()->nombres }}</span>
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="text-red-600 hover:underline">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
            </nav>

            <main class="mx-auto max-w-5xl px-4 py-8">
                @if (session('estado'))
                    <div class="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                        {{ session('estado') }}
                    </div>
                @endif

                @yield('contenido')
            </main>
        </div>
    @else
        @yield('contenido')
    @endauth
</body>
</html>
