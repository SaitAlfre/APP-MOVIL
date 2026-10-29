<?php

namespace App\Domain\Jornadas;

use App\Domain\Recepcion\RecepcionAcopio;

/**
 * Etapa operativa de una jornada vista por administración: sale a la ruta, termina y la planta
 * vuelve a medir la leche. La diferencia solo sirve para controlar la ruta; al proveedor se le
 * paga lo registrado en campo.
 */
enum EtapaJornada: string
{
    case EnRuta = 'en_ruta';
    case PorRecibir = 'por_recibir';
    case RecibidaConDiferencia = 'con_diferencia';
    case RecibidaSinDiferencia = 'sin_diferencia';

    public static function de(bool $abierta, ?RecepcionAcopio $recepcion): self
    {
        if ($recepcion !== null) {
            return $recepcion->diferencia() === 0.0 ? self::RecibidaSinDiferencia : self::RecibidaConDiferencia;
        }

        return $abierta ? self::EnRuta : self::PorRecibir;
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::EnRuta => 'En ruta',
            self::PorRecibir => 'Por recibir en planta',
            self::RecibidaConDiferencia => 'Recibida con diferencia',
            self::RecibidaSinDiferencia => 'Recibida sin diferencia',
        };
    }

    /** Variante del componente `x-ui.badge`. */
    public function variante(): string
    {
        return match ($this) {
            self::EnRuta => 'blue',
            self::PorRecibir => 'yellow',
            self::RecibidaConDiferencia => 'red',
            self::RecibidaSinDiferencia => 'green',
        };
    }

    public function siguienteAccion(): string
    {
        return match ($this) {
            self::EnRuta => 'Ver recojos',
            self::PorRecibir => 'Registrar llegada',
            self::RecibidaConDiferencia => 'Revisar diferencia',
            self::RecibidaSinDiferencia => 'Ver detalle',
        };
    }

    /** Paso alcanzado en el recorrido En ruta → Terminada → Recibida en planta (1 a 3). */
    public function paso(): int
    {
        return match ($this) {
            self::EnRuta => 1,
            self::PorRecibir => 2,
            self::RecibidaConDiferencia, self::RecibidaSinDiferencia => 3,
        };
    }
}
