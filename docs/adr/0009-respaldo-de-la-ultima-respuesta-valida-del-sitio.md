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
- **La memoria sigue siendo compartida.** La ACL aísla claves, no memoria. Con `volatile-lru`, las copias y las sesiones (que tienen TTL, 120 min) compiten por expulsión; las listas de las colas no tienen TTL y `volatile-lru` no las expulsa. Se acota con la lista blanca, el tope de 1 MiB por cuerpo y el TTL, y se **mide** en T9.6 (número de claves y bytes). Si superara el umbral que se fije al medir, se revisa el reparto.
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
