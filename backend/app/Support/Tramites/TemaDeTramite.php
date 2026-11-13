<?php

declare(strict_types=1);

namespace App\Support\Tramites;

use App\Support\Busqueda\TextoBuscable;

/**
 * Los temas con que se organiza el catálogo y la regla que asigna uno de partida a cada
 * trámite existente según las palabras de su nombre.
 *
 * Es una clasificación **inicial y revisable**: el catálogo heredado no traía tema alguno y sin
 * él el filtro «Tema» no serviría de nada. La Entidad la corrige desde el panel; el orden de las
 * reglas importa, porque gana la primera cuyas palabras aparecen en el nombre.
 */
final class TemaDeTramite
{
    public const COMODIN = 'otros-tramites';

    /**
     * Los temas, en el orden en que se muestran.
     *
     * @var list<array{slug: string, nombre: string}>
     */
    private const TEMAS = [
        ['slug' => 'educacion', 'nombre' => 'Educación'],
        ['slug' => 'pensiones-y-prestaciones', 'nombre' => 'Pensiones y prestaciones'],
        ['slug' => 'salud', 'nombre' => 'Salud y servicios funerarios'],
        ['slug' => 'transito-y-movilidad', 'nombre' => 'Tránsito y movilidad'],
        ['slug' => 'juegos-rifas-y-espectaculos', 'nombre' => 'Juegos, rifas y espectáculos'],
        ['slug' => 'hacienda-e-impuestos', 'nombre' => 'Hacienda e impuestos'],
        ['slug' => 'urbanismo-y-construccion', 'nombre' => 'Urbanismo, predios y vivienda'],
        ['slug' => 'ambiente', 'nombre' => 'Ambiente'],
        ['slug' => 'cultura-y-deporte', 'nombre' => 'Cultura y deporte'],
        ['slug' => 'gobierno-y-convivencia', 'nombre' => 'Gobierno y convivencia'],
    ];

    /**
     * Las reglas, en orden de prioridad: gana la primera que coincide.
     *
     * Cada palabra se busca **al comienzo de una palabra del nombre** (`educativ` coincide con
     * «educativos» pero no con «reeducativo»), de modo que «cobranza» no cuenta como «obra» ni
     * «aguardiente» como «agua». Para exigir la palabra completa se le deja un espacio al final
     * (`'obra '`). El orden resuelve los cruces conocidos: las prestaciones de los docentes son
     * prestaciones antes que educación, y una matrícula de vehículo es tránsito, no educación.
     *
     * @var list<array{slug: string, palabras: list<string>}>
     */
    private const REGLAS = [
        ['slug' => 'pensiones-y-prestaciones', 'palabras' => ['pension es', 'pension de', 'pension por', 'pensional', 'pensionad', 'cesantia', 'por muerte', 'auxilio funerario', 'prestacion', 'reliquidacion']],
        ['slug' => 'transito-y-movilidad', 'palabras' => ['vehicul', 'transito', 'licencia de conduccion', 'conductor', 'motocicleta', 'placa ', 'transporte', 'movilidad', 'parqueadero', 'circulacion']],
        ['slug' => 'educacion', 'palabras' => ['docente', 'educacion', 'educativ', 'escolar', 'estudiante', 'colegio', 'matriculas', 'grado academico', 'rector ', 'rectores ', 'trabajo y el desarrollo humano']],
        ['slug' => 'salud', 'palabras' => ['salud', 'farmaceutic', 'sanitari', 'medic', 'vacuna', 'estetica', 'cadaver', 'exhumacion', 'inhumacion', 'cremacion', 'funerari']],
        ['slug' => 'juegos-rifas-y-espectaculos', 'palabras' => ['rifa', 'juegos de suerte', 'juegos promocionales', 'sorteo', 'pirotecn', 'espectaculo', 'azar']],
        ['slug' => 'gobierno-y-convivencia', 'palabras' => ['mercancia']],
        ['slug' => 'hacienda-e-impuestos', 'palabras' => ['impuesto', 'predial', 'industria y comercio', 'tributari', 'contribucion', 'paz y salvo', 'sobretasa', 'rentas', 'valorizacion', 'saldo', 'declaraciones', 'recibos de pago']],
        ['slug' => 'urbanismo-y-construccion', 'palabras' => ['construccion', 'urbanis', 'plan parcial', 'planes parciales', 'regularizacion', 'curaduria', 'predio', 'inmueble', 'catastral', 'linderos', 'cesion', 'obra ', 'obras ', 'espacio publico', 'nomenclatura', 'uso del suelo', 'estratificacion', 'vivienda', 'propiedad horizontal', 'asentamiento', 'piscina', 'areas ', 'planos', 'ocupacion']],
        ['slug' => 'ambiente', 'palabras' => ['ambiental', 'vertimiento', 'forestal', 'arbol', 'fauna', 'flora', 'residuos', 'ruido', 'agua ', 'aguas ', 'publicidad exterior']],
        ['slug' => 'cultura-y-deporte', 'palabras' => ['deportiv', 'cultural', 'artes escenicas', 'club ', 'clubes ', 'biblioteca']],
        ['slug' => 'gobierno-y-convivencia', 'palabras' => ['convivencia', 'policia', 'inspeccion', 'comisaria', 'personeria', 'reunion', 'manifestacion', 'canina', 'perro', 'establecimiento de comercio', 'juridica', 'victima', 'sisben', 'subsidio', 'adulto mayor', 'comunal', 'dignatarios', 'representacion legal', 'residencia', 'socioeconomico', 'supervision ']],
    ];

    /** @return list<array{slug: string, nombre: string}> */
    public static function catalogo(): array
    {
        $temas = self::TEMAS;
        $temas[] = ['slug' => self::COMODIN, 'nombre' => 'Otros trámites'];

        return array_values($temas);
    }

    public static function slugPara(string $nombre): string
    {
        $limpio = preg_replace('/[^a-z0-9]+/', ' ', TextoBuscable::normalizar($nombre)) ?? '';
        $texto = ' '.trim($limpio).' ';
        foreach (self::REGLAS as $regla) {
            foreach ($regla['palabras'] as $palabra) {
                if (str_contains($texto, ' '.$palabra)) {
                    return $regla['slug'];
                }
            }
        }

        return self::COMODIN;
    }
}
