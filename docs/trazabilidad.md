# Matriz de trazabilidad de la Sede Electrónica

> **Documento generado.** No se edita a mano: se produce con `make trazabilidad`
> (`npm run trazabilidad` desde `sitio/`), que lee el expediente, el contrato OpenAPI y el
> código, y escribe lo que encuentra. Última generación: **2026-10-29**.

## Qué mide esta matriz, y qué no

Mide **trazabilidad**: para cada criterio del expediente, dónde hay algo que lo trabaja.
Los estados son cuatro y conviene no confundirlos:

| Estado | Significado |
|---|---|
| **prueba** | Hay una prueba automatizada que cita el criterio. Es el estado más fuerte de esta matriz. |
| **implementación** | El criterio se cita en el código del producto (sitio, panel o backend), sin prueba que lo acredite. |
| **desviación declarada** | Un ADR explica por qué no aplica o por qué se aparta del criterio. |
| **sin evidencia** | Nadie lo ha tocado, o nadie lo ha citado. Es la lista de trabajo. |

> ⚠️ **Esto no es una declaración de conformidad.** Una cita en un comentario acredita que
> alguien trabajó el criterio; no acredita que se cumpla. La conformidad la miden las puertas
> que ejecutan el producto: `make accesibilidad` (axe sobre el sitio construido y
> `make compilar` (tipos y compilación de los dos frontends). La versión anterior de este
> documento daba por acreditados 122 de 140 criterios citando pruebas de un árbol de carpetas
> que no existe en este repositorio; esa cifra no era verificable y se retiró.

## Resumen

| Familia | Criterios | Con prueba | Con evidencia | Desviación declarada | Sin evidencia |
|---|---|---|---|---|---|
| **CAG** — Diseño | 34 | 22 | 33 | 10 | 0 |
| **FUN** — Funcionalidad | 60 | 1 | 14 | 2 | 44 |
| **SEG** — Seguridad | 18 | 1 | 2 | 0 | 16 |
| **RT** — Requisitos técnicos | 5 | 0 | 0 | 0 | 5 |
| **O** — Obligaciones derivadas | 14 | 0 | 1 | 0 | 13 |
| **ACC** — Accesibilidad (citados sin enunciado) | 6 | 0 | 0 | 0 | 6 |
| **Total** | **137** | **24** | **50** | **12** | **84** |

Cobertura de trazabilidad: **36 %** de los criterios tiene algo que los trabaja. Los que tienen prueba automatizada son **24**.

Las columnas **no suman el total** a propósito: un criterio puede estar citado en el código *y* declarado como desviación en un ADR (CAG-06, por ejemplo, se omite con motivo y además aparece en los comentarios del componente que lo explica). La columna que hay que vigilar es la última.

## Operaciones del contrato

| Método | Ruta | Criterios declarados (`x-criterios`) | Estado (`x-status`) |
|---|---|---|---|
| GET | `/entidad` | FUN-007, FUN-009, FUN-014, FUN-015, SEG-006, CAG-12 | pending |
| GET | `/tramites` | FUN-001, FUN-021, FUN-022, RN-01 | implemented |
| GET | `/tramites/{slug}` | FUN-021, FUN-023, FUN-024, CAG-20 | implemented |
| GET | `/identidad/top-bar` | RF-01-001, RNF-ACES-01 | pending |
| GET | `/identidad/footer` | RF-01-002, RF-01-018, FUN-014 | pending |
| GET | `/identidad/menu` | RF-01-007 | pending |
| GET | `/noticias` | RF-01-013 | pending |
| GET | `/noticias/{slug}` | — | pending |
| GET | `/transparencia/subsecciones` | RF-02-001, RN-02 | pending |
| GET | `/transparencia/documentos` | RF-02-002, RF-02-028 | pending |
| GET | `/transparencia/documentos/{slug}` | RF-02-004, RF-02-024 | pending |
| GET | `/transparencia/buscar` | RF-02-027, RNF-REND-01 | pending |
| GET | `/transparencia/directorio` | RF-02-006, RN-03 | pending |
| GET | `/transparencia/calendario-tributario` | RF-02-016 | pending |
| GET | `/buscar` | RF-01-009, RNF-REND-01 | pending |
| GET | `/sitemap.xml` | RF-01-010, RNF-ACES-01 | pending |
| POST | `/consentimientos` | RF-B1-008, RF-01-D01, RN-01-D01 | implemented |
| GET | `/politicas` | RF-B1-009, RF-B3-058, RF-B2-008, RF-B2-009, RF-B2-010 | implemented |
| GET | `/politicas/{slug}` | RF-B1-009, RF-B3-058, RF-B2-008, RF-B2-009, RF-B2-010 | implemented |
| POST | `/panel/login` | RF-SEG-02, RNF-SEG-02 | pending |
| POST | `/panel/mfa` | RF-SEG-02 | pending |
| POST | `/panel/logout` | — | pending |
| GET | `/panel/perfil` | — | pending |
| PATCH | `/panel/entidad` | RF-01-001, RF-01-002, RF-01-003, RF-01-018 | implemented |
| GET | `/panel/politicas` | RF-B1-009, RF-B2-008, RF-B2-009, RF-B2-010 | implemented |
| PATCH | `/panel/politicas/{slug}` | RF-B1-009, RF-B2-008, RF-B2-009, RF-B2-010, RN-01-D01 | implemented |
| GET | `/panel/entidad/{modelUuid}/archivos` | — | implemented |
| POST | `/panel/entidad/{modelUuid}/archivos` | — | implemented |
| GET | `/panel/archivos/{uuid}` | — | implemented |
| DELETE | `/panel/archivos/{uuid}` | — | implemented |
| GET | `/panel/transparencia/documentos` | RF-02-021 | pending |
| POST | `/panel/transparencia/documentos` | RF-02-021 | pending |
| PATCH | `/panel/transparencia/documentos/{id}` | RF-02-021 | pending |
| DELETE | `/panel/transparencia/documentos/{id}` | RF-02-021 | pending |
| GET | `/panel/alertas` | RF-02-022 | pending |
| GET | `/panel/ita/tablero` | RF-02-029 | pending |
| GET | `/panel/auditoria` | — | pending |

El contrato declara **37 operaciones**, de las cuales **29** llevan `x-criterios`. Las que no lo llevan no se pueden trazar a un criterio del expediente desde el contrato: la relación está en el código que las consume.

## CAG — Diseño

Enunciados en `docs/Sección 2 §2.4`.

| Criterio | Estado | Evidencia (fichero:línea) |
|---|---|---|
| **CAG-01** | desviación declarada | `sitio/app/components/govco/CarruselGovco.vue:25` · `sitio/tests/diseno.mjs:2` |
| **CAG-02** | **prueba** | `sitio/tests/diseno.mjs:211` |
| **CAG-03** | **prueba** | `sitio/app/components/govco/CarruselGovco.vue:29` · `sitio/tests/diseno.mjs:250` |
| **CAG-04** | **prueba** | `sitio/app/components/govco/CarruselGovco.vue:52` · `sitio/tests/diseno.mjs:211` |
| **CAG-05** | **prueba** | `sitio/tests/diseno.mjs:287` |
| **CAG-06** | desviación declarada | `sitio/app/components/govco/BarraSuperior.vue:19` · `sitio/scripts/trazabilidad.mjs:251` · `sitio/tests/diseno.mjs:72` |
| **CAG-07** | **prueba** | `sitio/app/components/govco/BotonAccesibilidad.vue:50` · `sitio/app/components/govco/PanelAccesibilidad.vue:38` · `sitio/tests/diseno.mjs:297` · `sitio/tests/movil.mjs:15` |
| **CAG-08** | **prueba** | `sitio/app/layouts/default.vue:9` · `sitio/tests/diseno.mjs:462` |
| **CAG-09** | **prueba** | `sitio/.vitest/json/output.json:1` · `sitio/app/components/govco/MenuNavegacionGovco.vue:91` · `sitio/app/config/sitemap.ts:8` · `sitio/tests/diseno.mjs:479` · +1 |
| **CAG-10** | implementación | `sitio/app/components/govco/MenuNavegacionGovco.vue:98` |
| **CAG-11** | **prueba** | `sitio/app/components/govco/MigaDePanGovco.vue:5` · `sitio/app/layouts/default.vue:106` · `sitio/tests/diseno.mjs:498` |
| **CAG-12** | **prueba** | `sitio/app/components/govco/PiePaginaGovco.vue:28` · `sitio/tests/diseno.mjs:513` · `contract/openapi-original-backup.yaml:66` · `contract/openapi.yaml:102` |
| **CAG-13** | **prueba** | `sitio/tests/diseno.mjs:545` · `panel/.vitest/json/output.json:1` · `panel/tests/componentes.test.ts:35` |
| **CAG-14** | **prueba** | `sitio/tests/diseno.mjs:555` |
| **CAG-15** | **prueba** | `sitio/app/components/govco/BuscadorGovco.vue:19` · `sitio/tests/diseno.mjs:570` |
| **CAG-16** | **prueba** | `sitio/app/pages/realizar-una-peticion.vue:715` · `sitio/tests/diseno.mjs:656` · `panel/.vitest/json/output.json:1` · `panel/tests/componentes.test.ts:87` |
| **CAG-17** | **prueba** | `sitio/tests/diseno.mjs:678` |
| **CAG-18** | desviación declarada | `sitio/tests/diseno.mjs:73` |
| **CAG-19** | desviación declarada | `sitio/tests/diseno.mjs:74` |
| **CAG-20** | **prueba** | `sitio/app/pages/realizar-una-peticion.vue:435` · `sitio/tests/diseno.mjs:806` · `contract/openapi-original-backup.yaml:299` · `contract/openapi.yaml:351` |
| **CAG-21** | desviación declarada | `sitio/app/components/ModalAvisoSalida.vue:9` · `sitio/app/composables/useAvisoSalida.ts:152` · `sitio/tests/diseno.mjs:609` · `panel/.vitest/json/output.json:1` · +1 |
| **CAG-22** | desviación declarada | `sitio/tests/diseno.mjs:75` |
| **CAG-23** | implementación | `sitio/app/pages/realizar-una-peticion.vue:1623` · `sitio/app/pages/tramites/index.vue:749` |
| **CAG-24** | desviación declarada | — |
| **CAG-25** | desviación declarada | `sitio/tests/diseno.mjs:76` |
| **CAG-26** | **prueba** | `sitio/app/components/govco/TarjetaInformacionGovco.vue:10` · `sitio/app/pages/index.vue:190` · `sitio/tests/diseno.mjs:738` |
| **CAG-27** | **prueba** | `sitio/tests/diseno.mjs:764` |
| **CAG-28** | **prueba** | `sitio/tests/accesibilidad.mjs:2` · `sitio/tests/diseno.mjs:955` |
| **CAG-29** | **prueba** | `sitio/app/components/govco/CargandoGovco.vue:3` · `sitio/app/pages/tramites/index.vue:297` · `sitio/tests/diseno.mjs:951` · `sitio/tests/kit-cargando.test.ts:2` |
| **CAG-30** | **prueba** | `sitio/tests/diseno.mjs:786` |
| **CAG-31** | **prueba** | `sitio/app/components/govco/GaleriaAplicacionesGovco.vue:14` · `sitio/tests/diseno.mjs:77` |
| **CAG-32** | **prueba** | `sitio/tests/accesibilidad.mjs:2` · `sitio/tests/diseno.mjs:13` |
| **CAG-33** | desviación declarada | `sitio/nuxt.config.ts:69` · `sitio/tests/diseno.mjs:913` |
| **CAG-34** | desviación declarada | `sitio/tests/diseno.mjs:2` |

## FUN — Funcionalidad

Enunciados en `docs/Sección 5 §5.3.1`.

| Criterio | Estado | Evidencia (fichero:línea) |
|---|---|---|
| **FUN-001** | implementación | `contract/openapi-original-backup.yaml:139` · `contract/openapi.yaml:178` |
| **FUN-002** | sin evidencia | — |
| **FUN-003** | sin evidencia | — |
| **FUN-004** | sin evidencia | — |
| **FUN-005** | sin evidencia | — |
| **FUN-006** | sin evidencia | — |
| **FUN-007** | implementación | `contract/openapi-original-backup.yaml:66` · `contract/openapi.yaml:102` |
| **FUN-008** | desviación declarada | — |
| **FUN-009** | implementación | `contract/openapi-original-backup.yaml:66` · `contract/openapi.yaml:102` |
| **FUN-010** | desviación declarada | — |
| **FUN-011** | implementación | `sitio/app/layouts/default.vue:253` |
| **FUN-012** | **prueba** | `sitio/.vitest/json/output.json:1` · `sitio/app/config/sitemap.ts:351` · `sitio/app/types/menu.ts:10` · `sitio/tests/sitemap.test.ts:58` |
| **FUN-013** | implementación | `sitio/app/components/SeccionEnPreparacion.vue:5` · `sitio/app/pages/index.vue:10` · `sitio/app/pages/noticias.vue:5` · `sitio/app/pages/portales.vue:5` · +3 |
| **FUN-014** | implementación | `sitio/app/components/govco/PiePaginaGovco.vue:174` · `contract/openapi-additional-paths.yaml:100` · `contract/openapi-original-backup.yaml:66` · `contract/openapi.yaml:102` |
| **FUN-015** | implementación | `contract/openapi-original-backup.yaml:66` · `contract/openapi.yaml:102` |
| **FUN-016** | sin evidencia | — |
| **FUN-017** | sin evidencia | — |
| **FUN-018** | sin evidencia | — |
| **FUN-019** | sin evidencia | — |
| **FUN-020** | sin evidencia | — |
| **FUN-021** | implementación | `contract/openapi-original-backup.yaml:139` · `contract/openapi.yaml:178` |
| **FUN-022** | implementación | `contract/openapi-original-backup.yaml:139` · `contract/openapi.yaml:178` |
| **FUN-023** | implementación | `contract/openapi-original-backup.yaml:299` · `contract/openapi.yaml:351` |
| **FUN-024** | implementación | `contract/openapi-original-backup.yaml:299` · `contract/openapi.yaml:351` |
| **FUN-025** | sin evidencia | — |
| **FUN-026** | implementación | `sitio/app/pages/noticias.vue:3` |
| **FUN-027** | implementación | `sitio/app/pages/portales.vue:3` |
| **FUN-028** | sin evidencia | — |
| **FUN-029** | sin evidencia | — |
| **FUN-030** | sin evidencia | — |
| **FUN-031** | sin evidencia | — |
| **FUN-032** | sin evidencia | — |
| **FUN-033** | sin evidencia | — |
| **FUN-034** | sin evidencia | — |
| **FUN-035** | sin evidencia | — |
| **FUN-036** | sin evidencia | — |
| **FUN-037** | sin evidencia | — |
| **FUN-038** | sin evidencia | — |
| **FUN-039** | sin evidencia | — |
| **FUN-040** | sin evidencia | — |
| **FUN-041** | sin evidencia | — |
| **FUN-042** | sin evidencia | — |
| **FUN-043** | sin evidencia | — |
| **FUN-044** | sin evidencia | — |
| **FUN-045** | sin evidencia | — |
| **FUN-046** | sin evidencia | — |
| **FUN-047** | sin evidencia | — |
| **FUN-048** | sin evidencia | — |
| **FUN-049** | sin evidencia | — |
| **FUN-050** | sin evidencia | — |
| **FUN-051** | sin evidencia | — |
| **FUN-052** | sin evidencia | — |
| **FUN-053** | sin evidencia | — |
| **FUN-054** | sin evidencia | — |
| **FUN-055** | sin evidencia | — |
| **FUN-056** | sin evidencia | — |
| **FUN-057** | sin evidencia | — |
| **FUN-058** | sin evidencia | — |
| **FUN-059** | sin evidencia | — |
| **FUN-060** | sin evidencia | — |

## SEG — Seguridad

Enunciados en `docs/Sección 4 §4.3`.

| Criterio | Estado | Evidencia (fichero:línea) |
|---|---|---|
| **SEG-001** | sin evidencia | — |
| **SEG-002** | sin evidencia | — |
| **SEG-003** | sin evidencia | — |
| **SEG-004** | sin evidencia | — |
| **SEG-005** | sin evidencia | — |
| **SEG-006** | implementación | `sitio/app/components/govco/PiePaginaGovco.vue:143` · `sitio/app/config/sitemap.ts:293` · `sitio/app/pages/politicas/[slug].vue:3` · `sitio/app/pages/politicas/index.vue:3` · +2 |
| **SEG-007** | sin evidencia | — |
| **SEG-008** | sin evidencia | — |
| **SEG-009** | sin evidencia | — |
| **SEG-010** | sin evidencia | — |
| **SEG-011** | sin evidencia | — |
| **SEG-012** | sin evidencia | — |
| **SEG-013** | sin evidencia | — |
| **SEG-014** | sin evidencia | — |
| **SEG-015** | sin evidencia | — |
| **SEG-016** | sin evidencia | — |
| **SEG-017** | sin evidencia | — |
| **SEG-018** | **prueba** | `backend/tests/Feature/SaludTest.php:15` |

## RT — Requisitos técnicos

Enunciados en `docs/Sección 4 §4.3`.

| Criterio | Estado | Evidencia (fichero:línea) |
|---|---|---|
| **RT-01** | sin evidencia | — |
| **RT-02** | sin evidencia | — |
| **RT-03** | sin evidencia | — |
| **RT-04** | sin evidencia | — |
| **RT-05** | sin evidencia | — |

## O — Obligaciones derivadas

Enunciados en `docs/Sección 1`.

| Criterio | Estado | Evidencia (fichero:línea) |
|---|---|---|
| **O-01** | implementación | `sitio/app/pages/[...ruta].vue:11` |
| **O-02** | sin evidencia | — |
| **O-03** | sin evidencia | — |
| **O-04** | sin evidencia | — |
| **O-05** | sin evidencia | — |
| **O-06** | sin evidencia | — |
| **O-07** | sin evidencia | — |
| **O-08** | sin evidencia | — |
| **O-09** | sin evidencia | — |
| **O-10** | sin evidencia | — |
| **O-11** | sin evidencia | — |
| **O-12** | sin evidencia | — |
| **O-13** | sin evidencia | — |
| **O-14** | sin evidencia | — |

## ACC — Accesibilidad (citados sin enunciado)

Enunciados en `(sin definir)`.

| Criterio | Estado | Evidencia (fichero:línea) |
|---|---|---|
| **ACC-001** | sin evidencia | — |
| **ACC-002** | sin evidencia | — |
| **ACC-003** | sin evidencia | — |
| **ACC-004** | sin evidencia | — |
| **ACC-007** | sin evidencia | — |
| **ACC-008** | sin evidencia | — |

## Criterios sin evidencia

La lista de trabajo, por familia. Es la cifra que hay que bajar:

- **FUN** (44): FUN-002, FUN-003, FUN-004, FUN-005, FUN-006, FUN-016, FUN-017, FUN-018, FUN-019, FUN-020, FUN-025, FUN-028, FUN-029, FUN-030, FUN-031, FUN-032, FUN-033, FUN-034, FUN-035, FUN-036, FUN-037, FUN-038, FUN-039, FUN-040, FUN-041, FUN-042, FUN-043, FUN-044, FUN-045, FUN-046, FUN-047, FUN-048, FUN-049, FUN-050, FUN-051, FUN-052, FUN-053, FUN-054, FUN-055, FUN-056, FUN-057, FUN-058, FUN-059, FUN-060
- **SEG** (16): SEG-001, SEG-002, SEG-003, SEG-004, SEG-005, SEG-007, SEG-008, SEG-009, SEG-010, SEG-011, SEG-012, SEG-013, SEG-014, SEG-015, SEG-016, SEG-017
- **RT** (5): RT-01, RT-02, RT-03, RT-04, RT-05
- **O** (13): O-02, O-03, O-04, O-05, O-06, O-07, O-08, O-09, O-10, O-11, O-12, O-13, O-14
- **ACC** (6): ACC-001, ACC-002, ACC-003, ACC-004, ACC-007, ACC-008

## Cómo se genera

```bash
make trazabilidad            # o: cd sitio && npm run trazabilidad
```

El generador (`sitio/scripts/trazabilidad.mjs`) lee el universo de criterios de las
secciones del expediente, busca cada identificador en `sitio/`, `panel/`, `backend/` y
`contract/`, y escribe este documento. **No inventa**: lo que no encuentra aparece como
«sin evidencia».
