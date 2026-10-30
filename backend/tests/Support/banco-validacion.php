<?php

declare(strict_types=1);

/**
 * El banco de validación de la búsqueda (AC-22.8).
 *
 * Se escribió **después** de fijar los parámetros con el banco de erratas y el
 * negativo, y se corrió una sola vez con ellos ya congelados: sirve para saber si la
 * calibración generaliza o sólo se ajustó a sus propias consultas. No se usa para
 * volver a ajustar; si se hiciera, dejaría de ser una validación.
 *
 * **Límite declarado.** Quien lo escribió es quien calibró, con el catálogo a la
 * vista: sigue siendo optimista respecto de cómo escriben los ciudadanos.
 *
 * @return array{positivos: list<array{consulta: string, esperado: list<string>, errata: string}>, negativos: list<string>, limite_conocido: list<string>}
 */
return [
    'positivos' => [
        ['consulta' => 'licensia de ocupasion del espacio publico', 'esperado' => ['T65974', 'T65975'], 'errata' => 'c→s (dos veces)'],
        ['consulta' => 'rejistro de contribuyentes industria y comercio', 'esperado' => ['T6082', 'T6071', 'T6079'], 'errata' => 'g→j'],
        ['consulta' => 'debolucion de saldo a favor', 'esperado' => ['T66178', 'T2678'], 'errata' => 'v→b'],
        ['consulta' => 'consepto sanitario', 'esperado' => ['T28648'], 'errata' => 'c→s'],
        ['consulta' => 'centros de estetica', 'esperado' => ['T66452'], 'errata' => 'sin tildes (no es errata: control)'],
        ['consulta' => 'blindage de un vehiculo', 'esperado' => ['T41370'], 'errata' => 'j→g'],
        ['consulta' => 'pension por imvalidez', 'esperado' => ['T69151'], 'errata' => 'n→m'],
        ['consulta' => 'plan parsial', 'esperado' => ['T66059', 'T66057', 'T66070', 'T66338'], 'errata' => 'c→s'],
        ['consulta' => 'clubes deportibos', 'esperado' => ['T28650', 'T28651'], 'errata' => 'v→b'],
        ['consulta' => 'libertad y tradisión de vehiculo', 'esperado' => ['T14088'], 'errata' => 'c→s, con tilde indebida'],
    ],
    // Consultas sin trámite propio en el catálogo: no deben devolver nada.
    'negativos' => [
        'antecedentes penales',
        'cita medica',
        'denuncia por robo',
        'certificado de bomberos',
        'matricula mercantil',
    ],
    // Falso positivo medido y aceptado. El impuesto de renta es nacional (lo administra
    // la DIAN), no hay trámite municipal con ese nombre, y aun así la búsqueda devuelve
    // un trámite de registro de contribuyentes de industria y comercio (T6071, puntaje
    // 0,667 en la calibración). Excluirlo exigiría un umbral que, con los mismos
    // parámetros, dejaría fuera aciertos legítimos (ver docs/busqueda.md). Esta entrada
    // fija que sigue ocurriendo, para que la mejora —o el empeoramiento— no pase
    // desapercibida.
    'limite_conocido' => [
        'impuesto de renta',
    ],
];
