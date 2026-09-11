<?php

namespace App\Http\Requests\Admin;

class UpdateProveedorRequest extends StoreProveedorRequest
{
    // Mismas reglas que el registro; el use case valida duplicados excluyendo al propio proveedor.
}
