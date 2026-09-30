# Informe de auditoría de configuración — Apéndices A–E
**Fuente auditada:** `docs-security/apendice-A.md` (530 l.), `apendice-B.md` (184 l.), `apendice-C.md` (239 l.), `apendice-D.md` (226 l.), `apendice-E.md` (449 l.).
**Método:** lectura íntegra por tramos de los cinco apéndices + verificación cruzada contra `capitulo-01..05`, `deliverable.md` y `guia-maestra-seguridad.md`. Todo dato va con `archivo:línea`. Las transcripciones se han extraído **literalmente** de los ficheros (no re-tecleadas).

---

## 0. ADVERTENCIA PREVIA — la premisa del plan no coincide con la fuente

El encargo describe el proyecto como «sede electrónica colombiana (Laravel 13 + PHP-FPM **8.5** + **PostgreSQL 18** + Redis 7) desplegada **con Docker** en DigitalOcean». Los apéndices y capítulos **no dicen eso**:

| Premisa del encargo | Lo que dicen realmente los documentos | Evidencia |
|---|---|---|
| PHP-FPM 8.5 | **PHP 8.4-FPM** (cap-05) y **php8.3-fpm** (cap-03, apéndice A, apéndice C, guía maestra) | `capitulo-05.md:3`, `capitulo-05.md:1530`, `capitulo-03-parte-a.md:337`, `apendice-A.md:500`, `apendice-C.md:13`, `guia-maestra-seguridad.md:139` |
| PostgreSQL 18 | **PostgreSQL 16** («alternativa»; el motor primario es MySQL 8.0) | `capitulo-04-parte-a.md:1`, `capitulo-04-parte-a.md:3`, `capitulo-05.md:3`, `apendice-D.md:104`, `apendice-E.md:281`, `guia-maestra-seguridad.md:5`, `deliverable.md:5` |
| Desplegado con Docker | Docker es **opcional**; el despliegue de los capítulos/Apéndice A es **nativo sobre el droplet** | `apendice-E.md:113` («Docker: En esta guía, opcional»), `apendice-A.md:494-500` (git pull + systemctl en el host) |
| Sede electrónica colombiana | **Cero** menciones a normativa colombiana en todo el corpus (Ley 527, Ley 1581, Decreto 1078, MinTIC, CONPES, GESI, sede electrónica) | búsqueda exhaustiva sobre `capitulo-*.md`, `apendice-*.md`, `deliverable.md`, `guia-maestra-seguridad.md`: 0 coincidencias |
| Dominio del proyecto | Todos los ejemplos usan el marcador `dominio.tld` / `example.tld` | `apendice-B.md:132`, `apendice-A.md:150`, `capitulo-05.md:3` |

**Consecuencia para el plan de construcción:** los apéndices son fuente operativa válida para un VPS **nativo** con MySQL 8.0 (o PG 16) y PHP 8.4/8.3, **no** para el stack declarado. Si el plan debe ser Laravel 13 + PHP-FPM 8.5 + PostgreSQL 18 + Docker, los apéndices cubren ~60 % del terreno y **falta** toda la capa contenedor (salvo `capitulo-05.md` §6, 47 menciones) y toda la capa normativa colombiana.

---

## 1. Apéndice A — Scripts completos

### 1.1 Inventario completo de artefactos del Apéndice A

El apéndice tiene **7 secciones (A.1–A.7)** y **12 artefactos desplegables** (11 archivos + 1 bloque de comandos). No contiene ningún otro script.

| # | Sección | Artefacto | Ruta de instalación | Propósito | Qué hace | Naturaleza |
|---|---|---|---|---|---|---|
| 1 | A.1 (`apendice-A.md:9`) | `cloudflare-api.conf` | `/etc/fail2ban/action.d/cloudflare-api.conf` | Acción custom Fail2Ban → Cloudflare API v4 | `actionban` hace POST a `/zones/<cf_zone_id>/firewall/access_rules/rules` con `Bearer <cf_api_token>`; `actionunban` hace DELETE de `<rule_id>` | **Nativo** (servicio de SO) |
| 2 | A.1 (`apendice-A.md:23`) | `jail.local` (fragmento) | `/etc/fail2ban/jail.local` | Config de jails | Define `[DEFAULT]` con `cf_zone_id`/`cf_api_token` y jails `sshd`, `nginx-http-auth`, `recidive` | **Nativo** |
| 3 | A.2 (`apendice-A.md:63`) | `update-cf-ips` | `/etc/cron.hourly/update-cf-ips` | Refrescar rangos Cloudflare y recargar nginx | Descarga `ips-v4`/`ips-v6`, regenera `cloudflare-realip.conf`, `nginx -t` y `systemctl reload nginx`, con rollback | **Nativo** (cron de host) |
| 4 | A.3 (`apendice-A.md:111`) | `ossec.conf` | `/var/ossec/etc/ossec.conf` | Config del agente Wazuh | `client_buffer`, logging, `localfile` (auth.log, syslog, kern.log, fail2ban.log, access.log nginx, security.log Laravel), `syscheck` FIM, `rootcheck`, `syscollector`, `active-response`, `server` | **Nativo** (agente en host); trasladable a contenedor solo con privilegios/FIM degradado |
| 5 | A.3 (`apendice-A.md:217`) | `laravel_rules.xml` | `/var/ossec/etc/rules/laravel_rules.xml` | Reglas custom Wazuh para el log JSON de Laravel | Reglas 100100 (level 5 login_failed), 100101 (level 12 permission_denied), 100102 (level 10 role_changed) | **Nativo** |
| 6 | A.4 (`apendice-A.md:243`) | `backup-mysql.sh` | `/usr/local/bin/backup-mysql.sh` | Backup diario MySQL cifrado | `mysqldump` → temp → `gpg --symmetric AES256` → `/var/backups/mysql/laravel-$TS.sql.gz.gpg` → `aws s3 cp` a `s3://backup-vps-secure/mysql/` con `--sse AES256` → purga local `-mtime +7` | **Nativo**; **trasladable a contenedor** (job/cron) |
| 7 | A.4 (`apendice-A.md:281`) | `backup-postgres.sh` | `/usr/local/bin/backup-postgres.sh` | Backup diario PostgreSQL cifrado | `pg_dump --format=custom --compress=9` → `gpg AES256` → `/var/backups/postgres/laravel-$TS.sqlc.gpg` → `aws s3 cp` a `s3://backup-vps-secure/postgres/` → purga `-mtime +7` | **Nativo**; **trasladable** |
| 8 | A.5 (`apendice-A.md:319`) | `wazuh-exporter.service` | `/etc/systemd/system/wazuh-exporter.service` | Unit systemd del exporter Prometheus de Wazuh | `ExecStart=/usr/local/bin/wazuh-exporter --listen-address 127.0.0.1:9093`, `User=prometheus`, hardening (`NoNewPrivileges`, `ProtectSystem=strict`, `ProtectHome`, `PrivateTmp`, `ReadWritePaths=/var/log/wazuh`) | **Nativo** |
| 9 | A.5 (`apendice-A.md:344`) | `prometheus.yml` (fragmento) | `/etc/prometheus/prometheus.yml` | Targets de scrape | jobs `wazuh` 9093, `mysqld` 9104, `postgres` 9187, `redis` 9121, `nginx` 9113, `node` 9100 | **Nativo** |
| 10 | A.6 (`apendice-A.md:377`) | `ci.yml` | `.github/workflows/ci.yml` | CI: lint + test + scan por PR | jobs `lint` (pint), `test` (mysql service + `artisan test --coverage --min=70`), `scan` (Trivy fs SARIF → Code Scanning) | **CI externo** |
| 11 | A.6 (`apendice-A.md:449`) | `deploy.yml` | `.github/workflows/deploy.yml` | Deploy a DigitalOcean «vía OIDC» | `aws-actions/configure-aws-credentials` con `role-to-assume` → `aws s3 sync ./build/ s3://myapp-static/` → ssh al droplet: `git pull`, `composer install --no-dev`, `migrate --force`, `config:cache`, `route:cache`, `sudo systemctl reload php8.3-fpm` | **CI externo** (despliega a host nativo) |
| 12 | A.7 (`apendice-A.md:505`) | Bloque de comandos ModSecurity | (no es archivo) | Alternativa WAF: ModSecurity V3 + OWASP CRS 4.0.0 | `apt install libnginx-mod-http-modsecurity`, activa motor, descarga CRS, define `modsecurity on;` en `nginx.conf` | **Nativo** |

**Ausencias notables del Apéndice A** (la guía maestra lo describe como «systemd units, snippets, hooks, fail2ban actions», `guia-maestra-seguridad.md:228`):
- **No hay hooks de Certbot**, aunque el checklist los exige (`apendice-C.md:73`) y existen en `capitulo-03-parte-b.md:61-77`.
- **No hay snippets de nginx** (`ssl-params.conf`, `security-headers.conf`, `rate-limit.conf`, `cloudflare-realip.conf`, `snippets/php-fpm.conf`): solo en `capitulo-03-parte-b.md:105-155`, `165-201`, `225-237`, `256-287` y `capitulo-03-parte-a.md:331-352`.
- **No hay units systemd** para nginx/php-fpm/mysql/redis (los drop-ins están en `capitulo-03-parte-a.md:74-101`, `capitulo-04-parte-a.md:56-88`, `capitulo-04-parte-b.md:30-50`).
- **No está el script del checklist** `pre-prod-check.sh`, que vive en `apendice-C.md:175-237`.


#### A.1 — Acción Fail2Ban para Cloudflare (`apendice-A.md:11-21`)
```ini
[Definition]
# Acción custom Fail2Ban → Cloudflare API v4
# Documentado en https://fail2ban.readthedocs.io/en/latest/actions.html
actionban = curl -s -X POST "https://api.cloudflare.com/client/v4/zones/<cf_zone_id>/firewall/access_rules/rules" \
    -H "Authorization: Bearer <cf_api_token>" \
    -H "Content-Type: application/json" \
    --data '{"mode":"block","configuration":{"target":"ip","value":"<ip>"},"notes":"Fail2Ban <name>"}'
actionunban = curl -s -X DELETE "https://api.cloudflare.com/client/v4/zones/<cf_zone_id>/firewall/access_rules/rules/<rule_id>" \
    -H "Authorization: Bearer <cf_api_token>"
```

#### A.1 — Fragmento de `jail.local` (`apendice-A.md:25-57`)
```ini
[DEFAULT]
cf_zone_id = ZONE_ID_FROM_CLOUDFLARE_DASHBOARD
cf_api_token = TOKEN_FROM_CLOUDFLARE_DASHBOARD

[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
findtime = 600
bantime = 3600
bantime.increment = true
bantime.factor = 1.5
bantime.maxtime = 86400

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/*error.log
maxretry = 3
findtime = 600
bantime = 3600

[recidive]
enabled = true
filter = recidive
logpath = /var/log/fail2ban.log
bantime = 604800  ; 7 días para reincidentes
findtime = 86400
maxretry = 5
```

#### A.2 — `/etc/cron.hourly/update-cf-ips` (`apendice-A.md:65-101`)
```bash
#!/bin/bash
# Descarga los rangos de Cloudflare y recarga nginx
# Documentado en https://developers.cloudflare.com/fundamentals/reference/update-ip-versions/

set -euo pipefail
TMPDIR=$(mktemp -d)
trap "rm -rf $TMPDIR" EXIT

curl -s https://www.cloudflare.com/ips-v4 -o $TMPDIR/ips-v4
curl -s https://www.cloudflare.com/ips-v6 -o $TMPDIR/ips-v6

# Backup de la versión actual
cp /etc/nginx/conf.d/cloudflare-realip.conf /etc/nginx/conf.d/cloudflare-realip.conf.bak

# Regenerar
cat > /etc/nginx/conf.d/cloudflare-realip.conf <<EOF
# Auto-generado $(date -Iseconds)
# IPv4
EOF
sed 's|^|set_real_ip_from |' $TMPDIR/ips-v4 >> /etc/nginx/conf.d/cloudflare-realip.conf
echo "" >> /etc/nginx/conf.d/cloudflare-realip.conf
echo "# IPv6" >> /etc/nginx/conf.d/cloudflare-realip.conf
sed 's|^|set_real_ip_from |' $TMPDIR/ips-v6 >> /etc/nginx/conf.d/cloudflare-realip.conf
echo "" >> /etc/nginx/conf.d/cloudflare-realip.conf
echo "real_ip_header CF-Connecting-IP;" >> /etc/nginx/conf.d/cloudflare-realip.conf

# Validar y recargar
if nginx -t; then
    systemctl reload nginx
    logger -t cf-ips "Cloudflare IPs actualizadas y nginx recargado"
else
    cp /etc/nginx/conf.d/cloudflare-realip.conf.bak /etc/nginx/conf.d/cloudflare-realip.conf
    logger -t cf-ips "ERROR: nueva config inválida, restaurada"
    exit 1
fi
```

#### A.2 — Permisos del script (`apendice-A.md:104`)
```bash
sudo chmod +x /etc/cron.hourly/update-cf-ips
```

#### A.3 — Agente Wazuh `/var/ossec/etc/ossec.conf` (`apendice-A.md:113-214`)
```xml
<ossec_config>
  <client_buffer>
    <disabled>no</disabled>
    <queue_size>5000</queue_size>
    <events_per_second>1000</events_per_second>
  </client_buffer>

  <logging>
    <log_format>plain</log_format>
  </logging>

  <!-- Syslog del sistema -->
  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/auth.log</location>
  </localfile>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/syslog</location>
  </localfile>

  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/kern.log</location>
  </localfile>

  <!-- Fail2Ban -->
  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/fail2ban.log</location>
  </localfile>

  <!-- nginx -->
  <localfile>
    <log_format>syslog</log_format>
    <location>/var/log/nginx/api.dominio.tld.access.log</location>
  </localfile>

  <!-- Laravel security log -->
  <localfile>
    <log_format>json</log_format>
    <location>/var/www/api.dominio.tld/storage/logs/security.log</location>
  </localfile>

  <!-- FIM sobre rutas sensibles -->
  <syscheck>
    <disabled>no</disabled>
    <frequency>21600</frequency>
    <scan_on_start>yes</scan_on_start>

    <directories check_all="yes" realtime="yes">/etc</directories>
    <directories check_all="yes" realtime="yes">/var/www</directories>
    <directories check_all="yes" realtime="yes">/etc/nginx</directories>
    <directories check_all="yes" realtime="yes">/etc/php</directories>
    <directories check_all="yes" realtime="yes">/etc/systemd</directories>

    <ignore>/etc/mtab</ignore>
    <ignore>/etc/hosts.deny</ignore>
    <ignore>/etc/adjtime</ignore>
  </syscheck>

  <!-- Auditd -->
  <localfile>
    <log_format>audit</log_format>
    <location>/var/log/audit/audit.log</location>
  </localfile>

  <rootcheck>
    <disabled>no</disabled>
    <check_files>yes</check_files>
    <check_trojans>yes</check_trojans>
    <check_dev>yes</check_dev>
    <check_system>yes</check_system>
    <check_policies>yes</check_policies>
  </rootcheck>

  <syscollector>
    <disabled>no</disabled>
    <interval>1d</interval>
    <scan_on_start>yes</scan_on_start>
    <hardware>yes</hardware>
    <os>yes</os>
    <network>yes</network>
    <packages>yes</packages>
    <ports>yes</ports>
    <processes>yes</processes>
  </syscollector>

  <active-response>
    <disabled>no</disabled>
    <!-- Respuesta automática: bloquear IP tras 5 intentos fallidos -->
    <repeated_offenders timeout="60">10.10.10.10</repeated_offenders>
  </active-response>

  <server>
    <address>MANAGER_IP</address>
    <port>1514</port>
    <protocol>tcp</protocol>
  </server>
</ossec_config>
```

#### A.3 — Reglas custom Laravel (`apendice-A.md:219-237`)
```xml
<group name="laravel">
  <rule id="100100" level="5">
    <decoded_as>json</decoded_as>
    <field name="event">login_failed</field>
    <description>Laravel: login fallido</description>
  </rule>
  <rule id="100101" level="12">
    <decoded_as>json</decoded_as>
    <field name="event">permission_denied</field>
    <description>Laravel: intento de acceso sin permiso</description>
  </rule>
  <rule id="100102" level="10">
    <decoded_as>json</decoded_as>
    <field name="event">role_changed</field>
    <description>Laravel: cambio de rol</description>
  </rule>
</group>
```

#### A.4 — `/usr/local/bin/backup-mysql.sh` (`apendice-A.md:245-279`)
```bash
#!/bin/bash
# Backup diario de MySQL con gpg --symmetric AES256
set -euo pipefail
TS=$(date +%Y%m%d-%H%M%S)
TMPDIR=$(mktemp -d)
trap "rm -rf $TMPDIR; shred -u $TMPDIR/dump.sql 2>/dev/null || true" EXIT

mysqldump \
    --user=backup_user \
    --password="${MYSQL_BACKUP_PASSWORD}" \
    --single-transaction \
    --routines --triggers --events --hex-blob \
    --default-character-set=utf8mb4 \
    laravel_db > "$TMPDIR/dump.sql"

gpg --batch --yes --symmetric \
    --cipher-algo AES256 \
    --compress-algo zlib \
    --passphrase-file /etc/dropbear/backup-gpg-passphrase \
    --output "/var/backups/mysql/laravel-$TS.sql.gz.gpg" \
    < "$TMPDIR/dump.sql"

# Subir a DO Spaces cifrado adicional (server-side)
aws s3 cp "/var/backups/mysql/laravel-$TS.sql.gz.gpg" \
    "s3://backup-vps-secure/mysql/laravel-$TS.sql.gz.gpg" \
    --endpoint-url https://nyc3.digitaloceanspaces.com \
    --sse AES256 \
    || logger -t backup "ERROR: upload a DO Spaces falló"

# Limpieza local: mantener 7 días
find /var/backups/mysql -name "*.gpg" -mtime +7 -delete

logger -t backup-mysql "Backup completado: laravel-$TS.sql.gz.gpg"
```

#### A.4 — `/usr/local/bin/backup-postgres.sh` (`apendice-A.md:283-313`)
```bash
#!/bin/bash
set -euo pipefail
TS=$(date +%Y%m%d-%H%M%S)
TMPDIR=$(mktemp -d)
trap "rm -rf $TMPDIR" EXIT

pg_dump \
    --username=backup_user \
    --dbname=laravel_db \
    --format=custom \
    --no-owner \
    --no-acl \
    --compress=9 \
    > "$TMPDIR/dump.sqlc"

gpg --batch --yes --symmetric \
    --cipher-algo AES256 \
    --passphrase-file /etc/dropbear/backup-gpg-passphrase \
    --output "/var/backups/postgres/laravel-$TS.sqlc.gpg" \
    < "$TMPDIR/dump.sqlc"

aws s3 cp "/var/backups/postgres/laravel-$TS.sqlc.gpg" \
    "s3://backup-vps-secure/postgres/laravel-$TS.sqlc.gpg" \
    --endpoint-url https://nyc3.digitaloceanspaces.com \
    --sse AES256 \
    || logger -t backup "ERROR: upload DO Spaces"

find /var/backups/postgres -name "*.gpg" -mtime +7 -delete
logger -t backup-postgres "Backup completado: laravel-$TS.sqlc.gpg"
```

#### A.5 — `/etc/systemd/system/wazuh-exporter.service` (`apendice-A.md:321-342`)
```ini
[Unit]
Description=Wazuh exporter for Prometheus
After=network.target

[Service]
Type=simple
User=prometheus
ExecStart=/usr/local/bin/wazuh-exporter --listen-address 127.0.0.1:9093
Restart=on-failure
RestartSec=5

# Hardening
NoNewPrivileges=yes
ProtectSystem=strict
ProtectHome=yes
PrivateTmp=yes
ReadWritePaths=/var/log/wazuh

[Install]
WantedBy=multi-user.target
```

#### A.5 — `/etc/prometheus/prometheus.yml` (`apendice-A.md:346-371`)
```yaml
scrape_configs:
  - job_name: 'wazuh'
    static_configs:
      - targets: ['127.0.0.1:9093']

  - job_name: 'mysqld'
    static_configs:
      - targets: ['127.0.0.1:9104']

  - job_name: 'postgres'
    static_configs:
      - targets: ['127.0.0.1:9187']

  - job_name: 'redis'
    static_configs:
      - targets: ['127.0.0.1:9121']

  - job_name: 'nginx'
    static_configs:
      - targets: ['127.0.0.1:9113']

  - job_name: 'node'
    static_configs:
      - targets: ['127.0.0.1:9100']
```

#### A.6 — `.github/workflows/ci.yml` (`apendice-A.md:379-447`)
```yaml
# CI: lint + test + scan en cada PR
name: CI
on:
  pull_request:
    branches: [main, 13.x]
  push:
    branches: [main, 13.x]

# Permisos mínimos
permissions:
  contents: read

jobs:
  lint:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af68340f9f97f9c5b6e1f10b97b6b  # v4.2.2
        with:
          persist-credentials: false
      - uses: shivammathur/setup-php@9b03234e3b5386951600b261c182ec56a7e8c1254d8988c7e9b0f9f3f5fb95aa  # 2.32.0
        with:
          php-version: '8.3'
      - run: composer install --no-interaction --prefer-dist
      - run: vendor/bin/pint --test

  test:
    runs-on: ubuntu-24.04
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: test
        ports: ['3306:3306']
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=5
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af68340f9f97f9c5b6e1f10b97b6b
        with:
          persist-credentials: false
      - uses: shivammathur/setup-php@9b03234e3b5386951600b261c182ec56a7e8c1254d8988c7e9b0f9f3f5fb95aa
        with:
          php-version: '8.3'
          extensions: mbstring, dom, fileinfo, mysql, pgsql, sqlite, redis
      - run: composer install --no-interaction --prefer-dist
      - run: php artisan test --coverage --min=70

  scan:
    runs-on: ubuntu-24.04
    permissions:
      contents: read
      security-events: write  # para subir SARIF a Code Scanning
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af68340f9f97f9c5b6e1f10b97b6b
        with:
          persist-credentials: false
      - name: Trivy filesystem scan
        uses: aquasecurity/trivy-action@57a6f8872fa6c1f4f4c1eb1a33259b51e3a7d6c2f9d5b9d4ddc2d5fd9b9e3a78  # 0.28.0
        with:
          scan-type: fs
          scan-ref: .
          severity: HIGH,CRITICAL
          format: sarif
          output: trivy-fs.sarif
      - name: Upload to Code Scanning
        uses: github/codeql-action/upload-sarif@4e828ff8d448a8a6e532957a7e73e516e9ad98e0c4b6f3a5c25f9b3c8e9e3e9c  # v3.28.18
        if: always()
        with:
          sarif_file: trivy-fs.sarif
```

#### A.6 — `.github/workflows/deploy.yml` (`apendice-A.md:451-501`)
```yaml
# Deploy a DigitalOcean via OIDC (sin secretos estáticos)
name: Deploy
on:
  push:
    branches: [main]
  workflow_dispatch:

# Ambiente protegido con reviewers requeridos
environment:
  name: production
  url: https://api.dominio.tld

permissions:
  contents: read
  id-token: write  # OIDC para DigitalOcean

jobs:
  deploy:
    runs-on: ubuntu-24.04
    steps:
      - uses: actions/checkout@11bd71901bbe5b1630ceea73d27597364c9af68340f9f97f9c5b6e1f10b97b6b
        with:
          persist-credentials: false

      - uses: aws-actions/configure-aws-credentials@e3dd6a429d7300a6a4c196c26e071d42e0343502cdf9020dde2276728b7fa4b0  # v4.0.2
        with:
          role-to-assume: ${{ secrets.DO_OIDC_ROLE_ARN }}
          aws-region: us-east-1

      - name: Push to Spaces
        run: |
          aws s3 sync ./build/ s3://myapp-static/ \
              --endpoint-url https://nyc3.digitaloceanspaces.com \
              --delete

      - name: Notify droplet via SSH (llave restringida)
        uses: appleboy/ssh-action@4f87b3a9d7e9e9c9e9d7e9c9e9d7e9c9e9d7e9c9e9d7e9c9e9d7e9c9e9d7e9c9  # v1.0.3
        with:
          host: ${{ secrets.DROPLET_HOST }}
          username: deployer
          key: ${{ secrets.DEPLOY_SSH_KEY }}
          script: |
            cd /var/www/api.dominio.tld
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            sudo systemctl reload php8.3-fpm
```

#### A.7 — ModSecurity V3 + OWASP CRS (`apendice-A.md:509-521`)
```bash
sudo apt install -y libnginx-mod-http-modsecurity
sudo cp /etc/modsecurity/modsecurity.conf-recommended /etc/modsecurity/modsecurity.conf

# Activar motor
sudo sed -i 's/SecRuleEngine DetectionOnly/SecRuleEngine On/' /etc/modsecurity/modsecurity.conf

# Cargar OWASP CRS
sudo curl -L https://github.com/coreruleset/coreruleset/archive/refs/tags/v4.0.0.tar.gz \
    | sudo tar -xz -C /etc/modsecurity --strip-components=1 --wildcards '*/rules/*.conf' '*/crs-setup.conf.example'

sudo mv /etc/modsecurity/crs-setup.conf.example /etc/modsecurity/crs-setup.conf
```

#### A.7 — Directivas en `nginx.conf` (`apendice-A.md:525-528`)
```nginx
modsecurity on;
modsecurity_rules_file /etc/modsecurity/modsecurity.conf;
```

> Cierre literal del apéndice: «Exclusiones (ya mostradas en cap-03 §3.15.3).» (`apendice-A.md:530`)


#### Hook de Certbot — **NO está en el Apéndice A**; vive en `capitulo-03-parte-b.md:61-77`
```bash
`/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh`:

#!/bin/sh
# Recarga nginx tras una renovación exitosa
# Documentado en https://eff-certbot.readthedocs.io/en/latest/using.html#renewal-hook
set -e
if [ "$RENEWED_LINEAGE" ]; then
    # Test primero para evitar tirar nginx si la config está rota
    nginx -t
    systemctl reload nginx
    logger -t certbot "Certificado renovado y nginx recargado: $RENEWED_LINEAGE"
fi

sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
```

#### Script de verificación final del Checklist (`apendice-C.md:175-237`) — **NO está en el Apéndice A**
```bash
#!/bin/bash
# Ejecuta este script en el droplet ANTES de declararlo producción
# Salida: PASS/FAIL por item, exit 1 si cualquier FAIL

set -e
PASS=0
FAIL=0

check() {
    local desc="$1"
    local cmd="$2"
    if eval "$cmd" > /dev/null 2>&1; then
        echo "PASS  $desc"
        PASS=$((PASS+1))
    else
        echo "FAIL  $desc"
        FAIL=$((FAIL+1))
    fi
}

echo "=== Cap-01: SO Hardening ==="
check "Ubuntu 24.04 LTS" "test -f /etc/lsb-release && grep -q noble /etc/lsb-release"
check "unattended-upgrades activo" "systemctl is-active --quiet unattended-upgrades || systemctl is-active --quiet apt-daily.timer"
check "root sin shell" "grep -q '/usr/sbin/nologin' /etc/passwd && grep '^root:' /etc/passwd | grep -q nologin"
check "PermitRootLogin no" "sshd -T 2>/dev/null | grep -q 'permitrootlogin no'"
check "PasswordAuthentication no" "sshd -T 2>/dev/null | grep -q 'passwordauthentication no'"
check "auditd activo" "systemctl is-active --quiet auditd"
check "AppArmor enforce" "aa-status 2>/dev/null | grep -c 'enforce' | awk '{exit !(\$1 >= 4)}'"

echo "=== Cap-02: Red ==="
check "DO Cloud Firewall" "doctl compute firewall list 2>/dev/null | grep -q 'default'"
check "UFW activo" "ufw status | grep -q 'Status: active'"
check "nftables activo" "systemctl is-active --quiet nftables.service"
check "Fail2Ban activo" "systemctl is-active --quiet fail2ban"
check "nginx rate limit" "grep -q 'limit_req_zone' /etc/nginx/conf.d/rate-limit.conf"

echo "=== Cap-03: Nginx + TLS ==="
check "nginx 1.29" "nginx -v 2>&1 | grep -q '1.29'"
check "TLS 1.3 OK" "echo | openssl s_client -connect api.dominio.tld:443 -tls1_3 2>/dev/null | grep -q 'Protocol.*TLSv1.3'"
check "HSTS presente" "curl -sI https://api.dominio.tld/ 2>/dev/null | grep -qi 'strict-transport-security: max-age=63072000'"
check "CSP presente" "curl -sI https://api.dominio.tld/ 2>/dev/null | grep -qi 'content-security-policy'"
check "X-Frame-Options DENY" "curl -sI https://api.dominio.tld/ 2>/dev/null | grep -qi 'x-frame-options: DENY'"

echo "=== Cap-04: Datos ==="
check "MySQL solo 127.0.0.1" "! ss -tlnp | grep -E '0.0.0.0:3306|:::3306'"
check "Redis solo 127.0.0.1" "! ss -tlnp | grep -E '0.0.0.0:6379|:::6379'"
check "MySQL sin networking o bind 127" "ss -tlnp | grep 3306 | grep -q 127.0.0.1"
check "Redis autenticado" "redis-cli -h 127.0.0.1 PING 2>&1 | grep -q NOAUTH"
check "Backups cifrados" "ls /var/backups/mysql/*.gpg 2>/dev/null | head -1 | xargs -I{} gpg --list-packets {} 2>/dev/null | grep -q 'AES256'"

echo "=== Cap-05: App + CI/CD ==="
check "APP_DEBUG false" "grep -q 'APP_DEBUG=false' /var/www/api.dominio.tld/.env"
check "APP_KEY presente" "grep -q 'APP_KEY=base64:' /var/www/api.dominio.tld/.env"
check "VITE sin secretos" "! grep -rE 'API_KEY|SECRET|TOKEN' /var/www/frontend/dist/ 2>/dev/null | grep -v node_modules"
check "Wazuh agent activo" "systemctl is-active --quiet wazuh-agent"
check "Trivy scanner presente" "command -v trivy"

echo
echo "Resultados: $PASS PASS, $FAIL FAIL"
[ $FAIL -eq 0 ] && echo "LISTO PARA PRODUCCIÓN" || echo "HAY $FAIL ITEMS FALLIDOS — REVISAR ANTES DE PROD"
exit $FAIL
```

> Instrucción literal de uso: «Guárdalo como `/usr/local/bin/pre-prod-check.sh` y ejecútalo antes de cada deploy importante.» (`apendice-C.md:239`)

### 1.3 Clasificación: nativo vs trasladable a contenedores

| Artefacto | ¿Nativo del SO? | ¿Trasladable a Docker? | Observación de traslado |
|---|---|---|---|
| `cloudflare-api.conf` + `jail.local` (A.1) | Sí (Fail2Ban del host) | **Parcial** | Fail2Ban necesita ver logs del kernel/host y escribir reglas de ban (nftables) → contenedor privilegiado o `network_mode: host`. Alternativa: mover el ban a Cloudflare edge (ya implementado) y dejar el jail en el host. |
| `update-cf-ips` (A.2) | Sí (cron.hourly + reload nginx) | **No útil** | Si nginx va en contenedor, el reload debe ser `docker exec nginx nginx -s reload`; el `systemctl reload nginx` de la línea 94 no aplica. |
| `ossec.conf` + `laravel_rules.xml` (A.3) | Sí | **Parcial** | El agente Wazuh puede correr en contenedor, pero `syscheck realtime` sobre `/etc`, `/var/www`, `/etc/nginx`, `/etc/php`, `/etc/systemd`, `rootcheck`, `syscollector` y el lectura de `/var/log/audit/audit.log` requieren montar el host (namespaces/privilegios) y pierden sentido sobre un FS efímero de contenedor. |
| `backup-mysql.sh` / `backup-postgres.sh` (A.4) | Sí | **Sí, es lo más trasladable** | Es un job batch: contenedor con `mysqldump`/`pg_dump`, la passphrase GPG y credenciales AWS inyectadas por secret. Ojo: la ruta `/etc/dropbear/backup-gpg-passphrase` (línea 264/301) es un path de host. |
| `wazuh-exporter.service` + `prometheus.yml` (A.5) | Sí | **Sí** | El unit systemd se sustituye por el servicio del compose (mismos flags); el hardening `ProtectSystem/PrivateTmp` se traduce a `read_only`, `cap_drop: ALL`, `security_opt: no-new-privileges`. |
| `ci.yml` / `deploy.yml` (A.6) | No (CI en GitHub) | **No aplica** | Pero el `deploy.yml` **asume host nativo**: `git pull`, `composer install` y `sudo systemctl reload php8.3-fpm` (líneas 495-500) no funcionan contra contenedores. |
| ModSecurity (A.7) | Sí (módulo dinámico de nginx) | **No directo** | `apt install libnginx-mod-http-modsecurity` instala el módulo compilado para el nginx de Ubuntu; incompatible con el nginx de nginx.org (ver D-07 en §6). |
| Hook Certbot | Sí (Certbot del host) | **Parcial** | `systemctl reload nginx` debe convertirse en reload del contenedor. |
| `pre-prod-check.sh` | Sí (script de host) | **Parcial** | Llama a `systemctl is-active`, `aa-status`, `ufw`, `sshd -T`, `ss` del host: en un modelo contenedor la mayoría de los checks cambian de destino. |

---

## 2. Apéndice B — Tabla maestra de configuración

### 2.1 Estructura

El apéndice se organiza en **9 secciones (B.1–B.9)**, cada una correspondiente a **un archivo o plano de configuración**, y cada sección es **una tabla markdown de 5 columnas** (encabezado literal, `apendice-B.md:9-10`):

```
| Opción | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
```
(las secciones B.2, B.5, B.6, B.7 usan `| Clave |`, `| Variable |`, `| Variable |`, `| Variable |` respectivamente; B.4 usa `| Header |`, B.8 `| Práctica |`, B.9 `| Directiva |` — `apendice-B.md:35`, `:75`, `:91`, `:108`, `:127`, `:149`, `:167`).

**Conteo exacto de filas:**

| Sección | Archivo / plano | Filas de datos |
|---|---|---|
| B.1 | `/etc/ssh/sshd_config` | **21** |
| B.2 | `/etc/sysctl.d/99-hardening.conf` | **18** |
| B.3 | `/etc/nginx/conf.d/ssl-params.conf` | **12** |
| B.4 | nginx security-headers (sin archivo explícito) | **11** |
| B.5 | `/etc/mysql/mysql.conf.d/hardening.cnf` | **12** |
| B.6 | `/etc/redis/redis.conf` | **14** |
| B.7 | Laravel 13 (`.env` production) | **17** |
| B.8 | GitHub Actions (workflow YAML) | **13** |
| B.9 | systemd hardening (cualquier unit) | **16** |
| | **TOTAL** | **134 filas** |

Verificación mecánica: 152 líneas que empiezan por `|` = 134 filas de datos + 9 encabezados + 9 separadores.

### 2.2 Tabla maestra (transcripción literal de las 134 filas, agrupada por archivo)

> Se reproduce el contenido exacto del apéndice; los números entre paréntesis indican el rango de líneas de origen.

#### B.1 OpenSSH server (`/etc/ssh/sshd_config`) (`apendice-B.md:7`)
| Opción | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `Port` | 22 | 22 (con port knocking) o custom | Cambio de puerto reduce ruido automatizado; port knocking real reduce superficie | man sshd_config §Port |
| `PermitRootLogin` | `prohibit-password` | `no` | Root nunca por SSH; forzar admin → sudo | [ubuntu.com/server/docs/security.openssh-server, 2024-05] |
| `PasswordAuthentication` | `yes` | `no` | Solo claves SSH; contraseñas son vector de brute-force | [ubuntu.com/server/docs/security.openssh-server, 2024-05] |
| `KbdInteractiveAuthentication` | `yes` | `no` | Evita PAM interactivo en SSH | man sshd_config |
| `PubkeyAuthentication` | `yes` | `yes` | Habilitado por defecto; verificar | — |
| `MaxAuthTries` | 6 | 3 | Limitar brute-force | [wiki.mozilla.org/Security/Server_Side_TLS, 2025-04] |
| `MaxSessions` | 10 | 5 | Limitar túneles concurrentes | man sshd_config |
| `LoginGraceTime` | 2m | 30s | Reduce ventana de conexión | man sshd_config |
| `ClientAliveInterval` | 0 | 300 | Desconectar clientes inactivos 5min | man sshd_config |
| `ClientAliveCountMax` | 3 | 2 | 2×300s = 10min para desconectar | man sshd_config |
| `AllowUsers` | (sin restricción) | `admin deployer` | Solo usuarios explícitos | man sshd_config |
| `AllowGroups` | (sin restricción) | `ssh-users` | Grupo explícito | man sshd_config |
| `HostKey /etc/ssh/ssh_host_ed25519_key` | existe | existe | Solo Ed25519, no RSA/ECDSA | [openssh.com/manual.html, 2024-12] |
| `KexAlgorithms` | global | `curve25519-sha256,curve25519-sha256@libssh.org,diffie-hellman-group16-sha512,diffie-hellman-group18-sha512` | Solo KEX modernos | [mozilla.org/Mage/SSH, 2025-04] |
| `Ciphers` | global | `chacha20-poly1305@openssh.com,aes256-gcm@openssh.com,aes128-gcm@openssh.com,aes256-ctr,aes192-ctr,aes128-ctr` | Solo AEAD | [mozilla.org/Mage/SSH, 2025-04] |
| `MACs` | global | `hmac-sha2-512-etm@openssh.com,hmac-sha2-256-etm@openssh.com,umac-128-etm@openssh.com,hmac-sha2-512,hmac-sha2-256` | Solo MAC modernos | [mozilla.org/Mage/SSH, 2025-04] |
| `AuthenticationMethods` | (none) | `publickey,keyboard-interactive` | Forzar 2FA | man sshd_config §AuthenticationMethods |
| `Banner` | (none) | `/etc/issue.net` | Aviso legal | man sshd_config |
| `X11Forwarding` | (varies) | `no` | X11 no necesario en server | man sshd_config |
| `AllowTcpForwarding` | `yes` | `local` (para túnel SSH admin) o `no` | Si admin necesita túnel a DBs | man sshd_config |
| `AllowAgentForwarding` | `yes` | `no` | Reduce superficie | man sshd_config |

#### B.2 sysctl (`/etc/sysctl.d/99-hardening.conf`) (`apendice-B.md:33`)
| Clave | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `net.ipv4.ip_forward` | 0 | 0 | No es router | kernel docs |
| `net.ipv4.conf.all.rp_filter` | 0 | 1 | Reverse-path filter anti-spoofing | RFC 3704 |
| `net.ipv4.icmp_echo_ignore_broadcasts` | 0 | 1 | Anti-smurf | kernel docs |
| `net.ipv4.conf.all.accept_redirects` | 1 | 0 | No aceptar ICMP redirects | kernel docs |
| `net.ipv4.conf.all.send_redirects` | 1 | 0 | No enviar redirects | kernel docs |
| `net.ipv4.conf.all.secure_redirects` | 1 | 0 | No aceptar secure redirects | kernel docs |
| `net.ipv4.conf.all.log_martians` | 0 | 1 | Log paquetes sospechosos | kernel docs |
| `net.ipv4.icmp_ignore_bogus_error_responses` | 0 | 1 | Ignorar errores bogus | kernel docs |
| `net.ipv4.tcp_syncookies` | 1 | 1 | Anti SYN flood | kernel docs |
| `net.ipv4.tcp_rfc1337` | 0 | 1 | Anti TIME_WAIT assassination | RFC 1337 |
| `net.ipv6.conf.all.accept_redirects` | 1 | 0 | IPv6 hardening | kernel docs |
| `net.ipv6.conf.all.accept_ra` | 1 | 0 | No aceptar Router Advertisements | kernel docs |
| `kernel.randomize_va_space` | 2 | 2 | Full ASLR | kernel docs |
| `kernel.kptr_restrict` | 1 | 2 | No exponer kernel pointers a no-root | kernel docs |
| `kernel.dmesg_restrict` | 1 | 1 | dmesg solo root | kernel docs |
| `kernel.yama.ptrace_scope` | 1 | 2 | Restringir ptrace | kernel docs |
| `fs.protected_hardlinks` | 0 | 1 | Hardlinks protegidos | kernel docs |
| `fs.protected_symlinks` | 0 | 1 | Symlinks protegidos | kernel docs |

#### B.3 nginx (`/etc/nginx/conf.d/ssl-params.conf`) (`apendice-B.md:56`)
| Opción | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `ssl_protocols` | `TLSv1 TLSv1.1 TLSv1.2` | `TLSv1.2 TLSv1.3` | TLS 1.0/1.1 deprecados | RFC 8996 |
| `ssl_ciphers` | (HIGH:!aNULL) | ECDHE-ECDSA-AES128/256-GCM-SHA256/384 + CHACHA20 | Mozilla intermediate | [ssl-config.mozilla.org, 2025-09] |
| `ssl_prefer_server_ciphers` | `off` | `on` | En TLS 1.2 (no aplica a 1.3) | nginx docs |
| `ssl_session_cache` | `none` | `shared:SSL:10m` | 10MB de cache | nginx docs |
| `ssl_session_timeout` | `5m` | `1d` | 1 día | nginx docs |
| `ssl_session_tickets` | `on` | `off` | Forward secrecy entre sesiones | [wiki.mozilla.org/Security/Server_Side_TLS, 2025-04] |
| `ssl_stapling` | `off` | `on` | OCSP stapling | RFC 6066 |
| `ssl_stapling_verify` | `off` | `on` | Verificar respuesta OCSP | RFC 6066 |
| `ssl_early_data` | `off` | `on` (con mitigación) | 0-RTT TLS 1.3 | RFC 8446 §8 |
| `ssl_dhparam` | (none) | `2048` o generado | DH param para DHE | nginx docs |
| `add_header Strict-Transport-Security` | (none) | `max-age=63072000; includeSubDomains; preload` | HSTS 2 años | RFC 6797 |
| `ssl_verify_client` | `off` | `on` (con mTLS Cloudflare) | AOP contra bypass | [developers.cloudflare.com/ssl/origin-configuration/authenticated-origin-pull, 2025-09] |

#### B.4 nginx security-headers (`apendice-B.md:73`)
| Header | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `X-Frame-Options` | (none) | `DENY` | Clickjacking | [owasp.org/www-project-secure-headers, 2025-09] |
| `X-Content-Type-Options` | (none) | `nosniff` | MIME sniffing | OWASP |
| `Referrer-Policy` | (none) | `strict-origin-when-cross-origin` | Privacidad referrer | OWASP |
| `Permissions-Policy` | (none) | `camera=(), microphone=(), geolocation=(), payment=()` | Restringir APIs del navegador | [w3.org/TR/permissions-policy, 2025-09] |
| `Content-Security-Policy` | (none) | `default-src 'none'; frame-ancestors 'none'; base-uri 'none'` (API) | Defensa XSS profunda | [w3.org/TR/CSP3, 2025-09] |
| `Cross-Origin-Opener-Policy` | (none) | `same-origin` | Cross-origin isolation | [resourcepolicy.fyi, 2025-09] |
| `Cross-Origin-Resource-Policy` | (none) | `same-origin` | Anti Spectre | [resourcepolicy.fyi, 2025-09] |
| `Cross-Origin-Embedder-Policy` | (none) | `require-corp` | Cross-origin embedder | [resourcepolicy.fyi, 2025-09] |
| `X-XSS-Protection` | (none) | `0` | Deprecado, forzar 0 | [owasp.org/www-project-secure-headers, 2025-09] |
| `Server` | `nginx/1.29.0` | (eliminado) | No exponer versión | nginx docs |
| `X-Powered-By` | (PHP) | (eliminado) | No exponer stack | nginx docs + headers-more |

#### B.5 MySQL (`/etc/mysql/mysql.conf.d/hardening.cnf`) (`apendice-B.md:89`)
| Variable | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `bind-address` | `127.0.0.1` | `127.0.0.1` | No escuchar en 0.0.0.0 | dev.mysql.com |
| `skip-networking` | 0 | 1 (recomendado) | Solo socket Unix | dev.mysql.com |
| `skip-name-resolve` | 0 | 1 | Evitar DNS lookups | dev.mysql.com |
| `local-infile` | 1 | 0 | Bloquear LOAD DATA LOCAL | dev.mysql.com |
| `max_connections` | 151 | 200 | Ajuste por carga esperada | dev.mysql.com |
| `require_secure_transport` | 0 | 1 | TLS obligatorio | dev.mysql.com |
| `ssl-ca`, `ssl-cert`, `ssl-key` | (none) | `/etc/mysql/certs/...` | Certificados para TLS | dev.mysql.com |
| `caching_sha2_password` | (plugin) | plugin activo | SHA1 deprecated | dev.mysql.com |
| `mysql_native_password` | (plugin) | NO usar | SHA1 vulnerable | [dev.mysql.com/doc/refman/8.0/en/native-authentication.html, 2025-09] |
| `slow_query_log` | 0 | 1 | Monitoreo | dev.mysql.com |
| `long_query_time` | 10 | 2 | Detección temprana | dev.mysql.com |
| `server_audit` (plugin) | (none) | MariaDB Audit | Auditoría de sentencias | [mariadb.com/kb/en/mariadb-audit-plugin, 2025-09] |

#### B.6 Redis 7 (`/etc/redis/redis.conf`) (`apendice-B.md:106`)
| Variable | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `bind` | `127.0.0.1` | `127.0.0.1 ::1` | Sin 0.0.0.0 | [redis.io/docs/latest/operate/oss_and_stack/management/security/, 2025-09] |
| `protected-mode` | `yes` | `yes` | Doble seguro | redis docs |
| `port` | 6379 | 6379 | o tls-port 6380 | redis docs |
| `requirepass` | (none) | `<64+ chars>` | Obligatorio | redis docs |
| `rename-command FLUSHDB` | (FLUSHDB) | `""` (bloqueado) | Anti wipe | redis docs |
| `rename-command CONFIG` | (CONFIG) | `"CONFIG_RENAMED"` | Anti recon | redis docs |
| `aclfile` | (none) | `/etc/redis/users.acl` | ACL granular | redis docs |
| `tls-port` | (none) | `6380` (si aplica) | Cifrado en tránsito | redis docs |
| `maxmemory` | (none) | `2gb` | Límite de uso | redis docs |
| `maxmemory-policy` | `noeviction` | `allkeys-lru` | Cache use case | redis docs |
| `appendonly` | `no` | `yes` | Persistencia | redis docs |
| `appendfsync` | `everysec` | `everysec` | Balance seguridad/rendimiento | redis docs |
| `dir` | `./` | `/var/lib/redis` | Datos separados | redis docs |
| `loglevel` | `notice` | `notice` | Log eventos relevantes | redis docs |

#### B.7 Laravel 13 (`.env` production) (`apendice-B.md:125`)
| Variable | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `APP_DEBUG` | `false` | `false` | NUNCA `true` en producción | [laravel.com/docs/13.x/configuration, 2025-09] |
| `APP_ENV` | `production` | `production` | — | laravel.com |
| `APP_KEY` | (generated) | Rotar trimestralmente | Cifrado de Laravel | [laravel.com/docs/13.x/encryption, 2025-09] |
| `APP_URL` | `http://localhost` | `https://api.dominio.tld` | HTTPS siempre | laravel.com |
| `LOG_CHANNEL` | `stack` | `stack` + security separado | Logs de seguridad | laravel.com |
| `SESSION_DRIVER` | `file` | `redis` | Sesiones centralizadas | laravel.com |
| `SESSION_SECURE_COOKIE` | `false` | `true` | Solo HTTPS | laravel.com |
| `SESSION_HTTP_ONLY` | `true` | `true` | Sin JS access | laravel.com |
| `SESSION_SAME_SITE` | `lax` | `lax` o `strict` | CSRF | laravel.com |
| `SANCTUM_STATEFUL_DOMAINS` | (empty) | `app.dominio.tld` | Dominios confiables | [laravel.com/docs/13.x/sanctum, 2025-09] |
| `TRUSTED_PROXIES` | (empty) | Rangos Cloudflare | `X-Forwarded-For` real | laravel.com |
| `CORS_ALLOWED_ORIGINS` | `*` | `https://app.dominio.tld` | Solo frontend | laravel.com |
| `DB_CONNECTION` | `sqlite` | `mysql` (o `pgsql`) | Producción | laravel.com |
| `DB_HOST` | `127.0.0.1` | `127.0.0.1` | NUNCA público | laravel.com |
| `DB_SOCKET` | (empty) | `/var/run/mysqld/mysqld.sock` | Más rápido que TCP | dev.mysql.com |
| `REDIS_HOST` | `127.0.0.1` | `127.0.0.1` | NUNCA público | laravel.com |
| `REDIS_PASSWORD` | (none) | `<64+ chars>` | Obligatorio | redis docs |

#### B.8 GitHub Actions (workflow YAML) (`apendice-B.md:147`)
| Práctica | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| Action pin | `@v4` (flotante) | SHA 40 chars + comentario versión | Inmutabilidad | [docs.github.com/actions/security-guides/security-hardening-for-github-actions, 2025-09] |
| Permissions | `write-all` (implícito) | `permissions: contents: read` mínimo | Menor privilegio | docs.github.com |
| secrets: GITHUB_TOKEN | scope amplio | Scope mínimo | — | docs.github.com |
| Secret estático | `secrets.DIGITALOCEAN_TOKEN` | OIDC `id-token: write` + `aws-actions/configure-aws-credentials` con role | Sin secretos estáticos | docs.github.com |
| `persist-credentials: false` en checkout | `true` | `false` | No persiste .git/config token | docs.github.com |
| Branch protection | none | `required_status_checks`, `required_reviews` | Pre-merge checks | docs.github.com |
| Environment protection | none | `production` con reviewers requeridos | Pre-deploy approval | docs.github.com |
| Secret scanning | off | ON | Detección de leaks | docs.github.com |
| Push protection | off | ON | Bloqueo de push con secrets | docs.github.com |
| Dependabot | off | ON | PRs automáticas de actualización | docs.github.com |
| actionlint | not run | run en CI | Validar YAML | [rhysd/actionlint, 2025-09] |
| zizmor | not run | run en CI | Security lint | [woodruffw/zizmor, 2025-09] |
| gitleaks | not run | run en CI | Pre-commit y CI | [gitleaks/gitleaks, 2025-09] |

#### B.9 systemd hardening (cualquier unit) (`apendice-B.md:165`)
| Directiva | Default | Endurecido | Justificación | Fuente |
|---|---|---|---|---|
| `NoNewPrivileges` | no | `yes` | Bloquear escalation | man systemd.exec |
| `ProtectSystem` | no | `strict` | Filesystem read-only excepto paths explícitos | man systemd.exec |
| `ProtectHome` | no | `yes` | No acceso a /home, /root | man systemd.exec |
| `PrivateTmp` | no | `yes` | /tmp aislado | man systemd.exec |
| `PrivateDevices` | no | `yes` | No acceso a /dev excepto null, zero, etc. | man systemd.exec |
| `ProtectKernelTunables` | no | `yes` | /proc y /sys protegidos | man systemd.exec |
| `ProtectKernelModules` | no | `yes` | No carga módulos | man systemd.exec |
| `ProtectControlGroups` | no | `yes` | /sys/fs/cgroup protegido | man systemd.exec |
| `RestrictAddressFamilies` | (none) | `AF_UNIX AF_INET AF_INET6` | Solo sockets necesarios | man systemd.exec |
| `RestrictNamespaces` | no | `yes` | Anti-container escape | man systemd.exec |
| `RestrictRealtime` | no | `yes` | No scheduling real-time | man systemd.exec |
| `MemoryDenyWriteExecute` | no | `yes` | Anti shellcode | man systemd.exec |
| `CapabilityBoundingSet` | (todas) | (vacío) + AmbientCapabilities selectivas | Solo caps necesarias | man systemd.exec |
| `LockPersonality` | no | `yes` | No cambio de personalidad | man systemd.exec |
| `SystemCallArchitectures` | (todas) | `native` | Solo arquitectura del kernel | man systemd.exec |
| `ReadWritePaths` | (none) | `/var/log /var/lib /var/run` específicos | Solo escribir donde necesita | man systemd.exec |

> Nota: las líneas anteriores son copia literal; puede verificarse con `sed -n '9,184p' apendice-B.md`.

### 2.3 Secciones **ausentes** del Apéndice B (pese a que el entregable promete «cada opción de cada archivo», `apendice-B.md:3`, `deliverable.md:58`)

| Plano exigido por los capítulos/checklist | Evidencia de que existe | ¿En B? |
|---|---|---|
| **PostgreSQL** (listen_addresses, pg_hba, scram-sha-256, pgaudit, ssl_min_protocol_version) | `capitulo-04-parte-a.md:301-372`, `apendice-C.md:103-105` | **NO** |
| **nginx.conf** principal y rate-limit (`limit_req_zone`, `limit_conn`, timeouts anti-Slowloris) | `capitulo-03-parte-b.md:225-237`, `capitulo-02.md:1184`, `apendice-C.md:48-50` | **NO** |
| **PHP-FPM / php.ini / opcache** (paquete de config del runtime) | `capitulo-05.md:2060-2120` (`pm.max_children = 50`, `opcache.jit = tracing`, `disable_functions`) | **NO** |
| **Certbot / Let's Encrypt** | `apendice-C.md:70-73`, `capitulo-03-parte-b.md:11-96` | **NO** |
| **Fail2Ban** (jails, maxretry, bantime, banaction) | `apendice-A.md:23-57`, `capitulo-02.md:733-871`, `apendice-C.md:43-46` | **NO** |
| **Wazuh / ossec.conf** | `apendice-A.md:111-237` | **NO** |
| **UFW / nftables** | `apendice-C.md:36-42`, `capitulo-02.md:1-230` | **NO** |
| **Docker / docker-compose / Dockerfile** | `capitulo-05.md:1918-2060`, `apendice-C.md:140-142` | **NO** |
| **auditd / AIDE / ClamAV / AppArmor / unattended-upgrades** | `apendice-C.md:9-31` | **NO** |
| **Cifrado en reposo de MySQL (keyring/tablespace)** | `capitulo-04-parte-a.md:117`, `apendice-C.md:101` | **NO** (B.5 no tiene fila de `keyring_file`/`early-plugin-load`) |

---

## 3. Apéndice C — Checklist de aceptación

**Estructura:** 7 secciones. C.1 SO (`:7`), C.2 Red y borde (`:32`), C.3 Nginx y TLS (`:56`), C.4 MySQL/PostgreSQL/Redis (`:90`), C.5 Aplicación y CI/CD (`:117`), C.6 Documentación y procesos (`:156`), C.7 Comprobación final con un solo script (`:173`). Regla de uso literal: «Cada item es ejecutable. Marca `[x]` cuando verifiques. No se admite producción con items sin marcar.» (`apendice-C.md:3`).

**Nº de items:** C.1 = 23, C.2 = 20, C.3 = 32, C.4 = 24, C.5 = 37, C.6 = 12 → **148 items** de checklist (contando los objetivos de auditoría externa de C.3 como items). El script de C.7 solo automatiza **28 checks**.

### 3.1 Checklist completo y literal (`apendice-C.md:7-171`)

```markdown
## C.1 — Sistema operativo (post cap-01)

- [ ] Ubuntu 24.04 LTS actualizado: `apt list --upgradable` muestra 0 paquetes
- [ ] `unattended-upgrades` activo y programado
- [ ] `dpkg-reconfigure -plow unattended-upgrades` muestra origen `${distro_id}:${distro_codename}-security`
- [ ] Usuario `admin` con sudo existe; `root` no tiene shell interactivo (`sudo usermod -s /usr/sbin/nologin root`)
- [ ] `/etc/sudoers.d/admin` permite NOPASSWD solo para `systemctl reload nginx|php8.3-fpm`
- [ ] Clave SSH ed25519 del admin está en `~/.ssh/authorized_keys` con `command="..."` y `from="..."` restrictivos
- [ ] SSH config endurecido: `PermitRootLogin no`, `PasswordAuthentication no`, `MaxAuthTries 3`
- [ ] `sshd -T | grep -E "kex|cipher|mac"` muestra solo algoritmos Mozilla modern
- [ ] TOTP 2FA activo en `admin` y `deployer`: `google-authenticator` configurado
- [ ] Banner SSH en `/etc/issue.net`
- [ ] `sysctl net.ipv4.tcp_syncookies` = 1
- [ ] `sysctl net.ipv4.conf.all.rp_filter` = 1
- [ ] `auditd` activo, `auditctl -l` muestra reglas para /etc/passwd, /etc/shadow, /etc/sudoers, /etc/ssh/
- [ ] `ausearch -m USER_LOGIN,USER_AUTH -ts today` muestra eventos
- [ ] AppArmor en modo enforce: `aa-status` muestra al menos 4 profiles enforce (nginx, php-fpm, mysql, redis)
- [ ] AIDE base inicial creada: `aide --init` ejecutado, `aide.db` en `/var/lib/aide/`
- [ ] `aide --check` programado en cron.daily
- [ ] ClamAV instalado, `freshclam` actualizado, `clamscan -r /var/www` programado
- [ ] rkhunter programado, `rkhunter --check` no reporta "Rootkit"
- [ ] journald persistente: `journalctl --list-boots` muestra varios boots
- [ ] DigitalOcean Monitoring agent reporta métricas
- [ ] DigitalOcean Backups semanal activo

## C.2 — Red y borde (post cap-02)

- [ ] DO Cloud Firewall: solo 80, 443 + opcional 22 desde IP admin
- [ ] Outbound DO Firewall: restringido a 80, 443, 53, 123, 25
- [ ] UFW `incoming: deny (incoming)`, `outgoing: allow (outgoing)`
- [ ] UFW logging: `ufw status verbose` muestra `logging: on (medium)`
- [ ] UFW permite SSH desde IP admin: `ufw status | grep 22/tcp` muestra `ALLOW <IP_ADMIN>`
- [ ] nftables activo: `systemctl status nftables.service` running
- [ ] `nft list ruleset` muestra tabla inet filter con chains input, forward, output
- [ ] set de IPs bloqueadas (`blackhole`) presente
- [ ] SSH rate limit en nftables: `limit rate 6/minute` para tcp/22
- [ ] Fail2Ban activo: `systemctl status fail2ban` running
- [ ] `fail2ban-client status sshd` muestra jails activos
- [ ] `fail2ban-client status recidive` configurado con bantime 7 días
- [ ] Acción Cloudflare configurada: `/etc/fail2ban/action.d/cloudflare-api.conf` presente
- [ ] Cloudflare IPs en `/etc/nginx/conf.d/cloudflare-realip.conf` actualizadas (cron hourly)
- [ ] nginx limit_req_zone api:10m rate=10r/s cargado
- [ ] nginx limit_conn conn 50 cargado
- [ ] Slowloris mitigado: client_body_timeout 12s, send_timeout 10s en server blocks
- [ ] GeoIP bloqueando países no objetivo (si aplica, justificado)
- [ ] Cloudflare Bot Fight Mode ON
- [ ] Cloudflare WAF managed rules ON
- [ ] Cloudflare Rate Limit L7 para /api/* configurado

## C.3 — Nginx y TLS (post cap-03)

- [ ] nginx 1.29.x mainline: `nginx -v` muestra `nginx/1.29.x`
- [ ] `dpkg -s nginx | grep -i source` muestra nginx.org
- [ ] systemd unit hardening: `systemctl show nginx | grep -E "ProtectSystem|NoNewPrivileges|CapabilityBoundingSet"` correcto
- [ ] `/etc/nginx/nginx.conf` minimal, sin includes de sitios
- [ ] `/etc/nginx/conf.d/ssl-params.conf` con TLS 1.2/1.3, Mozilla intermediate
- [ ] `/etc/nginx/conf.d/security-headers.conf` con todos los headers
- [ ] `/etc/nginx/conf.d/rate-limit.conf` con zonas
- [ ] `/etc/nginx/conf.d/cloudflare-realip.conf` con rangos CF
- [ ] Site API `api.dominio.tld` con server block 443 + 80 → 301
- [ ] Site frontend `app.dominio.tld` con server block 443 + 80 → 301
- [ ] `nginx -t` pasa sin errores
- [ ] `systemctl reload nginx` sin errores
- [ ] Certbot: `certbot certificates` muestra certs para ambos dominios
- [ ] Renovación automática: `systemctl list-timers certbot.timer` muestra next run
- [ ] `certbot renew --dry-run` pasa
- [ ] Hook `/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh` con +x
- [ ] `openssl s_client -connect api.dominio.tld:443 -tls1_3` muestra `Protocol: TLSv1.3`
- [ ] `curl -I https://api.dominio.tld/` muestra `Strict-Transport-Security: max-age=63072000`
- [ ] `curl -I https://api.dominio.tld/` muestra `Content-Security-Policy`
- [ ] `curl -I https://app.dominio.tld/` muestra `X-Frame-Options: DENY`
- [ ] `curl -I https://app.dominio.tld/` muestra `Referrer-Policy`
- [ ] **SSL Labs:** https://www.ssllabs.com/ssltest/analyze.html?d=api.dominio.tld → **A+**
- [ ] **SSL Labs:** https://www.ssllabs.com/ssltest/analyze.html?d=app.dominio.tld → **A+**
- [ ] **Mozilla Observatory:** https://observatory.mozilla.org/analyze/api.dominio.tld → **A+**
- [ ] **securityheaders.com:** https://securityheaders.com/?q=api.dominio.tld → **A+**
- [ ] **HSTS Preload:** https://hstspreload.org/?domain=api.dominio.tld → **Eligible**
- [ ] HSTS enviado a hstspreload.org (opcional pero recomendado)
- [ ] Authenticated Origin Pulls ON en Cloudflare dashboard
- [ ] `curl --resolve api.dominio.tld:443:<IP_REAL> https://api.dominio.tld/` falla con error de certificado cliente
- [ ] `ss -tlnp | grep :443` muestra nginx escuchando solo en 443 (no en 80, ya redirigido)
- [ ] `access_log` de nginx muestra IP real del usuario (no IP de Cloudflare)

## C.4 — MySQL/PostgreSQL/Redis (post cap-04)

- [ ] `ss -tlnp | grep -E '3306|5432|6379|6380'` muestra SOLO 127.0.0.1, NUNCA 0.0.0.0
- [ ] mysql: `SHOW VARIABLES LIKE 'bind_address';` → `127.0.0.1`
- [ ] mysql: `SHOW VARIABLES LIKE 'require_secure_transport';` → `ON`
- [ ] mysql: `SHOW VARIABLES LIKE 'have_ssl';` → `YES`
- [ ] mysql: `SELECT user, host, plugin FROM mysql.user;` muestra `caching_sha2_password` para todos
- [ ] mysql: cero `mysql_native_password`
- [ ] mysql: 5 usuarios separados (root, app, migrate, bi_reader, backup, exporter)
- [ ] mysql: `SHOW GRANTS FOR 'laravel_app'@'127.0.0.1';` muestra solo CRUD mínimo
- [ ] mysql: `SHOW GRANTS FOR 'bi_reader'@'127.0.0.1';` muestra solo SELECT
- [ ] mysql: tablespaces cifrados: `SELECT name, encryption FROM information_schema.tables;`
- [ ] mysql: conexión sin TLS falla con `insecure transport` error
- [ ] pgsql: `listen_addresses = 'localhost'` en postgresql.conf
- [ ] pgsql: pg_hba.conf con scram-sha-256 (NO trust, NO md5)
- [ ] pgsql: `SELECT * FROM pg_hba_file_rules();` muestra solo local+hostssl+scram-sha-256
- [ ] redis: `bind 127.0.0.1` (NO 0.0.0.0)
- [ ] redis: `protected-mode yes`
- [ ] redis: `requirepass` configurado (64+ chars)
- [ ] redis: `CONFIG GET requirepass` sin AUTH falla
- [ ] redis: `ACL LIST` muestra `laravel_app` con permisos limitados
- [ ] redis: rename-command FLUSHDB, FLUSHALL, CONFIG, DEBUG, SHUTDOWN, KEYS deshabilitados o renombrados
- [ ] Túnel SSH funcional: `ssh -L 13306:127.0.0.1:3306 deployer@droplet` + `mysql -h 127.0.0.1 -P 13306 -u laravel_app -p` conecta
- [ ] Backups cifrados: `gpg --list-packets /var/backups/mysql/laravel-*.gpg` muestra AES256
- [ ] Backups a DO Spaces: `aws s3 ls s3://backup-vps-secure/mysql/` muestra archivos
- [ ] Drill de restore ejecutado en los últimos 30 días

## C.5 — Aplicación y CI/CD (post cap-05)

- [ ] `composer install --no-dev --optimize-autoloader` ejecutado
- [ ] `.env` NO en git (`.env.example` sí)
- [ ] `APP_DEBUG=false`, `APP_ENV=production`
- [ ] `APP_KEY` rotada en los últimos 90 días
- [ ] `APP_URL=https://api.dominio.tld`
- [ ] `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`
- [ ] `SANCTUM_STATEFUL_DOMAINS=app.dominio.tld`
- [ ] `TRUSTED_PROXIES` con rangos Cloudflare
- [ ] `CORS_ALLOWED_ORIGINS=https://app.dominio.tld` (NO `*`)
- [ ] Laravel Sanctum tokens con abilities: `php artisan tinker` + `auth()->user()->createToken('test', ['read:posts'])`
- [ ] Vue 3 build con `vite build` y `sourcemap: false`
- [ ] `dist/index.html` NO contiene secretos (grep -E "API_KEY|SECRET|TOKEN" dist/index.html)
- [ ] VITE_* solo contiene `VITE_APP_NAME`, `VITE_API_BASE_URL`
- [ ] GitHub Actions pinneadas a SHA de 40 chars
- [ ] `permissions: contents: read` en todos los workflows
- [ ] OIDC a DigitalOcean (no `secrets.DIGITALOCEAN_TOKEN`)
- [ ] `actionlint` pasa en CI
- [ ] `zizmor` pasa en CI
- [ ] `gitleaks` no detecta secretos
- [ ] `trivy fs --severity HIGH,CRITICAL .` sin hallazgos
- [ ] `trivy image php:8.3-fpm --severity HIGH,CRITICAL` sin hallazgos críticos
- [ ] Docker base: `dhi.io/php:8.3-fpm` o `dhi.io/node:20-alpine` (Docker Hardened Images)
- [ ] Dockerfile: `USER` no-root con UID numérico
- [ ] docker-compose: `read_only: true`, `cap_drop: ALL`, `security_opt: no-new-privileges:true`
- [ ] Branch protection en `main`: required_reviews ≥ 1, required_status_checks
- [ ] Environment `production` con reviewers requeridos
- [ ] Secret scanning ON en el repo
- [ ] Push protection ON en el repo
- [ ] Dependabot ON con auto-PRs de seguridad
- [ ] Wazuh agent activo: `systemctl status wazuh-agent`
- [ ] Wazuh FIM activo sobre /etc, /var/www, /etc/nginx, /etc/php
- [ ] Wazuh reglas custom Laravel en `/var/ossec/etc/rules/laravel_rules.xml`
- [ ] Alertas Slack/Telegram configuradas
- [ ] Prometheus node_exporter, mysqld_exporter, redis_exporter, wazuh-exporter activos
- [ ] Grafana dashboard con panel principal (CPU, RAM, disco, network, 5xx, bans)
- [ ] Uptime monitoring externo (UptimeRobot, Healthchecks.io)

## C.6 — Documentación y procesos

- [ ] `guia-maestra-seguridad.md` accesible al equipo
- [ ] Apéndice A (scripts) bajo control de versiones
- [ ] Apéndice B (tabla de configuración) revisado trimestralmente
- [ ] Apéndice C (este checklist) ejecutado en último deploy
- [ ] Apéndice D (referencias oficiales) actualizado
- [ ] Apéndice E (glosario) compartido con el equipo
- [ ] Runbook de incidentes (cap-05 §5.9) impreso/disponible
- [ ] Calendario de rotación de secretos: APP_KEY, DB passwords, SSH keys, master keys
- [ ] Política de backups probada con drill mensual
- [ ] Calendario de auditorías externas (trimestral)
- [ ] Plan de respuesta a incidentes con contactos actualizados
- [ ] Post-mortem obligatorio tras incidente real

---
```

### 3.2 Script bash de verificación

Transcrito íntegro en §1.2 de este informe (origen: `apendice-C.md:176-237`, guardado en `/usr/local/bin/pre-prod-check.sh` según `:239`).

### 3.3 Objetivos de calificación externa (literal)

| Verificación | Herramienta / URL | Valor esperado | Línea |
|---|---|---|---|
| SSL Labs API | `https://www.ssllabs.com/ssltest/analyze.html?d=api.dominio.tld` | **A+** | `apendice-C.md:79` |
| SSL Labs frontend | `https://www.ssllabs.com/ssltest/analyze.html?d=app.dominio.tld` | **A+** | `apendice-C.md:80` |
| Mozilla Observatory | `https://observatory.mozilla.org/analyze/api.dominio.tld` | **A+** | `apendice-C.md:81` |
| securityheaders.com | `https://securityheaders.com/?q=api.dominio.tld` | **A+** | `apendice-C.md:82` |
| HSTS Preload | `https://hstspreload.org/?domain=api.dominio.tld` | **Eligible** | `apendice-C.md:83` |
| Envío real a preload | «HSTS enviado a hstspreload.org (opcional pero recomendado)» | — | `apendice-C.md:84` |
| Variantes en cap-03 | SSL Labs api → A+ (`capitulo-03-parte-b.md:445`), Observatory api → A+ (`:446`), securityheaders api → A+ (`:447`), HSTS preload api → Eligible (`:448`), SSL Labs app → A+ (`:449`) | idem | `capitulo-03-parte-b.md:445-449` |

Nota: el checklist **no fija objetivo de calificación para `app.dominio.tld`** en Observatory ni securityheaders (solo SSL Labs), ni para `dominio.tld` raíz (relevante para preload, que es de dominio completo).

---

## 4. Apéndice D — Mapa de referencias oficiales

**Estructura:** 16 secciones (D.1–D.16). Regla declarada (literal): «Cada fuente usada en los capítulos, con URL exacta y fecha de acceso. Las fechas siguen el calendario de producción de la guía (septiembre 2026). Si una fuente cambia, documenta la nueva URL y la fecha del cambio.» (`apendice-D.md:3`).

### 4.1 Conteo exacto

**172 líneas de referencia** (líneas que contienen `https://`), distribuidas así:

| Sección | Tema | Refs |
|---|---|---|
| D.1 | Sistema operativo y kernel | 21 |
| D.2 | nginx y TLS | 26 |
| D.3 | Cloudflare | 13 |
| D.4 | DigitalOcean | 11 |
| D.5 | MySQL | 8 |
| D.6 | PostgreSQL | 8 |
| D.7 | Redis | 5 |
| D.8 | Firewall y red | 5 |
| D.9 | Laravel 13 | 16 |
| D.10 | Vue 3 + Vite | 11 |
| D.11 | GitHub Actions y CI/CD | 15 |
| D.12 | Docker y contenedores | 6 |
| D.13 | IDS, monitoring y respuesta a incidentes | 13 |
| D.14 | Backups | 4 |
| D.15 | MFA y 2FA | 2 |
| D.16 | Estándares y frameworks | 8 |
| | **TOTAL** | **172** |

Nota de conteo: la sección D.1 incluye un sub-bloque de 5 man-pages de Ubuntu Noble y D.2 encabeza con un índice de 5 módulos de nginx; si se cuentan **bloques temáticos** en vez de URLs, son **16 bloques / ~120 fuentes distintas**.

### 4.2 Transcripción literal (`apendice-D.md:7-226`)

```markdown
## D.1 Sistema operativo y kernel

- Ubuntu 24.04 LTS Server Guide — `https://help.ubuntu.com/`
  - Security: `https://help.ubuntu.com/24.04/serverguide/security.html.xhtml`
  - Openssh server: `https://ubuntu.com/server/docs/security.openssh-server`
  - AppArmor: `https://documentation.ubuntu.com/server/how-to/security/apparmor/`
  - 2FA TOTP: `https://documentation.ubuntu.com/server/how-to/security/two-factor-authentication-with-totp-or-hotp/`
  - unattended-upgrades: `https://help.ubuntu.com/24.04/serverguide/automatic-updates.html.xhtml`
  - Audit: `https://documentation.ubuntu.com/server/how-to/security/auditing/`
- man-pages Ubuntu Noble:
  - `https://manpages.ubuntu.com/manpages/noble/en/man5/sshd_config.5.html`
  - `https://manpages.ubuntu.com/manpages/noble/en/man8/auditd.8.html`
  - `https://manpages.ubuntu.com/manpages/noble/en/man8/sysctl.8.html`
  - `https://manpages.ubuntu.com/manpages/noble/en/man8/ufw.8.html`
  - `https://manpages.ubuntu.com/manpages/noble/en/man8/nft.8.html`
- Kernel docs: `https://docs.kernel.org/networking/ip-sysctl.html`
- systemd.exec man: `https://www.freedesktop.org/software/systemd/man/systemd.exec.html`
- NIST SP 800-123 (Guide to General Server Security): `https://csrc.nist.gov/pubs/sp/800/123/final`
- CIS Benchmarks (Ubuntu 24.04): `https://www.cisecurity.org/benchmark/ubuntu_linux`
- OpenSSH: `https://www.openssh.com/manual.html`
- AIDE: `https://aide.github.io/`
- ClamAV: `https://www.clamav.net/`
- rkhunter: `https://rkhunter.sourceforge.net/`
- AppArmor: `https://gitlab.com/apparmor/apparmor/-/wikis/Documentation`

## D.2 nginx y TLS

- nginx docs: `https://nginx.org/en/docs/`
  - ngx_http_ssl_module: `https://nginx.org/en/docs/http/ngx_http_ssl_module.html`
  - ngx_http_v2_module: `https://nginx.org/en/docs/http/ngx_http_v2_module.html`
  - ngx_http_headers_module: `https://nginx.org/en/docs/http/ngx_http_headers_module.html`
  - ngx_http_limit_req_module: `https://nginx.org/en/docs/http/ngx_http_limit_req_module.html`
  - ngx_http_limit_conn_module: `https://nginx.org/en/docs/http/ngx_http_limit_conn_module.html`
- nginx security advisories: `https://nginx.org/en/security_advisories.html`
- Mozilla SSL Configuration Generator: `https://ssl-config.mozilla.org/`
- Mozilla Server Side TLS: `https://wiki.mozilla.org/Security/Server_Side_TLS`
- RFC 8446 (TLS 1.3): `https://datatracker.ietf.org/doc/html/rfc8446`
- RFC 6066 (TLS Extensions): `https://datatracker.ietf.org/doc/html/rfc6066`
- RFC 6797 (HSTS): `https://datatracker.ietf.org/doc/html/rfc6797`
- RFC 8996 (Deprecating TLS 1.0/1.1): `https://datatracker.ietf.org/doc/html/rfc8996`
- OWASP Secure Headers Project: `https://owasp.org/www-project-secure-headers/`
- CSP Level 3: `https://www.w3.org/TR/CSP3/`
- Permissions Policy: `https://www.w3.org/TR/permissions-policy/`
- Cross-Origin Resource Policy: `https://resourcepolicy.fyi/`
- Let's Encrypt: `https://letsencrypt.org/docs/`
- Certbot: `https://eff-certbot.readthedocs.io/`
- Certbot DNS-01: `https://eff-certbot.readthedocs.io/en/latest/using.html#dns-plugins`
- Qualys SSL Labs: `https://www.ssllabs.com/ssltest/`
- Mozilla Observatory: `https://observatory.mozilla.org/`
- securityheaders.com: `https://securityheaders.com/`
- HSTS Preload: `https://hstspreload.org/`
- ModSecurity v3: `https://github.com/owasp-modsecurity/ModSecurity/wiki`
- OWASP CRS: `https://coreruleset.org/`

## D.3 Cloudflare

- SSL/TLS Overview: `https://developers.cloudflare.com/ssl/`
- SSL Modes (Full vs Full Strict): `https://developers.cloudflare.com/ssl/origin-configuration/ssl-modes/`
- Authenticated Origin Pulls: `https://developers.cloudflare.com/ssl/origin-configuration/authenticated-origin-pull/`
- DNS docs: `https://developers.cloudflare.com/dns/`
- WAF docs: `https://developers.cloudflare.com/waf/`
- DDoS protection: `https://developers.cloudflare.com/ddos-protection/`
- Bot Management: `https://developers.cloudflare.com/bots/`
- Rate Limiting rules: `https://developers.cloudflare.com/waf/rate-limiting-rules/`
- Cloudflare IP ranges: `https://developers.cloudflare.com/fundamentals/reference/update-ip-versions/`
- Page Rules: `https://developers.cloudflare.com/rules/page-rules/`
- Cloudflare Zero Trust / Access: `https://developers.cloudflare.com/cloudflare-one/policies/access/`
- Cloudflare R2: `https://developers.cloudflare.com/r2/`
- Cloudflare Workers: `https://developers.cloudflare.com/workers/`

## D.4 DigitalOcean

- Droplets Overview: `https://docs.digitalocean.com/products/droplets/`
- How to Create a Droplet: `https://docs.digitalocean.com/products/droplets/how-to/create/`
- Cloud Firewalls: `https://docs.digitalocean.com/products/networking/firewalls/`
- Monitoring: `https://docs.digitalocean.com/products/monitoring/`
- Backups: `https://docs.digitalocean.com/products/backups/`
- Snapshots: `https://docs.digitalocean.com/products/images/snapshots/`
- Volumes: `https://docs.digitalocean.com/products/volumes/`
- VPC: `https://docs.digitalocean.com/products/networking/vpc/`
- Spaces: `https://docs.digitalocean.com/products/spaces/`
- API: `https://docs.digitalocean.com/reference/api/`
- doctl CLI: `https://docs.digitalocean.com/reference/doctl/`

## D.5 MySQL

- MySQL 8.0 Reference Manual: `https://dev.mysql.com/doc/refman/8.0/en/`
- Security: `https://dev.mysql.com/doc/refman/8.0/en/security.html`
- caching_sha2_password: `https://dev.mysql.com/doc/refman/8.0/en/caching-sha2-pluggable-authentication.html`
- mysql_native_password deprecation: `https://dev.mysql.com/doc/refman/8.0/en/native-authentication.html`
- TLS: `https://dev.mysql.com/doc/refman/8.0/en/encrypted-connection-protocols.html`
- systemd: `https://dev.mysql.com/doc/refman/8.0/en/using-systemd.html`
- InnoDB Tablespace Encryption: `https://dev.mysql.com/doc/refman/8.0/en/innodb-tablespace-encryption.html`
- MariaDB Audit Plugin: `https://mariadb.com/kb/en/mariadb-audit-plugin/`

## D.6 PostgreSQL

- PostgreSQL 16 Documentation: `https://www.postgresql.org/docs/16/`
- Server Setup: `https://www.postgresql.org/docs/16/runtime.html`
- Client Authentication: `https://www.postgresql.org/docs/16/client-authentication.html`
- pg_hba.conf: `https://www.postgresql.org/docs/16/auth-pg-hba-conf.html`
- Encryption: `https://www.postgresql.org/docs/16/encryption.html`
- Row-level security: `https://www.postgresql.org/docs/16/ddl-rowsecurity.html`
- pgaudit: `https://github.com/pgaudit/pgaudit`
- pg_dump: `https://www.postgresql.org/docs/16/app-pgdump.html`

## D.7 Redis

- Redis 7.x documentation: `https://redis.io/docs/latest/`
- Security: `https://redis.io/docs/latest/operate/oss_and_stack/management/security/`
- ACL: `https://redis.io/docs/latest/develop/reference/acl/`
- TLS: `https://redis.io/docs/latest/operate/oss_and_stack/management/security/encryption/`
- Persistence (RDB + AOF): `https://redis.io/docs/latest/operate/oss_and_stack/management/persistence/`

## D.8 Firewall y red

- nftables wiki: `https://wiki.nftables.org/`
- man nft(8): `https://manpages.debian.org/bookworm/nftables/nft.8.en.html`
- UFW docs (Ubuntu): `https://help.ubuntu.com/community/UFW`
- Fail2Ban: `https://fail2ban.readthedocs.io/`
- iproute2: `https://wiki.linuxfoundation.org/networking/iproute2`

## D.9 Laravel 13

- Laravel 13 docs: `https://laravel.com/docs/13.x`
- Configuration: `https://laravel.com/docs/13.x/configuration`
- Encryption: `https://laravel.com/docs/13.x/encryption`
- Sanctum: `https://laravel.com/docs/13.x/sanctum`
- Routing (rate limiting): `https://laravel.com/docs/13.x/routing#rate-limiting`
- Authorization (Gates & Policies): `https://laravel.com/docs/13.x/authorization`
- Validation: `https://laravel.com/docs/13.x/validation`
- Eloquent (mass assignment): `https://laravel.com/docs/13.x/eloquent`
- Migrations: `https://laravel.com/docs/13.x/migrations`
- Artisan: `https://laravel.com/docs/13.x/artisan`
- Deployment: `https://laravel.com/docs/13.x/deployment`
- JSON:API Resources: `https://laravel.com/docs/13.x/eloquent-resources`
- Logging: `https://laravel.com/docs/13.x/logging`
- Testing: `https://laravel.com/docs/13.x/testing`
- Fortify (MFA, 2FA): `https://laravel.com/docs/13.x/fortify`
- Spatie Permission: `https://spatie.be/docs/laravel-permission/`

## D.10 Vue 3 + Vite

- Vue 3 docs: `https://vuejs.org/guide/introduction.html`
- Composition API: `https://vuejs.org/guide/extras/composition-api-faq.html`
- TypeScript: `https://vuejs.org/guide/typescript/overview.html`
- Pinia: `https://pinia.vuejs.org/`
- Vue Router: `https://router.vuejs.org/`
- TanStack Query: `https://tanstack.com/query/latest`
- TanStack Table: `https://tanstack.com/table/latest`
- TanStack Form + Zod: `https://tanstack.com/form/latest`
- Vite: `https://vitejs.dev/`
- TailwindCSS v4: `https://tailwindcss.com/`
- DOMPurify: `https://github.com/cure53/DOMPurify`

## D.11 GitHub Actions y CI/CD

- Security hardening for Actions: `https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions`
- OpenID Connect: `https://docs.github.com/en/actions/security-for-github-actions/security-guides/automatic-token-authentication`
- Encrypted secrets: `https://docs.github.com/en/actions/security-for-github-actions/security-guides/using-secrets-in-github-actions`
- Workflow syntax: `https://docs.github.com/en/actions/writing-workflows/workflow-syntax-for-github-actions`
- Environments: `https://docs.github.com/en/actions/managing-workflow-runs-and-deployments/managing-deployments/managing-environments-for-deployment`
- Branch protection: `https://docs.github.com/en/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/about-protected-branches`
- Secret scanning: `https://docs.github.com/en/code-security/secret-scanning/introduction/about-secret-scanning`
- Push protection: `https://docs.github.com/en/code-security/secret-scanning/working-with-push-protection/about-push-protection`
- Dependabot: `https://docs.github.com/en/code-security/dependabot/dependabot-security-updates/about-dependabot-security-updates`
- actionlint: `https://github.com/rhysd/actionlint`
- zizmor: `https://github.com/woodruffw/zizmor>
- gitleaks: `https://github.com/gitleaks/gitleaks>
- Trivy: `https://github.com/aquasecurity/trivy>
- Grype: `https://github.com/anchore/grype>
- Syft (SBOM): `https://github.com/anchore/syft>

## D.12 Docker y contenedores

- Docker docs: `https://docs.docker.com/>
- Docker Security: `https://docs.docker.com/engine/security/>
- Docker Bench for Security: `https://github.com/docker/docker-bench-security>
- Docker Hardened Images (DHI): `https://www.docker.com/products/hardened-images/>
- OWASP Docker Top 10: `https://owasp.org/www-project-docker-top-10/>
- CIS Docker Benchmark: `https://www.cisecurity.org/benchmark/docker>

## D.13 IDS, monitoring y respuesta a incidentes

- Wazuh docs: `https://documentation.wazuh.com/current/>
- Wazuh rules: `https://documentation.wazuh.com/current/user-manual/ruleset/custom.html>
- Wazuh FIM (syscheck): `https://documentation.wazuh.com/current/user-manual/capabilities/file-integrity/index.html>
- OSSEC: `https://www.ossec.net/>
- auditd man: `https://man7.org/linux/man-pages/man8/auditd.8.html>
- Prometheus: `https://prometheus.io/docs/>
- Grafana: `https://grafana.com/docs/>
- node_exporter: `https://github.com/prometheus/node_exporter>
- mysqld_exporter: `https://github.com/prometheus/mysqld_exporter>
- postgres_exporter: `https://github.com/prometheus-community/postgres_exporter>
- redis_exporter: `https://github.com/oliver006/redis_exporter>
- wazuh-exporter: `https://github.com/daviguilar/wazuh-exporter>
- NIST SP 800-61 (Incident Handling): `https://csrc.nist.gov/pubs/sp/800/61/r2/final>

## D.14 Backups

- restic: `https://restic.readthedocs.io/>
- borgbackup: `https://borgbackup.readthedocs.io/>
- GPG man: `https://gnupg.org/documentation/manuals/gnupg/gnupg.html>
- Boto3 (DO Spaces): `https://boto3.amazonaws.com/v1/documentation/api/latest/index.html>

## D.15 MFA y 2FA

- RFC 6238 (TOTP): `https://datatracker.ietf.org/doc/html/rfc6238>
- google-authenticator PAM: `https://github.com/google/google-authenticator>

## D.16 Estándares y frameworks

- OWASP Top 10: `https://owasp.org/Top10/>
- OWASP API Security Top 10: `https://owasp.org/API-Security/editions/2023/en/0x11-t10/>
- OWASP Cheat Sheet Series: `https://cheatsheetseries.owasp.org/>
- NIST Cybersecurity Framework: `https://www.nist.gov/cyberframework>
- CIS Controls: `https://www.cisecurity.org/controls>
- ISO/IEC 27001:2022: `https://www.iso.org/standard/27001>
- ISO/IEC 27002:2022: `https://www.iso.org/standard/75652.html>
- GDPR (Reglamento UE 2016/679): `https://eur-lex.europa.eu/eli/reg/2016/679/oj>
```

### 4.3 Referencias más relevantes, por tema (resumen)

| Tema | Referencia núcleo | Para qué sirve en el plan |
|---|---|---|
| TLS/nginx | `https://ssl-config.mozilla.org/` (`apendice-D.md:41`), `https://wiki.mozilla.org/Security/Server_Side_TLS` (`:42`), `https://nginx.org/en/docs/http/ngx_http_ssl_module.html` (`:35`) | Fija la cipher list y el perfil «intermediate» que B.3 exige; es la fuente de la que se copian los valores de `ssl-params.conf`. |
| HSTS/headers | `https://datatracker.ietf.org/doc/html/rfc6797` (`:45`), `https://owasp.org/www-project-secure-headers/` (`:47`), `https://www.w3.org/TR/CSP3/` (`:48`) | Justifican `max-age=63072000; includeSubDomains; preload` y la CSP estricta sin `unsafe-inline`. |
| Cloudflare | `https://developers.cloudflare.com/ssl/origin-configuration/authenticated-origin-pull/` (`:65`), `.../waf/rate-limiting-rules/` (`:70`), `.../fundamentals/reference/update-ip-versions/` (`:71`) | AOP/mTLS, WAF y la lista de rangos que consume `update-cf-ips` (A.2). |
| DigitalOcean | `https://docs.digitalocean.com/products/networking/firewalls/` (`:81`), `.../spaces/` (`:87`), `.../backups/` (`:83`) | Firewall de borde (C.2), destino de backups (A.4) y snapshots. |
| Datos | `https://dev.mysql.com/doc/refman/8.0/en/` (`:93`) y `https://www.postgresql.org/docs/16/` (`:104`) | Menor privilegio, `caching_sha2_password`, `scram-sha-256`, `pg_hba.conf`. **Nota: D.6 fija PostgreSQL 16, no 18.** |
| App | `https://laravel.com/docs/13.x` (`:131`) y sub-páginas `configuration` (`:132`), `encryption` (`:133`), `sanctum` (`:134`), `routing#rate-limiting` (`:135`) | Base de la tabla B.7 y del endurecimiento del `.env`. |
| CI/CD | `https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions` (`:164`), OIDC (`:165`), `https://github.com/woodruffw/zizmor` (`:174`), `gitleaks` (`:175`) | Pinning por SHA, `permissions` mínimo, OIDC y los linters que B.8/C.5 exigen. |
| Contenedores | `https://www.docker.com/products/hardened-images/` (`:185`), CIS Docker (`:187`), OWASP Docker Top 10 (`:186`) | Único respaldo documental de la capa Docker (B no tiene tabla Docker; cap-05 §6 sí). |
| Detección/respuesta | `https://documentation.wazuh.com/current/` (`:191`), `https://csrc.nist.gov/pubs/sp/800/61/r2/final` (`:203`) | Agente Wazuh (A.3) y runbook NIST (cap-05 §9). |
| Cumplimiento | ISO 27001 (`:224`), GDPR (`:226`), OWASP Top 10 (`:219`) | **No hay ninguna referencia a normativa colombiana.** |

---

## 5. Apéndice E — Glosario

**Estructura:** un término por línea, formato `**Término:** definición` sin secciones ni índice alfabético estricto. Regla declarada: «Definiciones operativas, no enciclopédicas. Cuando un término tiene varios usos, aquí está el sentido en que se usa en esta guía.» (`apendice-E.md:3`).

**Nº de términos definidos: 223** (verificado: `grep -c '^\*\*' apendice-E.md` = 223 líneas con ese patrón). Coincide con la promesa del entregable «200+ términos definidos» (`deliverable.md:51`).

### 5.1 Selección de los 30 términos más relevantes para el proyecto (cita literal)

```markdown
**A+ (SSL Labs):** Calificación máxima en [Qualys SSL Labs](https://www.ssllabs.com/ssltest/). Indica TLS 1.2/1.3 sin vulnerabilidades, cifrados fuertes, OCSP stapling, forward secrecy.
**AEAD (Authenticated Encryption with Associated Data):** Modo de cifrado que autentica el mensaje además de cifrarlo. AES-GCM y ChaCha20-Poly1305 son AEAD.
**AIDE (Advanced Intrusion Detection Environment):** Herramienta que crea una base de datos hash de archivos críticos y detecta cambios. Es la línea base de integridad del sistema.
**APO / Authenticated Origin Pulls:** Mecanismo mTLS de Cloudflare donde solo sus edges pueden conectar al origin (vuestro droplet) porque presentan un certificado cliente firmado por la CA de CF.
**Argon2id:** Algoritmo de hash de contraseñas recomendado por OWASP. Laravel 13 lo soporta vía `Hash::driver('argon')`.
**Audit (auditd):** Subsistema del kernel Linux que registra llamadas al sistema (`syscall`). Permite rastrear accesos a archivos, cambios de configuración, ejecución de binarios.
**Bcrypt:** Algoritmo de hash de contraseñas (cost 12 por defecto). Laravel lo usa por defecto.
**Bogon:** Dirección IP privada (RFC 1918) o reservada que aparece en Internet. Indicador de spoofing.
**Cipher Suite:** Combinación de algoritmos para TLS: key exchange + authentication + encryption + MAC. Cada TLS handshake negocia uno.
**Clickjacking:** Ataque que engaña al usuario para hacer click en algo distinto de lo que cree. Mitigado por `X-Frame-Options: DENY` o CSP `frame-ancestors 'none'`.
**CORS Allowed Origins:** Lista explícita de orígenes que pueden hacer fetch a tu API. `*` es inseguro en producción.
**CSP (Content-Security-Policy):** Header HTTP que indica al navegador de dónde puede cargar recursos. Defensa profunda contra XSS.
**Docker:** Plataforma de contenedores. En esta guía, opcional. Si se usa, base DHI (Docker Hardened Images).
**Endurecer (hardening):** Aplicar configuración para reducir superficie de ataque.
**Fail2Ban:** Herramienta que escanea logs y aplica bans (iptables/nftables) tras N intentos fallidos.
**FIM (File Integrity Monitoring):** Monitoreo de integridad de archivos. Wazuh FIM, AIDE, Tripwire.
**Forward secrecy:** Propiedad por la que el compromiso de la clave privada del servidor no descifra sesiones pasadas. Requiere DHE/ECDHE.
**Header:** Cabecera HTTP. Metadatos enviados con cada request/response.
**HSTS (HTTP Strict Transport Security):** Header que indica al navegador "este sitio debe ser HTTPS siempre, no aceptar HTTP".
**JSON:API:** Especificación para APIs (jsonapi.org). Laravel 13 tiene soporte nativo.
**mTLS (Mutual TLS):** TLS donde ambos lados presentan certificado. Usado en AOP (Cloudflare).
**MFA (Multi-Factor Authentication):** Autenticación multifactor. TOTP (RFC 6238) es la opción común.
**Nginx:** Servidor web + reverse proxy. Versión objetivo: 1.29 mainline.
**OCSP Stapling:** El servidor obtiene una respuesta OCSP firmada por la CA y la envía en cada TLS handshake. Evita que el cliente tenga que contactar la CA.
**PostgreSQL:** RDBMS. Versión objetivo: 16.
**PHP:** Lenguaje de programación. Versión objetivo: 8.3+.
**RPO (Recovery Point Objective):** Cuánto dato se acepta perder. Define frecuencia de backup.
**RTO (Recovery Time Objective):** Cuánto tiempo se acepta estar caído. Define la prioridad de los runbooks.
**WAF (Web Application Firewall):** Firewall a nivel de aplicación HTTP. Cloudflare WAF, ModSecurity.
**XSS (Cross-Site Scripting):** Vulnerabilidad que permite inyectar JS. CSP la mitiga.
```

Complemento necesario (términos que el plan va a necesitar y que sí están definidos):

```markdown
**Audit (auditd):** Subsistema del kernel Linux que registra llamadas al sistema (`syscall`). Permite rastrear accesos a archivos, cambios de configuración, ejecución de binarios.
**MySQL:** RDBMS. Versión objetivo: 8.0 LTS.
**NIST (National Institute of Standards and Technology):** Agencia de estándares USA. Mantiene SP 800 series (seguridad).
**PHP-FPM (PHP FastCGI Process Manager):** Gestor de procesos para PHP. Usado por nginx vía FastCGI.
**Redis:** Almacén de estructuras en memoria. Versión objetivo: 7.x.
**Sast:** Static Application Security Testing. Análisis de código sin ejecutarlo.
**SBOM (Software Bill of Materials):** Lista de componentes de un binario. Syft, CycloneDX, SPDX.
**SHA-256:** Hash de 256 bits. Estándar actual.
**SIEM (Security Information and Event Management):** Sistema que centraliza logs y alertas. Wazuh Manager + Elasticsearch.
**SLO (Service Level Objective):** Objetivo medible de servicio. Más granular que SLA.
**SLI (Service Level Indicator):** Indicador medido. Latencia p99, error rate, etc.
**Token (Sanctum):** Cadena aleatoria hasheada en DB. Tiene abilities, expiración, last_used_at.
**TOTP (Time-based One-Time Password):** RFC 6238. Códigos de 6 dígitos que rotan cada 30s.
**Zero Trust:** Modelo de seguridad que asume cero confianza perimetral. Cloudflare Access implementa esto.
```

### 5.2 Términos del glosario que **contradicen** los capítulos o el propio corpus

| Término | Dice el glosario | Contradice a | Evidencia |
|---|---|---|---|
| **Docker** | «Plataforma de contenedores. En esta guía, **opcional**. Si se usa, base DHI» | `capitulo-05.md` §6 (Dockerfile DHI multi-stage obligatorio, `docker-compose` con secretos, entrypoint) y `deliverable.md:78` («Docker hardening con dhi.io… no-root, read_only, cap_drop ALL»). El capítulo 5 **sí** asume contenedores como vía de despliegue; el glosario lo degrada a opcional. | `apendice-E.md:113` vs `capitulo-05.md:1918-2060`, `deliverable.md:78` |
| **PostgreSQL** | «Versión objetivo: **16**» | La premisa del encargo (PostgreSQL 18) y el propio D.6 (docs de PG 16). Coherente con cap-04 §4.8, que además llama a PG «alternativa» frente a MySQL primario. | `apendice-E.md:281` vs `capitulo-04-parte-a.md:1,301`, `apendice-D.md:104` |
| **MySQL** | «Versión objetivo: **8.0 LTS**» | MySQL 8.0 **no es una release LTS** (LTS es 8.4); además el usuario está en EOL de soporte extendido. Cap-04 usa `IDENTIFIED WITH caching_sha2_password` y `log_warnings = 2` (variable retirada en MySQL 8.0.3+). | `apendice-E.md:249` vs `capitulo-04-parte-a.md:186`, `capitulo-04-parte-a.md:126` |
| **Argon2id** | «Laravel 13 lo soporta vía `Hash::driver('argon')`» | En Laravel el driver `argon` corresponde a **Argon2i**; Argon2id se selecciona con `argon2id`. La definición del término y el código que propone no coinciden. | `apendice-E.md:19` |
| **PHP** | «Versión objetivo: **8.3+**» | El corpus mezcla 8.3 y 8.4: `php8.3-fpm` en cap-03/A.6/C.1/guía-maestra frente a `php8.4-fpm` en cap-05. El «8.3+» del glosario blanquea la inconsistencia en lugar de resolverla. | `apendice-E.md:289` vs `capitulo-03-parte-a.md:337`, `capitulo-05.md:1530`, `apendice-A.md:500`, `apendice-C.md:13` |
| **Aux** | «Servicio de copia automática de DigitalOcean (snapshots semanales)» | No existe producto DigitalOcean llamado «Aux»: DO ofrece *Backups* y *Snapshots* (`apendice-D.md:83-84`, `capitulo-05.md:3081`). Término inventado o mal nombrado. | `apendice-E.md:29` vs `apendice-D.md:83-84`, `apendice-C.md:30` |
| **TOTP / MFA / PAM** | TOTP es «la opción común»; Google Authenticator se enchufa como módulo PAM | El cap-01 y B.1 desactivan `KbdInteractiveAuthentication`, que es el canal por el que PAM/TOTP funciona. Glosario y capítulos describen mecanismos incompatibles entre sí (ver D-01). | `apendice-E.md:239,273,405` vs `apendice-B.md:14,27`, `apendice-C.md:17` |
| **Nginx** | «Versión objetivo: 1.29 mainline» | B.4 fija el default como `nginx/1.29.0`; C.3 verifica «1.29.x». No es contradicción dura, pero el corpus no fija un patch level único. | `apendice-E.md:251` vs `apendice-B.md:86`, `apendice-C.md:58` |
| **HTTP/2** | «RFC 7540» | RFC 7540 fue **obsoleta por RFC 9113**; el corpus despliega nginx 1.29 (donde además `listen ... http2` está deprecado, `capitulo-03-parte-a.md:248`). | `apendice-E.md:177` |
| **SE Linux / SELinux**, **SSO / Single Sign-On**, **Proxy / Reverse Proxy / Reverse proxy** | Entradas duplicadas con etiquetas distintas para el mismo concepto | Ninguno: es defecto editorial interno del glosario (rompe el criterio «un término, una definición»). | `apendice-E.md:337` y `:341`; `:355` y `:383`; `:287` y `:319` |
| **Sast** | Minúscula | Ortografía del estándar (SAST) y del resto del glosario (que usa mayúsculas) | `apendice-E.md:329` |

**Relleno:** 17 entradas están marcadas explícitamente como irrelevantes («No relevante aquí», «Sin relevancia aquí», «No usado directamente aquí»): `apendice-E.md:39` (BPM), `:49` (CASB), `:57` (CFR), `:77` (COS), `:109` (DNI), `:127` (EFS), `:193` (Ingress controller), `:203` (IPSec), `:215` (Kerberos), `:221` (LDAP), `:247` (Mux), `:277` (PCI DSS), `:337` (SE Linux), `:343` (Service Worker), `:387` (SVC), `:407` (TPM), `:437` (WebAuthn). Es ~7,6 % del glosario dedicado a términos que el propio glosario declara fuera de alcance.

**Ausencia normativa:** el glosario define GDPR (`:157`), CCPA (`:51`), PCI DSS (`:277`), ISO 27001 (`:205`) y SOC 2 (`:369`), pero **no define ninguna norma colombiana** (Ley 527 de 1999, Ley 1581 de 2012, Decreto 1078 de 2015, GESI/MinTIC, CONPES 3995) ni términos del dominio «sede electrónica» (acuse de recibo, radicado, notificación electrónica, firma electrónica). Búsqueda exhaustiva: 0 coincidencias en todo el corpus.

---

## 6. Defectos y contradicciones

Nomenclatura: **A-nn** defectos del Apéndice A, **B-nn** del B, **C-nn** del C, **D-nn** del D, **E-nn** del E, **X-nn** contradicciones transversales (apéndice ↔ capítulo o ↔ premisa). Severidad: **[CRÍT]** rompe el despliegue o la seguridad prometida; **[ALTO]** el plan no puede ejecutarse como está escrito; **[MEDIO]** inconsistencia que produce drift o auditoría fallida; **[BAJO]** editorial.

### 6.1 Contradicciones entre apéndices

1. **X-01 [CRÍT] `skip-networking` vs checklist que exige listener TCP.** B.5:94 recomienda `skip-networking = 1`; cap-04-a:100 lo deja **activo**; pero C.4:92 exige `ss -tlnp | grep -E '3306|5432|6379|6380'` mostrando `127.0.0.1`, C.4:112 exige el túnel SSH a `3306` y **C.7:222 exige literalmente** `ss -tlnp | grep 3306 | grep -q 127.0.0.1`. Con `skip-networking` no hay listener y ambos checks del checklist **fallan siempre**. La propia cap-04-a:143 documenta «NO debe devolver nada si skip-networking está ON».
2. **X-02 [CRÍT] `require_secure_transport=ON` vs procedimiento de túnel con TLS desactivado.** cap-04-a:114 activa `require_secure_transport = ON`; C.4:102 exige que la conexión sin TLS **falle**; pero cap-04-b:229-230 y su regla de :234 ordenan `mysql -h 127.0.0.1 -P 13306 -u laravel_app -p --ssl-mode=DISABLED` «con túnel SSH desactiva el TLS del motor». Es la ruta de administración documentada y **no puede funcionar** con el motor endurecido.
3. **X-03 [ALTO] Usuarios de sistema: `admin`/`deployer` vs `ops`/`deploy`/`auditor`.** B.1:21 fija `AllowUsers admin deployer`; C.1:12-13 habla del «usuario `admin` con sudo» y `/etc/sudoers.d/admin`; guía-maestra:34 «Usuario admin con sudo + clave SSH + 2FA TOTP». Cap-01 crea **otra** nomenclatura: `ops`, `deploy`, `auditor` (bash) y `app`, `dbadmin` (nologin) — cap-01:380-384, 671-672. El usuario `admin` **no existe** en ningún capítulo y el `deployer` del CI sí (cap-05:1622). El plan no puede aplicar B.1 ni C.1 sin inventar cuentas.
4. **X-04 [ALTO] Host keys: «solo Ed25519, no RSA/ECDSA» vs RSA host key configurada.** B.1:23 afirma que el endurecido es «Solo Ed25519, no RSA/ECDSA», pero cap-01:642-643 define `HostKey /etc/ssh/ssh_host_ed25519_key` **y** `/etc/ssh/ssh_host_rsa_key`, con `HostKeyAlgorithms ssh-ed25519,rsa-sha2-512,rsa-sha2-256` (:650) y `PubkeyAcceptedAlgorithms` con RSA (:649).
5. **X-05 [ALTO] Algoritmos SSH: B.1 lista suites que el capítulo prohíbe.** B.1:25 admite `aes256-ctr,aes192-ctr,aes128-ctr` y B.1:26 añade `hmac-sha2-512,hmac-sha2-256` (no-ETM); cap-01:647 limita `Ciphers` a 3 AEAD y cap-01:648 limita `MACs` a 3 variantes `-etm@openssh.com`. B.1 es más laxo que el capítulo que dice endurecer.
6. **X-06 [MEDIO] SSH: `AllowAgentForwarding` y `MaxSessions`.** B.1:31 endurece `AllowAgentForwarding no`; cap-01:676 lo deja `yes`. B.1:17 endurece `MaxSessions 5`; cap-01:665 fija `4`. B.1:no incluye `MaxStartups`, que cap-01:666 fija en `3:50:10`.
7. **X-07 [MEDIO] `tcp_max_syn_backlog` con dos valores.** cap-01:1136 fija `2048`; cap-02:1110-1123 fija `4096` en `/etc/sysctl.d/99-anti-ddos.conf`. Dos archivos sysctl con el mismo parámetro y valores distintos; B.2 **ninguno** de los dos.
8. **X-08 [CRÍT] Fail2Ban: tres configuraciones incompatibles del mismo jail `sshd`.** A.1:30-40 (`maxretry 3`, `findtime 600`, `bantime 3600`, `bantime.factor 1.5`, `bantime.maxtime 86400`, `logpath /var/log/auth.log`); cap-02:803-811 (`maxretry 3`, `findtime 10m`, `bantime 24h`, `backend systemd`, `mode aggressive`, `logpath %(sshd_log)s`); cap-05:2949-2956 (`maxretry 5`, `findtime 600`, `bantime 3600`, `logpath /var/log/auth.log`, `action iptables-multiport`). Además el `recidive` difiere en los tres (A.1:50-56 `maxretry 5/bantime 604800/findtime 86400`; cap-02:859-865 `maxretry 3/bantime 1w/findtime 1d`).
9. **X-09 [CRÍT] Fail2Ban: `banaction` divergente (nftables vs iptables).** cap-02:781-782 exige `banaction = nftables-multiport` / `banaction_allports = nftables-allports` y C.2:41 exige el set `blackhole` de nftables; A.1 **no define `banaction`** (queda el default de iptables) y cap-05:2953 usa `action iptables-multiport[...]`, lo que choca con la regla dura «no mezclar iptables-legacy con nftables» (cap-02:445).
10. **X-10 [ALTO] Acción Cloudflare: dos archivos, dos endpoints, dos esquemas de autenticación.** A.1:9 → `/etc/fail2ban/action.d/cloudflare-api.conf`, endpoint `zones/<cf_zone_id>/firewall/access_rules/rules`, `Authorization: Bearer <cf_api_token>`, variables en `[DEFAULT]`. cap-02:876 → `/etc/fail2ban/action.d/cloudflare-ban.conf`, endpoint `user/firewall/access_rules/rules`, cabeceras `X-Auth-Email` + `X-Auth-Key` (= **Global API Key**). Y cap-02:910-911 prohíbe explícitamente usar la Global API Key en producción → cap-02 se contradice a sí mismo, mientras A.1 (Bearer token) es la versión correcta. C.2:46 y guía-maestra:189 verifican/citan el nombre del archivo de **A**, no el del capítulo.
11. **X-11 [ALTO] `actionunban` de A.1 inoperante.** A.1:19-20 hace `DELETE .../rules/<rule_id>` con una etiqueta que Fail2Ban **no** sustituye; cap-02:899+ documenta que Cloudflare genera un ID nuevo en cada ban y por eso hace primero un *lookup*. El unban de A.1 nunca funcionará.
12. **X-12 [ALTO] La acción de A.1 nunca se activa.** En A.1:23-57 ningún jail tiene `action = ...cloudflare-api`; cap-02:794-795 sí asigna `action = %(action_mwl)s` + `cloudflare-ban` en `[DEFAULT]`. El artefacto se define y se queda sin uso, pero C.2:46 lo exige «presente».
13. **X-13 [MEDIO] Rotación de backups: 3 niveles vs 1.** C.4:114 y C.6:167 y guía-maestra:177 y deliverable:74 exigen rotación **7/30/365**; cap-04-b:366-371 la implementa (`-mtime +7 -delete` + `mv ... /var/backups/monthly/`); A.4 solo borra `-mtime +7` (líneas 276 y 311) y **no** tiene capa mensual ni anual.
14. **X-14 [ALTO] Dos arquitecturas de backup simultáneas.** A.4 sube con `aws s3 cp` a `s3://backup-vps-secure/...` con `--endpoint-url https://nyc3.digitaloceanspaces.com` (A.4:269-273, 305-309) y cifra con GPG; cap-05 §8.2 usa **restic** (`RESTIC_REPOSITORY=s3:s3.nyc3.digitaloceanspaces.com/example-prod-backups`, cap-05:2994) con retención `--keep-daily 7 --keep-weekly 4 --keep-monthly 6` (cap-05:3045). El checklist C.4:114 solo verifica la de A.4, que no es la que el capítulo 5 define como política 3-2-1-1-0 (cap-05:2967-2973).
15. **X-15 [MEDIO] Passphrase GPG en dos ubicaciones.** A.4:264/301 y cap-04-b:392/411 usan `/etc/dropbear/backup-gpg-passphrase`; cap-05 §8.2/§8.6 usan `/etc/restic/passphrase` (cap-05:2981, 3138-3143). Dos secretos distintos, dos procedimientos de custodia.
16. **X-16 [CRÍT] `pm.max_children = 50` es incompatible con la memoria declarada.** cap-05:2108 `pm.max_children = 50` con `memory_limit = 256M` (cap-05:2103, 2068) implica hasta **12,8 GB** de workers, sobre un droplet de 4 vCPU/8 GB (cap-01:117) y con el contenedor `api` limitado a `memory 512M` (cap-05:2298-2300). cap-01:98 estima «~256 MB por worker; 4 workers × 256 MB … ≈ 3,5 GB». Tres modelos de capacidad incompatibles; B no tiene ninguna fila de FPM.
17. **X-17 [ALTO] Redis: `--bind 0.0.0.0` en el contenedor.** cap-05:2428 (`redis:7-alpine`, `--bind 0.0.0.0`) contradice la regla dura de cap-04-b:59 («Redis nunca debe hacer bind en 0.0.0.0») y B.6:110 (`bind 127.0.0.1 ::1`).
18. **X-18 [ALTO] Redis: `maxmemory` con dos valores y política de desalojo.** B.6:118 y cap-04-b:94 fijan `maxmemory 2gb`; cap-05:2421 fija `--maxmemory 256mb`. En los tres casos `allkeys-lru` (B.6:119), que con `SESSION_DRIVER=redis` (B.7:134) puede desalojar sesiones y colas.
19. **X-19 [MEDIO] Exporters: tres listas distintas.** A.5:346-371 define jobs `wazuh 9093`, `mysqld 9104`, `postgres 9187`, `redis 9121`, `nginx 9113`, `node 9100`; cap-05:2812-2843 define `node 9100`, `postgres 9187`, `redis 9121`, `nginx 9113`, `php-fpm 9253` (**sin** `wazuh-exporter`, y con un typo `job_job:` en cap-05:2832); C.5:152 exige `node, mysqld, redis, wazuh` (**sin** `postgres_exporter` ni `php-fpm`). Un stack PostgreSQL no puede certificarse con la lista del checklist.
20. **X-20 [MEDIO] Drill de restore: semanal vs mensual.** cap-05:3092 «Drill de restauración **semanal**» con cron `17 4 * * 0` (cap-05:3122); cap-04-b:429 «el drill **mensual** es lo que cuenta»; C.4:115 «en los últimos 30 días»; C.6:166 «drill mensual»; guía-maestra:84 «Drill de restore mensual»; deliverable:74 «drill mensual obligatorio».
21. **X-21 [ALTO] Rate-limit de nginx: dos conjuntos de zonas incompatibles.** cap-02 §6.3.1: `per_ip:10m rate=10r/s`, `per_server:10m rate=100r/s`, `limit_conn conn_per_ip 10`, escritas en `/etc/nginx/nginx.conf`. cap-03 §3.12: `api:10m rate=10r/s`, `app:10m rate=30r/s`, `limit_conn conn 50`, en `/etc/nginx/conf.d/rate-limit.conf`. C.2:48-49 solo verifica las de cap-03, pero C.3:61 exige un `nginx.conf` «minimal, sin includes de sitios».
22. **X-22 [MEDIO] Timeouts anti-Slowloris con dos valores.** C.2:50 exige `client_body_timeout 12s` y `send_timeout 10s`; cap-02:1184-1185 fija `client_body_timeout 10s` y `send_timeout 30s`. `send_timeout` difiere por factor 3.
23. **X-23 [MEDIO] `send_timeout` vs `fastcgi_read_timeout`.** cap-03-a:349-350 fija `fastcgi_send_timeout 30s` / `fastcgi_read_timeout 30s` y `request_terminate_timeout = 30s` (cap-05:2114) mientras `max_execution_time = 30` (cap-05:2065): cualquier operación de 30 s queda al borde del corte en tres capas simultáneas. No contradictorio, pero el diseño no está justificado ni verificado por C.
24. **X-24 [ALTO] `ib_forward` y Docker.** B.2:37 y cap-01:1100 fijan `net.ipv4.ip_forward = 0` «No es router»; cap-05 §6 despliega con Docker (`bridge` propio, cap-05:2270-2282) que **requiere** `ip_forward=1` para el egress de los contenedores. Si el plan adopta contenedores, el sysctl de B.2/cap-01 rompe la red del stack.
25. **X-25 [ALTO] Capa de firewall nativa vs contenedores.** C.2 exige DO Firewall + UFW + nftables + Fail2Ban como capas del host (C.2:34-45, cap-02:239-626), pero con contenedores los puertos publicados (`ports: "127.0.0.1:8080:8080"`, cap-05:2466) se insertan en cadenas propias de Docker que **no** pasan por UFW; y cap-05 usa `iptables -I INPUT` (cap-05:3199) y `ufw deny` (cap-05:3222) pese a la regla «no mezclar iptables-legacy con nftables» (cap-02:445).
26. **X-26 [ALTO] Egreso: el agente Wazuh no puede llegar al manager.** A.3:209-213 y cap-05:2770-2781 requieren TCP **1514** desde el agente al manager (`audisp-remote` → `remote_server=wazuh.example.tld port=1514`). Ni C.2:35 ni cap-02:137-148 permiten egreso 1514: la lista de outbound es 53/80/443/123/587 y el deny explícito cubre `54-79`, `588-7999`, etc. El agente quedaría incomunicado. Lo mismo ocurre con el egreso **22** (denegado por la regla `tcp 0-52 deny`, cap-02:143) que necesita cualquier `git pull`/`rsync` saliente (A.6:495).
27. **X-27 [MEDIO] Egreso SMTP: 25 vs 587.** C.2:35 permite «25»; cap-02:139 permite `587` a `IP_SMTP_RELAY/32` y **no** permite 25. C.1:35 excluye explícitamente 587 de la lista del checklist.
28. **X-28 [MEDIO] `client_body_timeout` + ACME:** cap-03-a:15 describe «TCP/80, 443 (nftables allow solo desde rangos Cloudflare)» y cap-03-b:290 insiste en restringir 443 a rangos CF; pero la emisión de certificados es por **HTTP-01** (cap-03-b:24-27 `certbot --nginx -d ... --no-redirect`, y cap-03-a:162 «el challenge HTTP-01 debe responder en HTTP») desde IP de Let's Encrypt, que **no** está en los rangos de Cloudflare. La emisión fallaría salvo excepción de allowlist no documentada.
29. **X-29 [MEDIO] Cifrado en reposo verificado con SQL inválido y sin ajuste en B.** C.4:101 verifica `SELECT name, encryption FROM information_schema.tables;` — esa columna no existe en esa vista (el cifrado de tablespace está en `information_schema.INNODB_TABLESPACES.ENCRYPTION`); cap-04-b:520 repite el mismo SQL; y B.5 no incluye ninguna fila de `early-plugin-load = keyring_file.so` / `keyring_file_data` que cap-04-a:118-119 sí define.
30. **X-30 [MEDIO] `mysql_native_password`/`mysql_native_password`...** — ver X-31; sin contenido propio.
31. **X-31 [MEDIO] Auditoría MySQL con plugin de MariaDB.** B.5:104 («server_audit (plugin) → MariaDB Audit») y cap-04-a:130-136 (`plugin-load-add = server_audit.so`) aplican el plugin de auditoría de MariaDB sobre **MySQL 8.0**, combinación no soportada; el equivalente para MySQL es `audit_log` (Enterprise) o `audit_log` de Percona/MariaDB únicamente en MariaDB.
32. **X-32 [BAJO] `log_warnings = 2` está retirado en MySQL 8.0.** cap-04-a:126 fija `log_warnings = 2`, variable eliminada en MySQL 8.0.3 (sustituida por `log_error_verbosity`). B.5 no la incluye.
33. **X-33 [ALTO] Cuentas MySQL: B/C exigen 5-6, el capítulo crea 3.** C.4:98 dice «5 usuarios separados (root, app, migrate, bi_reader, backup, exporter)» — la lista tiene 6; `deliverable.md:70` y `capitulo-04-parte-b.md:521` dicen «5 usuarios». cap-04-a:186-198 crea `laravel_app`, `laravel_migrate`, `bi_reader` (+ root); `backup_user` y `exporter` aparecen solo en cron/exporters (cap-04-b:303, 466-474, 490-506). El checklist exige `SHOW GRANTS` de usuarios que ningún apéndice crea.
34. **X-34 [MEDIO] `CORS_ALLOWED_ORIGINS` no existe.** B.7:140 y C.5:127 usan esa variable; Laravel no la lee y cap-05:286-296 configura `config/cors.php` con `allowed_origins` hard-coded. El checklist verifica una variable inexistente.
35. **X-35 [ALTO] `TRUSTED_PROXIES` no es una env de Laravel y el capítulo se contradice.** B.7:139 y C.5:126 exigen `TRUSTED_PROXIES` con rangos Cloudflare; cap-05:130 lo pone a `*` en el `.env` de ejemplo y cap-05:263 prohíbe `TRUSTED_PROXIES=*`; la implementación real está en `bootstrap/app.php` con 23 rangos + `10.0.0.0/8` (cap-05:235-258) y `trustHosts` (cap-05:272-275). Confiar en `10.0.0.0/8` como proxy es además un riesgo de forja de `X-Forwarded-For` en una VPC compartida.
36. **X-36 [ALTO] `DB_HOST` con tres valores.** B.7:142 `127.0.0.1`; cap-04-a:409 `127.0.0.1`; cap-05:134 `10.0.0.5` (VPC) y cap-05:2295 `DB_HOST=postgres` (compose). Tres topologías de datos incompatibles con B.7:143 (`DB_SOCKET=/var/run/mysqld/mysqld.sock`, que solo existe en el host MySQL).
37. **X-37 [CRÍT] `APP_KEY` rotación trimestral sin plan de re-cifrado.** B.7:131 («Rotar trimestralmente») y C.5:122 («rotada en los últimos 90 días»), con `APP_PREVIOUS_KEYS` en cap-05:184-186 y la advertencia de invalidar sesiones y tokens (cap-05:189). Ningún apéndice documenta qué columnas cifradas con `Crypt::encryptString()`/`$casts` cifrados se rompen ni cómo migrarlas; el checklist solo comprueba la fecha, no la integridad de los datos cifrados.
38. **X-38 [MEDIO] SHA de acciones: 40 vs 64 caracteres.** C.5:132 exige «SHA de 40 chars» y guía-maestra:77 igual; cap-05:8-9 dice «pinneadas por SHA-256 **de 40 caracteres**» (un SHA-256 tiene 64). A.6 usa **64 hex en las 10 acciones** (A.6:396,399,415,418,431,435,443,472,476,488), incluido el SHA real de `actions/checkout` v4.2.2 al que se le añadieron 24 caracteres (`11bd71901bbe5b1630ceea73d27597364c9af683` + `40f9f97f9c5b6e1f10b97b6b`) y un SHA sintético con patrón repetido para `appleboy/ssh-action` (`4f87b3a9d7e9e9c9...`). El corpus no tiene un solo pin correcto y verificable.
39. **X-39 [ALTO] CI de A.6 no ejecuta lo que B.8/C.5/cap-05 exigen.** B.8:161-163, C.5:135-137 y cap-05:1352-1362 exigen `actionlint`, `zizmor` y `gitleaks`; A.6 `ci.yml` solo ejecuta `pint`, `artisan test` y Trivy. Tampoco incluye `concurrency`, `cache`, PHPStan ni `permissions: checks: write` por job (cap-05:1242-1285).
40. **X-40 [ALTO] `deploy.yml` es inválido y usa un OIDC que DigitalOcean no tiene.** A.6:460-462 coloca `environment:` a nivel de workflow (clave job-level en GitHub Actions) → el environment protegido con reviewers (B.8:157, C.5:144) no se aplica; A.6:476-479 usa `aws-actions/configure-aws-credentials` con `role-to-assume` (DigitalOcean no expone STS `AssumeRoleWithWebIdentity`), mientras cap-05:1586-1722 despliega con SSH key + `rsync` a `/var/www/html/releases/$SHA`, purga opcache, hace snapshot y publica en Sentry. Son dos pipelines distintos.
41. **X-41 [MEDIO] `sudo` del deploy imposible.** A.6:500 ejecuta `sudo systemctl reload php8.3-fpm` como `deployer`; el NOPASSWD solo se concede en `/etc/sudoers.d/admin` (C.1:13) y cap-01:425-433 lo define en `/etc/sudoers.d/20-deploy-cicd` para el usuario `deploy`. Ni el usuario ni el archivo coinciden con A.6.
42. **X-42 [MEDIO] Versión de PHP: 8.3 vs 8.4.** C.1:13 y C.7 no aplican, pero C.5:139-140 (imagen `php:8.3-fpm`, `dhi.io/php:8.3-fpm`), A.6:401/420/500 y cap-03-a:337 usan **8.3**; cap-05:3/1265/1660 y cap-05:1920 usan **8.4** (`dhi.io/php:8.4-fpm-alpine3.22`); E:289 dice «8.3+»; la premisa del plan dice 8.5. Cuatro valores para el runtime.
43. **X-43 [ALTO] Modelo de despliegue: nativo vs contenedor.** A.6 y C.5 asumen host nativo (`git pull`, `/var/www/api.dominio.tld`, `systemctl reload php8.3-fpm`, `APP_DEBUG=false` en `.env`); cap-05 §6 despliega contenedores `read_only`, `cap_drop: [ALL]`, secrets en `/run/secrets`, `whitelist` de runner CIDR (cap-05:1727-1731) y prohíbe bind mounts de código (cap-05:2479). E:113 declara Docker «opcional». No existe una ruta única.
44. **X-44 [MEDIO] `fail2ban-client status` con dos configuraciones de jail.** A.1 define 3 jails (`sshd`, `nginx-http-auth`, `recidive`); cap-02:920-931 espera **7** jails (`recidive, sshd, nginx-http-auth, nginx-badbots, nginx-noscript, nginx-ddos, api-login`) y cap-05:2938-2956 añade `[api-login]`. C.2:44-45 solo verifica `sshd` y `recidive`.
45. **X-45 [BAJO] `nftables` y `ufw` simultáneos.** C.2:36-40 exige UFW activo **y** nftables activo con `table inet filter` y chains propias; cap-02:618-626 advierte que con `policy drop` **no** debe usarse UFW y que la coexistencia exige `policy accept`. El checklist no verifica la condición de coexistencia.
46. **X-46 [MEDIO] `ss -tlnp | grep :443` «no en 80».** C.3:87 exige que nginx **no** escuche en 80; C.3:66-67 exige server blocks «443 + 80 → 301» y cap-03-a:158-169 los implementa. Los dos items del mismo checklist son mutuamente excluyentes.
47. **X-47 [MEDIO] ModSecurity con dos rutas y sin `load_module`.** A.7:511-527 usa `/etc/modsecurity/modsecurity.conf` y `modsecurity_rules_file` dentro de `http{}`; cap-03-b:375-410 usa `/etc/nginx/modsecurity/modsecurity.conf` + `exclude.conf`. A.7 no incluye `load_module ngx_http_modsecurity_module.so;` ni el `include` de `crs-setup.conf`, por lo que el CRS 4.0.0 descargado no queda operativo; además el paquete `libnginx-mod-http-modsecurity` de Ubuntu no es compatible con el nginx de nginx.org que exige C.3:59.
48. **X-48 [BAJO] `--wildcards` deja el CRS a medias.** A.7:517-520 extrae solo `*/rules/*.conf` y mueve `crs-setup.conf.example` a `crs-setup.conf` pero ningún `include` referencia ese archivo.
49. **X-49 [MEDIO] El apéndice A no es autosuficiente.** C.2:47-49 exige `cloudflare-realip.conf`, `rate-limit.conf`; C.3:62-64 exige `ssl-params.conf`, `security-headers.conf`, `rate-limit.conf`, `cloudflare-realip.conf`; C.3:73 exige el hook de Certbot; C.5:148 exige el `wazuh-agent`. **Ninguno** de esos artefactos está en el Apéndice A (los archivos están en cap-03/cap-04/cap-05). El apéndice titulado «Scripts completos» omite exactamente los artefactos que su propio checklist verifica.
50. **X-50 [BAJO] Referencia cruzada rota al runbook.** C.6:164 remite a «Runbook de incidentes (cap-05 **§5.9**)»; §5.9 de cap-05 es «Secret scanning + push protection» (cap-05:1890) y el runbook es **§9** (cap-05:3147). El mismo error está en deliverable.md:59 y guia-maestra-seguridad.md:204.
51. **X-51 [BAJO] Colisión de nombres de apéndices.** cap-05:3500 «## 11. Apéndice A — Inventario de secretos» y cap-05:3536 «## 12. Apéndice B — Referencias bibliográficas» colisionan con los apéndices A–E del entregable, y cap-05:3578 cita «NIST SP 800-61r3» mientras D:203 cita «SP 800-61 r2».
52. **X-52 [BAJO] Rutas de despliegue contradictorias.** `/var/www/api.dominio.tld` (A.6:494, C.5:227, cap-04-b:289), `/var/www/laravel` (cap-01:1627), `/var/www/html/releases/$SHA` + `current` (cap-05:1629,1660), `/var/www/html` (cap-05:2004). El checklist fija rutas de una topología que el capítulo 5 no usa.
53. **X-53 [MEDIO] `opcache.validate_timestamps = 0` en un modelo de dos despliegues.** cap-05:2093 lo desactiva (correcto en imagen inmutable) pero A.6:498-500 y cap-05 §5.5 despliegan código nuevo en el host/release y solo recargan FPM: en el modelo de A.6, con `validate_timestamps=0` la purga de opcache depende de `curl 127.0.0.1:8080/opcache-purge` (cap-05:1657), que A.6 **no** ejecuta → riesgo de servir código antiguo tras el deploy.
54. **X-54 [MEDIO] `npm`/Node y la CI del frontend.** C.5:129-131 exige `vite build` y ausencia de secretos, pero A.6 `ci.yml` no tiene job de frontend; cap-05:1336-1342 añade `actions/setup-node@v4.1.0` con `node-version '22'` y `pnpm`. C.5:140 menciona `dhi.io/node:20-alpine` (Node **20**). Dos versiones de Node.
55. **X-55 [BAJO] D.11-D.16 con markdown roto.** 38 líneas entre `apendice-D.md:174` y `:226` cierran las URLs con `>` en lugar de backtick; toda la mitad final del mapa de referencias se renderiza mal.
56. **X-56 [BAJO] D promete fechas de acceso y no incluye ninguna.** apendice-D.md:3 declara «URL exacta y **fecha de acceso**» y calendario «septiembre 2026»; B usa fechas 2024-05…2025-09; cap-02 y cap-05 usan 2026-09-30 / 2026-09-22. Ningún apéndice tiene fechas de acceso por referencia.
57. **X-57 [BAJO] Fuentes citadas en B que no existen en D.** B.2:38 «RFC 3704» y B.2:46 «RFC 1337»; B.1:24-26 etiqueta «mozilla.org/Mage/SSH» (URL inexistente, usada 3 veces); A.1:14 «fail2ban.readthedocs.io/en/latest/actions.html». Ninguna está en el Apéndice D, que se declara mapa completo.
58. **X-58 [BAJO] `tcp_rfc1337` en B sin respaldo en el capítulo.** B.2:46 endurece `net.ipv4.tcp_rfc1337 = 1`; cap-01:1096-1183 no lo fija. A la inversa, cap-01 fija 3 claves que B.2 no recoge: `kernel.kexec_load_disabled=1` (cap-01:1162), `kernel.unprivileged_bpf_disabled=1` (:1165), `kernel.io_uring_disabled=2` (:1168), `net.ipv4.tcp_timestamps=0` (:1142), `fs.suid_dumpable=0` (:1175), `vm.unprivileged_userfaultfd=0` (:1183).
59. **X-59 [BAJO] `ssl_protocols` default mal informado.** B.3:60 declara el default como `TLSv1 TLSv1.1 TLSv1.2`; en nginx ≥1.23.4 (y por tanto en el 1.29 del corpus) el default es `TLSv1.2 TLSv1.3`. La columna «Default» de B.3 es incorrecta para la versión desplegada.
60. **X-60 [MEDIO] Certbot y `snap` vs `apt`.** cap-03-b:11-16 instala Certbot por **snap** (`snap install --classic certbot` + symlink a `/usr/bin/certbot`), mientras C.3:70-73 verifica `certbot certificates` y `systemctl list-timers certbot.timer` (timer del paquete APT). Con el snap el `certbot.timer` es el del snap y la ruta del hook de C.3:73 debe existir en `/etc/letsencrypt/...` (compartida), pero el checklist no distingue el método de instalación.
61. **X-61 [BAJO] `openssl s_client` sin `-servername`.** C.3:74 y C.7:214 ejecutan `openssl s_client -connect api.dominio.tld:443 -tls1_3` sin `-servername`, mientras cap-03-b:426 sí lo usa. Tras Cloudflare y con dos server blocks, el chequeo puede negociar el certificado equivocado y dar un falso resultado.

### 6.2 Defectos internos del Apéndice A (los scripts no funcionan como están escritos)

62. **A-01 [ALTO] `update-cf-ips` pierde los rangos en silencio.** A.2:74-75 usa `curl -s` sin `-f`: ante un 500/timeout descarga un archivo vacío, regenera `cloudflare-realip.conf` **sin** `set_real_ip_from`, `nginx -t` pasa y recarga el servicio (A.2:93-95). El rollback solo se activa si `nginx -t` falla. Resultado: `access_log` con IP de Cloudflare (rompe C.3:88) y rate-limit por IP de CF.
63. **A-02 [MEDIO] Falta `real_ip_recursive on;`.** A.2:90 solo escribe `real_ip_header CF-Connecting-IP;`; sin `real_ip_recursive` la última IP de la cadena no se resuelve correctamente en escenarios con múltiples saltos.
64. **A-03 [MEDIO] Comentario INI con `;`.** A.1:54 `bantime = 604800  ; 7 días para reincidentes` usa `;` como comentario en un archivo que Fail2Ban lee con ConfigParser (`#`); el valor puede quedar contaminado o producir un `bantime` inválido.
65. **A-04 [ALTO] `active-response` de Wazuh incoherente.** A.3:203-207: el comentario dice «bloquear IP tras 5 intentos fallidos» pero el elemento es `<repeated_offenders timeout="60">10.10.10.10</repeated_offenders>` — `repeated_offenders` define IPs de confianza con timeout ampliado, no bloqueo, y `10.10.10.10` es una IP privada de ejemplo. Además falta el `<command>`/`<rules_id>` que sí tiene cap-05:2604-2608 (`command firewall-drop`, `rules_id 5710,5720,5730`, `timeout 600`).
66. **A-05 [ALTO] Placeholder `MANAGER_IP` en producción.** A.3:210 `<address>MANAGER_IP</address>` literal; cap-05:2770-2781 usa `wazuh.example.tld` con instalación por `WAZUH_MANAGER` (cap-05:2491-2497), que A.3 no documenta.
67. **A-06 [MEDIO] FIM solapado y sin exclusiones de runtime.** A.3:165-169 monitoriza `/etc` completo **y** `/etc/nginx`, `/etc/php`, `/etc/systemd` (subdirectorios de `/etc`), con `realtime="yes"`; cap-05:2503-2608 usa `recursion_level`, `tags`, `restrict`, `nodiff`, `ignore` y `file_limit`. A.3 ignora `storage/logs`, `bootstrap/cache` y `sessions`, que cap-05 excluye explícitamente → tormenta de eventos.
68. **A-07 [MEDIO] `.gz` engañoso en el nombre del dump de MySQL.** A.4:261-266 no comprime con gzip (solo GPG con `--compress-algo zlib`, que es compresión OpenPGP, no gzip) pero nombra el archivo `laravel-$TS.sql.gz.gpg`; cap-04-b:303-315 sí hace `mysqldump | gzip | gpg`.
69. **A-08 [ALTO] `--sse AES256` contra DigitalOcean Spaces.** A.4:272 y 308 piden cifrado server-side `AES256`; Spaces no implementa SSE-S3, y el objeto ya va cifrado con GPG. La bandera es engañosa o provoca error según el cliente.
70. **A-09 [MEDIO] Credenciales en la línea de comandos.** A.4:255 `--password="${MYSQL_BACKUP_PASSWORD}"` (y `--passphrase-file` sí es correcto) quedan visibles en `ps`/`/proc`; lo mismo en cap-04-b:304.
71. **A-10 [BAJO] Asimetría de borrado seguro.** A.4:251 hace `shred -u $TMPDIR/dump.sql` en el script de MySQL y A.4:288 solo `rm -rf` en el de PostgreSQL, aunque el dump en claro es igual de sensible.
72. **A-11 [MEDIO] `wazuh-exporter` en el puerto 9093.** A.5:329 usa `127.0.0.1:9093`, puerto por defecto de **Alertmanager**; si el plan añade Alertmanager (habitual junto a Prometheus, cap-05 §7.6 no lo menciona) hay colisión.
73. **A-12 [ALTO] El unit de A.5 no cumple el estándar de B.9.** B.9:167-184 exige 16 directivas para «cualquier unit»; A.5:321-342 solo aplica 5 (`NoNewPrivileges`, `ProtectSystem`, `ProtectHome`, `PrivateTmp`, `ReadWritePaths`). Faltan `PrivateDevices`, `ProtectKernelTunables/Modules`, `ProtectControlGroups`, `RestrictAddressFamilies`, `RestrictNamespaces`, `RestrictRealtime`, `MemoryDenyWriteExecute`, `CapabilityBoundingSet`, `LockPersonality`, `SystemCallArchitectures`.
74. **A-13 [MEDIO] `prometheus.yml` de A.5 sin intervalos ni alertas.** A.5:346-371 no define `scrape_interval` (cap-05:2812 fija 15s) ni `evaluation_interval` (30s), no incluye `php-fpm 9253` (cap-05:2884) y no define `alertmanager` pese a que C.5:151 exige alertas Slack/Telegram.
75. **A-14 [ALTO] Los 10 SHAs de acciones son inválidos (64 chars).** Ver X-38. Un `uses: ...@<64 hex>` **no** resuelve a un commit y el workflow falla al arrancar.
76. **A-15 [MEDIO] `ci.yml` con `mysql:8.0` en un stack PostgreSQL.** A.6:408-413 levanta `mysql:8.0`; cap-05:1292-1301 levanta `postgres:16-alpine` y `redis:7-alpine`. El job de test de A.6 no prueba el motor del proyecto.
77. **A-16 [BAJO] `lint` sin cache ni PHPStan.** A.6:393-403 solo ejecuta `pint --test`; cap-05:1279-1285 ejecuta `phpstan analyse` y usa `actions/cache`.

### 6.3 Defectos internos del Apéndice B

78. **B-01 [CRÍT] `KbdInteractiveAuthentication no` + `AuthenticationMethods publickey,keyboard-interactive`.** B.1:14 desactiva keyboard-interactive y B.1:27 exige usarlo como segundo factor «para forzar 2FA»; C.1:17 exige TOTP vía `google-authenticator` (que funciona por PAM/keyboard-interactive, definido en E:273). El capítulo resuelve el conflicto con un bloque `Match Address` (cap-01:736-739: `Match Address 203.0.113.0/24,...` + `AuthenticationMethods publickey,keyboard-interactive`, y `capitulo-01.md:846`), dejando `publickey` como valor global (cap-01:659). **B.1 copia los dos valores sin el contexto `Match` y produce una configuración que bloquea el 2FA en todo el host.**
79. **B-02 [ALTO] B.5 `skip-networking=1` + `require_secure_transport=1`.** Sin TCP no existe transporte al que exigir TLS; el par de filas B.5:94 y B.5:98 es internamente contradictorio y además choca con X-01.
80. **B-03 [MEDIO] B.6 `requirepass` + `aclfile`.** B.6:113 y B.6:116 activan los dos mecanismos; con `aclfile` la credencial del usuario `default` se define en el ACL (`user default on >pass`), por lo que `requirepass` es redundante y confuso. C.4:108-110 verifica `requirepass` y `ACL LIST` como si fueran independientes.
81. **B-04 [MEDIO] B.6 no bloquea los comandos que C.4 exige.** B.6:114-115 solo renombra `FLUSHDB` y `CONFIG`; C.4:111 exige además `FLUSHALL`, `DEBUG`, `SHUTDOWN`, `KEYS` (cap-04-b:75-80 sí los bloquea).
82. **B-05 [MEDIO] B.6 sin configuración de persistencia RDB/`save`.** B.6:120 solo activa AOF (`appendonly yes`); cap-04-b:88 y cap-05:2422-2424 definen además `save 900 1 / 300 10 / 60 10000` y `dbfilename`. Si el plan usa el Apéndice B como fuente única, pierde los snapshots RDB que el procedimiento de backup de cap-04-b:345 copia (`cp /var/lib/redis/dump.rdb ...`).
83. **B-06 [MEDIO] B.4 exige COEP que el capítulo comenta.** B.4:84 `Cross-Origin-Embedder-Policy: require-corp`; cap-03-b:191 lo deja **comentado**. Aplicarlo con `CORP: same-origin` rompería recursos embebidos legítimos y cap-05:1178 espera `COEP require-corp` en el frontend: el corpus no decide.
84. **B-07 [MEDIO] B.4 CSP incompleta y `Permissions-Policy` recortada.** B.4:81 omite `form-action 'none'` (cap-03-b:184 sí la tiene) y B.4:80 omite `accelerometer`, `gyroscope`, `magnetometer`, `usb` e `interest-cohort()` respecto de cap-03-b:179.
85. **B-08 [MEDIO] B.3 `ssl_dhparam` sin implementación y con suites DHE activas.** B.3:69; cap-03-b:115 incluye `DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384` y **no** define `ssl_dhparam`, por lo que nginx usaría su parámetro DH interno (1024 bits) — hallazgo típico de auditoría SSL Labs.
86. **B-09 [BAJO] B.3 sin `ssl_ecdh_curve` ni `ssl_conf_command Ciphersuites`.** cap-03-b:118,124 definen ambos; B.3 no los recoge, pese a que `ssl_ecdh_curve X25519:secp384r1` es lo que hace que la cipher list negociada sea la esperada.
87. **B-10 [BAJO] B.3 `ssl_early_data on` sin mitigación en el apéndice.** B.3:68 remite a «(con mitigación)»; la mitigación solo existe como comentario (cap-03-b:139-145) y ningún checklist la verifica.
88. **B-11 [BAJO] B.9 `CapabilityBoundingSet` vacío rompe nginx.** B.9:181 propone conjunto vacío «+ AmbientCapabilities selectivas» sin especificar el valor; cap-03-a:97-98 usa `CAP_NET_BIND_SERVICE` + `AmbientCapabilities=CAP_NET_BIND_SERVICE` y cap-04-a:75 / cap-04-b:39 lo dejan vacío para MySQL/Redis (que no necesitan puertos privilegiados). Un operador que aplique B.9 literalmente impide a nginx escuchar en 443.
89. **B-12 [ALTO] B.9 `MemoryDenyWriteExecute=yes` contra el JIT de opcache.** B.9:180 lo recomienda para cualquier unit; cap-05:2094-2095 activa `opcache.jit = tracing` y `opcache.jit_buffer_size = 64M`. El JIT necesita memoria ejecutable; la combinación rompe una de las dos partes. Ningún capítulo define unit propia de php-fpm que resuelva el conflicto.
90. **B-13 [BAJO] B.8 sin artefactos asociados.** B.8:160-163 exige Dependabot, secret scanning, push protection, actionlint, zizmor y gitleaks; el único archivo con workflows (A.6) no los implementa y no hay `dependabot.yml` en ningún apéndice (cap-05:1822-1849 sí lo trae).
91. **B-14 [BAJO] B.2 con «Default» que no es el default de Ubuntu.** B.2:38 `rp_filter` default 0 y B.2:53-54 `fs.protected_hardlinks/symlinks` default 0: en Ubuntu 24.04 el valor efectivo de fábrica ya es 1 en los tres casos (y cap-01:1096-1097, 1174-1175 lo confirman como valor final). La columna «Default» describe el default del kernel upstream, no el del sistema desplegado.
92. **B-15 [BAJO] B.1 sin `MaxStartups`, `PermitUserEnvironment`, `AuthorizedKeysCommand`.** cap-01:666, 682, 942-947 los fija; B.1 no los recoge (ni como fila de verificación).
93. **B-16 [MEDIO] Fuentes inexistentes o mal atribuidas en B.** «mozilla.org/Mage/SSH» (B.1:24,25,26) no es una URL real; `MaxAuthTries` se atribuye a Mozilla Server Side TLS (B.1:16) cuando es una directiva de OpenSSH (man sshd_config); B.2:38/46 citan RFC 3704 y RFC 1337 que no están en D.

### 6.4 Defectos internos del Apéndice C

94. **C-01 [ALTO] «5 usuarios» y seis nombres.** C.4:98 (ver X-33).
95. **C-02 [ALTO] Checks mutuamente excluyentes de MySQL.** C.4:92 (`SOLO 127.0.0.1`) + C.4:112 (túnel a 3306) + C.7:222 (listener en 127.0.0.1) contra B.5:94 y cap-04-a:100 (`skip-networking`) (ver X-01).
96. **C-03 [MEDIO] SQL de cifrado inválido.** C.4:101 y cap-04-b:520 (ver X-29).
97. **C-04 [BAJO] `redis-cli PING` esperando `NOAUTH`.** C.7:223 asume 6379 en claro; cap-04-b:250-251 fijan `REDIS_PORT=6380` y `REDIS_SCHEME=tls`, con lo que el check falla o conecta a otro servicio.
98. **C-05 [BAJO] `(root, app, migrate, bi_reader, backup, exporter)` no coincide con los nombres reales.** cap-04-a usa `laravel_app`, `laravel_migrate`, `bi_reader`; C.4:99-100 verifica `'laravel_app'@'127.0.0.1'` y `'bi_reader'@'127.0.0.1'` (correcto) pero C.4:98 lista «app», «migrate», «backup», «exporter».
99. **C-06 [MEDIO] C.5:139-140 con tres imágenes base.** `php:8.3-fpm` (Docker Hub) + `dhi.io/php:8.3-fpm` + `dhi.io/node:20-alpine`, frente a `dhi.io/php:8.4-fpm-alpine3.22` de cap-05:1920: el checklist no puede pasar junto con el capítulo.
100. **C-07 [MEDIO] C.5:152 exige `mysqld_exporter` en un stack PostgreSQL** y omite `postgres_exporter` pese a que A.5:356-358 y cap-05:2812-2843 lo despliegan.
101. **C-08 [BAJO] C.7 automatiza 28 de 148 items** y su encabezado promete «PASS/FAIL por item» (C.7:178). Además `exit $FAIL` (C.7:236) devuelve el número de fallos como código de salida (truncado a 8 bits con >255 fallos), lo que confunde con `exit 1`.
102. **C-09 [MEDIO] C.3:79-83 verifican dominios `api.dominio.tld`/`app.dominio.tld` de marcador.** Con el dominio real, los objetivos A+/Eligible no son verificables tal cual; el checklist no indica cómo parametrizar el dominio (mismo problema en todo el corpus: `dominio.tld` en A/B/C y `example.tld` en cap-05).
103. **C-10 [BAJO] C.1:13 concede NOPASSWD a `admin`, usuario inexistente en cap-01** (ver X-03) y solo para `nginx|php8.3-fpm` (no `php8.4-fpm`, cap-05:1660).
104. **C-11 [BAJO] C.1:12 `usermod -s /usr/sbin/nologin root`** deja el sistema sin shell de recuperación; no hay item de acceso de emergencia ni de usuario break-glass (cap-01 no lo define tampoco).
105. **C-12 [BAJO] C.6:158-169 remiten a artefactos inexistentes o mal ubicados:** `guia-maestra-seguridad.md` (existe, `:158`), «Apéndice A bajo control de versiones» (`:159`, pero A no tiene repositorio declarado), «Runbook (cap-05 §5.9)» (`:164`, ver X-50), «Calendario de auditorías externas (trimestral)» (`:167`) frente a deliverable.md:141 («auditoría externa tras 3 meses») y cap-05:3496 («penetration test externo **Anual**») → tres periodicidades distintas de auditoría externa.
106. **C-13 [MEDIO] C.4:114 verifica el bucket de A.4 mientras el capítulo usa restic** (ver X-14), y C.4 no verifica la política 3-2-1-1-0 ni `restic check` (cap-05:2971-2973) ni el objeto inmutable (cap-05:3074-3079).

### 6.5 Defectos internos del Apéndice D y E

107. **D-01 [MEDIO] Sin fechas de acceso y con calendario contradictorio.** (X-56) D:3 promete fechas; B usa 2024-2025, los capítulos 2026-09. `deliverable.md:3` fecha el entregable en 2026-09-30 y `capitulo-02.md:1396` declara «todas las URLs consultadas el 2026-09-30», mientras B.3:61 cita «ssl-config.mozilla.org, 2025-09». Un auditor no puede determinar la vigencia de las fuentes.
108. **D-02 [MEDIO] 38 líneas con markdown roto** (X-55): apendice-D.md:174-226.
109. **D-03 [MEDIO] D.6 fija PostgreSQL 16 y D.5 MySQL 8.0** frente a la premisa PostgreSQL 18 / y frente a E:249 que llama «LTS» a MySQL 8.0 (la LTS es 8.4).
110. **D-04 [BAJO] D.12 documenta Docker (6 refs) sin que B tenga tabla de Docker** y sin que A/C lo desarrollen (salvo C.5:140-142), mientras cap-05 §6 sí: la tabla maestra no gobierna la capa que el mapa de referencias respalda.
111. **D-05 [BAJO] D no incluye nginx.com/`ngx_http_core_module`** que cap-02:1196 cita como fuente de `client_body_timeout`/`send_timeout`, ni `bugs.launchpad.net` (cap-02:718) ni `ssdnodes.com` (cap-02:85) que los capítulos sí usan como `[F]`.
112. **E-01 [MEDIO] Argon2id mal documentado.** E:19: Laravel soporta Argon2id con `Hash::driver('argon2id')`; `'argon'` es Argon2i. cap-05:361-368 define el hashing correcto (`driver argon2id`, memory 65536, threads 1, time 4, verify true) — el glosario contradice al capítulo.
113. **E-02 [MEDIO] Docker «opcional»** (E:113) frente a cap-05 §6 y deliverable.md:78 (ver X-43).
114. **E-03 [BAJO] «Aux» no existe** como servicio de DigitalOcean (E:29); los productos son Backups (C.1:30, cap-01:2153) y Snapshots (cap-05:3074).
115. **E-04 [BAJO] Duplicados y desorden alfabético:** «SE Linux» (E:337) y «SELinux» (E:341); «Single Sign-On (SSO)» (E:355) y «SSO» (E:383); «Proxy / Reverse Proxy» (E:287) y «Reverse proxy» (E:319); «CP (Certificate Pinning)» indexado bajo CP (E:81); «CORS Preflight» (E:79) después de «CORS Allowed Origins» (E:75). 17 entradas se declaran irrelevantes (E:39, 49, 57, 77, 109, 127, 193, 203, 215, 221, 247, 277, 337, 343, 387, 407, 437).
116. **E-05 [ALTO] Ausencia total de dominio y normativa.** Ni el glosario ni D ni B ni C contienen términos o referencias de la sede electrónica colombiana (Ley 527/1999, Ley 1581/2012, Decreto 1078/2015, GESI/MinTIC, CONPES 3995, acuse de recibo, radicado, notificación electrónica, firma electrónica, archivo digital). El glosario dedica entrada a GDPR (E:157) y CCPA (E:51) pero ninguna a la ley de protección de datos aplicable en Colombia (Ley 1581). Si el plan debe ser de una sede electrónica colombiana, **el corpus fuente no lo cubre**.

### 6.6 Contradicciones con la premisa del encargo (resumen ejecutivo)

| Premisa | Fuente dice | Severidad |
|---|---|---|
| PHP-FPM 8.5 | 8.3 (A.6, C.1, C.5, cap-03) y 8.4 (cap-05, Dockerfile); «8.3+» en E:289 | **[ALTO]** — X-42 |
| PostgreSQL 18 | PostgreSQL 16 en D.6, E:281, cap-04 §4.8 («alternativa»), cap-05:3, guía-maestra:5, deliverable:5; MySQL 8.0 es el motor **primario** (cap-04-a:1) | **[ALTO]** — X-36, D-03 |
| Despliegue con Docker | Nativo en A.6/C.5/cap-04; contenedor en cap-05 §6; E:113 «opcional» | **[ALTO]** — X-43 |
| Sede electrónica colombiana | 0 referencias normativas colombianas en todo el corpus; glosario solo GDPR/ISO/NIST/OWASP | **[CRÍT]** para el alcance del plan — E-05 |
| Laravel 13 | Coincide (E:219, D.9, cap-05:3) | OK |
| Redis 7 | Coincide (E:315, B.6, cap-04-b:17) | OK (con X-18) |
| nginx 1.29 | Coincide (E:251, C.3:58, cap-03-a:3) | OK |
| DigitalOcean | Coincide (D.4, cap-01 §2) | OK (región: cap-01:130 usa `fra1`, guía-maestra:118 usa `SFO3`; cap-05 usa `nyc3` para Spaces — inconsistencia menor de región) |

---

## 7. Hechos verificados (`hecho — archivo:línea`)

1. El Apéndice A tiene 7 secciones (A.1–A.7) y contiene 12 artefactos: acción y jail de Fail2Ban, cron de IPs de Cloudflare, `ossec.conf`, reglas Wazuh, dos scripts de backup, unit y scrape de exporters, dos workflows de GitHub Actions y el bloque de ModSecurity — `apendice-A.md:7,61,109,241,317,375,505`.
2. El Apéndice A **no** contiene hooks de Certbot, snippets de nginx ni units systemd de nginx/php-fpm/MySQL/Redis, aunque el checklist los exige — `apendice-A.md:9-530` frente a `apendice-C.md:62-64,73`.
3. El Apéndice B tiene 9 secciones y **134 filas de datos** (152 líneas de tabla menos 9 encabezados y 9 separadores) — `apendice-B.md:9-184`.
4. La acción de Fail2Ban del Apéndice A nunca se referencia desde ningún jail — `apendice-A.md:23-57`; el capítulo sí la referencia con **otro** nombre de archivo — `capitulo-02.md:794-795,876`.
5. La acción de cap-02 autentica con `X-Auth-Email` + `X-Auth-Key` (Global API Key) pese a que el propio capítulo la prohíbe — `capitulo-02.md:890-891` frente a `capitulo-02.md:910-911`.
6. Los 10 SHAs de acciones del Apéndice A tienen 64 caracteres hexadecimales, no 40 — `apendice-A.md:396,399,415,418,431,435,443,472,476,488` (verificado con `grep -o '@[0-9a-f]\{7,\}'`).
7. El SHA real de `actions/checkout` v4.2.2 aparece con 24 caracteres añadidos (`11bd71901bbe5b1630ceea73d27597364c9af683` + `40f9f97f9c5b6e1f10b97b6b`) — `apendice-A.md:396` frente a `capitulo-05.md:1262` (que usa el SHA correcto de 40).
8. `deploy.yml` declara `environment:` a nivel de workflow, sintaxis inválida en GitHub Actions (es clave de job) — `apendice-A.md:460-462` frente a `capitulo-05.md:1598-1603`.
9. `deploy.yml` usa `aws-actions/configure-aws-credentials` con `role-to-assume` para un «OIDC de DigitalOcean», que no es un proveedor AWS STS — `apendice-A.md:476-479`; el capítulo 5 despliega con clave SSH y `rsync` a `releases/$SHA` — `capitulo-05.md:1620-1629`.
10. `deploy.yml` recarga `php8.3-fpm` y hace `git pull` en `/var/www/api.dominio.tld` (modelo nativo mutable) — `apendice-A.md:494-500` frente al modelo de releases/symlink — `capitulo-05.md:1640-1668`.
11. El script de checklist solo verifica 28 items de 148 y devuelve el nº de fallos como código de salida — `apendice-C.md:175-237`.
12. El Apéndice C fija los objetivos externos: SSL Labs API A+, SSL Labs app A+, Mozilla Observatory A+, securityheaders.com A+, HSTS Preload Eligible — `apendice-C.md:79-83`.
13. El checklist exige que nginx no escuche en 80 y simultáneamente server blocks «443 + 80 → 301» — `apendice-C.md:87` frente a `apendice-C.md:66-67`.
14. C.4 lista «5 usuarios» y enumera seis nombres; cap-04 solo crea tres usuarios de aplicación — `apendice-C.md:98` frente a `capitulo-04-parte-a.md:186-198`.
15. B.5 recomienda `skip-networking = 1` mientras C.4/C.7 exigen un listener TCP en `127.0.0.1:3306` — `apendice-B.md:94` frente a `apendice-C.md:92,112,222`.
16. C.4 verifica el cifrado de tablespace con un `SELECT` sobre una columna inexistente en `information_schema.tables` — `apendice-C.md:101` y `capitulo-04-parte-b.md:520`.
17. El Apéndice B no incluye ninguna sección de PostgreSQL, PHP-FPM/php.ini/opcache, nginx.conf, Certbot, Fail2Ban, Wazuh, Docker ni auditd — `apendice-B.md:1-184` frente a `capitulo-04-parte-a.md:301-372`, `capitulo-05.md:2060-2120`, `apendice-C.md:36-50,70-73,140-142`.
18. B.1 desactiva keyboard-interactive y a la vez lo exige como segundo factor para 2FA, mientras C.1 exige TOTP — `apendice-B.md:14,27` frente a `apendice-C.md:17`; el capítulo lo resuelve con `Match Address` — `capitulo-01.md:736-739`.
19. B.1 afirma «Solo Ed25519, no RSA» pero cap-01 configura host key RSA y `HostKeyAlgorithms` con `rsa-sha2-*` — `apendice-B.md:23` frente a `capitulo-01.md:642-650`.
20. B.1 usa `AllowUsers admin deployer` mientras cap-01 crea `ops`, `deploy` y `auditor` — `apendice-B.md:21` frente a `capitulo-01.md:380-384,672`.
21. B.2 propone `net.ipv4.ip_forward = 0` y cap-05 despliega un stack Docker que requiere forwarding — `apendice-B.md:37`, `capitulo-01.md:1100` frente a `capitulo-05.md:2270-2282`.
22. B.9 exige `MemoryDenyWriteExecute=yes` para cualquier unit mientras cap-05 activa el JIT de opcache — `apendice-B.md:180` frente a `capitulo-05.md:2094-2095`.
23. B.9 propone `CapabilityBoundingSet` vacío mientras nginx necesita `CAP_NET_BIND_SERVICE` — `apendice-B.md:181` frente a `capitulo-03-parte-a.md:97-98`.
24. B.4 exige `Cross-Origin-Embedder-Policy: require-corp` que cap-03 deja comentado — `apendice-B.md:84` frente a `capitulo-03-parte-b.md:191`.
25. B.3 exige `ssl_dhparam 2048` que ningún capítulo define, mientras la cipher list incluye suites `DHE-RSA-*` — `apendice-B.md:69` frente a `capitulo-03-parte-b.md:115`.
26. B.7 y C.5 verifican `CORS_ALLOWED_ORIGINS`, variable que Laravel no lee y que ningún capítulo define — `apendice-B.md:140`, `apendice-C.md:127` frente a `capitulo-05.md:286-296`.
27. El `.env` de cap-05 pone `TRUSTED_PROXIES=*` y el propio capítulo lo prohíbe 130 líneas después — `capitulo-05.md:130` frente a `capitulo-05.md:263`.
28. cap-05 define `DB_HOST=10.0.0.5`, `DB_HOST=postgres` (compose) y cap-04 exige `127.0.0.1` — `capitulo-05.md:134`, `capitulo-05.md:2295`, `capitulo-04-parte-a.md:409`.
29. El contenedor Redis de cap-05 arranca con `--bind 0.0.0.0` contra la regla dura de cap-04 — `capitulo-05.md:2428` frente a `capitulo-04-parte-b.md:59`.
30. El pool FPM define `pm.max_children = 50` con `memory_limit = 256M` dentro de un contenedor limitado a 512 MB — `capitulo-05.md:2108,2103,2298-2300`.
31. Los exports de Fail2Ban del `sshd` jail tienen tres valores distintos según el documento (A.1, cap-02 y cap-05) — `apendice-A.md:30-40`, `capitulo-02.md:803-811`, `capitulo-05.md:2949-2956`.
32. El drill de restauración es «semanal» en cap-05 y «mensual» en cap-04, C.4, C.6, guía-maestra y deliverable — `capitulo-05.md:3092` frente a `capitulo-04-parte-b.md:429`, `apendice-C.md:115,166`, `guia-maestra-seguridad.md:84`, `deliverable.md:74`.
33. La política de backup del capítulo 5 usa restic sobre `example-prod-backups` con retención 7/4/6 mientras el Apéndice A sube dumps GPG a `backup-vps-secure` — `capitulo-05.md:2994,3045` frente a `apendice-A.md:269-276`.
34. El Apéndice A cifra con `--sse AES256` contra DigitalOcean Spaces y una passphrase en `/etc/dropbear/`, mientras el capítulo 5 usa `/etc/restic/passphrase` — `apendice-A.md:264,272` frente a `capitulo-05.md:2981,3138`.
35. El cuerpo de fail2ban del checklist exige bloquear `FLUSHALL`, `DEBUG`, `SHUTDOWN` y `KEYS`, pero B.6 solo bloquea `FLUSHDB` y `CONFIG` — `apendice-C.md:111` frente a `apendice-B.md:114-115`, `capitulo-04-parte-b.md:75-80`.
36. La configuración de rate-limit de nginx tiene dos conjuntos de zonas incompatibles (cap-02 en `nginx.conf` con `per_ip`/`per_server`; cap-03 en `conf.d/rate-limit.conf` con `api`/`app`) y C.3 exige un `nginx.conf` minimal — `capitulo-02.md:1144-1155` frente a `capitulo-03-parte-b.md:230-237` y `apendice-C.md:61`.
37. C.2 exige `client_body_timeout 12s`/`send_timeout 10s` y cap-02 fija `10s`/`30s` — `apendice-C.md:50` frente a `capitulo-02.md:1184-1185`.
38. El egreso permitido (53/80/443/123/587) bloquea el puerto 22 saliente y el 1514 que el agente Wazuh necesita — `apendice-C.md:35`, `capitulo-02.md:137-148` frente a `apendice-A.md:211`, `capitulo-05.md:2780`.
39. El runbook del checklist apunta a «cap-05 §5.9», sección que en realidad trata de secret scanning; el runbook es §9 — `apendice-C.md:164` frente a `capitulo-05.md:1890` y `capitulo-05.md:3147`.
40. El Apéndice D contiene 172 líneas de referencia en 16 secciones y ninguna fecha de acceso, pese a que su encabezado las promete — `apendice-D.md:3,7-226`.
41. 38 líneas de D (174-226) cierran las URLs con `>` en lugar de backtick y rompen el markdown — `apendice-D.md:174-226`.
42. D.6 documenta PostgreSQL **16**, no 18, y D.5 MySQL 8.0 — `apendice-D.md:93,104`.
43. El glosario define 223 términos, de los cuales 17 se declaran irrelevantes y varios están duplicados semánticamente — `apendice-E.md:5-449` (líneas 39, 49, 57, 77, 109, 127, 193, 203, 215, 221, 247, 277, 337, 343, 387, 407, 437; duplicados en 337/341, 355/383, 287/319).
44. El glosario atribuye a Laravel un `Hash::driver('argon')` para Argon2id, cuando ese driver es Argon2i — `apendice-E.md:19` frente a `capitulo-05.md:361-368`.
45. No existe ninguna referencia a legislación colombiana ni al concepto «sede electrónica» en apéndices ni capítulos — búsqueda exhaustiva de `Ley 527|Ley 1581|Decreto 1078|MinTIC|CONPES|GESI|sede electr` en `apendice-*.md`, `capitulo-*.md`, `deliverable.md`, `guia-maestra-seguridad.md`: 0 coincidencias.
46. El corpus declara MySQL 8.0 como motor primario y PostgreSQL 16 como alternativa — `capitulo-04-parte-a.md:1,15,301`, `guia-maestra-seguridad.md:5,22`, `deliverable.md:5`.
47. El corpus mezcla PHP 8.3 (`apendice-A.md:500`, `apendice-C.md:13`, `capitulo-03-parte-a.md:337`) y PHP 8.4 (`capitulo-05.md:3,1530,1920`).
48. cap-05 §6 prohíbe el bind mount de código en producción y usa `read_only`, `cap_drop: [ALL]` y secretos en `/run/secrets`, mientras el Apéndice A despliega con `git pull` — `capitulo-05.md:2241-2256,2474-2479` frente a `apendice-A.md:494-500`.
49. El `prometheus.yml` del capítulo 5 tiene un typo `job_job:` en el job de redis y no incluye `wazuh-exporter`, mientras A.5 no incluye el exporter de php-fpm — `capitulo-05.md:2832,2812-2843` frente a `apendice-A.md:346-371`.
50. El checklist exige `mysql` con `require_secure_transport=ON` y cap-04 documenta la administración con `--ssl-mode=DISABLED` a través del túnel SSH — `apendice-C.md:94,102` frente a `capitulo-04-parte-b.md:229-234`.
51. cap-01 fija `net.ipv4.tcp_max_syn_backlog = 2048` y cap-02 fija `4096` para el mismo parámetro — `capitulo-01.md:1136` frente a `capitulo-02.md:1110-1123`.
52. B.2 cita como fuente `RFC 3704` y `RFC 1337`, que no existen en el mapa de referencias del Apéndice D — `apendice-B.md:38,46` frente a `apendice-D.md:7-226`.
53. El checklist C.5 exige `mysqld_exporter` y omite `postgres_exporter`, que sí despliegan A.5 y cap-05 — `apendice-C.md:152` frente a `apendice-A.md:356-358`, `capitulo-05.md:2884`.
54. El entregable promete que el Apéndice A incluye «systemd units, snippets, hooks» — `guia-maestra-seguridad.md:228` — y el apéndice no contiene snippets ni hooks (el hook está en `capitulo-03-parte-b.md:61-77`).
55. `deliverable.md:3` fecha el entregable el 2026-09-30 y cap-02 declara las URLs consultadas el 2026-09-30, mientras B.3 cita fuentes de 2025-09 y B.1 de 2024-05 — `deliverable.md:3`, `capitulo-02.md:1396`, `apendice-B.md:12,61`.

---

## 8. Recomendaciones de corrección priorizada

| # | Acción | Defectos que cierra |
|---|---|---|
| 1 | Decidir el stack real (PHP 8.5/8.4/8.3, PostgreSQL 18/16 vs MySQL, Docker sí/no) y reescribir la cabecera de los 5 apéndices y de los capítulos 04/05 con un único valor. | X-42, X-43, X-36, D-03, E-02 |
| 2 | Resolver `skip-networking` vs listener TCP y alinear B.5 con C.4/C.7. | X-01, C-02, B-02 |
| 3 | Unificar Fail2Ban en **un** `jail.local` + **una** acción Cloudflare (Bearer token, unban con lookup) y borrar las variantes de A.1/cap-02/cap-05. | X-08, X-09, X-10, X-11, X-12, A-03, C-04 |
| 4 | Reemplazar los 10 SHAs de 64 caracteres por SHAs reales de 40 y mover `environment:` al job en `deploy.yml`; añadir actionlint/zizmor/gitleaks al CI de A.6. | A-14, X-38, X-39, X-40 |
| 5 | Añadir al Apéndice A los artefactos que su checklist exige: hook de Certbot, 4 snippets de nginx, units systemd de nginx/php-fpm/MySQL/Redis y `pre-prod-check.sh`. | X-49, A-12, A-13 |
| 6 | Corregir el modelo de capacidad de PHP-FPM (workers × memoria × límite de contenedor) y documentarlo en B. | X-16 |
| 7 | Rediseñar el modelo de usuarios (admin/ops/deploy/deployer/backup) de forma única para B, C, A.4 y cap-01/cap-05. | X-03, X-41, C-10, C-05 |
| 8 | Añadir a B las 10 secciones ausentes (PostgreSQL, php-fpm, nginx.conf, Certbot, Fail2Ban, Wazuh, Docker, UFW/nftables, auditd/AIDE, cifrado en reposo) y a D las fechas de acceso, arreglando el markdown de las líneas 174-226. | E-01, D-01, D-02, D-04, D-05 |
| 9 | Sustituir el egreso permitido para no bloquear 1514 (Wazuh) ni 22 (git/rsync saliente) y decidir la coexistencia UFW/nftables/Docker. | X-26, X-27, X-25, X-45 |
| 10 | Decidir **una** política de backup y **una** frecuencia de drill, y reflejarlas en A.4, cap-04, cap-05 y C.4/C.6. | X-13, X-14, X-15, X-20, C-13 |
| 11 | Añadir al corpus la capa normativa y de dominio colombiana (Ley 527/1999, Ley 1581/2012, Decreto 1078/2015, GESI, sede electrónica, firma electrónica) y sus referencias en D y términos en E. | E-05, X-60… |
| 12 | Reparar las referencias cruzadas rotas (`cap-05 §5.9` → §9; apéndices A/B internos de cap-05; `dominio.tld` paramétrico). | X-50, X-51, C-09 |

---

**Fin del informe.** Archivos auditados íntegramente: `apendice-A.md` (530 l.), `apendice-B.md` (184 l.), `apendice-C.md` (239 l.), `apendice-D.md` (226 l.), `apendice-E.md` (449 l.). Verificación cruzada: `capitulo-01.md`, `capitulo-02.md`, `capitulo-03-parte-a/b.md`, `capitulo-04-parte-a/b.md`, `capitulo-05.md`, `deliverable.md`, `guia-maestra-seguridad.md`.

### 6.7 Addendum — hallazgos de la verificación cruzada capítulo por capítulo

63. **X-62 [ALTO] PostgreSQL 18 aparece exactamente una vez en todo el corpus.** `capitulo-02.md:48` rotula una capa como «Postgres 18 / SQLite WAL»; el resto del corpus fija **PostgreSQL 16** (`capitulo-04-parte-a.md:1,3,301`, `capitulo-05.md:3`, `apendice-D.md:104`, `apendice-E.md:281`, `guia-maestra-seguridad.md:5`, `deliverable.md:5`). El único eco de la premisa PostgreSQL 18 es un rótulo de diagrama, no una configuración.
64. **X-63 [CRÍT] El propio bloque de verificación de cap-01 es contradictorio.** cap-01:993-1012 declara como salida esperada de `sshd -T` **a la vez** `kbdinteractiveauthentication no` y `authenticationsmethods publickey,keyboard-interactive` (cap-01:1004), mientras la configuración solo define `AuthenticationMethods publickey` de forma global (cap-01:659) y reserva el segundo factor para un bloque `Match Address` (cap-01:736-739). Un operador que valide con la tabla del capítulo verá como «correcto» un estado que no corresponde a la configuración global. Esto agrava B-01: el Apéndice B copia los dos valores sin el contexto `Match` y sin la advertencia de cap-01:849.
65. **X-64 [ALTO] El túnel SSH obligatorio del checklist no está permitido por la configuración SSH global.** C.4:112 exige `ssh -L 13306:127.0.0.1:3306 deployer@droplet`, cap-04-b:184-220 lo define como vía única de administración, pero cap-01:677 fija `AllowTcpForwarding no` y `AllowStreamLocalForwarding no` (cap-01:678); el capítulo solo sugiere una excepción vía `Match User` (cap-01:694) **sin escribirla**. B.1:30 propone el valor «`local` … o `no`», y guía-maestra:187-188 atribuye las llaves SSH al usuario `deployer` del CI (cap-05:1622), no a `deploy`. El procedimiento de administración de datos no es ejecutable con la configuración publicada.
66. **X-65 [MEDIO] `AllowUsers`/2FA y los usuarios reales.** cap-01:814 exige que `ops`, `deploy` y `auditor` ejecuten `google-authenticator`; cap-01:672 propone `AllowUsers ops deploy auditor`; B.1:21 fija `AllowUsers admin deployer`; C.1:17 exige TOTP en `admin` y `deployer`. Los tres conjuntos de cuentas son disjuntos en al menos un elemento.
67. **X-66 [BAJO] Versión del cliente Fail2Ban incoherente con el paquete.** cap-02:715 muestra `fail2ban-client version → 0.10.2` mientras cap-02:712 y cap-02:264 verifican el paquete `1.0.2-3ubuntu0.1`. Un `fail2ban-client` de la 1.0.2 reporta `1.0.2`, no `0.10.2`.
68. **X-67 [MEDIO] El egreso del SIEM por rsyslog tampoco está permitido.** cap-01:2015-2023 configura `omfwd` a `logs.internal.example.com` por **TCP 6514** con TLS; C.2:35 y cap-02:137-148 deniegan el rango `588-7999` en salida. Igual que con Wazuh 1514 (X-26), el reenvío de logs definido en cap-01 es imposible con el firewall definido en cap-02.
69. **X-68 [MEDIO] `ufw limit 8000/tcp` y un backend que no escucha en 8000.** cap-02:296 abre y limita el puerto 8000 «App backend rate-limited», mientras cap-02:1165 publica la app en `http://127.0.0.1:8000` y cap-05:2466 expone `127.0.0.1:8080:8080` en el contenedor nginx. Tres puertos distintos para el mismo plano; C.2 no verifica ninguno.
70. **X-69 [MEDIO] La fecha del corpus no es homogénea.** cap-02:6 y cap-02:1396 fechan el documento y sus fuentes en `2026-09-30`; cap-05 cita `2026-09-22`; B.3:61 cita «ssl-config.mozilla.org, 2025-09»; B.1:13 cita «2024-05»; apendice-D.md:3 declara calendario «septiembre 2026» sin fechas. `deliverable.md:3` fecha el entregable `2026-09-30`. (Refuerza X-56/D-01.)
71. **X-70 [ALTO] El «Apéndice A» y el «Apéndice B» de cap-05 son otros apéndices.** cap-05:3500 «Apéndice A — Inventario de secretos» (25 secretos con su rotación) y cap-05:3536 «Apéndice B — Referencias bibliográficas» (con ~40 fuentes fechadas) **duplican el contenido** que el entregable asigna a los apéndices D y E, con numeración y contenido distintos (p. ej. cap-05:3578 cita «NIST SP 800-61r3» mientras D:203 cita «SP 800-61 r2»). Un plan que cite «Apéndice B» queda ambiguo.

**Conteo final de defectos catalogados: 71** (X-01…X-70 y los internos A-01…A-16, B-01…B-16, C-01…C-13, D-01…D-05, E-01…E-05 referenciados dentro de ellos con su ID local).

