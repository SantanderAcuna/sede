<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Retención del registro de consentimientos
    |--------------------------------------------------------------------------
    |
    | Días que se conserva cada registro antes de que `consentimientos:depurar` lo
    | borre. El registro es la prueba de qué consintió un navegador, así que no
    | debe borrarse antes de que deje de poder reclamarse; pero tampoco conservarse
    | sin límite (Ley 1581 de 2012, principio de finalidad).
    |
    | El valor por defecto —tres años— es una PROPUESTA de TIC, no una decisión
    | jurídica: debe confirmarlo Jurídica / el Oficial de Protección de Datos
    | (T-20) y fijarse con `CONSENTIMIENTOS_RETENCION_DIAS`.
    */
    'retencion_dias' => (int) env('CONSENTIMIENTOS_RETENCION_DIAS', 1095),
];
