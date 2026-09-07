package pe.ecolecta.domain.fake

import pe.ecolecta.domain.DeviceIdProvider

class FakeDeviceIdProvider(private val id: String = "device-test") : DeviceIdProvider {
    override fun obtenerId(): String = id
}
