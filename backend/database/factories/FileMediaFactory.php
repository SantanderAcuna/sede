<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FileMedia;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FileMedia>
 */
final class FileMediaFactory extends Factory
{
    protected $model = FileMedia::class;

    public function definition(): array
    {
        $extension = $this->faker->randomElement(['pdf', 'png', 'jpg', 'docx']);
        $filename = Str::uuid()->toString().'.'.$extension;

        return [
            'model_uuid' => null,
            'model_type' => 'App\Models\Entidad',
            'model_id' => null,
            'collection' => $this->faker->randomElement(['logos', 'documentos', 'firmas', 'fotos']),
            'filename' => $filename,
            'original_filename' => $this->faker->word().'.'.$extension,
            'mime_type' => $this->faker->randomElement([
                'application/pdf',
                'image/png',
                'image/jpeg',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ]),
            'size' => $this->faker->numberBetween(1024, 10485760),
            'disk' => 'public',
            'path' => 'file_media/Entidad/'.$this->faker->randomElement(['logos', 'documentos']).'/'.$filename,
            'metadata' => null,
        ];
    }
}
