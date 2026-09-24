<?php

namespace Tests\Unit;

use App\Domain\Proveedores\ProveedorQr;
use PHPUnit\Framework\TestCase;

/** El panel y la app deben producir y leer el mismo contenido para la misma ficha. */
class ProveedorQrTest extends TestCase
{
    public function test_codifica_el_codigo_con_el_mismo_formato_que_la_app(): void
    {
        $this->assertSame('ECOLECTA:PROVEEDOR:CODIGO:PRV-FAON-01', ProveedorQr::generar('PRV-FAON-01'));
        $this->assertSame('PRV-FAON-01', ProveedorQr::extraerCodigo('  ECOLECTA:PROVEEDOR:CODIGO:PRV-FAON-01 '));
    }

    public function test_sigue_leyendo_el_formato_anterior_por_id(): void
    {
        $this->assertSame(17, ProveedorQr::extraerId('ECOLECTA:PROVEEDOR:17'));
        $this->assertNull(ProveedorQr::extraerCodigo('ECOLECTA:PROVEEDOR:17'));
        $this->assertNull(ProveedorQr::extraerId('ECOLECTA:PROVEEDOR:CODIGO:17'));
    }

    public function test_rechaza_contenido_ajeno_o_vacio(): void
    {
        $this->assertNull(ProveedorQr::extraerCodigo('https://example.com'));
        $this->assertNull(ProveedorQr::extraerCodigo('ECOLECTA:PROVEEDOR:CODIGO:'));
        $this->assertNull(ProveedorQr::extraerId('ECOLECTA:PROVEEDOR:abc'));
    }
}
