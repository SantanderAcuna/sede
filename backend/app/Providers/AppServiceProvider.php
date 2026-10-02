<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Integraciones\FuenteFichaGovCoInterface;
use App\Contracts\Repositories\AuthRepositoryInterface;
use App\Contracts\Repositories\EntidadRepositoryInterface;
use App\Contracts\Repositories\IngestaTramiteRepositoryInterface;
use App\Contracts\Repositories\MenuRepositoryInterface;
use App\Contracts\Repositories\TramiteRepositoryInterface;
use App\Contracts\Services\AuthServiceInterface;
use App\Contracts\Services\EntidadServiceInterface;
use App\Contracts\Services\IdentidadServiceInterface;
use App\Contracts\Services\IngestaTramitesInterface;
use App\Contracts\Services\TramiteServiceInterface;
use App\Models\User;
use App\Repositories\Eloquent\AuthRepository;
use App\Repositories\Eloquent\EntidadRepository;
use App\Repositories\Eloquent\IngestaTramiteRepository;
use App\Repositories\Eloquent\MenuRepository;
use App\Repositories\Eloquent\TramiteRepository;
use App\Services\AuthService;
use App\Services\EntidadService;
use App\Services\GovCo\FuenteFichaGovCo;
use App\Services\IdentidadService;
use App\Services\IngestaTramites;
use App\Services\TramiteService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        // Entidad: datos institucionales para cabecera y pie de página
        $this->app->bind(EntidadRepositoryInterface::class, EntidadRepository::class);
        $this->app->bind(EntidadServiceInterface::class, EntidadService::class);

        // Identidad: top-bar, footer y menú
        $this->app->bind(MenuRepositoryInterface::class, MenuRepository::class);
        $this->app->bind(IdentidadServiceInterface::class, IdentidadService::class);

        // Auth: login, logout y perfil
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(AuthServiceInterface::class, AuthService::class);

        // La ingesta y su fuente. La interfaz de la fuente es lo que permite probar
        // la ingesta entera —incluido su comportamiento de reanudación— sin salir a
        // la red: la fuente del Estado limita la tasa y una prueba que dependiera
        // de ella fallaría los días que la fuente decide no contestar.
        $this->app->bind(IngestaTramiteRepositoryInterface::class, IngestaTramiteRepository::class);
        $this->app->bind(FuenteFichaGovCoInterface::class, FuenteFichaGovCo::class);
        $this->app->bind(IngestaTramitesInterface::class, IngestaTramites::class);
    }

    /**
     * Arranca los servicios transversales: sobre plano, autorización y límites.
     */
    public function boot(): void
    {
        // El sobre plano es global, no del controlador. Sin esto, cualquier
        // `JsonResource` que se devuelva sin pasar por `ApiResponse` sale con el
        // envoltorio `{"data": …}` de Laravel y aparecen dos formas de respuesta
        // según quién escriba la línea; con esto, `data` es siempre el objeto que
        // el contrato declara.
        JsonResource::withoutWrapping();

        // El super-admin no pasa por las comprobaciones de permisos. El `null` del
        // caso contrario no es un descuido: devolver `false` aquí negaría el
        // permiso antes de que la Policy o el `Gate` pudieran concederlo, y un
        // `Gate::before` que deniega no se puede revertir después.
        Gate::before(function (User $usuario, string $habilidad) {
            return $usuario->hasRole('super-admin') ? true : null;
        });

        // El acceso se limita por correo y por IP a la vez: el primer límite frena
        // el ataque contra una cuenta concreta —cinco intentos— y el segundo frena
        // el barrido de muchas cuentas desde una misma máquina, que el primero no
        // vería porque cada correo es distinto.
        RateLimiter::for('login', function (Request $peticion) {
            return [
                Limit::perMinute(5)->by($peticion->input('email').'|'.$peticion->ip()),
                Limit::perMinute(20)->by($peticion->ip()),
            ];
        });

        // La API entera cabe en sesenta peticiones por minuto. Se cuenta por
        // usuario cuando hay sesión y por IP cuando no, para que un cliente
        // autenticado no consuma el cupo de la oficina entera que comparte salida.
        RateLimiter::for('api', function (Request $peticion) {
            return Limit::perMinute(60)->by($peticion->user()?->id ?: $peticion->ip());
        });
    }
}
