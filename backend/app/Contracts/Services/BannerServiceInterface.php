<?php

declare(strict_types=1);

namespace App\Contracts\Services;

use App\Exceptions\OrdenDeBannersDesactualizado;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

interface BannerServiceInterface
{
    /** @return Collection<int, Banner> todas, en el orden elegido */
    public function todos(): Collection;

    /** @return Collection<int, Banner> las que el sitio muestra ahora */
    public function vigentes(): Collection;

    public function porUuid(string $uuid): ?Banner;

    /** @param array<string, mixed> $datos */
    public function crear(User $actor, array $datos): Banner;

    /** @param array<string, mixed> $datos */
    public function actualizar(User $actor, Banner $banner, array $datos): Banner;

    public function guardarImagen(User $actor, Banner $banner, UploadedFile $imagen, string $alt): Banner;

    /**
     * @param  list<string>  $uuids  todos los banners, en el nuevo orden
     * @param  list<string>  $antes  el orden que vio quien guarda, para detectar que otra persona lo cambió
     *
     * @throws OrdenDeBannersDesactualizado si el orden actual ya no es `$antes`
     * @throws ValidationException si `$uuids` no es la lista completa, una vez cada una
     */
    public function reordenar(User $actor, array $uuids, array $antes): void;

    public function eliminar(User $actor, Banner $banner): void;
}
