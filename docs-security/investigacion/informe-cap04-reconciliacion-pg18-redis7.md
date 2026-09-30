# Informe de reconciliación — Capítulo 04 (partes A y B)

**Fuentes (leídas completas):**
- `capitulo-04-parte-a.md` (458 líneas) → citado como `a:L`
- `capitulo-04-parte-b.md` (545 líneas) → citado como `b:L`

**Regla de este informe:** sólo se transcribe lo que los documentos dicen, con cita `archivo:línea`.
Cuando algo no aparece, se escribe **no consta**. Los bloques de configuración son literales.
Las secciones 9.3 y 9.5 marcan explícitamente lo que es **análisis mío** (no consta en el documento).

---

## 1. Selección de motor — criterio exacto (§4.1)

Título de la sección: `## 4.1 Selección del motor: MySQL 8 vs PostgreSQL 16` (`a:15`).

Criterio literal de la tabla (`a:17`–`a:25`):

| Criterio | MySQL 8.0 LTS | PostgreSQL 16 |
|---|---|---|
| Soporte LTS | Oracle hasta abril 2026 (Community Extensions después) [mysql.com/support, 2025-08] | PostgreSQL Global Dev Group, sin LTS formal; versiones mayores anuales [postgresql.org/support, 2025-09] |
| Cifrado autenticado por defecto | Sí (caching_sha2_password) | Sí (SCRAM-SHA-256) |
| Auditoría nativa | Enterprise Audit (pago) o plugin MariaDB Audit (community) | pgaudit (open source, mantenido por PostgreSQL) [github.com/pgaudit/pgaudit, 2025-09] |
| Row-level security | No nativo (vía views o app logic) | Sí, nativo y granular [postgresql.org/docs/16/ddl-rowsecurity.html, 2025-09] |
| Tipo favorito por Laravel 13 | Por defecto; Eloquent lo aprovecha nativamente | Soporte completo via `DB_CONNECTION=pgsql` |
| Procedimientos almacenados | SQL/PSM | PL/pgSQL (más rico) |
| Extensions vectoriales (pgvector, etc.) | No nativo | pgvector nativo |

Decisión recomendada, literal (`a:27`):

> **Decisión recomendada [A]:** MySQL 8.0 LTS si vienes de ecosistema LAMP o WordPress. PostgreSQL 16 si necesitas RLS, pgvector, o un sistema de tipos estricto. Para esta guía **documentamos ambas** porque el código de Laravel 13 es agnóstico (`DB_CONNECTION=mysql` o `pgsql`).

Stack objetivo declarado (`a:3`): «Ubuntu 24.04 LTS, MySQL 8.0 LTS (motor primario) o PostgreSQL 16 (alternativa), Redis 7.x. Cero exposición a Internet: bind solo en `127.0.0.1`, administración remota exclusivamente por túnel SSH.»

**Para qué caso recomienda cada uno:** MySQL 8.0 LTS como **motor primario** por defecto y para quien viene de LAMP/WordPress (`a:3`, `a:27`); PostgreSQL 16 como **alternativa** sólo si se necesitan RLS, pgvector o sistema de tipos estricto (`a:27`).

---

## 2. PostgreSQL — lo que dice el documento

### 2.1 Instalación y versión

- Versión: **16** (`a:1`, `a:3`, `a:15`, `a:305`, `a:336`, `a:361`, `b:443`).
- **No consta** un comando de instalación del servidor PostgreSQL (no hay `apt install postgresql-16`). El único `apt install` de PostgreSQL es el de la extensión de auditoría (`a:358`):

```bash
sudo apt install -y postgresql-16-pgaudit
```

- **No consta** el repositorio PGDG ni cómo obtener la versión 16 en Ubuntu 24.04.

### 2.2 `postgresql.conf` (valores literales)

Ruta declarada: `/etc/postgresql/16/main/postgresql.conf` (`a:305`).

```ini
# ================ Bind ================
listen_addresses = 'localhost'
port = 5432

# ================ Cifrado en tránsito ================
ssl = on
ssl_cert_file = '/etc/postgresql/16/main/server.crt'
ssl_key_file = '/etc/postgresql/16/main/server.key'
ssl_ca_file = '/etc/postgresql/16/main/ca.crt'
ssl_min_protocol_version = 'TLSv1.2'
ssl_ciphers = 'HIGH:!aNULL:!MD5'

# ================ Logging ================
logging_collector = on
log_directory = 'log'
log_filename = 'postgresql-%Y-%m-%d.log'
log_statement = 'mod'          # log solo DDL
log_min_duration_statement = 1000  # log queries > 1s
log_connections = on
log_disconnections = on

# ================ Recursos ================
max_connections = 200
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 16MB
```
(`a:307`–`a:334`)

Bloque adicional de pgaudit en el mismo archivo (`a:363`–`a:366`):

```ini
shared_preload_libraries = 'pgaudit'
pgaudit.log = 'ddl, role, write'
```

### 2.3 `pg_hba.conf` (literal)

Ruta declarada: `/etc/postgresql/16/main/pg_hba.conf` (`a:336`). Contenido literal (`a:338`–`a:345`):

```
# TYPE  DATABASE        USER            ADDRESS         METHOD
local   all             postgres                        peer
local   all             all                             peer
host    all             all             127.0.0.1/32    scram-sha-256
host    all             all             ::1/128         scram-sha-256
# NO permitir host sin TLS. NO usar "trust" ni "md5" (deprecated).
```

### 2.4 Método de autenticación

- `scram-sha-256` para las conexiones `host` desde `127.0.0.1/32` y `::1/128`; `peer` para las locales (`a:342`–`a:343`).
- Se declara como característica del motor: «Cifrado autenticado por defecto … Sí (SCRAM-SHA-256)» (`a:20`).
- **No consta** `password_encryption = 'scram-sha-256'` en `postgresql.conf`.
- **No consta** la creación del usuario con `IDENTIFIED`/`SCRAM` explícito: el único `CREATE USER` es `CREATE USER laravel_app WITH PASSWORD 'STRONG_PASSWORD';` (`a:350`).

### 2.5 SSL obligatorio

- Parámetros: `ssl = on`, certificados y `ssl_min_protocol_version = 'TLSv1.2'`, `ssl_ciphers = 'HIGH:!aNULL:!MD5'` (`a:313`–`a:318`).
- El comentario de `pg_hba.conf` afirma «NO permitir host sin TLS» (`a:344`), pero **no consta** ninguna línea `hostssl` ni `hostnossl` que lo imponga.
- Del lado del cliente Laravel: `PGSSLMODE=require` (`a:455`).
- **No consta** generación de certificados para PostgreSQL (el único procedimiento de generación de certificados del capítulo es el de Redis, `b:120`–`b:131`).

### 2.6 pgaudit

- Instalación: `sudo apt install -y postgresql-16-pgaudit` (`a:358`).
- Configuración: `shared_preload_libraries = 'pgaudit'` + `pgaudit.log = 'ddl, role, write'` (`a:364`–`a:365`).
- Activación (`a:370`–`a:372`):

```bash
sudo systemctl restart postgresql
sudo -u postgres psql -c "CREATE EXTENSION IF NOT EXISTS pgaudit;"
```

- **No consta** destino del log de auditoría, rotación ni retención de pgaudit.

### 2.7 systemd hardening de la unidad PostgreSQL

**No consta.** El capítulo aporta unidades endurecidas sólo para **MySQL** (`a:58`–`a:77`) y **Redis** (`b:30`–`b:44`). Para PostgreSQL el único uso de systemd es `sudo systemctl restart postgresql` (`a:371`).

### 2.8 Cuentas y privilegios

Comandos literales (`a:349`–`a:353`):

```bash
sudo -u postgres psql -c "CREATE USER laravel_app WITH PASSWORD 'STRONG_PASSWORD';"
sudo -u postgres psql -c "CREATE DATABASE laravel_db OWNER laravel_app;"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE laravel_db TO laravel_app;"
```

- **No consta** separación de roles para PostgreSQL (no hay `laravel_migrate`, `bi_reader`, `backup_user` ni `exporter` con sentencias SQL en PG). El usuario de respaldo `backup_user` sí se usa en el `pg_dump` (`b:329`), pero su creación y privilegios **no constan**.
- **No consta** `REVOKE` ni permisos a nivel de tabla/esquema (`GRANT ... ON ALL TABLES IN SCHEMA`).
- La única regla general de menor privilegio del capítulo es la de MySQL (§4.4, `a:182`–`a:210`) y el diagrama MySQL (`b:486`–`b:507`).

### 2.9 Cifrado en reposo y en tránsito (PostgreSQL)

- En tránsito: `ssl = on` + TLSv1.2+ (`a:313`–`a:318`); cliente `PGSSLMODE=require` (`a:455`).
- En reposo: la tabla resumen afirma «InnoDB tablespace encryption / pgcrypto» (`b:520`), pero **el capítulo nunca configura `pgcrypto`**: no hay `CREATE EXTENSION pgcrypto`, ni `pgcrypto` en `shared_preload_libraries`, ni uso de `pgp_sym_encrypt`. La verificación que acompaña esa fila es de MySQL: `SELECT name, encryption FROM information_schema.tables;` (`b:520`).
- LUKS (§4.7) se plantea sólo para el volumen de datos de MySQL: «Si usas un volumen separado de DigitalOcean (`/var/lib/mysql`)…» (`a:295`).

### 2.10 Si el documento NO cubre PostgreSQL en profundidad — qué falta

El propio documento lo sitúa como alternativa: «## 4.8 PostgreSQL 16 — alternativa», «Si eliges PostgreSQL en lugar de MySQL, los principios son idénticos» (`a:301`, `a:303`). Frente a las ~265 líneas dedicadas a MySQL (`a:31`–`a:297`), PostgreSQL recibe `a:301`–`a:373`.

**Falta explícitamente (no consta):**
1. Instalación del servidor y obtención de la versión (repo PGDG o equivalente).
2. `systemd` hardening de `postgresql.service`.
3. Endurecimiento del sistema operativo del clúster (permisos de `data/`, `pg_hba` externo, `pg_ident.conf`).
4. Creación de certificados TLS y su rotación.
5. `password_encryption`, `scram_iterations`, caducidad de contraseñas.
6. Roles/usuarios de menor privilegio (app, migraciones, BI, backup, exporter) con sus GRANT/REVOKE por esquema y tabla.
7. Cifrado en reposo real (nada de pgcrypto operativo, ni tablespace cifrado, ni LUKS para el directorio de datos de PG).
8. Copias de seguridad específicas de PG más allá del comando `pg_dump` (no hay script `/usr/local/bin/backup-postgres.sh`, aunque el cron lo invoca: `b:360`).
9. Drill de restauración de PostgreSQL (el drill documentado es sólo MySQL: `b:405`–`b:427`).
10. Monitoreo específico de PG (exporters sí: `b:457`; configuración del exporter PostgreSQL **no consta**; sólo se configura el de MySQL, `b:463`–`b:475`).
11. Conexión de Laravel por socket Unix o pooling (`pgbouncer`) — **no consta**.
12. RLS, pgvector: se citan como motivo de elección (`a:22`, `a:25`) pero **no** se documenta su uso.

---

## 3. MySQL 8 — resumen de lo que sí define

### 3.1 Instalación y `mysql_secure_installation`

```bash
# Ubuntu 24.04 incluye mysql-server-8.0 en universe
sudo apt install -y mysql-server-8.0
mysql --version
# mysql  Ver 8.0.x for Linux on x86_64 (Source distribution)
```
(`a:35`–`a:40`)

`sudo mysql_secure_installation` (`a:53`) con el asistente descrito en `a:44`–`a:50`: contraseña de `root@localhost` (usa **auth_socket** en Ubuntu 24.04, `a:46`), elimina usuarios anónimos, deshabilita root remoto, elimina la base `test`, recarga privilegios.

### 3.2 `my.cnf` endurecido (fragmento `/etc/mysql/mysql.conf.d/hardening.cnf`, literal)

```ini
[mysqld]
# ================ Bind ================
bind-address = 127.0.0.1
mysqlx-bind-address = 127.0.0.1

# ================ Red ================
skip-networking                 # NO aceptar TCP; solo socket Unix
# Si necesitas TCP (para túnel SSH sigue funcionando vía 127.0.0.1):
# skip-networking
# bind-address = 127.0.0.1
skip-name-resolve
max_connections = 200

# ================ Local file ================
local-infile = 0                # bloquea LOAD DATA LOCAL INFILE (vector de exfil)

# ================ Cifrado en tránsito ================
ssl-ca = /etc/mysql/certs/ca.pem
ssl-cert = /etc/mysql/certs/server-cert.pem
ssl-key = /etc/mysql/certs/server-key.pem
require_secure_transport = ON   # rechaza conexiones no TLS

# ================ Cifrado en reposo ================
# InnoDB tablespace encryption (requiere keyring)
early-plugin-load = keyring_file.so
keyring_file_data = /var/lib/mysql-keyring/keyring

# ================ Logging ================
log_error = /var/log/mysql/error.log
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2
log_warnings = 2

# ================ Auditoría ================
# Plugin MariaDB Audit (community, gratis, binario)
plugin-load-add = server_audit.so
server_audit_events = CONNECT,QUERY_DDL,QUERY_DML
server_audit_logging = ON
server_audit_output_type = FILE
server_audit_file_path = /var/log/mysql/audit.log
server_audit_file_rotate_size = 1000000
server_audit_file_rotations = 10
```
(`a:91`–`a:137`)

**Decisión sobre `skip-networking` [A]** (literal, `a:149`): «lo dejo recomendado porque la única vía legítima al MySQL desde fuera del droplet es el túnel SSH. Si activas `skip-networking`, MySQL solo escucha en socket Unix y no hay manera de saltarse el túnel.»

**Validación** (`a:141`–`a:146`):

```bash
sudo systemctl restart mysql
ss -tlnp | grep 3306   # NO debe devolver nada si skip-networking está ON
sudo mysql -e "SHOW VARIABLES LIKE 'have_ssl';"
sudo mysql -e "SHOW VARIABLES LIKE 'require_secure_transport';"
# Si todo va bien: have_ssl=YES, require_secure_transport=ON
```

### 3.3 `caching_sha2_password` y comandos de cuentas

- Justificación: «MySQL 8 cambió el plugin por defecto a `caching_sha2_password`, que **no es vulnerable al handshake SHA1**…» (`a:155`).
- Comprobación y migración (`a:159`–`a:165`):

```sql
SELECT user, host, plugin FROM mysql.user WHERE plugin = 'mysql_native_password';
-- Si hay usuarios con mysql_native_password, migrarlos:

ALTER USER 'app_user'@'localhost' IDENTIFIED WITH caching_sha2_password BY 'STRONG_RANDOM_PASSWORD';
FLUSH PRIVILEGES;
```

- Generación de contraseña: `openssl rand -base64 48` (64 caracteres) (`a:167`–`a:171`).
- Verificación (`a:175`–`a:177`):

```sql
SELECT user, host, plugin FROM mysql.user;
-- Todos deben tener caching_sha2_password (excepto root@localhost que usa auth_socket en Ubuntu 24.04)
```

### 3.4 Usuarios y privilegios exactos (§4.4, literal)

```sql
-- Usuario de aplicación (solo desde localhost)
CREATE USER 'laravel_app'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY 'STRONG_PASSWORD';
-- Identificado por la app, NO por el usuario humano

-- Permisos: solo lo que la app necesita en runtime
GRANT SELECT, INSERT, UPDATE, DELETE ON laravel_db.* TO 'laravel_app'@'127.0.0.1';

-- Usuario de migraciones (solo desde localhost, solo durante deploy)
CREATE USER 'laravel_migrate'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY 'MIGRATE_PASSWORD';
GRANT ALL PRIVILEGES ON laravel_db.* TO 'laravel_migrate'@'127.0.0.1';

-- Usuario de lectura para BI/dashboards (opcional)
CREATE USER 'bi_reader'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY 'READER_PASSWORD';
GRANT SELECT ON laravel_db.* TO 'bi_reader'@'127.0.0.1';

-- Refrescar
FLUSH PRIVILEGES;
```
(`a:184`–`a:202`)

Razones (`a:204`–`a:210`): `laravel_app` no debe poder `DROP TABLE` ni `ALTER TABLE`; `laravel_migrate` sólo durante `php artisan migrate` en deploy; `bi_reader` para Looker/Metabase/Tableau. «**Rotación trimestral de contraseñas** (ver Capítulo 5): política explícita + recordatorio en el runbook.» (`a:210`)

Usuarios adicionales (privilegios **sólo** declarados en el diagrama, sin `CREATE USER` en el capítulo) (`b:498`, `b:500`):

```
backup_user@127.0.0.1   SELECT, LOCK TABLES, RELOAD, EVENT   (cron backups)
exporter@127.0.0.1      PROCESS, REPLICATION CLIENT, SELECT  (Prometheus)
```

El usuario `exporter` se menciona además como regla: «Crear usuario `exporter` con permisos mínimos (`PROCESS`, `REPLICATION CLIENT`, `SELECT`)» (`b:480`).

### 3.5 InnoDB Tablespace Encryption y keyring

- «Con el plugin `keyring_file`, MySQL cifra los tablespaces con AES-256 (modo CBC + ECD).» (`a:249`)
- Directorio de keys (`a:253`–`a:257`):

```bash
sudo mkdir -p /var/lib/mysql-keyring
sudo chown mysql:mysql /var/lib/mysql-keyring
sudo chmod 700 /var/lib/mysql-keyring
```

- Cifrar tabla existente: `ALTER TABLE users ENCRYPTION='Y';` (`a:262`)
- Crear tabla cifrada desde cero (`a:268`–`a:273`):

```sql
CREATE TABLE audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event VARCHAR(64) NOT NULL,
    payload JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENCRYPTION='Y';
```

- Verificación (`a:279`–`a:280`):

```sql
SELECT name, encryption FROM information_schema.tables WHERE table_schema='laravel_db';
-- encryption = 'Y' para las que están cifradas
```

- Rotación de la master key: `ALTER INSTANCE ROTATE INNODB MASTER KEY;` (`a:286`)
- Custodia (`a:289`): «el archivo `/var/lib/mysql-keyring/keyring` contiene la master key. **Debe respaldarse en un lugar seguro y separado del droplet** (Vault, DO Spaces con KMS, HSM). Si pierdes esta key, pierdes los datos cifrados.»
- **No consta** periodicidad explícita de rotación de la master key; sólo la mención general «Rotación | Trimestral de contraseñas, claves SSH, master keys | Runbook Capítulo 5» (`b:524`).

### 3.6 Cifrado en tránsito (MySQL)

`require_secure_transport = ON` (`a:114`). Verificación (`a:218`–`a:224`, `a:228`–`a:243`):

```sql
SHOW VARIABLES LIKE 'require_secure_transport';
-- require_secure_transport = ON

SHOW VARIABLES LIKE 'ssl_%';
-- ssl_ca, ssl_cert, ssl_key deben apuntar a archivos existentes y válidos
```

```bash
mysql -h 127.0.0.1 -u laravel_app -p \
    --ssl-ca=/etc/mysql/certs/ca.pem \
    --ssl-mode=REQUIRED \
    -e "SHOW STATUS LIKE 'Ssl_cipher';"
# Debe devolver: Ssl_cipher = TLS_AES_256_GCM_SHA384 o similar TLS 1.3
```

```bash
mysql -h 127.0.0.1 -u laravel_app -p
# ERROR 1045 (28000): Access denied for user 'laravel_app'@'localhost' (using password: YES)
# O
# ERROR 3159 (HY000): Connections using insecure transport are prohibited while --require_secure_transport=ON.
```

**No consta** generación de los certificados de MySQL (`/etc/mysql/certs/*`): el único procedimiento de generación es el de Redis (`b:120`–`b:131`).

### 3.7 systemd unit hardening (MySQL, literal)

`/etc/systemd/system/mysql.service.d/override.conf` (`a:58`–`a:77`):

```ini
[Service]
ProtectSystem=strict
ProtectHome=yes
PrivateTmp=yes
NoNewPrivileges=yes
ProtectKernelTunables=yes
ProtectKernelModules=yes
ReadWritePaths=/var/lib/mysql /var/run/mysqld /var/log/mysql
PrivateDevices=yes
ProtectControlGroups=yes
RestrictAddressFamilies=AF_UNIX AF_INET AF_INET6
RestrictNamespaces=yes
RestrictRealtime=yes
MemoryDenyWriteExecute=yes
CapabilityBoundingSet=
AmbientCapabilities=
```

Aplicación y verificación (`a:83`–`a:87`):

```bash
sudo systemctl daemon-reload
sudo systemctl restart mysql
sudo systemctl show mysql | grep -E "ProtectSystem|NoNewPrivileges"
```

---

## 4. Redis 7

### 4.1 Instalación y versión

```bash
curl -fsSL https://packages.redis.io/gpg | sudo gpg --dearmor -o /usr/share/keyrings/redis-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/redis-archive-keyring.gpg] https://packages.redis.io/deb $(lsb_release -cs) main" | \
    sudo tee /etc/apt/sources.list.d/redis.list
sudo apt update
sudo apt install -y redis
redis-server --version   # Redis server v=7.x.x
```
(`b:11`–`b:18`). Versión declarada: **Redis 7.x** y `v=7.x.x` (`a:3`, `b:17`); **no consta** la versión menor exacta.

### 4.2 systemd unit hardening (literal, `b:30`–`b:44`)

Se aplica con `sudo systemctl edit redis` (`b:25`) y se añade:

```ini
[Service]
ProtectSystem=strict
ProtectHome=yes
PrivateTmp=yes
NoNewPrivileges=yes
ProtectKernelTunables=yes
ReadWritePaths=/var/lib/redis /var/log/redis /var/run/redis
PrivateDevices=yes
CapabilityBoundingSet=
AmbientCapabilities=
RestrictAddressFamilies=AF_UNIX AF_INET AF_INET6
RestrictNamespaces=yes
MemoryDenyWriteExecute=yes
```

Aplicar (`b:48`–`b:51`):

```bash
sudo systemctl daemon-reload
sudo systemctl restart redis
```

### 4.3 `redis.conf` endurecido (literal, `b:57`–`b:102`)

```ini
# ================ Bind ================
# NUNCA 0.0.0.0
bind 127.0.0.1 ::1

# ================ Protected mode ================
protected-mode yes

# ================ Puerto ================
port 6379
# Si quieres TLS, usa tls-port (ver 4.11.4)

# ================ Autenticación ================
# Contraseña de 64+ caracteres generada con openssl
requirepass $(openssl rand -base64 48)
# ↑ Sustituir en runtime por una variable gestionada con Ansible/Vault

# ================ Deshabilitar comandos peligrosos ================
rename-command FLUSHDB ""
rename-command FLUSHALL ""
rename-command CONFIG "CONFIG_RENAMED_TO_AVOID_RECON"
rename-command DEBUG ""
rename-command SHUTDOWN "SHUTDOWN_OPERATOR_ONLY"
rename-command KEYS ""
# ↑ Vacío = bloqueado. Recomendado por [redis.io/docs/latest/operate/oss_and_stack/management/security/, 2025-09].

# ================ ACL por usuario ================
aclfile /etc/redis/users.acl

# ================ Persistencia ================
dir /var/lib/redis
dbfilename dump.rdb
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec

# ================ Límites ================
maxmemory 2gb
maxmemory-policy allkeys-lru

# ================ Logging ================
loglevel notice
logfile /var/log/redis/redis-server.log
slowlog-log-slower-than 10000
slowlog-max-len 128
```

**Comandos exactos renombrados/bloqueados** (`b:75`–`b:80`): `FLUSHDB` → `""` (bloqueado), `FLUSHALL` → `""`, `CONFIG` → `"CONFIG_RENAMED_TO_AVOID_RECON"`, `DEBUG` → `""`, `SHUTDOWN` → `"SHUTDOWN_OPERATOR_ONLY"`, `KEYS` → `""`.

### 4.4 TLS en Redis

`/etc/redis/redis.conf` (`b:108`–`b:116`):

```ini
tls-port 6380
tls-cert-file /etc/redis/certs/redis-server.crt
tls-key-file /etc/redis/certs/redis-server.key
tls-ca-cert-file /etc/redis/certs/ca.pem
tls-auth-clients yes
tls-protocols "TLSv1.2 TLSv1.3"
tls-ciphers HIGH:!aNULL:!MD5
```

Generación de certificados autofirmados (`b:120`–`b:131`):

```bash
sudo mkdir -p /etc/redis/certs
cd /etc/redis/certs
sudo openssl req -x509 -nodes -newkey rsa:2048 \
    -keyout redis-server.key \
    -out redis-server.crt \
    -days 3650 \
    -subj "/CN=redis.dominio.tld"
sudo cp redis-server.crt ca.pem
sudo chown redis:redis /etc/redis/certs/*
sudo chmod 600 /etc/redis/certs/*.key
```

Puerto TLS: **6380** (`b:109`). Certificados: `redis-server.crt`, `redis-server.key`, `ca.pem` en `/etc/redis/certs/` (`b:110`–`b:112`). **No consta** generación de certificado de cliente.

### 4.5 `users.acl` (literal, `b:135`–`b:144`)

```redis
# Usuario admin (para debugging con redis-cli)
user admin on 'STRONG_ADMIN_PASSWORD' ~* &* +@all

# Usuario app (solo lo que Laravel necesita)
user laravel_app on 'STRONG_APP_PASSWORD' ~laravel_cache:* ~laravel_session:* ~laravel_queue:* &* +@read +@write +@connection -@admin -@dangerous -@keyspace -@scripting

# Aplicar
ACL SAVE
```

Ruta del archivo: `/etc/redis/users.acl` (`b:84`, `b:133`). **No consta** usuario `default` deshabilitado (`user default off`) ni `masterauth`.

### 4.6 Verificación post-instalación de Redis (`b:152`–`b:174`)

```bash
# 1. ¿Quién escucha?
sudo ss -tlnp | grep redis-server
# Debe mostrar solo 127.0.0.1:6379 (y 6380 si TLS)

# 2. ¿Está activa la autenticación?
redis-cli -h 127.0.0.1 -p 6379 PING
# (sin AUTH) → (error) NOAUTH Authentication required.

# 3. ¿Comandos peligrosos están deshabilitados?
redis-cli -h 127.0.0.1 -p 6379 -a 'STRONG_APP_PASSWORD' FLUSHDB
# (error) ERR unknown command 'FLUSHDB'

# 4. ¿TLS funciona?
redis-cli -h 127.0.0.1 -p 6380 --tls \
    --cacert /etc/redis/certs/ca.pem \
    -a 'STRONG_APP_PASSWORD' PING
# PONG

# 5. ¿Protected mode rechaza intentos remotos?
redis-cli -h <IP_PUBLICA_DEL_DROPLET> -p 6379 PING
# (debe fallar: connection refused porque no escucha en 0.0.0.0)
```

---

## 5. Túnel SSH — procedimiento exacto

### 5.1 Túneles por línea de comandos (`b:182`–`b:191`)

```bash
# MySQL Workbench en localhost:13306 → droplet → 127.0.0.1:3306
ssh -L 13306:127.0.0.1:3306 deployer@DROPLET_IP

# PostgreSQL pgAdmin en localhost:15432 → droplet → 127.0.0.1:5432
ssh -L 15432:127.0.0.1:5432 deployer@DROPLET_IP

# Redis Desktop Manager en localhost:16379 → droplet → 127.0.0.1:6380
ssh -L 16379:127.0.0.1:6380 deployer@DROPLET_IP
```

### 5.2 `~/.ssh/config` persistente (`b:195`–`b:212`)

```sshconfig
Host droplet-prod
    HostName <IP_PUBLICA>
    User deployer
    Port 22
    IdentityFile ~/.ssh/droplet-prod-ed25519
    IdentitiesOnly yes
    ForwardAgent no

    # Túneles automáticos para administración
    LocalForward 13306 127.0.0.1:3306
    LocalForward 15432 127.0.0.1:5432
    LocalForward 16379 127.0.0.1:6380

    # Keep-alive
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

«Ahora basta `ssh droplet-prod` para tener los tres túneles abiertos automáticamente.» (`b:214`)

### 5.3 Comprobación (`b:218`–`b:232`)

```bash
# Conectar el túnel en background
ssh -fN droplet-prod
# -f → background
# -N → no shell remoto

# Verificar puertos locales
ss -tlnp | grep -E "13306|15432|16379"
# Deben estar escuchando en 127.0.0.1

# Probar MySQL
mysql -h 127.0.0.1 -P 13306 -u laravel_app -p \
    --ssl-mode=DISABLED  # porque ya va cifrado por SSH
    -e "SELECT VERSION();"
```

**Nota [A]** (literal, `b:234`): «cuando usas túnel SSH, desactiva el TLS del motor de BD (porque ya va cifrado). Actívalo solo si sales del host sin SSH. Esto simplifica la cadena de cifrado y reduce CPU.»

### 5.4 Diagrama (§4.9, `a:379`–`a:398`) y su lectura

Participantes: Operador (Workbench), ssh client, Droplet (sshd), mysqld (127.0.0.1:3306). El operador conecta a `localhost:13306`; SSH reenvía a `127.0.0.1:3306`; el diagrama muestra `My->>D: TLS handshake (caching_sha2_password + ssl-mode=REQUIRED)` (`a:394`).

Lectura literal (`a:400`): «la clave SSH + 2FA ya validaron al operador. El túnel SSH cifra toda la sesión con ChaCha20-Poly1305. MySQL además exige TLS. Son **tres capas de cifrado independientes** sobre el mismo canal (SSH + TLS-MySQL + autenticación SHA2).»

---

## 6. Copias de seguridad

### 6.1 Qué se copia, con qué comando, cómo se cifra, dónde se guarda

**MySQL — `mysqldump` + `gpg`** (`b:302`–`b:316`):

```bash
mysqldump \
    --user=backup_user \
    --password='BACKUP_PASSWORD' \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    laravel_db | gzip | \
    gpg --batch --yes --symmetric \
        --cipher-algo AES256 \
        --compress-algo zlib \
        --output /var/backups/mysql/laravel-$(date +%Y%m%d-%H%M%S).sql.gz.gpg
```

Permisos (`b:318`–`b:322`):

```bash
sudo chown backup:backup /var/backups/mysql -R
sudo chmod 700 /var/backups/mysql
```

**PostgreSQL — `pg_dump` + `gpg`** (`b:327`–`b:337`):

```bash
pg_dump \
    --username=backup_user \
    --format=custom \
    --no-owner \
    --no-acl \
    laravel_db | \
    gpg --batch --yes --symmetric \
        --cipher-algo AES256 \
        --output /var/backups/postgres/laravel-$(date +%Y%m%d-%H%M%S).sqlc.gpg
```

**Redis — persistencia nativa + copia** (`b:339`–`b:349`):

```bash
# RDB snapshot automático (configurado en redis.conf save)
cp /var/lib/redis/dump.rdb /var/backups/redis/redis-$(date +%Y%m%d-%H%M%S).rdb
gpg --batch --yes --symmetric --cipher-algo AES256 \
    --output /var/backups/redis/redis-$(date +%Y%m%d-%H%M%S).rdb.gpg \
    /var/backups/redis/redis-$(date +%Y%m%d-%H%M%S).rdb
```

**Algoritmo de cifrado:** AES256 simétrico (`--cipher-algo AES256`) en todos los casos (`b:313`, `b:335`, `b:346`, `b:391`). En MySQL además `--compress-algo zlib` (`b:314`). **No consta** uso de clave asimétrica, ni `--recipient`, ni sellado de tiempo.

**Dónde se guarda:** `/var/backups/mysql/`, `/var/backups/postgres/`, `/var/backups/redis/` (`b:315`, `b:336`, `b:345`) y `/var/backups/monthly/` para el movimiento mensual (`b:370`–`b:371`). **No consta** copia fuera del droplet (off-site) de los propios ficheros; lo único que se manda fuera es la **clave** (ver 6.4).

### 6.2 Script de respaldo (`b:374`–`b:397`, literal)

```bash
#!/bin/bash
set -euo pipefail
TS=$(date +%Y%m%d-%H%M%S)
TMPDIR=$(mktemp -d)
trap "rm -rf $TMPDIR" EXIT

mysqldump \
    --user=backup_user \
    --password="${MYSQL_BACKUP_PASSWORD}" \
    --single-transaction \
    --routines --triggers --events --hex-blob \
    laravel_db > "$TMPDIR/dump.sql"

gpg --batch --yes --symmetric \
    --cipher-algo AES256 \
    --passphrase-file /etc/dropbear/backup-gpg-passphrase \
    --output /var/backups/mysql/laravel-$TS.sql.gz.gpg \
    < "$TMPDIR/dump.sql"

logger -t backup-mysql "Backup completado: laravel-$TS.sql.gz.gpg"
```

```bash
sudo chmod +x /usr/local/bin/backup-mysql.sh
```
(`b:399`–`b:401`)

### 6.3 Rotación exacta y cómo se implementa (`b:351`–`b:372`, literal)

`/etc/cron.d/db-backups`:

```cron
# MySQL — diario a las 03:00, rotación 7/30/365
0 3 * * * backup /usr/local/bin/backup-mysql.sh

# PostgreSQL — diario a las 03:30, rotación 7/30/365
30 3 * * * backup /usr/local/bin/backup-postgres.sh

# Redis — cada 6 horas
0 */6 * * * backup /usr/local/bin/backup-redis.sh

# Limpieza: borrar > 7 días
0 4 * * * backup find /var/backups/mysql -name "*.gpg" -mtime +7 -delete
0 4 * * * backup find /var/backups/postgres -name "*.gpg" -mtime +7 -delete

# Mensual: mover a /var/backups/monthly/
0 5 1 * * backup mv /var/backups/mysql/*.gpg /var/backups/monthly/ 2>/dev/null || true
0 5 1 * * backup mv /var/backups/postgres/*.gpg /var/backups/monthly/ 2>/dev/null || true
```

Rotación declarada: **7/30/365** en los comentarios de las entradas de MySQL y PostgreSQL (`b:356`, `b:359`); **implementada realmente** sólo la purga de >7 días (`b:366`–`b:367`) y el movimiento mensual a `/var/backups/monthly/` (`b:370`–`b:371`). Redis: cada 6 horas sin regla de limpieza (`b:363`).

**No consta** script `/usr/local/bin/backup-postgres.sh` ni `/usr/local/bin/backup-redis.sh` (se invocan en el cron, `b:360`, `b:363`, pero sólo se transcribe el de MySQL, `b:374`). **No consta** purga de `/var/backups/monthly/` ni reglas de 30/365 días. **No consta** subida a almacenamiento externo (restic, rclone, DO Spaces) ni verificación de integridad `gpg --list-packets` automatizada (sólo aparece como método de verificación en la tabla, `b:523`).

### 6.4 Drill de restauración — periodicidad y procedimiento

Periodicidad: **mensual** — «### 4.15.5 Drill de restore (mensual)» (`b:403`) y «**Regla:** si el drill falla, **no es un backup**, es una esperanza. El drill mensual es lo que cuenta.» (`b:429`).

Procedimiento literal (`b:405`–`b:427`):

```bash
# Probar descifrar y restaurar
TS=20250915-030000
TMPDIR=$(mktemp -d)

# Descifrar
gpg --batch --yes --decrypt \
    --passphrase-file /etc/dropbear/backup-gpg-passphrase \
    --output $TMPDIR/dump.sql \
    /var/backups/mysql/laravel-$TS.sql.gz.gpg

# Restaurar en una BD de prueba
mysql -h 127.0.0.1 -u laravel_migrate -p -e "CREATE DATABASE IF NOT EXISTS laravel_restore_test;"
mysql -h 127.0.0.1 -u laravel_migrate -p laravel_restore_test < $TMPDIR/dump.sql

# Verificar
mysql -h 127.0.0.1 -u laravel_app -p laravel_restore_test \
    -e "SELECT COUNT(*) FROM users; SELECT MAX(created_at) FROM audit_log;"

# Limpiar
mysql -h 127.0.0.1 -u laravel_migrate -p -e "DROP DATABASE laravel_restore_test;"
rm -rf $TMPDIR
```

**No consta** automatización del drill (no hay entrada de cron para él) ni drill equivalente para PostgreSQL/Redis.

### 6.5 Custodia de la clave de cifrado

Reglas que sí constan:

1. `--passphrase-file /etc/dropbear/backup-gpg-passphrase` como fuente de la passphrase de gpg (`b:392`, `b:412`).
2. Tabla resumen: «Backups cifrados | AES-256 **con clave separada del droplet** | `gpg --list-packets backup.gpg` muestra cipher-algo» (`b:523`).
3. Master key de InnoDB: «**Debe respaldarse en un lugar seguro y separado del droplet** (Vault, DO Spaces con KMS, HSM). Si pierdes esta key, pierdes los datos cifrados.» (`a:289`).

**No consta** procedimiento de custodia para la passphrase de gpg (quién la guarda, dónde, cómo se rota), ni rotación de esa clave; sólo la mención general «Rotación | Trimestral de contraseñas, claves SSH, master keys» (`b:524`).

### 6.6 restic, DO Spaces, WORM

- **restic:** no consta (no aparece en ninguno de los dos archivos).
- **WORM / almacenamiento inmutable / object lock:** no consta.
- **DO Spaces:** aparece **una sola vez** en el capítulo, y sólo como posible destino de custodia de la master key de InnoDB: «(Vault, DO Spaces con KMS, HSM)» (`a:289`). **No** se usa DO Spaces como destino de los backups. Lo más cercano a almacenamiento externo es la afirmación «Redis AOF y RDB ya están cifrados en reposo por LUKS o DO Volume» (`b:341`) y el párrafo sobre DigitalOcean Volumes: «DigitalOcean Volumes ya están cifrados **at-rest** con AES-256-XTS por defecto» (`a:295`).

---

## 7. Monitoreo y verificación

### 7.1 Verificación post-instalación — comandos y resultado esperado

**Redis** (`b:152`–`b:174`): ver §4.6 de este informe.
- `sudo ss -tlnp | grep redis-server` → «Debe mostrar solo 127.0.0.1:6379 (y 6380 si TLS)» (`b:154`–`b:155`).
- `redis-cli -h 127.0.0.1 -p 6379 PING` sin AUTH → «(error) NOAUTH Authentication required.» (`b:158`–`b:159`).
- `redis-cli ... FLUSHDB` → «(error) ERR unknown command 'FLUSHDB'» (`b:162`–`b:163`).
- `redis-cli -h 127.0.0.1 -p 6380 --tls --cacert ... PING` → «PONG» (`b:166`–`b:169`).
- `redis-cli -h <IP_PUBLICA_DEL_DROPLET> -p 6379 PING` → «(debe fallar: connection refused porque no escucha en 0.0.0.0)» (`b:172`–`b:173`).

**MySQL** (`a:141`–`a:146`, `a:218`–`a:243`):
- `ss -tlnp | grep 3306` → «NO debe devolver nada si skip-networking está ON» (`a:143`).
- `SHOW VARIABLES LIKE 'have_ssl'` / `require_secure_transport` → «have_ssl=YES, require_secure_transport=ON» (`a:146`).
- `SHOW STATUS LIKE 'Ssl_cipher'` → «TLS_AES_256_GCM_SHA384 o similar TLS 1.3» (`a:233`).
- Conexión sin TLS → error 1045 o 3159 (`a:240`–`a:242`).
- `SELECT user, host, plugin FROM mysql.user;` → «Todos deben tener caching_sha2_password (excepto root@localhost que usa auth_socket…)» (`a:177`).
- `SELECT name, encryption FROM information_schema.tables WHERE table_schema='laravel_db';` → «encryption = 'Y' para las que están cifradas» (`a:279`–`a:280`).

**Checklist final del capítulo** (`b:529`–`b:543`, literal):

```bash
# Validación completa
sudo ss -tlnp | grep -E '3306|5432|6379|6380'
# Esperado: solo 127.0.0.1:3306 (MySQL) y 127.0.0.1:6379 (Redis)
# Si tienes PostgreSQL: 127.0.0.1:5432
# Si tienes TLS en Redis: 127.0.0.1:6380

# Cero bind a 0.0.0.0 o ::
sudo ss -tlnp | grep -E '0.0.0.0:3306|:::3306|0.0.0.0:6379|:::6379|0.0.0.0:5432|:::5432'
# Esperado: vacío

# TLS obligatorio
mysql -h 127.0.0.1 -u laravel_app -p --ssl-mode=DISABLED -e "SELECT 1"
# Esperado: error de insecure transport
```

### 7.2 Tabla resumen de garantías y verificación (§4.18, `b:515`–`b:525`)

| Capa | Garantía | Cómo se verifica |
|---|---|---|
| Red | MySQL/PG/Redis NO escucha en IP pública | `ss -tlnp \| grep -E '3306\|5432\|6379'` muestra solo `127.0.0.1` |
| Autenticación | Solo `caching_sha2_password` o `scram-sha-256` | `SELECT user, plugin FROM mysql.user;` |
| Cifrado en tránsito | TLS obligatorio | Conexión sin `--ssl-mode=REQUIRED` falla |
| Cifrado en reposo | InnoDB tablespace encryption / pgcrypto | `SELECT name, encryption FROM information_schema.tables;` |
| Menor privilegio | 5 usuarios separados (app, migrate, bi, backup, exporter) | `SHOW GRANTS FOR 'laravel_app'@'127.0.0.1';` |
| Túnel SSH | Única vía legítima para administrar | `ss -tlnp` no muestra puertos públicos |
| Backups cifrados | AES-256 con clave separada del droplet | `gpg --list-packets backup.gpg` muestra cipher-algo |
| Rotación | Trimestral de contraseñas, claves SSH, master keys | Runbook Capítulo 5 |
| Monitoreo | Logs + exporters Prometheus | `tail -f /var/log/mysql/error.log` y `:9104/metrics` |

### 7.3 Logs nativos (`b:437`–`b:448`)

```bash
# MySQL
sudo tail -f /var/log/mysql/error.log
sudo tail -f /var/log/mysql/mysql-slow.log

# PostgreSQL
sudo tail -f /var/log/postgresql/postgresql-16-main.log

# Redis
sudo tail -f /var/log/redis/redis-server.log
sudo redis-cli -a 'STRONG_APP_PASSWORD' SLOWLOG GET 10
```

### 7.4 Exporters Prometheus (`b:452`–`b:482`)

```bash
# MySQL
sudo apt install -y prometheus-mysqld-exporter

# PostgreSQL
sudo apt install -y prometheus-postgres-exporter

# Redis
sudo apt install -y prometheus-redis-exporter
```

```bash
sudo tee /etc/default/prometheus-mysqld-exporter <<'EOF'
ARGS="--config.my-cnf /etc/mysql/exporter.cnf --web.listen-address=127.0.0.1:9104"
EOF

sudo tee /etc/mysql/exporter.cnf <<EOF
[client]
user=exporter
password=EXPORTER_PASSWORD
EOF
```

**Reglas duras** (`b:477`–`b:480`): «1. Los exporters escuchan SOLO en `127.0.0.1`. El scraper de Prometheus va por túnel SSH o por socket Unix. 2. Crear usuario `exporter` con permisos mínimos (`PROCESS`, `REPLICATION CLIENT`, `SELECT`).»

**No consta**: configuración de los exporters de PostgreSQL y Redis (sólo se documenta la del de MySQL); reglas de alerta; umbrales; Grafana.

---

## 8. Configuración de Laravel

### 8.1 MySQL / PostgreSQL (§4.10)

`.env` (`a:408`–`a:419`):

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_app
DB_PASSWORD=STRONG_PASSWORD_FROM_ENV

# TLS para MySQL (opcional pero recomendado)
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca.pem
MYSQL_ATTR_SSL_VERIFY_SERVER_CERT=1
```

`config/database.php` (`a:423`–`a:444`):

```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'laravel_db'),
    'username' => env('DB_USERNAME', 'laravel_app'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => env('MYSQL_ATTR_SSL_VERIFY_SERVER_CERT', false),
    ]) : [],
],
```

Variante PostgreSQL (`a:448`–`a:456`):

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_db
DB_USERNAME=laravel_app
DB_PASSWORD=STRONG_PASSWORD_FROM_ENV
PGSSLMODE=require
```

### 8.2 Redis (§4.14)

`.env` (`b:242`–`b:252`):

```ini
REDIS_CLIENT=phpredis   # o predis si no tienes phpredis instalado
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=STRONG_APP_PASSWORD
REDIS_PORT=6379
REDIS_DB=0

# Para TLS (si habilitado en sección 4.11.4)
REDIS_PORT=6380
REDIS_SCHEME=tls
```

`config/database.php` (`b:256`–`b:284`):

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),

    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'laravel_database_'),
        'persistent' => false,
    ],

    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'username' => env('REDIS_USERNAME'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'scheme' => env('REDIS_SCHEME', 'tcp'),
        'read_write_timeout' => 60,
    ],

    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

**Validación** (`b:288`–`b:294`):

```bash
cd /var/www/api.dominio.tld
php artisan tinker
> Cache::put('test', 'ok', 60);
> Cache::get('test');
# "ok"
```

### 8.3 Persistencia y pool

- Persistencia de conexión Redis: `'persistent' => false` (`b:263`).
- Timeout de lectura/escritura Redis: `'read_write_timeout' => 60` (`b:274`).
- Pool **no consta**: no hay `DB_POOL`, ni PgBouncer, ni `pool` en `config/database.php`, ni configuración de persistent connections para MySQL/PostgreSQL. `'persistent'` sólo aparece para Redis (`b:263`).
- **No consta** `QUEUE_CONNECTION`, `CACHE_STORE`/`CACHE_DRIVER` ni `SESSION_DRIVER` en los `.env` documentados (`a:408`, `b:242`), aunque sí existen colas/caché/sesiones en Redis (`b:140`, `b:277`).
- **No consta** la variable `REDIS_USERNAME` en el `.env` (sólo se lee en `config/database.php`, `b:269`).
- **No consta** instalación de `phpredis`/`predis` ni de extensiones PHP.

---

## 9. Defectos, contradicciones y traslado a Docker

### 9.1 Versiones incoherentes con el proyecto

| # | Defecto | Cita |
|---|---|---|
| V1 | El capítulo fija **PostgreSQL 16**, no 18: título y stack | `a:1`, `a:3`, `a:15` |
| V2 | Rutas y paquetes atados a la 16: `postgresql.conf`/`pg_hba.conf` en `/etc/postgresql/16/main/` | `a:305`, `a:336`, `a:361` |
| V3 | Paquete de auditoría de la 16: `postgresql-16-pgaudit` | `a:358` |
| V4 | Ruta de log de la 16: `postgresql-16-main.log` | `b:443` |
| V5 | **No consta** el repositorio PGDG ni ningún método para obtener PG 18 en Ubuntu 24.04 (Ubuntu 24.04 trae PG 16 de serie) | ausente |
| V6 | MySQL: «Oracle hasta abril 2026» — fecha de caducidad del soporte del motor primario propuesto | `a:19` |
| V7 | «Laravel 13» se asume en todo el capítulo; la versión real del proyecto **no consta** | `a:23`, `a:404`, `b:1` |
| V8 | Redis «7.x» sin versión menor concreta | `a:3`, `b:17` |

### 9.2 Contradicciones internas

| # | Contradicción | Citas |
|---|---|---|
| C1 | `skip-networking` está **activo** en el fragmento, pero el túnel SSH apunta a `127.0.0.1:3306`, el diagrama §4.9 abre TCP a 3306 y el checklist final espera ver 3306 escuchando | `a:100` vs `b:184`, `a:393`, `b:532` |
| C2 | Con `skip-networking`, MySQL sólo escucha en socket Unix; pero las cuentas son `@'127.0.0.1'` (no `@'localhost'`), así que la app no podría autenticarse por socket | `a:100`, `a:186`, `a:193`, `a:197` |
| C3 | El mensaje de error de ejemplo muestra `'laravel_app'@'localhost'` aunque `skip-name-resolve` está activo y las cuentas son de `127.0.0.1` | `a:104`, `a:240` |
| C4 | La verificación de MySQL dice que `ss` **no debe devolver nada** en 3306, pero el checklist final **espera** `127.0.0.1:3306` | `a:143` vs `b:532` |
| C5 | La conexión por túnel usa `--ssl-mode=DISABLED` y la nota dice «desactiva el TLS del motor», pero `require_secure_transport = ON` rechaza conexiones no TLS, y §4.5/§4.18 exigen TLS obligatorio | `b:230`, `b:234` vs `a:114`, `a:236`, `b:519`, `b:541` |
| C6 | El diagrama §4.9 muestra `TLS handshake (… ssl-mode=REQUIRED)` en la conexión por túnel | `a:394` vs `b:230` |
| C7 | Tres capas de cifrado (SSH + TLS + SHA2) frente a «desactiva el TLS del motor» | `a:400` vs `b:234` |
| C8 | El túnel de Redis apunta al puerto **TLS 6380**, pero la nota pide desactivar TLS | `b:189`–`b:190` vs `b:234` |
| C9 | El script de backup escribe `laravel-$TS.sql.gz.gpg` pero **no comprime** el dump (entra `dump.sql` sin gzip) y el drill descomprime a `dump.sql`; §4.15.1 sí comprime con `gzip` | `b:311` vs `b:388`, `b:393`–`b:394`, `b:413` |
| C10 | El drill usa `laravel_migrate` para `CREATE DATABASE`/`DROP DATABASE`, pero sus privilegios son sólo `ALL PRIVILEGES ON laravel_db.*` | `a:194` vs `b:417`, `b:425` |
| C11 | Se anuncia rotación **7/30/365**, pero sólo existe borrado >7 días y un `mv` mensual; no hay reglas de 30 ni de 365 días, ni purga de `/var/backups/monthly/` | `b:356`, `b:359` vs `b:366`–`b:371` |
| C12 | Los backups de Redis (cada 6 h) no tienen limpieza ni rotación | `b:363` vs `b:366`–`b:371` |
| C13 | El `cp` y el `gpg` del respaldo Redis invocan `$(date …)` por separado: si cambia el segundo, el gpg apunta a un fichero inexistente | `b:345`, `b:347`, `b:348` |
| C14 | gpg simétrico sin `--passphrase-file` en §4.15.1, §4.15.2 y §4.15.3 → pide passphrase interactiva; en cron no funciona | `b:312`, `b:334`, `b:346` vs `b:392` |
| C15 | La passphrase de cifrado vive en el mismo droplet, contra la regla «clave separada del droplet» | `b:392`, `b:412` vs `b:523`, `a:289` |
| C16 | Ruta de la passphrase en `/etc/dropbear/` (directorio de Dropbear SSH, no de secretos) | `b:392`, `b:412` |
| C17 | `${MYSQL_BACKUP_PASSWORD}` se usa en el script pero **nunca se define** en ningún archivo del capítulo | `b:385` |
| C18 | El CA de MySQL para Laravel es `/etc/ssl/certs/ca.pem`, pero el CA del servidor es `/etc/mysql/certs/ca.pem` | `a:417` vs `a:111` |
| C19 | `REDIS_PORT` se define **dos veces** en el mismo `.env` (6379 y 6380) | `b:246`, `b:250` |
| C20 | El `.env` no define `REDIS_USERNAME`, pero `config/database.php` lo lee y `users.acl` crea `laravel_app`; con `requirepass` el `AUTH` iría como usuario `default` | `b:243`–`b:247`, `b:269`, `b:140`, `b:71` |
| C21 | Los `redis-cli -a 'STRONG_APP_PASSWORD'` asumen que `default` tiene la clave de la app | `b:162`, `b:168`, `b:447` vs `b:71`, `b:140` |
| C22 | El prefijo de Laravel es `laravel_database_`, pero los patrones ACL son `~laravel_cache:*`, `~laravel_session:*`, `~laravel_queue:*` | `b:262` vs `b:140` |
| C23 | `tls-auth-clients yes` exige certificado de cliente, pero sólo se genera el del servidor y se copia como CA; el `redis-cli --tls` de verificación no lleva `--cert`/`--key` | `b:113`, `b:128`, `b:166`–`b:168` |
| C24 | Se habilita `tls-port 6380` sin poner `port 0`: el 6379 sigue en claro (el capítulo no lo advierte y hasta verifica en 6379) | `b:66`, `b:109`, `b:158` |
| C25 | La tabla resumen cita «pgcrypto» para el cifrado en reposo de PostgreSQL, pero §4.8 nunca lo configura; la verificación de esa fila es de MySQL | `b:520` vs `a:301`–`a:373` |
| C26 | Se proclaman «5 usuarios separados» pero sólo hay 3 `CREATE USER`; `backup_user` y `exporter` sólo existen en el diagrama/reglas | `b:521` vs `a:186`–`a:198`, `b:480`, `b:498`, `b:500` |
| C27 | PostgreSQL: `OWNER laravel_app` + `GRANT ALL PRIVILEGES ON DATABASE`, contra el principio de menor privilegio del propio capítulo | `a:351`–`a:352` vs `a:182`–`a:210` |
| C28 | `pg_hba.conf` no usa `hostssl`, así que el comentario «NO permitir host sin TLS» no está implementado | `a:342`–`a:344` |
| C29 | El comentario del respaldo Redis dice «configurado en redis.conf save», pero el fragmento de `redis.conf` no define `save` | `b:344` vs `b:87`–`b:91` |
| C30 | Se migra un usuario `'app_user'@'localhost'` que no se crea en ningún otro punto del capítulo | `a:163` |
| C31 | `backup_user` tiene `LOCK TABLES, RELOAD` pero el dump usa `--single-transaction` (no los necesita) | `b:498` vs `b:311`, `b:387` |
| C32 | El scraper de Prometheus debería ir «por túnel SSH», pero el `ssh config` sólo abre 13306/15432/16379 y no el 9104 | `b:479` vs `b:205`–`b:207` |
| C33 | El exporter de MySQL no tiene TLS en su `[client]`, pero MySQL exige `require_secure_transport = ON` | `b:470`–`b:474` vs `a:114` |
| C34 | El bloque de verificación por túnel está sintácticamente roto: el comentario `#` corta la continuación de línea y `-e "SELECT VERSION();"` queda como comando aparte | `b:229`–`b:231` |
| C35 | `log_warnings = 2` (parámetro retirado en MySQL 8.0.30+) — *(a verificar contra la versión exacta del motor)* | `a:126` |
| C36 | El plugin `server_audit.so` es el de **MariaDB** (el propio texto lo dice) y **no** viene con `mysql-server-8.0` de Oracle: exige binario externo compatible | `a:129`–`a:130` |
| C37 | `early-plugin-load = keyring_file.so` — el capítulo no menciona la alternativa de componente keyring — *(a verificar)* | `a:118` |
| C38 | Redis: se anuncia «persistencia nativa», pero no se documenta `save` ni `BGSAVE` antes de copiar el RDB | `b:339`–`b:345` |
| C39 | §4.7 dice que LUKS «es redundante en DO», pero §4.15.3 justifica el cifrado de Redis «por LUKS o DO Volume» | `a:295` vs `b:341` |
| C40 | Toda la custodia/rotación de claves se delega a un capítulo externo no incluido | `a:210`, `b:524` |

### 9.3 Traslado a contenedores Docker (análisis mío, no consta en el documento)

El documento está escrito **íntegramente para servicios nativos** (apt + systemd + rutas FHS + cron del sistema). Con el despliegue previsto (Docker en un Droplet):

**A. NO trasladable tal cual**

| Elemento del documento | Cita | Por qué no |
|---|---|---|
| Override de systemd para MySQL | `a:58`–`a:77` | No hay systemd dentro del contenedor; se traduce a `cap_drop`, `read_only`, `tmpfs`, `security_opt`, `userns_mode` |
| `systemctl edit redis` + override | `b:25`, `b:30`–`b:44`, `b:48`–`b:51` | Igual que el anterior |
| `sudo systemctl restart mysql/redis/postgresql` | `a:85`, `a:142`, `a:371`, `b:50` | Se sustituye por `docker compose restart` |
| `apt install` de los motores | `a:37`, `a:358`, `b:16`, `b:454`–`b:460` | Se sustituye por imágenes oficiales / Dockerfile derivado |
| `mysql_secure_installation` | `a:42`–`a:54` | La imagen oficial no lo usa: `MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER` |
| `bind-address = 127.0.0.1` / `mysqlx-bind-address` | `a:96`–`a:97` | Con red bridge, el contenedor debe escuchar en `0.0.0.0`; si escucha en loopback, el contenedor de Laravel no lo alcanza. El aislamiento se logra **no publicando puertos** y con redes internas, no con el bind |
| `skip-networking` | `a:100` | Mata toda conectividad TCP entre contenedores: la app no podría conectar |
| `bind 127.0.0.1 ::1` (Redis) | `b:60` | Mismo problema entre contenedores (aunque aceptable si el proceso Laravel vive en el mismo contenedor) |
| `/etc/mysql/mysql.conf.d/hardening.cnf` | `a:91` | Trasladable como archivo **montado** en la ruta de configuración de la imagen (o `--defaults-extra-file`), no como edición del paquete |
| `/etc/postgresql/16/main/*.conf`, `/etc/postgresql/16/main/pg_hba.conf` | `a:305`, `a:336` | La imagen oficial usa `PGDATA` (`/var/lib/postgresql/data`) o `conf.d`; la ruta de Debian no existe |
| `/etc/redis/redis.conf`, `/etc/redis/users.acl`, `/etc/redis/certs` | `b:55`, `b:84`, `b:121` | Trasladables por volumen, pero el `uid/gid` `redis:redis` puede diferir según la imagen |
| `/etc/cron.d/db-backups` y los scripts de backup | `b:353`–`b:401` | No hay cron en los contenedores: requiere cron del **host**, o un contenedor sidecar (cron/supercronic/Ofelia/`docker compose exec`) |
| Rutas de log del sistema (`/var/log/mysql/*`, `/var/log/redis/*`, `/var/log/postgresql/*`) | `a:122`–`a:124`, `b:99`, `b:443`, `b:446` | Dentro del contenedor; se leen con `docker logs`/`exec` o se montan en un volumen |
| `/etc/dropbear/backup-gpg-passphrase` | `b:392`, `b:412` | En contenedores debe ser un secreto montado (`docker secret`, bind read-only o variable de entorno inyectada) |
| `ss -tlnp` como prueba de «no escucha en IP pública» | `a:143`, `b:154`, `b:225`, `b:517`, `b:531` | Dentro del contenedor mostrará `0.0.0.0`/`::` y en el host mostrará el proxy/`docker-proxy`; la prueba correcta es «ningún puerto publicado» (`docker compose ps`, `docker port`) |
| `sudo -u postgres psql -c …` | `a:350` | En contenedor: `docker compose exec -T postgres psql -U postgres -c …` |
| LUKS / DigitalOcean Volume | `a:293`–`a:297` | Se aplica en el **host** antes de montar; dentro del contenedor es transparente |
| `auth_socket` para `root@localhost` en Ubuntu 24.04 | `a:46`, `a:177`, `b:490` | Comportamiento del paquete Debian/Ubuntu; la imagen oficial de MySQL no lo tiene |
| `mysqldump`/`pg_dump` ejecutados «en el host» | `b:303`, `b:328` | Con la BD en contenedor y sin puertos publicados, el cliente del host no alcanza el socket/TCP; hay que ejecutarlos dentro del contenedor o de un sidecar |
| El checker `mysql -h 127.0.0.1 -P 13306 …` | `b:229` | Sólo funciona si el contenedor publica `127.0.0.1:3306:3306` en el host |

**B. Sí trasladable**

1. Todo el endurecimiento por **archivos de configuración** (fragmento `my.cnf`, `postgresql.conf`, `pg_hba.conf`, `redis.conf`, `users.acl`) montados como volúmenes o con `command:`.
2. Todo el **SQL de cuentas y privilegios** (`a:184`–`a:202`, `a:349`–`a:352`) y los `GRANT`, vía `/docker-entrypoint-initdb.d` o migraciones de Laravel.
3. **`caching_sha2_password`** (`a:155`–`a:178`) y **SCRAM-SHA-256** (`a:342`–`a:343`): son del motor, no del empaquetado.
4. **TLS de los motores**: los certificados y directivas (`a:111`–`a:114`, `b:108`–`b:131`) funcionan igual si se montan los certificados como volumen.
5. **InnoDB Tablespace Encryption**: trasladable **sólo si `/var/lib/mysql-keyring` es un volumen persistente**; si no, la master key se pierde al recrear el contenedor y los datos cifrados quedan inutilizables.
6. **`ALTER INSTANCE ROTATE INNODB MASTER KEY`** (`a:286`): funciona en contenedor vía `exec`.
7. **pgaudit** (`a:355`–`a:372`): requiere Dockerfile derivado (`postgresql-18-pgaudit` + `shared_preload_libraries`).
8. **`mysqldump`/`pg_dump` + `gpg`** (`b:302`–`b:349`): trasladables ejecutándolos dentro del contenedor de BD o de un sidecar con los clientes instalados.
9. **El túnel SSH** (`b:182`–`b:232`): sigue siendo la vía de administración correcta, pero requiere publicar los puertos en el loopback del droplet (`127.0.0.1:3306:3306`) y que `sshd` corra en el **host**, no en el contenedor. Esto **no consta** en el documento.
10. **Exporters Prometheus** (`b:452`–`b:482`): llevan a sidecars contenedorizados; el bind a `127.0.0.1` debe revisarse igual que el de las BD.
11. **La regla de oro** («la base de datos nunca está en una IP pública», `a:5`) es válida en Docker, pero se cumple **no publicando puertos al exterior** y aislando redes, no con `bind 127.0.0.1`.

### 9.4 Cambios mínimos para PG 18 (si el proyecto usa 18)

Todo lo que el capítulo fija en la 16 debe reescribirse: `a:1`, `a:3`, `a:15`, `a:305`, `a:336`, `a:361`, `a:358`, `b:443`. **No consta** en el documento cómo instalar PG 18 en Ubuntu 24.04 (haría falta el repositorio PGDG, que el capítulo no menciona) ni la ruta del paquete `postgresql-18-pgaudit`.

---

## 10. Hechos verificados

1. `descripción` — hecho — `capitulo-04-parte-a.md:1` — el título del capítulo es «Capítulo 04 — MySQL 8 / PostgreSQL 16 + Redis 7 sin exposición, cifrados, accesibles solo por túnel SSH».
2. `stack` — hecho — `capitulo-04-parte-a.md:3` — stack objetivo: Ubuntu 24.04 LTS, MySQL 8.0 LTS (motor primario) o PostgreSQL 16 (alternativa), Redis 7.x; bind solo en `127.0.0.1`.
3. `regla de oro` — hecho — `capitulo-04-parte-a.md:5` — «la base de datos nunca está en una IP pública».
4. `selección` — hecho — `capitulo-04-parte-a.md:27` — MySQL 8.0 LTS para LAMP/WordPress; PostgreSQL 16 si se necesitan RLS, pgvector o tipos estrictos.
5. `RLS` — hecho — `capitulo-04-parte-a.md:22` — RLS: no nativo en MySQL, nativo y granular en PostgreSQL.
6. `pgvector` — hecho — `capitulo-04-parte-a.md:25` — pgvector nativo en PostgreSQL, no nativo en MySQL.
7. `LTS MySQL` — hecho — `capitulo-04-parte-a.md:19` — soporte de Oracle hasta abril de 2026.
8. `skip-networking` — hecho — `capitulo-04-parte-a.md:100` — `skip-networking` queda activo en el fragmento (no comentado).
9. `TLS MySQL` — hecho — `capitulo-04-parte-a.md:114` — `require_secure_transport = ON`.
10. `keyring` — hecho — `capitulo-04-parte-a.md:118`–`119` — `early-plugin-load = keyring_file.so` y `keyring_file_data = /var/lib/mysql-keyring/keyring`.
11. `auditoría MySQL` — hecho — `capitulo-04-parte-a.md:131` — `server_audit_events = CONNECT,QUERY_DDL,QUERY_DML`.
12. `privilegios app` — hecho — `capitulo-04-parte-a.md:190` — `GRANT SELECT, INSERT, UPDATE, DELETE ON laravel_db.* TO 'laravel_app'@'127.0.0.1';`.
13. `privilegios migración` — hecho — `capitulo-04-parte-a.md:194` — `GRANT ALL PRIVILEGES ON laravel_db.* TO 'laravel_migrate'@'127.0.0.1';`.
14. `rotación master key` — hecho — `capitulo-04-parte-a.md:286` — `ALTER INSTANCE ROTATE INNODB MASTER KEY;`.
15. `custodia master key` — hecho — `capitulo-04-parte-a.md:289` — la master key debe respaldarse en un lugar seguro y separado del droplet (Vault, DO Spaces con KMS, HSM).
16. `volúmenes DO` — hecho — `capitulo-04-parte-a.md:295` — los DigitalOcean Volumes ya están cifrados at-rest con AES-256-XTS por defecto.
17. `pg conf` — hecho — `capitulo-04-parte-a.md:309`–`310` — PostgreSQL: `listen_addresses = 'localhost'` y `port = 5432`.
18. `pg tls` — hecho — `capitulo-04-parte-a.md:317` — `ssl_min_protocol_version = 'TLSv1.2'`.
19. `pg_hba` — hecho — `capitulo-04-parte-a.md:342`–`343` — `host all all 127.0.0.1/32 scram-sha-256` y `host all all ::1/128 scram-sha-256`.
20. `pg privilegios` — hecho — `capitulo-04-parte-a.md:352` — `GRANT ALL PRIVILEGES ON DATABASE laravel_db TO laravel_app;`.
21. `pgaudit` — hecho — `capitulo-04-parte-a.md:358` — paquete `postgresql-16-pgaudit`.
22. `pgaudit log` — hecho — `capitulo-04-parte-a.md:365` — `pgaudit.log = 'ddl, role, write'`.
23. `bind Redis` — hecho — `capitulo-04-parte-b.md:60` — `bind 127.0.0.1 ::1`.
24. `rename-command` — hecho — `capitulo-04-parte-b.md:75`–`80` — se bloquean/renombran `FLUSHDB`, `FLUSHALL`, `CONFIG`, `DEBUG`, `SHUTDOWN` y `KEYS`.
25. `ACL Redis` — hecho — `capitulo-04-parte-b.md:84` — `aclfile /etc/redis/users.acl`.
26. `límites Redis` — hecho — `capitulo-04-parte-b.md:94`–`95` — `maxmemory 2gb` y `maxmemory-policy allkeys-lru`.
27. `TLS Redis` — hecho — `capitulo-04-parte-b.md:109`–`113` — `tls-port 6380`, `tls-auth-clients yes`, `tls-protocols "TLSv1.2 TLSv1.3"`.
28. `usuario ACL app` — hecho — `capitulo-04-parte-b.md:140` — `user laravel_app on '…' ~laravel_cache:* ~laravel_session:* ~laravel_queue:* &* +@read +@write +@connection -@admin -@dangerous -@keyspace -@scripting`.
29. `túnel MySQL` — hecho — `capitulo-04-parte-b.md:184` — `ssh -L 13306:127.0.0.1:3306 deployer@DROPLET_IP`.
30. `túnel Redis` — hecho — `capitulo-04-parte-b.md:190` — `ssh -L 16379:127.0.0.1:6380 deployer@DROPLET_IP`.
31. `tls por túnel` — hecho — `capitulo-04-parte-b.md:230` — la prueba por túnel usa `--ssl-mode=DISABLED`.
32. `cifrado backups` — hecho — `capitulo-04-parte-b.md:313` — `--cipher-algo AES256` (simétrico) para MySQL; igual en `b:335` (PG) y `b:346` (Redis).
33. `cron backups` — hecho — `capitulo-04-parte-b.md:357` — `0 3 * * * backup /usr/local/bin/backup-mysql.sh`.
34. `limpieza backups` — hecho — `capitulo-04-parte-b.md:366`–`367` — sólo se borran `*.gpg` con `-mtime +7` en `/var/backups/mysql` y `/var/backups/postgres`.
35. `passphrase gpg` — hecho — `capitulo-04-parte-b.md:392` — `--passphrase-file /etc/dropbear/backup-gpg-passphrase`.
36. `drill` — hecho — `capitulo-04-parte-b.md:403` y `429` — el drill de restauración es mensual y «si el drill falla, no es un backup, es una esperanza».
37. `exporter` — hecho — `capitulo-04-parte-b.md:480` — usuario `exporter` con `PROCESS`, `REPLICATION CLIENT`, `SELECT`.
38. `rotación trimestral` — hecho — `capitulo-04-parte-b.md:524` — rotación trimestral de contraseñas, claves SSH y master keys, definida en el Runbook del Capítulo 5.
39. `checklist` — hecho — `capitulo-04-parte-b.md:532` — el checklist espera «solo 127.0.0.1:3306 (MySQL) y 127.0.0.1:6379 (Redis)».
40. `backups AES-256` — hecho — `capitulo-04-parte-b.md:523` — garantía declarada: «AES-256 con clave separada del droplet».
