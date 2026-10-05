<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasUuids;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

/**
 * Archivo subido al sistema.
 *
 * Modelo polimórfico: un archivo puede pertenecer a cualquier entidad del sistema
 * (trámite, entidad, usuario, etc.) sin tables intermedias. El archivo físico
 * vive en un disco configurado (por defecto `public`) y este modelo guarda la
 * referencia + metadatos.
 *
 * R-52: FilesMedia polymorphic.
 *
 * @property string $uuid
 * @property string|null $model_uuid
 * @property string $model_type
 * @property int|null $model_id
 * @property string $collection
 * @property string $filename
 * @property string $original_filename
 * @property string $mime_type
 * @property int $size
 * @property string $disk
 * @property string $path
 * @property array<string, mixed>|null $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class FileMedia extends Model
{
    use HasUuids;

    protected $table = 'file_media';

    /** @var list<string> */
    protected $fillable = [
        'model_uuid',
        'model_type',
        'model_id',
        'collection',
        'filename',
        'original_filename',
        'mime_type',
        'size',
        'disk',
        'path',
        'metadata',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'metadata' => 'array',
        ];
    }

    /**
     * Relación polimórfica al modelo propietario.
     *
     * @phpstan-return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * URL pública del archivo (válida mientras el disco sea accesible).
     */
    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Elimina el archivo físico y el registro de la base de datos.
     */
    public function deleteFully(): void
    {
        Storage::disk($this->disk)->delete($this->path);
        $this->delete();
    }
}
