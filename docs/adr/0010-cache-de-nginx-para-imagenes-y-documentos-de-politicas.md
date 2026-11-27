# Archivo 0010 (no es el `ADR-0010` del registro): caché de nginx como respaldo de las imágenes públicas y los documentos de políticas

> **Estado:** ACEPTADA (Fase 0C, tarea T9.7; ficha del 2026-11-23 y análisis del 2026-11-26; decisiones de José: barrido incluido, B9 incluido, `inactive=1h`)
> **Fecha:** 2026-11-26
> **Decisor:** José
> **Tags:** nginx, resiliencia, imágenes, seguridad
> **Complementa:** el archivo 0009 (respaldo del sitio). Aquel cubre el JSON que consume el sitio; este cubre lo que el navegador pide **directamente** a la API: imágenes y documentos.

## Contexto

El archivo 0009 hace que el sitio sirva la última respuesta buena del JSON cuando la API cae. Las imágenes (`/api/v1/banners/{uuid}/imagen`, `/api/v1/noticias/{slug}/imagen`, `/api/v1/contenido/imagenes/{uuid}`) y los documentos de políticas (`/politicas/{slug}.pdf|.odt` y `/politicas/{slug}/documento`) no pasan por el sitio: nginx las manda a PHP-FPM. Si PHP cae, el visitante ve la página con imágenes rotas y el enlace de descarga en error.

## Decisión

nginx guarda esas respuestas y, si PHP falla o no contesta, sirve la última copia buena. **No es una caché de rendimiento:** la vigencia es de 1 s (`fastcgi_cache_valid 200 404 1s`) y `Cache-Control`/`Expires` del origen se ignoran solo para nginx; el navegador y Cloudflare siguen viendo los de la aplicación. Con PHP sano cada petición llega a PHP.

1. **Dos zonas**, en el volumen con nombre `cache-sede` (el contenedor de nginx es de solo lectura y su `tmpfs` son 64 MB): `imagenes` (1 GiB) y `documentos` (512 MiB). Tope de disco: 1,5 GiB.
2. **Copia vieja** ante `error timeout http_500 http_503` y mientras se renueva (`updating`). No se usa `http_502` ni `http_504`: nginx no los acepta en `fastcgi_cache_use_stale` (la ficha original los tenía y nginx no arrancaba).
3. **Quien edita no usa la caché.** La cookie de sesión del panel (`sede-session`, nombre que sale de `Str::slug(APP_NAME).'-session'`) hace que la petición no se busque ni se guarde: el editor ve imágenes sin publicar. Tres defensas independientes: (a) el `map $sesion_del_panel`; (b) un 200 que el origen marca `private`/`no-store` no se guarda; (c) nginx no guarda una respuesta con `Set-Cookie`, que Laravel emite siempre al editor. Una guarda de CI falla si el nombre de la cookie de Laravel y el del `map` dejan de coincidir.
4. **Lo despublicado no resucita.** Un 404 se guarda **solo si reemplaza una entrada existente** (`$upstream_cache_status` = `EXPIRED`). Sin esto, una imagen o un documento despublicado volvía a servirse como copia vieja si PHP se caía después (medido). Un 404 nuevo (`MISS`) no se guarda: direcciones inventadas no llenan la caché.
5. **Imágenes:** espera de 5 s al origen (2 s de conexión) para que la copia salga rápido; antes era 60 s. **Documentos:** conservan los 60 s de espera de lectura (el PDF/A se genera al vuelo), pero conectan con el mismo tope de 2 s: sin él, `fastcgi_connect_timeout` vale 60 s por omisión y, con `app` parado, la copia vieja tardó 60,0 s en salir (medido en staging el 2026-11-26; con 2 s, como las imágenes). Además, el bloqueo de caché espera hasta 60 s (`fastcgi_cache_lock_timeout`/`_age`): varias descargas en frío del mismo documento generan UNA vez (con el valor por omisión, 5 s, cuatro simultáneas generaron cuatro).
6. **`fastcgi_read_timeout 60s` pasa a `nginx.conf` (contexto http).** Repetir la directiva en un `location` que incluye `snippets/fastcgi.conf` es `emerg` y nginx no arranca.
7. **Barrido de edad (servicio `cache-barrido`).** `inactive` de nginx no es un tope de edad: una entrada que se sigue pidiendo durante una caída no caduca (medido). El barrido borra lo que lleva más de 24 h sin renovarse (`find -mmin +1429`, cada 10 min; busybox borra desde N+1 minutos, de modo que `+1440` dejaba pasar hasta 24 h y 1 min). Corre en un contenedor aparte, con la misma imagen y el mismo volumen, como el usuario de nginx (101), sin red, sin capacidades y en solo lectura: nginx corre sin `DAC_OVERRIDE` y sus carpetas son 0700, así que ni siquiera root puede borrar desde dentro de nginx. Desactiva el `HEALTHCHECK` heredado (sin red quedaría `unhealthy`) y atiende `SIGQUIT`, la señal de parada de la imagen de nginx (sin eso `docker stop` tarda 10 s).
8. **`inactive=1h`.** Con un valor largo, si el barrido borra un archivo antes de que nginx purgue su entrada, nginx registra un `[crit] unlink() failed` por archivo, y cada reinicio de nginx (cada despliegue) reinicia el reloj de `inactive` de todo lo guardado. Con 1 h, mientras PHP esté sano nginx purga primero y el `[crit]` solo aparece tras una caída de más de 24 h, cuando sí es una señal real. **Contrapartida aceptada:** la copia vieja solo existe para lo pedido en la última hora o la hora siguiente al último reinicio. La portada se pide sin parar; la imagen de una noticia antigua puede no tener copia durante una caída.
9. **Orden en `default.conf`.** La ubicación de imágenes va antes de `~ /\.` y de los bloques por extensión, y el primer carácter del slug no puede ser un punto: con la expresión más simple, `/api/v1/noticias/.oculto/imagen` pasaba de 403 a llegar a PHP (medido).
10. **Sin `add_header`** en estas ubicaciones: descartaría las cabeceras de seguridad que heredan del servidor.

## Consecuencias

- Una caída de PHP-FPM ya no rompe las imágenes de la portada ni las descargas de políticas ya vistas en la última hora.
- El primer visitante tras un reinicio, o tras una hora sin que nadie pida un recurso, no tiene copia: si PHP está caído en ese momento, ve el error.
- La edad de la copia vieja es **menor de 24 h** (barrido), no «24 h exactas»: la cuantifica la prueba con tiempos escalados, no un reloj de 24 h.
- `cache=$upstream_cache_status` en el registro de nginx (`MISS`, `HIT`, `EXPIRED`, `STALE`, `BYPASS`) permite medir la caché. El umbral de alerta y el aviso de `[crit]` son de DISP-01 (monitoreo).
- **No resuelve:** los 300 s de `max-age` del navegador y de Cloudflare (una imagen despublicada se sigue viendo hasta 5 min allí) ni los 86 400 s de la imagen de contenido (decisión de la D9, ficha del 2026-11-23, observación 1).

## Evidencia

- `deploy/pruebas/guarda-cache-nginx.sh` (estática) y `deploy/pruebas/prueba-cache-nginx.sh` (nginx real: la imagen de `docker/nginx/Dockerfile` con la configuración del repo y las restricciones de `compose.yaml`; origen FastCGI simulado con las cabeceras medidas en staging el 2026-11-26). Ambas en el job `Pila`. **No ejercitan PHP-FPM:** la verificación con PHP real es la de staging tras desplegar.
- Pruebas de backend: `LecturasPublicasSinCookiesTest` (las tres rutas de imagen con `Referer` del dominio de la sede no ponen cookies), `NoticiasTest` (idem con una imagen 200) e `ImagenContenidoConSesionRealTest` (el editor recibe su cookie de sesión).
