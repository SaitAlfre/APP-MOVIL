<?php

return [
    'teselas' => env('MAPA_TESELAS_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    // Planta de Huata: los camiones salen de aquí y vuelven aquí. Ubicación tomada de Google Maps (Street View).
    'planta' => [
        'nombre' => env('MAPA_PLANTA_NOMBRE', 'Planta Ecolactea · Huata'),
        'latitud' => (float) env('MAPA_PLANTA_LAT', -15.6137348),
        'longitud' => (float) env('MAPA_PLANTA_LNG', -69.976203),
    ],
    'centro' => [(float) env('MAPA_PLANTA_LAT', -15.6137348), (float) env('MAPA_PLANTA_LNG', -69.976203)],
    'ruteo' => env('MAPA_RUTEO_URL', 'https://routing.openstreetmap.de/routed-car/route/v1/driving'),
    // OpenRouteService traza el camino desde el servidor; la clave nunca se envía al navegador ni al celular.
    'ors' => [
        'clave' => env('ORS_API_KEY', env('OpenRouteService')),
        'url' => env('ORS_URL', 'https://api.openrouteservice.org'),
        'perfiles' => ['camion' => 'driving-hgv', 'motocarga' => 'driving-car'],
    ],
];
