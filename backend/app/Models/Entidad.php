<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Entidad institucional titular de la sede electrónica.
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $sigla
 * @property string|null $nit
 * @property string|null $direccion
 * @property string|null $municipio
 * @property string|null $departamento
 * @property string|null $pais
 * @property string|null $telefono
 * @property string|null $linea_atencion
 * @property string|null $linea_gratuita
 * @property string|null $linea_anticorrupcion
 * @property string|null $correo_atencion
 * @property string|null $correo_notificaciones_judiciales
 * @property string|null $horario
 * @property string|null $codigo_postal
 * @property string|null $dominio
 * @property string|null $logo
 * @property list<array{red:string,url:string}> $redes
 * @property list<array{slug:string,nombre:string}> $politicas
 * @property list<string> $datos_por_confirmar
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Entidad extends Model
{
    protected $table = 'entidads';

    /** @var list<string> */
    protected $fillable = [
        'nombre',
        'sigla',
        'nit',
        'direccion',
        'municipio',
        'departamento',
        'pais',
        'telefono',
        'linea_atencion',
        'linea_gratuita',
        'linea_anticorrupcion',
        'correo_atencion',
        'correo_notificaciones_judiciales',
        'horario',
        'codigo_postal',
        'dominio',
        'logo',
        'redes',
        'politicas',
        'datos_por_confirmar',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'redes' => 'array',
            'politicas' => 'array',
            'datos_por_confirmar' => 'array',
        ];
    }
}
