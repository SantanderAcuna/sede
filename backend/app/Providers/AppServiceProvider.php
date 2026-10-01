<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Integraciones\FuenteFichaGovCoInterface;
use App\Contracts\Repositories\IngestaTramiteRepositoryInterface;
use App\Contracts\Repositories\TramiteRepositoryInterface;
use App\Contracts\Services\IngestaTramitesInterface;
use App\Contracts\Services\TramiteServiceInterface;
use App\Repositories\Eloquent\IngestaTramiteRepository;
use App\Repositories\Eloquent\TramiteRepository;
use App\Services\GovCo\FuenteFichaGovCo;
use App\Services\IngestaTramites;
use App\Services\TramiteService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Registra las implementaciones de las interfaces del proyecto.
     *
     * Se atan con `bind` y no con `singleton`: ni el servicio ni el repositorio
     * guardan estado entre peticiones, así que compartir la instancia sólo
     * serviría para que un dato de una petición sobreviviera a la siguiente. Lo
     * que sí importa es que el controlador pida la interfaz y no la clase
     * concreta: es lo que permite sustituirla en una prueba sin tocar la base.
     */
    public function register(): void
    {
        $this->app->bind(TramiteRepositoryInterface::class, TramiteRepository::class);
        $this->app->bind(TramiteServiceInterface::class, TramiteService::class);

        // La ingesta y su fuente. La interfaz de la fuente es lo que permite probar
        // la ingesta entera —incluido su comportamiento de reanudación— sin salir a
        // la red: la fuente del Estado limita la tasa y una prueba que dependiera
        // de ella fallaría los días que la fuente decide no contestar.
        $this->app->bind(IngestaTramiteRepositoryInterface::class, IngestaTramiteRepository::class);
        $this->app->bind(FuenteFichaGovCoInterface::class, FuenteFichaGovCo::class);
        $this->app->bind(IngestaTramitesInterface::class, IngestaTramites::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
