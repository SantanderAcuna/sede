# Verificación de la Fase 0C, sitio público resistente (2026-11-26)

Estado: **cerrada por José el 2026-11-26.** Este documento solo registra lo medido; las decisiones están en
`docs/adr/0009-respaldo-de-la-ultima-respuesta-valida-del-sitio.md` y
`docs/adr/0010-cache-de-nginx-para-imagenes-y-documentos-de-politicas.md`.
Los archivos 0009 y 0010 figuran como ACEPTADA.

## Tareas y evidencia en el repositorio (HECHO: cada commit está en `develop`)

| Tarea | PR y commit | Prueba |
|---|---|---|
| T9.1 Cliente único de la API pública | #701 `1115779` | `sitio/tests/cliente-api.test.ts` |
| T9.2 Núcleo del respaldo (lista blanca, tiempos, cortocircuito) | #703 `81637be` | `sitio/tests/respaldo-api.test.ts` |
| T9.3 Almacén Redis con usuario ACL | #704 `7694a13` | `almacen-respaldo.test.ts`, `almacen-respaldo.redis.test.ts` (Redis real en CI) |
| T9.4 El respaldo entra en la ruta `/api/v1` | #707 `4c0b5cf` (y N1 `e448481`) | `respaldo-ruta.test.ts`, `respaldo-sitio.test.ts`, `ruta-api-v1.test.ts` |
| Corrección N3: a los 4 s se sirve la copia si existe; sin copia se espera | #721 `979ba64` | `respaldo-api.test.ts`, `respaldo-sitio.test.ts` |
| T9.5 Red propia, usuario ACL y contraseña propia del sitio en Redis | #723 `f243fa1` | `deploy/pruebas/prueba-acl-redis-sitio.sh`, `guarda-cache-sitio.sh`, `guarda-bootstrap-cache.sh`, `prueba-render-entorno.sh` |
| T9.6 Verificación real del respaldo (sitio compilado y Redis real) | #725 `54f93b0` | `sitio/tests/respaldo-extremo.test.ts` |
| T9.6 Ventana de lentitud de 30 s (decisión de José, opción B) | #727 `2e8e2c1` | `respaldo-api.test.ts` |
| T9.7 Caché de nginx para imágenes y documentos de políticas | #732 `bbe0c23` (ajuste previo #705 `ee6494f`) | `deploy/pruebas/prueba-cache-nginx.sh` (nginx real, origen FastCGI simulado), `guarda-cache-nginx.sh`, pruebas de backend de imagen con sesión real |
| T9.7 Documentos conectan con tope de 2 s | #739 `8be202d` | sección 8b de `prueba-cache-nginx.sh` (origen que no acepta conexiones) y guarda estática |
| T9.8 401 y 419 con sesión abierta abren el aviso «Tu sesión venció» | #737 `bbb4497` | `panel/tests/sesion-vencida.test.ts`, `panel/tests/sesion-vencida-integracion.test.ts` (cableado real) |
| Cierre | este documento | — |

Conteo de pruebas en `develop` `8be202d`, medido el 2026-11-26: sitio 1083 pasan y 14 se omiten (47 archivos); panel 723 pasan (48 archivos).

## Verificado en staging (2026-11-25 y 2026-11-26)

| Comprobación | Resultado |
|---|---|
| T9.5, redes y variables de `sitio` | `sede-backend` y `sede-cache-sitio`; ningún secreto ajeno (sin `APP_KEY`, `DB_*`, `REDIS_PASSWORD` principal ni `BACKUP_PASSPHRASE`) |
| T9.5, ACL de Redis | usuario `sitio` con `~sitio:* -@all +get +set +del +ping +auth`; `get sede_cache:x` y `flushall` → NOPERM; el cliente real guardó 7 claves sin rechazos |
| T9.6, copias en Redis | 8 claves `sitio:*` con TTL entre 62 743 y 63 201 s (tope 86 400), JSON válido; 41 546 B en memoria frente a `maxmemory` de 448 MiB (0,009 %); `evicted_keys` 0 |
| T9.6, ventana de lentitud con `app` en pausa | portada 4,055 s (antes 16,2 s); `/tramites`, `/noticias`, `/politicas` entre 0,031 y 0,037 s; 14 `respaldo_servido`, todos motivo `tiempo`; recuperación 0,34 s |
| T9.7, imagen y documento con PHP-FPM real | EXPIRED/HIT correctos; cookie `sede-session` → BYPASS; slug inexistente → 404 sin guardarse; `If-None-Match` → 304 |
| T9.7, con `app` detenido (despliegue `5a00225`) | imagen y documento 200 `STALE` en 2,0 s; sin copia 504 en 2,0 s; `app` de vuelta en ~6 s |
| T9.8, en el navegador integrado | con «Nueva noticia» a medio escribir y la sesión cerrada en el servidor, «Guardar» recibió 401, se abrió el aviso, la URL no cambió y los campos quedaron intactos |
| T9.8, a mano (José) | contraseña mala: el aviso sigue con el error; contraseña buena: se cierra |
| Despliegues | 8 checks de CI, despliegue a pruebas y 4 navegadores (chromium, firefox, webkit, msedge) en verde en `6283ae8`, `da59f3a`, `18d901f` y `04e7409`; en `5a00225` (#739) los 8 checks y el despliegue en verde, sin comprobar el resultado de los 4 navegadores |

## Defectos hallados durante la fase y corregidos

- N3 (#721): la espera de 4 s servía la copia aunque la API contestara a tiempo con error; corregido y probado.
- T9.6 (#727): con la API colgada la portada tardaba 16,2 s; ahora 4,03 s.
- T9.7 (#739): los documentos de políticas esperaban 60 s por la conexión con la copia vieja y `app` detenido; ahora 2,0 s.
- T9.8 (#737): dos defectos de la primera versión, hallados antes de fusionar (una contraseña mala dentro del aviso cerraba la sesión local; una lectura 401 borraba la sesión y el 419 siguiente no avisaba). Las pruebas con `api/auth` simulado no los veían; la prueba de integración usa el cableado real.

## Pendientes que no bloquean el cierre

- Umbral de memoria de `sitio:*` en 10 % de `maxmemory` (44,8 MiB), estimación sin datos de la Entidad; la alerta se aplazó a la fase de monitoreo (DISP-01). Hoy 0,009 %.
- El valor por omisión de 60 s de conexión sigue en las demás rutas de la API (`/api/v1`, `/sanctum/csrf-cookie`, `/health`): decisión de producción junto con `pm.max_children = 5` (auditoría H3), con diseño de Kubernetes y prueba de carga.
- 6 descargas simultáneas del mismo documento sin copia y con `app` detenido dan 504 escalonados entre 2 y 12 s (hacen cola en el bloqueo); documentado en el ADR 0010.
- No visto en el servidor: que el barrido borre archivos de más de 24 h (nada es tan viejo todavía); solo lo cubre `guarda-cache-nginx.sh` en CI.
- Sin probar con réplicas reales del sitio: medido con dos procesos Node contra un mismo Redis (ADR 0009).
- `publicar-entorno.sh` reescribe `.env` y `compose.yaml` sin guardar `.anterior` (comportamiento medido, 2026-11-25). Revisado el 2026-11-26: **no se cambia**, porque `desplegar` copia a `.anterior` el `.env` que encuentra justo antes de sustituirlo; si `publicar` guardara el anterior, ese guardado lo pisaría el siguiente despliegue, y un retroceso tras rotar secretos volvería a las contraseñas viejas. El riesgo que queda es acotado: publicar desde un commit más nuevo que la versión en marcha y que el despliegue siguiente falle (el retroceso devuelve el `compose.yaml` publicado, no el de la versión previa). El daño no está demostrado; solo se decide con una medición de ese caso.
- Decisión pendiente sobre `rotar-secretos.py --github`.
- Antes de promover a `main`: comprobar en GitHub la variable `SEDE_DESPLIEGUE_PRODUCCION` (no se puede leer desde el entorno de Claude). `main` (`9d91290`) está 105 archivos atrás de `staging`.
- Comprobación local de José (`make local`, 2026-11-26): terminó con «Listo» y la siembra completa. `sede-redis-1`, `sede-postgres-1`, `sede-app-1` y `sede-sitio-1` en `Healthy`; `sede-nginx-1`, `sede-cache-barrido-1` y `sede-correo-1` arriba. Faltan en lo que pegó los pasos 3 y 4 (migraciones) y las comprobaciones desde el navegador en `https://localhost:8443`; no se leen como verificadas. Necesitó `POSTGRES_PUERTO_LOCAL=5434 REDIS_PUERTO_LOCAL=6380` porque su PC tiene otros contenedores en 5432, 5433, 6379 y otros puertos (conflicto de su máquina, no de la pila).
- Defecto hallado en esa corrida y corregido en #742: `make local` con un `.env` anterior a T9.5 no agregaba `REDIS_SITIO_PASSWORD` y Compose se negaba a arrancar; prueba nueva `deploy/pruebas/prueba-local-env.sh` (3 fallas en rojo antes, verde después). #742 está fusionada en `develop`.
