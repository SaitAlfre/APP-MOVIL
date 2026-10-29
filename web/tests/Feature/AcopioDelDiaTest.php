<?php

namespace Tests\Feature;

use App\Domain\Jornadas\EtapaJornada;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\ReclamoProveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acopio del día y detalle de jornada: etapas, recepción en planta dentro de la jornada y reclamos.
 */
class AcopioDelDiaTest extends TestCase
{
    use RefreshDatabase;

    private string $hoy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoy = now('America/Lima')->toDateString();
        $this->actingAs(Usuario::factory()->create(['roles' => ['admin']]), 'operador');
    }

    private function jornadaConLitros(float $litros, bool $cerrada = true, ?string $fecha = null): Jornada
    {
        $jornada = Jornada::factory()->create(['fecha' => $fecha ?? $this->hoy, 'cerrada_en' => $cerrada ? now() : null]);
        Entrega::factory()->create(['jornada_id' => $jornada->id, 'litros' => $litros, 'anulada' => false]);

        return $jornada;
    }

    private function recibir(Jornada $jornada, float $litrosMedidos, ?string $motivo = null): void
    {
        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'),
            'litros_medidos' => $litrosMedidos,
            'motivo_diferencia' => $motivo,
        ])->assertSessionHasNoErrors();
    }

    public function test_cada_jornada_muestra_su_etapa_y_el_resumen_del_dia(): void
    {
        $enRuta = $this->jornadaConLitros(20, cerrada: false);
        $porRecibir = $this->jornadaConLitros(30);
        $conDiferencia = $this->jornadaConLitros(100);
        $sinDiferencia = $this->jornadaConLitros(50);
        $this->recibir($conDiferencia, 97, 'Se volcó un porongo en la trocha');
        $this->recibir($sinDiferencia, 50);

        $respuesta = $this->get(route('admin.jornadas.index'))->assertOk()->assertSee('Acopio del día');

        $respuesta->assertViewHas('etapas', fn (array $etapas) => $etapas[$enRuta->id] === EtapaJornada::EnRuta
            && $etapas[$porRecibir->id] === EtapaJornada::PorRecibir
            && $etapas[$conDiferencia->id] === EtapaJornada::RecibidaConDiferencia
            && $etapas[$sinDiferencia->id] === EtapaJornada::RecibidaSinDiferencia);
        $respuesta->assertViewHas('resumen', fn (array $resumen) => $resumen['litros'] === 200.0
            && $resumen['enRuta'] === 1 && $resumen['porRecibir'] === 1 && $resumen['diferencia'] === -3.0);
        $respuesta->assertSee('faltan 3.0 L');
    }

    public function test_filtra_por_etapa(): void
    {
        $porRecibir = $this->jornadaConLitros(30);
        $this->jornadaConLitros(20, cerrada: false);

        $this->get(route('admin.jornadas.index', ['etapa' => EtapaJornada::PorRecibir->value]))->assertOk()
            ->assertViewHas('paginador', fn ($paginador) => $paginador->pluck('id')->all() === [$porRecibir->id]);
        $this->get(route('admin.jornadas.index', ['etapa' => 'inventada']))->assertSessionHasErrors('etapa');
    }

    public function test_avisa_jornadas_de_dias_anteriores_sin_llegada_a_planta(): void
    {
        $this->jornadaConLitros(40, fecha: now('America/Lima')->subDays(2)->toDateString());

        $this->get(route('admin.jornadas.index'))->assertOk()
            ->assertViewHas('pendientesAnteriores', 1)
            ->assertSee('Hay 1 jornada de días anteriores');
    }

    public function test_el_detalle_reune_recojos_recepcion_y_reclamos(): void
    {
        $jornada = $this->jornadaConLitros(25);
        $web = Entrega::query()->where('jornada_id', $jornada->id)->firstOrFail();
        Entrega::factory()->create(['jornada_id' => $jornada->id, 'litros' => 18, 'uuid_movil' => 'ent-celular-1']);
        $anulada = Entrega::factory()->create(['jornada_id' => $jornada->id, 'litros' => 12]);
        $this->post(route('admin.acopiadores.entregas.anular', $anulada), ['motivo' => 'Registro duplicado'])->assertSessionHasNoErrors();
        ReclamoProveedor::query()->create(['proveedor_id' => $web->proveedor_id, 'entrega_id' => $web->id,
            'litros_originales' => 25, 'litros_solicitados' => 27, 'motivo' => 'Entregué dos litros más', 'estado' => 'pendiente']);

        $this->get(route('admin.acopiadores.jornadas.show', $jornada))->assertOk()
            ->assertSee('Por recibir en planta')
            ->assertSee('Panel web')
            ->assertSee('Celular')
            ->assertSee('Anulado: Registro duplicado')
            ->assertSee('Entregué dos litros más')
            ->assertSee('Registrar llegada')
            ->assertDontSee('name="tachos"', false)
            ->assertViewHas('litros', 43.0);
    }

    public function test_la_llegada_registrada_desde_la_jornada_vuelve_a_la_jornada(): void
    {
        $jornada = $this->jornadaConLitros(60);

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'),
            'litros_medidos' => 58,
            'motivo_diferencia' => 'Derrame al descargar',
            'volver' => 'jornada',
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.acopiadores.jornadas.show', $jornada->id));

        $this->get(route('admin.acopiadores.jornadas.show', $jornada))->assertOk()
            ->assertSee('Recibida con diferencia')
            ->assertSee('Faltan 2.0 L')
            ->assertSee('Derrame al descargar');
    }

    public function test_recepcion_en_planta_separa_viajes_por_estado_y_busca_por_zona(): void
    {
        $porRecibir = $this->jornadaConLitros(30);
        $conDiferencia = $this->jornadaConLitros(100);
        $this->recibir($conDiferencia, 96.5, 'Derrame al pasar la trocha');

        $this->get(route('admin.recepcion.index'))->assertOk()
            ->assertViewHas('conteos', fn (array $conteos) => $conteos === ['' => 2, 'pendiente' => 1, 'diferencia' => 1, 'exacta' => 0])
            ->assertViewHas('mermaPorZona', fn (array $zonas) => $zonas[0]['value'] === 3.5)
            ->assertSee('Merma por zona');
        $this->get(route('admin.recepcion.index', ['estado' => 'diferencia']))->assertOk()
            ->assertViewHas('filas', fn (array $filas) => array_column($filas, 'jornadaId') === [$conDiferencia->id]);
        $this->get(route('admin.recepcion.index', ['buscar' => $porRecibir->zona->nombre]))->assertOk()
            ->assertViewHas('filas', fn (array $filas) => array_column($filas, 'jornadaId') === [$porRecibir->id]);
    }

    public function test_recepcion_en_planta_filtra_hoy_ayer_y_esta_semana(): void
    {
        $lima = now('America/Lima');
        $hoy = $this->jornadaConLitros(30);
        $ayer = $this->jornadaConLitros(20, fecha: $lima->copy()->subDay()->toDateString());
        $haceDosSemanas = $this->jornadaConLitros(25, fecha: $lima->copy()->subDays(15)->toDateString());
        $jornadas = fn (array $filas) => collect($filas)->pluck('jornadaId')->sort()->values()->all();

        $this->get(route('admin.recepcion.index', ['periodo' => 'hoy']))->assertOk()
            ->assertViewHas('filas', fn (array $filas) => $jornadas($filas) === [$hoy->id]);
        $this->get(route('admin.recepcion.index', ['periodo' => 'ayer']))->assertOk()
            ->assertViewHas('filas', fn (array $filas) => $jornadas($filas) === [$ayer->id]);
        $this->get(route('admin.recepcion.index', ['periodo' => 'semana']))->assertOk()
            ->assertViewHas('filas', fn (array $filas) => ! in_array($haceDosSemanas->id, $jornadas($filas), true) && in_array($hoy->id, $jornadas($filas), true));
        $this->get(route('admin.recepcion.index', ['periodo' => 'mes']))->assertSessionHasErrors('periodo');
    }

    public function test_la_recepcion_solo_admite_litros_con_un_decimal(): void
    {
        $jornada = $this->jornadaConLitros(40);

        $this->post(route('admin.recepcion.llegada.store', $jornada->id), [
            'llegada_en' => now()->format('Y-m-d\TH:i'), 'litros_medidos' => 39.55, 'motivo_diferencia' => 'Derrame',
        ])->assertSessionHasErrors('litros_medidos');
    }

    public function test_una_jornada_en_ruta_no_ofrece_registrar_llegada(): void
    {
        $jornada = $this->jornadaConLitros(10, cerrada: false);

        $this->get(route('admin.acopiadores.jornadas.show', $jornada))->assertOk()
            ->assertSee('Registra la llegada cuando el acopiador termine la jornada.')
            ->assertSee('Agregar recojo')
            ->assertDontSee('data-modal-open="recepcion-'.$jornada->id.'"', false);
    }
}
