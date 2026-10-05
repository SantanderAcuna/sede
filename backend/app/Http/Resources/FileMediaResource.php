<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\FileMedia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin FileMedia
 *
 * @property FileMedia $resource
 */
final class FileMediaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'model_uuid' => $this->model_uuid,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'collection' => $this->collection,
            'filename' => $this->filename,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'disk' => $this->disk,
            'path' => $this->path,
            'url' => $this->url(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
