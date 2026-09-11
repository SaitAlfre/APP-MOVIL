<?php

namespace App\Infrastructure\Persistence\Eloquent;

use App\Domain\Proveedores\EstadoProveedor;
use Database\Factories\ProveedorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proveedor extends Model
{
    use HasFactory;

    protected static function newFactory(): ProveedorFactory
    {
        return ProveedorFactory::new();
    }

    protected $table = 'proveedores';

    protected $fillable = [
        'codigo',
        'nombres',
        'dni',
        'telefono',
        'direccion',
        'zona_id',
        'tachos',
        'capacidad_tacho_l',
        'estado',
        'creado_por_usuario_id',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoProveedor::class,
            'capacidad_tacho_l' => 'decimal:2',
        ];
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por_usuario_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
