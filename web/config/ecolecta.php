<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Umbral de alerta por merma de transporte
    |--------------------------------------------------------------------------
    |
    | Porcentaje de merma (litros recolectados vs. litros medidos en planta)
    | a partir del cual un vehículo se marca como alerta en el dashboard
    | operativo. No es una regla de negocio: es solo el punto de corte visual
    | para llamar la atención del administrador.
    |
    */
    'umbral_merma_porcentaje' => (float) env('ECOLECTA_UMBRAL_MERMA_PORCENTAJE', 8.0),

];
