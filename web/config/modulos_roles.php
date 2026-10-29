<?php

/*
| Módulos que un rol personalizado puede recibir. «eliminar» siempre es una baja lógica que conserva el
| historial (anular, cancelar, deshacer, retirar o desactivar); nunca un borrado físico. Las rutas que
| protege se reconocen en App\Http\Middleware\EnsurePermiso::esBajaLogica(). Los módulos sin «eliminar»
| explican en «protegido» por qué no admiten esa acción.
*/

return [
    'usuarios' => ['nombre' => 'Usuarios y accesos', 'acciones' => ['ver', 'crear', 'editar'], 'protegido' => 'Las cuentas no se eliminan: suspender o restaurar el acceso es una edición que conserva su historial.'],
    'proveedores' => ['nombre' => 'Proveedores', 'acciones' => ['ver', 'crear', 'editar', 'eliminar'], 'eliminar' => 'Retirar una ficha de proveedor (estado Retirado); se conservan entregas, pagos y QR.'],
    'acopiadores' => ['nombre' => 'Jornadas de acopio', 'acciones' => ['ver', 'crear', 'editar', 'eliminar'], 'eliminar' => 'Anular una entrega con motivo y deshacer una marca de no acopiado; ambas quedan auditadas.'],
    'zonas_vehiculos' => ['nombre' => 'Zonas y vehículos', 'acciones' => ['ver', 'crear', 'editar', 'eliminar'], 'eliminar' => 'Desactivar zonas, vehículos y rutas; reactivarlos requiere Editar.'],
    'mapa_rutas' => ['nombre' => 'Mapas y rutas', 'acciones' => ['ver', 'crear', 'editar'], 'protegido' => 'El mapa guarda nuevas versiones de cada ruta; desactivar rutas se autoriza en Zonas y vehículos.'],
    'recepcion' => ['nombre' => 'Recepción en planta', 'acciones' => ['ver', 'crear'], 'protegido' => 'Las llegadas a planta forman parte de la trazabilidad de la leche.'],
    'calidad' => ['nombre' => 'Análisis de calidad', 'acciones' => ['ver', 'crear'], 'protegido' => 'Los análisis respaldan pagos y sanciones; no se eliminan.'],
    'entregas' => ['nombre' => 'Entregas', 'acciones' => ['ver', 'crear'], 'protegido' => 'Las entregas se anulan desde Jornadas de acopio, con su permiso Eliminar.'],
    'sanciones' => ['nombre' => 'Sanciones y reclamos', 'acciones' => ['ver', 'editar'], 'protegido' => 'Las sanciones y reclamos se resuelven; no se eliminan.'],
    'produccion' => ['nombre' => 'Producción', 'acciones' => ['ver', 'crear', 'editar', 'eliminar'], 'eliminar' => 'Cancelar un lote de producción; el lote y sus movimientos se conservan.'],
    'productos' => ['nombre' => 'Productos', 'acciones' => ['ver', 'crear', 'editar', 'eliminar'], 'eliminar' => 'Desactivar productos; reactivarlos requiere Editar.'],
    'recetas' => ['nombre' => 'Recetas', 'acciones' => ['ver', 'crear', 'editar', 'eliminar'], 'eliminar' => 'Desactivar recetas; reactivarlas requiere Editar.'],
    'inventario' => ['nombre' => 'Inventario', 'acciones' => ['ver', 'crear'], 'protegido' => 'Las existencias se corrigen con un movimiento de ajuste, no eliminando registros.'],
    'ventas' => ['nombre' => 'Ventas y compradores', 'acciones' => ['ver', 'crear', 'editar', 'eliminar'], 'eliminar' => 'Anular ventas pendientes y desactivar compradores; reactivarlos requiere Editar.'],
    'comunicados' => ['nombre' => 'Comunicados', 'acciones' => ['ver', 'crear', 'editar'], 'protegido' => 'Los comunicados no tienen baja: se editan o se dejan en borrador.'],
    'importaciones' => ['nombre' => 'Importaciones', 'acciones' => ['ver', 'crear'], 'protegido' => 'Cada importación queda registrada para auditoría.'],
    'configuracion' => ['nombre' => 'Configuración', 'acciones' => ['ver', 'editar'], 'protegido' => 'La configuración se modifica; no tiene registros que eliminar.'],
    'liquidaciones' => ['nombre' => 'Liquidaciones y precios', 'acciones' => ['ver', 'crear', 'editar'], 'protegido' => 'Las liquidaciones, pagos y precios son registros económicos y no se eliminan.'],
    'auditoria' => ['nombre' => 'Auditoría', 'acciones' => ['ver'], 'protegido' => 'La auditoría es de solo lectura y no se puede alterar.'],
    'dashboard' => ['nombre' => 'Resumen del día', 'acciones' => ['ver'], 'protegido' => 'Solo consulta.'],
    'reportes' => ['nombre' => 'Reportes y exportaciones', 'acciones' => ['ver'], 'protegido' => 'Solo consulta.'],
];
