<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Token de la app móvil. Solo se guarda su hash: el valor en claro se entrega una sola vez al celular. */
class TokenMovil extends Model
{
    public const int DIAS_VIGENCIA = 30;

    protected $table = 'tokens_movil';

    protected $fillable = ['usuario_id', 'token_hash', 'dispositivo', 'ultimo_uso_en', 'expira_en'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return [
            'ultimo_uso_en' => 'datetime',
            'expira_en' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /** @return array{0: self, 1: string} el registro y el token en claro */
    public static function emitir(Usuario $usuario, ?string $dispositivo): array
    {
        $token = Str::random(64);
        $registro = self::query()->create([
            'usuario_id' => $usuario->id,
            'token_hash' => self::hash($token),
            'dispositivo' => $dispositivo,
            'expira_en' => now()->addDays(self::DIAS_VIGENCIA),
        ]);

        return [$registro, $token];
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
