package pe.ecolecta.presentation.proveedor

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.size
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.WaterDrop
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import pe.ecolecta.domain.model.Entrega
import pe.ecolecta.presentation.design.ChipSync
import pe.ecolecta.presentation.design.Colores
import pe.ecolecta.presentation.design.Espaciado
import pe.ecolecta.presentation.design.Tarjeta
import pe.ecolecta.presentation.design.formatearFechaHora
import pe.ecolecta.presentation.design.formatearLitros

/**
 * Fila de entrega tal como la ve el proveedor: gota + litros + fecha, con la insignia de
 * sincronización a la derecha. La comparten Inicio y Mis entregas, que antes la repetían con
 * pequeñas diferencias de color e íconos.
 */
@Composable
fun FilaEntrega(entrega: Entrega, modifier: Modifier = Modifier, onClick: (() -> Unit)? = null) {
    Tarjeta(modifier = modifier, onClick = onClick) {
        Row(
            Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Row(horizontalArrangement = Arrangement.spacedBy(Espaciado.s), verticalAlignment = Alignment.CenterVertically) {
                Icon(
                    Icons.Filled.WaterDrop,
                    contentDescription = null,
                    tint = Colores.brand,
                    modifier = Modifier.size(20.dp),
                )
                Column {
                    Text(formatearLitros(entrega.litros), color = Colores.textPrimary, style = MaterialTheme.typography.titleMedium)
                    Text(
                        formatearFechaHora(entrega.registradoEn),
                        color = Colores.textSecundario,
                        style = MaterialTheme.typography.bodySmall,
                    )
                }
            }
            ChipSync(entrega)
        }
    }
}
