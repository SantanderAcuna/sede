<?php

declare(strict_types=1);

namespace App\Models\Traits;

use Illuminate\Support\Str;

/**
 * Añade un UUID v4 como identificador público al modelo.
 *
 * Se genera automáticamente al crear el modelo y se expone como ruta-clave
 * (`getRouteKeyName()`) para que las URLs usen el UUID en lugar del id
 * secuencial. El `id` interno se mantiene para las relaciones de clave ajena.
 *
 * R-40: UUID en vez de auto-increment.
 */
trait HasUuids
{
    /**
     * Boot the trait: registers the creating event to generate UUID.
     */
    protected static function bootHasUuids(): void
    {
        static::creating(function ($model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model (used by route model binding).
     *
     * Returning 'uuid' means route parameters like `{user}` resolve by UUID.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
