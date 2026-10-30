<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * El acceso al catálogo de trámites.
 *
 * La publicación se decide en `Tramite::scopePublicados` y se aplica aquí y en el
 * repositorio de la búsqueda, que son las dos puertas por las que el ciudadano
 * llega al catálogo: son los únicos lugares donde puede comprobarse que nada sin
 * ficha en GOV.CO salga a la luz. Si la regla viviera repartida entre el
 * controlador y el servicio, bastaría con añadir una consulta nueva para saltársela
 * sin que nada fallara.
 */
interface TramiteRepositoryInterface
{
    /**
     * La página de trámites publicados que cumplen los filtros.
     *
     * @return LengthAwarePaginator<int, Tramite>
     */
    public function paginar(FiltrosTramite $filtros): LengthAwarePaginator;

    /** El trámite publicado con ese slug, o nulo si no lo hay. */
    public function porSlug(string $slug): ?Tramite;
}
