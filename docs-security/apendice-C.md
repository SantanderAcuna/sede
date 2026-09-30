# Apéndice C — Checklist de aceptación previa a producción

> Cada item es ejecutable. Marca `[x]` cuando verifiques. No se admite producción con items sin marcar.

---

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

## C.7 — Comprobación final con un solo script

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

Guárdalo como `/usr/local/bin/pre-prod-check.sh` y ejecútalo antes de cada deploy importante.
