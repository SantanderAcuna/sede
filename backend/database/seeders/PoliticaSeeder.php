<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Politica;
use Illuminate\Database\Seeder;

/**
 * Las cinco políticas de la sede, con TEXTO DE EJEMPLO (T-20, H-21).
 *
 * Son un punto de partida completo —cada política trae todos sus apartados
 * mínimos— para que la sede nunca publique una página vacía y para que la
 * Entidad edite desde el panel en lugar de escribir desde cero. **No son texto
 * jurídico aprobado**: por eso nacen con `es_ejemplo = true`, sin acto
 * administrativo, y el sitio lo avisa. Lo que sea institucional y TIC no pueda
 * saber va entre corchetes como «[por definir: ...]»; el panel no deja quitar la
 * marca de ejemplo mientras queden corchetes.
 *
 * **Idempotente y respetuoso con la edición**: sólo crea las que faltan. Si la
 * Entidad ya cargó su texto, un despliegue no lo pisa.
 */
final class PoliticaSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->ejemplos() as $slug => $politica) {
            Politica::query()->firstOrCreate(['slug' => $slug], $politica + [
                'version' => 1,
                'vigente_desde' => null,
                'acto_tipo' => null,
                'acto_numero' => null,
                'acto_fecha' => null,
                'es_ejemplo' => true,
            ]);
        }
    }

    /**
     * @param  list<string>  $parrafos
     * @return array{id: string, titulo: string, parrafos: list<string>}
     */
    private static function seccion(string $id, string $titulo, array $parrafos): array
    {
        return ['id' => $id, 'titulo' => $titulo, 'parrafos' => $parrafos];
    }

    /**
     * @return array<string, array{nombre: string, secciones: list<array{id: string, titulo: string, parrafos: list<string>}>}>
     */
    private function ejemplos(): array
    {
        $contacto = 'Alcaldía Distrital de Santa Marta, Calle 14 No. 2-49, Palacio Municipal. '
            .'Correo institucional: atencionalciudadano@santamarta.gov.co.';
        $responsable = 'El responsable del tratamiento es la Alcaldía Distrital de Santa Marta '
            .'([por definir: dependencia y cargo del responsable]). Puede contactarlo en: '.$contacto;

        return [
            'terminos-y-condiciones-de-uso' => [
                'nombre' => 'Términos y condiciones de uso',
                'secciones' => [
                    self::seccion('condiciones-y-alcance', 'Condiciones y alcance de uso', [
                        'Estos términos regulan el uso de la Sede Electrónica de la Alcaldía Distrital de Santa Marta. Al usar la sede, la persona acepta las condiciones aquí descritas.',
                        'La sede ofrece información institucional, trámites y servicios. Algunos servicios pueden tener condiciones adicionales, que se indican en cada uno.',
                    ]),
                    self::seccion('limites-de-uso', 'Límites de uso', [
                        'La sede debe usarse de buena fe y para los fines para los que fue dispuesta. No está permitido intentar acceder a áreas restringidas, alterar su funcionamiento, extraer información de forma masiva o usarla para actividades ilícitas.',
                    ]),
                    self::seccion('derechos-del-usuario', 'Derechos del usuario', [
                        'Toda persona tiene derecho a acceder a la información pública de la sede, a recibir atención por los canales dispuestos y a que sus datos personales se traten conforme a la política de protección de datos.',
                    ]),
                    self::seccion('deberes-del-usuario', 'Deberes del usuario', [
                        'La persona debe suministrar información veraz, proteger sus credenciales cuando las tenga y abstenerse de suplantar a otras personas.',
                    ]),
                    self::seccion('responsabilidad-de-la-entidad', 'Responsabilidad de la Entidad', [
                        'La Entidad procura que la información publicada sea actual y exacta, y que el sitio esté disponible. Puede haber interrupciones por mantenimiento o por causas ajenas a su control; cuando sean previsibles se anunciarán.',
                        'La sede puede enlazar a sitios de terceros; la Entidad no responde por su contenido ni por sus políticas.',
                    ]),
                    self::seccion('contacto', 'Contacto', [
                        'Para consultas sobre estos términos: '.$contacto,
                    ]),
                    self::seccion('privacidad-y-seguridad', 'Referencia a la política de privacidad y seguridad', [
                        'El tratamiento de datos personales y las medidas de seguridad de la sede se describen en las políticas de Seguridad y privacidad y de Protección y tratamiento de datos personales, disponibles en el pie de página.',
                    ]),
                ],
            ],
            'seguridad-y-privacidad' => [
                'nombre' => 'Seguridad y privacidad',
                'secciones' => [
                    self::seccion('categorias-de-datos', 'Categorías de datos', [
                        'La sede puede tratar datos de identificación y contacto (nombre, documento, correo, teléfono), datos de las solicitudes que la persona presenta y datos técnicos de la visita (por ejemplo, tipo de navegador), estos últimos de forma agregada.',
                        'La sede no solicita datos sensibles salvo que un trámite concreto lo requiera, y en ese caso lo indicará expresamente.',
                    ]),
                    self::seccion('finalidades', 'Finalidades', [
                        'Los datos se usan para atender las solicitudes de la persona, prestar los trámites y servicios, cumplir obligaciones legales de la Entidad y mejorar el servicio. No se usan para fines distintos sin una autorización nueva.',
                    ]),
                    self::seccion('derechos-arco', 'Derechos de acceso, actualización, rectificación, supresión y revocatoria', [
                        'La persona titular puede conocer, actualizar y rectificar sus datos, solicitar la supresión de los que no deban conservarse, revocar la autorización que haya dado y pedir prueba de ella, conforme a la Ley 1581 de 2012.',
                        'Puede ejercer estos derechos por los canales indicados en el apartado de contacto.',
                    ]),
                    self::seccion('responsable', 'Contacto del responsable', [$responsable]),
                    self::seccion('reclamos', 'Mecanismo de reclamo', [
                        'La persona puede presentar su reclamo por escrito a los canales del apartado de contacto, identificándose y describiendo los hechos. La Entidad responderá dentro de los plazos que fija la ley [por definir: plazo y procedimiento internos].',
                        'Si no queda conforme con la respuesta, puede acudir a la Superintendencia de Industria y Comercio.',
                    ]),
                ],
            ],
            'proteccion-y-tratamiento-de-datos-personales' => [
                'nombre' => 'Protección y tratamiento de datos personales',
                'secciones' => [
                    self::seccion('categorias-de-datos', 'Categorías de datos', [
                        'Datos de identificación y contacto, datos de las solicitudes presentadas ante la Entidad y, cuando un trámite lo exija, otros datos que se indicarán en ese trámite. Los datos de menores y los sensibles sólo se tratan cuando la ley lo permite y con las garantías que ella exige.',
                    ]),
                    self::seccion('finalidades', 'Finalidades', [
                        'Gestionar peticiones, quejas, reclamos, sugerencias y denuncias; tramitar los servicios solicitados; comunicar respuestas y notificaciones; cumplir deberes legales y de rendición de cuentas; y elaborar estadísticas sin identificar a las personas [por definir: finalidades específicas aprobadas por la Entidad].',
                    ]),
                    self::seccion('derechos-arco', 'Derechos de acceso, actualización, rectificación, supresión y revocatoria', [
                        'Los titulares tienen derecho a: conocer, actualizar y rectificar sus datos; solicitar prueba de la autorización; ser informados del uso que se les ha dado; presentar quejas ante la Superintendencia de Industria y Comercio; revocar la autorización y solicitar la supresión cuando no se respeten los principios o garantías legales; y acceder gratuitamente a sus datos.',
                    ]),
                    self::seccion('responsable', 'Contacto del responsable', [$responsable]),
                    self::seccion('reclamos', 'Mecanismo de reclamo', [
                        'Las consultas y reclamos se presentan por escrito ante el responsable, con la identificación del titular, la descripción de los hechos y los datos de contacto. La Entidad los atiende en los términos de la Ley 1581 de 2012 [por definir: canal y formulario oficiales].',
                    ]),
                ],
            ],
            'uso-de-cookies' => [
                'nombre' => 'Uso de cookies',
                'secciones' => [
                    self::seccion('cookies-que-usa', 'Qué cookies usa el sitio', [
                        'El sitio no instala cookies para funcionar. Guarda en el navegador, mediante almacenamiento local, dos cosas: la decisión que la persona toma sobre las cookies y sus preferencias de accesibilidad (tamaño de letra y contraste).',
                        'Si la persona acepta la categoría de analítica, el sitio puede cargar un servicio de medición de visitas que usa sus propias tecnologías de almacenamiento. Sin esa aceptación no se carga.',
                    ]),
                    self::seccion('finalidad', 'Finalidad de cada una', [
                        'Necesarias: recordar la decisión sobre cookies; no pueden desactivarse porque sin ellas no se podría respetar esa decisión. Preferencias: recordar los ajustes de accesibilidad. Analítica: contar visitas y entender qué contenidos se consultan, sin identificar a las personas.',
                    ]),
                    self::seccion('duracion', 'Duración', [
                        'La decisión sobre cookies se conserva doce meses, o hasta que cambie esta política, y después se vuelve a preguntar. Las preferencias de accesibilidad se conservan hasta que la persona las cambie o borre los datos del navegador.',
                    ]),
                    self::seccion('consentimiento', 'Cómo se administra el consentimiento', [
                        'Al entrar por primera vez el sitio muestra un aviso con tres opciones: aceptar todas, rechazar las opcionales o elegir por categoría. Nada opcional se activa antes de que la persona acepte.',
                        'Puede cambiar o retirar su decisión en cualquier momento desde «Configurar cookies», en el pie de página. Al retirar la analítica, la medición se detiene de inmediato.',
                        'Para demostrar la decisión, el sitio guarda en el servidor, sin datos que identifiquen a la persona, qué categorías se aceptaron o rechazaron, con qué versión de esta política y en qué fecha.',
                    ]),
                ],
            ],
            'derechos-de-autor-y-uso-sobre-contenidos' => [
                'nombre' => 'Derechos de autor y uso sobre contenidos',
                'secciones' => [
                    self::seccion('titularidad', 'Titularidad de los contenidos', [
                        'Los contenidos de la sede (textos, imágenes, documentos, datos y diseño) pertenecen a la Alcaldía Distrital de Santa Marta o se usan con autorización de sus titulares. Los símbolos y marcas de terceros, como los del Gobierno de Colombia, pertenecen a sus titulares.',
                    ]),
                    self::seccion('reutilizacion', 'Condiciones de reutilización', [
                        'La información pública puede reutilizarse, copiarse y difundirse citando la fuente, sin alterar su sentido y sin sugerir que la Entidad respalda el uso que se haga de ella. No se autoriza el uso de los escudos, logotipos y marcas con fines comerciales o que induzcan a confusión [por definir: condiciones aprobadas por la Entidad].',
                    ]),
                    self::seccion('licencia', 'Licencia que los acompaña', [
                        'Salvo indicación distinta en un contenido concreto, la reutilización se rige por la licencia [por definir: licencia aprobada por la Entidad, por ejemplo una licencia abierta de datos].',
                    ]),
                    self::seccion('como-citar', 'Cómo citarlos', [
                        'Citar: «Alcaldía Distrital de Santa Marta, [título del contenido], [fecha de publicación o consulta], disponible en [dirección de la página]». Si el contenido se modifica, debe indicarse.',
                    ]),
                ],
            ],
        ];
    }
}
