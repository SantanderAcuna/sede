<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tramite;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tramite>
 */
final class TramiteFactory extends Factory
{
    protected $model = Tramite::class;

    public function definition(): array
    {
        $nombre = $this->faker->sentence(4);

        return [
            'codigo' => 'TR-'.Str::upper(Str::random(6)),
            'slug' => Str::slug($nombre),
            'nombre' => $nombre,
            'resumen' => $this->faker->paragraph(),
            'type' => 'tramites',
            'modalidad' => 'parcialmente_en_linea',
            'tiene_costo' => 'gratuito',
            'tiempo_solucion_dias' => $this->faker->numberBetween(1, 60),
            'canal_inicio' => 'propio',
            'consulta_estado' => '/seguimiento?radicado=…',
            'requisitos' => [['descripcion' => 'Cédula de ciudadanía', 'obligatorio' => true]],
            'documentos' => null,
            'resultado' => null,
            'producto_final' => null,
            'observaciones_resultado' => null,
            'fecha_cualquiera' => true,
            'cuando_se_puede_realizar' => null,
            'url_calendario' => null,
            'perfiles' => null,
            'momentos' => null,
            'puntos_atencion' => null,
            'normativa' => null,
            'canales_consulta_estado' => null,
            'medios_resultado' => null,
            'audiencias' => null,
            'cuentas' => null,
            'seguimiento' => null,
            'palabras_relacionadas' => null,
            'costo' => null,
            'costo_tipo_valor' => null,
            'costo_moneda' => null,
            'costo_url_pago' => null,
            'costo_descripcion' => null,
            'costo_cuentas' => null,
            'url_inicio' => null,
            'url_manual_tramite_en_linea' => null,
            'url_ficha_gov_co' => 'https://www.gov.co/ficha/'.Str::random(6),
            'categoria_slug' => null,
            'categoria_nombre' => null,
            'procedencia_fuente' => null,
            'procedencia_url' => null,
            'procedencia_obtenido_en' => null,
            'procedencia_nota' => null,
            'procedencia_api' => null,
            'procedencia_origen_por_campo' => null,
            'procedencia_derivados' => null,
            'procedencia_faltantes' => null,
            'publicado_en' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['publicado_en' => now()]);
    }
}
