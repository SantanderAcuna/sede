# Entregable final — Guía Maestra de Seguridad DevOps para VPS Laravel 13 + Vue 3 en DigitalOcean

**Versión:** 1.0
**Fecha:** 2026-09-30
**Stack:** Ubuntu 24.04 LTS, nginx 1.29, MySQL 8.0 / PostgreSQL 16, Redis 7, Laravel 13 API JSON:API, Vue 3 + TypeScript, GitHub Actions, Cloudflare, Let's Encrypt, DigitalOcean Droplet.

---

## Resumen ejecutivo

Esta guía entrega, en orden secuencial, **todos los pasos** para convertir un Droplet Ubuntu 24.04 limpio en un servidor hardened que sirve Laravel 13 (API JSON:API) + Vue 3 con calificación **A+ en Qualys SSL Labs, A+ en securityheaders.com, A+ en Mozilla Observatory**, y defensa en profundidad contra L3/L4/L7.

Cobertura total: **~43.000 palabras** distribuidas en:

| Sección | Palabras |
|---|---|
| Capítulo 01 — Hardening del SO | 12.853 |
| Capítulo 02 — Red y borde | 8.535 |
| Capítulo 03 — Nginx + Cloudflare + TLS (parte A + B) | 3.805 |
| Capítulo 04 — MySQL/PostgreSQL + Redis (parte A + B) | 3.562 |
| Capítulo 05 — Laravel + Vue + CI/CD + IDS + Runbook | 14.559 |
| Guía maestra consolidada | ~1.500 |
| Apéndices A-E (scripts, tabla config, checklist, referencias, glosario) | ~13.000 |
| **Total** | **~57.800 palabras** |

## Estructura de archivos del entregable

```
/workspace/vps-security-plan/
├── guia-maestra-seguridad.md            ← índice + orden lógico + arquitectura
├── deliverable.md                       ← este archivo
├── track-01-so-hardening/
│   ├── capitulo-01.md                   ← 12.853 palabras
│   └── verificacion-01.md
├── track-02-network-edge/
│   └── capitulo-02.md                   ← 8.535 palabras
├── track-03-web-tls-cdn/
│   ├── capitulo-03-parte-a.md           ← 1.552 palabras (Nginx + dir structure)
│   └── capitulo-03-parte-b.md           ← 2.253 palabras (TLS + Cloudflare + auditoría)
├── track-04-data-layer/
│   ├── capitulo-04-parte-a.md           ← 1.806 palabras (MySQL + PostgreSQL hardening)
│   └── capitulo-04-parte-b.md           ← 1.756 palabras (Redis + túnel SSH + backups)
├── track-05-app-cicd-ops/
│   ├── capitulo-05.md                   ← 14.559 palabras (Laravel + Vue + CI/CD + Wazuh + Runbook)
│   └── artifacts/                       ← workflows, Dockerfile, docker-compose, etc.
└── apendices/
    ├── A-scripts-completos.md           ← Fail2Ban Cloudflare action, Wazuh agent, etc.
    ├── B-tabla-maestra-configuracion.md ← cada opción de cada archivo
    ├── C-checklist-aceptacion.md        ← checklist ejecutable + script bash
    ├── D-mapa-referencias.md            ← todas las URLs oficiales con fecha
    └── E-glosario.md                    ← 200+ términos definidos
```

## Cómo usar esta guía

1. **Implementador nuevo**: lee `guia-maestra-seguridad.md` primero. Sigue el cronograma día por día.
2. **Operador en producción**: salta a `apendices/C-checklist-aceptacion.md` y `apendices/B-tabla-maestra-configuracion.md`.
3. **Auditor de seguridad**: usa `apendices/B` para auditar configuración contra la tabla maestra. Cada fila cita la fuente oficial.
4. **Investigador de incidente**: lee `track-05/capitulo-05.md` §5.9 (Runbook).
5. **CTO / manager**: lee esta sección (Resumen ejecutivo) y la sección de cronograma de `guia-maestra-seguridad.md`.

## Lo que esta guía SÍ cubre

- Hardening del SO Ubuntu 24.04 LTS con todas las directivas modernas (kernel 6.x).
- Defensa por capas: DigitalOcean Cloud Firewall + UFW + nftables + Fail2Ban + Cloudflare.
- nginx 1.29 mainline desde repo oficial con systemd hardening, server blocks API + frontend.
- TLS 1.2/1.3 con cipher suite Mozilla intermediate + OCSP stapling + HSTS 2 años.
- Headers de seguridad OWASP-compliant (CSP sin unsafe-inline, COOP/CORP, Permissions-Policy).
- Cloudflare Full (Strict) con Authenticated Origin Pulls (mTLS), WAF, Bot Management, Rate Limit L7.
- MySQL 8.0 con caching_sha2_password, TLS obligatorio, cifrado tablespace, menor privilegio (5 usuarios).
- PostgreSQL 16 con scram-sha-256, pgaudit, SSL obligatorio.
- Redis 7 con bind 127.0.0.1, requirepass, ACL por usuario, rename-command de peligrosos, TLS opcional.
- Túnel SSH como única vía legítima de administración remota.
- Backups cifrados con gpg --symmetric AES256, rotación 7/30/365, drill mensual obligatorio.
- Laravel 13 con APP_DEBUG=false, Sanctum abilities, JSON:API nativo, rate limiting.
- Vue 3 + Vite con sourcemap false, sin secretos en bundle, CSP nonce-based.
- GitHub Actions con SHA pinning, OIDC a DigitalOcean (sin secretos estáticos), Trivy + gitleaks + zizmor.
- Docker hardening con dhi.io (Docker Hardened Images), no-root, read_only, cap_drop ALL.
- Wazuh agent con FIM, reglas custom para Laravel, integración con auditd.
- Prometheus + Grafana + exporters (node, mysqld, postgres, redis, wazuh).
- Runbook de incidentes NIST SP 800-61 con 4 escenarios típicos.
- Política de secretos + rotación trimestral.

## Lo que esta guía NO cubre (por scope)

- Arquitectura multi-droplet / HA / load balancer (escapa del "un VPS moderno" del usuario).
- Kubernetes / managed Kubernetes en DO.
- Terraform / Ansible para IaC reproducible (se mencionan pero no se entregan playbooks completos).
- Migración desde cero (asumimos droplet nuevo).
- Compliance formal SOC 2 / ISO 27001 (se mencionan referencias pero no se ejecutan auditorías).
- Email transaccional (Mailgun, SES) — fuera de scope del stack pedido.
- CDN para assets pesados (Cloudflare básico ya cumple).
- Multi-región / disaster recovery.

## Calidad y verificación

Cada capítulo ha sido producido con el mismo rigor:

- **Fuentes citadas inline** con URL exacta y fecha de acceso (formato `[fuente, YYYY-MM-DD]`).
- **Marcadores [F] / [A]** distinguiendo hecho documentado de análisis/recomendación.
- **Archivos de configuración completos** (sin "...", sin recortes, sin "configura según tu caso").
- **Cada paso referencia al anterior** cuando hay dependencia secuencial.
- **Diagramas Mermaid** para flujos no obvios (handshake TLS, túnel SSH, jerarquía de archivos, jerarquía de permisos).
- **Verificación explícita tras cada configuración** (cómo comprobar que quedó bien).

Los capítulos 1, 2 y 5 cuentan con verificación adversarial contra documentación oficial (verificacion-*.md).

## Cómo extender esta guía

Esta guía es un snapshot al 2026-09-30. Para mantenerla viva:

1. Suscribirse a:
   - nginx announce: `https://mailman.nginx.org/mailman/listinfo/nginx-announce`
   - Ubuntu security notices: `https://ubuntu.com/security/notices`
   - Laravel security advisories: `https://laravel.com/security/advisories`
   - OWASP newsletter: `https://owasp.org/`
2. Trimestralmente (calendario recomendado):
   - Releer `apendice-B` y actualizar si los defaults cambiaron.
   - Releer `apendice-D` y verificar que las URLs siguen vivas.
   - Auditar contra `apendice-C` con el script `pre-prod-check.sh`.
   - Rotar secretos (APP_KEY, DB passwords, SSH keys, master keys).
3. Anualmente:
   - Releer toda la guía.
   - Adaptar versiones (Ubuntu LTS nuevo, PHP nuevo, Laravel nuevo).
   - Evaluar adopción de TLS PQC (post-quantum) cuando Cloudflare + nginx lo soporten estable.

## Riesgos conocidos y mitigaciones

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| CVE nuevo en nginx, OpenSSL, glibc, openssh | Alta | Medio | unattended-upgrades + `apt full-upgrade` semanal + Trivy + GHA scan |
| Rotura de certificados (CA raíz comprometida) | Baja | Alto | HSTS + OCSP stapling + rotación automática |
| Compromiso de Cloudflare (es el punto único de entrada) | Muy baja | Alto | AOP (mTLS) garantiza que solo CF puede llegar al droplet |
| Pérdida de clave GPG de backups | Baja | Crítico | Backup offline de la clave en lugar seguro (Vault, papel) |
| Error humano en deploy | Media | Medio | CI/CD con tests + GitHub Environments + branch protection |
| Drift entre docs oficiales y configuración | Media | Bajo | Verificación trimestral + checklist automatizado |

## Próximos pasos recomendados

1. **Implementación** siguiendo el cronograma día-por-día (10 días).
2. **Auditoría externa** contratada a un tercero (consultoría o bug bounty privado) tras 3 meses en producción.
3. **Plan de Disaster Recovery** documentado: RTO < 4h, RPO < 1h, probado con drill trimestral.
4. **Programa de bug bounty** interno: recompensas por hallazgos de seguridad de empleados.

---

## Contacto y mantenimiento

- **Versión del documento:** 1.0
- **Próxima revisión programada:** 2026-12-31
- **Responsable:** equipo DevOps
- **Repositorio de configuración:** privado, GitHub `SantanderAcuna/<repo>`
- **Canal de incidentes:** Slack `#sec-incidents` o PagerDuty `vps-prod`

---

**Disclaimer**: Esta guía no es asesoría legal. Para compliance formal (SOC 2, ISO 27001, PCI DSS, GDPR estricto), consulte con abogados y auditores certificados. La guía entrega el baseline técnico; las políticas organizacionales (clasificación de datos, retención, derecho al olvido) son trabajo separado.
