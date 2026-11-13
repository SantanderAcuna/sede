<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Tramite;
use App\Support\Tramites\FiltrosTramite;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * El acceso al catálogo de trámites.
 *
 * La publicación se decide en `Tramite::scopePublicados` y se aplica aquí y en el repositorio de
 * la búsqueda, que son las dos puertas por las que el ciudadano llega al catálogo: los únicos
 * lugares donde puede comprobarse que nada incompleto salga a la luz. Si la regla viviera repartida
 * entre el controlador y el servicio, bastaría con añadir una consulta nueva para saltársela.
 */
interface TramiteRepositoryInterface
{
    /**
     * La página de trámites publicados del grupo que cumplen los filtros, con su dependencia y su
     * tema ya cargados para la tarjeta.
     *
     * @return LengthAwarePaginator<int, Tramite>
     */
    public function paginar(FiltrosTramite $filtros): LengthAwarePaginator;

    /**
     * El recuento de cada faceta dentro del grupo y la búsqueda vigentes, para los filtros de la
     * barra lateral. Cada faceta cuenta sobre el grupo activo —no sobre la selección de las demás
     * facetas— para que elegir una no borre del panel las otras opciones.
     *
     * @return array{
     *     tipos: list<array{valor: string, total: int}>,
     *     modalidades: list<array{valor: string, total: int}>,
     *     costos: list<array{valor: string, total: int}>,
     *     categorias: list<array{slug: string, nombre: string, total: int}>,
     *     dependencias: list<array{id: int, nombre: string, total: int}>,
     *     solicitantes: list<array{valor: string, total: int}>
     * }
     */
    public function facetas(FiltrosTramite $filtros): array;

    /** El trámite publicado con ese slug, con toda su ficha cargada, o nulo si no lo hay. */
    public function porSlug(string $slug): ?Tramite;
}
