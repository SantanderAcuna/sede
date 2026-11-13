<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Los mensajes de validación salen en español (M01, transversal).
 *
 * Sin `lang/es` ni locale `es`, Laravel devolvía «The per page field must not
 * be greater than 100.» y el panel mostraba inglés a los funcionarios.
 */
final class MensajesEnEspanolTest extends TestCase
{
    public function test_el_idioma_de_la_aplicacion_es_espanol(): void
    {
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('es', config('app.fallback_locale'));
    }

    public function test_el_validador_habla_espanol(): void
    {
        $errores = Validator::make(['titulo' => ''], ['titulo' => 'required'])->errors();

        $this->assertSame('El campo título es obligatorio.', $errores->first('titulo'));
    }

    public function test_los_atributos_conocidos_se_nombran_en_lenguaje_natural(): void
    {
        $errores = Validator::make(
            ['fecha_publicacion' => ''],
            ['fecha_publicacion' => 'required'],
        )->errors();

        $this->assertSame('El campo fecha de publicación es obligatorio.', $errores->first('fecha_publicacion'));
    }

    public function test_el_limite_de_una_cadena_se_dice_en_espanol(): void
    {
        $errores = Validator::make(['titulo' => str_repeat('a', 151)], ['titulo' => 'max:150'])->errors();

        $this->assertSame('El campo título no puede tener más de 150 caracteres.', $errores->first('titulo'));
    }

    public function test_la_respuesta_http_422_trae_el_detalle_en_espanol(): void
    {
        $respuesta = $this->getJson('/api/v1/noticias?per_page=101')->assertStatus(422);

        $this->assertSame('Error de validación.', $respuesta->json('message'));
        $this->assertSame(
            'El campo por página no puede ser mayor que 100.',
            $respuesta->json('errors.per_page.0'),
        );
    }

    public function test_no_quedan_mensajes_en_ingles_en_validacion_ni_auth(): void
    {
        foreach (['validation', 'auth', 'passwords', 'pagination'] as $archivo) {
            $ruta = lang_path("es/{$archivo}.php");
            $this->assertFileExists($ruta, "Falta lang/es/{$archivo}.php");
        }

        $this->assertSame('Estas credenciales no coinciden con nuestros registros.', trans('auth.failed'));
    }
}
