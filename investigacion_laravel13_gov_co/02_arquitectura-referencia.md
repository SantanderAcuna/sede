# 02 — Arquitectura de Referencia y Proyectos de Infraestructura

**Proyecto:** Sede Electrónica del Distrito de Santa Marta (Alcaldía Distrital, NIT 891.780.009-4)
**Fase:** 2 — Proyectos y Arquitecturas de Referencia
**Fecha de cierre:** 2026-10-05
**Estado:** Pendiente (en revisión por el usuario)
**Aprobado por:** [usuario]

> **Documento precedente:** `01_requisitos.md` (Fase 1, aprobado por el usuario 2026-10-05). Este documento asume todas las decisiones de la Fase 1.

---

## 1. Decisiones arquitectónicas base (Fase 1)

| # | Decisión | Valor |
|---|---|---|
| D-01 | Carga | 500.000 sesiones/visitas únicas diarias interactuando |
| D-02 | Presupuesto | $200 USD/mes máximo |
| D-03 | Proveedor cloud | DigitalOcean (GCP descartado) |
| D-04 | BD | DigitalOcean Managed PostgreSQL Production con Standby |
| D-05 | Acceso DB desde local | Túnel SSH vía bastión dedicado ($6/mes) |
| D-06 | Orquestación | **DOKS (Kubernetes gestionado)** con 3 nodos s-4vcpu-8gb, HPA + Cluster Autoscaler |
| D-07 | Transparencia | Módulo 02 de los 12 módulos de la sede |

---

## 2. Arquitectura objetivo (target architecture)

```
                            ┌─────────────────────────────────────────┐
                            │         INTERNET / GOV.CO               │
                            │   (redirección con enmascaramiento)     │
                            └──────────────┬──────────────────────────┘
                                           │
                                           ▼
                              ┌────────────────────────┐
                              │   Cloudflare (WAF)     │
                              │   Full Strict TLS      │
                              └────────────┬───────────┘
                                           │443
                                           ▼
                              ┌────────────────────────┐
                              │  DigitalOcean Load     │  ← M00c
                              │  Balancer (entry)     │
                              └────────────┬───────────┘
                                           │
                    ┌──────────────────────┼──────────────────────┐
                    │                      │                      │
                    ▼                      ▼                      ▼
           ┌────────────┐         ┌────────────┐         ┌────────────┐
           │  DOKS Node │         │  DOKS Node │         │  DOKS Node │
           │  #1        │         │  #2        │         │  #3        │
           │ 4 vCPU/8GB│         │ 4 vCPU/8GB│         │ 4 vCPU/8GB│
           └─────┬──────┘         └─────┬──────┘         └─────┬──────┘
                 │                      │                      │
    ┌────────────┼──────────────────────┼──────────────────────┼────────────┐
    │            │                      │                      │            │
    ▼            ▼                      ▼                      ▼            ▼
┌───────┐  ┌───────┐           ┌───────┐              ┌───────┐  ┌───────┐
│ nginx │  │ PHP   │           │ PHP   │              │ PHP   │  │redis- │
│(ingress)│ │FPM/Horizon│     │FPM/Horizon│          │FPM/Horizon│ │session│
└───────┘  └───────┘           └───────┘              └───────┘  └───────┘
    │           │                   │                      │
    │           ▼                   ▼                      │
    │     ┌──────────┐        ┌──────────┐                │
    │     │ Nuxt SSR │        │ Nuxt SSR │  ← 3 réplicas │
    │     │ (sitio/) │        │ (sitio/) │                │
    │     └──────────┘        └──────────┘                │
    │           │                   │                      │
    │           ▼                   ▼                      │
    │     ┌──────────┐        ┌──────────┐                │
    │     │ Nuxt SPA │        │ Nuxt SPA │  ← 3 réplicas │
    │     │ (panel/) │        │ (panel/) │                │
    │     └──────────┘        └──────────┘                │
    │                                                   │
    ▼                                                   │
┌─────────────────────────────────────────────────────────┐
│              SeaweedFS StatefulSet                     │  ← M00f
│         (PersistentVolumeClaim, API S3)                │
│    Todos los archivos de usuario (adjuntos, docs)      │
└─────────────────────────────────────────────────────────┘

    ┌──────────────────────────────────────────────┐
    │      DigitalOcean Managed PostgreSQL           │  ← M00b
    │  Production 2 vCPU / 4 GB RAM / 38 GB SSD     │
    │            + Standby replica (HA)              │
    │          Connection string via private network  │
    └──────────────────────────────────────────────┘

    ┌─────────────┐          ┌──────────────────────────┐
    │  Bastión    │ ◄── SSH ─│  Admin local             │  ← M00d
    │  Droplet    │  túnel   │  (psql, pgAdmin, etc.)    │
    │  $6/mes     │          └──────────────────────────┘
    │  AllowTcp   │              ┌──────────────────────────┐
    │  Forwarding │              │  GitHub Actions          │
    │  yes (solo) │              │  CI/CD pipeline          │
    └─────────────┘              │  (build → trivy →       │
                                 │   helm deploy → DOKS)   │
                                 └──────────────────────────┘
```

---

## 3. Proyectos de infraestructura

Cada proyecto es un bloque de trabajo autónomo con su propia referencia arquitectónica, criterios de	done y costo estimado.

---

### PROYECTO-01: Migración Docker Compose → DOKS

**Objetivo:** Migrar la aplicación de un droplet único con Docker Compose a un cluster DOKS con 3 nodos, alta disponibilidad y auto-scaling.

#### 3.1.1 Arquitectura de referencia — DOKS

| Componente | Especificación |
|---|---|
| **Plataforma** | DigitalOcean Kubernetes (DOKS) — managed Kubernetes |
| **Versión K8s** | 1.30+ (más reciente estable disponible en DO) |
| **Node pool** | 3 nodos `s-4vcpu-8gb` (4 vCPU / 8 GB RAM / 80 GB SSD) |
| **Auto-scaling** | Cluster Autoscaler: min 3 / max 6 nodos |
| **HPA** | Horizontal Pod Autoscaler en todos los deployments (web, workers) |
| **Kubernetes networking** | Cilium o kube-proxy (default DO) con network policies |
| **Ingress** | NGINX Ingress Controller via Helm, con TLS terminates en Cloudflare (origen HTTP) |
| **DNS interno** | CoreDNS (gestionado por DOKS) |

#### Servicios desplegados en DOKS

| Deployment | Réplicas | Recursos (request/limit) | Notas |
|---|---|---|---|
| `sitio` (Nuxt SSR) | 3 | 500m / 1000m CPU, 512Mi / 1Gi RAM | SSR rendering, público |
| `panel` (Nuxt SPA) | 3 | 250m / 500m CPU, 256Mi / 512Mi RAM | Admin, autenticado |
| `backend` (Laravel / Octane) | 3 | 1000m / 2000m CPU, 1Gi / 2Gi RAM | API PHP |
| `worker` (Laravel Horizon) | 2 | 500m / 1000m CPU, 512Mi / 1Gi RAM | Colas, no expose puerto |
| `redis` | 2 | 250m / 500m CPU, 256Mi / 512Mi RAM | Cache + sessions |
| `seaweedfs` | 2 | 500m / 1000m CPU, 1Gi / 2Gi RAM | Storage S3-compatible |
| `nginx-ingress` | 2 | 200m / 400m CPU, 256Mi / 512Mi RAM | Ingress controller |

#### Helm charts requeridos

```
helm repo add bitnami https://charts.bitnami.com/nginx
helm repo add codecentric https://codecentric.github.io/helm-charts  # for keycloak
helm repo add ingress-nginx https://kubernetes.github.io/ingress-nginx
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo add grafana https://grafana.github.io/helm-charts
```

#### Migración de volúmenes

| Tipo de dato | Estrategia |
|---|---|
| Código fuente | Imagen Docker en ghcr.io, no volumen |
| Archivos de usuario (adjuntos, docs) | **SeaweedFS** con PVC RWX (ReadWriteMany) — NO volume hostPath ni emptyDir |
| Sessiones PHP / cache | Redis (ya en DOKS) |
| Uploads temporales | emptyDir (no persistente, OK para tmp) |

**Criterio crítico:** ningún archivo de usuario se guarda en un volumen local de un pod. Si un pod muere y se rearranca en otro nodo, el archivo debe seguir accesible. Esa es la razón de SeaweedFS con PVC.

#### Conexión a Managed PostgreSQL

```
# En el Secret de Kubernetes (no en configmap)
POSTGRES_HOST= приватный_ендпоинт_DO_ManagedDB.private
POSTGRES_PORT=5432
POSTGRES_DB=sede
POSTGRES_USER=sede_app
POSTGRES_PASSWORD=<Kubernetes Secret>
```

El endpoint privado de DigitalOcean (private network) evita que el tráfico de BD cruce internet.

#### Conexión al Bastión SSH

```
BASTION_HOST= <IP del droplet bastión>
BASTION_USER=deploy
SSH_KEY_PATH=/etc/secrets/ssh_key
LOCAL_PORT=5433  # tunnel: localhost:5433 → bastion → managed DB:5432
```

El túnel SSH se levanta manualmente desde el bastion hacia el Managed PostgreSQL (el bastion no puede recibir conexiones externas, solo se accede a él por VPN o SSH desde la red de la entidad).

#### Costo mensual

| Recurso | Especificación | Costo/mes |
|---|---|---|
| DOKS cluster fee | flat | $0 |
| 3 × s-4vcpu-8gb | nyc3 | $144 |
| DigitalOcean Load Balancer | entry point | $12 |
| Volume (block storage) | solo si se necesita | $0–10 |
| **Subtotal** | | **$156/mes** |

#### Criterios de	done

- [ ] Cluster DOKS creado con 3 nodos s-4vcpu-8gb
- [ ] `sitio`, `panel`, `backend`, `worker`, `redis`, `seaweedfs` desplegados con Helm
- [ ] 3 réplicas de cada servicio web verificadas (`kubectl get pods -o wide`)
- [ ] HPA configurado y verificado con `kubectl autoscale` o HPA manifest
- [ ] Cluster Autoscaler habilitado (min 3 / max 6)
- [ ] NGINX Ingress funcionando con TLS de Cloudflare (origen HTTP → Cloudflare Full Strict)
- [ ] Secretos de BD y Redis en Kubernetes Secrets (no en configmap)
- [ ] SeaweedFS responde en `http://seaweedfs.default.svc.cluster.local:8332`
- [ ] Despliegue via GitHub Actions (Helm upgrade) funciona en staging

---

### PROYECTO-02: DigitalOcean Managed PostgreSQL Production con Standby

**Objetivo:** Reemplazar PostgreSQL self-hosted en contenedor por una base de datos gestionada con alta disponibilidad.

#### 3.2.1 Arquitectura de referencia

```
┌─────────────────────────────────────────────────────┐
│         DigitalOcean Managed PostgreSQL               │
│                                                     │
│  ┌──────────────┐    Sincrónico    ┌──────────────┐│
│  │   Primary    │◄────────────────►│   Standby    ││
│  │  (escribe)   │   replication    │  (replica)   ││
│  └──────┬───────┘                  └──────────────┘│
│         │                                          │
│  ←─ Public endpoint (privado)                      │
│  ←─ Private network (para DOKS pods)               │
└─────────────────────────────────────────────────────┘
         ▲
         │ read replica (opcional, para queries de reporting)
```

#### Especificaciones

| Parámetro | Valor |
|---|---|
| **Plan** | Production (no Essentials — incluye standby) |
| **vCPU** | 2 vCPU |
| **RAM** | 4 GB |
| **Storage** | 38 GB SSD |
| **Región** | nyc3 (misma que DOKS) |
| **Alta disponibilidad** | Standby replica síncrona, failover automático |
| **Backups** | Automáticos (7 días retention en plan Production) + punto-in-time recovery |
| **Conexión** | Private network hacia DOKS (no tráfico por internet) |
| **Usuarios** | Mínimo 2: app (aplicación) + admin (para túnel SSH vía bastion) |
| **Contraseñas** | Generadas con `openssl rand -hex 32`, almacenadas en GitHub Secrets + 1Password |

#### Configuración de conexión (DOKS Secret)

```yaml
# kubernetes/secrets.yaml
apiVersion: v1
kind: Secret
metadata:
  name: db-credentials
  namespace: default
type: Opaque
stringData:
  POSTGRES_HOST: "privado.private.postgresql.database.azure.com"  # DO proporciona esto
  POSTGRES_PORT: "5432"
  POSTGRES_DB: "sede_production"
  POSTGRES_USER: "sede_app"
  POSTGRES_PASSWORD: "<generated>"
  POSTGRES_ADMIN_USER: "sede_admin"
  POSTGRES_ADMIN_PASSWORD: "<generated>"
```

#### Permitted networks

```
# Agregar el CIDR del DOKS cluster a permitted networks
# Para que los pods puedan conectarse sin exponer PostgreSQL a internet
DO Managed DB → Allowed Connections → DigitalOcean Kubernetes (automatic)
```

#### Costo mensual

| Recurso | Especificación | Costo/mes |
|---|---|---|
| Managed PostgreSQL Production | 2 vCPU / 4 GB RAM / 38 GB SSD + Standby | $45–60 |
| Point-in-time recovery | Incluido en Production | $0 |
| **Subtotal** | | **$45–60/mes** |

#### Criterios de	done

- [ ] Managed PostgreSQL Production creado en nyc3 con Standby
- [ ] Private network connectivity verificada desde DOKS pods
- [ ] Tablas de la aplicación creadas con migrations
- [ ] Usuario `sede_app` creado con privilegios mínimos (no superuser)
- [ ] Backups automáticos configurados (Retention ≥ 7 días)
- [ ] Failover automático probado (matar el primary, verificar que standby toma)
- [ ] Connection string almacenada en GitHub Secrets + 1Password del equipo
- [ ] Punto de conexión desde el túnel SSH verificado (bastion → Managed DB)

---

### PROYECTO-03: Túnel SSH vía Droplet Bastión

**Objetivo:** Permitir administración de la BD (psql, pg_dump, pg_restore) desde la red local sin exponer PostgreSQL a internet.

#### 3.3.1 Arquitectura de referencia

```
┌────────────────┐    SSH (puerto 22)    ┌────────────────┐
│  Estación de    │ ──────────────────────►  Droplet        │
│  trabajo del   │   (solo conexión SSH    Bastión         │
│  admin (local) │    desde IP whitelist) │  $6/mes        │
│                │                        │                │
│  psql -h localhost│                       │  AllowTcp     │
│  -p 5433       │      SSH tunnel         │  Forwarding   │
│                │ ◄──────────────────────│  yes          │
└────────────────┘                        └───────┬────────┘
                                                  │
                                          SSH jump
                                                  │
                                                  ▼
                                         ┌────────────────┐
                                         │  Managed        │
                                         │  PostgreSQL     │
                                         │  (sin acceso    │
                                         │   a internet)   │
                                         └────────────────┘
```

#### Especificaciones del droplet bastión

| Parámetro | Valor |
|---|---|
| **Droplet** | `s-1vcpu-1gb` (el más pequeño, solo para SSH) |
| **SO** | Ubuntu 24.04 LTS |
| **Región** | nyc3 (misma que DOKS y Managed DB) |
| **Firewall** | UFW: solo puerto 22, source = IP fija de la Alcaldía |
| **SSH** | Clave pública/privada, sin contraseña. `AllowTcpForwarding yes`. `PasswordAuthentication no` |
| **Usuarios** | `deploy` (para pipelines) + `admin_<nombre>` (uno por administrador) |
| **Auditoría** | `journalctl -u sshd` + `auditd` para registrar comandos |

#### Configuración SSH del bastión

```bash
# /etc/ssh/sshd_config (bastión)
AllowTcpForwarding yes
AllowStreamLocalForwarding no
PasswordAuthentication no
PermitRootLogin no
X11Forwarding no
PrintMotd yes

# /etc/ssh/sshd_config.d/bastion.conf
# Restringir por IP
Match Address 192.168.1.0/24,10.0.0.0/8
    AllowTcpForwarding yes
```

#### Script de túnel (para el admin)

```bash
#!/bin/bash
# conectar-bastion.sh
BASTION_HOST="<IP_DEL_BASTION>"
BASTION_USER="admin_tu_nombre"
LOCAL_PORT="5433"         # puerto local para el túnel
REMOTE_HOST="<DO_ManagedDB_PrivateHost>"
REMOTE_PORT="5432"

# Abrir túnel: localhost:5433 → bastion → managed_db:5432
ssh -L ${LOCAL_PORT}:${REMOTE_HOST}:${REMOTE_PORT} \
    -N -C ${BASTION_USER}@${BASTION_HOST} &
SSH_TUNNEL_PID=$!

echo "Túnel abierto en localhost:${LOCAL_PORT}"
echo "Presiona Ctrl+C para cerrar"

# Verificar conexión
sleep 2
psql -h localhost -p ${LOCAL_PORT} -U sede_admin -d sede_production

# Al salir, cerrar el túnel
kill $SSH_TUNNEL_PID 2>/dev/null
```

#### Permitted networks en Managed PostgreSQL

Agregar a allowed connections del Managed PostgreSQL:
- La IP pública del droplet bastión
- Opcionalmente: el CIDR interno de DigitalOcean para droplets (`10.XXX.0.0/16`)

> **Nota de seguridad:** la IP del bastión debe ser fija o estar en una red con IP fija. Si la Alcaldía usa IP dinámica, se requiere VPN o un rango de IPs blancas documentado.

#### Costo mensual

| Recurso | Especificación | Costo/mes |
|---|---|---|
| Droplet bastión | 1 vCPU / 1 GB RAM | $6 |
| **Subtotal** | | **$6/mes** |

#### Criterios de	done

- [ ] Droplet bastión creado con Ubuntu 24.04
- [ ] Usuario admin personal creado con clave SSH
- [ ] `AllowTcpForwarding yes` verificado
- [ ] UFW configurado con IP fija de la Alcaldía
- [ ] Túnel SSH funcionando: `psql -h localhost -p 5433 -U sede_admin`
- [ ] Auditoría SSH (`journalctl -u sshd`) accesible
- [ ] Script `conectar-bastion.sh` entregado al equipo

---

### PROYECTO-04: Pipeline CI/CD para DOKS

**Objetivo:** Reemplazar el pipeline actual (`docker compose push` + `desplegar.sh` SSH) por un pipeline GitHub Actions que construya imágenes, escanee y despliegue a DOKS via Helm.

#### 3.4.1 Pipeline actual (lo que existe)

```
GitHub Actions: ci.yml + despliegue.yml
├── Build (docker build)
├── Trivy scan (HIGH/CRITICAL blocking)
├── Push to ghcr.io
└── SSH to droplet + docker compose pull && docker compose up -d
```

**Problemas del pipeline actual:**
1. No escala a múltiples nodos DOKS (docker compose solo funciona en un host)
2. No soporta Helm ni rollback basado en releases
3. No tiene environment promotions (staging → production)
4. Secrets en GitHub Secrets pero no en Kubernetes Secrets

#### 3.4.2 Pipeline objetivo (DOKS-ready)

```
┌─────────────────────────────────────────────────────────────────┐
│                     GitHub Actions Pipeline                       │
│                                                                  │
│  ┌─────────┐   ┌──────────┐   ┌──────────┐   ┌────────────┐  │
│  │  PR     │──►│   CI     │──►│  Build   │──►│   Push     │  │
│  │ Trigger │   │ (lint,   │   │  (docker │   │  (ghcr.io) │  │
│  │         │   │  test)   │   │  build)  │   │            │  │
│  └─────────┘   └──────────┘   └──────────┘   └─────┬──────┘  │
│                                                     │           │
│                                           ┌─────────▼────────┐   │
│                                           │    Trivy Scan   │   │
│                                           │ (block HIGH/    │   │
│                                           │  CRITICAL)      │   │
│                                           └─────────┬────────┘   │
│                                                     │           │
│                              ┌──────────────────────┼─────────┐  │
│                              │                      │         │  │
│                   ┌──────────▼──────┐   ┌──────────▼────┐  │  │
│                   │   Staging       │   │   Production   │  │
│                   │   deploy        │   │   deploy       │  │
│                   │   (auto)        │   │   (manual      │  │
│                   │                 │   │    approval)   │  │
│                   └─────────────────┘   └────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

#### Arquitectura de GitHub Actions para DOKS

```yaml
# .github/workflows/doks-deploy.yml
name: DOKS Deploy

on:
  push:
    branches: [main, staging, develop]
  pull_request:
    branches: [main]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}
  CLUSTER_NAME: sede-electonica-cluster
  REGION: nyc3

jobs:
  # ── JOB 1: Build y scan (corre en PR y push) ──────────────────────
  build:
    runs-on: ubuntu-latest
    outputs:
      image-tag: ${{ steps.meta.outputs.tags }}

    steps:
      - uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Login to GHCR
        uses: docker/login-action@v3
        with:
          registry: ghcr.io
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Docker metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ghcr.io/${{ github.repository }}/backend
          tags: |
            type=sha,prefix=
            type=ref,event=branch
            type=semver,pattern={{version}}

      - name: Build Docker image
        uses: docker/build-push-action@v5
        with:
          context: ./backend
          push: ${{ github.event_name != 'pull_request' }}
          tags: ${{ steps.meta.outputs.tags }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

      - name: Run Trivy scanner
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: ${{ steps.meta.outputs.tags }}
          format: sarif
          output: trivy-results.sarif
          severity: HIGH,CRITICAL
          exit-code: '1'  # Block on HIGH/CRITICAL

      - name: Upload Trivy results to Security tab
        uses: github/codeql-action/upload-sarif@v2
        if: always()
        with:
          sarif_file: trivy-results.sarif

  # ── JOB 2: Deploy a staging (auto en push a staging) ─────────────
  deploy-staging:
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/staging'
    environment: staging

    steps:
      - uses: actions/checkout@v4

      - name: Setup Helm
        uses: azure/setup-helm@v4
        with:
          version: '3.14.0'

      - name: Configure kubectl for DOKS
        uses: digitalocean/kubectl-config@v1
        with:
          token: ${{ secrets.DOKS_TOKEN_STAGING }}

      - name: Deploy via Helm
        run: |
          helm upgrade --install sede-backend ./helm/sede-backend \
            --namespace sede-staging \
            --create-namespace \
            --set image.tag=${{ needs.build.outputs.image-tag }} \
            --set env.APP_ENV=staging \
            --wait --timeout 5m

  # ── JOB 3: Deploy a producción (manual approval) ─────────────────────
  deploy-production:
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/main'
    environment: production
    # Require manual approval in GitHub Environments
    # (needs GitHub Pro or Team plan)

    steps:
      - uses: actions/checkout@v4

      - name: Setup Helm
        uses: azure/setup-helm@v4
        with:
          version: '3.14.0'

      - name: Configure kubectl for DOKS
        uses: digitalocean/kubectl-config@v1
        with:
          token: ${{ secrets.DOKS_TOKEN_PROD }}

      - name: Run pre-deployment smoke tests
        run: |
          kubectl run smoke-test --image=${{ needs.build.outputs.image-tag }} \
            --restart=Never -n sede-production -- \
            curl -f http://sede-backend:8000/health
        continue-on-error: true

      - name: Deploy via Helm
        run: |
          helm upgrade --install sede-backend ./helm/sede-backend \
            --namespace sede-production \
            --create-namespace \
            --set image.tag=${{ needs.build.outputs.image-tag }} \
            --set env.APP_ENV=production \
            --atomic \
            --cleanup-on-fail \
            --wait --timeout 10m

      - name: Verify deployment
        run: |
          kubectl rollout status deployment/sede-backend -n sede-production
          kubectl rollout status deployment/sede-worker -n sede-production
```

#### GitHub Secrets requeridos

| Secret | Descripción |
|---|---|
| `DOKS_TOKEN_STAGING` | Token de DigitalOcean con acceso K8s para staging |
| `DOKS_TOKEN_PROD` | Token de DigitalOcean con acceso K8s para producción |
| `POSTGRES_PASSWORD_STAGING` | Password de BD staging |
| `POSTGRES_PASSWORD_PROD` | Password de BD producción |
| `REDIS_PASSWORD_STAGING` | Password de Redis staging |
| `REDIS_PASSWORD_PROD` | Password de Redis producción |
| `APP_KEY_STAGING` | Laravel APP_KEY staging |
| `APP_KEY_PROD` | Laravel APP_KEY producción |
| `S3_SECRET_KEY` | Secret para SeaweedFS S3 |

> **Nota sobre GitHub Environments:** las protecciones de entorno (`required_reviewers`, `deployment_branch_policy`) requieren GitHub Pro o Team. Si la entidad no tiene ese plan, la aprobación manual se hace fuera de GitHub (por ejemplo, el lead de proyecto合併 manualmente запускает deploy desde su cuenta).

#### Helm chart structure

```
helm/sede-backend/
├── Chart.yaml
├── values.yaml              # valores por defecto
├── values.staging.yaml     # override para staging
├── values.production.yaml  # override para producción
└── templates/
    ├── deployment-backend.yaml
    ├── deployment-worker.yaml
    ├── deployment-sitio.yaml
    ├── deployment-panel.yaml
    ├── deployment-redis.yaml
    ├── deployment-seaweedfs.yaml
    ├── ingress.yaml
    ├── service.yaml
    ├── secret.yaml           # DB passwords, APP_KEY, etc.
    ├── configmap.yaml        # vars no secret
    └── hpa.yaml
```

#### Costo

No hay costo adicional de pipeline (GitHub Actions gratis para repos públicos, minutos incluidos en el plan).

#### Criterios de	done

- [ ] `doks-deploy.yml` creado en `.github/workflows/`
- [ ] `helm/sede-backend/` chart creado y funcional
- [ ] `ci.yml`原来的 lint + test jobs migrados al nuevo pipeline
- [ ] Trivy blocking on HIGH/CRITICAL
- [ ] Deploy a staging automático en push a `staging`
- [ ] Deploy a producción con approval manual en push a `main`
- [ ] Rollback verificado (`helm rollback sede-backend`)

---

## 4. Stack tecnológico consolidado

| Capa | Tecnología | Versión | Notas |
|---|---|---|---|
| Portal público | Nuxt 4 SSR | 4.5.2 | Sitio público |
| Panel admin | Nuxt SPA | 4.5.2 | Sin SSR |
| Backend API | Laravel 13 (Octane/FrankenPHP) | 13.34 | PHP 8.5 |
| Colas | Laravel Horizon | 5.50 | Supervisor en DOKS |
| BD | PostgreSQL (Managed) | 18.6 | Production + Standby |
| Cache/sessions | Redis | 8.10 | En DOKS, 2 réplicas |
| Storage | SeaweedFS | latest | StatefulSet en DOKS |
| Orquestación | DOKS (Kubernetes gestionado) | 1.30+ | 3 nodos |
| Ingress | NGINX Ingress Controller | Helm | TLS en Cloudflare |
| CI/CD | GitHub Actions | — | DOKS deploy |
| Registry | ghcr.io | — | Imágenes privadas |
| Escaneo | Trivy | — | Blocking HIGH/CRITICAL |
| Borde TLS | Cloudflare | Full Strict | Origen HTTP |
| Hardening host | UFW, fail2ban, auditd, AIDE | — | Solo en droplet bastión |
| Backups BD | DigitalOcean Managed backups + PITR | — | Automático |
| Backups archivos | SeaweedFS snapshots | — | Programado |

---

## 5. Costo mensual consolidado

| Servicio | Costo/mes |
|---|---|
| DOKS 3 × s-4vcpu-8gb | $144 |
| DigitalOcean Load Balancer | $12 |
| DigitalOcean Managed PostgreSQL Production + Standby | $45–60 |
| Droplet bastión SSH | $6 |
| DigitalOcean Spaces (backups off-site, opcional) | $5 |
| **Total infraestructura** | **$212–227/mes** |

> **Brecha con presupuesto ($200/mes):** hay un sobrecosto de **$12–27/mes**. Soluciones posibles:
> 1. Iniciar con Managed PostgreSQL **Essentials** ($25/mes, sin standby) y hacer upgrade a Production cuando el tráfico real justifique el costo.
> 2. Reducir el bastión a un droplet temporal ($4/mes) hasta que haya presupuesto.
> 3. Usar el Load Balancer básico ($10/mes) en lugar del plan con más features.
>
> **Recomendación:** opción 1 (Managed PostgreSQL Essentials + planificar upgrade a Production en 3 meses con crecimiento de usuarios). El costo de Essentials + DOKS + LB + Bastión = **~$189/mes**, dentro del presupuesto.

---

## 6. Riesgos residuales de los proyectos

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|---|
| KR-01 | El equipo no tiene experiencia con Kubernetes/DOKS | Media | Alto | Capacitación Kubernetes (CKA o curso en línea) antes de la Fase 0. Contratar soporte DevOps si el equipo no tiene tiempo |
| KR-02 | Migración de volúmenes emptyDir/hostPath a SeaweedFS rompe los uploads existentes | Baja | Alto | Hacer backup completo de SeaweedFS antes de la migración. Validar que todos los archivos son accesibles post-migración |
| KR-03 | Managed PostgreSQL Essentials no tiene standby → SPOF temporal | Alta | Alto | Planificar upgrade a Production en ≤3 meses. Monitorear con check externo |
| KR-04 | GitHub Environments sin protección (plan no Pro/Team) → no hay approval automático | Media | Medio | Aprobación manual documentada fuera de GitHub; el lead de proyecto合併 запускает el deploy |
| KR-05 | IP dinámica en la Alcaldía impide restringir el acceso SSH al bastión | Alta | Alto | Solicitar rango de IPs fijas a la entidad o implementar VPN (WireGuard) |
| KR-06 | Docker Compose existente no se migra automáticamente a Helm charts | Alta | Medio | Los Charts se escriben desde cero basándose en los manifests de producción actuales. Tiempo estimado: 2–3 semanas |

---

## 7. Firmas

- Usuario: ______________________ Fecha: __________
- Arquitecto: ___________________ Fecha: __________
