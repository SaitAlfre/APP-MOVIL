package pe.ecolecta.domain

import android.Manifest
import android.annotation.SuppressLint
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.content.IntentFilter
import android.content.pm.PackageManager
import android.location.LocationManager
import android.os.Looper
import android.util.Log
import androidx.core.content.ContextCompat
import com.google.android.gms.location.CurrentLocationRequest
import com.google.android.gms.location.LocationAvailability
import com.google.android.gms.location.LocationCallback
import com.google.android.gms.location.LocationRequest
import com.google.android.gms.location.LocationResult
import com.google.android.gms.location.LocationServices
import com.google.android.gms.location.Priority
import kotlinx.coroutines.channels.awaitClose
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.flow.callbackFlow
import pe.ecolecta.domain.model.ConfiguracionSeguimiento

private const val TAG = "EcolectaUbicacion"

actual class LocationProvider(private val context: Context) {
    private val cliente = LocationServices.getFusedLocationProviderClient(context)

    @SuppressLint("MissingPermission")
    actual fun observarUbicacion(): Flow<EventoUbicacion> = callbackFlow {
        if (!tienePermiso()) {
            Log.w(TAG, "observarUbicacion: permiso de ubicación no concedido")
            close(PermisoUbicacionDenegadoException("Permiso de ubicación no concedido."))
            return@callbackFlow
        }

        // "Sin señal GPS" solo debe salir de señales reales de la plataforma (proveedor apagado,
        // LocationAvailability=false), nunca de "no llegó una captura nueva": con el filtro de
        // distancia mínima, quedarse quieto es una razón legítima para no recibir actualizaciones.
        if (!proveedorHabilitado()) {
            Log.w(TAG, "observarUbicacion: la ubicación del sistema (GPS/red) está desactivada")
            trySend(EventoUbicacion.SenalPerdida)
        }

        val receptorProveedor = object : BroadcastReceiver() {
            override fun onReceive(ctx: Context?, intent: Intent?) {
                val habilitado = proveedorHabilitado()
                Log.d(TAG, "cambio de proveedor de ubicación detectado, habilitado=$habilitado")
                trySend(if (habilitado) EventoUbicacion.SenalRecuperada else EventoUbicacion.SenalPerdida)
            }
        }
        ContextCompat.registerReceiver(
            context,
            receptorProveedor,
            IntentFilter(LocationManager.PROVIDERS_CHANGED_ACTION),
            ContextCompat.RECEIVER_NOT_EXPORTED,
        )

        // Fix inmediato para que el mapa del proveedor muestre algo sin esperar el primer intervalo.
        cliente.getCurrentLocation(
            CurrentLocationRequest.Builder().setPriority(Priority.PRIORITY_HIGH_ACCURACY).build(),
            null,
        ).addOnSuccessListener { ubicacion ->
            if (ubicacion != null) {
                Log.d(TAG, "fix inicial recibido: lat=${ubicacion.latitude} lng=${ubicacion.longitude} precision=${ubicacion.accuracy}")
                trySend(EventoUbicacion.Capturada(ubicacion.aUbicacionCruda()))
            } else {
                Log.w(TAG, "getCurrentLocation devolvió null (sin fix reciente en caché todavía)")
            }
        }.addOnFailureListener { error -> Log.e(TAG, "getCurrentLocation falló", error) }

        val solicitud = LocationRequest.Builder(ConfiguracionSeguimiento.INTERVALO_MS)
            .setMinUpdateIntervalMillis(ConfiguracionSeguimiento.INTERVALO_MINIMO_MS)
            .setMinUpdateDistanceMeters(ConfiguracionSeguimiento.DISTANCIA_MINIMA_M)
            .setPriority(Priority.PRIORITY_HIGH_ACCURACY)
            .build()

        val callback = object : LocationCallback() {
            override fun onLocationResult(resultado: LocationResult) {
                val ubicacion = resultado.lastLocation ?: return
                Log.d(TAG, "onLocationResult: lat=${ubicacion.latitude} lng=${ubicacion.longitude} precision=${ubicacion.accuracy}")
                trySend(EventoUbicacion.Capturada(ubicacion.aUbicacionCruda()))
            }

            override fun onLocationAvailability(availability: LocationAvailability) {
                // LocationAvailability es, según la documentación oficial, solo una estimación de la
                // plataforma — no una prueba definitiva de pérdida de señal. Se deja como log técnico
                // únicamente; el estado real de "Sin señal GPS" lo decide solo el proveedor del
                // sistema (PROVIDERS_CHANGED_ACTION) o un error real al registrar/usar la ubicación.
                Log.d(TAG, "onLocationAvailability (solo informativo, no cambia el estado): disponible=${availability.isLocationAvailable}")
            }
        }

        // Antes se pasaba `null` como Looper y se descartaba el Task devuelto: si el registro fallaba
        // (p. ej. al ejecutarse en un hilo sin Looper preparado, como Dispatchers.Default), el fallo
        // quedaba silencioso para siempre y nunca llegaba ningún callback ("Sin señal GPS" indefinido).
        // Se fuerza el Looper principal y se registra el resultado real del Task.
        cliente.requestLocationUpdates(solicitud, callback, Looper.getMainLooper())
            .addOnSuccessListener { Log.d(TAG, "requestLocationUpdates registrado correctamente") }
            .addOnFailureListener { error ->
                Log.e(TAG, "requestLocationUpdates falló", error)
                close(error)
            }

        awaitClose {
            Log.d(TAG, "deteniendo actualizaciones de ubicación")
            cliente.removeLocationUpdates(callback)
            context.unregisterReceiver(receptorProveedor)
        }
    }

    private fun tienePermiso(): Boolean =
        ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED ||
            ContextCompat.checkSelfPermission(context, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED

    private fun proveedorHabilitado(): Boolean {
        val manager = context.getSystemService(Context.LOCATION_SERVICE) as? LocationManager ?: return false
        return manager.isProviderEnabled(LocationManager.GPS_PROVIDER) || manager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)
    }

    private fun android.location.Location.aUbicacionCruda() = UbicacionCruda(
        lat = latitude,
        lng = longitude,
        precisionM = accuracy.toDouble(),
        timestamp = time,
    )
}
