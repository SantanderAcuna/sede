<?php

declare(strict_types=1);

namespace App\Services\Politicas;

use App\Models\Politica;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;
use ZipArchive;

/**
 * Genera las descargas de una política en formato abierto (T-20, AC-20.5):
 * ODT (OpenDocument, Ley 1712 de 2014: formatos abiertos) y PDF/A-2b, el
 * formato de archivo a largo plazo.
 */
final class PoliticaDocumentos
{
    public const TIPO_ODT = 'application/vnd.oasis.opendocument.text';

    public const TIPO_PDF = 'application/pdf';

    /**
     * Los pares «etiqueta: valor» que abren el documento.
     *
     * @return array<string, string>
     */
    private function datosDelDocumento(Politica $politica): array
    {
        $acto = trim(implode(' ', array_filter([
            $politica->acto_tipo,
            $politica->acto_numero !== null ? 'N.º '.$politica->acto_numero : null,
            $politica->acto_fecha !== null ? 'del '.$politica->acto_fecha->toDateString() : null,
        ], static fn (?string $parte): bool => $parte !== null && $parte !== '')));

        return [
            'Entidad' => 'Alcaldía Distrital de Santa Marta',
            'Versión' => (string) $politica->version,
            'Vigente desde' => $politica->vigente_desde?->toDateString() ?? 'Sin fecha definida',
            'Acto administrativo' => $acto !== '' ? $acto : 'Sin acto administrativo',
            'Estado' => $politica->es_ejemplo ? 'Texto de ejemplo, pendiente de aprobación por la Entidad' : 'Texto aprobado',
        ];
    }

    private static function xml(string $texto): string
    {
        return htmlspecialchars($texto, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function odt(Politica $politica): string
    {
        $cuerpo = '<text:h text:outline-level="1">'.self::xml($politica->nombre).'</text:h>';
        foreach ($this->datosDelDocumento($politica) as $etiqueta => $valor) {
            $cuerpo .= '<text:p>'.self::xml($etiqueta.': '.$valor).'</text:p>';
        }
        foreach ($politica->secciones as $seccion) {
            $cuerpo .= '<text:h text:outline-level="2">'.self::xml($seccion['titulo']).'</text:h>';
            foreach ($seccion['parrafos'] as $parrafo) {
                $cuerpo .= '<text:p>'.self::xml($parrafo).'</text:p>';
            }
        }

        $contenido = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            .'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" office:version="1.2">'
            .'<office:body><office:text>'.$cuerpo.'</office:text></office:body></office:document-content>';

        $meta = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<office:document-meta xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" office:version="1.2">'
            .'<office:meta><dc:title>'.self::xml($politica->nombre).'</dc:title>'
            .'<dc:language>es-CO</dc:language>'
            .'<meta:initial-creator>Alcaldía Distrital de Santa Marta</meta:initial-creator></office:meta></office:document-meta>';

        $manifiesto = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<manifest:manifest xmlns:manifest="urn:oasis:names:tc:opendocument:xmlns:manifest:1.0" manifest:version="1.2">'
            .'<manifest:file-entry manifest:full-path="/" manifest:version="1.2" manifest:media-type="'.self::TIPO_ODT.'"/>'
            .'<manifest:file-entry manifest:full-path="content.xml" manifest:media-type="text/xml"/>'
            .'<manifest:file-entry manifest:full-path="meta.xml" manifest:media-type="text/xml"/>'
            .'</manifest:manifest>';

        $ruta = tempnam(sys_get_temp_dir(), 'odt');
        if ($ruta === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del ODT.');
        }

        try {
            $zip = new ZipArchive;
            if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No se pudo abrir el archivo ODT para escribirlo.');
            }
            // `mimetype` va primero y SIN comprimir: así lo exige el formato para que
            // cualquier programa reconozca el tipo leyendo los primeros bytes.
            $zip->addFromString('mimetype', self::TIPO_ODT);
            $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
            $zip->addFromString('content.xml', $contenido);
            $zip->addFromString('meta.xml', $meta);
            $zip->addFromString('META-INF/manifest.xml', $manifiesto);
            $zip->close();

            $binario = file_get_contents($ruta);
            if ($binario === false) {
                throw new RuntimeException('No se pudo leer el ODT generado.');
            }

            return $binario;
        } finally {
            @unlink($ruta);
        }
    }

    public function pdf(Politica $politica): string
    {
        $e = static fn (string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');

        $html = '<h1>'.$e($politica->nombre).'</h1><table>';
        foreach ($this->datosDelDocumento($politica) as $etiqueta => $valor) {
            $html .= '<tr><th>'.$e($etiqueta).'</th><td>'.$e($valor).'</td></tr>';
        }
        $html .= '</table>';
        foreach ($politica->secciones as $seccion) {
            $html .= '<h2>'.$e($seccion['titulo']).'</h2>';
            foreach ($seccion['parrafos'] as $parrafo) {
                $html .= '<p>'.$e($parrafo).'</p>';
            }
        }

        $temporal = storage_path('app/mpdf');
        if (! is_dir($temporal)) {
            mkdir($temporal, 0775, true);
        }

        // PDF/A exige fuentes incrustadas: se usa DejaVu, que mPDF trae, y no las
        // fuentes base del PDF, que no se incrustan.
        //
        // Es PDF/A-2b y no el 1b que mPDF usa por defecto: veraPDF rechazó el 1b
        // (ISO 19005-1, 6.3.5) porque mPDF no escribe el CIDSet que exige para las
        // fuentes TrueType incrustadas; el 2b lo hace opcional y valida.
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $temporal,
            'default_font' => 'dejavusans',
            'PDFA' => true,
            'PDFAauto' => true,
            'PDFAversion' => '2-B',
        ]);
        $mpdf->SetTitle($politica->nombre);
        $mpdf->SetAuthor('Alcaldía Distrital de Santa Marta');
        $mpdf->SetCreator('Sede Electrónica');
        $mpdf->SetSubject('Política de la Sede Electrónica, versión '.$politica->version);
        $mpdf->SetDisplayMode('fullpage');
        $mpdf->WriteHTML(
            '<style>body{font-size:11pt;line-height:1.4}h1{font-size:18pt}h2{font-size:13pt;margin-top:14pt}'
            .'th{text-align:left;padding-right:10pt}td,th{font-size:10pt}</style>'.$html
        );

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
