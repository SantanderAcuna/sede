<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\CanalInicioTramite;
use App\Enums\CostoTramite;
use App\Enums\ModalidadTramite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Valida la creación y la edición de un trámite desde el panel.
 *
 * Crear exige solo el nombre (un trámite puede existir incompleto, como borrador); publicarlo
 * exige lo demás y lo comprueba el servicio. Editar acepta cualquier subconjunto. Las formas de
 * cada lista repiten las del contrato (`TramiteItem`). `slug` y `codigo` no se aceptan: no se
 * cambian a mano (el slug es el enlace de la ficha).
 */
final class GuardarTramiteRequest extends FormRequest
{
    private const REQUISITO_TIPOS = ['documento', 'verificacion_institucional', 'solicitud', 'formulario', 'pago'];

    private const AUDIENCIAS = ['Ciudadano', 'Extranjeros', 'Organizaciones', 'Instituciones o dependencias públicas'];

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
        $requisito = static fn (string $base): array => [
            "{$base}.tipo" => ['required', Rule::in(self::REQUISITO_TIPOS)],
            // La fuente oficial publica requisitos sin descripción (solo el nombre del documento o del
            // formulario): exigirla rechazaría al reenviar un trámite sin tocarlo.
            "{$base}.descripcion" => ['sometimes', 'nullable', 'string', 'max:2000'],
            "{$base}.obligatorio" => ['sometimes', 'nullable', 'boolean'],
            "{$base}.cantidad" => ['sometimes', 'nullable', 'integer', 'min:0'],
            "{$base}.unidad_cantidad" => ['sometimes', 'nullable', 'string', 'max:40'],
            "{$base}.nota" => ['sometimes', 'nullable', 'string', 'max:2000'],
            "{$base}.canal" => ['sometimes', 'nullable', Rule::in(['web', 'presencial', 'correo', 'telefonico'])],
            "{$base}.url" => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            "{$base}.correo" => ['sometimes', 'nullable', 'email', 'max:240'],
            "{$base}.orden" => ['sometimes', 'nullable', 'integer', 'min:1'],
            "{$base}.tipos_audiencia" => ['sometimes', 'array'],
            "{$base}.tipos_audiencia.*" => [Rule::in(self::AUDIENCIAS)],
            "{$base}.documento" => ['sometimes', 'nullable', 'string', 'max:1000'],
            "{$base}.formulario" => ['sometimes', 'nullable', 'string', 'max:1000'],
            "{$base}.formulario_url" => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            "{$base}.url_pago" => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            "{$base}.canales" => ['sometimes', 'array', 'max:10'],
            "{$base}.canales.*.tipo" => ['required', Rule::in(['correo', 'presencial', 'web', 'telefonico'])],
            "{$base}.canales.*.email" => ['sometimes', 'nullable', 'email', 'max:240'],
            "{$base}.canales.*.url" => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            "{$base}.pago" => ['sometimes', 'array', 'max:20'],
            "{$base}.pago.*.valor" => ['sometimes', 'nullable', 'max:30'],
            "{$base}.pago.*.moneda" => ['sometimes', 'nullable', 'string', 'max:20'],
            // La fuente usa sus propios códigos (SMLMV, SMLDV, AVALUO_LIQUIDACION…): se conservan tal cual.
            "{$base}.pago.*.tipo_valor" => ['sometimes', 'nullable', 'string', 'max:30'],
            "{$base}.pago.*.descripcion" => ['sometimes', 'nullable', 'string', 'max:2000'],
            "{$base}.cuentas" => ['sometimes', 'array', 'max:20'],
            "{$base}.cuentas.*.banco" => ['sometimes', 'nullable', 'string', 'max:120'],
            "{$base}.cuentas.*.tipo" => ['sometimes', 'nullable', 'string', 'max:40'],
            "{$base}.cuentas.*.numero" => ['sometimes', 'nullable', 'string', 'max:40'],
            "{$base}.cuentas.*.titular" => ['sometimes', 'nullable', 'string', 'max:120'],
        ];

        return [
            'nombre' => [$crear ? 'required' : 'sometimes', 'string', 'min:3', 'max:200'],
            'resumen' => ['sometimes', ...$texto(500)],
            'url_ficha_gov_co' => ['sometimes', ...$url],
            'modalidad' => ['sometimes', 'nullable', Rule::enum(ModalidadTramite::class)],
            'tiene_costo' => ['sometimes', 'nullable', Rule::enum(CostoTramite::class)],
            'tiempo_solucion_dias' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:36500'],
            'canal_inicio' => ['sometimes', 'nullable', Rule::enum(CanalInicioTramite::class)],
            'url_inicio' => ['sometimes', ...$url],
            'url_manual_tramite_en_linea' => ['sometimes', ...$url],
            'consulta_estado' => ['sometimes', ...$texto(500)],
            'resultado' => ['sometimes', ...$texto(2000)],
            'producto_final' => ['sometimes', ...$texto(500)],
            'observaciones_resultado' => ['sometimes', ...$texto(2000)],
            'fecha_cualquiera' => ['sometimes', 'nullable', 'boolean'],
            'cuando_se_puede_realizar' => ['sometimes', ...$texto(1000)],
            'url_calendario' => ['sometimes', ...$url],
            'palabras_relacionadas' => ['sometimes', ...$texto(1000)],
            'publicado' => ['sometimes', 'boolean'],

            'costo' => ['sometimes', 'nullable', 'array'],
            'costo.tiene_costo' => ['sometimes', 'nullable', Rule::enum(CostoTramite::class)],
            'costo.tipo_valor' => ['sometimes', 'nullable', Rule::in(['fijo', 'smlv', 'avaluo_liquidacion', 'rango'])],
            'costo.valor' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'costo.moneda' => ['sometimes', 'nullable', 'string', 'max:20'],
            'costo.url_pago' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'costo.descripcion' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'costo.cuentas' => ['sometimes', 'array', 'max:20'],
            // Dos formas conviven en los datos: la del contrato (entidad, numero_cuenta…) y la de la
            // fuente (banco, numero…). Se aceptan ambas; la fuente publica cuentas a medias y se conservan.
            'costo.cuentas.*.entidad' => ['sometimes', 'nullable', 'string', 'max:120'],
            'costo.cuentas.*.banco' => ['sometimes', 'nullable', 'string', 'max:120'],
            'costo.cuentas.*.tipo_cuenta' => ['sometimes', 'nullable', 'string', 'max:40'],
            'costo.cuentas.*.tipo' => ['sometimes', 'nullable', 'string', 'max:40'],
            'costo.cuentas.*.titular' => ['sometimes', 'nullable', 'string', 'max:120'],
            'costo.cuentas.*.numero_cuenta' => ['sometimes', 'nullable', 'string', 'max:40'],
            'costo.cuentas.*.numero' => ['sometimes', 'nullable', 'string', 'max:40'],
            'costo.cuentas.*.codigo_recaudo' => ['sometimes', 'nullable', 'string', 'max:40'],

            'categoria' => ['sometimes', 'nullable', 'array'],
            'categoria.slug' => ['required_with:categoria', 'string', 'regex:/^[a-z0-9-]+$/', 'max:120'],
            'categoria.nombre' => ['required_with:categoria', 'string', 'max:160'],

            'requisitos' => ['sometimes', 'array', 'max:100'],
            ...$requisito('requisitos.*'),

            'documentos' => ['sometimes', 'array', 'max:100'],
            'documentos.*.nombre' => ['required', 'string', 'max:300'],
            'documentos.*.url' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'documentos.*.formato' => ['sometimes', 'nullable', 'string', 'max:30'],

            'momentos' => ['sometimes', 'array', 'max:30'],
            'momentos.*.orden' => ['required', 'integer', 'min:1'],
            'momentos.*.titulo' => ['required', 'string', 'max:500'],
            'momentos.*.descripcion' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'momentos.*.requisitos' => ['sometimes', 'array', 'max:50'],
            ...$requisito('momentos.*.requisitos.*'),

            'audiencias' => ['sometimes', 'array', 'max:200'],
            'audiencias.*.grupo' => ['sometimes', 'nullable', 'string', 'max:60'],
            'audiencias.*.nombre' => ['required', 'string', 'max:120'],
            'audiencias.*.descripcion' => ['sometimes', 'nullable', 'string', 'max:1000'],

            'perfiles' => ['sometimes', 'array', 'max:30'],
            'perfiles.*' => ['string', 'max:60'],
            'medios_resultado' => ['sometimes', 'array', 'max:30'],
            'medios_resultado.*' => ['string', 'max:60'],

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
            'normativa.*.url_descarga' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'normativa.*.archivo' => ['sometimes', 'nullable', 'string', 'max:300'],

            'cuentas' => ['sometimes', 'array', 'max:20'],
            'cuentas.*.banco' => ['sometimes', 'nullable', 'string', 'max:120'],
            'cuentas.*.tipo' => ['sometimes', 'nullable', 'string', 'max:40'],
            'cuentas.*.numero' => ['sometimes', 'nullable', 'string', 'max:40'],
            'cuentas.*.titular' => ['sometimes', 'nullable', 'string', 'max:120'],

            'canales_consulta_estado' => ['sometimes', 'array', 'max:20'],
            'canales_consulta_estado.*.canal' => ['required', Rule::in(['sede', 'presencial', 'telefonico', 'web', 'correo', 'otro'])],
            'canales_consulta_estado.*.habilitado' => ['required', 'boolean'],
            'canales_consulta_estado.*.nombre' => ['sometimes', 'nullable', 'string', 'max:160'],
            'canales_consulta_estado.*.url' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'canales_consulta_estado.*.correo' => ['sometimes', 'nullable', 'email', 'max:240'],
            'canales_consulta_estado.*.telefono' => ['sometimes', 'nullable', 'string', 'max:40'],
            'canales_consulta_estado.*.horario' => ['sometimes', 'nullable', 'string', 'max:1000'],

            'seguimiento' => ['sometimes', 'nullable', 'array'],
            'seguimiento.telefono' => ['sometimes', 'array', 'max:20'],
            'seguimiento.telefono.*.numero' => ['sometimes', 'nullable', 'string', 'max:40'],
            'seguimiento.telefono.*.extension' => ['sometimes', 'nullable', 'string', 'max:20'],
            'seguimiento.telefono.*.horario' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'seguimiento.email' => ['sometimes', 'array', 'max:20'],
            'seguimiento.email.*.email' => ['sometimes', 'nullable', 'email', 'max:240'],
            'seguimiento.web' => ['sometimes', 'array', 'max:20'],
            'seguimiento.web.*.url' => ['sometimes', 'nullable', 'url:http,https', 'max:500'],
            'seguimiento.web.*.nombre' => ['sometimes', 'nullable', 'string', 'max:160'],
            'seguimiento.presencial' => ['sometimes', 'array', 'max:500'],
            'seguimiento.presencial.*.nombre' => ['required', 'string', 'max:160'],
            'seguimiento.presencial.*.direccion' => ['required', 'string', 'max:500'],
            'seguimiento.presencial.*.horario' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'seguimiento.presencial.*.telefono' => ['sometimes', 'nullable', 'string', 'max:40'],
            'seguimiento.presencial.*.departamento' => ['sometimes', 'nullable', 'string', 'max:120'],
            // La fuente entrega el municipio como texto o como un objeto pequeño (código DANE, nombre…).
            'seguimiento.presencial.*.municipio' => ['sometimes', 'nullable', self::textoOMapaPequeno(...)],
            'seguimiento.presencial.*.latitud' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'seguimiento.presencial.*.longitud' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /** Texto corto, o un mapa de hasta 10 claves con valores escalares cortos. */
    private static function textoOMapaPequeno(string $atributo, mixed $valor, \Closure $falla): void
    {
        if (is_string($valor)) {
            if (mb_strlen($valor) > 120) {
                $falla('El campo :attribute es demasiado largo.');
            }

            return;
        }
        if (! is_array($valor) || count($valor) > 10) {
            $falla('El campo :attribute no tiene un formato válido.');

            return;
        }
        foreach ($valor as $clave => $dato) {
            if (! is_string($clave) || mb_strlen($clave) > 60 || (! is_scalar($dato) && $dato !== null) || (is_string($dato) && mb_strlen($dato) > 120)) {
                $falla('El campo :attribute no tiene un formato válido.');

                return;
            }
        }
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
