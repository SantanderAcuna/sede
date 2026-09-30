# Sede Electrónica — Alcaldía Distrital de Santa Marta

Sede electrónica del Distrito Turístico, Cultural e Histórico de Santa Marta, construida
para cumplir el Decreto Ley 2106 de 2019, la Resolución MinTIC 1519 de 2020 y los criterios
de aceptación del expediente normativo que vive en `docs/`.

**Estado:** en construcción. Aprovisionamiento del servidor terminado; la aplicación todavía
no existe. El plan completo está en [`plan.md`](plan.md).

---

## Qué es

Una sede electrónica es, por definición legal, el punto único donde un ciudadano ejerce sus
derechos frente a una autoridad: consulta trámites, radica solicitudes, paga, recibe
notificaciones y ejerce control social. El artículo 14 del Decreto Ley 2106 de 2019 exige
que **cada autoridad tenga una sola**, y que integre todos sus portales y aplicaciones.

Ese «una sola» no es retórica: condiciona la arquitectura entera. Un solo dominio, un solo
origen, un solo certificado y un solo lugar donde se aplican las cabeceras de seguridad.

## Arquitectura

Tres aplicaciones, un contrato y un punto de entrada.

```
                    Internet
                        │
                        ▼
              ┌──────────────────┐
              │    Cloudflare    │  WAF · DDoS L7 · Full (Strict)
              └────────┬─────────┘
                       │ HTTPS
                       ▼
        ┌──────────────────────────────────┐
        │  nginx  ·  único punto de entra  │
        └───┬───────────┬──────────────┬───┘
            │           │              │
     /          /panel        /api/v1
            │           │              │
      ┌─────▼────┐ ┌────▼─────┐  ┌─────▼──────┐
      │  sitio   │ │  panel   │  │  backend   │
      │ Nuxt SSR │ │ SPA Vue  │  │ Laravel 13 │
      │ público  │ │ editorial│  │  API REST  │
      └──────────┘ └──────────┘  └─────┬──────┘
                                       │
                          ┌────────────┴────────────┐
                          │  PostgreSQL 18 · Redis  │
                          └─────────────────────────┘
```

| Aplicación | Carpeta | Para quién | Por qué así |
|---|---|---|---|
| API REST | `backend/` | Las dos anteriores | Laravel 13, PHP 8.5 |
| Panel | `panel/` | Funcionarios y ciudadanos | SPA: no necesita posicionamiento y sí respuesta inmediata |
| Sitio | `sitio/` | El público | SSR: su contenido debe ser rastreable y auditable sin ejecutar JavaScript |

El **contrato OpenAPI** en `contract/` es la única fuente de verdad del intercambio HTTP.
Backend y frontends no se acoplan entre sí: se acoplan al mismo contrato.

## Stack

| Capa | Tecnología | Versión |
|---|---|---|
| Sistema | Ubuntu LTS | 26.04 |
| Contenedores | Docker · Compose | 29.8 · 5.5 |
| Servidor web | nginx (*stable*) | 1.30.5 |
| Aplicación | PHP-FPM · Laravel | 8.5 · 13 |
| Datos | PostgreSQL · Redis | 18 · 8 |
| Panel | Vue · TypeScript · Vite | 3.5 · 7 · 8 |
| Sitio | Nuxt · TypeScript | 4.5 |
| Contrato | OpenAPI | 3.1 |

Versiones verificadas contra los registros oficiales el 2026-09-30.

## Ramas

Tres ramas permanentes y prefijos por tipo de trabajo. Se sigue la estrategia del proyecto:
`feat/{modulo}` → `develop` → `staging` → `main`.

| Rama | Propósito | Se despliega a |
|---|---|---|
| `main` | Producción | Producción |
| `staging` | Pruebas y aceptación | Entorno de pruebas |
| `develop` | Integración | — |

Prefijos: `feat/` · `fix/` · `refactor/` · `docs/` · `test/` · `chore/` · `hotfix/`.

Los mensajes de confirmación siguen la forma `tipo(ámbito): efecto`, en imperativo y en
castellano — describen el efecto, no el archivo tocado.

**Un PR es una unidad de revisión, no de código:** más de 400 líneas obliga a dividirlo.

## Dónde está cada cosa

| Ruta | Contenido |
|---|---|
| `plan.md` | **El plan de construcción.** Empieza por aquí |
| `accesos.md` | Qué credenciales existen, dónde viven y quién las custodia. Sin valores |
| `docs/` | Expediente normativo: las seis secciones y los 140 criterios de aceptación |
| `docs/adr/` | Decisiones de arquitectura |
| `docs/investigacion/` | Investigación destilada: entidad, integración estatal, requisitos |
| `docs-security/` | Guía de seguridad: cinco capítulos y cinco apéndices |
| `GUIA-MAESTRA-COMPLETA.md` | Guía de casa: stack, metodología y reglas absolutas |
| `contract/` | Contrato OpenAPI |
| `docker/` | Imágenes y configuración de los contenedores |

## Puesta en marcha

```bash
make instalar    # dependencias del backend y de los dos frontends
make preparar    # base de datos local con datos de ejemplo
make dev         # API y panel en segundo plano
make comprobar   # todas las puertas de calidad
```

## Reglas del proyecto

- **Contrato primero.** Ninguna línea de implementación existe sin su operación en el
  contrato.
- **Corte vertical.** Cada funcionalidad se completa de la base de datos a la interfaz,
  con pruebas, antes de empezar la siguiente.
- **Ninguna fase se cierra con una puerta en rojo.**
- **El código va en castellano**: identificadores, mensajes y confirmaciones.
- **Accesibilidad como atributo legal**, no como detalle de acabado: WCAG 2.1 AA es
  obligatorio desde el 1 de enero de 2022.
- **Los secretos nunca entran al repositorio.** Este repositorio es público.

## Documentos primarios

Los PDF y presentaciones oficiales —el Decreto Ley 2106 de 2019, los criterios de
aceptación de MinTIC, el Kit UI— no se versionan: pesan 84 MB, son documentos de terceros y
su contenido está transcrito en el expediente markdown de `docs/`. Viven junto al proyecto,
fuera del control de versiones.

---

**Titular:** Alcaldía Distrital de Santa Marta · NIT 891.780.009-4
