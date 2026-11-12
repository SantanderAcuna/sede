<?php

declare(strict_types=1);

namespace App\Support\Tramites;

use App\Support\Busqueda\TextoBuscable;

/**
 * Los temas con que se organiza el catálogo y la regla que asigna uno de partida a cada
 * trámite existente según las palabras de su nombre.
 *
 * Es una clasificación **inicial y revisable**: el catálogo heredado no traía tema alguno y sin
 * él el filtro «Tema» no serviría de nada. La Entidad la corrige desde el panel; el orden de la
 * lista importa, porque gana el primer tema cuyas palabras aparecen en el nombre.
 */
final class TemaDeTramite
{
    public const COMODIN = 'otros-tramites';

    /** @var list<array{slug: string, nombre: string, palabras: list<string>}> */
    private const TEMAS = [
        ['slug' => 'educacion', 'nombre' => 'Educación', 'palabras' => ['docente', 'educacion', 'educativ', 'escolar', 'estudiante', 'colegio', 'matricula', 'grado academico', 'rector', 'trabajo y el desarrollo humano']],
        ['slug' => 'pensiones-y-prestaciones', 'nombre' => 'Pensiones y prestaciones', 'palabras' => ['pension', 'cesantia', 'por muerte', 'prestacion', 'reliquidacion']],
        ['slug' => 'salud', 'nombre' => 'Salud y servicios funerarios', 'palabras' => ['salud', 'farmaceutic', 'sanitari', 'medic', 'vacuna', 'estetica', 'cadaver', 'exhumacion', 'inhumacion', 'cremacion', 'funerari']],
        ['slug' => 'transito-y-movilidad', 'nombre' => 'Tránsito y movilidad', 'palabras' => ['vehicul', 'transito', 'licencia de conduccion', 'conductor', 'motocicleta', 'placa', 'transporte', 'movilidad', 'parqueadero', 'circulacion vial']],
        ['slug' => 'juegos-rifas-y-espectaculos', 'nombre' => 'Juegos, rifas y espectáculos', 'palabras' => ['rifa', 'juegos de suerte', 'juegos promocionales', 'sorteo', 'pirotecn', 'espectaculo', 'azar']],
        ['slug' => 'hacienda-e-impuestos', 'nombre' => 'Hacienda e impuestos', 'palabras' => ['impuesto', 'predial', 'industria y comercio', 'tributari', 'contribucion', 'paz y salvo', 'sobretasa', 'rentas', 'valorizacion', 'avaluo', 'saldo', 'declaraciones', 'recibos de pago']],
        ['slug' => 'urbanismo-y-construccion', 'nombre' => 'Urbanismo, predios y vivienda', 'palabras' => ['construccion', 'urbanis', 'plan parcial', 'planes parciales', 'regularizacion', 'curaduria', 'predio', 'inmueble', 'catastral', 'linderos', 'cesion', 'obra', 'espacio publico', 'nomenclatura', 'uso del suelo', 'estratificacion', 'vivienda', 'propiedad horizontal', 'asentamiento', 'piscina', 'areas', 'planos', 'ocupacion']],
        ['slug' => 'ambiente', 'nombre' => 'Ambiente', 'palabras' => ['ambiental', 'vertimiento', 'forestal', 'arbol', 'fauna', 'flora', 'residuos', 'ruido', 'agua', 'publicidad exterior']],
        ['slug' => 'cultura-y-deporte', 'nombre' => 'Cultura y deporte', 'palabras' => ['deportiv', 'cultural', 'artes escenicas', 'club', 'biblioteca']],
        ['slug' => 'gobierno-y-convivencia', 'nombre' => 'Gobierno y convivencia', 'palabras' => ['convivencia', 'policia', 'inspeccion', 'comisaria', 'personeria', 'reunion', 'manifestacion', 'canina', 'perro', 'establecimiento de comercio', 'juridica', 'victima', 'sisben', 'subsidio', 'adulto mayor', 'comunal', 'dignatarios', 'representacion legal', 'residencia', 'socioeconomico', 'supervision']],
    ];

    /** @return list<array{slug: string, nombre: string}> */
    public static function catalogo(): array
    {
        $temas = array_map(static fn (array $t): array => ['slug' => $t['slug'], 'nombre' => $t['nombre']], self::TEMAS);
        $temas[] = ['slug' => self::COMODIN, 'nombre' => 'Otros trámites'];

        return array_values($temas);
    }

    public static function slugPara(string $nombre): string
    {
        $limpio = preg_replace('/[^a-z0-9]+/', ' ', TextoBuscable::normalizar($nombre)) ?? '';
        $texto = ' '.trim($limpio).' ';
        foreach (self::TEMAS as $tema) {
            foreach ($tema['palabras'] as $palabra) {
                if (str_contains($texto, $palabra)) {
                    return $tema['slug'];
                }
            }
        }

        return self::COMODIN;
    }
}
