# Apéndice D — Mapa de referencias oficiales

> Cada fuente usada en los capítulos, con URL exacta y fecha de acceso. Las fechas siguen el calendario de producción de la guía (septiembre 2026). Si una fuente cambia, documenta la nueva URL y la fecha del cambio.

---

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
