<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Búsqueda de la sede (T-22)
|--------------------------------------------------------------------------
|
| Los valores de relevancia salen de una calibración medida, no de una
| intuición: ver `docs/busqueda.md` §2 y `casos-de-prueba-t22-busqueda.md`
| (TC-BUS-24). Cambiar uno sin repetir esa calibración rompe el equilibrio entre
| tolerar erratas y no devolver ruido, y las pruebas del banco lo señalan.
|
*/

return [

    /*
    | Similitud mínima de palabra (`pg_trgm.word_similarity`) para que un contenido
    | entre en los resultados. Se fija por transacción: el valor global de la base
    | (0,6) no encontraría «licensia» (0,556) y cambiarlo afectaría a todo lo demás.
    | Meseta medida: de 0,45 a 0,50 con la cobertura de abajo (docs/busqueda.md §2.1).
    */
    'umbral' => 0.45,

    /*
    | Cada palabra de la consulta (de `palabra_minima` letras o más) tiene que
    | aparecer en el contenido, con esta similitud como mínimo. Es lo que impide
    | que «licencia de conducción» encuentre «licencia de exhumación»: sin esta
    | exigencia ninguna cifra de `umbral` separaba ese ruido de un acierto legítimo.
    |
    | 0,40 desde la medición sintética (docs/busqueda.md §2.3): con 0,45 se perdían
    | erratas reales en palabras largas; con 0,35 aparece ruido en el banco negativo.
    */
    'cobertura_palabra' => 0.40,

    /* Las palabras más cortas (de, y, la, el…) no se exigen: no distinguen nada. */
    'palabra_minima' => 4,

    /* Cuántas palabras de la consulta se exigen como máximo; el resto se ignora. */
    'palabras_maximas' => 6,

    /* Largo máximo del extracto de cada resultado, recortado sin partir palabras. */
    'extracto_maximo' => 200,

    /*
    | Peticiones por minuto y por IP, compartidas por las dos operaciones. Una
    | persona que escribe con normalidad (una petición por cada pausa de 250 ms)
    | queda muy por debajo; una oficina entera que comparte salida también. La IP
    | que cuenta es la real del cliente: nginx la restituye desde Cloudflare.
    */
    'limite_por_minuto' => (int) env('BUSQUEDA_LIMITE_POR_MINUTO', 120),

];
