# Archivo 0009 (no es el `ADR-0009` del registro): respaldo de la última respuesta válida del sitio, en Redis, con acceso acotado

> **Estado:** ACEPTADA (Fase 0C, tareas T9.1–T9.5; ficha del 2026-11-18, decisiones D-0C.1 a D-0C.5 aprobadas por José)
> **Fecha:** 2026-11-25
> **Decisor:** José
> **Tags:** sitio, resiliencia, Redis, seguridad
> **Corrige:** el archivo 0006 («No hay caché intermedia»), que era cierto cuando se escribió y dejó de serlo con esta decisión.

## Contexto

El sitio público se renderiza en servidor y consulta la API en cada visita. Si la API cae o tarda, el visitante veía un estado de error aunque la página se hubiera mostrado bien un minuto antes. José decidió (2026-11-13) que ante una falla el sitio sirva **la última respuesta válida desde una copia en el servidor**, con el orden API → copia → código, y que cuando la API responde manda la API.

Tres hechos condicionan el diseño:

1. La caché de rutas de Nitro 2.13.6 no expresa lo decidido (ficha, H22): sirve la copia **en vez de** consultar la API mientras es fresca y no tiene un tope de edad para el valor viejo.
2. El sitio es el contenedor más expuesto de la pila: renderiza y reenvía contenido, y habla con Internet a través de nginx. Darle acceso a la caché que guarda también las sesiones y las colas de la aplicación amplía lo que un sitio comprometido puede tocar.
3. Redis es compartido: sesiones, colas, caché de Laravel y, ahora, las copias del sitio comparten `maxmemory 448mb`.

## Decisión

1. **Cliente único y respaldo en el servidor del sitio** (`sitio/server/utils/respaldo-api.ts`). Cada `GET` público de la lista blanca va a la API; si la respuesta es válida se guarda en segundo plano, y si la API falla se sirve la última copia, con la cabecera `X-Sede-Respaldo` (edad en segundos). Cada visita sigue consultando la API: es **respaldo**, no caché de rendimiento (D-0C.3).
2. **Qué se guarda.** Sólo `GET` de `/identidad/footer`, `/banners`, `/tramites` (con `page`, `per_page` y `type` ∈ {`tramites`, `opa`, `consultas`}), `/tramites/{slug}`, `/noticias`, `/noticias/{slug}`, `/politicas` y `/politicas/{slug}`. Nada con texto libre (`/buscar`), ningún `POST`. No se guarda una respuesta con `Set-Cookie`, con `Cache-Control: no-store`, de otro anfitrión que el dominio del despliegue, de más de 1 MiB, con URL firmada, ni que no sea 200. La petición sale **sin** `cookie`, `authorization` ni `proxy-authorization`. `private` en `Cache-Control` **no** impide guardar: Laravel lo pone por omisión en todo JSON y la API pública no es personal (decisión de José, 2026-11-25).
3. **Tiempos.** A los 4 s se busca la copia: si existe se sirve (motivo `tiempo`); si no, se sigue esperando a la API hasta 20 s. La llamada a la API no se cancela a los 4 s: si termina bien más tarde, su respuesta queda como copia nueva. Una respuesta lenta pero válida no cuenta como fallo del cortocircuito, que abre tras 3 fallos consecutivos (error, 20 s o 5xx) y pausa 30 s con una sola sonda. Edad máxima de la copia: **24 h** (D-0C.4, estimación sin dato propio). Escritura: una por clave cada 10 s.
4. **Sin Redis configurado el respaldo no actúa** y la lectura pasa por el proxy de siempre (corte a 20 s). Así, un despliegue sin las variables de Redis no cambia el comportamiento.
5. **Acceso a Redis acotado por tres capas a la vez** (T9.5):
   - **Red.** El sitio **no** entra en `data` (alcanzaría PostgreSQL). Redis se suma a una red nueva, `cache-sitio`, interna, que sólo comparten él y el sitio.
   - **Credencial.** Un usuario ACL `sitio` que sólo puede `GET`, `SET`, `DEL`, `PING` y `AUTH` sobre claves `sitio:*` (`-@all +get +set +del +ping +auth ~sitio:*`). Su contraseña, `REDIS_SITIO_PASSWORD`, **no es** la principal. El cliente se configura para esa ACL (`enableReadyCheck:false`, `disableClientInfo:true`): con las opciones por omisión, `INFO` y `CLIENT SETINFO` son rechazados.
   - **Entorno.** El contenedor del sitio **no carga el `.env`**. Antes lo cargaba entero: clave de la aplicación, clave del dueño de la base, frase de las copias y `REDIS_PASSWORD`. Con acceso a Redis, bastaba leer `REDIS_PASSWORD` de su propio entorno para entrar como `default` y anular la ACL. Ahora recibe una lista explícita (`NUXT_API_INTERNA`, `NUXT_PUBLIC_API_URL`, `NUXT_PUBLIC_DOMINIO`, `NUXT_REDIS_HOST/PORT/USUARIO/PASSWORD`).
6. **`REDIS_SITIO_PASSWORD` es un secreto obligatorio** del servidor (`/etc/sede/<entorno>.secretos.env`, ADR-0019). Sin ella el despliegue se detiene antes de tocar nada. Añadirla obliga a editar `despliegue-remoto.sh`, regenerar su huella y **reinstalarlo a mano** en el servidor.
7. **El sitio no depende de Redis para arrancar.** `sitio` no declara `depends_on: redis`: el respaldo falla abierto (ADR-0018).

## Consecuencias

- **Un sitio comprometido** puede leer y escribir copias (`sitio:*`) y nada más: ni sesiones, ni colas, ni `FLUSHALL`, ni `CONFIG`, ni scripts, ni PostgreSQL. Puede **envenenar sus propias copias**: si controla el proceso, controla también lo que el proceso sirve, así que, por razonamiento, esta decisión no amplía lo que ya podía hacer con lo que sirve.
- **La memoria sigue siendo compartida.** La ACL aísla claves, no memoria. Con `volatile-lru`, las copias y las sesiones (que tienen TTL, 120 min) compiten por expulsión; las listas de las colas no tienen TTL y `volatile-lru` no las expulsa. Se acota con la lista blanca, el tope de 1 MiB por cuerpo y el TTL, y se **midió** en T9.6 (sección «Medición (T9.6)»). Si superara el umbral fijado en «Medición (T9.6)», se revisa el reparto.
- **Reiniciar Redis corta sesiones y colas unos segundos** (la línea de comandos cambia con esta decisión, así que el primer despliegue lo recrea). Horizon reconecta. Se despliega en hora de poco tráfico y se avisa antes.
- **La red `cache-sitio` y la lista de entorno quedan vigiladas por la CI:** `deploy/pruebas/guarda-cache-sitio.sh` lee la pila resuelta (red, regla ACL, entorno exacto del sitio, ausencia de otros secretos) y `deploy/pruebas/prueba-acl-redis-sitio.sh` arranca el Redis de `compose.yaml`, con su comando, y prueba lo que el usuario puede y no puede hacer.
- **Con el respaldo y una API caída más de 24 h**, la página muestra el estado de error: es el tope aprobado.
- **Lo que no cubre:** las imágenes (T9.7, caché de nginx), el 419 del panel (T9.8) y un entorno con varias réplicas del sitio (medido con dos procesos Node contra un mismo Redis: uno escribe y el otro lee; no medido con réplicas reales; cada proceso tiene su propio cortocircuito).

## Alternativas descartadas

- **Caché de rutas de Nitro:** sirve la copia en vez de consultar la API y no tiene tope de edad para el valor viejo (H22).
- **Compartir `REDIS_PASSWORD` con el sitio:** un sitio comprometido tendría Redis entero.
- **Poner el sitio en `data`:** alcanzaría PostgreSQL.
- **Un Redis aparte sólo para el sitio:** aísla memoria y claves, pero suma un servicio y su memoria al servidor; se reconsidera si T9.6 muestra presión de memoria sobre el Redis compartido.
- **Mantener el `env_file` del sitio y confiar en la ACL:** la ACL se anula leyendo la contraseña principal del propio entorno.

## Medición (T9.6, 2026-11-25)

Hecha con el sitio compilado de `develop` (f243fa1 más la corrección de la ficha), el Redis de producción por su digest con el comando de `compose.yaml`, el usuario ACL `sitio` y un proxy a la API real de staging que se apaga o se degrada. La parte que puede romperse en silencio quedó como prueba permanente: `sitio/tests/respaldo-extremo.test.ts` (corre en el CI, con el sitio compilado y el Redis real).

**Comportamiento observado**

| Caso | Resultado |
|---|---|
| API sana | La página responde 200 y el sitio guarda su lectura en `sitio:respaldo:…` con 24 h de vida. Cuatro páginas públicas (portada, catálogo, noticias y políticas) dejaron 7 claves, el mismo número que se contó en el servidor de staging. |
| API apagada, con copia | 200 en 12–30 ms; texto de la página idéntico al de la API sana (portada, catálogo, noticias, políticas, ficha); una línea `respaldo_servido` por lectura. |
| API apagada, sin copia | El estado de error de siempre («No se pudo cargar el catálogo de trámites»), idéntico al del sitio anterior al respaldo. |
| 404 de la API | Se pasa tal cual; no se guarda ni se sirve de la copia. |
| 503 de la API | Con copia se sirve la copia (motivo `5xx`); sin copia, la página de error. |
| Redis apagado | La página no se vuelve más lenta (tres rondas alternadas de 20 peticiones: mediana 0,267–0,274 s apagado frente a 0,273–0,285 s encendido; sin copias servidas en ninguna). |
| Recuperación | Con el cortocircuito abierto, la API vuelve a usarse a los 31 s del último fallo (pausa de 30 s y una sonda). Con menos de 3 fallos, en la primera petición. |
| Ruta de ficha con la API fallando | **Corregido.** Antes, `/tramites/{slug}` respondía 404 («El trámite no existe») ante cualquier fallo de la API, también en el sitio anterior al respaldo; ahora 404 solo si la API dijo 404 y 503 si no contestó. |

**API colgada: ventana de lentitud (decisión de José, 2026-11-25).** Con la API *colgada* (no rechaza, no contesta), cada lectura esperaba sus 4 s antes de servir la copia y las lecturas de una página son consecutivas: la portada tardaba 16,2 s (4 lecturas) y el catálogo, las noticias y las políticas 8,2 s (2 lecturas). Ahora, cuando una lectura sirve la copia por lentitud, durante `VENTANA_LENTITUD_MS` (30 s) las lecturas que tengan copia la sirven sin esperar, y la petición a la API sigue saliendo. La ventana se cierra antes si la API contesta (ni 5xx ni 429). Sin copia no hay atajo: se espera a la API como siempre. Medido con el sitio compilado y Redis real: portada 4,03 s la primera vez (antes 16,2 s) y 0,03 s dentro de la ventana; catálogo, noticias y políticas 0,01–0,03 s tras ella; pasada la ventana con la API aún colgada responde el cortocircuito; con la API recuperada, 1,1 s y la copia se renueva. **Contrapartida:** dentro de la ventana se sirve la copia aunque la API ya hubiera contestado a *esa* ruta; la copia tiene la edad de la última visita a ella (hasta 24 h) y la ventana dura como mucho 30 s. Con la API apagada o devolviendo 5xx no ocurría esta espera: responde en milisegundos.

**Memoria** (catálogo de staging: 123 trámites, 12 noticias, 5 políticas), medida con `MEMORY USAGE` tras guardar con el mismo formato y la misma llamada que el almacén:

| Conjunto | Claves | Bytes en Redis | % de `maxmemory` (448 MiB) |
|---|---|---|---|
| Lo que las páginas pueden guardar (`per_page` 3, 6, 9 y 10, o ninguno) | 342 | 2,3 MiB | 0,52 % |
| Todo lo que admite la lista blanca (`per_page` 1…100, todas las páginas, tres `type`) | 1.946 | 28,1 MiB | 6,28 % |

La clave mayor alcanzable por las páginas ocupa 28,8 KB; la mayor de la lista blanca, 98,4 KB (`/tramites?page=1&per_page=100`). Redis no expulsó ninguna clave (`evicted_keys` 0) y el registro de la ACL quedó vacío. El `per_page` lo fija el código de cada página, no el visitante, y nginx envía `/api/v1` del navegador a PHP sin pasar por la ruta del sitio, así que el segundo conjunto es una cota, no un caso alcanzable. Ambas cifras crecen en proporción al tamaño del catálogo.

**Umbral de memoria (decisión de José, 2026-11-25).** Se revisa el reparto si las claves `sitio:*` pasan del **10 % de `maxmemory`** (44,8 MiB con 448 MiB): 1,6 veces la cota máxima medida (6,28 %). La cifra es una estimación de quien propuso, sin datos de la Entidad detrás. **Aún no hay alerta automática:** el repositorio no tiene ninguna que llegue a una persona (lo más parecido es el correo opcional de las copias, `BACKUP_NOTIFICACION_CORREO`, y los temporizadores de systemd no avisan a nadie), y José decidió dejarla para la fase de monitoreo (DISP-01). **Actualización 2026-11-26:** la parte de disponibilidad desde fuera ya existe (`.github/workflows/sonda.yml` y `deploy/sonda-sitio.sh`): cada 15 minutos pide `/`, `/health` y `/api/v1/tramites?per_page=1` a staging y abre una incidencia si algo no responde. **Corrección 2026-11-27:** no comprueba `X-Sede-Respaldo`: esa cabecera la pone la ruta interna del sitio y las peticiones de un visitante a `/api/v1` no pasan por ella (nginx las manda directo a PHP), así que desde fuera nunca puede verse; la API caída se ve por el 5xx de `/api/v1` y de `/health`. Si Cloudflare desafía al ejecutor de GitHub (`cf-mitigated: challenge`) el resultado es «no verificable» y no abre incidencia. Solo corre desde `main` (límite de GitHub para los flujos programados). **No cubre** ninguna de las tres señales de abajo: siguen siendo comprobación manual. Mientras tanto la comprobación es manual, de solo lectura, en el servidor: `INFO memory` (`used_memory`, `maxmemory`), `INFO stats` (`evicted_keys`) y la suma de `MEMORY USAGE` de las claves `sitio:*` (`SCAN --pattern 'sitio:*'`). Estado medido el 2026-11-25: `used_memory` 2,20 MiB, `sitio:*` 41.546 B (8 claves, 0,009 % de `maxmemory`), `evicted_keys` 0. Lo que la fase de monitoreo debe vigilar: (1) `sitio:*` > 10 % de `maxmemory`, (2) `evicted_keys` creciente (es el daño real: sesiones del panel o copias expulsadas; el 10 % es solo una señal indirecta) y (3) rechazos nuevos en `ACL LOG` del usuario `sitio`.

**Actualización 2026-11-26 (señales internas de Redis).** Las tres señales de abajo las vigila ahora `php artisan monitoreo:redis` (`backend/app/Console/Commands/VigilarRedis.php`), que el servicio `scheduler` ejecuta cada 15 minutos. Solo LEE Redis con el usuario principal de la aplicación (comandos crudos: INFO, SCAN, MEMORY USAGE, ACL LOG, sin el prefijo de Laravel). Avisa de: `sitio:*` por encima del 10 % de `maxmemory` (como mucho cada 6 h); `evicted_keys` mayor que en la corrida anterior; y suma de rechazos del usuario `sitio` en `ACL LOG` mayor que en la corrida anterior. La primera corrida solo toma la medida, y si un contador baja (Redis reiniciado, registro vaciado) no avisa. Con `MONITOREO_NOTIFICACION_CORREO` envía un correo con todas las señales; siempre deja el error en el registro y termina con error. **Limitaciones, sin disimular:** (a) no hay SMTP en staging ni en producción (`MAIL_MAILER=log`), así que hoy el «correo» solo llega al registro del servicio `scheduler`; el aviso llegará a una bandeja cuando exista un proveedor; (b) el destinatario no se versiona (el repositorio es público): va en `.accesos.local.env` y `publicar-entorno.sh` lo escribe en el archivo de secretos del servidor; (c) el aviso de `ACL LOG` cuenta rechazos del usuario `sitio`, incluidos los que provoque una prueba a mano; (d) probado contra Redis 7.0 real y la lógica con un lector falso; con Redis 8 (la imagen de producción) el CI prueba `LectorRedisReal` contra `redis:8-alpine` (`ci.yml`, servicio `redis`, mismo resumen que `compose.yaml`), de modo que ya no está «sin medir» para esa clase.

**Corrección 2026-11-27 (la agenda con Redis caído).** Con Redis caído no corría ninguna tarea programada, tampoco `monitoreo:redis`: Laravel 13 consulta la caché por omisión (Redis) en cada `schedule:run` para saber si la agenda está en pausa, y otra vez para el candado de cada tarea con `withoutOverlapping()`. Medido en la imagen `sede-app` (Laravel 13.40.0, phpredis) sin red: `schedule:run` salía con código 1 por `Redis::connect()`. Ahora `backend/routes/console.php` desactiva esa consulta (`Schedule::withoutInterruptionPolling()`: no se usan `schedule:pause` ni tareas de menos de un minuto) y guarda los candados en disco (`Schedule::useCache('file')`), válido porque hay un solo programador (`compose.yaml`). Con el mismo Redis inalcanzable, `schedule:run` sale con 0, los seis candados se toman y una segunda corrida simultánea se salta. Lo vigila `AgendaSinRedisTest`. Con Redis caído, `monitoreo:redis` informa «No se pudo leer Redis» y termina con error; sin SMTP, ese aviso sigue llegando solo al registro, limitación (a). Consecuencia: `schedule:pause` y `schedule:interrupt` no tienen efecto (`AgendaSinRedisTest` lo fija); para detener la agenda se detiene el servicio `scheduler`. Los candados de disco sobreviven a un SIGKILL y a los reinicios (`almacenamiento-app` es un volumen): por eso `monitoreo:redis` caduca a los 10 min y las cinco tareas diarias a las 23 h, y `AgendaCandadosTest` exige que ninguna caduque después de su siguiente corrida.

