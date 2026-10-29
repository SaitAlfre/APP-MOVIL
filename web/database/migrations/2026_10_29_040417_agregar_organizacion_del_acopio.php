<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Organización del acopio: ubicación propia de cada proveedor (web o celular), tipo de vehículo
 * para el trazado por carretera y fecha efectiva de los traslados de zona (aviso de 3 días).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proveedores', function (Blueprint $table) {
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->string('ubicacion_origen', 10)->nullable();
            $table->foreignId('ubicacion_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->timestamp('ubicacion_en')->nullable();
        });
        Schema::table('vehiculos', function (Blueprint $table) {
            $table->string('tipo', 12)->default('camion');
        });
        Schema::table('rutas', function (Blueprint $table) {
            $table->string('tipo_vehiculo', 12)->default('camion');
        });
        Schema::table('solicitudes_traslado', function (Blueprint $table) {
            $table->date('fecha_efectiva')->nullable();
            $table->timestamp('aplicado_en')->nullable();
        });

        // Las paradas ya ubicadas en las rutas son la mejor fuente inicial de la ubicación del proveedor.
        DB::table('rutas')->whereNotNull('paradas')->orderBy('id')->get(['paradas'])->each(function ($ruta): void {
            foreach (json_decode($ruta->paradas, true) ?: [] as $parada) {
                if (is_numeric($parada['proveedor_id'] ?? null) && is_numeric($parada['latitud'] ?? null) && is_numeric($parada['longitud'] ?? null)) {
                    DB::table('proveedores')->where('id', (int) $parada['proveedor_id'])->whereNull('latitud')->update([
                        'latitud' => (float) $parada['latitud'], 'longitud' => (float) $parada['longitud'], 'ubicacion_origen' => 'web', 'ubicacion_en' => now(),
                    ]);
                }
            }
        });
        DB::table('solicitudes_traslado')->whereNull('fecha_efectiva')->update(['fecha_efectiva' => DB::raw('date(created_at)')]);
        DB::table('solicitudes_traslado')->where('estado', 'APROBADA')->update(['aplicado_en' => DB::raw('resuelto_en')]);
    }

    public function down(): void
    {
        Schema::table('solicitudes_traslado', fn (Blueprint $table) => $table->dropColumn(['fecha_efectiva', 'aplicado_en']));
        Schema::table('rutas', fn (Blueprint $table) => $table->dropColumn('tipo_vehiculo'));
        Schema::table('vehiculos', fn (Blueprint $table) => $table->dropColumn('tipo'));
        Schema::table('proveedores', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ubicacion_por');
            $table->dropColumn(['latitud', 'longitud', 'ubicacion_origen', 'ubicacion_en']);
        });
    }
};
