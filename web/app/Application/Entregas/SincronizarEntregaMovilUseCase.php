<?php

namespace App\Application\Entregas;

use App\Domain\Auditoria\AccionAuditoria;
use App\Domain\Entregas\Exceptions\EntregaMovilRechazadaException;
use App\Infrastructure\Persistence\Eloquent\Auditoria;
use App\Infrastructure\Persistence\Eloquent\Entrega;
use App\Infrastructure\Persistence\Eloquent\Jornada;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Recibe una entrega registrada en la app móvil y la deja en la tabla `entregas` que consulta el panel.
 *
 * - Idempotente: la clave es el id del celular (`uuid_movil`). Reenviar la misma versión no cambia nada.
 * - Los ids locales del celular no existen aquí: proveedor, zona y vehículo se resuelven por su código,
 *   nombre y placa. Si alguno no existe, se rechaza con un motivo claro en vez de adivinar.
 * - El autor es el usuario del token, nunca un dato del cuerpo de la petición.
 * - Correcciones y anulaciones actualizan la misma fila y dejan su auditoría; nunca hay borrado físico
 *   y una anulación no se revierte desde el celular.
 * - `version_movil` impide que un reenvío atrasado pise una versión más reciente.
 */
final class SincronizarEntregaMovilUseCase
{
    public const string CREADA = 'creada';

    public const string ACTUALIZADA = 'actualizada';

    public const string SIN_CAMBIOS = 'sin_cambios';

    /**
     * @param  array{id: string, jornadaId: string, jornadaAbiertaEn: int, jornadaCerradaEn: int|null, proveedorCodigo: string, zonaNombre: string, vehiculoPlaca: string, acopiadorUsername: string, registradoEn: int, litros: float|int, tachos: int, observaciones: string|null, anulada: bool, motivo: string|null, actualizadoEn: int}  $datos
     * @return array{estado: string, entrega: Entrega}
     */
    public function ejecutar(Usuario $autor, array $datos): array
    {
        if ($autor->username !== $datos['acopiadorUsername'] && ! $autor->tieneRol('admin')) {
            throw EntregaMovilRechazadaException::autorDistinto($datos['acopiadorUsername']);
        }

        $proveedor = Proveedor::query()->where('codigo', $datos['proveedorCodigo'])->first()
            ?? throw EntregaMovilRechazadaException::noExiste('El proveedor', $datos['proveedorCodigo']);
        $zona = Zona::query()->where('nombre', $datos['zonaNombre'])->first()
            ?? throw EntregaMovilRechazadaException::noExiste('La zona', $datos['zonaNombre']);
        $vehiculo = Vehiculo::query()->where('placa', $datos['vehiculoPlaca'])->first()
            ?? throw EntregaMovilRechazadaException::noExiste('El vehículo', $datos['vehiculoPlaca']);
        // El acopiador de la jornada debe existir con el mismo usuario: nunca se asigna a quien envía.
        $acopiador = Usuario::query()->where('username', $datos['acopiadorUsername'])->first()
            ?? throw EntregaMovilRechazadaException::noExiste('El acopiador', $datos['acopiadorUsername']);

        return DB::transaction(function () use ($autor, $acopiador, $datos, $proveedor, $zona, $vehiculo): array {
            $jornada = $this->jornada($acopiador, $datos, $zona, $vehiculo);
            $existente = Entrega::query()->where('uuid_movil', $datos['id'])->lockForUpdate()->first();

            if ($existente === null) {
                return ['estado' => self::CREADA, 'entrega' => $this->crear($autor, $datos, $jornada, $proveedor, $zona, $vehiculo)];
            }

            return $this->actualizar($autor, $datos, $existente, $proveedor);
        });
    }

    /** @param  array<string, mixed>  $datos */
    private function jornada(Usuario $acopiador, array $datos, Zona $zona, Vehiculo $vehiculo): Jornada
    {
        $jornada = Jornada::query()->where('uuid_movil', $datos['jornadaId'])->lockForUpdate()->first();
        $cerradaEn = $datos['jornadaCerradaEn'] !== null ? $this->instante($datos['jornadaCerradaEn']) : null;

        if ($jornada === null) {
            $abiertaEn = $this->instante($datos['jornadaAbiertaEn']);

            return Jornada::query()->create([
                'uuid_movil' => $datos['jornadaId'],
                'usuario_id' => $acopiador->id,
                'zona_id' => $zona->id,
                'vehiculo_id' => $vehiculo->id,
                // Fecha de acopio en Perú, igual que en el celular.
                'fecha' => $abiertaEn->copy()->setTimezone('America/Lima')->toDateString(),
                'abierta_en' => $abiertaEn,
                'cerrada_en' => $cerradaEn,
            ]);
        }

        if ($jornada->cerrada_en === null && $cerradaEn !== null) {
            $jornada->update(['cerrada_en' => $cerradaEn]);
        }

        return $jornada;
    }

    /** @param  array<string, mixed>  $datos */
    private function crear(Usuario $autor, array $datos, Jornada $jornada, Proveedor $proveedor, Zona $zona, Vehiculo $vehiculo): Entrega
    {
        $entrega = Entrega::query()->create([
            'uuid_movil' => $datos['id'],
            'version_movil' => $datos['actualizadoEn'],
            'jornada_id' => $jornada->id,
            'proveedor_id' => $proveedor->id,
            'usuario_id' => $jornada->usuario_id,
            'zona_id' => $zona->id,
            'vehiculo_id' => $vehiculo->id,
            'litros' => $datos['litros'],
            'tachos' => $datos['tachos'],
            'observaciones' => $datos['observaciones'],
            'registrado_en' => $this->instante($datos['registradoEn']),
            'anulada' => $datos['anulada'],
        ]);

        $this->auditar($entrega, $autor, AccionAuditoria::Crear, null, $this->valores($entrega), 'Registrada en la app móvil.');

        if ($datos['anulada']) {
            // Se registró y se anuló antes de poder enviarse: queda el alta y la anulación.
            $this->auditar($entrega, $autor, AccionAuditoria::Anular, null, null, $datos['motivo'] ?: 'Anulada en la app móvil.');
        }

        return $entrega;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array{estado: string, entrega: Entrega}
     */
    private function actualizar(Usuario $autor, array $datos, Entrega $entrega, Proveedor $proveedor): array
    {
        if ($entrega->proveedor_id !== $proveedor->id) {
            throw EntregaMovilRechazadaException::cambioDeProveedor();
        }

        $versionGuardada = (int) $entrega->version_movil;

        if ($datos['actualizadoEn'] < $versionGuardada) {
            throw EntregaMovilRechazadaException::versionObsoleta();
        }

        if ($datos['actualizadoEn'] === $versionGuardada) {
            return ['estado' => self::SIN_CAMBIOS, 'entrega' => $entrega];
        }

        if ($entrega->anulada && ! $datos['anulada']) {
            throw EntregaMovilRechazadaException::reactivacion();
        }

        $antes = $this->valores($entrega);
        $cambiaronLitros = abs((float) $entrega->litros - (float) $datos['litros']) >= 0.005 || $entrega->tachos !== $datos['tachos'];
        $seAnula = ! $entrega->anulada && $datos['anulada'];

        $entrega->update([
            'litros' => $datos['litros'],
            'tachos' => $datos['tachos'],
            'observaciones' => $datos['observaciones'],
            'anulada' => $datos['anulada'],
            'version_movil' => $datos['actualizadoEn'],
        ]);

        if ($cambiaronLitros) {
            $this->auditar($entrega, $autor, AccionAuditoria::Corregir, $antes, $this->valores($entrega), $datos['motivo'] ?: 'Corrección registrada en la app móvil.');
        }

        if ($seAnula) {
            $this->auditar($entrega, $autor, AccionAuditoria::Anular, null, null, $datos['motivo'] ?: 'Anulada en la app móvil.');
        }

        return ['estado' => self::ACTUALIZADA, 'entrega' => $entrega];
    }

    private function auditar(Entrega $entrega, Usuario $autor, AccionAuditoria $accion, ?string $antes, ?string $despues, string $motivo): void
    {
        Auditoria::query()->create([
            'entidad' => 'entrega',
            'entidad_id' => $entrega->id,
            'accion' => $accion->value,
            'valor_antes' => $antes,
            'valor_despues' => $despues,
            'motivo' => $motivo,
            'usuario_id' => $autor->id,
            'ocurrido_en' => now(),
        ]);
    }

    private function valores(Entrega $entrega): string
    {
        return 'litros='.(float) $entrega->litros.';tachos='.$entrega->tachos;
    }

    private function instante(int $epochMs): Carbon
    {
        return Carbon::createFromTimestampMs($epochMs)->setTimezone(config('app.timezone'));
    }
}
