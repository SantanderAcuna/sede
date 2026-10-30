<?php

declare(strict_types=1);

/**
 * El banco de consultas con errata de la búsqueda (AC-22.3).
 *
 * Cada entrada es lo que un ciudadano podría escribir con UNA errata típica del
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
];
