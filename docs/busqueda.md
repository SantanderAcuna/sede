# Búsqueda de la sede (T-22 · H-23)

Documento de diseño y de verificación de la búsqueda real: sugerencias mientras se
escribe, tolerancia a erratas y resultados por pertinencia.

- **Resuelve:** H-23 · RF-B1-005, RF-B1-006, RF-B2-035, RF-B2-036, RF-B3-067 · D-03.
- **Decisión de arquitectura:** [ADR-0017](adr/README.md).
- **Estado:** entrega 1 (contrato, datos, premisa medida; PR #380) y entrega 2 (API, límite de peticiones, reindexado, calibración, registro sin la consulta) · entrega 3 (sitio) pendiente. Se llaman *entregas* (E1-E3) para no confundirlas con las fases 0-4 del plan de remediación.
- **Criterios y casos de prueba:** [`casos-de-prueba-t22-busqueda.md`](../sede-electronica-doc/01-estructura-identidad/casos-de-prueba-t22-busqueda.md) (§5 y §7 de este documento sólo remiten a él).
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
| `GET /api/v1/busqueda/sugerencias?q=&limite=` | ≤ 10 × {título, categoría, tipo, url, score} | `implemented` (entrega 2) |
| `GET /api/v1/buscar?q=&tipo=&page=&per_page=` | Colección paginada × {título, categoría, extracto, fecha, autor, miniatura, url, score} | `implemented` (entrega 2) |

## 2. Qué se midió (entrega 1)

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

## 2.1 Calibración (entrega 2)

Los parámetros están en `backend/config/busqueda.php` y salen de esta medición, **HECHO**,
sobre el catálogo real en PostgreSQL 16 local. Banco de calibración: 30 consultas con errata
(`banco-erratas.php`) y 12 negativas (`banco-negativo.php`). Celdas = aciertos entre los tres
primeros / negativas con ruido.

| Umbral \ cobertura | 0,40 | **0,45** | 0,50 | 0,55 | 0,58 |
|---|---|---|---|---|---|
| 0,35 | 30 / 2 | 30 / 2 | 30 / 2 | 25 / 1 | 23 / 1 |
| 0,40 | 30 / 2 | 30 / 1 | 30 / 1 | 25 / 0 | 23 / 0 |
| **0,45** | 30 / 0 | **30 / 0** | 30 / 0 | 25 / 0 | 23 / 0 |
| 0,50 | 30 / 0 | 30 / 0 | 30 / 0 | 25 / 0 | 23 / 0 |
| 0,55 | 28 / 0 | 28 / 0 | 28 / 0 | 25 / 0 | 23 / 0 |

(Celda = aciertos de 30 entre los tres primeros / consultas negativas con ruido de 12; medido
con el SQL del repositorio sobre PostgreSQL 16.) **Sin** cobertura por palabra, el umbral 0,50
acierta 30 pero deja pasar la negativa «licencia de conduccion» (0,600 con «licencia de
exhumación»). Los aciertos más difíciles —«esterilisacion canina» y «estratificasion
socioeconomica»— valen 0,500. Se eligió **0,45 / 0,45**: está en la meseta (umbral 0,45–0,50 con
cobertura 0,40–0,50) y queda a un paso de cada borde, en lugar de al filo.

**Validación con parámetros congelados** (`banco-validacion.php`, escrito después): **10 / 10**
positivos; 5 de 6 negativos sin ruido. El sexto, «impuesto de renta» (impuesto nacional, sin
trámite municipal), devuelve un trámite de registro de contribuyentes de industria y comercio
(puntaje 0,667). **Es un falso positivo conocido y aceptado**: excluirlo exigiría un umbral que
deja fuera aciertos legítimos. Queda fijado en una prueba para que mejorarlo o empeorarlo no pase
inadvertido.

**Qué no demuestra esta calibración:** los bancos y la elección de parámetros son del mismo autor
y con el catálogo a la vista (R2); la elección se hizo sobre el banco de calibración, no sobre el de
validación; y los sinónimos y siglas («Sisbén») no los resuelven los trigramas (§9).

## 2.2 Registro de acceso sin la consulta (R3, AC-22.7)

En `docker/nginx/nginx.conf`, dos `map` (`$uri_registrable`, `$referer_registrable`) recortan la
cadena de consulta cuando trae el parámetro `q` o `buscar`, y `log_format principal` los usa en
lugar de `$request` y `$http_referer`. **HECHO:** probado con nginx 1.24 real (`nginx -t` y 11
peticiones: ninguna consulta ni su referer llegó al registro; `?page=2&per_page=20`,
`?categoria=x&quest=1` y `/buscar` sin consulta se conservan). En CI, una guarda aplica las
expresiones del propio archivo a URL de ejemplo. **No está probado en staging:** TC-BUS-23 (leer el
registro real) lo ejecuta el titular.

**Informes de la política de contenido.** Su cuerpo trae la URL del documento (`document-uri`, `referrer`); una violación en `/buscar?q=…` llevaría la consulta al registro `csp_informe`. nginx no puede reescribir un JSON, así que un tercer `map` (`$informe_registrable`) sustituye el cuerpo completo por un aviso cuando contiene un parámetro `q` o `buscar` (también como `\u0026`). Se pierde el detalle de esos informes, no el hecho de que ocurrieron. HECHO: probado con nginx 1.24 real con cinco informes (tres con consulta omitidos, dos sin ella conservados, 0 fugas); la guarda de CI aplica la misma expresión.

La aplicación tampoco registra la consulta: ninguna capa llama a `Log::` con ella y una prueba lo
comprueba para éxito, validación fallida y 429.

## 3. Decisiones

| # | Decisión | Alternativa descartada | Motivo |
|---|---|---|---|
| D1 | `pg_trgm` + índices GIN en PostgreSQL | Meilisearch / Elasticsearch | HECHO: 124 trámites; un servicio nuevo es otra pieza que operar y parchear. HIPÓTESIS sobre el volumen futuro (ver R1). |
| D2 | Texto normalizado **al guardar** (`TextoBuscable`) | `unaccent()` en la consulta | `unaccent()` es `STABLE`, no se indexa sin envolverla; una extensión menos. Mismo criterio que ya usa `Tramite::busqueda`. |
| D3 | Dos columnas por tabla: título y texto completo | Una sola | Para pesar más una coincidencia en el título. |
| D4 | Dos operaciones distintas | Una con `autocompletar` | Ver §1. |
| D5 | `word_similarity` y umbral por transacción (`SET LOCAL` / `set_config(…, true)`) | Umbral global del servidor | Cambiar el global afectaría a toda consulta de la base. |
| D6 | Desempate por `similarity()` de la cadena entera | Orden alfabético | HECHO: sin él, «Exención del impuesto predial…» ganaba a «Impuesto predial…» por orden alfabético. |
| D7 | Umbral **0,45**, calibrado en la entrega 2 (§2.1) | 0,4 (ruido) · 0,5–0,55 (sin margen sobre el piso de 0,500) | HECHO: con la cobertura por palabra (D8), 0,45 está en una meseta de 0,45–0,50 con 30/30 aciertos y 0 ruido; queda a ≈ 0,05 de cada borde. |
| D8 | **Cobertura por palabra:** cada palabra de la consulta con ≥ 4 letras (hasta 6) debe aparecer en el contenido con `word_similarity` ≥ 0,45 | Sólo el umbral global | HECHO: ningún umbral global separaba «licencia de conducción» (ruido, 0,600 con «licencia de exhumación») de los aciertos más difíciles (0,500). Es un cambio de diseño, no un ajuste de cifra. |

## 4. Riesgos

| # | Riesgo | Tipo | Mitigación | Cuándo |
|---|---|---|---|---|
| R1 | Un volumen mucho mayor que 124 filas degrada el p95 | HIPÓTESIS | AC-22.6 mide contra staging; el índice GIN ya existe | E3 |
| R2 | Los bancos son optimistas (los escribí yo, con el catálogo a la vista) y los parámetros se eligieron con uno de ellos | HECHO | Ampliados a 30 + 12 negativos + 10 de validación con los parámetros ya congelados (§2.1). Sigue pendiente pedir a la Entidad consultas reales de atención | E2 (parcial) |
| R3 | **El texto buscado queda en el registro de acceso.** `docker/nginx/nginx.conf` registraba `$request` (con `?q=`) y `$http_referer` (el sitio pide sus recursos con la página `/buscar?q=…` como referer). Un ciudadano puede escribir un número de documento. | HECHO el formato · INFERENCIA el contenido | **Mitigado en E2** (§2.2): si la consulta trae `q` o `buscar`, el registro lleva sólo la ruta, en la petición y en el `Referer`. **Quedan** el `error_log` de nginx y el registro de Cloudflare (§9). Los informes CSP se trataron después (§2.2). Revisar con el oficial de datos personales (Ley 1581) | E2 (parcial) |
| R4 | Cada pulsación es una petición: abuso o carga | INFERENCIA | `throttle:busqueda` en el servidor: **120 por minuto y por IP**, compartido por las dos operaciones, configurable con `BUSQUEDA_LIMITE_POR_MINUTO`; 429 con `Retry-After`. La pausa de 250 ms en el cliente es de la entrega 3 | E2 (servidor) · E3 (cliente) |
| R5 | Las políticas indexan texto de ejemplo (`es_ejemplo`) | HECHO | La página enlazada muestra ese mismo texto con su aviso; al aprobarse el texto, el hook reindexa solo | — |
| R6 | Relevancia subjetiva | HECHO (Guía §D-03) | Orden documentado y reproducible: la fórmula está en el código y el banco en el repositorio | E2 |
| R7 | Si `TextoBuscable::normalizar` cambia, lo guardado queda desfasado | INFERENCIA | Prueba que fija su salida; comando `php artisan busqueda:reindexar` (idempotente, `--simular` cuenta sin escribir) | E2 |
| R8 | Sin Cloudflare caché sobre `/api/v1/buscar` el origen recibe todo | HIPÓTESIS | Medir antes de decidir; no se cachea una búsqueda con texto del ciudadano | E3 |

## 5. Criterios de aceptación

Los AC-22.1 a AC-22.12 los define la auditoría del Módulo 01 (§8, T-22; es un archivo local). Su
resumen de lectura, con la entrega en que se comprueba cada uno, está en el §1 de los casos de
prueba (enlace arriba). Los umbrales que la auditoría marca como «míos» (8 de 10, p95 < 300 ms)
se revisan tras medir.

## 6. Definición de hecho (DoD)

La de T-22 es la DoD global del plan (auditoría §8.0): AC ejecutados con su salida en el PR, CI 7/7,
prueba que guarda la corrección (en rojo antes y en verde después, o la razón por la que no aplica),
`make trazabilidad` regenerado, sin secretos ni IP, reversión descrita y, desde 2026-10-29,
documentación al día y casos de prueba ejecutados con su resultado anotado. Más dos ítems propios:

- [ ] Contrato validado (`redocly lint`, sin avisos nuevos) y tipos regenerados.
- [ ] Límites declarados, no ocultos (§9).

## 7. Casos de prueba

Viven en el documento enlazado arriba: 32 casos (TC-BUS-01 a TC-BUS-32) con pasos, resultado
esperado, tipo y resultado obtenido. De la entrega 1, TC-BUS-01 a 12 están ejecutados en CI (08, 09
y 10 con salvedades escritas allí) y TC-BUS-13 (revertir la migración) está **sin ejecutar**. Los de
la entrega 2 (TC-BUS-14 a 24) tienen su estado y su evidencia en ese documento; los de la entrega 3
están sin ejecutar porque aún no se han construido. La matriz AC → TC está en la
auditoría (§10).

## 8. Plan por entregas

1. **Entrega 1 (PR #380):** contrato, extensión e índices, texto buscable, premisa medida.
   Sin ruta nueva: las operaciones siguen `pending`.
2. **Entrega 2 (hecha):** repositorio y servicio de búsqueda, controladores, `throttle:busqueda`,
   comando `busqueda:reindexar`, calibración (§2.1) con el banco ampliado y el negativo,
   registro de acceso sin consulta (§2.2), pruebas HTTP. Contrato → `implemented`.
3. **Entrega 3:** sitio — `combobox` accesible en el encabezado, página `/buscar` con los
   campos nuevos, pausa de 250 ms; axe; p95 contra staging. Antes de codificarla, la
   especificación de interacción (AC-22.12) la aprueba el titular.

## 9. Límites que quedan

- La relevancia se calibra con bancos que escribió quien conoce el catálogo; la medida
  real requiere consultas de ciudadanos (R2). Un falso positivo está medido y aceptado
  («impuesto de renta», §2.1).
- **El registro sin la consulta tiene bordes** (§2.2): el `error_log` de nginx copia la línea de
  petición cuando una petición falla aguas arriba (nginx no permite filtrarla), y Cloudflare
  conserva su propio registro con la URL completa. El primero sólo aparece cuando algo falla; el
  segundo depende de la configuración de Cloudflare (retención y campos de los registros), no de
  este repositorio. Hay que decidirlos con el oficial de datos personales. Una alternativa de
  diseño, que cambiaría el contrato, sería enviar la consulta por `POST`; no se adoptó porque
  rompe el enlace compartible de una búsqueda y los resultados cacheables.
- El límite de 120 peticiones por minuto y por IP es una estimación (INFERENCIA: una persona
  escribiendo genera una petición por pausa de 250 ms; una oficina que comparte salida suma). No
  se midió con tráfico real. **Riesgo concreto:** si muchos ciudadanos comparten una IP pública
  —la red móvil con NAT de operador, o el wifi de una oficina de atención—, el cupo se agota
  para todos y reciben 429 sin haber hecho nada. HIPÓTESIS (no hay datos de la distribución de
  IP de los visitantes). Para detectarlo: contar los 429 de `/buscar` en el registro de acceso
  tras la salida a producción y subir `BUSQUEDA_LIMITE_POR_MINUTO` sin tocar código si hace falta.
- El texto de búsqueda de una política se calcula al guardarla; las políticas sembradas son de
  ejemplo (R5).
- Los sinónimos («carro» ↔ «vehículo», «cédula» ↔ «documento») no los resuelven los
  trigramas; si la Entidad los necesita, se incorporan por el campo `palabras_relacionadas`
  del trámite, que hoy no se indexa. Decisión de la Entidad, no de TIC.
- No se probó con lector de pantalla ni dispositivos reales (entrega 3).
