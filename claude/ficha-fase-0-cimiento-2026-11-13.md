# Ficha de fase: Fase 0, cimiento transversal (2026-11-13; actualizada 2026-11-17)

Estado: **0B: T6 mezclada (#621) y T7 mezclada (#622) en `develop`; faltan T8 y T10. 0A construida, verificada en local y en staging, promovida y aprobada por José (2026-11-17). 0B: ficha aprobada por José el 2026-11-17 con las correcciones de esta versión; se ejecuta tarea por tarea y no se avanza a la siguiente sin su visto bueno. 0C pendiente.** **HECHO** = leído o medido; **INFERENCIA** = deducido; **HIPÓTESIS** = sin comprobar.

**Regla de entrega (José, 2026-11-17, guardada en memoria):** cada tarea entra a `develop` por su PR (prueba primero, CI verde); **no se promueve a `staging` ni a `main` hasta que José lo autorice o la fase esté terminada.** No se promueve tarea por tarea.

## 1. Hallazgos de las lecturas (algunos corrigen el plan)

| # | Hallazgo | Evidencia | Efecto |
|---|---|---|---|
| H1 | Existía un diseño de roles no usado (`docker/postgres/init/01-usuarios.sql.example`) | `docker/postgres/init/` | Reutilizado en la 0A; `sede_migracion` no se crea (D-0.1) |
| H2 | `POSTGRES_USER=sede` es el superusuario de arranque y dueño; la app lo usaba | `compose.yaml` | D-0.1: `sede` migra; la app usa `sede_app` (hecho en 0A) |
| H3 | La copia de Laravel usaba la conexión de la app | `config/backup.php` | `pgsql_copia` con `sede_lectura` (hecho en 0A) |
| H4 | `scripts/verificar-respaldo.sh` no depende del rol de la app: vuelca con el usuario del contenedor | `scripts/verificar-respaldo.sh` | `make respaldo` prueba la restauración, no el cambio de la 0A; ese lo prueba `backup:run` |
| H5 | **Nitro instalado: `nitropack 2.13.6`** (Nuxt 4.6.4). Lo citado de `useKV`, «no existe isr» y no servir viejo si falla es de Nitro 3 | lockfile del sitio; docs | Sin verificar para 2.13.6; se lee el código instalado antes de T9 |
| H6 | `file_media` guarda `model_uuid`; su índice único incluye `model_id`, que hoy admite NULL (en PostgreSQL los NULL no chocan) | migración `2026_10_05_000001`; modelos; servicio | T8 rellena `model_id`, lo hace `NOT NULL` y retira la rama «legacy» |
| **H7 (corregido)** | El panel tiene 10 archivos en `src/services/`. Los importan **40 archivos y 91 referencias**: 17 archivos y 21 referencias en `panel/src`, **más 23 archivos y 70 referencias en `panel/tests`** (24 `vi.mock`). La ficha del 13-nov mezclaba los alcances («17 archivos y 91 referencias») | `grep` en `panel/src` y `panel/tests`, 2026-11-17 | T6: el cambio es mecánico pero abarca src **y** pruebas |
| H8 | `.gitignore` excluía `.env`, `.env.*`, `*.local.env`, pero **no** las copias `pass.md.*` ni `.accesos.local.env.*` | `git check-ignore`, 2026-11-17 | Corregido en #618 (repositorio público) |
| H9 | El CI no usa el simulador del contrato | `ci.yml` | A6 se resuelve en la Fase 1 |
| H10 | `phpunit.xml` corre en PostgreSQL (`DB_CONNECTION=pgsql`, base `sede_pruebas`) | `phpunit.xml:45-46` | La migración de roles funciona con ese usuario |
| **H11 (resuelto)** | Acceso a staging: la sesión no lo tiene, pero José ejecuta desde su PC `ssh ops` + `sudo docker compose` sobre `/opt/sede/staging` y pega la salida | verificado 2026-11-17 | Las lecturas de staging se hacen así, solo lectura |
| **H12** | **0A verificada en staging** (2026-11-17): `app`, `scheduler` y `horizon` con `DB_USERNAME=sede_app`; roles `sede` (superusuario), `sede_app` y `sede_lectura` sin superusuario; `public` sin `CREATE` para PUBLIC; copia nocturna sana | salida del servidor; `docs/verificacion-fase-0a-2026-11-17.md` | Cierra el pendiente H11 de la 0A. Queda: conexión viva como `sede_app`, tarea programada disparada por el scheduler, tabla creada vía `migrar` (Fase 1) |
| **H13** | **`file_media` en staging: 16 filas** (4 `Banner`, 12 `Noticia`, 0 `Politica`); **0** con `model_id` nulo, **0** incoherentes con el id real del modelo, **0** huérfanos; todos en el disco `local` | consulta de solo lectura, 2026-11-17 | En staging el relleno de `model_id` no cambia filas; el riesgo real está en `NOT NULL` y en mover las lecturas. Es staging: una base local con filas de fábrica podría tener nulos |
| **H14** | `FilesMediaService::upload()` **siempre escribe `model_id` y `model_uuid` juntos**. La única fuente de `model_id` nulo en el código es `FileMediaFactory` (pruebas, deja `model_uuid` en `null`). Las **lecturas reales** usan `model_uuid`: `Banner::imagenPrincipal()`, `Noticia::imagenPrincipal()`, `Politica::documentoOficial()`, `PanelPoliticaController` (borrado) y `getByCollection()` (rama «legacy», línea 88). **Las cuatro ya filtran por `model_type`**, así que pasar a `model_id` numérico no mezcla modelos | lectura completa de `FilesMediaService.php` y de los cuatro puntos | T8: se cambian esas lecturas a `model_id`; el filtro por `model_type` se conserva |
| **H15** | `FileMediaResource` **expone `model_uuid` en la API** y aparece en `panel/src/types/openapi.d.ts:1456` | `FileMediaResource.php:25`; `openapi.d.ts` | T8 **no** quita el campo ni la columna: se retiran juntos, con regeneración del contrato, en la entrega posterior (expandir–contraer) |
| **H16** | T10 es más chico de lo supuesto: `phpunit.xml` ya corre en PostgreSQL. Queda `'default' => env('DB_CONNECTION', 'sqlite')` en `config/database.php:22`, el mismo valor por defecto en `config/queue.php:108,127`, la conexión `sqlite` en `config/database.php:46-49`, dos comentarios que mencionan SQLite (migración de trámites) y una línea `permission.testing` («fix for sqlite testing») en la migración de permisos | `grep` en el repo, 2026-11-17 | Cambiar el valor por defecto exige comprobar que ningún entorno dependa de él |
| **H17** | **T7 no puede quedar en verde con «una única excepción» hoy:** 4 archivos del sitio tienen **18 literales únicos** (el «12» anotado antes no se reproduce; recontado el 2026-11-17 con el detector de T7) de datos institucionales escritos (`PiePaginaGovco.vue`, `atencion.vue`, `accesibilidad.vue`, `realizar-una-peticion.vue`) | `grep` en `sitio/app`, 2026-11-17 | Decisión D-0.4 |
| **H18** | CI: la puerta de Dependencias falla en todo el repo por GHSA-fc5q-39xc-vjpf (`picomatch` ≤ 4.0.7, ReDoS, CVE-2026-112366, 2026-10-19); no hay versión corregida (4.0.7 es la última). **Tolerado** en `ci.yml` (#614), con condición de retiro. El job «Navegadores (webkit)» del despliegue de `cc5c29e` falló por `apt` agotando 180 s al instalar dependencias del navegador (ninguna prueba corrió) | `ci.yml`; logs del despliegue | Contexto: no bloquea la 0B. Si webkit repite el fallo, merece arreglo aparte |

## 2. Decisiones resueltas
- **D-0.1:** `sede` es dueño y ejecuta las migraciones solo desde el despliegue. Se crean `sede_app` y `sede_lectura`. La app, Horizon y el programador nunca usan `sede`.
- **D-0.2:** tres entregas, cada una con su aprobación y su merge: **0A** infraestructura de BD, **0B** orden del código, **0C** sitio.
- **D-0.3 (2026-11-17):** promoción solo con autorización de José o con la fase terminada; no por tarea.
- **D-0.4 (2026-11-17, T7):** guardia **con línea base y trinquete**. Lista versionada de las excepciones actuales (archivo y literal); solo pueden bajar; un literal nuevo fuera de la lista **rompe la CI**; y una excepción de la lista que **ya no existe en el código también rompe la CI** (obliga a quitarla). Cada archivo que la Fase 1 limpie sale de la lista. La excepción permanente es el respaldo del pie (orden API, caché, código). José delegó la elección («soluciones limpias, con profundidad y rigor PhD»).
- **D-0.5 (2026-11-17, T8):** pruebas de la migración sobre datos de ejemplo con conteos antes y después, **más una copia manual (`backup:run`) en staging justo antes de la promoción**.

## 3. Alcance y tareas

| Entrega | Tarea | Qué se hace | Prueba (TDD) | Criterio de aceptación |
|---|---|---|---|---|
| 0A | T1 Docker local | `make local`: genera `.env.local` con secretos aleatorios, compila el panel, levanta la pila, migra y siembra dentro del contenedor, prueba de humo; bajar y reiniciar limpio; README corregido (`make dev` no existe) | Humo: sitio y panel responden, API en verde, se escribe y se lee un dato | Desde una copia limpia, un comando deja la pila funcionando y el humo pasa. **Estado:** construida; el humo completo no se corrió sobre la pila en uso porque `SedeSeeder` sobrescribe Entidad y menú |
| 0A | T2 Roles de BD | Migración idempotente: `sede_app` y `sede_lectura` con los permisos de H1; `REVOKE` de `CONNECT/TEMPORARY` a `PUBLIC` y de `CREATE` sobre `public`; claves del entorno (hash SCRAM por validar) | Migración dos veces; falla sin secreto fuera de pruebas; `sede_app` no puede `CREATE TABLE` ni `DROP`; `sede_lectura` no escribe | Pruebas en CI y Docker local. **Estado:** 9 de 9, 31 aserciones, con autenticación real; verificada en staging |
| 0A | T3 Conexiones | `pgsql` (app), `pgsql_migracion` (despliegue, usa `sede`) y `pgsql_copia`; `migrate --database`; app, Horizon y programador con `sede_app` | Humo con los tres servicios; un trabajo en cola y una tarea programada | Ningún servicio corre como `sede`. **Estado:** verificado en local (cola real en Horizon) y en staging; falta ver una tarea disparada por el scheduler |
| 0A | T4 Copia | La copia nocturna usa `pgsql_copia`; revisar `docs/copias-de-seguridad.md` contra RNF-DISP-02 | `make respaldo` con roles separados; prueba de CI | Copia y restauración verificadas. **Estado:** `backup:run --only-db` con `sede_lectura` y `make respaldo` en local; copia nocturna sana en staging |
| 0A | T5 TLS | Configuración y documentación; riesgo de staging por escrito. Producción aún no existe: se prueba en Docker local con certificado de prueba | Conexión con `sslmode=require` en la pila local | `sede_app` conecta con TLS. **Estado:** promovido (#611); el certificado de producción se emite cuando exista el entorno |
| 0B | T6 Panel `services` → `api` | Mover los 10 archivos; `http.ts` → `client.ts`; actualizar **40 archivos y 91 referencias** (17 en `src`, 23 en `tests`) y los 24 `vi.mock` | Primero una prueba de estructura que falla: no existe `src/services`, existen los 10 archivos en `src/api` con `client.ts`, y ningún archivo importa la ruta vieja. Después vitest, lint y compilación | Todo en verde y sin la ruta vieja. **Estado:** mezclada en `develop` (#621, 2026-11-17); verificada en el panel local por José |
| 0B | T7 Guardia | Prueba que falla si aparecen datos institucionales en el código del sitio, con **línea base y trinquete** (D-0.4) | Caso positivo (inserción detectada), negativo (código limpio), y caso del trinquete (una excepción obsoleta rompe) | Detecta la inserción de prueba; la línea base coincide con los 18 literales únicos de hoy. **Estado:** mezclada en `develop` (#622, 2026-11-17) |
| 0B | T8 `file_media` | `hash_sha256` (cálculo al subir y relleno desde el disco; nullable, y lista los archivos ausentes); rellenar `model_id` desde `model_uuid` y hacerlo `NOT NULL`; **si hay filas sin dueño, la migración se detiene y las lista (no borra ni inventa)**; lecturas a `model_id` (H14); **`model_uuid` y el campo del contrato se retiran en entrega posterior** | Migración sobre datos de ejemplo (con y sin nulos), conteos antes y después, unicidad sin nulos, lecturas con `model_type` | Cero `model_id` nulos; lecturas sin `model_uuid`; copia previa en staging (D-0.5) |
| 0B | T10 Motores de BD | Quitar el valor por defecto `sqlite` y su conexión, los comentarios y la línea `permission.testing`, previa comprobación de que ningún entorno dependa de ellos | Suite completa en PostgreSQL; comprobación de `queue.php` | Sin ramas muertas |
| 0C | T9 Sitio | Un cliente Axios único; caché de la última respuesta válida con Redis (**tras leer el código de Nitro 2.13.6**) | API ok, API caída con caché, API caída sin caché | Los tres casos pasan |

## 4. Conversión de datos y retroceso

| Cambio | Antes → después | Verificación | Retroceso |
|---|---|---|---|
| `file_media.model_id` | Admite NULL → `NOT NULL`, rellenado desde `model_uuid` si hacía falta | Staging hoy: 16 filas, 0 nulos, 0 incoherentes, 0 huérfanos. Se repite la consulta antes de migrar | Restaurar la copia previa; `model_uuid` intacto hasta la entrega de retiro |
| `file_media.hash_sha256` | No existe → hash del archivo en disco | Filas sin hash = archivos ausentes, listadas | Columna nullable |
| Roles de BD | (hecho en 0A) | Verificado en staging | — |

Capas de retroceso: copia verificada antes de cada entrega, migraciones que solo añaden, imagen anterior desplegable. Límite conocido: la copia está en el mismo servidor.

## 5. Estimación (rango en horas; sin velocidad medida del repo)

| Entrega | Tareas | Estimación |
|---|---|---|
| 0A Infraestructura de BD | T1–T5 | **Hecha** |
| 0B Orden del código | T6, T7, T8, T10 | 11–21 h (T10 y el relleno de T8 resultaron más chicos; T6 abarca pruebas) |
| 0C Sitio | T9 | 8–14 h |

## 6. Riesgos
1. Hash SCRAM en lugar de clave en claro: factible según la doc de `CREATE ROLE`; las pruebas de la 0A con autenticación real pasaron.
2. Migraciones con superusuario `sede` desde el despliegue: es lo que ya ocurría; el aislamiento nuevo es que la app deja de usarlo.
3. Nitro 2: lo documentado no coincide con la versión instalada (H5).
4. T8 cambia las lecturas de imágenes de noticias, banners y documentos de políticas: si algún `model_id` fuera incorrecto, la imagen dejaría de mostrarse. Mitigación: consulta de coherencia antes de migrar (H13) y pruebas de las cuatro lecturas.
5. T10: cambiar el valor por defecto de la conexión puede afectar a un entorno que no declare `DB_CONNECTION`. Mitigación: revisar `queue.php`, los `.env.example` y el CI antes de quitarlo.
6. T7: la línea base es deuda declarada, no resuelta; se paga en la Fase 1. La guardia es por patrones: no ve datos con otra forma ni el nombre de la Entidad.
7. Staging sin TLS a la BD y copia en el mismo servidor: aceptados y documentados.
8. CI: advisory de `picomatch` tolerado y job webkit con fallo de `apt` (H18).
9. `migrate:fresh` por `exec app` falla a propósito (la app es `sede_app`); en local se usa el servicio `migrar` con `pgsql_migracion`. Borra los datos locales, incluido el super-admin (el sembrador lo recrea).

## 7. Cierre de cada entrega
ADR por decisión, `make trazabilidad`, prueba en Docker local, verificación en staging, aprobación de José y **promoción solo cuando él lo autorice o la fase esté terminada**.
