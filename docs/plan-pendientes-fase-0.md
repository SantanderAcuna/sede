# Plan: pendientes de la fase 0 (D2, D3, D6, D7, D8, sugerencias de las revisiones y auditoría 0C)

Rama de este documento: `docs/plan-pendientes-fase-0` (desde `origin/develop` `898e839`). Cada tarea tiene su propia
rama y su PR desde `develop`. TDD estricto (regla global del usuario): el backend se prueba con `php artisan test`
sobre Postgres y Redis desechables, como en `ci.yml`; las guardas de shell, con `bash`. Estado: **solo plan, nada
implementado**.

## Decisiones de José (2026-11-28)

| Punto | Decisión |
|---|---|
| D4 SMTP | No se hará. |
| D6 canal de las alertas | Una tarea en el servidor abre una incidencia de GitHub con un token de grano fino (solo incidencias), guardado únicamente en `/etc/sede/<entorno>.secretos.env` (ADR-0019). |
| D6 umbral de la caché | Avisar cuando nginx sirve copias `STALE` durante 5 minutos seguidos. |
| D2 medición | Prueba de carga en local contra la pila local, sin acceso al servidor. CAP-01 queda pendiente hasta que exista un entorno equivalente a producción. |
| D3, D7, D8 | Cambiar las citas sin fuente por las fuentes que existen y dejar constancia de que las fichas 0C y 0D no se conservan. |
| D9 secretos en GitHub | Fuera de este plan (la evidencia queda abajo). |
| Auditorías 0C | Verificar cada punto e incluir solo los abiertos. Las auditorías y los informes con datos del servidor van a `docs/privado/`, que no se versiona (PR #757). |

## Evidencia (verificada el 2026-11-28 sobre `origin/develop` `898e839`)

- **PHP-FPM.** El repositorio no configura ningún pool; rige el `www.conf` de la imagen: `pm = dynamic`,
  `pm.max_children = 5`, `start_servers = 2`, `min/max_spare = 1/3`. `docker/app/php.ini`: `memory_limit = 128M`.
  Servicio `app` en `compose.yaml`: `mem_limit: 1.5g`. Un worker en reposo en `sede-app-1` ocupa unos 5 MB de memoria
  anónima. `ab` está instalado en este equipo. Plan archivado `docs/archivo/plan.md` §15.3 (líneas 1839–1848):
  `max_children = límite del contenedor × 0,75 ÷ memory_limit` (= 9 con los valores de hoy); línea 2604:
  «recálculo con carga real (F10.3)».
- **Requisitos.** CAP-01 (`entidad-transparencia/.../criterios-aceptacion-fuente-de-verdad.md:322`): 1000 VUs ×
  10 min, 0 × 5xx, p95 ≤ 500 ms, «contra un entorno equivalente a producción, no contra staging compartido».
  REND-01: TTFB p95 ≤ 200 ms con 100 VUs × 5 min. Staging y producción comparten servidor: `DEPLOY_HOST` es un único
  secreto de repositorio. Ese servidor tiene 4 vCPU, 7,8 GB de RAM y 154 GB de disco, medido el 2026-11-26 según
  `docs/privado/informe-disco-imagenes-docker-2026-11-26.md` (líneas 3-5; dato de ese informe, no medido de nuevo).
- **Alertas hoy.** `sonda.yml` abre una incidencia asignada al dueño (no necesita SMTP). `VigilarRedis` envía
  `AvisoRedis` por correo (`VigilarRedis.php:98-104`); con `MAIL_MAILER=log` solo llega al registro. nginx escribe
  `cache=$upstream_cache_status` en su registro de accesos (`nginx.conf:75-81`); topes de caché: imágenes 1 GB,
  documentos 512 MB (`nginx.conf:198-199`). El ADR 0010, línea 34, deja «el umbral de alerta y el aviso de
  `[crit]`» a DISP-01 sin definirlos.
- **Fuentes revisadas para D3, D7 y D8, sin resultado:** el disco completo (home, `/tmp`, worktrees), las 10
  carpetas de proyectos de `~/.claude`, las sesiones de `~/.config/Claude`, Engram, todas las ramas locales y
  remotas y toda la historia de git, y los PR e issues de GitHub. Lo que sí existe: el nombre de la ficha 0D,
  `ficha-fase-0d-integridad-bd-2026-11-18` (PR #637–#642); las opciones de `ioredis` «de la ficha (H35, B4)» (cuerpo
  del PR #704); «(auditoría H3)», añadida por `a06ff56` (#741) sin fuente. Las fichas 0C y 0D salieron de sesiones
  en la nube cuyas transcripciones no están en este equipo.
- **D9 (fuera del plan, solo evidencia).** Los entornos `staging` y `production` guardan 8 secretos de la aplicación
  cada uno, creados el 2026-09-29/30 y nunca actualizados. Solo dos trabajos pueden recibirlos (`staging` y
  `produccion` de `despliegue.yml`; documentación de GitHub: «only available to workflow jobs that reference the
  environment»). Ninguna rama ni ningún commit de la historia lee esos nombres, `toJSON(secrets)`, `secrets[…]` ni
  `secrets: inherit`. No hay secretos de Dependabot ni de Codespaces, ni disparadores `pull_request_target` o
  `workflow_run`, ni ejecutores propios. Solo los escribe `deploy/rotar-secretos.py:214,385` (`--github`,
  `--empujar`). El ADR-0019 dice que no están en GitHub Actions: esa frase es falsa.

## Puntos de la auditoría 0C, verificados uno por uno

Fuente: `docs/privado/auditoria-pendientes-fase-0c-revision-2-2026-11-27.md`, §7 (carpeta privada, no se versiona).

| Punto de la auditoría | Estado hoy | Evidencia |
|---|---|---|
| El monitor de Redis no puede avisar con Redis caído | Corregido | #754: `Schedule::useCache('file')`, `withoutInterruptionPolling()` |
| `publicar-entorno.sh` reescribe `.env`/`compose.yaml`; `chown docker/` | Corregido | #750; `publicar-entorno.sh:28-31` respeta un entorno ya desplegado; ya no hay `chown` |
| La sonda toma `cf-mitigated` por caída; comprueba `X-Sede-Respaldo` | Corregido | #751; `sonda-sitio.sh:57,71` → «NO VERIFICABLE»; ADR 0009 línea 77 corregida |
| ADR 0009 «Redis 8 sin medir» | Corregido | ADR 0009 línea 79 |
| Contenedor local `sede-prism` | Corregido | no aparece en `docker ps -a` |
| Política de ramas y dominio de producción | **Abierto, bloquea D5** | La política admite `master` (no existe) y `staging`; el último despliegue a producción (ejecución `40796242910`) se rechazó: «Branch "main" is not allowed to deploy to production due to environment protection rules». El entorno `production` no tiene variables (`SEDE_DOMINIO` sin definir). |
| `SEDE_DESPLIEGUE_PRODUCCION=false` hasta estar listo | **Abierto** | La variable del repositorio vale `true` |
| Destinatario y SMTP de las alertas de Redis | Sustituido | D4 descartado; lo reemplaza el canal de incidencias de D6 |
| Retirar `rotar-secretos.py --github` | Excluido | Es parte de D9, fuera del plan |
| Copia local al día | **Abierto (local)** | La copia principal está en `develop`, 12 commits por detrás de `origin/develop` |
| `horizon`/`scheduler` locales con imagen vieja | **Abierto (local)** | Ambos corren `6b1182752276`; `app` corre `sede/sede-app:dev` (`cb2d69c…`) |
| Documentación que contradice el código (§4.6) | **Abierto** | ver T4 |
| Consulta por la Ley 594/2000 | **Abierto (jurídico, José)** | fuera del código |

## Tareas

Heurística de tamaño: unas 400 líneas por tarea (solo orientativa). La ruta de cada tarea y la evidencia de cada
disparador de delegación van en su PR.

- [ ] **T1 Pruebas de candados: límite inferior y margen** (directa, 1 archivo de prueba). En `AgendaCandadosTest`,
  un candado tomado en la primera corrida sigue tomado a mitad de una corrida legítima, y cada caducidad deja al
  menos 5 minutos antes de la corrida siguiente. RED: cambiar una caducidad en una copia y ver fallar cada aserción
  nueva. Comprobaciones: suite, Pint y PHPStan.
- [ ] **T2 Guarda de secretos: sugerencias de la revisión** (directa, 1 guion). Comentar por qué la autoprueba
  anida 3000 corchetes (`RecursionError`) y por qué los mensajes se cortan a 100 caracteres; un único ayudante para
  las autopruebas escritas a mano; `cargar_prohibidos()` devuelve el conjunto en vez de rellenar una variable
  global; una autoprueba de que una lista ilegible sale con 3. Primero el RED de la autoprueba nueva.
  Comprobaciones: `bash deploy/pruebas/guarda-secretos-workflows.sh` y la mutación sobre una copia de los flujos
  reales.
- [ ] **T3 Citas de D3, D7 y D8** (escritor delegado: 4 archivos). `docs/verificacion-fase-0c-2026-11-26.md:51` cita
  el plan archivado §15.3 y F10.3 en lugar de «auditoría H3», y dice que el tope global de conexión ya es de 2 s
  (`nginx.conf:162`). `ci.yml:206,211` y `sitio/server/utils/almacen-respaldo.ts:141` citan el PR #704 en lugar de
  «ficha 0C». Los dos documentos de verificación dejan constancia de que las fichas 0C y 0D no se conservan.
  `ci.yml` es configuración de CI: el cambio es solo de comentarios y se señala en el PR.
- [ ] **T4 Documentación que contradice el código** (escritor delegado: 4 documentos).
  Verificado hoy:
  - `verificacion-fase-0c:53` dice que no se ha visto el barrido; la auditoría de la revisión 2 registra una prueba
    controlada el 2026-11-27 a las 01:50. Como esa auditoría es privada, el documento público dice qué se midió y
    cuándo, sin enlazarla.
  - `verificacion-fase-0c:57` dice que `SEDE_DESPLIEGUE_PRODUCCION` no se puede leer; se lee y vale `true`. El número
    de archivos se recalcula al escribirlo.
  - `docs/despliegue-secretos.md:25-36` habla de «seis» secretos obligatorios: hoy son siete, S3 es opcional y
    «la canalización» no genera ninguno. La línea 66 dice que no hay canalización (existe `despliegue.yml`), y la 83,
    que no hay reglas de protección (`production` tiene política de ramas).
  - `deploy/README.md:324-326` dice que producción se lanza a mano desde `staging`; el trabajo exige
    `github.ref_name == 'main'` (línea 478), así que no se ejecuta nunca.
  - Se corrige la frase del ADR-0019 sobre los secretos en GitHub Actions (`docs/adr/README.md:711`) con los hechos
    de D9.
- [ ] **T5 D2 `pm.max_children`** (escritor delegado: archivo de pool, Dockerfile, prueba y ADR). Cargar la pila
  local con `ab` en `/`, `/api/v1/tramites` y `/api/v1/entidad` y muestrear el pico de memoria de cada worker de FPM;
  calcular el valor con la fórmula del §15.3 usando ese pico medido en vez de `memory_limit`, con el tope de
  1,5 GB × 0,75. Añadir un archivo de pool versionado que copie `docker/app/Dockerfile`, una guarda de que el
  `pm.max_children` efectivo de la imagen coincide con él (`php-fpm -tt`) y un registro de la decisión.
  **Compuerta:** José aprueba el número antes de fusionar el PR. CAP-01 sigue abierto.
- [ ] **T6 Alertas de D6 como incidencias de GitHub** (escritor delegado; en dos PR).
  - **T6a** `monitoreo:redis` abre o actualiza una incidencia por tipo de señal en lugar de mandar correo, sin
    duplicarlas, como `sonda.yml` (una etiqueta y una sola incidencia abierta). Habla con la API de GitHub mediante
    el `Http` de Laravel, simulado en las pruebas (sin llamadas reales). Lee el token de la configuración y suma una
    entrada opcional a `deploy/secretos-requeridos.txt`; sin token registra el error y sale con 1, como hoy. Retira
    `AvisoRedis` y el envío de correo que este cambio deja sin uso.
  - **T6b** Líneas `[crit]` y copias `STALE` durante 5 minutos seguidos. nginx escribe el registro de accesos en
    stdout y el de errores en stderr (`nginx.conf:11,81`), así que el `scheduler` no puede leerlos. El diseño (un
    registro extra en un volumen compartido u otra fuente) se mide primero, dentro de esta tarea.
    **Compuerta:** el diseño medido se le muestra a José antes de escribir el código.
  - **José:** crea el token de grano fino (Issues: lectura y escritura, solo este repositorio) y lo instala con
    `publicar-entorno.sh`. La comprobación en vivo va después.

## Acciones de José (no son código)

- [ ] J1 Política de ramas de producción: cambiar `master` por `main` (GitHub → Environments → production). Bloquea D5.
- [ ] J2 Decidir `SEDE_DESPLIEGUE_PRODUCCION` (hoy `true`) y el dominio de producción `SEDE_DOMINIO` (D-20).
- [ ] J3 En local: `git pull` en la copia principal (12 commits por detrás) y recrear `horizon`/`scheduler` con la
  imagen actual.
- [ ] J4 Consulta por la Ley 594/2000 (jurídica).
- [ ] J5 Crear el token de GitHub de T6.

## Fuera del alcance

D4 (SMTP), D9 (copias en GitHub de los secretos de la aplicación y `--github`) y la revisión nativa de la rama
completa contra `585bcde`.

## Orden

T1, T2, T3 y T4 son independientes. T5 y T6 no dependen entre sí; la comprobación en vivo de T6 espera a J5. D5
(promover a `main`) espera a J1 y J2.
