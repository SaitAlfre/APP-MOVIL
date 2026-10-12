<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Token de la app móvil. Solo se guarda su hash: el valor en claro se entrega una sola vez al celular. */
class TokenMovil extends Model
{
    public const int MINUTOS_VIGENCIA = 15;

    public const int DIAS_REFRESH = 30;

    protected $table = 'tokens_movil';

    protected $fillable = ['usuario_id', 'token_hash', 'dispositivo', 'ultimo_uso_en', 'expira_en', 'refresh_expira_en'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'ultimo_uso_en' => 'datetime',
            'expira_en' => 'datetime',
            'refresh_expira_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /** @return array{0: self, 1: string, 2: string} Sesión, access token y refresh token en claro. */
    public static function emitir(Usuario $usuario, ?string $dispositivo): array
    {
        return DB::transaction(function () use ($usuario, $dispositivo): array {
            $token = Str::random(64);
            $registro = self::query()->create([
                'usuario_id' => $usuario->id,
                'token_hash' => self::hash($token),
                'dispositivo' => $dispositivo,
                'expira_en' => now()->addMinutes(self::MINUTOS_VIGENCIA),
                'refresh_expira_en' => now()->addDays(self::DIAS_REFRESH),
            ]);

            return [$registro, $token, $registro->emitirRefresh()];
        });
    }

    /**
     * Conserva los hashes usados hasta el cierre de la sesión para detectar reutilización.
     * La rotación no extiende la fecha límite original de la sesión.
     *
     * @return array{0: self, 1: string, 2: string}|null
     */
    public static function renovar(string $refreshToken): ?array
    {
        return DB::transaction(function () use ($refreshToken): ?array {
            $hash = self::hash($refreshToken);
            $refresh = DB::table('refresh_tokens_movil')->where('token_hash', $hash)->first();
            if ($refresh === null) {
                return null;
            }
            $registro = self::query()->whereKey($refresh->token_movil_id)->lockForUpdate()->first();
            if ($registro === null) {
                return null;
            }
            $refresh = DB::table('refresh_tokens_movil')->where('token_hash', $hash)->first();
            if ($refresh === null) {
                return null;
            }
            $usuario = $registro->usuario;
            if ($refresh->usado_en !== null || $registro->refresh_expira_en === null
                || $registro->refresh_expira_en->lte(now()) || $usuario === null
                || ! $usuario->activo || $usuario->estaBloqueada()) {
                $registro->delete();

                return null;
            }
            $consumido = DB::table('refresh_tokens_movil')->where('id', $refresh->id)
                ->whereNull('usado_en')->update(['usado_en' => now()]);
            if ($consumido !== 1) {
                $registro->delete();

                return null;
            }
            $token = Str::random(64);
            $registro->forceFill([
                'token_hash' => self::hash($token),
                'expira_en' => now()->addMinutes(self::MINUTOS_VIGENCIA)->min($registro->refresh_expira_en),
                'ultimo_uso_en' => now(),
            ])->save();

            return [$registro, $token, $registro->emitirRefresh()];
        }, 3);
    }

    public static function revocar(?string $accessToken, ?string $refreshToken): bool
    {
        return DB::transaction(function () use ($accessToken, $refreshToken): bool {
            $registro = null;
            if ($refreshToken !== null) {
                $refresh = DB::table('refresh_tokens_movil')->where('token_hash', self::hash($refreshToken))
                    ->whereNull('usado_en')->first();
                if ($refresh !== null) {
                    $registro = self::query()->whereKey($refresh->token_movil_id)
                        ->where('refresh_expira_en', '>', now())->lockForUpdate()->first();
                }
            } elseif ($accessToken !== null) {
                $registro = self::query()->where('token_hash', self::hash($accessToken))
                    ->where('expira_en', '>', now())->lockForUpdate()->first();
            }
            if ($registro === null) {
                return false;
            }
            $registro->delete();

            return true;
        }, 3);
    }

    private function emitirRefresh(): string
    {
        $refreshToken = Str::random(64);
        DB::table('refresh_tokens_movil')->insert([
            'token_movil_id' => $this->id,
            'token_hash' => self::hash($refreshToken),
            'created_at' => now(),
        ]);

        return $refreshToken;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
