# 01 — Levantamiento de Requisitos de Infraestructura

**Proyecto:** Sede Electrónica del Distrito de Santa Marta (Alcaldía Distrital, NIT 891.780.009-4)
**Fecha de cierre:** 2026-10-05
**Estado:** Pendiente (en revisión por el usuario)
**Aprobado por:** [usuario]

> **Fuentes de este levantamiento:** corpus de elicitación `sede-electronica-doc/` · implementación ya existente (`compose.yaml`, `deploy/`, `.github/workflows/`, `docker/`, `backend/`, `sitio/`, `panel/`) · `plan.md` v1.0 (2026-09-30, *propuesto, pendiente de ratificación*). Respuesta directa del usuario del 2026-10-05.

---

## 1. Resumen ejecutivo

La **Sede Electrónica del Distrito de Santa Marta** es un portal público (Nuxt 4 SSR) + panel de administración (Vue 3) + API (Laravel 13.34 / PHP 8.5) integrado al Portal Único GOV.CO por redireccionamiento con enmascaramiento de URL, con trámites (124 en SUIT), PQRSD, transparencia, participación, datos abiertos e interoperabilidad X-Road/PDI (SCD). Maneja datos personales (identificación, contacto, biometría en niveles alto/muy alto, datos sensibles y de menores), por lo que aplican Ley 1581/2012, Resolución 1519/2020 (MSPI), WCAG 2.1 AA y el modelo de seguridad y privacidad de MinTIC.

**La infraestructura no es greenfield.** Ya existe una pila Docker Compose completa sobre un droplet DigitalOcean (`165.22.46.11`, región `nyc3`), con Nginx 1.30.5 como reverse proxy/TLS, PostgreSQL 18.6 y Redis 8.10 autoalojados en contenedores, CI/CD en GitHub Actions (build → escaneo Trivy → SSH → `desplegar.sh` con rollback automático), hardening de host (UFW, fail2ban, auditd, AIDE, ClamAV, unattended-upgrades) y respaldos cifrados (7/30/365) con simulacro mensual. El `plan.md` deja **fuera de alcance** Wazuh/IDS, Prometheus/Grafana, app móvil Flutter y sidecar de IA.

**Hallazgo crítico de carga.** El usuario fija **500.000 usuarios diarios** (R-01) Y CONFIRMA que hay miles de usuarios concurrentes haciendo transacciones simultáneas. Convertido a carga (ventana 16 h, sesión 5 min, factor pico 3×, 6 requests dinámicos/sesión) resulta en **~2.600 sesiones concurrentes promedio y ~7.800 en pico, con ~52 RPS dinámicos promedio y ~156–260 RPS en pico**. Un solo nodo de 4 vCPU/8 GB con 4 workers PHP-FPM rinde ~67 req/s teóricos.

**Conclusión: escala horizontal obligatoria desde la Fase 0. No hay opción de un solo droplet.** La Fase 0 aprovisiona la infraestructura mínima con ≥2 nodos de aplicación + balanceador de carga + BD replicada.

**Conflicto del túnel SSH — resuelto (D-05).** El usuario exige acceso DB por túnel SSH local para ahorrar costo. Conflicto triple resuelto: `AllowTcpForwarding no` se mantiene en el droplet de app; se crea un **droplet bastión separado** (~$6/mes) con `AllowTcpForwarding yes` solo ahí. El túnel va de local → bastión → app.

**Decisiones clave de este ciclo:** Presupuesto $200/mes + DigitalOcean (GCP descartado). **DOKS** (Kubernetes gestionado) con 3 nodos s-4vcpu-8gb desde la Fase 0. Managed PostgreSQL Production con Standby. Túnel SSH vía bastión. **Escala horizontal obligatoria** (miles de transacciones concurrentes confirmadas por el usuario). Transparencia es Módulo 02, no proyecto independiente.

**Versiones confirmadas** (plan.md §4 verificadas 2026-09-30): Laravel **13.34.0**, PHP **8.5**, PostgreSQL **18.6**, Redis **8.10**, nginx **1.30.5** (corrige CVE-2026-90439), Node **24 LTS**, Nuxt **4.5.2**, Vue **3.5.43**, TypeScript **7.0.2**, Docker Engine **29.8.1** / Compose **5.5.1**.

---

## 2. Tabla de requisitos

Leyenda de **Estado**: `Aprobado` = confirmado por el usuario o decidido por el titular · `Propuesto` = propuesto en `plan.md` §21, pendiente de ratificación · `Pendiente` = dato ausente o por confirmar.

| # | Categoría | Requisito | Valor | Origen | Estado |
|---|---|---|---|---|---|
| R-01 | Carga | **Usuarios diarios** | **500.000 sesiones/visitas únicas** interactuando (leer, descargar, loguearse, iniciar trámites) | usuario (2026-10-05) | Aprobado |
| R-02 | Carga | Usuarios concurrentes (umbral normativo) | ≥ 5.000 | RNF-B3-016/017 | Aprobado |
| R-03 | Carga | Transacciones concurrentes (umbral normativo) | 100.000 | RNF-B3-016/017 | Aprobado |
| R-04 | Carga | RPS dinámico pico (derivado de R-01) | ~156–260 | cálculo (§5) | Pendiente confirmación |
| R-05 | Carga | Factor pico/medio y estacionalidad | 3× (impuestos/catastro/tránsito) | supuesto §4 | Pendiente |
| R-06 | Disponibilidad | Trámites digitalizados | ≥ 98 % mensual | RNF-B1-001 | Aprobado |
| R-07 | Disponibilidad | Sede informativa | ≥ 95 % (C-02) | corpus | Propuesto |
| R-08 | Disponibilidad | Objetivo global (plan) | ≥ 99 % mensual | plan §21.E | Propuesto |
| R-09 | Continuidad | RTO / RPO | ≤ 4 h / ≤ 24 h | plan §16.7, §21.E | Propuesto |
| R-10 | Continuidad | RTO/RPO SCD (referencia) | ≤ 8 min / ≤ 30 min | RNF-B1-004/005 | Aprobado |
| R-11 | Rendimiento | Carga inicial / páginas internas / FCP | ≤ 3 s / ≤ 1,5 s / ≤ 1 s | RNF-B1-008 | Aprobado |
| R-12 | Rendimiento | Autenticación / componentes SCD / sede↔Articulador | < 1 s / < 5 s / < 3 s | RNF-B1-009/010/011 | Aprobado |
| R-13 | Infraestructura | Proveedor cloud + borde | DigitalOcean + Cloudflare | plan D-xx, corpus | Aprobado |
| R-14 | Infraestructura | Región | `nyc3` (Nueva York 3) | plan §13.1 | Aprobado |
| R-15 | Infraestructura | Droplet actual | `165.22.46.11`, **4 vCPU / 7,8 GB / 154 GB** (Básico s-4vcpu-8gb) | plan §13.1 + SA-4 | Confirmado |
| R-16 | Infraestructura | Orquestación | **DOKS (Kubernetes gestionado por DigitalOcean)** con 3 nodos s-4vcpu-8gb, HPA + Cluster Autoscaler (min 3, max 6), 3 réplicas de cada servicio web, 2–3 workers. Costo: ~$156/mes (nodos + LB) | plan D-13 + usuario (2026-10-05) | Aprobado (D-06) |
| R-17 | Infraestructura | Reverse proxy / TLS | nginx **1.30.5** stable + certificado de origen; TLS en el borde Cloudflare Full Strict | plan D-12, §13.3 | Aprobado |
| R-18 | Infraestructura | SO | Ubuntu **24.04.5** LTS (kernel 6.8.0-142); **26.04 contradicho en §14.0** (ver C-01) | plan §13.1 | Confirmado (24.04.5) |
| R-19 | BD | Motor | PostgreSQL **18.6** | plan D-11, compose.yaml | Aprobado |
| R-20 | BD | Modelo | **DigitalOcean Managed PostgreSQL Production** con Standby (2 vCPU / 4 GB RAM, 38 GB SSD) — self-hosted CONTAINER ya no aplica en DOKS | P-24 | ✅ Resuelto |
| R-21 | BD | Gestión administrativa | **RESUELTO (D-05)**: túnel SSH vía **bastión dedicado** ($6/mes) para administración local. `AllowTcpForwarding no` en nodos DOKS; se habilita solo en droplet bastión | usuario 2026-10-05 | ✅ Resuelto |
| R-22 | BD | Gestionada DigitalOcean vs autoalojada | **RESUELTO (D-04 + P-24)**: **Managed PostgreSQL Production con Standby** (HA real, desde día 1) | usuario 2026-10-05 + P-24 | ✅ Resuelto |
| R-23 | BD | Alta disponibilidad (réplica) | **RESUELTO (P-24)**: Managed PostgreSQL con Standby incluido | P-24 | ✅ Resuelto |
| R-24 | Cache/colas | Redis | **8.10** (plan §21.B); docs-security书上 7 (desactualizado) | plan §21.B | Propuesto |
| R-25 | Colas | Laravel Horizon | 5.50 (supervisor en contenedor) | composer.json | Aprobado |
| R-26 | Almacenamiento | Objetos S3 | **SeaweedFS self-hosted** como StatefulSet en DOKS con PersistentVolumeClaims (no DO Spaces). API S3 para todos los archivos de usuario (adjuntos, documentos). Ningún archivo se guarda en volumen local de un pod | M00f | Aprobado (resuelto en DOKS) |
| R-27 | CI/CD | Pipelines | `ci.yml` + `despliegue.yml` (SSH→Droplet, rollback); `entrega.yml` / `vigilancia.yml` **NO existen aún** | .github/workflows, plan §17 | Parcial (2 de 4) |
| R-28 | CI/CD | Registro + escaneo | ghcr.io + Trivy (bloqueante HIGH/CRITICAL) + Dependabot | despliegue.yml | Aprobado |
| R-29 | Seguridad | Marco | MSPI + ISO 27000 / NIST 800-53 | RNF-B2-012 | Aprobado |
| R-30 | Seguridad | Cifrado | TLS 1.2+ (Full Strict) · AES-256 reposo · RSA ≥ 2048 | plan §13.3, RNF | Aprobado |
| R-31 | Seguridad | Logs de auditoría | retención ≥ 5 años, append-only/hash-chain, Hora Legal + TSA | RNF-09-D01, RN-09-D05 | Aprobado |
| R-32 | Seguridad | Acceso | RBAC + MFA (roles CMS) + SoD + rate limiting + captcha | RN-09-D04, módulo 09 | Aprobado |
| R-33 | Respaldos | Estrategia | Diario (volcado PG cifrado AES-256 + docs incremental), 7/30/365, object lock, simulacro mensual | plan §16 | Aprobado |
| R-34 | Observabilidad | Alcance real | **Journald + logs nginx/contenedores + alertas DigitalOcean + check externo + informe disponibilidad**; Prometheus/Grafana/Loki/Jaeger/Sentry **FUERA de alcance** (D-15) — entidad-transparencia los propone pero no aplican a la sede | plan D-15, §13.4 | Aprobado |
| R-35 | CI/CD | Puertas en PR | **NO**: auditoria-sede.md §21.3 documenta que las puertas existen pero **nadie las ejecuta en cada propuesta de cambio** | auditoria-sede.md:2445-2448 | Pendiente |
| R-36 | Arquitectura | Conflicto entidad-transparencia vs plan.md | `entidad-transparencia/` propone ADR-015: GCP + GKE + Cloud SQL HA + Prometheus/Grafana/Loki/Jaeger/Sentry (~USD 928–1200/mes). **RESUELTO (D-03, D-06, D-07)**: sede usa **DigitalOcean + DOKS + Managed PostgreSQL**; transparencia es Módulo 02 | usuario 2026-10-05 | ✅ Resuelto |
| R-37 | Interoperabilidad | Servidor X-Road/PDI | 3 ambientes (QA 1C/4G · Preprod 2C/6G · Prod 4C/16G, HA) | módulo 10, RF-B1-028 | Aprobado |

---

## 3. Decisiones tomadas y pendientes abiertos

### 3.1 Decisiones de este ciclo (respuestas del usuario 2026-10-05)

| # | Decisión | Valor | Fuente |
|---|---|---|---|
| D-01 | 500k usuarios = sesiones/visitas únicas interactuando | Usuario 2026-10-05 | ✅ |
| D-02 | Presupuesto máximo | **200 USD/mes** | usuario |
| D-03 | Proveedor cloud | **DigitalOcean** (GCP descartado) | usuario |
| D-04 | BD | **Self-hosted en contenedor** (no gestionada por DO) | usuario |
| D-05 | Acceso DB desde local | **Túnel SSH** vía **bastión dedicado** ($6/mes). `AllowTcpForwarding no` se mantiene en app; se habilita solo en droplet bastión | usuario |
| D-06 | Orquestación | **DOKS (Kubernetes gestionado por DigitalOcean)** desde la Fase 0. 3 nodos s-4vcpu-8gb, HPA + Cluster Autoscaler (min 3, max 6), 3 réplicas de cada servicio web, 2–3 workers. Costo: ~$144/mes (nodos) + LB (~$12) | usuario (2026-10-05) |
| D-07 | Transparencia | Es **Módulo 02** de los 12 módulos. NO es proyecto independiente; ADR-015 de entidad-transparencia NO aplica a la sede | usuario |

### 3.2 Pendientes abiertos

| # | Pendiente | Impacto | Prioridad |
|---|---|---|---|
| P-02 | **RPS pico real** y factor pico/medio (confirmar 3–5×) | Define si 1 droplet basta o se necesitan ≥2 nodos | Alta |
| P-06 | **Balanceador**: Nginx propio (hoy) vs Load Balancer gestionado de DigitalOcean | Topología de entrada | Alta |
| P-07 | **Dominio de producción `.gov.co`** y plan de Cloudflare | TLS/WAF/enmascaramiento GOV.CO | Alta |
| P-08 | **Disponibilidad/RTO/RPO finales**: plan (≥99%/RTO≤4h/RPO≤24h) vs expediente (≥98%/RTO≤8min/RPO≤30min) | Estrategia HA/DR y costo | Alta |
| P-09 | **Equipo**: tamaño y nivel para operar Kubernetes | Decide cuándo migrar a DOKS | Media |
| P-10 | **Gestión de secretos**: GitHub Secrets + `.env` vs SOPS/Vault | Fase 8 | Media |
| P-11 | **HA de BD** (réplica) y **DR** (sitio alterno) | Fase 10 | Media |
| P-12 | **Ventanas de mantenimiento** permitidas | Estrategia de despliegue sin caída | Baja |
| P-13 | **Proveedor de correo** transaccional (hoy `mock`) | plan §21.G | Baja |
| P-14 | **TLS de origen**: Cloudflare Full Strict sola vs Certbot también en origen | Configuración TLS | Media |
| P-15 | **CI/CD en PRs**: puertas existen pero **no se ejecutan en cada PR** | Calidad y seguridad del desarrollo | Alta |
| P-16 | **Entornos protegidos**: GitHub Environments necesitan plan Pro o Team | plan §21.K | Media |
| P-17 | **`localStorage`** para preferencia accesibilidad vs prohibición OWASP para tokens | Seguridad frontend | Media |
| P-18 | **HPKP**: PDF oficial lo exige; expediente lo prohíbe (deprecada) | Seguridad TLS | Baja |
| P-19 | **Staging indexable**: robots.txt permite todo, sin `noindex` por entorno | SEO/seguridad | Media |
| P-20 | **Wazuh** en docs-security vs D-15 lo excluye del alcance | Fase 8 | Baja |
| P-22 | **Stack de observabilidad**: Prometheus/Grafana/Loki/Jaeger/Sentry vs journald + DO alerts | Fase 9 | Baja |
| P-23 | ~~Arquitectura de escala horizontal~~ | **RESUELTO (D-06)**: 3 nodos s-4vcpu-8gb en DOKS, HPA + Cluster Autoscaler (min 3, max 6), 3 réplicas de cada servicio web, 2–3 workers. Costo: ~$144/mes + LB | ✅ Resuelto |
| P-24 | ~~Replicación PostgreSQL~~ | **RESUELTO**: DigitalOcean Managed PostgreSQL Production con Standby (2 vCPU / 4 GB RAM, 38 GB SSD). ~$45–60/mes. HA real desde el día 1 | ✅ Resuelto |
| P-25 | ~~Presupuesto vs HA completa~~ | **RESUELTO**: No se acepta SPOF. Arrancar directamente con Managed DB + Standby desde el día 1. Migrar a Managed en cuanto haya presupuesto disponible | ✅ Resuelto |

### 3.3 Orden de construcción de módulos (basado en corpus `sede-electronica-doc/`)

> Los módulos **07 (Accesibilidad), 08 (Usabilidad) y 09 (Seguridad)** son **transversales**: se **verifican en cada módulo**, no como fases independientes. El plan.md Fase 10 valida la conformidad transversal.

#### FASE 0 — Cimientos (DOKS + Managed DB)
| Módulo | Nombre | Prioridad | Dependencias |
|---|---|---|---|
| M00a | Cluster DOKS (3 nodos s-4vcpu-8gb) con HPA y Cluster Autoscaler (min 3, max 6) | Must | ninguna |
| M00b | Managed PostgreSQL Production con Standby (2 vCPU / 4 GB RAM, 38 GB SSD) | Must | ninguna |
| M00c | Load Balancer de DigitalOcean (entry point del cluster) | Must | M00a |
| M00d | Droplet bastión SSH ($6/mes) | Must | ninguna |
| M00e | CI/CD actualizado para DOKS (Helm charts o kustomize, no docker-compose) | Must | M00a–c |
| M00f | SeaweedFS como storage compartido (API S3, para archivos de usuario en volúmenes persistentes) | Must | M00a |

#### FASE 1 — Identidad y envolvente
| Módulo | Nombre | Prioridad | Dependencias | Contenido clave |
|---|---|---|---|---|
| **M01** | Estructura e Identidad GOV.CO | Must | ninguna | Top bar, footer, menús, buscador, Kit UI v9.2, **integración/redirección GOV.CO** (Art. 14 DL 2106), cookies, políticas, 404, mapa del sitio |

#### FASE 2 — Información pública
| Módulo | Nombre | Prioridad | Dependencias | Contenido clave |
|---|---|---|---|---|
| **M02** | Transparencia y Acceso a la Información | Must | M01 | 10 subsecciones Ley 1712, directorio SIGEP, SECOP, planeación, trámites linked, datos abiertos linked, grupos de interés, obligación de reporte, tributaria |
| **M11** | Datos Abiertos | Must | M02 (subsección) | Federación datos.gov.co, CSV/XML/RDF/JSON/ODF, metadatos, licencia abierta, registro activos |

#### FASE 3 — Núcleo transaccional
| Módulo | Nombre | Prioridad | Dependencias | Contenido clave |
|---|---|---|---|---|
| **M03** | Servicios y Trámites | Must | M01 + M12 | Catálogo 124 trámites SUIT, flujo 4 etapas, autenticación, pagos PSE, carpeta ciudadana, expediente electrónico |
| **M12** | Gestión de Contenidos y Administración | Must | M01 | CMS, roles/permisos (RBAC+MFA), auditoría, TRD/AGN, registro 24/7, SGDEA, tablero ITA, notificaciones multicanal, firma electrónica |

#### FASE 4 — Canal ciudadano y atención
| Módulo | Nombre | Prioridad | Dependencias | Contenido clave |
|---|---|---|---|---|
| **M04** | PQRSD | Must | M01 + M09 + M12 | Formulario, anonimato, radicado, seguimiento, plazos, SGDEA, captcha |
| **M06** | Canales de Atención | Should | M01 + M12 | Canales, agendamiento citas con control de concurrencia |

#### FASE 5 — Participación
| Módulo | Nombre | Prioridad | Dependencias | Contenido clave |
|---|---|---|---|---|
| **M05** | Participa | Should | M01 + M02 | 4 fases Ley 1757, SUCOP, micrositios grupos de interés, lenguaje claro |

#### FASE 6 — Interoperabilidad
| Módulo | Nombre | Prioridad | Dependencias | Contenido clave |
|---|---|---|---|---|
| **M10** | Interoperabilidad X-Road/PDI | Must | M03 + M04 + M12 | Servidor X-Road (QA 1C/4GB ~$30/mes · Preprod 2C/6GB · Prod 4C/16G HA ~$120/mes), SCD, TSA (GSE RFC 3161), SUIT/SIGEP/SECOP/SGDEA |

#### TRANSVERSAL — Se valida en cada fase
| Módulo | Nombre | Prioridad | Dependencias | Contenido clave |
|---|---|---|---|---|
| **M07** | Accesibilidad (WCAG 2.1 AA) | Must | todas | 52 criterios, barra accesibilidad, multimedia, teclado. Obligatorio desde 01/01/2022 |
| **M08** | Usabilidad | Should | todas | UX, SUS ≥80, lenguaje claro, responsive, SEO |
| **M09** | Seguridad Digital | Must | todas | HTTPS, cabeceras, captcha, MSPI, incidentes, backups, MFA, RBAC |

#### Mapa de dependencias
```
M01 (identidad) ───────────────────────┐
    ├── M02 ──────────────────── M11 (datos abiertos)
    ├── M03 ────────────── M12 (gestion contenidos)
    │         │
    │         └── M10 (interoperabilidad) ←── M04 (PQRSD)
    ├── M04 ────────────── M12
    ├── M05 ────────────── M02
    └── M06 ────────────── M12
```

> **Nota presupuesto ($200/mes):** DOKS 3 nodos (~$144) + LB (~$12) + Managed PostgreSQL (~$45–60) + bastión ($6) = **~$207–222/mes**. Excede el presupuesto por $7–22. Solución: comenzar con DOKS 3 nodos + Managed PostgreSQL (~$201–210) Y diferir el bastión ($6/mes) o iniciar con Managed PostgreSQL Essentials ($25/mes, sin standby) e inmediatamente planificar la migración a Production. X-Road QA (~$30/mes) queda diferido a la Fase 6.

---

## 4. Supuestos explícitos

| # | Supuesto | Justificación | Autorizado |
|---|---|---|---|
| S-01 | "Usuario diario" ≈ sesión/visita única de 5 min | Base para el cálculo. **RESUELTO (D-01):** confirmado como sesiones/visitas únicas interactuando | ✅ Resuelto |
| S-02 | Ventana activa de 16 h/día (06:00–22:00) | Servicio público colombiano | No |
| S-03 | Factor pico/medio = 3× (hasta 5× en fechas límite) | Estacionalidad de impuestos/catastro/tránsito | No |
| S-04 | ~6 requests dinámicos/sesión; estáticos absorbidos por Cloudflare | SSR + API + caché de borde | No |

> S-02, S-03 y S-04 **no están autorizados**. Son hipótesis de cálculo; se reemplazan por datos reales antes de fijar capacidad.

---

## 5. Riesgos iniciales de infraestructura

| Riesgo | Probabilidad | Impacto | Mitigación preliminar |
|---|---|---|---|
| ~~Escala horizontal obligatoria~~ | ~~Cierto~~ | ~~Crítico~~ | **RESUELTA (D-06):** DOKS con 3 nodos s-4vcpu-8gb, HPA, 3 réplicas |
| ~~PostgreSQL como SPOF~~ | ~~Alta~~ | ~~Crítico~~ | **RESUELTO (P-24):** Managed PostgreSQL Production con Standby desde día 1 |
| **Presupuesto $200/mes ajustado**: DOKS + Managed PostgreSQL sale ~$201–222/mes, ligeramente por encima del límite | Alta | Alto | Iniciar con Managed PostgreSQL Essentials ($25/mes, sin standby) y planificar migración a Production cuando hayan usuarios; o diferir el bastión ($6/mes) e invertir en Managed DB completo |
| **Competencia Kubernetes en el equipo**: DOKS requiere conocimiento de K8s (deployments, services, ingress, volumes, secrets) | Media | Alto | Capacitación del equipo antes de Fase 0; considerar contratar soporte de DigitalOcean o un ingeniero DevOps con experiencia K8s |
| **Storage compartido en DOKS**: los volúmenes locales de un pod no son visibles en otro pod. Archivos de usuario (adjuntos, documentos) deben ir a SeaweedFS (API S3), no a volúmenes emptyDir/hostPath | Alta | Alto | Todos los archivos de usuario van a SeaweedFS self-hosted (ya en el stack); ningún archivo se guarda en volumen local de un pod |
| ~~Conflicto túnel SSH~~ | ~~Cierto~~ | ~~Alto~~ | **RESUELTO (D-05):** bastión dedicado con `AllowTcpForwarding yes` solo ahí |
| Nube extranjera (nyc3) con datos de ciudadanos | Media | Medio | Declaración de conformidad SIC (Art. 26 Ley 1581); evaluar residencia de datos |
| Inconsistencias documentales no corregidas (OS, tamaño, versiones) | Media | Medio | Verificar en el droplet y ratificar (§6) |
| DRP/BCP no probado end-to-end | Media | Alto | Simulacro mensual de restauración (ya planificado, plan §16.6) |
| SO X-Road obsoletos en las guías (Ubuntu 18.04/RHEL7) | Media | Medio | Confirmar versión vigente con la AND antes de la fase 6 |

---

## 6. Hallazgos y correcciones del repositorio

Correcciones detectadas al revisar toda la documentación. **No se aplican ediciones** sin confirmación del usuario (algunas requieren verificación en el droplet).

| # | Archivo / sección | Incorrección detectada | Corrección propuesta | Requiere verificación |
|---|---|---|---|---|
| C-01 | `plan.md` §13.1 vs §14.0 | OS contradictorio: §13.1 dice **Ubuntu 24.04.5** (kernel 6.8.0-142); §14.0 L1642 dice **Ubuntu 26.04** | Kernel 6.8.0 es de 24.04; **Corregir §14.0 a 24.04.5** | `lsb_release -a` / `uname -r` en droplet |
| C-02 | `plan.md` D-22 vs §13.1 | Tamaño contradictorio: D-22 = **2 vCPU/8 GB/160 GB**; §13.1 = **4 vCPU/7,8 GB/154 GB** | Droplet real es 4 vCPU/7,8 GB; **Corregir D-22** | `lscpu` / `free -h` / panel DO |
| C-03 | Encargo vs repo | "Nuxt 3" en el encargo; repo/plan usa **Nuxt 4** | Registrar Nuxt 4.5.2 | — |
| C-04 | `_bd/README.md` vs `compose.yaml` | "PostgreSQL 15+" en diseño BD; implementación usa **PostgreSQL 18** | Registrar PostgreSQL 18.6 | — |
| C-05 | corpus vs plan §21.E | Disponibilidad/RPO/RTO contradictorios (95/99%, RPO 1h/24h, RTO 8min/4h) | Ratificar valores finales (P-08) | — |
| C-06 | `preparar.sh` L525-526 vs docs-security | `AllowTcpForwarding no` global bloquea túnel; docs-security permite `Match User` con excepción | El patrón correcto ya estaba en docs-security; **RESUELTO (D-05)** con bastión dedicado | — |
| C-07 | `plan.md` §14.6 vs encargo vs docs-security | Triple conflicto sobre administración de BD (túnel vs docker exec) | **RESUELTO (D-05)**: túnel SSH vía bastión dedicado | — |
| C-08 | Encargo | "Laravel 13 requiere PHP 8.3+"; repo usa **PHP 8.5** | Registrar PHP 8.5 | — |
| C-09 | `plan.md` §17 vs `.github/workflows/` | §17 anuncia `entrega.yml` y `vigilancia.yml`; solo existen `ci.yml` y `despliegue.yml` | Crearlos (Fase 0 / Fase 12) | — |
| C-10 | `README.md` L65 vs `GUIA-MAESTRA` vs docs-security | **Ubuntu: 26.04** (README) vs **24.04** (GUIA-MAESTRA y docs-security) | Corregir README a **24.04.5** | — |
| C-11 | `GUIA-MAESTRA` §3.1 vs `composer.json` | **PHP: 8.4** (GUIA-MAESTRA) vs **8.5** (composer.json y repo real) | Actualizar GUIA-MAESTRA a **PHP 8.5** | — |
| C-12 | `GUIA-MAESTRA` §8.2 vs repo | **Redis: 7** (GUIA-MAESTRA) vs **8** (repo) | Actualizar GUIA-MAESTRA a **Redis 8.10** | — |
| C-13 | `GUIA-MAESTRA` §6.2 vs §8.3 | **HSTS contradictorio** dentro de la propia guía: §6.2 `max-age=63072000; preload` vs §8.3 `max-age=31536000` sin preload | Unificar a **max-age=63072000; includeSubDomains; preload** | — |
| C-14 | `GUIA-MAESTRA` §6.1 vs `README.md` | **TLS: Certbot** en GUIA-MAESTRA vs **Cloudflare Full (Strict)** en README. Implementación real: Cloudflare Strict con origen HTTP, sin Certbot | Actualizar GUIA-MAESTRA: **Certbot no aplica** (origen HTTP) | — |
| C-15 | `entidad-transparencia/` vs plan.md | ADR-015 propone **GCP/GKE** (~USD 928–1200/mes). **RESUELTO (D-03, D-07)**: sede usa DigitalOcean + Compose; transparencia es Módulo 02 | Cerrar el conflicto: ADR-015 aplica solo a transparencia como subproyecto | — |
| C-16 | `docs/Sección 6` §6.12.2 | **URL incorrecta del CDN del Kit UI**: `https://cdn.www.gov.co/layout/v5/all.css` devuelve **HTML**. URL real: `https://cdn.www.gov.co/layout-govco-v5/all.css` | **Ya resuelto** por ADR-0002 (vendorización local en `frontend/public/govco/`). Corregir Sección 6 del expediente | — |
| C-17 | `docs/Sección 1` §1.2.7 vs `docs/Sección 5` §5.6.1 | **SLA contradictorio**: §1 ≥**99%** vs §5 ≥**95%** | 95% = piso normativo; 99% = meta de diseño. Clarificar para SLA contractuales (P-08) | — |
| C-18 | `docs/despliegue-secretos.md` + plan.md §17 | **Pipeline de deploy NO existe**: `entrega.yml` construye/escanea/firma pero **no despliega**. `DEPLOY_*` secretos faltantes | Diseñar e implementar pipeline production (Fase 0) | — |
| C-19 | `docs/despliegue-secretos.md` L80-97 | **GitHub Environments sin protección**: no se pueden exigir 2 aprobaciones automáticas con plan no Pro/Team | Subir a GitHub Pro/Team, o ejercer aprobaciones fuera de GitHub | — |
| C-20 | ADR-013 vs `compose.yaml` | **SeaweedFS vs MinIO**: MinIO ya no es descargable; repo usa **SeaweedFS** (`docker/s3/Dockerfile`) | Actualizar documentación a **SeaweedFS** | — |
| C-21 | `docs/despliegue-secretos.md` L49-57 | **Reverb no disponible**: Laravel 13 fija `guzzlehttp/psr7 ^3.1.0`; Reverb exige `^2.6` → conflicto | Usar **SSE** para notificaciones en vivo (ya en ADR-0010 de docs/) | — |
| C-22 | `docs/despliegue-secretos.md` + `compose.yaml` | **S3 self-hosted**: storage es **SeaweedFS** en contenedor, NO MinIO ni DigitalOcean Spaces | Actualizar docs: S3 = **SeaweedFS self-hosted** | — |

---

## 7. Firma

- Usuario: ______________________ Fecha: __________
- Arquitecto: ___________________ Fecha: __________
