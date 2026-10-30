<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTOs\Busqueda\ResultadoBusqueda;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * El acceso a la búsqueda de la sede.
 *
 * Como el catálogo, la búsqueda tiene una sola puerta: aquí se decide qué
 * contenido es publicable y cómo se ordena por pertinencia. Un trámite sin ficha
 * en GOV.CO no aparece en el catálogo y tampoco puede aparecer aquí; si la regla
 * se repitiera en dos consultas, antes o después una de las dos la olvidaría.
 *
 * Recibe el texto **ya normalizado** (`TextoBuscable::paraConsulta`) y sus
 * palabras exigibles: normalizar es una regla del dato, no del acceso.
 */
interface BusquedaRepositoryInterface
{
    /**
     * Los `$limite` contenidos más pertinentes, de mayor a menor.
     *
     * @param  list<string>  $palabras  Palabras que el contenido debe contener (con tolerancia)
     * @return list<ResultadoBusqueda>
     */
    public function sugerir(string $consulta, array $palabras, int $limite): array;

    /**
     * Una página de resultados por pertinencia.
     *
     * @param  list<string>  $palabras
     * @param  string|null  $tipo  `tramite`, `politica` o nulo para todos
     * @return LengthAwarePaginator<int, ResultadoBusqueda>
     */
    public function buscar(string $consulta, array $palabras, ?string $tipo, int $pagina, int $porPagina): LengthAwarePaginator;
}
