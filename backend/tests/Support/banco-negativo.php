<?php

declare(strict_types=1);

/**
 * El banco negativo de la búsqueda (AC-22.8): consultas que **no** deben devolver nada.
 *
 * Un buscador tolerante a erratas que devuelve algo para todo es peor que uno estricto:
 * el ciudadano que busca un servicio que la Alcaldía no presta (el pasaporte lo expide
 * la Cancillería; la cédula, la Registraduría) debe ver «sin resultados» y no un trámite
 * que se le parece por casualidad. Hay tres clases: ruido sin sentido, servicios de
 * otras entidades —que comparten palabras con trámites municipales— y entradas que
 * no son texto de búsqueda (sólo dígitos, una frase ajena al dominio).
 *
 * `licencia de conduccion` es el caso difícil que obligó a añadir la cobertura por
 * palabra: se parece a «licencia de exhumación» en dos de sus tres palabras.
 *
 * @return list<string>
 */
return [
    'xyzxyz',
    'asdfghjk',
    'pasaporte',
    'licencia de conduccion',
    'cedula de ciudadania',
    'registro civil de nacimiento',
    'antecedentes judiciales',
    'libreta militar',
    'subsidio de vivienda',
    'visa de turismo',
    '1234567890',
    'pizza a domicilio',
];
