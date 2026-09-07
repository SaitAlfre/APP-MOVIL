package pe.ecolecta.domain.model

enum class SyncState {
    PENDING,
    SYNCING,
    SYNCED,
    ERROR,
    CONFLICT,
}
