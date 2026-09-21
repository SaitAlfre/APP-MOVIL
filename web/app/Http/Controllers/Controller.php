<?php

namespace App\Http\Controllers;

use Illuminate\Database\QueryException;

abstract class Controller
{
    protected function mensajeSeguro(\Throwable $error): string
    {
        if ($error instanceof QueryException) {
            report($error);

            return 'No se pudo guardar la operación. Inténtalo nuevamente o contacta al administrador.';
        }

        return $error->getMessage();
    }
}
