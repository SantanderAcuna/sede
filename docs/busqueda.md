# Búsqueda de la sede (T-22 · H-23)

Documento de diseño y de verificación de la búsqueda real: sugerencias mientras se
escribe, tolerancia a erratas y resultados por pertinencia.

- **Resuelve:** H-23 · RF-B1-005, RF-B1-006, RF-B2-035, RF-B2-036, RF-B3-067 · D-03.
- **Decisión de arquitectura:** [ADR-0017](adr/README.md).
- **Estado:** fase 1 (contrato, datos, premisa medida) · fases 2 y 3 pendientes.
- **Convención:** **HECHO** = medido o leído en el repositorio · **INFERENCIA** = deducido
  de hechos · **HIPÓTESIS** = no verificado todavía.

## 1. Alcance

| Entra | No entra | Por qué |
|---|---|---|
| Trámites y políticas (API) | Noticias, documentos de transparencia, directorio | No existen como contenido (T-23, T-24…). El enum `tipo_recurso` crece cuando existan. |
| Secciones estáticas del sitio | — | Las lista el sitio en el cliente (`config/sitemap.ts`); no se mezclan con el ranking del servidor. Se muestran como grupo aparte, como hoy. |
| `autor` y `miniatura` en el contrato | Valores reales | **HECHO:** ningún contenido los registra. Viajan siempre y valen `null`; no se inventan. |

**Cambio de contrato (reversible, sin consumidores):** `/buscar` tenía dos formas de
respuesta según `autocompletar`. Una operación cuya respuesta cambia de forma según un
parámetro no se puede tipar en un cliente generado; se separó en dos operaciones.
**HECHO:** `buscarGlobal` y `SugerenciaBusquedaItem` no los usa ningún código del sitio ni
del panel (sólo los tipos generados).

| Operación | Forma | Estado |
|---|---|---|
| `GET /api/v1/busqueda/sugerencias?q=` | ≤ 10 × {título, categoría, tipo, url, score} | `pending` → `implemented` en la fase 2 |
| `GET /api/v1/buscar?q=&tipo=&page=&per_page=` | Colección paginada × {título, categoría, extracto, fecha, autor, miniatura, url, score} | ídem |

## 2. Qué se midió (fase 1)

Catálogo real: **124 trámites** de la entidad `0043` de SUIT, nombres publicados
(`nombreEstandarizado_govco`) y resumen recortado a 500 caracteres. PostgreSQL 16.15 local;
la prueba `BancoDeErratasTest` lo repite en el 18 de CI.

| Medición | Resultado | Lectura |
|---|---|---|
| Consultas con errata que acierta el `LIKE` actual (banco de 10) | **0 / 10** | HECHO. Es la línea base y el motivo de T-22. |
| Ídem con `pg_trgm` (similitud de palabra sobre título y texto) | **10 / 10** en el primer o segundo puesto | HECHO, **con sesgo**: el banco lo escribí con el catálogo a la vista. |
| Similitud más baja de un acierto del banco | **0,500** («esterilisacion canina» → «…esterilizar caninos…») | HECHO. Sin margen sobre un umbral de 0,5. |
| «licensia» con el umbral por defecto de `pg_trgm` (0,6) | 0,556 → **no** encuentra nada | HECHO. El umbral hay que fijarlo por transacción. |
| Consulta sin sentido («xyzxyz») | 0 resultados | HECHO. |
| «pasaporte» (no hay trámite) con umbral 0,4 / 0,5 | 2 / 0 resultados (ruido: «aportes») | HECHO. Define el costo de bajar el umbral. |
| Operador `<%` con índice GIN | Usa `Bitmap Index Scan` | HECHO (con `enable_seqscan=off`; con 124 filas el planificador prefiere recorrer la tabla). |
| `CREATE EXTENSION pg_trgm` con un rol sin superusuario | Funciona (`trusted`) | HECHO. No hace falta pedir privilegios al despliegue. |

**Un error mío, corregido:** la primera medición usó el campo `titulo` de la fuente y no el
`nombreEstandarizado_govco` que el sitio publica. Se repitió con los nombres reales; el
resultado se mantuvo, pero el banco y el piso de 0,500 salen de la segunda.

## 3. Decisiones

| # | Decisión | Alternativa descartada | Motivo |
|---|---|---|---|
| D1 | `pg_trgm` + índices GIN en PostgreSQL | Meilisearch / Elasticsearch | HECHO: 124 trámites; un servicio nuevo es otra pieza que operar y parchear. HIPÓTESIS sobre el volumen futuro (ver R1). |
| D2 | Texto normalizado **al guardar** (`TextoBuscable`) | `unaccent()` en la consulta | `unaccent()` es `STABLE`, no se indexa sin envolverla; una extensión menos. Mismo criterio que ya usa `Tramite::busqueda`. |
| D3 | Dos columnas por tabla: título y texto completo | Una sola | Para pesar más una coincidencia en el título. |
| D4 | Dos operaciones distintas | Una con `autocompletar` | Ver §1. |
| D5 | `word_similarity` y umbral por transacción (`SET LOCAL` / `set_config(…, true)`) | Umbral global del servidor | Cambiar el global afectaría a toda consulta de la base. |
| D6 | Desempate por `similarity()` de la cadena entera | Orden alfabético | HECHO: sin él, «Exención del impuesto predial…» ganaba a «Impuesto predial…» por orden alfabético. |
| D7 | Umbral inicial 0,4 y **calibración en la fase 2** | Fijar 0,5 hoy | Por el piso de 0,500 (sin margen) y el ruido de 0,4; se resuelve con un banco ampliado y uno negativo. |

## 4. Riesgos

| # | Riesgo | Tipo | Mitigación | Cuándo |
|---|---|---|---|---|
| R1 | Un volumen mucho mayor que 124 filas degrada el p95 | HIPÓTESIS | AC-22.6 mide contra staging; el índice GIN ya existe | F3 |
| R2 | El banco es optimista (lo escribí yo) | HECHO | Pedir a la Entidad consultas reales de atención; ampliar a ≥ 30 con casos de una palabra y negativos | F2 |
| R3 | **El texto buscado queda en el registro de acceso.** `docker/nginx/nginx.conf` registra `$request` (con `?q=`) y `$http_referer` (el sitio pide sus recursos con la página `/buscar?q=…` como referer). Un ciudadano puede escribir un número de documento. | HECHO el formato · INFERENCIA el contenido | `log_format` sin argumentos para `/api/v1/buscar` y `/api/v1/busqueda`, y referer sin consulta; revisar con el oficial de datos personales (Ley 1581) | F2 |
| R4 | Cada pulsación es una petición: abuso o carga | INFERENCIA | Pausa de 250 ms en el cliente, `throttle:busqueda` en el servidor (429 declarado en el contrato) | F2 |
| R5 | Las políticas indexan texto de ejemplo (`es_ejemplo`) | HECHO | La página enlazada muestra ese mismo texto con su aviso; al aprobarse el texto, el hook reindexa solo | — |
| R6 | Relevancia subjetiva | HECHO (Guía §D-03) | Orden documentado y reproducible: la fórmula está en el código y el banco en el repositorio | F2 |
| R7 | Si `TextoBuscable::normalizar` cambia, lo guardado queda desfasado | INFERENCIA | Prueba que fija su salida; comando `busqueda:reindexar` | F2 |
| R8 | Sin Cloudflare caché sobre `/api/v1/buscar` el origen recibe todo | HIPÓTESIS | Medir antes de decidir; no se cachea una búsqueda con texto del ciudadano | F3 |

## 5. Criterios de aceptación

Heredados de la auditoría (AC-22.1…22.6), con el reparto por fase. Los umbrales marcados
con † son míos y se revisan tras medir.

| AC | Criterio | Verificación | Fase |
|---|---|---|---|
| AC-22.1 | ≤ 10 sugerencias con título, categoría y URL; contrato `pending` → `implemented` | `curl '…/api/v1/busqueda/sugerencias?q=licen'` | F2 |
| AC-22.2 | Con < 3 caracteres no consulta; con ≥ 3 muestra sugerencias | Prueba del componente | F3 |
| AC-22.3 | ≥ 8 de 10 consultas con errata devuelven el esperado entre los 3 primeros † | Banco `tests/Support/banco-erratas.php` | **F1 (consulta)** · F2 (API) |
| AC-22.4 | `GET /buscar?q=`: sólo contenido de la sede; fecha, categoría, título, extracto (autor y miniatura cuando existan); orden por pertinencia | Prueba HTTP + `curl` | F2 |
| AC-22.5 | axe: 0 críticas/serias; patrón `combobox` (`aria-expanded`, `aria-controls`, `listbox`), con teclado | axe sobre `/buscar` y el encabezado | F3 |
| AC-22.6 | 100 consultas contra staging: p95 < 300 ms † | Script de carga contra staging | F3 |

**Criterios añadidos por el análisis de riesgos** (no estaban en la auditoría):

| AC | Criterio | Verificación | Fase |
|---|---|---|---|
| AC-22.7 | El texto buscado no aparece en el registro de acceso ni en el referer registrado | Petición real + lectura del registro de nginx | F2 |
| AC-22.8 | Una consulta sin sentido devuelve lista vacía, no ruido | Banco negativo | F2 |
| AC-22.9 | La corrección de un trámite o una política desde el panel se refleja en la búsqueda sin reindexar a mano | Prueba de modelo | **F1** |

## 6. Definición de hecho (DoD)

Aplica a cada fase de T-22:

- [ ] Criterios de la fase ejecutados con su salida en el PR.
- [ ] CI 7/7 (Contrato, Backend, Panel, Sitio, Pila, Dependencias, Credenciales).
- [ ] Prueba en rojo antes y en verde después, o la razón por la que no aplica.
- [ ] Contrato validado (`redocly lint`, sin avisos nuevos) y tipos regenerados.
- [ ] `make trazabilidad` regenerado.
- [ ] Sin secretos, IP ni dominios en código, pruebas ni documentos.
- [ ] Reversa descrita: la migración tiene `down()` y no borra la extensión.
- [ ] Límites declarados, no ocultos.

## 7. Casos de prueba

| TC | Verifica | Pasos | Esperado | Tipo |
|---|---|---|---|---|
| TC-BUS-01 | Extensión instalada | `select count(*) from pg_extension where extname='pg_trgm'` | 1 | Auto. (`IndicesDeBusquedaTest`) |
| TC-BUS-02 | Índices GIN de trigramas | Leer `pg_indexes` de los cuatro índices | `USING gin` + `gin_trgm_ops` sobre su columna | Auto. |
| TC-BUS-03 | Texto buscable del trámite al guardar y al corregir | Crear, actualizar el nombre | `busqueda_titulo` y `busqueda` siguen el cambio | Auto. (AC-22.9) |
| TC-BUS-04 | Texto buscable de la política con el texto de sus apartados | Crear, cambiar apartados | `busqueda` incluye nombre, títulos y párrafos | Auto. (AC-22.9) |
| TC-BUS-05 | El texto buscable no se rellena a mano | `create(['busqueda' => 'x'])` | Se ignora | Auto. |
| TC-BUS-06 | Normalización fija | Tildes, eñes, comodines, dobles espacios | Salidas exactas | Auto. (`TextoBuscableTest`) |
| TC-BUS-07 | Línea base | LIKE sobre el banco de erratas | 0 aciertos | Auto. (`BancoDeErratasTest`) |
| TC-BUS-08 | Tolerancia a erratas (consulta) | Banco de 10 sobre el catálogo sembrado | ≥ 8 en el top 3 | Auto. (AC-22.3) |
| TC-BUS-09 | Consulta sin sentido | «xyzxyz» | 0 filas | Auto. |
| TC-BUS-10 | Operador con índice y umbral por transacción | `EXPLAIN` de `'licensia' <% busqueda_titulo` | Plan con `tramites_busqueda_titulo_trgm`; ≥ 1 fila | Auto. |
| TC-BUS-11 | Contrato | `redocly lint` | Válido, sin avisos nuevos | Auto. (CI Contrato) |
| TC-BUS-12 | Deriva del contrato | `DerivaDelContratoTest` | Las operaciones `pending` no exigen ruta | Auto. |
| TC-BUS-13 | Revertir la migración | `migrate:rollback --step=1` | Columnas e índices fuera; la extensión se queda | Manual (QA / despliegue) |
| TC-BUS-14… | Sugerencias, API, componente, axe, p95, registro de acceso | Ver §5 | — | Fases 2 y 3 |

## 8. Plan por fases

1. **Fase 1 (este PR):** contrato, extensión e índices, texto buscable, premisa medida.
   Sin ruta nueva: las operaciones siguen `pending`.
2. **Fase 2:** repositorio y servicio de búsqueda, controladores, `throttle:busqueda`, comando
   `busqueda:reindexar`, calibración del umbral con el banco ampliado y el negativo,
   registro de acceso sin consulta (R3), pruebas HTTP. Contrato → `implemented`.
3. **Fase 3:** sitio — `combobox` accesible en el encabezado, página `/buscar` con los
   campos nuevos, pausa de 250 ms; axe; p95 contra staging.

## 9. Límites que quedan

- La relevancia se calibra con un banco que escribió quien conoce el catálogo; la medida
  real requiere consultas de ciudadanos (R2).
- Los sinónimos («carro» ↔ «vehículo», «cédula» ↔ «documento») no los resuelven los
  trigramas; si la Entidad los necesita, se incorporan por el campo `palabras_relacionadas`
  del trámite, que hoy no se indexa. Decisión de la Entidad, no de TIC.
- No se probó con lector de pantalla ni dispositivos reales (fase 3).
