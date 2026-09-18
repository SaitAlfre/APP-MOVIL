<?php

namespace App\Http\Controllers\Admin\Produccion;

use App\Domain\Inventario\InsumoRepositoryInterface;
use App\Domain\Productos\ProductoRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request, InsumoRepositoryInterface $insumos, ProductoRepositoryInterface $productos): View
    {
        $tab = $request->string('tab')->toString() === 'productos' ? 'productos' : 'insumos';

        return view('admin.produccion.inventario.index', [
            'tab' => $tab,
            'insumos' => $tab === 'insumos' ? $insumos->paginar(20) : null,
            'productos' => $tab === 'productos' ? $productos->paginar(20) : null,
        ]);
    }
}
