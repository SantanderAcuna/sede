<?php

declare(strict_types=1);

namespace App\Models;

use App\Exceptions\ArchivoEnUso;
use App\Models\Traits\HasUuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Archivo subido al sistema.
 *
 * El archivo físico vive en un disco configurado (`FILESYSTEM_DISK`) y este modelo guarda la
 * referencia. No tiene dueño polimórfico: quién lo usa lo dicen las relaciones explícitas
 * (`noticias.imagen_principal_uuid`, `banners.imagen_uuid`, `noticia_imagenes`,
 * `politica_documentos`), todas ON DELETE RESTRICT. `uso` solo distingue qué política de
 * limpieza le toca: `contenido` sin uso se borra a los 7 días; `documento` nunca (Ley 594/2000).
 *
 * @property string $uuid
 * @property string $uso imagen | contenido | documento
 * @property int|null $subido_por
 * @property string $filename
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size
 * @property string $disk
 * @property string $path
 * @property string|null $hash_sha256 SHA-256 del contenido; vacío hasta calcularlo (`archivos:calcular-hash`)
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class FileMedia extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'file_media';

    /** @var string La PK es uuid, no id. */
    protected $primaryKey = 'uuid';

    /** @var false No es auto-increment. */
    public $incrementing = false;

    /**
     * La clave es un uuid (texto). Sin declararlo, Laravel la trata como entera y en la carga ansiosa de
     * una relación hacia este modelo convierte los uuid a `0` (`where uuid in (0)`), que PostgreSQL rechaza.
     *
     * @var string
     */
    protected $keyType = 'string';

    public const USO_IMAGEN = 'imagen';

    public const USO_CONTENIDO = 'contenido';

    public const USO_DOCUMENTO = 'documento';

    /** @var list<string> */
    protected $fillable = [
        'uso',
        'subido_por',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
        'hash_sha256',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    /**
     * Nombre de la colección tal como lo conoce la API y el panel (`documento-oficial` para los
     * documentos de política); la columna se llama `uso` y `documento` a secas.
     */
    public function coleccionApi(): string
    {
        return $this->uso === self::USO_DOCUMENTO ? 'documento-oficial' : $this->uso;
    }

    /**
     * URL del archivo.
     *
     * En un disco local servido por Laravel (`serve => true`, el caso de `local`)
     * la ruta `/storage/…` exige firma en producción: una URL sin firmar da 403. Por
     * eso se devuelve una URL temporal firmada y no `url()`. En `public` y en discos
     * remotos se mantiene `url()`.
     */
    public function url(): string
    {
        $disco = Storage::disk($this->disk);

        if (config("filesystems.disks.{$this->disk}.driver") === 'local'
            && config("filesystems.disks.{$this->disk}.serve") === true) {
            return $disco->temporaryUrl($this->path, now()->addMinutes(15));
        }

        return $disco->url($this->path);
    }

    /**
     * ¿Alguna noticia, banner, imagen de texto o documento de política usa este archivo?
     */
    public function enUso(): bool
    {
        return DB::table('noticias')->where('imagen_principal_uuid', $this->uuid)->exists()
            || DB::table('banners')->where('imagen_uuid', $this->uuid)->exists()
            || DB::table('noticia_imagenes')->where('file_media_uuid', $this->uuid)->exists()
            || DB::table('politica_documentos')->where('file_media_uuid', $this->uuid)->exists();
    }

    /**
     * Elimina el registro y después el archivo físico.
     *
     * El orden importa: si falla el disco queda un archivo suelto (basura inofensiva y detectable); con el orden
     * contrario, si fallara la fila quedaría un registro que apunta a un archivo inexistente (imagen rota para el
     * público). Un archivo en uso no se borra: la base lo impide con `RESTRICT`.
     *
     * @throws ArchivoEnUso
     */
    public function deleteFully(): void
    {
        if ($this->enUso()) {
            throw new ArchivoEnUso($this->uuid);
        }

        try {
            // Con su propio punto de guardado: un fallo de la base no aborta una transacción que ya esté abierta.
            DB::transaction(fn () => self::where('uuid', $this->uuid)->delete());
        } catch (QueryException $e) {
            // 23001 (PostgreSQL 18) o 23503 (16): una clave foránea RESTRICT lo impidió, porque alguien lo empezó a usar.
            if (in_array($e->errorInfo[0] ?? null, ['23001', '23503'], true)) {
                throw new ArchivoEnUso($this->uuid, $e);
            }

            throw $e;
        }

        Storage::disk($this->disk)->delete($this->path);
    }
}
