<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Canal de sincronización con la app móvil. Solo aditiva: no modifica ni borra filas existentes.
 *
 * - `tokens_movil`: un token por usuario y dispositivo, obtenido con su propio usuario y PIN. Solo se
 *   guarda el hash SHA-256; el token en claro solo lo conoce el celular.
 * - `uuid_movil` en jornadas y entregas: id del registro en el celular. Es la clave idempotente: un
 *   reenvío actualiza la misma fila y nunca crea duplicados.
 * - `version_movil`: reloj lógico (updated_at del celular, ms) para no aplicar una versión más vieja.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens_movil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->string('dispositivo', 120)->nullable();
            $table->timestamp('ultimo_uso_en')->nullable();
            $table->timestamp('expira_en');
            $table->timestamps();
        });

        Schema::table('jornadas', function (Blueprint $table) {
            $table->string('uuid_movil', 64)->nullable()->unique();
        });

        Schema::table('entregas', function (Blueprint $table) {
            $table->string('uuid_movil', 64)->nullable()->unique();
            $table->unsignedBigInteger('version_movil')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('entregas', function (Blueprint $table) {
            $table->dropUnique(['uuid_movil']);
            $table->dropColumn(['uuid_movil', 'version_movil']);
        });

        Schema::table('jornadas', function (Blueprint $table) {
            $table->dropUnique(['uuid_movil']);
            $table->dropColumn('uuid_movil');
        });

        Schema::dropIfExists('tokens_movil');
    }
};
