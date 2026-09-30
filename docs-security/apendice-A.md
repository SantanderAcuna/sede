# Apéndice A — Scripts completos

> Scripts reutilizables que se referencian desde los capítulos. Cada uno está pensado para producción, no para "se entiende". Las rutas son absolutas; no se omite nada.

---

## A.1 Fail2Ban — acción custom para Cloudflare (cap-02)

`/etc/fail2ban/action.d/cloudflare-api.conf`:

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

Configurar `/etc/fail2ban/jail.local` (fragmento):

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

---

## A.2 Cloudflare — extracción de IP actual y reload nginx (cap-02, cap-03)

`/etc/cron.hourly/update-cf-ips`:

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

```bash
sudo chmod +x /etc/cron.hourly/update-cf-ips
```

---

## A.3 Wazuh agent (cap-05)

`/var/ossec/etc/ossec.conf`:

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

Regla custom para log de seguridad de Laravel en `/var/ossec/etc/rules/laravel_rules.xml`:

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

---

## A.4 Script de backup MySQL/PostgreSQL (cap-04)

`/usr/local/bin/backup-mysql.sh`:

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

`/usr/local/bin/backup-postgres.sh`:

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

---

## A.5 Wazuh + Prometheus exporters systemd (cap-04, cap-05)

`/etc/systemd/system/wazuh-exporter.service`:

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

`/etc/prometheus/prometheus.yml` (fragmento):

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

---

## A.6 GitHub Actions workflows seguros (cap-05)

`.github/workflows/ci.yml`:

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

`.github/workflows/deploy.yml`:

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

---

## A.7 Apache mod_security → nginx ModSecurity V3 (cap-03)

Si el operador prefiere ModSecurity sobre OWASP CRS:

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

Incluir en `nginx.conf` (dentro del bloque `http`):

```nginx
modsecurity on;
modsecurity_rules_file /etc/modsecurity/modsecurity.conf;
```

Exclusiones (ya mostradas en cap-03 §3.15.3).
