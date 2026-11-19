<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Services\BannerServiceInterface;
use App\Exceptions\BannerIncompleto;
use App\Models\Banner;
use App\Models\User;
use App\Support\Acceso\Huella;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * El carrusel de la portada. La imagen se guarda en la tabla común de archivos (`FilesMediaService`).
 *
 * Quien publica una diapositiva sin imagen o sin texto alternativo recibe un 422 con lo que falta:
 * el panel y la API no la dejan pasar (Res. MinTIC 1519/2020, Anexo 2).
 */
final class BannerService implements BannerServiceInterface
{
    public function __construct(private readonly FilesMediaService $archivos) {}

    public function todos(): Collection
    {
        return Banner::query()->with('imagenPrincipal')->orderBy('orden')->orderBy('id')->get();
    }

    public function vigentes(): Collection
    {
        return Banner::query()->vigentes()->with('imagenPrincipal')->get();
    }

    public function porUuid(string $uuid): ?Banner
    {
        return Banner::query()->with('imagenPrincipal')->where('uuid', $uuid)->first();
    }

    public function crear(User $actor, array $datos): Banner
    {
        unset($datos['imagen_alt']);
        $banner = new Banner($datos);
        $banner->orden = ((int) Banner::query()->max('orden')) + 1;
        $banner->activo = (bool) ($datos['activo'] ?? false);
        $this->exigirCompleto($banner);
        $banner->save();

        Huella::registrar($actor, 'banner_creado', 'banners', $banner->id, ['titulo' => $banner->titulo]);

        return $this->porUuid($banner->uuid) ?? $banner;
    }

    public function actualizar(User $actor, Banner $banner, array $datos): Banner
    {
        $alt = array_key_exists('imagen_alt', $datos) ? trim((string) $datos['imagen_alt']) : null;
        unset($datos['imagen_alt']);

        $banner->fill($datos);
        $imagen = $banner->imagenPrincipal;
        if ($alt !== null && $imagen !== null) {
            // Escritura doble (hasta D10): la columna nueva manda y `metadata` conserva lo viejo al día.
            $banner->imagen_alt = $alt;
            $imagen->metadata = ['alt' => $alt] + ($imagen->metadata ?? []);
        }

        // Antes de guardar nada: si queda publicada e incompleta, no cambia ni el texto ni el alt.
        $this->exigirCompleto($banner);

        DB::transaction(function () use ($banner, $imagen, $alt): void {
            $banner->save();
            if ($alt !== null && $imagen !== null) {
                $imagen->save();
            }
        });

        Huella::registrar($actor, 'banner_actualizado', 'banners', $banner->id, [
            'campos' => array_merge(array_keys($datos), $alt !== null ? ['imagen_alt'] : []),
            'estado' => $banner->estado(),
        ]);

        return $this->porUuid($banner->uuid) ?? $banner;
    }

    public function guardarImagen(User $actor, Banner $banner, UploadedFile $imagen, string $alt): Banner
    {
        $anterior = $banner->imagenPrincipal;

        // 1) Primero se sube la nueva: si falla, la anterior sigue en su sitio.
        $nueva = $this->archivos->upload(
            file: $imagen,
            collection: Banner::COLECCION_IMAGEN,
            model: $banner,
            metadata: ['alt' => $alt],
        );

        // 2) Transacción corta, sin E/S de archivos.
        DB::transaction(function () use ($banner, $nueva, $alt): void {
            $banner->imagen_uuid = $nueva->uuid;
            $banner->imagen_alt = $alt;
            $banner->save();
        });

        // 3) Después del commit se borra la anterior; si falla, queda un archivo sin uso que la limpieza lista.
        if ($anterior !== null && $anterior->uuid !== $nueva->uuid) {
            try {
                $this->archivos->delete($anterior->uuid);
            } catch (Throwable $e) {
                Log::warning('No se pudo borrar la imagen anterior del banner.', ['archivo' => $anterior->uuid, 'exception' => $e]);
            }
        }
        $banner->unsetRelation('imagenPrincipal');

        Huella::registrar($actor, 'banner_imagen_cambiada', 'banners', $banner->id, ['titulo' => $banner->titulo]);

        return $this->porUuid($banner->uuid) ?? $banner;
    }

    public function reordenar(User $actor, array $uuids): void
    {
        DB::transaction(function () use ($actor, $uuids): void {
            // Se bloquean todas las filas en un orden fijo (por id) antes de actualizar: si dos personas reordenan
            // a la vez cada una actualizaría en su propio orden, y PostgreSQL abortaría una por interbloqueo.
            // Con el bloqueo tomado se vuelve a comprobar la lista, porque entre la validación de la petición y
            // aquí pudo borrarse o crearse una diapositiva.
            /** @var list<string> $vigentes */
            $vigentes = Banner::query()->orderBy('id')->lockForUpdate()->pluck('uuid')->map(fn ($u) => (string) $u)->all();
            $enviados = array_map('strval', array_values($uuids));
            $esperados = $vigentes;
            sort($enviados);
            sort($esperados);
            if ($enviados !== $esperados) {
                throw ValidationException::withMessages([
                    'uuids' => ['El orden debe incluir todas las diapositivas, una sola vez cada una. Recargue la lista e inténtelo de nuevo.'],
                ]);
            }

            foreach (array_values($uuids) as $posicion => $uuid) {
                Banner::query()->where('uuid', $uuid)->update(['orden' => $posicion + 1]);
            }
            Huella::registrar($actor, 'banners_reordenados', 'banners', null, ['orden' => array_values($uuids)]);
        });
    }

    public function eliminar(User $actor, Banner $banner): void
    {
        $titulo = $banner->titulo;
        $id = $banner->id;
        $banner->delete();

        Huella::registrar($actor, 'banner_eliminado', 'banners', $id, ['titulo' => $titulo]);
    }

    /** @throws BannerIncompleto */
    private function exigirCompleto(Banner $banner): void
    {
        if (! $banner->activo) {
            return;
        }
        $problemas = $banner->problemas();
        if ($problemas !== []) {
            throw new BannerIncompleto($problemas);
        }
    }
}
