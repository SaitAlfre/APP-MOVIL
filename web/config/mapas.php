<?php

return [
    'teselas' => env('MAPA_TESELAS_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
    'centro' => [-15.61, -69.97],
    'ruteo' => env('MAPA_RUTEO_URL', 'https://routing.openstreetmap.de/routed-car/route/v1/driving'),
];
