<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Entidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Entidad>
 */
final class EntidadFactory extends Factory
{
    protected $model = Entidad::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'sigla' => strtoupper($this->faker->lexify('???')),
            'nit' => $this->faker->numerify('#########-#'),
            'direccion' => $this->faker->streetAddress(),
            'municipio' => $this->faker->city(),
            'departamento' => $this->faker->state(),
            'pais' => 'Colombia',
            'telefono' => $this->faker->phoneNumber(),
            'linea_atencion' => $this->faker->phoneNumber(),
            'linea_gratuita' => $this->faker->optional()->phoneNumber(),
            'linea_anticorrupcion' => $this->faker->optional()->phoneNumber(),
            'correo_atencion' => $this->faker->companyEmail(),
            'correo_notificaciones_judiciales' => $this->faker->optional()->companyEmail(),
            'horario' => 'Lunes a viernes 8:00 - 17:00',
            'codigo_postal' => $this->faker->postcode(),
            'dominio' => $this->faker->domainName(),
            'redes' => [
                ['red' => 'twitter', 'url' => 'https://twitter.com/ejemplo'],
                ['red' => 'facebook', 'url' => 'https://facebook.com/ejemplo'],
            ],
            'politicas' => [
                ['slug' => 'privacidad', 'nombre' => 'Política de privacidad'],
                ['slug' => 'tratamiento-datos', 'nombre' => 'Política de tratamiento de datos'],
            ],
            'datos_por_confirmar' => [],
        ];
    }
}
