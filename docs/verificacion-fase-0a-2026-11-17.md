# Verificación de la Fase 0A (2026-11-17)

Estado: **verificada en local y en staging, y aprobada por José el 2026-11-17.** Pendientes que no
bloquean la fase: ver el final. Este documento solo registra lo medido; la decisión está en
`docs/adr/0004-roles-de-base-de-datos-y-tls.md`.

## Verificado en local

| Tarea | Prueba | Resultado |
|---|---|---|
| T2 Roles | `RolesBaseDeDatosTest` contra PostgreSQL 18 desechable, con autenticación real (origen que no es `127.0.0.1`) | 9 de 9, 31 aserciones, sin omitidos |
| T2 Tablas futuras | La tabla de apoyo se crea después de migrar los roles; `sede_app` y `sede_lectura` la usan | Pasa: solo `ALTER DEFAULT PRIVILEGES` puede explicarlo |
| T3 Conexiones | `compose.yaml` y contenedores recreados: `app`, `scheduler` y `horizon` con `DB_USERNAME=sede_app`; consulta real `current_user` en la app | `sede_app` |
| T3 Cola | Trabajo encolado (`Artisan::queue('db:show')`) y ejecutado por Horizon | `completed`, cola `default` |
| T4 Copia | `backup:run --only-db` con `pgsql_copia` (`sede_lectura`) | Zip de 98,34 KB, verificado y copiado al disco `copias` |
| T4 Monitor | `backup:monitor` tras la copia | «healthy» (antes: copia de 4 días) |
| T4 Restauración | `make respaldo` | 35 tablas y 3464 filas iguales, sin errores |

## Verificado en staging (2026-11-17, tras la promoción #615)

Despliegue a pruebas en éxito y los cuatro navegadores en verde. Lectura de solo lectura en el servidor,
con el usuario `ops` y `sudo docker compose` sobre `/opt/sede/staging`:

| Comprobación | Resultado |
|---|---|
| `DB_USERNAME` de `app`, `scheduler` y `horizon` | `sede_app` en los tres (es la configuración del contenedor, no una conexión viva) |
| Roles `sede%` en `pg_roles` | `sede`: superusuario; `sede_app` y `sede_lectura`: sin superusuario, sin crear BD ni roles, con login |
| `\dn+ public` | `pg_database_owner=UC`; PUBLIC, `sede_app` y `sede_lectura` solo `U` (sin `CREATE`) |
| Copia nocturna | carpeta `sede` modificada el 17-nov 06:30 UTC (01:30 de Bogotá, la hora programada); `backup:monitor`: «healthy» |

Límites que siguen vigentes y aceptados: la copia está en el mismo servidor que la base, y el TLS a la
base está apagado en staging (ADR-0004).

## Hallazgos del entorno local (no del código de la 0A)

- El contenedor `scheduler` se había creado el 2026-09-29, antes de la 0A, y corría como `sede`. Se recreó.
- `horizon` fallaba con `WRONGPASS` contra Redis: el contenedor `redis` conservaba una clave anterior al
  `.env` actual. Se recreó `redis` junto con `horizon`.
- La copia nocturna no se había ejecutado desde el 2026-11-13. Causa no confirmada.

## Pendiente (no bloquea la aprobación; se comprueba tras la promoción)

- Una tarea programada disparada por el `scheduler` (solo se ejecutó `backup:run` a mano).
- `make local` completo: no se corrió, porque `SedeSeeder` sobrescribe Entidad y menú en una base en uso.
- La tabla creada por la migración real a través del servicio `migrar` y `bd:sincronizar-roles`: se
  comprueba en el primer despliegue con tablas nuevas (Fase 1).
- Una conexión viva como `sede_app` en staging (`pg_stat_activity`), que completaría la lectura de
  `DB_USERNAME`.
- Producción: el certificado TLS de la base se emite con `certificado-bd.sh produccion --programar` cuando
  exista el entorno (dominio sin decidir, §21.A del plan).
