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
| "Consulta" ve todo pero no gestiona nada. Los módulos de Producción,
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

$rolesWeb = ['admin', 'recepcion', 'calidad', 'produccion', 'liquidaciones', 'consulta'];

return [

    'usuarios' => [
        'ver' => ['admin'],
        'gestionar' => ['admin'],
    ],

    'proveedores' => [
        'ver' => ['admin', 'consulta'],
        'gestionar' => ['admin'],
    ],

    'acopiadores' => [
        'ver' => ['admin', 'consulta'],
        'gestionar' => ['admin'],
    ],

    'zonas_vehiculos' => [
        'ver' => ['admin', 'consulta'],
        'gestionar' => ['admin'],
    ],

    'recepcion' => [
        'ver' => ['admin', 'recepcion', 'calidad', 'produccion', 'liquidaciones', 'consulta'],
        'gestionar' => ['admin', 'recepcion'],
    ],

    'calidad' => [
        'ver' => ['admin', 'recepcion', 'calidad', 'produccion', 'liquidaciones', 'consulta'],
        'gestionar' => ['admin', 'calidad'],
    ],

    'entregas' => [
        'ver' => ['admin', 'recepcion', 'calidad', 'consulta'],
        'gestionar' => ['admin', 'recepcion'],
    ],

    'sanciones' => [
        'ver' => ['admin', 'calidad', 'liquidaciones', 'consulta'],
        'gestionar' => ['admin', 'calidad'],
    ],

    'produccion' => [
        'ver' => ['admin', 'recepcion', 'calidad', 'produccion', 'liquidaciones', 'consulta'],
        'gestionar' => ['admin', 'produccion'],
    ],

    'inventario' => [
        'ver' => ['admin', 'produccion', 'consulta'],
        'gestionar' => ['admin', 'produccion'],
    ],

    'ventas' => [
        'ver' => ['admin', 'produccion', 'consulta'],
        'gestionar' => ['admin', 'produccion'],
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
        'ver' => ['admin', 'recepcion', 'calidad', 'produccion', 'liquidaciones', 'consulta'],
        'gestionar' => ['admin', 'liquidaciones'],
    ],

    'auditoria' => [
        'ver' => ['admin', 'consulta'],
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
