<?php

declare(strict_types=1);

/**
 * El banco de consultas con errata de la búsqueda (AC-22.3 y AC-22.8).
 *
 * Son 30 consultas: las 10 de la primera entrega y 20 más escritas al calibrar la
 * entrega 2. Cada entrada es lo que un ciudadano podría escribir con UNA errata típica del
 * español —ese/ce/ci/z, h muda, letra omitida o cambiada— y los trámites del
 * catálogo oficial que cuentan como acierto. Los aciertos se identifican por el
 * **código SUIT** del trámite (`T…`), que no cambia cuando GOV.CO actualiza un
 * nombre; nunca por una posición ni por un texto.
 *
 * **Límite declarado.** Lo escribió quien tenía el catálogo a la vista, así que es
 * optimista: mide cuánto de la tolerancia es técnicamente posible, no cómo
 * escriben los ciudadanos. Se complementa con la lista de consultas reales que la
 * Entidad pueda aportar (ver `docs/busqueda.md`). Las consultas son de una o de
 * varias palabras a propósito: con una sola palabra la similitud baja mucho.
 *
 * @return list<array{consulta: string, esperado: list<string>, errata: string}>
 */
return [
    ['consulta' => 'sertificado de residencia', 'esperado' => ['T66256'], 'errata' => 'c→s'],
    ['consulta' => 'impuesto predial unifcado', 'esperado' => ['T2621'], 'errata' => 'letra omitida'],
    ['consulta' => 'matricula de vehiculos automotres', 'esperado' => ['T65676'], 'errata' => 'letra omitida'],
    ['consulta' => 'vacunacion antirabica', 'esperado' => ['T28666'], 'errata' => 'sin tildes (no es errata: control)'],
    ['consulta' => 'esterilisacion canina', 'esperado' => ['T66139'], 'errata' => 'z→s, y forma distinta del nombre oficial'],
    ['consulta' => 'pension de jubilasion', 'esperado' => ['T28611', 'T28612'], 'errata' => 'c→s'],
    ['consulta' => 'asignacion de nomenclatora', 'esperado' => ['T65993'], 'errata' => 'letra cambiada'],
    ['consulta' => 'englobe y desenglove', 'esperado' => ['T87633'], 'errata' => 'b→v'],
    ['consulta' => 'licencia de exumacion', 'esperado' => ['T41351'], 'errata' => 'h muda omitida'],
    ['consulta' => 'estratificasion socioeconomica', 'esperado' => ['T65992'], 'errata' => 'c→s; la palabra está en el resumen, no en el título'],
    ['consulta' => 'impuesto predial', 'esperado' => ['T2621', 'T2636'], 'errata' => 'sin errata (control)'],
    ['consulta' => 'cremasion de cadaveres', 'esperado' => ['T41353'], 'errata' => 'c→s'],
    ['consulta' => 'inhumasion', 'esperado' => ['T66456'], 'errata' => 'c→s'],
    ['consulta' => 'exumacion', 'esperado' => ['T41351'], 'errata' => 'h muda omitida'],
    ['consulta' => 'pico y plaka', 'esperado' => ['T89270'], 'errata' => 'c→k'],
    ['consulta' => 'espectaculos publicos', 'esperado' => ['T40684', 'T41140', 'T41309', 'T2676', 'T66171'], 'errata' => 'sin tildes (no es errata: control)'],
    ['consulta' => 'rifas', 'esperado' => ['T40900', 'T41356', 'T40869', 'T65960'], 'errata' => 'sin errata (control)'],
    ['consulta' => 'certificdo catastral', 'esperado' => ['T87648'], 'errata' => 'letra omitida'],
    ['consulta' => 'avaluo catastral', 'esperado' => ['T87636', 'T87635'], 'errata' => 'sin tildes (no es errata: control)'],
    ['consulta' => 'duplicado de plaka', 'esperado' => ['T65678'], 'errata' => 'c→k'],
    ['consulta' => 'duplicado licencia de tansito', 'esperado' => ['T65684'], 'errata' => 'letra omitida'],
    ['consulta' => 'matricula de vehiculo', 'esperado' => ['T65676'], 'errata' => 'sin tildes (no es errata: control)'],
    ['consulta' => 'nomenclatura', 'esperado' => ['T65993'], 'errata' => 'sin errata (control)'],
    ['consulta' => 'publicidad esterior', 'esperado' => ['T33046', 'T40942'], 'errata' => 'x→s'],
    ['consulta' => 'cesantias', 'esperado' => ['T28632', 'T28596', 'T28732'], 'errata' => 'sin tildes (no es errata: control)'],
    ['consulta' => 'paz y salvo', 'esperado' => ['T66181'], 'errata' => 'sin errata (control)'],
    ['consulta' => 'uso del suello', 'esperado' => ['T41362'], 'errata' => 'letra duplicada'],
    ['consulta' => 'piscinas', 'esperado' => ['T28614'], 'errata' => 'sin errata (control)'],
    ['consulta' => 'fuegos artifisiales', 'esperado' => ['T41360'], 'errata' => 'c→s'],
    ['consulta' => 'organisaciones comunales', 'esperado' => ['T10962', 'T10936'], 'errata' => 'z→s'],
];
