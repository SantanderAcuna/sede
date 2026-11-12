<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use App\Models\Tramite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la creación y la edición de un trámite, OPA o consulta desde el panel.
 *
 * **Forma relacional y simétrica.** La ficha se envía con la misma forma con la que la lee el
 * panel (`TramiteFichaResource`): pasos, requisitos (cada uno con el paso al que pertenece, por su
 * número de orden), documentos, puntos de atención, normas, canales de consulta, solicitantes y, en
 * el costo, las cuentas. Así abrir un trámite y guardarlo sin tocarlo no pierde ni reordena nada.
 *
 * Crear exige solo el nombre (puede existir como borrador); publicar exige lo demás y lo comprueba
 * el servicio. `slug` y `codigo` no se aceptan: son enlaces que ya circulan.
 */
final class GuardarTramiteRequest extends FormRequest
{
    private const REQUISITO_TIPOS = ['documento', 'verificacion_institucional', 'solicitud', 'formulario', 'pago'];

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $crear = $this->isMethod('POST');
        $url = ['nullable', 'url:http,https', 'regex:/^\S+$/', 'max:500'];
        $texto = static fn (int $max): array => ['nullable', 'string', 'max:'.$max];

        return [
            'nombre' => [$crear ? 'required' : 'sometimes', 'string', 'min:3', 'max:200'],
            'type' => ['sometimes', Rule::in(Tramite::TIPOS)],
            'resumen' => ['sometimes', ...$texto(500)],
            'url_ficha_gov_co' => ['sometimes', ...$url],
            'modalidad' => ['sometimes', 'nullable', Rule::enum(ModalidadTramite::class)],
            'tiene_costo' => ['sometimes', 'nullable', Rule::enum(CostoTramite::class)],
            'tiempo_solucion_dias' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:36500'],
            'canal_inicio' => ['sometimes', 'nullable', Rule::enum(CanalInicioTramite::class)],
            'url_inicio' => ['sometimes', ...$url],
            'url_manual_tramite_en_linea' => ['sometimes', ...$url],
            'consulta_estado' => ['sometimes', ...$texto(500)],
            'resultado' => ['sometimes', ...$texto(5000)],
            'observaciones_resultado' => ['sometimes', ...$texto(2000)],
            'fecha_cualquiera' => ['sometimes', 'nullable', 'boolean'],
            'cuando_se_puede_realizar' => ['sometimes', ...$texto(1000)],
            'url_calendario' => ['sometimes', ...$url],
            'palabras_relacionadas' => ['sometimes', ...$texto(1000)],
            'dependencia_id' => ['sometimes', 'nullable', 'integer', 'exists:dependencias,id'],
            'medios_resultado' => ['sometimes', 'array', 'max:30'],
            'medios_resultado.*' => ['string', 'max:120'],
            'publicado' => ['sometimes', 'boolean'],

            'costo' => ['sometimes', 'nullable', 'array'],
            'costo.tiene_costo' => ['sometimes', 'nullable', Rule::enum(CostoTramite::class)],
            'costo.tipo_valor' => ['sometimes', 'nullable', 'string', 'max:40'],
            'costo.valor' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'costo.moneda' => ['sometimes', 'nullable', 'string', 'max:20'],
            'costo.url_pago' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'costo.descripcion' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'costo.cuentas' => ['sometimes', 'array', 'max:20'],
            'costo.cuentas.*.banco' => ['sometimes', 'nullable', 'string', 'max:120'],
            'costo.cuentas.*.tipo' => ['sometimes', 'nullable', 'string', 'max:40'],
            'costo.cuentas.*.numero' => ['sometimes', 'nullable', 'string', 'max:40'],
            'costo.cuentas.*.titular' => ['sometimes', 'nullable', 'string', 'max:120'],

            'categoria' => ['sometimes', 'nullable', 'array'],
            'categoria.slug' => ['required_with:categoria', 'string', 'regex:/^[a-z0-9-]+$/', 'max:120'],
            'categoria.nombre' => ['required_with:categoria', 'string', 'max:160'],

            'pasos' => ['sometimes', 'array', 'max:30'],
            'pasos.*.orden' => ['required', 'integer', 'min:1'],
            'pasos.*.titulo' => ['required', 'string', 'max:500'],
            'pasos.*.descripcion' => ['sometimes', 'nullable', 'string', 'max:5000'],

            'requisitos' => ['sometimes', 'array', 'max:150'],
            'requisitos.*.tipo' => ['required', Rule::in(self::REQUISITO_TIPOS)],
            'requisitos.*.descripcion' => ['required', 'string', 'max:2000'],
            'requisitos.*.obligatorio' => ['sometimes', 'nullable', 'boolean'],
            'requisitos.*.nota' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'requisitos.*.url' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'requisitos.*.orden' => ['sometimes', 'nullable', 'integer', 'min:1'],
            // El paso al que pertenece el requisito, por su número de orden; nulo si es general.
            'requisitos.*.paso' => ['sometimes', 'nullable', 'integer', 'min:1'],

            'documentos' => ['sometimes', 'array', 'max:100'],
            'documentos.*.nombre' => ['required', 'string', 'max:300'],
            'documentos.*.url' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'documentos.*.formato' => ['sometimes', 'nullable', 'string', 'max:30'],

            'puntos_atencion' => ['sometimes', 'array', 'max:500'],
            'puntos_atencion.*.nombre' => ['required', 'string', 'max:160'],
            'puntos_atencion.*.direccion' => ['required', 'string', 'max:500'],
            'puntos_atencion.*.horario' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'puntos_atencion.*.telefono' => ['sometimes', 'nullable', 'string', 'max:40'],
            'puntos_atencion.*.municipio' => ['sometimes', 'nullable', 'string', 'max:120'],
            'puntos_atencion.*.departamento' => ['sometimes', 'nullable', 'string', 'max:120'],
            'puntos_atencion.*.latitud' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'puntos_atencion.*.longitud' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],

            'normativa' => ['sometimes', 'array', 'max:100'],
            'normativa.*.tipo' => ['required', 'string', 'max:200'],
            'normativa.*.numero' => ['sometimes', 'nullable', 'string', 'max:200'],
            'normativa.*.anio' => ['sometimes', 'nullable', 'integer', 'between:1800,2200'],
            'normativa.*.articulos' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'normativa.*.url' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],

            'canales_consulta_estado' => ['sometimes', 'array', 'max:20'],
            'canales_consulta_estado.*.canal' => ['required', Rule::in(['sede', 'presencial', 'telefonico', 'web', 'correo', 'otro'])],
            'canales_consulta_estado.*.habilitado' => ['required', 'boolean'],
            'canales_consulta_estado.*.nombre' => ['sometimes', 'nullable', 'string', 'max:160'],
            'canales_consulta_estado.*.url' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'canales_consulta_estado.*.correo' => ['sometimes', 'nullable', 'email', 'max:240'],
            'canales_consulta_estado.*.telefono' => ['sometimes', 'nullable', 'string', 'max:40'],
            'canales_consulta_estado.*.horario' => ['sometimes', 'nullable', 'string', 'max:1000'],

            'solicitantes' => ['sometimes', 'array', 'max:20'],
            'solicitantes.*' => ['string', 'max:30'],
        ];
    }

    /**
     * Solo lo que se validó, sin `slug` ni `codigo`: no se cambian a mano.
     *
     * @return array<string, mixed>
     */
    public function datos(): array
    {
        return $this->validated();
    }
}
