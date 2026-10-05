package pe.ecolecta.domain.calidad

import kotlin.test.Test
import kotlin.test.assertEquals
import kotlin.test.assertTrue
import kotlin.test.assertNull
import kotlinx.datetime.LocalDateTime
import kotlinx.datetime.TimeZone
import kotlinx.datetime.toLocalDateTime
import pe.ecolecta.domain.model.EstadoControlCalidad
import pe.ecolecta.domain.model.LecturaCalidad

class ControlCalidadTest {
    @Test
    fun `lee todos los resultados y la hora anterior a la fecha del ticket de referencia`() {
        val lectura = ParserComprobanteLactomat.parsear(comprobanteLactomatReferencia)
        assertEquals("49731", lectura.serialAnalizador)
        assertEquals("1", lectura.modoAnalizador)
        assertEquals(18.4, lectura.temperatura)
        assertEquals(4.36, lectura.grasa)
        assertEquals(8.72, lectura.sng)
        assertEquals(1.032, lectura.densidad)
        assertEquals(3.42, lectura.proteina)
        assertEquals(4.68, lectura.lactosa)
        assertEquals(0.72, lectura.sales)
        assertEquals(13.88, lectura.solidosTotales)
        assertEquals(86.12, lectura.aguaAnadida)
        assertEquals(-0.522, lectura.puntoCongelacion)
        assertEquals(6.68, lectura.ph)
        val fecha = kotlin.time.Instant.fromEpochMilliseconds(lectura.fechaHoraEpochMs!!)
            .toLocalDateTime(TimeZone.currentSystemDefault())
        assertEquals(LocalDateTime(2026, 9, 2, 12, 24), fecha)
    }

    @Test
    fun `reconstruye las filas cuando el OCR separa etiquetas y valores en columnas`() {
        val texto = ordenarTextoComprobante(listOf(
            LineaTextoComprobante("Grasa..........", 20f, 30f, 20f),
            LineaTextoComprobante("SNG............", 20f, 60f, 20f),
            LineaTextoComprobante("Punto cong.....", 20f, 90f, 20f),
            LineaTextoComprobante("4.36%", 200f, 32f, 20f),
            LineaTextoComprobante("8.72%", 200f, 61f, 20f),
            LineaTextoComprobante("−0.522 C", 200f, 91f, 20f),
        ))
        val lectura = ParserComprobanteLactomat.parsear(texto)
        assertEquals(4.36, lectura.grasa)
        assertEquals(8.72, lectura.sng)
        assertEquals(-0.522, lectura.puntoCongelacion)
    }

    @Test
    fun `una etiqueta sin valor no roba el numero del siguiente parametro`() {
        val lectura = ParserComprobanteLactomat.parsear("Temp ... Grasa 4.36%\nSNG\nDensidad 1.032")
        assertNull(lectura.temperatura)
        assertNull(lectura.sng)
        assertEquals(4.36, lectura.grasa)
        assertEquals(1.032, lectura.densidad)
    }

    @Test
    fun `parsea comprobante Lactomat con coma o punto decimal`() {
        val texto = """
            Analizador de LACTOMAT
            SN: 49731  Mode: 1
            Temp. ........ 6,5 C
            Grasa ........ 3.45%
            SNG .......... 8.70%
            Densidad ..... 1.030
            Proteina ..... 3.20%
            Lactosa ...... 4.70%
            Sales ........ 0.70%
            Total solidos  12.15%
            Agua anadida . 0.0%
            Punto cong. .. -0.530 C
            pH ........... 6.7
        """.trimIndent()

        val lectura = ParserComprobanteLactomat.parsear(texto)

        assertEquals("49731", lectura.serialAnalizador)
        assertEquals("1", lectura.modoAnalizador)
        assertEquals(6.5, lectura.temperatura)
        assertEquals(3.45, lectura.grasa)
        assertEquals(-0.530, lectura.puntoCongelacion)
        assertEquals(6.7, lectura.ph)
    }

    @Test
    fun `no confunde el valor de un parametro con un numero de otra columna en la misma linea`() {
        // Varios analizadores imprimen dos lecturas por línea para ahorrar papel; el valor de
        // cada parámetro debe tomarse del número que sigue a SU etiqueta, no del último de la línea.
        val texto = "Temp 6.5 C   Grasa 3.45 %\nDensidad 1.030   pH 6.7\nSNG 8.70%   Lactosa 4.70%"

        val lectura = ParserComprobanteLactomat.parsear(texto)

        assertEquals(6.5, lectura.temperatura)
        assertEquals(3.45, lectura.grasa)
        assertEquals(1.030, lectura.densidad)
        assertEquals(6.7, lectura.ph)
        assertEquals(8.70, lectura.sng)
        assertEquals(4.70, lectura.lactosa)
    }

    @Test
    fun `reconoce etiquetas en ingles que usan otros modelos de analizador`() {
        val texto = """
            S/N 88213  Mode 2
            Fat 3.60%
            SNF 8.75%
            Density 1.031
            Protein 3.30%
            Lactose 4.75%
            Salts 0.72%
            Total Solids 12.35%
            Water 0.0%
            Freezing Point -0.525 C
            pH 6.65
        """.trimIndent()

        val lectura = ParserComprobanteLactomat.parsear(texto)

        assertEquals("88213", lectura.serialAnalizador)
        assertEquals("2", lectura.modoAnalizador)
        assertEquals(3.60, lectura.grasa)
        assertEquals(8.75, lectura.sng)
        assertEquals(1.031, lectura.densidad)
        assertEquals(3.30, lectura.proteina)
        assertEquals(4.75, lectura.lactosa)
        assertEquals(0.72, lectura.sales)
        assertEquals(12.35, lectura.solidosTotales)
        assertEquals(0.0, lectura.aguaAnadida)
        assertEquals(-0.525, lectura.puntoCongelacion)
        assertEquals(6.65, lectura.ph)
    }
}

internal val comprobanteLactomatReferencia = """
    Analizador de LACTOMAT
    SN: 49731  Mode: 1
    Temp. 18.4 C
    Grasa............ 4.36%
    SNG.............. 8.72%
    Densidad......... 1.032
    Proteina......... 3.42%
    Lactosa.......... 4.68%
    Sales............ 0.72%
    Total solidos.... 13.88%
    Agua anadida..... 86.12%
    Punto cong....... -0.522 C
    pH............... 6.68
    12:24 02/09/26
""".trimIndent()
