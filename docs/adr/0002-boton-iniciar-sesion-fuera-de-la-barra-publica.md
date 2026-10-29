# ADR-0002: El botón «Iniciar sesión» sale de la barra superior pública

> **Estado:** ACEPTADA
> **Fecha:** 2026-10-29
> **Decisor:** José (equipo TIC). Registrada como D-6 en la auditoría del Módulo 01.
> **Tags:** sitio, identidad, GOV.CO

## Contexto

La barra superior del Estado (la franja azul con el logotipo de GOV.CO) es un componente
obligatorio que ve toda la ciudadanía. El sitio ponía en ella un icono de usuario que llevaba
a `/admin/acceso`, que es la entrada del panel de **funcionarios**, no de ciudadanos.

Dos problemas: un icono de usuario en la barra pública promete una cuenta ciudadana que hoy no
existe (el acceso del ciudadano a sus trámites se resolverá con Servicios Ciudadanos Digitales),
y dirigía a toda la ciudadanía a la puerta del panel administrativo.

## Decisión

Se retira el botón de la barra superior. Los funcionarios entran por la URL directa del panel.
La barra conserva únicamente el logotipo, que enlaza a `https://www.gov.co/home/` (RF-B1-001).

## Consecuencias

- La ciudadanía deja de ver un acceso que no puede usar.
- **No es una medida de seguridad:** la ruta `/admin/acceso` sigue existiendo y pública. La
  protección del panel es la autenticación, no que el enlace esté escondido.
- Cuando exista el acceso ciudadano, se decidirá aquí o en un ADR nuevo si vuelve un botón a la
  barra y hacia qué entrada.

## Evidencia

`sitio/app/components/govco/BarraSuperior.vue`; auditoría del Módulo 01, H-19, AC-27.4.
