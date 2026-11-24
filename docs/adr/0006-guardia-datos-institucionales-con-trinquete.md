# Archivo 0006 (no es el `ADR-0006` del registro): guardia de datos institucionales en el sitio, con línea base y trinquete

> **Estado:** ACEPTADA (entrega 0B, tarea T7, de la Fase 0; José aprobó la entrega, confirmado el 2026-11-22)
> **Fecha:** 2026-11-17
> **Decisor:** José, que delegó la elección («soluciones limpias, con profundidad y rigor PhD»); decisión D-0.4 de la ficha de la Fase 0
> **Tags:** sitio, datos institucionales, pruebas

## Contexto

Los datos de la Entidad (teléfonos, correos, dirección, código postal, NIT, dependencias) viven en base de datos y se leen por la API. Escritos en el código del sitio son copias que se desactualizan: había tres copias del contacto y una con el teléfono distinto. Una guardia que exigiera «cero literales» desde el primer día no podía quedar en verde: cuatro archivos del sitio (`PiePaginaGovco.vue`, `atencion.vue`, `accesibilidad.vue`, `realizar-una-peticion.vue`) tienen 18 literales únicos que la Fase 1 debe limpiar.

## Decisión

`sitio/tests/datos-institucionales.test.ts` detecta literales institucionales y los compara con una **línea base versionada** (`datos-institucionales.linea-base.json`) con trinquete:

1. Un literal que no está en la línea base **rompe la CI**.
2. Una entrada de la línea base que **ya no existe** en el código también la rompe: la lista solo puede bajar, y cada archivo que la Fase 1 limpie sale de ella.
3. La única excepción permanente es el respaldo del pie de página (`permanente: true`, 6 de las 18 entradas): si la API no responde o no trae ninguna red válida, el pie usa el respaldo escrito en el código (`REDES_RESPALDO`, en `layouts/default.vue`). No hay caché intermedia: el orden es API y, si falla, código.

El detector se prueba aparte (correo, teléfono normalizado sin importar los espacios, dirección, código postal, NIT y dependencias; un caso negativo con código limpio).

## Consecuencias

- La deuda queda **declarada y medida**, no escondida: 18 literales en 4 archivos, 12 de ellos a retirar en la Fase 1.
- Es una guardia por patrones: no ve datos con otra forma ni el nombre de la Entidad.
- Alternativa descartada: exigir cero literales ya (no puede quedar en verde) o una lista sin trinquete (la deuda podría crecer o quedar obsoleta sin que nada lo dijera).
