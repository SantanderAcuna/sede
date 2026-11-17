# Verificación de la Fase 0A (2026-11-17)

Estado: **verificada en local y aprobada por José el 2026-11-17; falta la verificación en staging, que se
hace con la promoción de `develop` a `staging`.** Este documento solo registra lo medido; la decisión está
en `docs/adr/0004-roles-de-base-de-datos-y-tls.md`.

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
- Staging: `\dn+` del esquema `public`, roles desplegados y copia nocturna (requieren acceso al servidor).
- Producción: el certificado TLS de la base se emite con `certificado-bd.sh produccion --programar` cuando
  exista el entorno (dominio sin decidir, §21.A del plan).
