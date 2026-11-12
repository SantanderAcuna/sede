<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tramite;
use App\Models\TramiteRequisito;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tramite>
 */
class TramiteFactory extends Factory
{
    protected $model = Tramite::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $nombre = $this->faker->sentence(4);

        return [
            'codigo' => 'T'.$this->faker->unique()->numerify('#####'),
            'slug' => Str::slug($nombre).'-'.Str::lower(Str::random(5)),
            'nombre' => $nombre,
            'resumen' => $this->faker->paragraph(),
            'type' => 'tramites',
            'modalidad' => 'parcialmente_en_linea',
            'tiene_costo' => 'gratuito',
            'tiempo_solucion_dias' => $this->faker->numberBetween(1, 60),
            'canal_inicio' => 'propio',
            'consulta_estado' => 'En la ventanilla única',
            'url_ficha_gov_co' => null,
            'publicado_en' => null,
        ];
    }

    /** Publicado: con fecha de alta y con el requisito que la regla de publicación exige. */
    public function published(): static
    {
        return $this->state(fn (): array => ['publicado_en' => now()])
            ->afterCreating(function (Tramite $tramite): void {
                if (! $tramite->requisitos()->exists()) {
                    $tramite->requisitos()->create(['orden' => 1, 'tipo' => 'documento', 'descripcion' => 'Cédula de ciudadanía', 'obligatorio' => true]);
                }
            });
    }

    public function conRequisito(string $descripcion = 'Cédula de ciudadanía'): static
    {
        return $this->afterCreating(function (Tramite $tramite) use ($descripcion): void {
            TramiteRequisito::query()->create(['tramite_id' => $tramite->id, 'orden' => 1, 'tipo' => 'documento', 'descripcion' => $descripcion, 'obligatorio' => true]);
        });
    }

    public function opa(): static
    {
        return $this->state(fn (): array => ['type' => 'opa', 'codigo' => 'T'.$this->faker->unique()->numerify('9####')]);
    }

    /** Una consulta de acceso a información pública: no es un trámite y no lleva código SUIT. */
    public function consulta(): static
    {
        return $this->state(fn (): array => ['type' => 'consultas', 'codigo' => 'C'.$this->faker->unique()->numerify('####'), 'modalidad' => null, 'tiene_costo' => null, 'tiempo_solucion_dias' => null, 'canal_inicio' => null, 'consulta_estado' => null]);
    }
}
