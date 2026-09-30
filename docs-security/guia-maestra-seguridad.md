# Guía Maestra de Seguridad DevOps — VPS Laravel 13 + Vue 3 en DigitalOcean

**Versión:** 1.0
**Fecha:** 2026-09-30
**Stack objetivo:** Ubuntu 24.04 LTS, nginx 1.29, MySQL 8.0 / PostgreSQL 16, Redis 7, Laravel 13 API, Vue 3 + TypeScript, GitHub Actions, Cloudflare, Let's Encrypt, DigitalOcean.
**Audiencia:** operador DevOps con Linux básico que necesita desplegar un stack Laravel + Vue desacoplado con seguridad de grado producción.
**Nivel:** deep-engineering-handbook (PhD-level).

---

## Índice ejecutivo

Esta guía cubre, en orden secuencial, **todos los pasos** para convertir un Droplet Ubuntu 24.04 limpio en un servidor hardened que sirve Laravel 13 + Vue 3 + JSON:API con calificación **A+ en SSL Labs, A+ en securityheaders.com y defensa en profundidad contra L3/L4/L7**.

Los cinco capítulos se complementan: cada uno presupone que el anterior está aplicado.

| # | Capítulo | Cubre | Palabras |
|---|---|---|---|
| 1 | Hardening del SO | Droplet, SSH/MFA, usuarios, sysctl, auditd, AppArmor, AIDE, ClamAV, parches | 12.853 |
| 2 | Red y borde | DO Cloud Firewall, UFW, nftables, Fail2Ban, DDoS por capas | 8.535 |
| 3 | Nginx + Cloudflare + TLS | Reverse proxy, ssl-params, security-headers, Certbot, mTLS Cloudflare, A+ SSL Labs | 3.805 |
| 4 | MySQL/PostgreSQL + Redis | Sin exposición, caching_sha2_password, cifrado, túnel SSH, backups AES-256 | 3.562 |
| 5 | Laravel + Vue + CI/CD + IDS | Sanctum, JSON:API, GitHub Actions seguro, Docker hardened, Wazuh, runbook | 14.559 |

Total: **43.314 palabras** + esta consolidación y los apéndices.

---

## Orden lógico de implementación (cronograma)

```
DÍA 1 — Hardening SO + SSH (cap-01)
   Crear droplet Ubuntu 24.04
   Usuario admin con sudo + clave SSH + 2FA TOTP
   sysctl endurecido, auditd, AppArmor profiles
   AIDE base inicial, ClamAV instalado
   unattended-upgrades activo

DÍA 2 — Firewall Cloud + UFW + nftables + Fail2Ban (cap-02)
   DO Cloud Firewall (primera capa, antes del SO)
   UFW + nftables con rate limit y blacklist dinámica
   Fail2Ban con jails sshd, nginx-*, recidive
   Logs centralizados a journald + rsyslog

DÍA 3 AM — Nginx + TLS + Let's Encrypt (cap-03)
   nginx mainline 1.29 desde repo oficial
   systemd hardening, server blocks api + frontend
   Certbot + certs para api.dominio.tld y app.dominio.tld
   ssl-params.conf (TLS 1.2/1.3, Mozilla intermediate)
   security-headers.conf (CSP estricta, HSTS 2 años)

DÍA 3 PM — Cloudflare delante (cap-03)
   DNS en Cloudflare con proxy
   SSL/TLS mode Full (Strict) + Authenticated Origin Pulls
   WAF + Bot Fight Mode + Rate Limit L7
   Page Rules para cache de estáticos

DÍA 4 — MySQL/PG + Redis sin exposición (cap-04)
   bind-address 127.0.0.1, skip-networking recomendado
   caching_sha2_password (mysql) / scram-sha-256 (pg)
   require_secure_transport ON
   Túneles SSH operativos
   Backups cifrados (gpg --symmetric AES256)

DÍA 5 — Laravel 13 + Sanctum + JSON:API (cap-05)
   composer install --no-dev --optimize-autoloader
   APP_DEBUG=false, APP_KEY rotada
   Sanctum con abilities granulares
   JsonApiResource para todas las respuestas

DÍA 6 — Vue 3 build + CSP + CORS (cap-05)
   vite build con sourcemap false
   VITE_*, sin secretos en bundle
   CSP nonce-based, sin unsafe-inline/unsafe-eval

DÍA 7 — GitHub Actions + Docker hardened + Wazuh (cap-05)
   Actions pinneadas a SHA de 40 chars
   Permissions mínimos por job
   OIDC a DigitalOcean sin secretos estáticos
   Wazuh agent + reglas Laravel custom + FIM

DÍA 8 — Backups cifrados + drill (cap-04 + cap-05)
   Cron 7/30/365 rotación
   Drill de restore mensual (regla: si no probaste restore, no es backup)

DÍA 9 — Auditoría final
   Qualys SSL Labs → A+
   Mozilla Observatory → A+
   securityheaders.com → A+
   HSTS preload → eligible
   Verificación bind en 0.0.0.0 → cero

DÍA 10 — Runbook + simulacro (cap-05)
   Escenarios: SSH comprometido, app comprometida, DDoS, ransomware
   Post-mortem obligatorio blameless
   Rotación de secretos programada
```

---

## Diagrama de la arquitectura final

```
                         Internet
                             │
                             ▼
                     ┌───────────────────┐
                     │   Cloudflare Edge  │
                     │  WAF + DDoS + Bot │
                     │  Rate Limit L7     │
                     │  Full (Strict)     │
                     │  AOP (mTLS)        │
                     └─────────┬─────────┘
                               │ HTTPS
                               ▼
                ┌──────────────────────────────────┐
                │  Droplet Ubuntu 24.04 LTS        │
                │  DigitalOcean, region SFO3       │
                ├──────────────────────────────────┤
                │  DO Cloud Firewall (cap-02)      │
                │  ↑ TCP/80, 443 solo              │
                │    desde rangos Cloudflare       │
                ├──────────────────────────────────┤
                │  nftables (cap-02)               │
                │  ↓ default DROP                  │
                │  ↓ allow established/related     │
                │  ↓ allow tcp/443,80 from CF      │
                │  ↓ rate-limit 6/min ssh          │
                ├──────────────────────────────────┤
                │  UFW (cap-02)                    │
                │  ↓ ssh rate limit                │
                ├──────────────────────────────────┤
                │  Fail2Ban (cap-02)               │
                │  ↓ jails sshd, nginx-*           │
                │  ↓ recidive                      │
                │  ↓ custom action Cloudflare API  │
                ├──────────────────────────────────┤
                │  nginx 1.29 mainline (cap-03)    │
                │  ├─ api.dominio.tld → PHP-FPM 8.3│
                │  │   → Laravel 13                │
                │  └─ app.dominio.tld → estáticos  │
                │      Vue 3 dist/                 │
                ├──────────────────────────────────┤
                │  systemd hardening               │
                │  ├─ NoNewPrivileges              │
                │  ├─ ProtectSystem=strict         │
                │  ├─ CapabilityBoundingSet (CF)   │
                │  └─ MemoryDenyWriteExecute       │
                ├──────────────────────────────────┤
                │  MySQL 8.0 (cap-04)              │
                │  ├─ bind 127.0.0.1               │
                │  ├─ caching_sha2_password        │
                │  ├─ require_secure_transport     │
                │  ├─ InnoDB tablespace enc        │
                │  └─ audit plugin                 │
                ├──────────────────────────────────┤
                │  Redis 7 (cap-04)                │
                │  ├─ bind 127.0.0.1               │
                │  ├─ protected-mode yes           │
                │  ├─ requirepass + ACL            │
                │  └─ TLS opcional (6380)          │
                ├──────────────────────────────────┤
                │  AppArmor (cap-01)               │
                │  ├─ nginx profile enforce        │
                │  ├─ php-fpm profile enforce      │
                │  └─ mysql profile enforce        │
                ├──────────────────────────────────┤
                │  auditd (cap-01) → Wazuh (cap-5) │
                │  ├─ /etc/passwd, /etc/shadow     │
                │  ├─ execve                       │
                │  └─ cambios de red               │
                ├──────────────────────────────────┤
                │  Backups cifrados (cap-04)       │
                │  ├─ mysqldump + gpg --symmetric  │
                │  ├─ pg_dump + gpg                │
                │  ├─ restic a DO Spaces cifrado   │
                │  └─ rotación 7/30/365            │
                └──────────────────────────────────┘
```

---

## Dependencias cruzadas explícitas

| Token | Creado en | Consumido en | Mecanismo |
|---|---|---|---|
| Llaves SSH ed25519 admin | cap-01 §1.4 | cap-05 §1.5 deploy | `~/.ssh/authorized_keys` con `command=` y `from=` restrictivos |
| Llave SSH ed25519 deployer | cap-01 §1.4 | cap-05 CI/CD | secretos de GitHub |
| Token Cloudflare API | cap-03 §3.13 | cap-02 §2.5 Fail2Ban action | script en `/etc/fail2ban/action.d/cloudflare-api.conf` |
| Cert Let's Encrypt | cap-03 §3.8 | cap-04 §4.5 TLS MySQL (reuso de CA opcional) | misma jerarquía de CA |
| Usuario MySQL `laravel_app` | cap-04 §4.4 | cap-05 §5.12 `.env` DB_USERNAME | secrets en Vault, no en repo |
| Password Redis `laravel_app` | cap-04 §4.11 | cap-05 §5.14 `.env` REDIS_PASSWORD | secrets en Vault |
| APP_KEY Laravel | cap-05 §5.1 | cap-05 §5.1 Laravel Encryption | `php artisan key:generate`, rotada trimestral |
| Master key InnoDB | cap-04 §4.6 | cap-04 §4.6 rotación trimestral | `/var/lib/mysql-keyring/keyring` + backup externo |
| Wazuh manager | cap-05 §5.7 | cap-01 §1.6 (auditd feed) | audisp-remote + reglas custom |

---

## Cómo leer esta guía

- **Implementador nuevo:** empieza por el día 1 y sigue el cronograma palabra por palabra.
- **Operador en producción:** salta al apéndice C (checklist) y apéndice D (referencias).
- **Auditor de seguridad:** lee el resumen ejecutivo, verifica contra apéndice B (tabla maestra) y corre los tests del día 9.
- **Investigador de incidente:** ve directo al cap-05 §5.9 (runbook).

---

## Cobertura por capa de defensa

| Capa | Mitigaciones | Capítulo |
|---|---|---|
| Perímetro (Internet) | Cloudflare WAF, DDoS L7, Bot Management | cap-03 |
| Red troncal (Cloudflare ↔ Origin) | mTLS Authenticated Origin Pulls, rangos CF en nftables | cap-02 + cap-03 |
| Borde del droplet | DO Cloud Firewall, UFW, nftables, Fail2Ban | cap-02 |
| Sistema operativo | sysctl endurecido, auditd, AppArmor, AIDE, ClamAV | cap-01 |
| Servicio (nginx) | ssl-params, security-headers, rate limit, ModSecurity | cap-03 |
| Aplicación | APP_DEBUG=false, Sanctum abilities, JSON:API shape | cap-05 |
| Datos (MySQL/PG/Redis) | bind 127.0.0.1, TLS obligatorio, menor privilegio, cifrado en reposo | cap-04 |
| Backups | gpg AES256, rotación 7/30/365, drill mensual | cap-04 |
| CI/CD | SHA pinning, OIDC, Trivy, sin secretos en YAML | cap-05 |
| Detección | Wazuh FIM, alertas Slack, Prometheus exporters | cap-05 |
| Respuesta | Runbook NIST SP 800-61, post-mortem blameless | cap-05 |

---

## Apéndices

- **Apéndice A** — Scripts completos (systemd units, snippets, hooks, fail2ban actions).
- **Apéndice B** — Tabla maestra de configuración (cada opción de cada archivo con su valor por defecto, valor endurecido, justificación, fuente oficial).
- **Apéndice C** — Lista de comprobación previa a producción (checklist ejecutable).
- **Apéndice D** — Mapa de referencias oficiales (URL + sección + fecha de acceso).
- **Apéndice E** — Glosario de términos (MFA, CSP, HSTS, OCSP, WAF, etc.).

Los archivos completos de los apéndices están en `/workspace/vps-security-plan/apendices/`.
