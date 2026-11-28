# Correcciones tras verificar las fases 0A–0D y DISP-01

Rama: `fix/programador-sin-redis` (desde `origin/develop` 3526ba3). TDD estricto (regla global del usuario); se
prueba con `php artisan test` en `backend/`, sobre Postgres y Redis desechables como en `ci.yml`.

Antes estaba en `odd/tasks/correcciones-fase-0-y-disp-01.md`, en inglés. Se pasó a `docs/` y al español el
2026-11-28, por pedido de José. Los pendientes que quedan abiertos siguen en
[`plan-pendientes-fase-0.md`](plan-pendientes-fase-0.md).

## Objetivo

Corregir lo que se verificó roto al auditar el estado de las fases (0A–0D, DISP-01), y dejar registrado, con
evidencia, lo que no se puede corregir sin una decisión de José.

## Evidencia (verificada el 2026-11-27)

- Plan vigente: `plan-correccion-bd-modulos-2026-11-13.md` (v3.5, fases 0–8), aprobado el 2026-11-13. Sus tres
  archivos no estaban en `develop` ni en el disco, pero SÍ estaban en git: commit `88c48e2` (José,
  2026-11-17 23:57Z) en `origin/feat/0b-t6-panel-api`, hecho 20 minutos después de fusionarse el PR #621 (23:37Z),
  por eso nunca llegó a `develop`. El mismo commit trae
  `sede-electronica-doc/03-servicios-tramites/decisiones-reconstruccion-tramites.md`. Frente a las copias recuperadas
  de la sesión `0728262f`: el plan y el estado del panel son idénticos salvo una línea en blanco final; la ficha de la
  fase 0 en git es más nueva (actualizada el 2026-11-17, 82 líneas frente a 68). Corregido el 2026-11-28: antes esta
  línea decía que ya no existían en git, y era falso. Siguiente fase según la v3.5: la Fase 1 (entidad y
  dependencias); no ha empezado (la tabla `entidads` sigue existiendo).
- `pm.max_children = 5` es el valor por omisión del `www.conf` de `php:8.6-fpm-alpine`; el repositorio no configura
  ningún pool de FPM. La regla del §15.3 del plan archivado, con el `mem_limit: 1.5g` y el `memory_limit = 128M`
  actuales, da 9.
- La fase 0D no está en el plan v3.5; empieza el 2026-11-18 (PR #637–#644). Solo el ADR 0007 registra la aprobación
  de la ficha 0D; el documento de la ficha no existe en ninguna parte.
- `verificacion-fase-0c-2026-11-26.md:51` cita una «auditoría H3» para `pm.max_children`; ningún documento del
  repositorio ni de las transcripciones locales define ese H3 (el H3 de la ficha de la fase 0 trata de las copias
  de seguridad).

## Tareas

- [x] T1 La agenda corre con Redis caído (directa: 1 archivo de código y su prueba). Commit `f6b6bb8`.
  - Antes: `schedule:run` salía con 1 en `Redis::connect()` en la imagen `sede-app`; no corría ninguna tarea.
  - Después: sale con 0, se toman los seis candados y una segunda corrida simultánea se salta. `AgendaSinRedisTest`
    RED→GREEN; suite completa: 1044 pruebas, 1040 pasan, 4 omitidas, 0 fallos; Pint y PHPStan limpios.
  - Revisión RDD: riesgo medio, consentimiento dado; se revisó junto con T2 (linaje `review-100e243f5700cdd7`),
    aprobada y reconocida después de `claude auth login`.
- [x] T2 El candado de `monitoreo:redis` caduca a los 10 min en vez de 1440 (directa: 1 archivo de código y su
  prueba).
  - RED: `Failed asserting that 1440 is identical to 10`; GREEN tras `withoutOverlapping(10)`. Medido con el almacén
    de archivos: el candado sigue tomado a los +9 min y queda libre a los +11 min. Pint y PHPStan limpios; suite
    completa: 1044 pruebas, 1040 pasan, 4 omitidas, 0 fallos.
  - Por qué: con `pcntl` cargado, el candado se suelta con SIGTERM, pero un SIGKILL (falta de memoria en el
    contenedor `scheduler` de 256 MB, `docker kill`) lo deja puesto 24 h y deja muda la vigilancia de Redis un día.
- [x] T3 Los candados de las cinco tareas diarias caducan a las 23 h en vez de a los 1440 min (directa: 1 archivo de
  código y su prueba).
  - RED: el candado de `consentimientos:depurar` seguía tomado el 2026-11-28 a las 02:15:00 tras un corte a las
    02:15:05 (caducaba a los 1440 min). GREEN con `withoutOverlapping(23 * 60)`. `AgendaCandadosTest` comprueba cada
    tarea con candado contra su propia corrida siguiente.
  - También, por la revisión RDD de T1/T2 (aprobada, 2 hallazgos informativos): `AgendaSinRedisTest` suelta ahora
    los candados en `setUp`/`tearDown` (sin restos que vuelvan inestable la prueba) y fija que la pausa y la
    interrupción están apagadas (lo dice el ADR 0009).
  - Suite completa sobre Postgres y Redis desechables: 1047 pruebas, 1043 pasan, 4 errores = `LectorRedisRealTest`
    (`Class "Redis" not found`: el PHP de este equipo no tiene phpredis, lo confirma `php -m`; la CI sí lo instala).
    Pint y PHPStan limpios.
- [x] D1 José decidió (2026-11-28) llevar a `develop` los cuatro archivos de `88c48e2`; se copiaron byte a byte
  (PR #756). Nunca existió una decisión de dejarlos sin versionar: el 2026-11-17 a las 22:05Z un agente los vio sin
  versionar y no los tocó porque parecían entregas de José, y se versionaron 2 h después (ver la evidencia).
- [ ] D2 Valor de `pm.max_children` en producción. Decidido el 2026-11-28: se mide con una prueba de carga en local
  (tarea T5 del plan).
- [ ] D3 La referencia a la «auditoría H3» no tiene fuente. La añadió `a06ff56` (#741, 2026-11-26). Se buscó sin
  resultado en el disco, en las transcripciones de `~/.claude` y `~/.config/Claude`, en Engram, en todas las ramas y
  en los PR e issues de GitHub. Decidido el 2026-11-28: se cita la fuente que existe (plan archivado §15.3 y F10.3),
  tarea T3 del plan.
- [x] D4 Proveedor SMTP: José decidió el 2026-11-28 que no se hará. Las alertas pasan por incidencias de GitHub
  (D6).
- [ ] D5 Promover a `main`: `sonda.yml` no existe en la rama por defecto, así que la sonda nunca se ejecuta (API de
  GitHub: 404). `develop` y `staging` están sincronizados desde el PR #753 (`d205d08`, desplegado bien en staging);
  a `main` le faltan 44 commits de `develop` y no tiene ninguno que le falte a `develop` (250 commits de fusión, sin
  parches propios). Bloqueado además por la política de ramas del entorno `production` (acción J1 del plan).
- [ ] D6 Alertas `[crit]` de nginx y umbral de la caché (ADR 0010). Decidido el 2026-11-28: avisos por incidencia
  de GitHub desde el servidor, y alerta cuando nginx sirve copias `STALE` 5 minutos seguidos (tarea T6 del plan).
- [ ] D7 El documento de la ficha 0D no existe; solo el ADR 0007 registra su aprobación. Decidido el 2026-11-28: se
  deja constancia de que no se conserva (tarea T3 del plan).
- [ ] D8 La ficha 0C tampoco se versionó nunca: `ci.yml:206,211` y `sitio/server/utils/almacen-respaldo.ts:141`
  citan sus hallazgos H35 y B4, pero ningún commit de ninguna rama contiene una ficha 0C. Por eso tampoco se puede
  comprobar el origen del H3 de D3. Decidido el 2026-11-28: se cita el PR #704 (tarea T3 del plan).
- D9 (fuera de este documento y del plan) Copias de los secretos de la aplicación en los entornos de GitHub: la
  evidencia está en el plan.

## Siguiente paso

T1–T3 están en `develop` y en `staging` (PR #754); D1 y D4 cerrados. El resto sigue en
[`plan-pendientes-fase-0.md`](plan-pendientes-fase-0.md).
