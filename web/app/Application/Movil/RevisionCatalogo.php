<?php

namespace App\Application\Movil;

use App\Domain\Movil\CambioMovilRechazadoException;
use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

/** Control de concurrencia exclusivo de los cuatro catálogos Coder 1. */
final class RevisionCatalogo
{
    public static function de(Model $registro): string
    {
        $campos = match (true) {
            $registro instanceof Zona => ['nombre', 'activo'],
            $registro instanceof Vehiculo => ['nombre', 'placa', 'activo'],
            $registro instanceof Usuario => ['username', 'nombres', 'dni', 'roles', 'activo', 'zona_id', 'version_sesion', 'bloqueado_manualmente', 'bloqueado_hasta'],
            $registro instanceof Proveedor => ['codigo', 'nombres', 'dni', 'telefono', 'direccion', 'zona_id', 'tachos', 'capacidad_tacho_l', 'estado', 'usuario_id', 'dueno'],
            default => throw new \InvalidArgumentException('Entidad fuera de Coder 1.'),
        };
        $datos = ['id' => $registro->getKey()];
        foreach ($campos as $campo) {
            $valor = $registro->getAttribute($campo);
            if (is_array($valor)) {
                sort($valor);
            }
            $datos[$campo] = $valor instanceof \BackedEnum ? $valor->value : $valor;
        }

        return hash('sha256', json_encode($datos, JSON_THROW_ON_ERROR));
    }

    public static function confirmada(string $entidad, ?int $id): ?string
    {
        $modelo = match ($entidad) {
            'zona' => Zona::class, 'vehiculo' => Vehiculo::class,
            'usuario' => Usuario::class, 'proveedor' => Proveedor::class,
            default => null,
        };

        return $modelo !== null && $id !== null ? self::de($modelo::query()->findOrFail($id)) : null;
    }

    /** null permite aplicar; id confirma un reintento cuyo contenido ya coincide sin volver a escribir. */
    public function comprobar(string $entidad, array $datos): ?int
    {
        $modelo = match ($entidad) {
            'zona' => Zona::class, 'vehiculo' => Vehiculo::class,
            'usuario' => Usuario::class, 'proveedor' => Proveedor::class,
        };
        $clave = match ($entidad) {
            'zona' => 'nombre', 'vehiculo' => 'placa', 'usuario' => 'username', 'proveedor' => 'codigo'
        };
        $query = $modelo::query()->lockForUpdate();
        $registro = isset($datos['servidorId']) ? $query->find($datos['servidorId'])
            : $query->whereRaw('UPPER('.$clave.') = ?', [mb_strtoupper(trim($datos[$clave]))])->first();
        if ($registro === null && ! isset($datos['servidorId']) && ! isset($datos['revisionBase'])) {
            return null;
        }
        if ($registro !== null) {
            if (isset($datos['revisionBase']) && hash_equals(self::de($registro), $datos['revisionBase'])) {
                return null;
            }
            if ($this->coincide($entidad, $registro, $datos)) {
                return $registro->id;
            }
        }
        throw new CambioMovilRechazadoException(
            'El catálogo cambió en el panel o falta su revisión inicial. Se conserva tu edición en el teléfono. Revisa ambas versiones antes de volver a enviarla.',
            'catalogo_conflicto', 409,
        );
    }

    private function coincide(string $entidad, Model $registro, array $datos): bool
    {
        $valores = match ($entidad) {
            'zona' => ['nombre' => trim($datos['nombre']), 'activo' => (bool) $datos['activo']],
            'vehiculo' => ['nombre' => trim($datos['nombre']), 'placa' => mb_strtoupper(trim($datos['placa'])), 'activo' => (bool) $datos['activo']],
            'usuario' => ['username' => $datos['username'], 'nombres' => trim($datos['nombres']), 'dni' => trim($datos['dni']), 'activo' => (bool) $datos['activo']],
            'proveedor' => ['codigo' => trim($datos['codigo']), 'nombres' => trim($datos['nombres']), 'dni' => trim($datos['dni']),
                'telefono' => $this->limpiar($datos['telefono'] ?? null), 'direccion' => $this->limpiar($datos['direccion'] ?? null),
                'tachos' => (int) $datos['tachos'], 'capacidad_tacho_l' => (float) $datos['capacidadTachoL']],
        };
        foreach ($valores as $campo => $valor) {
            $actual = $registro->getAttribute($campo);
            if (is_float($valor) ? (float) $actual !== $valor : $actual !== $valor) {
                return false;
            }
        }
        if ($entidad === 'usuario') {
            $roles = array_map('strtolower', $datos['roles']);
            $actuales = $registro->roles;
            sort($roles);
            sort($actuales);

            return $roles === $actuales
                && (! ($datos['asignacionZonaIncluida'] ?? false) || $this->mismaZona($registro->zona_id, $datos))
                && (! isset($datos['pin']) || Hash::check($datos['pin'], $registro->pin_hash));
        }
        if ($entidad === 'proveedor') {
            if (array_key_exists('dueno', $datos) && $registro->dueno !== $this->limpiar($datos['dueno'])) {
                return false;
            }
            $username = $registro->usuario_id === null ? null : Usuario::query()->whereKey($registro->usuario_id)->value('username');

            return $this->mismaZona($registro->zona_id, $datos)
                && $registro->estado->value === strtolower($datos['estado']) && $username === ($datos['usuarioUsername'] ?? null);
        }

        return true;
    }

    private function mismaZona(?int $actual, array $datos): bool
    {
        $id = $datos['zonaId'] ?? (isset($datos['zonaNombre']) ? Zona::query()->where('nombre', $datos['zonaNombre'])->value('id') : null);

        return $actual === ($id === null ? null : (int) $id);
    }

    private function limpiar(?string $valor): ?string
    {
        return $valor === null || trim($valor) === '' ? null : trim($valor);
    }
}
