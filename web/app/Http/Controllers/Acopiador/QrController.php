<?php

namespace App\Http\Controllers\Acopiador;

use App\Application\Proveedores\EscanearQrProveedorUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrController extends Controller
{
    public function resolver(Request $request, EscanearQrProveedorUseCase $escanear): JsonResponse
    {
        $request->validate(['contenido' => ['required', 'string']]);

        $resultado = $escanear->ejecutar($request->string('contenido')->toString());

        if ($resultado->proveedor === null) {
            return response()->json(['estado' => $resultado->estado], 404);
        }

        return response()->json([
            'estado' => $resultado->estado,
            'proveedor' => [
                'id' => $resultado->proveedor->id,
                'codigo' => $resultado->proveedor->codigo,
                'nombres' => $resultado->proveedor->nombres,
                'zona_id' => $resultado->proveedor->zonaId,
            ],
        ]);
    }
}
