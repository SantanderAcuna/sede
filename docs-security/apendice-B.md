# Apéndice B — Tabla maestra de configuración

> Cada opción de cada archivo de configuración crítica. Valor por defecto → valor endurecido → justificación → fuente oficial.

---

## B.1 OpenSSH server (`/etc/ssh/sshd_config`)

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

## B.2 sysctl (`/etc/sysctl.d/99-hardening.conf`)

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

## B.3 nginx (`/etc/nginx/conf.d/ssl-params.conf`)

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

## B.4 nginx security-headers

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

## B.5 MySQL (`/etc/mysql/mysql.conf.d/hardening.cnf`)

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

## B.6 Redis 7 (`/etc/redis/redis.conf`)

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

## B.7 Laravel 13 (`.env` production)

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

## B.8 GitHub Actions (workflow YAML)

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

## B.9 systemd hardening (cualquier unit)

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
