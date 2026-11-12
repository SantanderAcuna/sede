<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Lecturas públicas de la sede
|--------------------------------------------------------------------------
|
| Entidad, políticas, identidad (barra superior, pie y menú) y trámites: lo que
| el sitio pide a la API para servir una página a un visitante sin sesión.
|
*/

return [

    /*
    | Peticiones por minuto y por IP, compartidas por todas las lecturas públicas.
    |
    | HECHO (medido en staging): cada página servida gasta dos o tres lecturas. Con
    | el límite general de 60, eran 20 a 30 páginas por minuto para todos los que
    | salen por una misma IP —una oficina, una red móvil con direcciones compartidas—.
    |
    | 300 son 100 a 150 páginas por minuto por IP. Es una INFERENCIA, no una medición
    | con ciudadanos: queda por debajo de lo que nginx deja pasar por IP (10 por
    | segundo), así que sigue siendo un freno real, y se cambia sin tocar código. El
    | criterio para subirlo: que los 429 de estas rutas pasen del 1 % en producción.
    |
    | La IP que cuenta es la real del visitante: nginx la restituye desde Cloudflare
    | y el sitio la reenvía por el canal interno. El contador vive en la caché
    | (`CACHE_STORE`): con varias réplicas tiene que ser una caché compartida —Redis—,
    | o cada réplica llevaría su propia cuenta y el límite real se multiplicaría.
    */
    'limite_por_minuto' => (int) env('LECTURAS_LIMITE_POR_MINUTO', 300),

    /*
    |--------------------------------------------------------------------------
    | Imágenes de las noticias, por minuto y por IP
    |--------------------------------------------------------------------------
    |
    | Contador aparte del de arriba: cada visita al home pide seis imágenes y, si
    | compartieran cupo, el home gastaría de una vez la mitad de las lecturas de
    | datos de quien lo visita. 600 = 100 visitas al home por minuto desde una misma
    | IP sin caché de navegador (ESTIMACIÓN, no una medición); con la caché del
    | navegador llegan muchas menos. Mismo criterio de ajuste: subirlo si los 429 de
    | estas rutas pasan del 1 % en producción.
    */
    'imagenes_por_minuto' => (int) env('IMAGENES_LIMITE_POR_MINUTO', 600),

];
