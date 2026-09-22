# Portal móvil del proveedor

Referencia pública: https://tutu-snack-52109538.figma.site/proveedor

Se trasladaron los estilos y la estructura del prototipo publicado a Compose Multiplatform: cabecera azul, fondo crema, tarjetas, acciones y navegación Inicio / Entregas / Calidad / Pagos / Perfil. El acceso al archivo editable de Figma fue rechazado por permisos. La igualdad visual exacta aún requiere comparación en dispositivo; no se ha certificado.

## Funciones implementadas

- Inicio con litros del proveedor autenticado, semana jueves–miércoles calculada en America/Lima y acceso a los módulos.
- Entregas con filtro semanal/historial y detalle; no permite cambiar litros al proveedor.
- Calidad: lista propia y detalle de los once parámetros, estado, observaciones y texto del comprobante si existe. No se inventan resultados ni umbrales.
- QR real compatible con el identificador existente; compartir imagen y aumentar/restaurar brillo en Android.
- Reclamos: selección de entrega, litros, motivo, descripción y foto opcional. Validación de propiedad, cantidad finita, dos decimales y duplicados.
- Traslado: selección de otra zona activa y motivo; guarda solicitud sin cambiar la asignación del proveedor.
- Solicitudes persistidas con estado PENDIENTE_ENVIO; consulta de evidencia y compartir resumen mediante Android. Compartir no confirma recepción administrativa.
- Perfil, acceso a la ruta existente, recarga local y cierre de sesión.
- Pagos: presentación de liquidaciones almacenadas y exportación de comprobante de texto para pagos confirmados. Actualmente no hay un servicio que alimente esos registros: la pantalla muestra el estado vacío y permite consultar entregas.

## Límites pendientes para operación remota

Este proyecto no tiene API móvil conectada para pagos, reclamos, traslados o comunicados. No se ha modificado la web ni se han añadido endpoints allí. Para cerrar el flujo real deben acordarse autenticación y contrato de API, descargar liquidaciones/comunicados, enviar solicitudes idempotentes con evidencia y recibir sus estados administrativos. El estado local no se presenta como «enviado».

El precio y total del inicio quedan «Por confirmar» sin una liquidación vigente. Los datos del prototipo (nombres, importes, comunicado de octubre) no se copian como información real.

Falta `androidApp/google-services.json` para una compilación normal con Firebase. La vista local de prueba no conecta Firebase ni comparte almacenamiento con la instalación normal.

## Probar en Android Studio

Abrir `E:\shagyy\APP-MOVIL`. Para generar la vista local desde PowerShell:

```powershell
$env:ANDROID_HOME='D:\android1\AVD\Sdk'
.\gradlew.bat :androidApp:assembleDebug -PlocalPreview=true
```

APK: `androidApp/build/outputs/apk/debug/androidApp-debug.apk`.
Paquete de vista local: `pe.ecolecta.preview`.
Usuario local de ejemplo: `prov_collana_01`. PIN: `1234`.

Para la configuración normal, colocar el archivo Firebase del proyecto autorizado en `androidApp/google-services.json` y compilar sin `-PlocalPreview=true`. Nunca usar la vista local como release.

## Verificaciones

```powershell
.\gradlew.bat :shared:testAndroidHostTest -PlocalPreview=true
node scripts/verificar-portal-proveedor.mjs
```

La prueba SQL crea una base temporal independiente, aplica la migración 7, comprueba separación por proveedor, rechaza IDs duplicados y reabre la base para verificar persistencia y conservación de la zona original. No borra ni modifica la base de la aplicación.

La prueba visual, el selector de fotos, compartir QR y el brillo deben verificarse en Android. El intento automatizado de abrir navegador/emulador fue bloqueado por la revisión de ejecución.
