<?php

declare(strict_types=1);

namespace App\Support\Tramites;

/**
 * Qué datos de contacto de la fuente se pueden publicar, y cuáles no.
 *
 * # La regla
 *
 * La fuente —la API de contenido de GOV.CO— devuelve, junto a la dirección
 * institucional de un punto de atención, un teléfono que a veces es un **móvil de
 * diez dígitos**, del tipo `3007659666`. Un punto de atención puede estar a nombre
 * de una oficina y su teléfono estar a nombre de quien la atiende, y publicarlo en
 * la sede electrónica sería tratar un dato personal como si fuera institucional.
 *
 * **Que el dato venga de una API pública no cambia nada.** La Ley 1581 de 2012 no
 * tiene una excepción para los datos personales que uno encuentra publicados: si
 * el Distrito los publica en su sede, responde por ellos igual que si los hubiera
 * escrito a mano. Es la misma razón por la que la siembra desde SUIT no copió el
 * bloque de contacto de la entidad.
 *
 * De ahí la asimetría, que es deliberada: **se publica lo que se puede demostrar
 * institucional y se descarta lo que puede ser de una persona**. Un fijo de la
 * Entidad —`(5) 4209600`— es conmutador o dependencia y ya lo publica el pie del
 * sitio; un móvil de diez dígitos no lo es.
 *
 * # Por qué la comprobación es por forma y no por lista
 *
 * No se guarda una lista de números prohibidos: una lista hay que mantenerla y se
 * queda vieja en cuanto la fuente cambia un teléfono. Se comprueba la **forma** del
 * número, que es lo que en Colombia distingue un móvil de un fijo.
 */
final class ContactoPublicable
{
    /**
     * El teléfono que se puede publicar, o nulo.
     *
     * Un campo puede traer **varios** números separados por comas —el de la
     * Dirección de Impuestos de Santa Marta trae dos extensiones—, así que se
     * filtra número a número: descartar la cadena entera por llevar un móvil al
     * lado perdería también el conmutador, y publicar la cadena entera por llevar
     * un conmutador publicaría el móvil.
     */
    public static function telefono(?string $declarado): ?string
    {
        if ($declarado === null || trim($declarado) === '') {
            return null;
        }

        $publicables = array_values(array_filter(
            array_map(trim(...), explode(',', $declarado)),
            self::esInstitucional(...),
        ));

        return $publicables === [] ? null : implode(', ', $publicables);
    }

    /**
     * Si un teléfono es institucional y no de una persona.
     *
     * En Colombia un móvil son diez dígitos que empiezan por 3, y con el indicativo
     * del país doce que empiezan por 57 y siguen con 3. Todo lo demás que tenga
     * dígitos se acepta: los fijos del Distrito son de siete y ocho.
     */
    public static function esInstitucional(string $telefono): bool
    {
        $digitos = preg_replace('/\D+/', '', $telefono) ?? '';

        if ($digitos === '') {
            return false;
        }

        if (strlen($digitos) === 10 && str_starts_with($digitos, '3')) {
            return false;
        }

        return ! (strlen($digitos) === 12 && str_starts_with($digitos, '573'));
    }

    /**
     * El correo que se puede publicar, o nulo.
     *
     * La fuente devuelve buzones de área —`impuesto-ica@santamarta.gov.co`—, que
     * son institucionales, y también textos que no son correos —«Ver tutoriales»—
     * porque el campo se reutiliza para describir el canal. Un valor que no tiene
     * forma de correo no es un correo y no se publica como si lo fuera.
     */
    public static function correo(?string $declarado): ?string
    {
        if ($declarado === null) {
            return null;
        }

        $correo = trim($declarado);

        if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $correo;
    }

    /**
     * Las coordenadas que se pueden publicar, o nulas.
     *
     * La fuente trae coordenadas **que no son de ningún sitio**: al punto de
     * atención `ALCALDIA LOCAL 3` del certificado de residencia le da
     * `Latitud: "20"` y `Longitud: "30"`, que caen en el golfo de Guinea. Publicar
     * eso pondría el marcador en el Atlántico, y un mapa que miente es peor que un
     * mapa sin marcador.
     *
     * Por eso sólo se publica lo que cae dentro de la caja que envuelve a Colombia.
     * No se comprueba que el punto esté en Santa Marta: un trámite del Distrito
     * podría tener un punto legítimo fuera, y descartarlo sería inventar una
     * restricción que la Entidad no ha declarado. La caja es ancha a propósito
     * —descarta lo imposible, no lo dudoso— y las coordenadas que no pasan se
     * declaran ausentes en la procedencia.
     *
     * @return array{latitud: float|null, longitud: float|null}
     */
    public static function coordenadas(?float $latitud, ?float $longitud): array
    {
        $dentro = $latitud !== null
            && $longitud !== null
            && $latitud >= -5.0 && $latitud <= 14.0
            && $longitud >= -82.0 && $longitud <= -66.0;

        return [
            'latitud' => $dentro ? $latitud : null,
            'longitud' => $dentro ? $longitud : null,
        ];
    }
}
