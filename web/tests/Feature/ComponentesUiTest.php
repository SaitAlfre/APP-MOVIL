<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Componentes del design system: se prueban aislados porque los reutiliza todo el panel
 * y un fallo aquí se propaga a todas las pantallas.
 */
class ComponentesUiTest extends TestCase
{
    public function test_la_insignia_de_estado_traduce_las_claves_del_dominio(): void
    {
        $casos = [
            'activo' => 'Activo',
            'en_proceso' => 'En proceso',
            'pagada' => 'Pagada',
            'anulada' => 'Anulada',
            'suspendido' => 'Suspendido',
        ];

        foreach ($casos as $clave => $etiqueta) {
            $html = Blade::render('<x-ui.estado :estado="$estado" />', ['estado' => $clave]);

            $this->assertStringContainsString($etiqueta, $html, "El estado {$clave} debería mostrarse como «{$etiqueta}».");
        }
    }

    public function test_la_insignia_de_estado_normaliza_acentos_y_mayusculas(): void
    {
        $html = Blade::render('<x-ui.estado estado="Revisión" />');

        $this->assertStringContainsString('En revisión', $html);
    }

    public function test_un_estado_desconocido_se_muestra_legible_en_vez_de_romper(): void
    {
        $html = Blade::render('<x-ui.estado estado="estado_inventado" />');

        $this->assertStringContainsString('Estado inventado', $html);
        $this->assertStringContainsString('bg-eh-surface-alt', $html);
    }

    public function test_el_color_de_la_insignia_nunca_va_solo_sin_texto(): void
    {
        foreach (['aprobado', 'rechazado', 'pendiente'] as $estado) {
            $html = Blade::render('<x-ui.estado :estado="$estado" />', ['estado' => $estado]);
            $texto = trim(strip_tags($html));

            $this->assertNotSame('', $texto, "La insignia de {$estado} quedó sin texto accesible.");
        }
    }

    public function test_un_icono_inexistente_cae_en_un_icono_generico_sin_fallar(): void
    {
        $html = Blade::render('<x-icon name="icono-que-no-existe" />');

        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('<path', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_un_icono_de_varios_trazos_los_dibuja_todos(): void
    {
        $html = Blade::render('<x-icon name="cog" />');

        $this->assertSame(2, substr_count($html, '<path'), 'El icono cog tiene dos trazos en el diseño.');
    }

    public function test_el_grafico_de_barras_sin_datos_muestra_el_mensaje_vacio(): void
    {
        $html = Blade::render('<x-ui.chart-bars :data="[]" empty="No hay entregas en este periodo." />');

        $this->assertStringContainsString('No hay entregas en este periodo.', $html);
        $this->assertStringNotContainsString('<figure', $html);
    }

    public function test_el_grafico_de_barras_con_todo_en_cero_tambien_muestra_el_vacio(): void
    {
        $datos = [['label' => 'Lun', 'value' => 0], ['label' => 'Mar', 'value' => 0]];

        $html = Blade::render('<x-ui.chart-bars :data="$datos" empty="Sin datos." />', ['datos' => $datos]);

        $this->assertStringContainsString('Sin datos.', $html);
    }

    public function test_el_grafico_de_barras_ofrece_una_alternativa_textual_con_los_valores(): void
    {
        $datos = [['label' => '20/09', 'value' => 120.5], ['label' => '21/09', 'value' => 80]];

        $html = Blade::render(
            '<x-ui.chart-bars :data="$datos" unit="L" description="Litros por día." />',
            ['datos' => $datos],
        );

        $this->assertStringContainsString('<figcaption', $html);
        $this->assertStringContainsString('Litros por día.', $html);
        $this->assertStringContainsString('20/09: 120.5 L', $html);
        $this->assertStringContainsString('21/09: 80.0 L', $html);
    }

    public function test_el_grafico_de_barras_reparte_como_maximo_seis_etiquetas_en_el_eje(): void
    {
        $datos = collect(range(1, 30))
            ->map(fn (int $dia) => ['label' => str_pad((string) $dia, 2, '0', STR_PAD_LEFT).'/09', 'value' => $dia])
            ->all();

        $html = Blade::render('<x-ui.chart-bars :data="$datos" />', ['datos' => $datos]);

        // 30 barras, pero solo 6 etiquetas posicionadas en el eje X.
        $this->assertSame(6, substr_count($html, 'style="left:'));
    }

    public function test_la_dona_calcula_porcentajes_y_total_sobre_los_valores_reales(): void
    {
        $datos = [
            ['label' => 'Aprobado', 'value' => 75, 'color' => '#2E7D46'],
            ['label' => 'Rechazado', 'value' => 25, 'color' => '#C94A4A'],
        ];

        $html = Blade::render('<x-ui.chart-donut :data="$datos" />', ['datos' => $datos]);

        $this->assertStringContainsString('Aprobado: 75 (75.0%)', $html);
        $this->assertStringContainsString('Rechazado: 25 (25.0%)', $html);
        $this->assertStringContainsString('>100<', $html, 'El centro de la dona debe mostrar el total.');
    }

    public function test_la_dona_descarta_las_categorias_en_cero(): void
    {
        $datos = [
            ['label' => 'Aprobado', 'value' => 5, 'color' => '#2E7D46'],
            ['label' => 'Observado', 'value' => 0, 'color' => '#E8B339'],
        ];

        $html = Blade::render('<x-ui.chart-donut :data="$datos" />', ['datos' => $datos]);

        $this->assertStringContainsString('Aprobado', $html);
        $this->assertStringNotContainsString('Observado', $html);
    }

    public function test_las_barras_horizontales_muestran_la_linea_de_contexto(): void
    {
        $datos = [['label' => 'Zona Norte', 'value' => 215.5, 'meta' => '8 entregas']];

        $html = Blade::render('<x-ui.chart-hbars :data="$datos" meta-key="meta" unit="L" />', ['datos' => $datos]);

        $this->assertStringContainsString('Zona Norte', $html);
        $this->assertStringContainsString('215.5 L', $html);
        $this->assertStringContainsString('8 entregas', $html);
    }

    public function test_la_tabla_lleva_caption_accesible_y_encabezados_con_scope(): void
    {
        $html = Blade::render(
            '<x-ui.table :headers="[\'Proveedor\', \'Litros\']" caption="Entregas del día"><tr><td>Fila</td></tr></x-ui.table>',
        );

        $this->assertStringContainsString('<caption class="sr-only">Entregas del día</caption>', $html);
        $this->assertSame(2, substr_count($html, 'scope="col"'));
    }

    public function test_el_campo_marca_el_error_de_validacion_del_formulario(): void
    {
        $bolsa = new ViewErrorBag;
        $bolsa->put('default', new MessageBag(['dni' => ['El DNI ya está registrado.']]));
        view()->share('errors', $bolsa);

        $html = Blade::render('<x-ui.field name="dni" label="DNI" />');

        $this->assertStringContainsString('El DNI ya está registrado.', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="dni-error"', $html);
        $this->assertStringContainsString('border-eh-red', $html);
    }

    public function test_un_campo_obligatorio_se_anuncia_tambien_a_lectores_de_pantalla(): void
    {
        view()->share('errors', new ViewErrorBag);

        $html = Blade::render('<x-ui.field name="litros" label="Litros" required unit="L" />');

        $this->assertStringContainsString('(obligatorio)', $html);
        $this->assertStringContainsString('required', $html);
        $this->assertStringContainsString('>L<', $html);
    }

    public function test_el_boton_es_enlace_cuando_recibe_href_y_boton_cuando_no(): void
    {
        $enlace = Blade::render('<x-ui.btn href="/admin/proveedores" icon="plus">Nuevo</x-ui.btn>');
        $boton = Blade::render('<x-ui.btn type="submit">Guardar</x-ui.btn>');

        $this->assertStringContainsString('<a href="/admin/proveedores"', $enlace);
        $this->assertStringContainsString('<svg', $enlace);
        $this->assertStringContainsString('<button type="submit"', $boton);
        $this->assertStringNotContainsString('<a ', $boton);
    }

    public function test_las_pestanas_marcan_la_activa_con_aria_current(): void
    {
        $tabs = [
            ['key' => 'resumen', 'label' => 'Resumen', 'url' => '/x?tab=resumen'],
            ['key' => 'entregas', 'label' => 'Entregas', 'url' => '/x?tab=entregas'],
        ];

        $html = Blade::render('<x-ui.tabs :tabs="$tabs" active="entregas" />', ['tabs' => $tabs]);

        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertStringContainsString('border-eh-primary text-eh-primary', $html);
    }

    public function test_el_paginador_usa_la_plantilla_del_diseno(): void
    {
        $paginador = new LengthAwarePaginator(['a'], 40, 15, 2, ['path' => '/admin/proveedores']);

        $html = $paginador->links()->toHtml();

        $this->assertStringContainsString('← Ant.', $html);
        $this->assertStringContainsString('Sig. →', $html);
        $this->assertStringContainsString('aria-label="Paginación"', $html);
        $this->assertStringContainsString('bg-eh-primary', $html);
    }

    public function test_el_estado_vacio_puede_ofrecer_una_accion_siguiente(): void
    {
        $html = Blade::render(
            '<x-ui.empty icon="droplets" title="Sin entregas" description="Aún no hay registros."><x-slot:action><x-ui.btn size="sm">Registrar</x-ui.btn></x-slot:action></x-ui.empty>',
        );

        $this->assertStringContainsString('Sin entregas', $html);
        $this->assertStringContainsString('Aún no hay registros.', $html);
        $this->assertStringContainsString('Registrar', $html);
    }
}
