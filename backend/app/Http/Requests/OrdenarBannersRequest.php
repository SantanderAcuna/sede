<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Banner;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/** El nuevo orden: la lista completa de diapositivas, sin repetidas ni desconocidas. */
final class OrdenarBannersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'uuids' => ['required', 'array', 'min:1', static function (string $atributo, mixed $valor, Closure $fallar): void {
                $enviados = is_array($valor) ? array_map('strval', $valor) : [];
                $existentes = Banner::query()->pluck('uuid')->map(fn ($u) => (string) $u)->all();
                sort($enviados);
                sort($existentes);
                if ($enviados !== $existentes) {
                    $fallar('El orden debe incluir todas las diapositivas, una sola vez cada una. Recargue la lista e inténtelo de nuevo.');
                }
            }],
            'uuids.*' => ['string'],
        ];
    }

    /** @return list<string> */
    public function uuids(): array
    {
        /** @var list<string> $uuids */
        $uuids = $this->validated('uuids');

        return array_values($uuids);
    }
}
