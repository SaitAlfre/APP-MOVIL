<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\Eloquent\Proveedor;
use App\Infrastructure\Persistence\Eloquent\Usuario;
use App\Infrastructure\Persistence\Eloquent\Vehiculo;
use App\Infrastructure\Persistence\Eloquent\Zona;
use Illuminate\Database\Seeder;

/**
 * Registra en el panel las mismas zonas, camiones y cuentas de prueba que siembra la app móvil
 * (CREDENCIALES_PRUEBA_MOVIL.md), para que las entregas enviadas desde el celular se puedan asociar:
 * el servidor las identifica por nombre de zona, placa, usuario y código de proveedor.
 *
 * Solo agrega lo que falta (nunca modifica ni borra). No se ejecuta con `db:seed` por defecto:
 * `php artisan db:seed --class=MovilDemoSeeder`.
 */
class MovilDemoSeeder extends Seeder
{
    public function run(): void
    {
        $zonas = [
            'FAON-MARKAPAJO' => 'faon',
            'MORO VIEJO-PANCHA' => 'moro',
            'COLLANA I-YASIN-HUAN' => 'collana',
            'PLANTA-COLLANA II' => 'planta',
        ];
        $numeroZona = 0;

        foreach ($zonas as $nombre => $clave) {
            $numeroZona++;
            $zona = Zona::query()->firstOrCreate(['nombre' => $nombre], ['activo' => true]);

            Usuario::query()->firstOrCreate(['username' => "acop_{$clave}"], [
                'nombres' => "Acopiador {$nombre}", 'dni' => '30'.str_pad((string) $numeroZona, 6, '0', STR_PAD_LEFT),
                'pin_hash' => '2468', 'activo' => true, 'roles' => ['acopiador'],
            ]);

            foreach ([1, 2, 3] as $numero) {
                $sufijo = str_pad((string) $numero, 2, '0', STR_PAD_LEFT);
                $cuenta = Usuario::query()->firstOrCreate(['username' => "prov_{$clave}_{$sufijo}"], [
                    'nombres' => "Proveedor {$nombre} {$sufijo}", 'dni' => $numeroZona.str_pad((string) $numero, 7, '0', STR_PAD_LEFT),
                    'pin_hash' => '1234', 'activo' => true, 'roles' => ['proveedor'],
                ]);
                $codigo = 'PRV-'.strtoupper($clave)."-{$sufijo}";
                $dni = $numeroZona.str_pad((string) $numero, 7, '0', STR_PAD_LEFT);

                if (Proveedor::query()->where('codigo', $codigo)->exists()) {
                    continue;
                }

                if (Proveedor::query()->where('dni', $dni)->exists()) {
                    $this->command?->warn("Se omite {$codigo}: el DNI {$dni} ya pertenece a otro proveedor del panel.");

                    continue;
                }

                Proveedor::query()->create([
                    'codigo' => $codigo, 'nombres' => "Proveedor {$nombre} {$sufijo}", 'dni' => $dni,
                    'direccion' => $nombre, 'zona_id' => $zona->id, 'tachos' => 2, 'capacidad_tacho_l' => 40,
                    'estado' => 'activo', 'usuario_id' => $cuenta->id,
                ]);
            }
        }

        foreach (['V1A-123' => 'Camión 1', 'V2B-456' => 'Camión 2', 'V3C-789' => 'Camión 3', 'V4D-012' => 'Camión 4'] as $placa => $nombre) {
            Vehiculo::query()->firstOrCreate(['placa' => $placa], ['nombre' => $nombre, 'activo' => true]);
        }
    }
}
