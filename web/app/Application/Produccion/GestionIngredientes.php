<?php

namespace App\Application\Produccion;

use App\Infrastructure\Persistence\Eloquent\LoteProduccion;
use App\Infrastructure\Persistence\Eloquent\Material;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class GestionIngredientes
{
    /** Cantidades por una unidad del producto, expresadas en la unidad del material. */
    public function receta(int $productoId): array
    {
        return DB::table('receta_ingredientes as receta')
            ->join('materiales as material', 'material.id', '=', 'receta.material_id')
            ->where('receta.producto_id', $productoId)->orderBy('material.id')
            ->get(['receta.material_id', 'material.nombre', 'material.unidad', 'receta.cantidad', 'material.existencia'])
            ->map(fn ($fila) => (array) $fila)->all();
    }

    public function guardar(int $productoId, array $ingredientes): void
    {
        DB::table('receta_ingredientes')->where('producto_id', $productoId)->delete();
        foreach ($ingredientes as $ingrediente) {
            DB::table('receta_ingredientes')->insert([
                'producto_id' => $productoId,
                'material_id' => $ingrediente['material_id'],
                'cantidad' => $ingrediente['cantidad'],
            ]);
        }
    }

    public function calcular(int $productoId, float $factor): array
    {
        return array_map(function (array $ingrediente) use ($factor): array {
            $ingrediente['cantidad'] = round((float) $ingrediente['cantidad'] * $factor, 3);
            $ingrediente['faltante'] = max(0, round($ingrediente['cantidad'] - (float) $ingrediente['existencia'], 3));

            return $ingrediente;
        }, $this->receta($productoId));
    }

    /** Se ejecuta dentro de la transacción del lote, bloqueando materiales en orden fijo. */
    public function comprobar(array $ingredientes): void
    {
        $materiales = Material::whereIn('id', array_column($ingredientes, 'material_id'))
            ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $faltantes = [];
        foreach ($ingredientes as $ingrediente) {
            $material = $materiales->get($ingrediente['material_id']);
            $faltante = round($ingrediente['cantidad'] - (float) ($material?->existencia ?? 0), 3);
            if ($material === null || $faltante > 0) {
                $faltantes[] = $ingrediente['nombre'].': faltan '.number_format($faltante, 3).' '.$ingrediente['unidad'];
            }
        }
        if ($faltantes !== []) {
            throw ValidationException::withMessages(['ingredientes' => 'Stock insuficiente. '.implode('; ', $faltantes).'. Registra una entrada en Inventario.']);
        }
    }

    public function consumir(LoteProduccion $lote, int $usuarioId): void
    {
        $ingredientes = $lote->ingredientes_snapshot ?? [];
        $this->comprobar($ingredientes);
        foreach ($ingredientes as $ingrediente) {
            Material::whereKey($ingrediente['material_id'])->decrement('existencia', $ingrediente['cantidad']);
            DB::table('movimientos_material')->insert([
                'material_id' => $ingrediente['material_id'], 'lote_id' => $lote->id,
                'usuario_id' => $usuarioId, 'tipo' => 'consumo', 'cantidad' => -$ingrediente['cantidad'],
                'motivo' => 'Inicio del lote '.$lote->codigo, 'fecha' => now(),
            ]);
        }
    }
}
