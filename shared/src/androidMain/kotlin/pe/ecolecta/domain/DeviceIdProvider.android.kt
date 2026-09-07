package pe.ecolecta.domain

import android.annotation.SuppressLint
import android.content.Context
import android.provider.Settings

class DeviceIdProviderAndroid(private val context: Context) : DeviceIdProvider {
    @SuppressLint("HardwareIds")
    override fun obtenerId(): String =
        Settings.Secure.getString(context.contentResolver, Settings.Secure.ANDROID_ID) ?: "android-desconocido"
}
