<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OFICIALES = ['admin', 'acopiador', 'calidad', 'proveedor'];

    public function up(): void
    {
        if (! Schema::hasTable('archivo_roles_usuarios')) {
            Schema::create('archivo_roles_usuarios', function (Blueprint $table) {
                $table->unsignedBigInteger('usuario_id')->primary();
                $table->text('roles_antes');
                $table->text('roles_despues');
                $table->text('roles_retirados');
                $table->timestamp('capturado_en');
            });
        }
        DB::transaction(function () {
            DB::table('usuarios')->orderBy('id')->chunkById(100, function ($usuarios) {
                foreach ($usuarios as $usuario) {
                    $roles = json_decode($usuario->roles ?? '[]', true, flags: JSON_THROW_ON_ERROR);
                    $oficiales = array_values(array_intersect($roles, self::OFICIALES));
                    $retirados = array_values(array_diff($roles, self::OFICIALES));
                    if ($retirados === []) {
                        continue;
                    }
                    DB::table('archivo_roles_usuarios')->insertOrIgnore([
                        'usuario_id' => $usuario->id, 'roles_antes' => json_encode($roles, JSON_THROW_ON_ERROR),
                        'roles_despues' => json_encode($oficiales, JSON_THROW_ON_ERROR),
                        'roles_retirados' => json_encode($retirados, JSON_THROW_ON_ERROR), 'capturado_en' => now(),
                    ]);
                    DB::table('usuarios')->where('id', $usuario->id)->update(['roles' => json_encode($oficiales, JSON_THROW_ON_ERROR)]);
                    $this->revocarSesiones($usuario->id);
                }
            });
        });
    }

    public function down(): void
    {
        DB::transaction(function () {
            $archivos = DB::table('archivo_roles_usuarios')->orderBy('usuario_id')->get();
            foreach ($archivos as $archivo) {
                $actuales = DB::table('usuarios')->where('id', $archivo->usuario_id)->value('roles');
                if ($actuales !== null && json_decode($actuales, true, flags: JSON_THROW_ON_ERROR) !== json_decode($archivo->roles_despues, true, flags: JSON_THROW_ON_ERROR)) {
                    throw new RuntimeException('No se revierte automáticamente: existen asignaciones posteriores. Revisa el archivo de roles y conserva los cambios actuales.');
                }
            }
            foreach ($archivos as $archivo) {
                DB::table('usuarios')->where('id', $archivo->usuario_id)->update(['roles' => $archivo->roles_antes]);
                $this->revocarSesiones($archivo->usuario_id);
            }
        });
        // El archivo se conserva para trazabilidad y recuperación; ninguna sesión antigua revive.
    }

    private function revocarSesiones(int $usuarioId): void
    {
        DB::table('usuarios')->where('id', $usuarioId)->increment('version_sesion');
        DB::table('tokens_movil')->where('usuario_id', $usuarioId)->delete();
        DB::table('sessions')->where('user_id', $usuarioId)->delete();
    }
};
