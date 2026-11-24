# Límites de tiempo por rol de la base de datos

Estado: implementada en la entrega 0D, tarea D7 (2026-11-18). Cierra el hallazgo R12 del plan de robustez.

## Para qué

Sin límites, una consulta atascada, un bloqueo que nadie suelta o una transacción abierta por olvido retienen una de las `max_connections` (100 en staging) hasta que nginx corta al cliente a los 60 s. En un pico de tráfico eso agota las conexiones para todos. Los límites cortan lo atascado y dejan intacto lo legítimo.

**Un valor por omisión de rol no es un techo.** Cualquier sesión puede subirlo con `SET`. Esto protege de errores y de la lentitud, no de una aplicación comprometida (eso lo cubren los permisos de `sede_app`, ver `docs/bitacora-auditoria.md`).

## Qué límites lleva cada uno

| Quién | `statement_timeout` | `lock_timeout` | `idle_in_transaction_session_timeout` | Cómo se aplica |
|---|---|---|---|---|
| Web (`sede_app`, contenedor `app`) | 5 s | 2 s | 15 s | `ALTER ROLE sede_app IN DATABASE … SET`, desde `RolesBaseDeDatos::aplicar()` con los valores de `config/database.php` (`limites.web`) |
| Planificador, Horizon y copia manual (`sede_app`) | 120 s | 5 s | 60 s | `PGOPTIONS` en `compose.yaml` (servicios `scheduler`, `horizon`, `copia`) |
| Dueño `sede` (migraciones) | sin límite | sin límite | sin límite | No se toca: una migración legítima puede tardar. Cada migración fija su propio `lock_timeout` de 3 s con `set local` |
| `sede_lectura` (copia nocturna con `pg_dump`) | sin límite | sin límite | sin límite | No se toca: un límite cortaría la copia a medias |

### Por qué estos valores (ESTIMACIONES, no mediciones de producción)

- **Medido en staging (2026-11-18, PostgreSQL 18.6):** la búsqueda tarda 30 ms con 123 trámites y cuesta unos 0,2 ms por fila (recorrido secuencial, CPU). Ninguna consulta de 1 s o más en el registro de PostgreSQL (`log_min_duration_statement=1000`), 0 deadlocks y 0 archivos temporales. Staging tiene datos de prueba: sirve de piso, no de garantía para producción.
- **Web 5 s:** unas 170 veces lo medido, unas 25 veces aunque el catálogo creciera 50 veces, y muy por debajo de los 60 s de nginx y PHP (`fastcgi_read_timeout`, `max_execution_time`) y de los 90 s de la cola (`retry_after`).
- **Procesos 120 s:** cubre el borrado diario de `consentimientos` cuando crezca (retención de 3 años) sin dejar una consulta colgada indefinidamente.
- **Inactivo en transacción 15 s / 60 s:** una transacción legítima no espera sin enviar nada. Hoy las `DB::transaction` del código solo hacen operaciones de base.

Si producción muestra que un valor corta trabajo legítimo, se cambia en `config/database.php` (web) o en `compose.yaml` (procesos) con su prueba, y se despliega: `aplicar()` lo deja vigente.

## Qué ve la ciudadanía

La API traduce dos errores de la base a **503 con `Retry-After: 5`** en el sobre de siempre (`success: false`):

| SQLSTATE | Significado |
|---|---|
| `57014` | La consulta pasó de `statement_timeout` |
| `55P03` | Un bloqueo no se soltó dentro de `lock_timeout` |

Es saturación pasajera, no un defecto de la petición. La excepción **se sigue reportando** en el registro: es la señal de que un límite se está tocando. Cualquier otro error de base sigue siendo 500. Fuera de `api/*` no se responde con el sobre. El contrato lo declara como `ServicioNoDisponible` en `contract/openapi.yaml`.

`idle_in_transaction_session_timeout` NO se traduce: PostgreSQL cierra la conexión y el error que llega a PHP depende del momento (pérdida de conexión). Se evita con la regla de abajo.

## Regla para quien escriba código

**Dentro de una `DB::transaction` no se sube un archivo, no se llama a un servicio externo ni se espera a nadie.** La transacción debe abrir, escribir y cerrar. Lo contrario deja una sesión inactiva con la transacción abierta, y a los 15 s la base la cierra y el trabajo se pierde. La mediateca (D8 a D10) toca archivos: sus transacciones deben cuidar esto.

## Comandos largos hechos a mano

Los comandos que se corren con `docker compose exec app php artisan …` heredan los límites ESTRICTOS de la web. Para uno que legítimamente tarde más (una reindexación, una importación), súbelos solo para ese comando, en el servidor:

```bash
sudo docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env exec -e PGOPTIONS="-c statement_timeout=120000" app php artisan <comando>
```

## Verificación

- `backend/tests/Feature/Operacion/LimitesPorRolTest.php`: el rol trae los límites (en esta base y no en todo el clúster), el dueño y la lectura no, y cada límite corta de verdad con su código (`57014`, `55P03`, conexión cerrada). `PGOPTIONS` lo respeta el controlador `pdo_pgsql` (comprobado). El compose lleva los valores de procesos en los tres servicios y no en `app`.
- `backend/tests/Feature/Api/V1/LimitesDeBaseRespuestaTest.php`: 503 con `Retry-After`, sin filtrar la consulta ni el nombre de la tabla; los demás errores de base siguen siendo 500.
- `backend/tests/Feature/Operacion/AgendaDeDepuracionTest.php`: la depuración de consentimientos corre a las 02:15 de Bogotá.

## Después de desplegar

`ALTER ROLE … SET` solo afecta a las sesiones NUEVAS. Los contenedores de la aplicación, de Horizon y del planificador toman los límites cuando se reinician (INFERENCIA: el despliegue los recrea; se comprueba en staging con la consulta de abajo y con `show statement_timeout` desde un proceso nuevo). Para comprobarlo en staging (solo lectura, en el servidor):

```bash
sudo docker compose -f /opt/sede/staging/compose.yaml --env-file /opt/sede/staging/.env exec -T postgres psql -U sede -d sede -c "select r.rolname, d.datname, s.setconfig from pg_db_role_setting s join pg_roles r on r.oid = s.setrole left join pg_database d on d.oid = s.setdatabase;"
```

Se espera una fila: `sede_app`, la base `sede` y los tres límites de la web.
