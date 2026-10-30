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
cobertura 0,40–0,50) y queda a un paso de cada borde, en lugar de al filo. **Revisado en §2.3:** la
medición sintética mostró que, dentro de esa misma meseta, 0,40 acierta más erratas reales; la
cobertura vigente es **0,40**.

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

## 2.3 Medición sintética: cuánto acierta de verdad (R2)

**Problema.** Los tres bancos manuales los escribí con el catálogo a la vista; «30 de 30» no es la
tasa de aciertos que cabe esperar con ciudadanos. Para tener una cifra que no dependa de lo que yo
elija, se generó un banco **por reglas y con semilla fija** (`backend/tests/Support/banco-sintetico.php`,
reglas en su cabecera): por cada trámite, tres fragmentos de 1 a 3 palabras de su nombre y UNA errata
(sibilante s/c/z, b/v, h muda, letra omitida o duplicada, letras trocadas, tecla vecina); y 300
consultas negativas de dos palabras corrientes del español que no están en el catálogo. Acierto =
un trámite cuyo nombre contiene el fragmento original entre los tres primeros. HECHO, medido con
el SQL del repositorio sobre PostgreSQL 16 local.

**Dos errores míos, corregidos durante la medición.**
1. La primera versión del generador no quitaba el «(s)» de los nombres («licencia(s)» → «licencia s»)
   y producía fragmentos como «s de»; con ello medí 69,5 % y casi lo anuncié como el recall real.
   Era un defecto de la prueba, no del buscador.
2. Mi base de calibración tenía los 124 trámites de la fuente, pero el sembrador publica uno menos:
   `T28661` no declara su término de obtención y se descarta. Se sacó de la base y **todas las cifras
   de abajo se repitieron con el catálogo que de verdad se publica**. (Banco manual 30/30 y validación
   10/10 se mantienen.)

| Umbral / cobertura | Recall top-3 (semilla de elección) | Semillas ajenas (11 · 222 · 3333) | Ruido manual | Ruido sintético (2 conjuntos) |
|---|---|---|---|---|
| 0,45 / 0,45 (anterior) | 85,0 % | 81,5 · 83,0 · 84,8 % | 0 / 12 | 0 / 300 · 0 / 300 |
| **0,45 / 0,40 (vigente)** | **91,1 %** | **88,8 · 90,1 · 90,1 %** | **0 / 12** | **0 / 300 · 0 / 300** |
| 0,45 / 0,35 | 92,0 % | 91,5 · 91,6 · 91,3 % | 1 / 12 | 0 / 300 · 0 / 300 |
| 0,40 / 0,40 | 93,9 % | 90,6 · 92,6 · 92,9 % | 2 / 12 | 4 / 300 · 1 / 300 |
| 0,50 / 0,40 | 89,9 % | 87,9 · 89,8 · 89,5 % | 0 / 12 | 0 / 300 · 0 / 300 |

La cobertura 0,40 gana unos 6 puntos de recall sin costo de ruido medido. 0,35 compra uno o dos
puntos más a cambio de ruido ya visible en el banco manual, y bajar el umbral a 0,40 mete ruido
también en el sintético; ambos se descartaron. **La elección se hizo con una semilla; las otras tres
confirman la ganancia (+5 a +8 puntos).**

**Lectura** (0,45 / 0,40; cuatro semillas sumadas, 1 303 consultas). Por tipo de errata:
sibilantes, letras duplicadas, h muda y omisión de una letra aciertan ≈ 99–100 %; b/v ≈ 88 % y la
tecla vecina ≈ 90 %; **dos letras trocadas, 161 de 247 (≈ 65 %)**, que para los trigramas es una
errata doble. Por largo de la palabra con errata: **4–5 letras, 96 de 142 (≈ 68 %)**; 6–8 letras
≈ 85 %; 9 o más ≈ 99 %. Por largo de la consulta: una sola palabra ≈ 80 %, dos o tres ≈ 93 %.
La debilidad está en palabras cortas con la errata en medio, en letras trocadas y en consultas de
una palabra; las palabras largas, que son las de la mayoría de los nombres de trámites, están bien
cubiertas. Todo esto es de **una errata por consulta**: dos erratas, sinónimos, siglas y jerga no
están medidos.

**Qué no demuestra.** Mismo autor, mismo catálogo, errata simulada. La cifra de ciudadanos reales
sigue pendiente de las consultas que aporte la Entidad. El archivo sintético es una **guarda de
regresión congelada** (la prueba exige 85 % y a lo sumo 1 / 300 con ruido; hoy 88,8 % y 0); no es
una validación independiente nueva, porque la elección de 0,40 salió de la misma familia de reglas.

## 2.4 Carga que produce quien escribe, y qué protege el límite (R4)

**Pregunta.** El límite de peticiones por IP se fijó como «lo que genera quien escribe con
normalidad», pero nadie había contado cuántas peticiones son. Con sugerencias mientras se
escribe, el cliente consulta cada vez que el ciudadano se detiene más tiempo que la pausa
(*debounce*); si su ritmo de escritura es más lento que la pausa, consulta en **cada** tecla.

**Datos publicados** (verificados en las fuentes): intervalo medio entre teclas en escritorio
**238,66 ms** (DE 111,6), y **más de 480 ms, hasta 900 ms**, en el 10 % más lento (Dhakal et al.,
CHI 2018, 168 000 voluntarios). En móvil, **36,17 palabras por minuto** de media, 26,3 entre los 50 y
los 59 años y 24,3 en menores de 10 (Palin et al., MobileHCI 2019, 37 370 voluntarios; no publican
intervalos entre teclas). Convertidos con la convención de 5 caracteres por palabra (INFERENCIA):
≈ 333 ms entre teclas a 36 WPM y ≈ 460 ms a 26 WPM.

**Modelo** (INFERENCIA; simulación de 4 000 consultas por celda, intervalos lognormales con el
coeficiente de variación del dato de escritorio, que es un supuesto mío para los otros grupos; largo de
la consulta tomado del banco sintético, mediana 14 caracteres; mínimo de 3 caracteres):

| Peticiones por consulta | pausa 250 ms | 300 ms | **350 ms** | 400 ms | 500 ms |
|---|---|---|---|---|---|
| escritorio, ritmo medio | 5,4 | 3,7 | 2,7 | 2,0 | 1,4 |
| móvil, 36 WPM | 8,7 | 6,7 | 5,3 | 4,1 | 2,5 |
| móvil, 50–59 años (26 WPM) | 11,2 | 10,0 | 8,6 | 7,1 | 4,9 |
| escritor lento (≈ 500 ms) | 11,7 | 10,5 | 9,4 | 8,1 | 5,8 |

Con la pausa de **250 ms** de la auditoría, una persona en móvil dispara 9–11 peticiones por
consulta: la pausa queda por debajo de su ritmo y consulta casi en cada tecla. Con **350 ms** baja a
5–9.

**Probabilidad de que un minuto supere el límite cuando varias personas comparten IP** (móvil a
36 WPM; cada persona hace en promedio 1 consulta por minuto; 20 000 minutos simulados):

| Personas activas en la misma IP | pausa 250 ms: > 60 | > 120 | pausa 350 ms: > 60 | > 120 | > 240 |
|---|---|---|---|---|---|
| 5 | 20,1 % | 0,2 % | 1,2 % | 0,0 % | 0,0 % |
| 10 | 79,9 % | 13,7 % | 31,8 % | **0,1 %** | 0,0 % |
| 20 | 99,9 % | 88,6 % | 96,9 % | 28,6 % | 0,0 % |
| 40 | 100 % | 100 % | 100 % | 99,6 % | 22,6 % |

**Lectura.**
1. El tope real de 60 por minuto que existía hasta #393 habría dado 429 a una oficina de 5
   personas el 20 % de los minutos con la pausa de 250 ms: el error no era teórico.
2. **Con una pausa de 350 ms, 120 por minuto protege a unas 10 personas buscando a la vez detrás de
   una IP (0,1 % de minutos con 429); para 20 haría falta 240.** Esa es ahora la afirmación del
   límite, y es una **INFERENCIA** sujeta a los supuestos de arriba, no una medición con ciudadanos.
3. No se cambia el valor por defecto (120): subirlo a ciegas debilita la defensa sin dato que lo
   pida. Se fija el criterio para cambiarlo: si tras salir a producción los 429 de `/buscar` y de las
   sugerencias superan el 1 % de las peticiones, o una IP concentra más de 10 personas, subir
   `BUSQUEDA_LIMITE_POR_MINUTO` a 240 sin tocar código.
4. **Consecuencia para la entrega 3:** la pausa pasa de 250 a **350 ms**. Es una desviación de
   AC-22.2 y de «Parámetros fijados» de la auditoría; la propone la especificación AC-22.12 y la
   decide el titular.

**Qué no demuestra.** Los supuestos de ritmo son de poblaciones de estudios, no de la ciudadanía de
Santa Marta; no incluye a quien pega texto, ni a lectores de pantalla (que pueden escribir más lento
o más rápido); y supone una consulta por minuto y persona activa, sin dato de uso real.

## 2.5 El render en servidor comparte cupo: hallazgo medido en staging

**Pregunta pendiente desde #386:** ¿qué IP ve la API cuando es el *servidor del sitio* quien la
consulta (render en servidor)? Lectura del código (HECHO): en el despliegue a staging `NUXT_PUBLIC_API_URL` es la URL pública
del propio dominio y el sitio la consulta con `useRequestFetch()` desde su contenedor. **INFERENCIA** a
partir de ello: la petición sale a Internet, vuelve por Cloudflare y la API (`real_ip_header
CF-Connecting-IP`) ve la IP de salida del servidor, no la del visitante, de modo que todas las cargas
de página compartirían un solo cupo. No vi esa IP.

**Medición en staging (HECHO, 2026-10-30):** 60 cargas seguidas de `/tramites` desde un solo cliente, a
0,4–0,7 s entre ellas. La página sirvió el catálogo hasta la carga nº 49 y desde la nº 50 el HTML
traía «No se pudo cargar el catálogo». **La causa no se observó** (no leí el registro del servidor; ver
abajo). Se recuperó unos 80 s después de empezar la ráfaga. La comprobación previa y un navegador real
posterior mostraron los 6 trámites, así que la API y la página funcionan fuera de la ráfaga.

**Qué significa (INFERENCIA):** un solo cliente puede degradar el contenido servido por el servidor
para **todos** los visitantes durante ≈ un minuto, y el tráfico normal de un sitio con muchos
visitantes agota el cupo sin que nadie abuse. No es de T-22: afecta a toda página que consulte la API
desde el servidor (catálogo, pie de página). T-22 lo hereda en la página `/buscar`.

**Con dos clientes (HECHO, 2026-10-30):** mientras una ráfaga mía de cargas de `/tramites` cruzaba el
umbral, un segundo equipo en otra red, que sólo sondeaba, recibió el error en **el mismo segundo** que
yo (primer error mío en la carga 10; primer error del otro equipo en el mismo segundo de reloj). Un
visitante que no hizo nada anómalo perdió el contenido por culpa de otro: el cupo es compartido.

**Qué NO está medido:** qué limitador respondió (el de Laravel `throttle:api` es el candidato por la
cifra de 60 y por el tiempo de recuperación, pero no leí el registro del servidor) ni cuál era la IP
vista. Para cerrarlo hace falta el registro del contenedor del sitio y de nginx en staging (lo tiene
el titular).

**Opciones** (para el registro; se aplicó la A):
- **A. Canal interno de confianza:** el sitio llama a la API por la red interna con la IP del visitante
  en una cabecera que nginx sólo acepta desde esa red. Corrige la causa; toca nginx, compose y el
  sitio, y abre una frontera de confianza que hay que probar.
- **B. Exención por secreto compartido:** las llamadas del sitio llevan un token y la API no las
  limita por IP. Simple; un secreto más que rotar y un atajo al límite.
- **C. Caché de las respuestas en el servidor del sitio** (`swr` de Nitro por minuto): reduce las
  llamadas por orden de magnitud para páginas iguales; no sirve para búsquedas distintas por
  visitante.
- **D. Resultados de `/buscar` pedidos por el navegador** (con la IP real): resuelve sólo la
  búsqueda y pierde el resultado sin JavaScript.

**Decisión aplicada: opción A (canal interno de confianza), ADR-0018.** Recomendada por mí y aprobada por el titular («hazlo»); ver opciones arriba. El sitio
reenvía las lecturas del render a un puerto interno de nginx con la IP del visitante.

**Verificación antes de desplegar (HECHO, sin staging):**
- nginx 1.24 real: con la cabecera, el cupo se separa por visitante (A pasa a 429 en la 4.ª petición,
  B recibe 200 en el mismo instante); sin cabecera todos comparten un cupo; una cabecera inválida, vacía
  o ausente conserva la dirección del servidor; en el puerto público la cabecera se ignora.
- Receptor FastCGI de prueba (devuelve los parámetros que recibiría PHP): `REMOTE_ADDR` = IP del
  visitante, `HTTP_HOST` = anfitrión público (sin duplicar), `X-Forwarded-Proto` = https y puerto 443.
- Sitio compilado real contra una API falsa que registra cabeceras: portada, trámites, búsqueda, ficha y
  pie llegan con la IP del visitante; una cabecera falsificada se sobrescribe; un valor basura queda
  vacío; POST responde 405; una ruta fuera de `/api/v1` responde 503.
- 285 pruebas unitarias del sitio (31 nuevas) pasan.

**Medición en staging tras el despliegue (HECHO, 2026-10-30).**
1. *Primer intento, sin efecto:* el despliegue de CI no vuelve a escribir el `.env`; el sitio seguía con la URL
   pública. Prueba directa: desde el equipo del titular, la API directa bajaba el cupo de 1 en 1
   (`x-ratelimit-remaining` 59, 58, 57, 56) y tres cargas de página no gastaron nada de ese cupo.
2. *Segundo intento, incidente:* con el `.env` nuevo el despliegue dejó el dominio en 521 (ADR-0018,
   «Incidente»). Se levantó arrancando nginx a mano; la corrección de fondo es #404.
3. *Con el canal activo:* cada carga de página consume **2 unidades del cupo de quien la pide**
   (59 → 56 → 53 → 50 entre lecturas de la API directa: 1 de la lectura + 2 del render).
4. *Dos clientes a la vez:* el cliente A (equipo del titular) hizo 66 cargas seguidas y desde la carga 31
   (~60 llamadas a la API, el límite de `throttle:api`) recibió el estado de error: 26 cargas con error.
   El cliente B (otra red), sondeando cada 2 s durante todo ese tiempo, tuvo **38 de 38 cargas correctas,
   0 con error, también durante los ~36 s en que A estaba limitado**. Antes del cambio, el error de uno
   apareció en el mismo segundo que el del otro.

**Límites de la medición.** Un solo par de clientes y una sola ejecución. El cliente B salía por el proxy
de mi entorno, cuya IP de salida no controlo (no pude limitarlo yo mismo: 100 llamadas directas en 49 s no
dieron 429, lo que sugiere que su IP de salida no es fija); por eso el límite se agotó en el equipo del
titular y B fue el testigo. La IP del render se leyó después en el registro de nginx del servidor
(2026-10-30, tres cargas reales de `/tramites` desde un cliente externo): la llamada de render a
`/api/v1/tramites` quedó con la misma IP que la página en las tres (ver ADR-0018). Lo que sigue sin
observarse es la IP leída en la propia API; nginx se la pasa a PHP como `REMOTE_ADDR`, así que es
una inferencia razonable, no una lectura.

**Límites de la verificación previa.** No corrió PHP-FPM ni Laravel reales ni el contenedor de nginx del
proyecto (usé el nginx del sistema con los mismos archivos de configuración); el `Host` y el esquema se
comprobaron en los parámetros FastCGI, no en un enlace generado por la API. **Lo que cierra el hallazgo
es repetir la medición de dos clientes en staging tras el despliegue** (tarea abierta).

**Residual.** El `sitemap.xml` lo genera el sitio con un `$fetch` simple, sin visitante: comparte el
cupo del servidor (poco tráfico). La IP de la propia API sigue sin leerse en un registro (ver arriba); la de nginx sí.

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
| D8 | **Cobertura por palabra:** cada palabra de la consulta con ≥ 4 letras (hasta 6) debe aparecer en el contenido con `word_similarity` ≥ **0,40** (era 0,45; §2.3) | Sólo el umbral global | HECHO: ningún umbral global separaba «licencia de conducción» (ruido, 0,600 con «licencia de exhumación») de los aciertos más difíciles (0,500). Es un cambio de diseño, no un ajuste de cifra. |

## 4. Riesgos

| # | Riesgo | Tipo | Mitigación | Cuándo |
|---|---|---|---|---|
| R1 | Un volumen mucho mayor que 124 filas degrada el p95 | HIPÓTESIS | AC-22.6 mide contra staging; el índice GIN ya existe | E3 |
| R2 | Los bancos son optimistas (los escribí yo, con el catálogo a la vista) y los parámetros se eligieron con uno de ellos | HECHO la cuantificación · PENDIENTE la cifra real | Ampliados a 30 + 12 negativos + 10 de validación (§2.1) y medido con un banco generado por reglas (§2.3): **≈ 90 %** entre los tres primeros con una errata por consulta, no 100 %. La cobertura pasó de 0,45 a 0,40. Sigue pendiente pedir a la Entidad consultas reales de atención | E2 (parcial) |
| R3 | **El texto buscado queda en el registro de acceso.** `docker/nginx/nginx.conf` registraba `$request` (con `?q=`) y `$http_referer` (el sitio pide sus recursos con la página `/buscar?q=…` como referer). Un ciudadano puede escribir un número de documento. | HECHO el formato · INFERENCIA el contenido | **Mitigado en E2** (§2.2): si la consulta trae `q` o `buscar`, el registro lleva sólo la ruta, en la petición y en el `Referer`. **Quedan** el `error_log` de nginx y el registro de Cloudflare (§9). Los informes CSP se trataron después (§2.2). Revisar con el oficial de datos personales (Ley 1581) | E2 (parcial) |
| R4 | Cada pulsación es una petición: abuso o carga | INFERENCIA | `throttle:busqueda` en el servidor: **120 por minuto y por IP**, compartido por las dos operaciones, configurable con `BUSQUEDA_LIMITE_POR_MINUTO`; 429 con `Retry-After`. **Corregido:** el grupo `api` traía además `throttle:api` (60 por minuto) y el tope real era 60 —medido en staging: 200 hasta la petición 60 y 429 desde la 61—; se quitó de las dos rutas (§9). La pausa de 250 ms en el cliente es de la entrega 3 | E2 (servidor) · E3 (cliente) |
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

Viven en el documento enlazado arriba: 36 casos (TC-BUS-01 a TC-BUS-32, más TC-BUS-33 a 36 propuestos por la especificación de interacción) con pasos, resultado
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
   especificación de interacción (AC-22.12) la aprueba el titular: está redactada en
   [`busqueda-interaccion.md`](busqueda-interaccion.md), pendiente de sus nueve decisiones.

## 9. Límites que quedan

- **Tiempos (AC-22.6), parcial.** HECHO, medido en staging desde la sesión de trabajo (100 consultas
  secuenciales tras 10 de calentamiento): `/busqueda/sugerencias` p95 174 ms; `/buscar` p95 268 y
  246 ms en dos corridas; la red sola (referencia) p95 145 ms. Cumple < 300 ms **en la API**, con
  margen escaso en `/buscar`. No cubre la página ni el combobox (entrega 3), ni se midió desde el
  servidor (TC-BUS-30), ni con más de 123 trámites (R1).

- La relevancia se calibra con bancos que escribió quien conoce el catálogo; la medida
  real requiere consultas de ciudadanos (R2). El banco generado por reglas (§2.3) da ≈ 90 %
  entre los tres primeros con una errata por consulta; con dos erratas, sinónimos o siglas no
  está medido. Un falso positivo está medido y aceptado («impuesto de renta», §2.1): es ruido de
  trigramas sobre una palabra de 5 letras («renta» alcanza 0,5 contra texto sin relación) y
  cualquier regla que lo excluya costaría aciertos legítimos; se queda fijado en una prueba.
- **El registro sin la consulta tiene bordes** (§2.2): el `error_log` de nginx copia la línea de
  petición cuando una petición falla aguas arriba (nginx no permite filtrarla), y Cloudflare
  conserva su propio registro con la URL completa. El primero sólo aparece cuando algo falla; el
  segundo depende de la configuración de Cloudflare (retención y campos de los registros), no de
  este repositorio. Hay que decidirlos con el oficial de datos personales. Una alternativa de
  diseño, que cambiaría el contrato, sería enviar la consulta por `POST`; no se adoptó porque
  rompe el enlace compartible de una búsqueda y los resultados cacheables.
- **Un error mío en el límite, corregido:** `bootstrap/app.php` activa `throttle:api` (60 por minuto por
  IP) en todo el grupo `api`; las rutas de búsqueda lo heredaban *además* de su `throttle:busqueda`, y
  el más estricto manda. El tope real era 60, no los 120 documentados. HECHO: en staging, desde una
  sola conexión, 60 respuestas 200 y 429 desde la 61; ahora `withoutMiddleware('throttle:api')` en
  esas dos rutas y una prueba fija que 100 peticiones pasan y la 101 no. El resto de la API conserva
  sus 60.
- El límite de 120 peticiones por minuto y por IP es una estimación (INFERENCIA: una persona
  escribiendo genera una petición por pausa de 250 ms; una oficina que comparte salida suma). No
  se midió con tráfico real. **Riesgo concreto:** si muchos ciudadanos comparten una IP pública
  —la red móvil con NAT de operador, o el wifi de una oficina de atención—, el cupo se agota
  para todos y reciben 429 sin haber hecho nada. HIPÓTESIS (no hay datos de la distribución de
  IP de los visitantes). Para detectarlo: contar los 429 de `/buscar` en el registro de acceso
  tras la salida a producción y subir `BUSQUEDA_LIMITE_POR_MINUTO` sin tocar código si hace falta.
  **Revisado (§2.4 y §2.5):** el modelo de carga fija qué protege el valor (≈ 10 personas por IP con la
  pausa de 350 ms) y el criterio para subirlo; el render en servidor comparte un solo cupo —medido en
  staging— y está pendiente de decisión (opciones A–D en §2.5).
- El texto de búsqueda de una política se calcula al guardarla; las políticas sembradas son de
  ejemplo (R5).
- Los sinónimos («carro» ↔ «vehículo», «cédula» ↔ «documento») no los resuelven los
  trigramas; si la Entidad los necesita, se incorporan por el campo `palabras_relacionadas`
  del trámite, que hoy no se indexa. Decisión de la Entidad, no de TIC.
- No se probó con lector de pantalla ni dispositivos reales (entrega 3).
