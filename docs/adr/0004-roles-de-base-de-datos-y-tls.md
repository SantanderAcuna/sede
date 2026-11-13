# ADR-0004: Roles de base de datos separados y TLS a la base

> **Estado:** PROPUESTA (entrega 0A de la Fase 0; pasa a ACEPTADA cuando José apruebe la entrega)
> **Fecha:** 2026-11-13
> **Decisor:** José (decisión D-0.1 de la ficha de la Fase 0)
> **Tags:** base de datos, seguridad, copias, despliegue

## Contexto

La aplicación, Horizon y el planificador usaban `sede`, el superusuario de arranque de la imagen de
PostgreSQL y dueño de todos los objetos. Un compromiso de la aplicación equivalía a poder alterar o
borrar el esquema. Además la copia nocturna usaba esa misma conexión, y la conexión a la base no
estaba cifrada.

## Decisión

1. **`sede` sigue siendo el dueño** y sólo corre migraciones, desde el servicio `migrar` del
   despliegue (`php artisan migrate --database=pgsql_migracion`, seguido de `bd:sincronizar-roles`).
2. **`sede_app`** (SELECT/INSERT/UPDATE/DELETE y secuencias; sin DDL ni TRUNCATE; sin superusuario,
   CREATEDB, CREATEROLE, REPLICATION ni BYPASSRLS) lo usan la aplicación, Horizon y el planificador.
   Compose **sustituye** `DB_USERNAME`/`DB_PASSWORD` del `.env` por `DB_APP_*` en esos servicios: el
   entorno de la aplicación nunca lleva la clave del dueño.
3. **`sede_lectura`** (SELECT) la usa la copia, por la conexión `pgsql_copia`.
4. Los roles se crean en una migración idempotente (`App\Support\RolesBaseDeDatos`). Los roles son del
   clúster, así que `down()` no los borra. **Rotar una clave** = cambiar el secreto y redesplegar:
   `bd:sincronizar-roles` corre en cada despliegue (una migración ya corrida no se repite).
5. `REVOKE CONNECT, TEMPORARY … FROM PUBLIC` y `REVOKE CREATE ON SCHEMA public FROM PUBLIC`.
6. **TLS:** `POSTGRES_SSL=on` (guion `docker/postgres/entrypoint-tls.sh`, que viaja DENTRO de `compose.yaml` como `configs.entrypoint_tls` —al servidor no llegan archivos sueltos del repositorio— y que `deploy/pruebas/guarda-montajes-compose.sh` mantiene idéntico a la fuente; que no arranca si faltan los
   certificados) y `DB_SSLMODE=require`. Producción: activado. Local: activado con certificado de
   prueba. **Staging: sin TLS, riesgo aceptado por José.**
7. Con `POSTGRES_SSL=on` el servidor **rechaza** las conexiones de red sin TLS (`pg_hba.conf` propio:
   `hostssl … scram-sha-256` y `hostnossl … reject`); los sockets locales quedan como en la imagen.

## Verificado (2026-11-13, PostgreSQL 18, autenticación SCRAM)

- Migraciones desde cero con el dueño, y la migración de roles dos veces.
- `sede_app` y `sede_lectura` inician sesión con su clave; se rechazan con clave incorrecta o cruzada.
- `sede_app`: lee y escribe; `DROP`, `CREATE TABLE`, `TRUNCATE` y `CREATE ROLE` denegados; el
  sembrador `SedeSeeder` corre completo con este rol.
- `sede_lectura`: `SELECT` sí, `DELETE` no; `pg_dump -Fc` completo funciona con este rol.
- Con `sslmode=require` la conexión de Laravel negocia TLSv1.3; con `sslmode=disable` el servidor la rechaza («no encryption»).

## Límites conocidos (no resueltos)

- `require` cifra pero **no verifica** al servidor; `verify-full` pide una autoridad certificadora.
- Con `log_statement` en `ddl`/`all`, `CREATE/ALTER ROLE … PASSWORD` deja la clave en el registro de
  PostgreSQL.
- La copia sigue en el mismo servidor.
- Las pruebas de PHPUnit sólo autentican claves si el servidor no confía en el origen (la imagen
  oficial confía en 127.0.0.1): la prueba se omite y lo dice. Con `DB_HOST` en una IP no local pasa
  9/9. Que el servicio de CI autentique es HIPÓTESIS hasta ver su primera ejecución.
- No verificado: el esquema `public` de staging (`\dn+`), sin acceso desde la sesión de desarrollo.

## Alternativas descartadas

- Un tercer rol `sede_migracion` (existía en `docker/postgres/init/01-usuarios.sql.example`, sin
  montar): el dueño ya migra y un rol más no aísla a la aplicación.
- Roles creados por un guion de inicialización: sólo corre al crear el volumen, no en entornos ya
  creados.
