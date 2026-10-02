<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Entidad;
use Illuminate\Database\Seeder;

/**
 *Seeder para los datos institucionales de la Alcaldía Distrital de Santa Marta.
 */
final class EntidadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Entidad::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Alcaldía Distrital de Santa Marta',
                'sigla' => 'D.T.C.H.',
                'nit' => '891.780.009-4',
                'direccion' => 'Calle 14 No. 2-49, Palacio Municipal',
                'municipio' => 'Santa Marta',
                'departamento' => 'Magdalena',
                'pais' => 'Colombia',
                'telefono' => '(+57) 605 420 9600',
                'linea_atencion' => '(+57) 605 4351719',
                'linea_gratuita' => '018000 955 532',
                'linea_anticorrupcion' => '(+57) 605 4351719',
                'correo_atencion' => 'atencionalciudadano@santamarta.gov.co',
                'correo_notificaciones_judiciales' => 'notificacionesalcaldiadistrital@santamarta.gov.co',
                'horario' => 'Lunes a viernes de 8:00 a 12:00 y de 14:00 a 18:00',
                'codigo_postal' => '470004',
                'dominio' => 'https://staging.santamarta.gov.co',
                'logo' => '/storage/entidad/escudo.svg',
                'redes' => [
                    ['red' => 'facebook', 'url' => 'https://www.facebook.com/SantaMartaDTCH'],
                    ['red' => 'instagram', 'url' => 'https://www.instagram.com/alcaldiadesantamarta'],
                    ['red' => 'x', 'url' => 'https://twitter.com/alcaldesantamarta'],
                    ['red' => 'youtube', 'url' => 'https://www.youtube.com/@AlcaldiadeSanta Marta'],
                ],
                'politicas' => [
                    ['slug' => 'terminos-y-condiciones', 'nombre' => 'Términos y condiciones'],
                    ['slug' => 'seguridad-y-privacidad', 'nombre' => 'Seguridad y privacidad'],
                    ['slug' => 'tratamiento-de-datos', 'nombre' => 'Protección y tratamiento de datos personales'],
                    ['slug' => 'uso-de-cookies', 'nombre' => 'Uso de cookies'],
                    ['slug' => 'derechos-de-autor', 'nombre' => 'Derechos de autor y uso sobre los contenidos'],
                ],
                'datos_por_confirmar' => [],
            ]
        );
    }
}
