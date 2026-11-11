<?php

declare(strict_types=1);

/**
 * Consultas que **no** deben devolver nada y que, en algún momento, sí lo hicieron.
 *
 * No es un banco de calibración: cada entrada es un hallazgo con su origen, y entra
 * *después* de medirlo. Mantenerlo aparte de `banco-negativo.php` (cuyo tamaño exigen los
 * criterios, AC-22.8) deja claro qué se usó para elegir los parámetros y qué se añadió
 * para que un hallazgo no vuelva.
 *
 * Los puntajes son los del repositorio sobre el catálogo que siembra `TramiteSeeder`.
 *
 * @return array<string, string> consulta => por qué entró
 */
return [
    'transparencia' => 'M01-3 / CA-01-009: devolvía «Duplicado licencia(s) de tránsito de un vehículo automotor» '
        .'(0,474 por el título; coincidían trigramas sueltos de «tránsito» y «automotor»). '
        .'El catálogo no tiene ningún trámite de transparencia.',
    'contratacion' => 'Mismo hallazgo: devolvía tres trámites de «Expedición concepto(s)…» sin relación '
        .'con la contratación pública (el catálogo no tiene ninguno).',
];
