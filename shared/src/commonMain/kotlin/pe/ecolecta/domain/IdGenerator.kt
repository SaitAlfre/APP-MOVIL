package pe.ecolecta.domain

import kotlin.uuid.ExperimentalUuidApi
import kotlin.uuid.Uuid

@OptIn(ExperimentalUuidApi::class)
fun nuevoId(): String = Uuid.random().toString()
