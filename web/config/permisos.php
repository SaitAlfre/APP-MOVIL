<?php

/*
|--------------------------------------------------------------------------
| Matriz de permisos del panel web
|--------------------------------------------------------------------------
|
| Por cada módulo, qué roles pueden "ver" (listar/consultar) y qué roles
| pueden además "gestionar" (crear, editar, cambiar estado, ejecutar
| acciones). Administrador siempre tiene ambos en todos los módulos.
|
| Los cuatro perfiles oficiales sustituyen a los roles anteriores. Los módulos de Producción,
| Liquidaciones y Auditoría no se filtran por zona: una vez recepcionada,
| la leche se junta en una sola pila sin importar su origen.
|
| Los valores son los mismos strings que Rol::value (ver
| App\Domain\Usuarios\Rol). Se guardan como texto plano, no como casos de
| enum, para que este archivo funcione igual con `config:cache`.
|
| Esta matriz se aplica en el servidor (middleware `permiso`), no solo para
| ocultar botones en las vistas.
|
*/

$rolesWeb = ['admin', 'calidad'];

return [

    'usuarios' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'proveedores' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'acopiadores' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'zonas_vehiculos' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'mapa_rutas' => [
        'ver' => ['admin', 'acopiador'],
        'gestionar' => ['admin'],
    ],

    'recepcion' => [
        'ver' => ['admin', 'calidad'],
        'gestionar' => ['admin'],
    ],

    'calidad' => [
        'ver' => ['admin', 'calidad'],
        'gestionar' => ['admin', 'calidad'],
    ],

    'entregas' => [
        'ver' => ['admin', 'calidad'],
        'gestionar' => ['admin'],
    ],

    'sanciones' => [
        'ver' => ['admin', 'calidad'],
        'gestionar' => ['admin', 'calidad'],
    ],

    'produccion' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'productos' => ['ver' => ['admin'], 'gestionar' => ['admin']],
    'recetas' => ['ver' => ['admin'], 'gestionar' => ['admin']],

    'inventario' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'ventas' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'comunicados' => [
        'ver' => $rolesWeb,
        'gestionar' => ['admin'],
    ],

    'importaciones' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'configuracion' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'liquidaciones' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'auditoria' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'dashboard' => [
        'ver' => $rolesWeb,
        'gestionar' => $rolesWeb,
    ],

    'reportes' => [
        'ver' => $rolesWeb,
        'gestionar' => $rolesWeb,
    ],

];
