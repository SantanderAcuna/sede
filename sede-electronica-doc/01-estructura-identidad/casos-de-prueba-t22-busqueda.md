# Casos de prueba · T-22 Búsqueda de la sede

| | |
|---|---|
| **Tarea** | T-22 · Buscador real: sugerencias, tolerancia ortográfica y pertinencia (hallazgo H-23) |
| **Criterios** | AC-22.1 a AC-22.12. Los define la auditoría del Módulo 01 (§8, T-22), que es un archivo local; el §1 de este documento los resume para que el repositorio se baste. |
| **Diseño y decisiones** | [`docs/busqueda.md`](../../docs/busqueda.md) · ADR-0017 en [`docs/adr/README.md`](../../docs/adr/README.md) |
| **Estado (2026-10-29)** | Entrega 1 ejecutada. Entrega 2 ejecutada en CI y en local, con **TC-BUS-17 y la lectura del registro de TC-BUS-23 pendientes** de staging. Entrega 3 **sin ejecutar** (no se ha construido). |
| **Convención de evidencia** | **HECHO** = medido o leído en el repositorio · **INFERENCIA** = deducido de hechos · **HIPÓTESIS** = no verificado todavía |

## 0. Cómo se lee este documento

- Un **AC** dice *qué* debe cumplirse; un **TC** dice *cómo* se comprueba y guarda el *resultado*. Todo AC tiene al menos un TC y todo TC verifica al menos un AC (o un ítem de la definición de hecho). La matriz AC → TC vive en la auditoría (§10).
- **Tipo.** *Auto.* = lo ejecuta CI o un script del repositorio. *Manual* = lo ejecuta una persona.
- **Resultado.** ✅ pasó · 🟡 pasó con una salvedad que se escribe · ❌ falló · ⬜ sin ejecutar. Todo ✅ y 🟡 trae fecha, entorno y evidencia; no se anota un resultado que no se haya obtenido.
- **Entregas.** Se llaman *entregas* (E1, E2, E3) para no confundirlas con las fases 0-4 del plan de remediación.
- **Esta carpeta es pública.** No debe aparecer aquí ningún secreto, IP, nombre de servidor ni detalle explotable de un hallazgo abierto. En los comandos, `<sede>` es la dirección de staging.

## 1. Criterios que cubre (resumen de lectura)

| AC | En una línea | Entrega |
|---|---|---|
| AC-22.1 | `GET /busqueda/sugerencias?q=`: ≤ 10 sugerencias con título, categoría, tipo y URL; entradas inválidas → 422; contrato `implemented` | E2 |
| AC-22.2 | Componente: con < 3 caracteres no consulta; con ≥ 3 consulta tras 250 ms | E3 |
| AC-22.3 | Banco de erratas: ≥ 8 de 10 con el resultado esperado entre los 3 primeros (en E2, ≥ 30 consultas y por la API) | E1 consulta · E2 API |
| AC-22.4 | `GET /buscar?q=`: sólo contenido de la sede; fecha, categoría, título, extracto; `autor` y `miniatura` en `null`; filtro `tipo`; paginación | E2 API · E3 página |
| AC-22.5 | axe sin violaciones críticas/serias; patrón `combobox` accesible con teclado | E3 |
| AC-22.6 | p95 < 300 ms sobre 100 consultas, desde el servidor y desde la sesión de trabajo | E3 |
| AC-22.7 | El texto buscado no queda en el registro de acceso ni en el referer | E2 |
| AC-22.8 | Banco negativo: consultas sin sentido o sin trámite devuelven lista vacía | E2 |
| AC-22.9 | Corregir un trámite o una política se refleja en la búsqueda sin reindexar a mano | E1 |
| AC-22.10 | Superar el límite de peticiones → 429 con `Retry-After` | E2 |
| AC-22.11 | `busqueda:reindexar` recalcula el texto buscable, es idempotente y no toca otras columnas | E2 |
| AC-22.12 | Especificación de interacción aprobada y cumplida (estados, textos, teclado, ≥ 44 px, sin carreras, sin tapar con el círculo de accesibilidad) | E3 |

## 2. Entorno y datos de prueba

| Elemento | Detalle |
|---|---|
| Catálogo | **HECHO:** 124 trámites de la entidad `0043` de SUIT, con el nombre publicado (`nombreEstandarizado_govco`) y el resumen recortado a 500 caracteres. |
| Banco de erratas | `backend/tests/Support/banco-erratas.php`: 10 consultas, **9 con una errata y 1 control sin errata** («vacunacion antirabica», sólo sin tildes). Cada acierto se identifica por el código SUIT del trámite, no por posición ni por texto. |
| Base de datos | Medición manual: PostgreSQL 16 local. CI: PostgreSQL 18 (imagen Alpine). **HECHO:** las pruebas PHP **no** se ejecutaron en el entorno de trabajo local (no pudo instalar las dependencias de PHP); su única ejecución es la de CI. |
| Staging | Despliegue en el servidor de staging; las pruebas contra él usan `<sede>`. |
| Sesgo declarado | El banco lo escribió quien tenía el catálogo a la vista: es optimista. Mide cuánto de la tolerancia es técnicamente posible, no cómo escriben los ciudadanos (riesgo R2 de `docs/busqueda.md`). |

## 3. Entrega 1 · contrato, datos y premisa medida (PR #380)

Evidencia común de los TC *Auto.* de esta entrega: **PR #380, fusionado el 2026-10-29; los 7 checks de CI (Contrato, Backend, Panel, Sitio, Pila, Dependencias, Credenciales) en verde** antes de la fusión.

| TC | Verifica | Pasos y esperado | Tipo | Resultado |
|---|---|---|---|---|
| TC-BUS-01 | AC-22.3, 22.6 | La extensión `pg_trgm` está instalada: `select count(*) from pg_extension where extname='pg_trgm'` → `1`. Prueba `IndicesDeBusquedaTest::test_la_extension_pg_trgm_esta_instalada`. | Auto. | ✅ CI, PR #380 |
| TC-BUS-02 | AC-22.3, 22.6 | Cada uno de los cuatro índices es `USING gin` con `gin_trgm_ops` sobre su columna (lectura de `pg_indexes`). Prueba `…::test_cada_indice_es_gin_con_trigramas_sobre_su_columna`. | Auto. | ✅ CI, PR #380 |
| TC-BUS-03 | AC-22.9 | Al crear un trámite y al corregir su nombre, `busqueda_titulo` y `busqueda` siguen el cambio. Prueba `…::test_el_tramite_mantiene_su_texto_buscable_al_guardarse_y_al_corregirse`. | Auto. | ✅ CI, PR #380 |
| TC-BUS-04 | AC-22.9 | El texto buscable de una política incluye su nombre y los títulos y párrafos de sus apartados, y cambia con ellos. Prueba `…::test_la_politica_mantiene_su_texto_buscable_con_el_texto_de_sus_apartados`. | Auto. | ✅ CI, PR #380 |
| TC-BUS-05 | AC-22.9 | El texto buscable no se rellena a mano: `create(['busqueda' => 'x'])` lo ignora. Prueba `…::test_el_texto_buscable_no_se_puede_rellenar_a_mano`. | Auto. | ✅ CI, PR #380 |
| TC-BUS-06 | AC-22.3, 22.9 | Normalización fija: tildes y eñes, comodines de `LIKE`, espacios y paréntesis, unión de nombre y resumen, texto de la política. Cinco pruebas de `TextoBuscableTest`. | Auto. | ✅ CI, PR #380 |
| TC-BUS-07 | AC-22.3 | Línea base: el `LIKE` actual sobre el banco de erratas no acierta ninguna. Prueba `BancoDeErratasTest::test_la_busqueda_actual_con_like_no_tolera_ninguna_errata`; la prueba `…::test_el_banco_se_refiere_a_tramites_que_existen` protege que el banco apunte a trámites reales. | Auto. | ✅ CI, PR #380. Medición manual previa en PG 16: **0 de 10** |
| TC-BUS-08 | AC-22.3 | Tolerancia a erratas por consulta SQL: ≥ 8 de 10 con el esperado entre los 3 primeros (`…::test_los_trigramas_ponen_el_resultado_esperado_entre_los_tres_primeros_en_8_de_10`). | Auto. | 🟡 CI, PR #380. Medición manual previa en PG 16: **10 de 10** en el primer o segundo puesto. **Salvedades:** banco optimista; el acierto más difícil da similitud **0,500**, sin margen; el umbral por defecto de `pg_trgm` (0,6) no encontraría «licensia» (0,556), por eso el umbral se fija por transacción y se calibra en E2. |
| TC-BUS-09 | AC-22.8 | Una consulta sin sentido («xyzxyz») devuelve 0 filas (`…::test_una_consulta_sin_sentido_no_devuelve_nada`). | Auto. | 🟡 CI, PR #380. Cubre **una** consulta; el banco negativo es TC-BUS-20. Medición manual: «pasaporte» (no hay trámite) devuelve 2 filas con umbral 0,4 («aportes») y 0 con 0,5. |
| TC-BUS-10 | AC-22.3, 22.6 | El operador `<%` usa el índice GIN con el umbral por transacción: `EXPLAIN` de `'licensia' <% busqueda_titulo` con `enable_seqscan=off` muestra `tramites_busqueda_titulo_trgm` y devuelve ≥ 1 fila (`…::test_el_operador_de_palabra_usa_el_indice_gin_con_un_umbral_por_transaccion`). | Auto. | 🟡 CI, PR #380. **Salvedad:** con 124 filas el planificador prefiere recorrer la tabla; sólo se prueba que el índice **puede** usarse. Su efecto en el p95 se mide en TC-BUS-30. |
| TC-BUS-11 | AC-22.1, 22.4 | El contrato es válido: `npx @redocly/cli@2.55.0 lint ../contract/openapi.yaml` (desde `panel/`) sin avisos nuevos. | Auto. | ✅ CI (job Contrato), PR #380 |
| TC-BUS-12 | AC-22.1, 22.4 | `DerivaDelContratoTest` no exige ruta para las operaciones `x-status: pending` (`sugerirBusqueda`, `buscarGlobal`). | Auto. | ✅ CI (job Backend), PR #380 |
| TC-BUS-13 | DoD «reversión» | Revertir la migración: `php artisan migrate:rollback --step=1` quita columnas e índices y **conserva** la extensión; luego `migrate` la reaplica. | Manual | ⬜ **Sin ejecutar.** La migración tiene `down()`, pero ni CI ni el entorno de trabajo ejecutan el rollback. **Pendiente:** automatizarlo como prueba o ejecutarlo en un entorno de pruebas. |

**Lo que la entrega 1 no demuestra (HECHO, no oculto):**

- La migración se aplicó en staging por el despliegue (verde y API sana), pero **no se inspeccionaron** las tablas de staging para confirmar columnas e índices. La evidencia es indirecta.
- Las pruebas nuevas verifican columnas y una extensión que no existían antes del PR: habrían fallado antes por construcción. No se corrió la comprobación «rojo antes, verde después» (principio 3 de la auditoría); para esta entrega se declara **no aplicable** por ser funcionalidad nueva, no corrección de un defecto.
- Las cifras de la medición manual (0/10, 10/10, 0,500) son de PostgreSQL 16; en CI la prueba las repite en PostgreSQL 18 con un umbral de 8 de 10, no con las cifras exactas.

## 4. Entrega 2 · API, límite, reindexado, calibración y privacidad

Construida y ejecutada en el PR #386 (2026-10-29). Lo que dice ✅ lo respalda CI 7/7 o una medición local con su cifra; lo que dice ⬜ o 🟡 no se ha completado y se explica.

| TC | Verifica | Pasos y esperado | Tipo | Resultado |
|---|---|---|---|---|
| TC-BUS-14 | AC-22.1 | `GET /api/v1/busqueda/sugerencias?q=licen` sobre el catálogo sembrado: `200`, `data` con ≤ 10 elementos, cada uno con título, categoría, tipo, URL y `score`; el primero relevante; sobre `success/message/data/errors`. | Auto. | ✅ CI 7/7, PR #386 (`BusquedaApiTest::test_las_sugerencias_traen_los_campos_del_contrato_y_se_ordenan_por_pertinencia`, `…_no_pasan_de_diez_y_respetan_limite`). Forma exacta de los siete campos y orden por pertinencia. |
| TC-BUS-15 | AC-22.1 | Validación: `q` de 2 y de 121 caracteres, `q` ausente, `limite=11` → `422` con el sobre de error y `errors` por campo. | Auto. | ✅ CI, PR #386 (`…::test_la_validacion_de_las_sugerencias_responde_422_en_espanol`, `…::test_tres_caracteres_exactos_son_validos`). Mensajes en español; `q` de 2, de 121, ausente, `limite` 0 y 11. |
| TC-BUS-16 | AC-22.1, 22.4 | Contrato: las dos operaciones pasan a `x-status: implemented`; `DerivaDelContratoTest` exige ahora su ruta; `redocly lint` limpio; `make tipos` regenera los tipos sin cambios inesperados. | Auto. | ✅ Lint: 0 errores y 25 avisos, **los mismos 25 que `develop`** (medido en local y en CI). `make tipos` no cambió los tipos (el estado `x-status` no los altera); `make trazabilidad` movió las dos filas a `implemented`. `DerivaDelContratoTest` ✅ en CI (Backend: 374 pruebas, 4423 aserciones). |
| TC-BUS-17 | AC-22.1 | `curl -s '<sede>/api/v1/busqueda/sugerencias?q=licen'` ejecutado **desde la sesión de trabajo y desde el servidor**; se anotan estado, número de sugerencias y que el contrato coincide. | Manual | 🟡 **Parcial (2026-10-29, staging, PR #387).** ✅ **Desde la sesión de trabajo:** `q=licen` → 200, 10 sugerencias con los siete campos del contrato; `q=licensia` → 200, 10; `q=ab` → 422 «Escriba al menos 3 caracteres para buscar.»; `limite=11` → 422; `/buscar` con «estratificasion socioeconomica» → 200, 1 resultado con los diez campos y `autor`/`miniatura` en `null`; «pasaporte» → 200, 0 resultados; `tipo=politica` filtra. Tiempos de 0,2 a 0,7 s medidos desde fuera, **no** son la medición de AC-22.6 (p95, entrega 3). ⬜ **Desde el servidor:** lo ejecuta el titular. Observación: `fecha` de un trámite es hoy la de su carga en la sede (`publicado_en`), no la de publicación oficial. |
| TC-BUS-18 | AC-22.3 | El banco de erratas, ampliado a ≥ 30 consultas (incluidas de una sola palabra y consultas reales de atención al ciudadano, si la Entidad las aporta), ejecutado **a través de la API**: ≥ 8 de cada 10 (o ≥ 80 %) con el esperado entre los 3 primeros. | Auto. | ✅ CI, PR #386 (`BancoDeErratasTest`, 30 consultas por la API, exige ≥ 27 de 30 entre los tres primeros). La cifra exacta no la imprime CI; **medida en local** con el SQL del repositorio sobre PostgreSQL 16: **30 / 30**. Límite: banco escrito por quien calibró, con el catálogo a la vista. |
| TC-BUS-19 | AC-22.4 | `GET /api/v1/buscar?q=…`: sólo trámites y políticas; cada resultado con fecha, categoría, título y extracto; `autor` y `miniatura` presentes y en `null`; `tipo=tramite` y `tipo=politica` filtran; `per_page=101` → `422`; la paginación respeta `page`/`per_page`; el orden es por pertinencia con desempate por similitud de la cadena entera. | Auto. | ✅ CI, PR #386 (`BusquedaApiTest`: diez campos, `autor`/`miniatura` en `null`, `tipo`, `per_page=101` → 422, paginación sin repetir ni perder filas, enlaces con `q`, política con su URL, trámite sin ficha ausente, extracto sin partir palabras). |
| TC-BUS-20 | AC-22.8 | Banco negativo (consultas sin sentido y términos que no existen, p. ej. «pasaporte») vía API → `data` vacío en todas. | Auto. | ✅ CI, PR #386 (`BancoDeErratasTest::test_el_banco_negativo_no_devuelve_nada_por_ninguna_de_las_dos_operaciones`: 12 consultas × 2 operaciones). **Medido en local:** 0 con ruido. Falso positivo conocido en el banco de validación: «impuesto de renta» (docs/busqueda.md §2.1), fijado por prueba. |
| TC-BUS-21 | AC-22.10 | Se superan las peticiones permitidas en cada operación → `429` con el sobre de error y `Retry-After`; con una ráfaga normal de escritura no se alcanza el límite. | Auto. | ✅ CI, PR #386 (`…::test_el_limite_de_peticiones_responde_429_con_retry_after`, `…::test_cada_ip_tiene_su_propio_cupo`). Cupo compartido entre las dos operaciones. **No probado:** que 120/min sea el valor adecuado con tráfico real (estimación). |
| TC-BUS-22 | AC-22.11 | Se corrompe `busqueda`/`busqueda_titulo` de una fila y se ejecuta `php artisan busqueda:reindexar`: vuelve a coincidir con `TextoBuscable`; una segunda ejecución no cambia nada; las demás columnas quedan iguales. | Auto. | ✅ CI, PR #386 (`ReindexarBusquedaTest`: repara trámites y políticas, idempotente, no toca otras columnas ni `updated_at`, `--simular` no escribe, catálogo vacío). |
| TC-BUS-23 | AC-22.7 | En staging: se hace una búsqueda con un texto reconocible y se lee el registro de acceso de nginx; **el texto no aparece** en la línea de acceso ni en el referer de las peticiones posteriores. La lectura del registro la hace el responsable del servidor. La solución se revisa con el Oficial de Protección de Datos. | Manual | 🟡 **Parcial.** ✅ nginx 1.24 real en el entorno de trabajo: `nginx -t` correcto y 11 peticiones, ninguna consulta ni referer con consulta llegó al registro; las URL sin `q`/`buscar` conservan su cadena. ✅ Guarda estática en CI (job Pila) que aplica las regex del archivo y falla si se rompe (probado con tres roturas deliberadas). ⬜ **Falta la lectura del registro real de staging**, que hace el titular. ✅ También los informes CSP (cuerpo con `q`/`buscar` → aviso en lugar del informe; nginx real, 5 informes, 0 fugas). Bordes no cubiertos: `error_log` de nginx y Cloudflare (docs/busqueda.md §9). **Procedimiento para el titular:** en staging, con la pila del punto de entrada, buscar en el sitio un texto reconocible (p. ej. `zzprueba123`), esperar un minuto y buscarlo en el registro del contenedor de nginx (`docker compose logs nginx` o el equivalente del servidor, filtrando por la cadena); esperado: 0 coincidencias. Repetirlo con el `error_log` y, si Cloudflare conserva registros, con ellos; anotar el resultado aquí. |
| TC-BUS-24 | AC-22.3, 22.8 | Calibración: se barre el umbral (por ejemplo, de 0,35 a 0,6) sobre el banco positivo y el negativo y se registra la tabla aciertos/ruido; se elige el valor que satisface a la vez AC-22.3 y AC-22.8. **Si ninguno lo hace, el resultado es esa tabla y una consulta al titular, no un banco recortado.** | Auto. + registro | ✅ Tabla de calibración 5 × 5 en `docs/busqueda.md` §2.1, medida en local (PostgreSQL 16, catálogo real). Se eligió 0,45 / 0,45, en la meseta. **Hizo falta añadir cobertura por palabra:** ningún umbral global satisfacía AC-22.3 y AC-22.8 a la vez. Validación con parámetros congelados: 10 / 10 y 5 de 6 negativos limpios. **Revisión posterior (`docs/busqueda.md` §2.3):** un banco generado por reglas (331 + 300) mostró que el 30 / 30 era optimista —≈ 82 % de acierto con 0,45 / 0,45— y que la cobertura 0,40 sube a ≈ 90 % sin ruido nuevo (0 / 12 y 0 / 300). Vigente: **0,45 / 0,40**. La cifra con ciudadanos reales sigue pendiente. |

## 5. Entrega 3 · sitio, accesibilidad y rendimiento

Todos **sin ejecutar** (⬜). TC-BUS-25 a 28 y 32 requieren antes la especificación de interacción aprobada (AC-22.12).

| TC | Verifica | Pasos y esperado | Tipo | Resultado |
|---|---|---|---|---|
| TC-BUS-25 | AC-22.2, 22.12 | Componente: con < 3 caracteres no consulta; con ≥ 3 consulta una sola vez tras 250 ms de pausa; una respuesta de una consulta anterior no reemplaza a la última (se cancela o se ignora). | Auto. | ⬜ |
| TC-BUS-26 | AC-22.5 | axe-core sobre `/buscar` y sobre el encabezado con el combobox abierto y cerrado: 0 violaciones críticas o serias. | Auto. | ⬜ |
| TC-BUS-27 | AC-22.5, 22.12 | Navegador: roles y atributos del combobox (`aria-expanded`, `aria-controls`, `listbox`, `aria-activedescendant`); teclado ↑ ↓ Enter Esc Tab; el foco visible no se pierde. | Auto. | ⬜ |
| TC-BUS-28 | AC-22.12 | Estados vacío, cargando, con resultados, sin resultados, error de red y `429` con sus textos en español; objetivos táctiles ≥ 44 px; capturas a 360 y 1280 px, incluida la convivencia con el círculo flotante de accesibilidad. | Auto. + capturas | ⬜ |
| TC-BUS-29 | AC-22.5 | Recorrido con un lector de pantalla real (NVDA o VoiceOver). | Manual | ⬜ **Requiere una persona con lector de pantalla.** Hoy es un límite declarado: no se ha probado con lector ni con dispositivos reales. |
| TC-BUS-30 | AC-22.6 | **Desde el servidor:** el script de carga de la entrega 3 lanza 100 consultas secuenciales (banco de erratas y términos del catálogo) tras 10 de calentamiento descartadas, contra la API del propio servidor; p95 < 300 ms. Aísla el tiempo de la aplicación. | Manual | ⬜ |
| TC-BUS-31 | AC-22.6 | **Desde la sesión de trabajo:** el mismo método contra `<sede>`; se anota también la latencia de una petición de referencia a un endpoint sin lógica. Cumple si p95 < 300 ms; si sólo esta medición lo supera, se informa la diferencia con la referencia y se decide con el titular. | Auto. | ⬜ |
| TC-BUS-32 | AC-22.4 | Página `/buscar` del sitio: a partir de `?q=` muestra cada resultado con fecha, categoría, título y extracto (y `autor` y `miniatura` sólo cuando existan), enlaza a la URL devuelta, pagina con `page`/`per_page` y muestra un texto propio cuando no hay resultados. | Auto. | ⬜ |
