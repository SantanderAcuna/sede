<?php

declare(strict_types=1);

return [
    /*
     | Cuántas valoraciones de fichas admite una misma IP por minuto. Es una escritura abierta a
     | internet, así que el cupo es ajustado; se lee dentro del limitador para que una prueba pueda
     | bajarlo con `config([...])` sin reiniciar la aplicación.
     */
    'limite_por_minuto' => (int) env('VALORACIONES_LIMITE_POR_MINUTO', 10),
];
