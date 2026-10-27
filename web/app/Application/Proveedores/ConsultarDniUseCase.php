<?php

namespace App\Application\Proveedores;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

final class ConsultarDniUseCase
{
    /** @return array{estado: string, mensaje?: string, nombre?: string} */
    public function ejecutar(string $dni): array
    {
        $token = trim((string) config('services.decolecta.token'));
        if ($token === '') {
            return ['estado' => 'no_disponible', 'mensaje' => 'La consulta de DNI no está disponible. Completa los datos manualmente.'];
        }

        try {
            $respuesta = Http::acceptJson()->withToken($token)->connectTimeout(3)->timeout(7)
                ->withoutRedirecting()->get('https://api.decolecta.com/v1/reniec/dni', ['numero' => $dni]);
        } catch (ConnectionException) {
            return ['estado' => 'no_disponible', 'mensaje' => 'El servicio no respondió. Puedes reintentar o completar los datos manualmente.'];
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
