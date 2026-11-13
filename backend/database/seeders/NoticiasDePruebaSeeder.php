<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Contracts\Services\NoticiaServiceInterface;
use App\Models\Noticia;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Diez noticias **de prueba** para ver la portada, el listado y el detalle (M01-6).
 *
 * # Dónde NO debe correr
 *
 * La sede no publica noticias inventadas (CT-01), así que este sembrador:
 *
 *   - **no está en `SedeSeeder`**, que es el que corre en cada despliegue;
 *   - **no está en `DatabaseSeeder` salvo en `local`**;
 *   - se niega a correr fuera de `local`/`testing` si `datos_de_prueba.permitir` no
 *     está encendida. Esa bandera no vive en ningún archivo de `deploy/`: se enciende sólo
 *     en el comando manual (`-e PERMITIR_DATOS_DE_PRUEBA=1`). El nombre del entorno no
 *     basta: staging y producción tienen `APP_ENV=production` los dos.
 *
 * Cada noticia dice en su título y en su texto que es de prueba. Se siembra a mano
 * en staging como describe `deploy/README.md`, «Noticias de prueba en staging».
 *
 * # Qué cubre
 *
 * Más de 6 (la portada corta en 6) y más de 9 (el listado pagina de a 9); imágenes
 * ancha, alta y cuadrada (el sitio las recorta); un título de 150 caracteres, el
 * límite; destacadas y no destacadas; fechas distintas para el orden inverso.
 *
 * **Idempotente**: no repite una noticia que ya existe —ni una eliminada— y no
 * pisa lo que una persona haya editado. Pasa por `NoticiaService`, el mismo camino
 * que el panel, así que lo que siembra es lo que el servidor aceptaría publicar.
 */
final class NoticiasDePruebaSeeder extends Seeder
{
    private const AVISO = 'Este texto es de prueba y no es información institucional de la Alcaldía.';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) && ! config('datos_de_prueba.permitir')) {
            throw new RuntimeException(
                'Las noticias de prueba no se siembran aquí: este entorno no tiene PERMITIR_DATOS_DE_PRUEBA. '
                .'La sede no publica noticias inventadas.'
            );
        }

        $servicio = app(NoticiaServiceInterface::class);

        foreach ($this->noticias() as $n => $datos) {
            if (Noticia::query()->withTrashed()->where('titulo', $datos['titulo'])->exists()) {
                continue;
            }

            $noticia = $servicio->crear([
                'titulo' => $datos['titulo'],
                'descripcion_corta' => $datos['descripcion'],
                'contenido' => implode("\n\n", [...$datos['parrafos'], self::AVISO]),
                'fecha_publicacion' => now()->subDays($n + 1)->setTime(9, 0),
                'destacada' => $datos['destacada'],
                'activo' => false,
            ], null);

            $servicio->guardarImagen(
                $noticia,
                $this->imagen($n + 1, $datos['forma']),
                'Imagen de prueba: rectángulo de color liso con el número '.str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT).'.',
            );

            $servicio->actualizar($noticia->refresh(), ['activo' => true]);
        }
    }

    /**
     * @return list<array{titulo: string, descripcion: string, parrafos: list<string>, forma: string, destacada: bool}>
     */
    private function noticias(): array
    {
        $parrafos = [
            'Primer párrafo de una noticia de prueba, escrito para ver cómo se parte el texto en el detalle.',
            'Segundo párrafo de prueba. Sirve para comprobar el espacio entre párrafos y el ancho de la columna de lectura.',
        ];

        $titulos = [
            'Noticia de prueba 01: jornada de atención en la sede',
            'Noticia de prueba 02: nuevo horario de atención',
            'Noticia de prueba 03: jornada de vacunación',
            'Noticia de prueba 04: apertura de convocatoria',
            'Noticia de prueba 05: título en el límite de ciento cincuenta caracteres para comprobar cómo se ajusta en la tarjeta, la lista y el detalle de la nota',
            'Noticia de prueba 06: mantenimiento de la sede electrónica',
            'Noticia de prueba 07: cierre de recaudo del impuesto',
            'Noticia de prueba 08: socialización del plan de desarrollo',
            'Noticia de prueba 09: recomendaciones para la temporada',
            'Noticia de prueba 10: la noticia más antigua de la lista',
        ];

        $formas = ['ancha', 'alta', 'cuadrada', 'ancha', 'alta', 'cuadrada', 'ancha', 'alta', 'cuadrada', 'ancha'];

        $noticias = [];
        foreach ($titulos as $i => $titulo) {
            $noticias[] = [
                'titulo' => $titulo,
                'descripcion' => 'Descripción corta de la noticia de prueba '.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT).', para ver cómo se ajusta en la tarjeta.',
                'parrafos' => $parrafos,
                'forma' => $formas[$i],
                'destacada' => $i % 4 === 0,
            ];
        }

        return $noticias;
    }

    /** Un PNG liso de la forma pedida, hecho con GD (sin descargar nada). */
    private function imagen(int $numero, string $forma): UploadedFile
    {
        [$ancho, $alto] = match ($forma) {
            'alta' => [600, 900],
            'cuadrada' => [800, 800],
            default => [1200, 600],
        };

        $lienzo = imagecreatetruecolor($ancho, $alto);
        if ($lienzo === false) {
            throw new RuntimeException('GD no pudo crear la imagen de prueba.');
        }

        $tono = ($numero * 36) % 360;
        $relleno = imagecolorallocate($lienzo, 60 + ($tono % 120), 90 + (($tono * 2) % 100), 140 + (($tono * 3) % 80));
        $texto = imagecolorallocate($lienzo, 255, 255, 255);
        if ($relleno === false || $texto === false) {
            throw new RuntimeException('GD no pudo asignar los colores de la imagen de prueba.');
        }

        imagefilledrectangle($lienzo, 0, 0, $ancho, $alto, $relleno);
        imagestring($lienzo, 5, 20, 20, 'PRUEBA '.str_pad((string) $numero, 2, '0', STR_PAD_LEFT), $texto);

        $ruta = tempnam(sys_get_temp_dir(), 'noticia-prueba-');
        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal de la imagen de prueba.');
        }
        imagepng($lienzo, $ruta);

        return new UploadedFile($ruta, 'prueba-'.$numero.'.png', 'image/png', null, true);
    }
}
