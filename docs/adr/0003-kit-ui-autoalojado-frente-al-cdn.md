# ADR-0003: El Kit UI de GOV.CO se sirve desde la Sede, no desde `cdn.www.gov.co`

> **Estado:** ACEPTADA (la decisión ya estaba tomada en el código; este ADR la documenta)
> **Fecha:** 2026-10-29
> **Decisor:** equipo TIC. Cierra AC-28.5 de la auditoría del Módulo 01.
> **Tags:** sitio, identidad, GOV.CO, resiliencia

## Contexto

El Kit UI de GOV.CO (versión 9.2: hojas de estilo, íconos y tipografías) se publica en el CDN del
Ministerio, `cdn.www.gov.co`. La guía de la Sede lo nombra «fuente de verdad gráfica» y la propia
guía advierte que, si el CDN falla, las tipografías pueden no cargar.

El requisito **RNF-01-D01** pide que, si ese CDN no responde, la sede degrade a fuentes locales sin
romper el diseño, con reintento de tiempo límite ≤ 3 s. Una forma de cumplirlo sería cargar del CDN
y tener un respaldo local. La otra es no depender del CDN en ejecución.

## Decisión

**El Kit se sirve desde la propia Sede**, en `sitio/public/govco/` (`all.css`, `bootstrap.min.css`,
`assets/`). Ninguna página pide hojas, íconos ni tipografías a `cdn.www.gov.co` en tiempo de
ejecución. **Es una divergencia deliberada de la letra de RNF-01-D01**: en vez de «degradar si el CDN
cae», se elimina la dependencia, que es una garantía más fuerte (no hay tiempo de espera de 3 s que
aguantar porque no hay petición).

Razones, en orden de peso:

1. **Disponibilidad.** La sede de una Alcaldía no debe verse rota porque un servicio de un tercero esté
   caído, lento o bloqueado en la red de quien la visita.
2. **Privacidad.** Cargar recursos de otro dominio le revela a ese tercero la IP y el navegador de cada
   visitante, y obligaría a mencionarlo en la política de cookies y de datos.
3. **Seguridad.** Un recurso de un tercero cargado en ejecución es código ajeno dentro de la página; la
   política de seguridad de contenido (CSP) puede ser estricta si todo es del mismo origen.
4. **Reproducibilidad.** Lo que se despliega es lo que se probó, sin que cambie por fuera.

## Lo que esta decisión obliga a hacer

**Mantener la copia fiel.** `all.css` es byte a byte igual al del CDN del Ministerio (comprobado con
`cmp` al incorporarlo) y `bootstrap.min.css` conserva el `sha384` que publican los ejemplos del
propio Kit. Por eso `sitio/public/govco/**` **no se edita nunca**: las correcciones van en
`app/assets/css/sitio.css`, con su motivo escrito (ver `sitio/AGENTS.md` §2–3).

**Cómo se actualiza la versión del Kit**

1. Descargar la versión nueva de la fuente oficial del Ministerio y anotar su número.
2. Sustituir **sólo** lo que cambió dentro de `sitio/public/govco/`, en un PR que no toque nada más.
3. Comprobar que la copia sigue siendo fiel (`cmp` contra el CDN para `all.css`; `sha384` para
   Bootstrap).
4. Pasar la batería: `npx vitest run` (incluye `tests/kit-estados-css.test.ts`, que falla si el Kit
   dejó de traer una regla de la que dependemos), `npm run typecheck`, `npm run build`, la captura
   visual (`scripts/captura-visual.mjs`) y las puertas de navegador (`npm run test:kit-ui`).
5. Revisar las reglas de `sitio.css` que corrigen defectos del Kit: puede que la versión nueva ya los
   arregle y la corrección sobre, o que cambie un selector y la corrección deje de ganar.
6. Anotar la versión en este ADR (sección «Historial») y en `AGENTS.md`.

## Consecuencias

- **Costo:** quedamos con la obligación de seguir las versiones del Kit nosotros; el CDN lo haría solo.
  Una actualización del Ministerio que no incorporemos nos deja en una versión vieja sin avisar.
- **Falta una comprobación automática** de que la copia sigue siendo fiel al CDN (hoy es manual, con
  `cmp`). Se deja anotado como mejora: un guion que descargue y compare, ejecutado a mano o por
  programación, y que avise cuando el CDN publique algo distinto.
- **Las tipografías** viajan con la Sede y por tanto su licencia de redistribución importa. Ver
  `docs/kit-ui-licencia-tipografias.md` (AC-28.6, pendiente de confirmación escrita).

## Historial de versiones del Kit

| Fecha | Versión | Nota |
|---|---|---|
| 2026-10-29 | Kit UI 9.2 (CSS 5.0 del «Layout gov.co») · Bootstrap 5.0.2 | Estado al redactar este ADR. |

## Evidencia

`sitio/public/govco/` · `sitio/nuxt.config.ts` (orden de hojas) · `sitio/AGENTS.md` §2 · requisito
RNF-01-D01 · auditoría del Módulo 01, H-20, AC-28.5.
