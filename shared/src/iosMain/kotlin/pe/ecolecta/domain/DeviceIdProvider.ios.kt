package pe.ecolecta.domain

import platform.UIKit.UIDevice

class DeviceIdProviderIOS : DeviceIdProvider {
    override fun obtenerId(): String =
        UIDevice.currentDevice.identifierForVendor?.UUIDString ?: "ios-desconocido"
}
