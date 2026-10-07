<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\PermissionAuditListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * Registra todos los listeners de eventos de la aplicación.
 */
final class EventServiceProvider extends ServiceProvider
{
    /**
     * Los eventos de la aplicación y sus listeners.
     *
     * @var array<class-string, list<class-string>>
     */
    protected $listen = [];

    /**
     * Los suscriptores de eventos (eventos con múltiples handlers).
     *
     * @var list<class-string>
     */
    protected $subscribe = [
        PermissionAuditListener::class,
    ];

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
