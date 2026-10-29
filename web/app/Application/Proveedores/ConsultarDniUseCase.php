<?php

namespace App\Application\Proveedores;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Consulta externa de RENIEC mediante Decolecta. Solo se usa cuando el DNI no existe en los registros internos. */
final class ConsultarDniUseCase
{
    public const MENSAJE_SIN_CONFIGURAR = 'La consulta externa de DNI (RENIEC) requiere que el administrador del sistema configure el servicio. Puedes completar los datos manualmente.';

    public function configurado(): bool
    {
        return trim((string) config('services.decolecta.token')) !== '';
    }

    /** @return array{estado: string, mensaje?: string, nombre?: string} */
    public function ejecutar(string $dni): array
    {
        $token = trim((string) config('services.decolecta.token'));
        if ($token === '') {
            return ['estado' => 'no_configurado', 'mensaje' => self::MENSAJE_SIN_CONFIGURAR];
        }

        try {
            $respuesta = Http::acceptJson()->withToken($token)->connectTimeout(3)->timeout(7)
                ->withoutRedirecting()->get('https://api.decolecta.com/v1/reniec/dni', ['numero' => $dni]);
        } catch (ConnectionException) {
            return ['estado' => 'no_disponible', 'mensaje' => 'El servicio no respondió. Puedes reintentar o completar los datos manualmente.'];
        }

        if (in_array($respuesta->status(), [401, 403], true)) {
            // Decolecta responde 401 «Apikey Required / Limit Exceeded» tanto por credencial inválida como por límite agotado.
            Log::warning('Consulta DNI rechazada por el servicio externo', ['estado' => $respuesta->status()]);

            return ['estado' => 'no_autorizado', 'mensaje' => 'El servicio de DNI rechazó la credencial configurada o se agotó su límite de consultas. Un administrador debe revisar la cuenta del servicio. Puedes completar los datos manualmente.'];
        }
        if ($respuesta->status() === 429) {
            return ['estado' => 'limite', 'mensaje' => 'El servicio de DNI alcanzó su límite de consultas. Reintenta más tarde o completa los datos manualmente.'];
        }
        if ($respuesta->status() === 404) {
            return ['estado' => 'sin_resultado', 'mensaje' => 'No se encontraron datos para este DNI. Verifica el número o completa los datos manualmente.'];
        }
        if (! $respuesta->successful()) {
            return ['estado' => 'no_disponible', 'mensaje' => 'No se pudo consultar el DNI. Puedes completar los datos manualmente.'];
        }

        $datos = $respuesta->json();
        if (! is_array($datos) || ($datos['document_number'] ?? null) !== $dni) {
            return ['estado' => 'no_disponible', 'mensaje' => 'No se pudo confirmar la respuesta para este DNI. Completa los datos manualmente.'];
        }
        $partes = [];
        foreach (['first_name', 'first_last_name', 'second_last_name'] as $campo) {
            if (! isset($datos[$campo]) || ! is_string($datos[$campo])) {
                return ['estado' => 'no_disponible', 'mensaje' => 'La respuesta está incompleta. Completa los datos manualmente.'];
            }
            $partes[] = trim($datos[$campo]);
        }
        $nombre = trim(implode(' ', array_filter($partes)));
        if ($partes[0] === '' || $partes[1] === '' || mb_strlen($nombre) > 150) {
            return ['estado' => 'no_disponible', 'mensaje' => 'La respuesta está incompleta. Completa los datos manualmente.'];
        }

        return ['estado' => 'encontrado', 'nombre' => $nombre];
    }
}
