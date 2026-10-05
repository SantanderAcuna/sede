# 02 — Arquitectura de Referencia y Proyectos de Infraestructura

**Proyecto:** Sede Electrónica del Distrito de Santa Marta (Alcaldía Distrital, NIT 891.780.009-4)
**Fase:** 2 — Proyectos y Arquitecturas de Referencia
**Fecha de cierre:** 2026-10-05
**Estado:** Pendiente (en revisión por el usuario)
**Aprobado por:** [usuario]

> **Decisión del usuario (2026-10-05):** Docker Compose (no DOKS), mismo número de droplets (3).
> **Documento precedente:** `01_requisitos.md` (Fase 1, aprobado por el usuario 2026-10-05).

---

## 1. Decisiones arquitectónicas base (Fase 1)

| # | Decisión | Valor |
|---|---|---|
| D-01 | Carga | 500.000 sesiones/visitas únicas diarias interactuando |
| D-02 | Presupuesto | $200 USD/mes máximo |
| D-03 | Proveedor cloud | DigitalOcean (GCP descartado) |
| D-04 | BD | DigitalOcean Managed PostgreSQL Production con Standby |
| D-05 | Acceso DB desde local | Túnel SSH vía bastión dedicado ($6/mes) |
| D-06 | **Orquestación** | **Docker Compose en 3 droplets** con Load Balancer de DigitalOcean |
| D-07 | Transparencia | Módulo 02 de los 12 módulos de la sede |

---

## 2. Arquitectura objetivo (target architecture)

```
                            ┌─────────────────────────────────────────┐
                            │         INTERNET / GOV.CO               │
                            │   (redirección con enmascaramiento)     │
                            └──────────────────────┬──────────────────┘
                                                   │
                                                   ▼
                                    ┌────────────────────────┐
                                    │   Cloudflare (WAF)      │
                                    │   Full Strict TLS       │
                                    └────────────┬───────────┘
                                                   │443
                                                   ▼
                                    ┌────────────────────────┐
                                    │  DigitalOcean Load     │  ← M00c
                                    │  Balancer              │  $12/mes
                                    │  healthcheck en :443   │
                                    └────────────┬───────────┘
                                                   │
                     ┌─────────────────────────────┼─────────────────────────────┐
                     │                             │                             │
                     ▼                             ▼                             ▼
            ┌───────────────┐            ┌───────────────┐            ┌───────────────┐
            │  Droplet App  │            │  Droplet App  │            │  Droplet App  │
            │  #1           │            │  #2           │            │  #3           │
            │ 4 vCPU / 8 GB │            │ 4 vCPU / 8 GB │            │ 4 vCPU / 8 GB │
            │ NYC3          │            │ NYC3          │            │ NYC3          │
            └───────┬───────┘            └───────┬───────┘            └───────┬───────┘
                    │                              │                              │
                    │   ┌──────────────────────────┴──────────────────────────┐   │
                    │   │           Docker Compose en cada droplet              │   │
                    │   │                                                      │   │
                    │   │  ┌──────────────┐  ┌──────────────┐  ┌─────────┐   │   │
                    │   │  │ nginx:1.30  │  │              │  │         │   │   │
                    │   │  │ (reverse     │  │ php-fpm +    │  │  Redis  │   │   │
                    │   │  │  proxy TLS)  │  │ Laravel +    │  │  8.10   │   │   │
                    │   │  │              │  │ Horizon      │  │         │   │   │
                    │   │  └──────┬───────┘  └──────┬───────┘  └─────────┘   │   │
                    │   │         │                 │                       │   │
                    │   │         ▼                 ▼                       │   │
                    │   │  ┌──────────────┐  ┌──────────────┐             │   │
                    │   │  │ Nuxt SSR     │  │ Nuxt SPA     │             │   │
                    │   │  │ (sitio/)     │  │ (panel/)     │             │   │
                    │   │  └──────────────┘  └──────────────┘             │   │
                    │   └──────────────────────────────────────────────────┘   │
                    └──────────────────────────────────────────────────────────────┘
                                        │
                                        │ Puerto 9000 (solo red privada)
                                        ▼
                               ┌─────────────────────┐
                               │ Droplet Storage      │  ← M00f
                               │ 2 vCPU / 4 GB / 80GB│  $24/mes
                               │ SeaweedFS (API S3)  │
                               │ Puerto 8332          │
                               └─────────────────────┘

    ┌──────────────────────────────────────────────────────────┐
    │      DigitalOcean Managed PostgreSQL                       │  ← M00b
    │  Production + Standby 2 vCPU / 4 GB RAM / 38 GB SSD    │  $45–60/mes
    │  Private network (solo accesible desde droplets)          │
    └──────────────────────────────────────────────────────────┘

    ┌─────────────┐       SSH túnel      ┌──────────────────────┐
    │  Bastión     │ ◄─────────────────► │  Admin local          │  ← M00d
    │  Droplet     │  (AllowTcpForward)   │  (psql, pgAdmin)     │  $6/mes
    │  1 vCPU/1GB  │                     └──────────────────────┘
    └─────────────┘
         │
         │ SSH jump
         ▼
    ┌──────────────────────────────────────────────────────┐
    │              GitHub Actions CI/CD                      │
    │  Build → Trivy → ghcr.io → Ansible → 3 droplets     │
    └──────────────────────────────────────────────────────┘
```

### Topología de red

```
┌─────────────────────────────────────────────────────┐
│              VPC DigitalOcean (nyc3)                  │
│                                                      │
│  app-droplet-1  10.XXX.0.1   (nginx + php + nuxt) │
│  app-droplet-2  10.XXX.0.2   (nginx + php + nuxt) │
│  app-droplet-3  10.XXX.0.3   (nginx + php + nuxt) │
│  storage-droplet 10.XXX.0.4  (SeaweedFS :8332)     │
│  bastion         10.XXX.0.5  (SSH externo solo)    │
│                                                      │
│  Managed PostgreSQL: endpoint privado               │
└─────────────────────────────────────────────────────┘
```

---

## 3. Proyectos de infraestructura

---

### PROYECTO-01: 3 Droplets con Docker Compose + Load Balancer

**Objetivo:** Replicar la aplicación en 3 droplets idénticos con balanceador de carga, eliminando el SPOF del droplet único actual.

#### 3.1.1 Arquitectura de referencia

| Componente | Especificación |
|---|---|
| **Droplets** | 3 × `s-4vcpu-8gb` (4 vCPU / 8 GB RAM / 80 GB SSD) en nyc3 |
| **Load Balancer** | DigitalOcean Load Balancer (entry point en puerto 443) |
| **Health checks** | HTTP cada 10s en `/health`; elimina droplet no responde en 3 intentos |
| **Docker Compose** | Idéntico en los 3 droplets (`/opt/sede/compose.yaml`) |
| **Conexión entre droplets** | Red privada de DigitalOcean (VPC) en 10.XXX.0.0/16 |
| **Despliegue** | Ansible playbook que ejecuta `docker compose pull && docker compose up -d` en los 3 droplets en paralelo |

#### Docker Compose en cada droplet

```yaml
# /opt/sede/compose.yaml
version: '3.9'

services:
  nginx:
    image: nginx:1.30.5-alpine
    container_name: sede-nginx
    restart: always
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx/nginx.conf:/etc/nginx/nginx.conf:ro
    depends_on:
      - sitio
      - panel

  php:
    image: ghcr.io/sede/backend:${IMAGE_TAG}
    container_name: sede-php
    restart: always
    working_dir: /var/www/html
    volumes:
      - ./backend:/var/www/html:ro
    environment:
      - APP_ENV=${APP_ENV}
      - APP_DEBUG=${APP_DEBUG}
      - DB_CONNECTION=pgsql
      - DB_HOST=${DB_HOST}
      - DB_PORT=${DB_PORT}
      - REDIS_HOST=127.0.0.1
    depends_on:
      - redis

  sitio:
    image: ghcr.io/sede/sitio:${IMAGE_TAG}
    container_name: sede-sitio
    restart: always
    environment:
      - NUXT_HOST=0.0.0.0
      - NUXT_PORT=3000

  panel:
    image: ghcr.io/sede/panel:${IMAGE_TAG}
    container_name: sede-panel
    restart: always
    environment:
      - NUXT_HOST=0.0.0.0
      - NUXT_PORT=3001

  horizon:
    image: ghcr.io/sede/backend:${IMAGE_TAG}
    container_name: sede-horizon
    restart: always
    command: php artisan horizon
    depends_on:
      - php
      - redis

  redis:
    image: redis:8-alpine
    container_name: sede-redis
    restart: always
    ports:
      - "6379:6379"
    command: redis-server --requirepass ${REDIS_PASSWORD}

networks:
  default:
    driver: bridge
```

#### Configuración del Load Balancer

| Parámetro | Valor |
|---|---|
| **Tipo** | DigitalOcean Load Balancer (regional) |
| **Región** | nyc3 |
| **Entrada** | 443 → targets :443 en los 3 droplets |
| **Health check** | `http://:443/health` cada 10s, timeout 5s |
| **SSL** | TLS terminates en Cloudflare (origen HTTP); el LB recibe HTTP y reenvía a droplets |
| **Sticky sessions** | No (sessions en Redis compartido) |
| **Backend protocol** | HTTP (interno, red privada) |

#### Ansible para despliegue multi-host

```ini
# ansible/inventory.ini
[droplets]
app-1 ansible_host=165.22.46.11 ansible_user=deploy
app-2 ansible_host=165.22.46.12 ansible_user=deploy
app-3 ansible_host=165.22.46.13 ansible_user=deploy

[droplets:vars]
ansible_ssh_private_key_file=~/.ssh/id_rsa_deploy
compose_path=/opt/sede
registry=ghcr.io
```

```yaml
# ansible/deploy.yml
- hosts: droplets
  become: true
  gather_facts: true
  vars:
    compose_path: /opt/sede
    registry: ghcr.io

  tasks:
    - name: Ensure /opt/sede exists
      file:
        path: "{{ compose_path }}"
        state: directory
        owner: deploy
        mode: '0755'

    - name: Copy compose file
      template:
        src: compose.yaml.j2
        dest: "{{ compose_path }}/compose.yaml"
      notify: Restart services

    - name: Pull images
      docker_image:
        name: "{{ registry }}/sede/{{ item }}:{{ image_tag }}"
        source: pull
      loop:
        - backend
        - sitio
        - panel
      notify: Restart services

    - name: Tag images locally
      command: >
        docker tag {{ registry }}/sede/{{ item }}:{{ image_tag }}
        sede/{{ item }}:latest
      loop:
        - backend
        - sitio
        - panel

    - name: Restart services
      docker_compose:
        project_src: "{{ compose_path }}"
        state: restarted
      register: result

    - name: Health check
      uri:
        url: "http://localhost/health"
        status_code: 200
      register: health
      until: health.status == 200
      retries: 5
      delay: 10

  handlers:
    - name: Restart services
      docker_compose:
        project_src: "{{ compose_path }}"
        restarted: yes
```

#### Costo mensual

| Recurso | Especificación | Costo/mes |
|---|---|---|
| 3 × Droplet s-4vcpu-8gb | nyc3 | $144 |
| DigitalOcean Load Balancer | regional entry point | $12 |
| **Subtotal** | | **$156/mes** |

#### Criterios de	done

- [ ] 3 droplets creados con Ubuntu 24.04 LTS
- [ ] Docker Compose idéntico en los 3 droplets (`/opt/sede/compose.yaml`)
- [ ] Load Balancer creado y verificando health check hacia los 3 droplets
- [ ] Sessions y cache en Redis local del droplet (no sticky sessions)
- [ ] Ansible playbook ejecuta despliegue simultáneo en los 3 droplets
- [ ] Al apagar un droplet, el LB deja de enviarle tráfico en ≤30s
- [ ] Ansible `deploy.yml` invocado desde GitHub Actions

---

### PROYECTO-02: Droplet de Storage con SeaweedFS

**Objetivo:** Storage compartido S3-compatible (archivos de usuario) accesible desde los 3 droplets de aplicación. Resuelve el problema de archivos subidos en droplet-1 no visibles en droplet-2 o droplet-3.

#### 3.2.1 Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│              Droplet Storage (10.XXX.0.4)                     │
│                    2 vCPU / 4 GB / 80 GB                    │
│                                                              │
│   SeaweedFS Master  :8888 (metadata, solo red interna)      │
│   SeaweedFS Volume  :8332 (API S3, lectura/escritura)       │
│                                                              │
│   Firewall UFW: acepta tráfico solo desde 10.XXX.0.0/16    │
└─────────────────────────────────────────────────────────────┘
          ▲
          │ S3 PUT/GET (puerto 8332, red privada)
          │
┌─────────┴──────────────────────────────────────────────────┐
│  Droplet App #1, #2, #3                                     │
│  Laravel → S3_ENDPOINT=http://10.XXX.0.4:8332               │
│            AWS_ACCESS_KEY_ID=<generado>                      │
│            AWS_SECRET_ACCESS_KEY=<generado>                  │
│            AWS_DEFAULT_REGION=nyc3                           │
│            AWS_S3_BUCKET=sede-files                         │
└─────────────────────────────────────────────────────────────┘
```

#### Configuración Laravel (S3)

```php
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'endpoint' => env('AWS_ENDPOINT', 'http://10.XXX.0.4:8332'),
    'use_path_style_endpoint' => true,  // IMPORTANTE para SeaweedFS
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => 'nyc3',
    'bucket' => env('AWS_BUCKET', 'sede-files'),
],
```

#### Costo mensual

| Recurso | Costo/mes |
|---|---|
| Droplet Storage | 2 vCPU / 4 GB / 80 GB SSD | $24 |
| **Subtotal** | | **$24/mes** |

#### Criterios de	done

- [ ] Droplet Storage creado (2 vCPU / 4 GB)
- [ ] SeaweedFS instalado y respondiendo en `:8332`
- [ ] Bucket `sede-files` creado
- [ ] Credenciales S3 en `secrets.env` de cada droplet app
- [ ] Upload de archivo desde droplet #1 → descarga desde droplet #2 → verificado
- [ ] Backups de volumen configurados (snapshot diario a DO Spaces)

---

### PROYECTO-03: DigitalOcean Managed PostgreSQL Production con Standby

**Objetivo:** Reemplazar PostgreSQL self-hosted en contenedor por una base de datos gestionada con alta disponibilidad.

#### 3.3.1 Arquitectura

```
┌──────────────────────────────────────────────────────┐
│         DigitalOcean Managed PostgreSQL               │
│                                                       │
│  Primary  ────── replicate ──────►  Standby         │
│  (escribe)       síncrono            (replica HA)   │
│                                                       │
│  Private network ──► Droplets app (no internet)     │
└──────────────────────────────────────────────────────┘
```

#### Especificaciones

| Parámetro | Valor |
|---|---|
| **Plan** | Production (incluye Standby) |
| **vCPU** | 2 vCPU |
| **RAM** | 4 GB |
| **Storage** | 38 GB SSD |
| **Región** | nyc3 (misma que droplets) |
| **Alta disponibilidad** | Standby replica síncrona, failover automático |
| **Backups** | Automáticos 7 días + point-in-time recovery |
| **Conexión** | Private network hacia droplets (no tráfico por internet) |

#### Connection string

```
POSTGRES_HOST= privado.private.postgresql.database.digitalocean.com
POSTGRES_PORT=5432
POSTGRES_DB=sede_production
POSTGRES_USER=sede_app
POSTGRES_PASSWORD=<generado con openssl rand -hex 32>
```

#### Costo mensual

| Recurso | Costo/mes |
|---|---|
| Managed PostgreSQL Production + Standby | $45–60 |
| **Subtotal** | **$45–60/mes** |

#### Criterios de	done

- [ ] Managed PostgreSQL Production creado en nyc3 con Standby
- [ ] Private network connectivity verificada desde los 3 droplets app
- [ ] Migrations aplicadas (`php artisan migrate`)
- [ ] Usuario `sede_app` con privilegios mínimos (no superuser)
- [ ] Backups automáticos activos (retention ≥ 7 días)
- [ ] Failover automático probado (matar primary, verificar standby toma)
- [ ] Connection string en `secrets.env` de cada droplet app

---

### PROYECTO-04: Túnel SSH vía Droplet Bastión

**Objetivo:** Permitir administración de la BD (psql, pg_dump) desde la red local sin exponer PostgreSQL a internet.

#### 3.4.1 Arquitectura

```
┌────────────────┐    SSH puerto 22    ┌────────────────┐
│  Admin local   │ ───────────────────►  Droplet Bastión  │
│  (psql,        │    (solo IP fija     │  1 vCPU / 1 GB  │
│   pgAdmin)     │     de la Alcaldía)  │  $6/mes         │
└───────┬────────┘                     │  AllowTcpFwd yes │
        │                              └────────┬────────┘
        │ SSH túnel (-L 5433:...)                 │ SSH jump
        │                                        │
        ▼                                        ▼
┌─────────────────────────────────────────────────┐
│  Managed PostgreSQL (sin acceso a internet)       │
└─────────────────────────────────────────────────┘
```

#### Especificaciones del bastión

| Parámetro | Valor |
|---|---|
| **Droplet** | `s-1vcpu-1gb` Ubuntu 24.04 LTS |
| **Región** | nyc3 |
| **Firewall** | UFW: solo 22/tcp desde IP fija de la Alcaldía |
| **SSH** | Clave pública/privada. `AllowTcpForwarding yes`. `PasswordAuthentication no` |
| **Usuarios** | `deploy` (pipeline) + `admin_<nombre>` (uno por admin) |

#### Script de túnel para el admin

```bash
#!/bin/bash
# conectar-bastion.sh
BASTION_HOST="<IP_DEL_BASTION>"
ADMIN_USER="admin_tu_nombre"
LOCAL_PORT="5433"
REMOTE_DB_HOST="<DO_ManagedDB_PrivateHost>"
REMOTE_DB_PORT="5432"

echo "Abriendo túnel: localhost:${LOCAL_PORT} → ${BASTION_HOST} → ${REMOTE_DB_HOST}:${REMOTE_DB_PORT}"
ssh -L ${LOCAL_PORT}:${REMOTE_DB_HOST}:${REMOTE_DB_PORT} \
    -N -C ${ADMIN_USER}@${BASTION_HOST} &
SSH_PID=$!

echo "Túnel PID: $SSH_PID"
echo "Conectar con: psql -h localhost -p ${LOCAL_PORT} -U sede_admin -d sede_production"
echo "Ctrl+C para cerrar"
wait $SSH_PID
```

#### Costo mensual

| Recurso | Costo/mes |
|---|---|
| Droplet bastión | $6 |
| **Subtotal** | **$6/mes** |

#### Criterios de	done

- [ ] Droplet bastión creado con Ubuntu 24.04
- [ ] Usuario admin personal con clave SSH
- [ ] `AllowTcpForwarding yes` verificado
- [ ] UFW con IP fija de la Alcaldía
- [ ] Túnel funcional: `psql -h localhost -p 5433 -U sede_admin`
- [ ] Script `conectar-bastion.sh` entregado al equipo

---

### PROYECTO-05: Pipeline CI/CD Multi-Host con Ansible

**Objetivo:** Reemplazar `desplegar.sh` (un solo host) por un pipeline GitHub Actions que despliegue simultáneamente a los 3 droplets usando Ansible.

#### 3.5.1 Pipeline objetivo

```
push (PR/branch) ──► lint + test ──► build + push ──► Trivy ──►
                                         │
                    ┌────────────────────┴────────────────────┐
                    │                                         │
          Staging auto-deploy                        Production manual
          (ansible-playbook a 3 pods)              (ansible-playbook a 3 pods)
```

#### GitHub Actions workflow

```yaml
# .github/workflows/multi-host-deploy.yml
name: Multi-Host Docker Deploy

on:
  push:
    branches: [main, staging, develop]
  pull_request:
    branches: [main]

env:
  REGISTRY: ghcr.io
  IMAGE_TAG: ${{ github.sha }}

jobs:
  build:
    runs-on: ubuntu-latest
    outputs:
      image-tag: ${{ env.IMAGE_TAG }}

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

      - name: Build and push backend
        run: |
          docker build -t $REGISTRY/sede/backend:$IMAGE_TAG ./backend
          docker push $REGISTRY/sede/backend:$IMAGE_TAG

      - name: Build and push sitio
        run: |
          docker build -t $REGISTRY/sede/sitio:$IMAGE_TAG ./sitio
          docker push $REGISTRY/sede/sitio:$IMAGE_TAG

      - name: Build and push panel
        run: |
          docker build -t $REGISTRY/sede/panel:$IMAGE_TAG ./panel
          docker push $REGISTRY/sede/panel:$IMAGE_TAG

      - name: Run Trivy scanner
        uses: aquasecurity/trivy-action@master
        with:
          image-ref: $REGISTRY/sede/backend:$IMAGE_TAG
          format: sarif
          output: trivy-results.sarif
          severity: HIGH,CRITICAL
          exit-code: '1'

      - name: Upload to Security tab
        uses: github/codeql-action/upload-sarif@v2
        if: always()
        with:
          sarif_file: trivy-results.sarif

  deploy-staging:
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/staging'
    environment: staging

    steps:
      - uses: actions/checkout@v4
      - name: Setup Python + Ansible
        run: pip install ansible

      - name: Deploy via Ansible
        env:
          IMAGE_TAG: ${{ needs.build.outputs.image-tag }}
        run: |
          ansible-playbook ansible/deploy.yml \
            -i ansible/inventory.staging.ini \
            --extra-vars "image_tag=$IMAGE_TAG"

  deploy-production:
    runs-on: ubuntu-latest
    needs: build
    if: github.ref == 'refs/heads/main'
    environment: production

    steps:
      - uses: actions/checkout@v4
      - name: Setup Python + Ansible
        run: pip install ansible

      - name: Deploy via Ansible
        env:
          IMAGE_TAG: ${{ needs.build.outputs.image-tag }}
        run: |
          ansible-playbook ansible/deploy.yml \
            -i ansible/inventory.production.ini \
            --extra-vars "image_tag=$IMAGE_TAG"

      - name: Verify deployment
        run: |
          curl -f https://www.santamarta.gov.co/health || exit 1
```

#### Ansible playbook (deploy.yml)

```yaml
# ansible/deploy.yml
- hosts: droplets
  become: true
  gather_facts: true
  vars:
    compose_path: /opt/sede
    registry: ghcr.io

  tasks:
    - name: Ensure /opt/sede exists
      file:
        path: "{{ compose_path }}"
        state: directory
        owner: deploy
        mode: '0755'

    - name: Copy secrets
      copy:
        content: "{{ secrets_content }}"
        dest: "{{ compose_path }}/.env"
        mode: '0600'

    - name: Pull images
      docker_image:
        name: "{{ registry }}/sede/{{ item }}:{{ image_tag }}"
        source: pull
      loop:
        - backend
        - sitio
        - panel

    - name: Tag images locally
      command: >
        docker tag {{ registry }}/sede/{{ item }}:{{ image_tag }}
        sede/{{ item }}:latest
      loop:
        - backend
        - sitio
        - panel

    - name: Restart services
      docker_compose:
        project_src: "{{ compose_path }}"
        state: restarted

    - name: Wait for services to be ready
      wait_for:
        timeout: 30

    - name: Health check
      uri:
        url: "http://localhost/health"
        status_code: 200
      register: health
      until: health.status == 200
      retries: 5
      delay: 10

  handlers:
    - name: Restart services
      docker_compose:
        project_src: "{{ compose_path }}"
        restarted: yes
```

#### Costo

Sin costo adicional (GitHub Actions gratis para repositorios públicos).

#### Criterios de	done

- [ ] `multi-host-deploy.yml` creado en `.github/workflows/`
- [ ] `ansible/deploy.yml` funcional (despliegue simultáneo a 3 droplets)
- [ ] `ansible/inventory.production.ini` con las IPs de los 3 droplets
- [ ] `ansible/inventory.staging.ini` con IPs de staging
- [ ] Trivy blocking on HIGH/CRITICAL en el pipeline
- [ ] Deploy a staging automático en push a `staging`
- [ ] Deploy a producción con approval manual en push a `main`
- [ ] Rollback funcional: `ansible-playbook ansible/rollback.yml -e "image_tag=<previous>"`
- [ ] `secrets.env` en cada droplet (postgres, redis, S3 credentials)

---

## 4. Stack tecnológico consolidado

| Capa | Tecnología | Versión | Notas |
|---|---|---|---|
| Portal público | Nuxt 4 SSR | 4.5.2 | Sitio público |
| Panel admin | Nuxt SPA | 4.5.2 | Sin SSR |
| Backend API | Laravel 13 (Octane/FrankenPHP) | 13.34 | PHP 8.5 |
| Colas | Laravel Horizon | 5.50 | Supervisor en Docker Compose |
| BD | PostgreSQL (Managed) | 18.6 | Production + Standby |
| Cache/sessions | Redis | 8.10 | En cada droplet |
| Storage archivos | SeaweedFS | latest | Droplet storage dedicado, API S3 |
| Orquestación | Docker Compose | 5.5.1 | En cada droplet (idéntico) |
| Despliegue | Ansible | 2.17+ | Playbook multi-host |
| Ingress | DigitalOcean Load Balancer + nginx | — | TLS en Cloudflare |
| CI/CD | GitHub Actions | — | Build → Trivy → Ansible → 3 droplets |
| Registry | ghcr.io | — | Imágenes privadas |
| Escaneo | Trivy | — | Blocking HIGH/CRITICAL |
| Borde TLS | Cloudflare | Full Strict | Origen HTTP |
| Hardening host | UFW, fail2ban, auditd, AIDE | — | En todos los droplets |
| Backups BD | Managed PostgreSQL backups + PITR | — | Automático |
| Backups archivos | SeaweedFS snapshots + DO Spaces | — | Programado |

---

## 5. Costo mensual consolidado

| Servicio | Especificación | Costo/mes |
|---|---|---|
| 3 × Droplet s-4vcpu-8gb | nyc3 | $144 |
| DigitalOcean Load Balancer | regional | $12 |
| Droplet Storage (SeaweedFS) | 2 vCPU / 4 GB | $24 |
| DigitalOcean Managed PostgreSQL Production + Standby | 2 vCPU / 4 GB / 38 GB SSD | $45–60 |
| Droplet Bastión SSH | 1 vCPU / 1 GB | $6 |
| **Total infraestructura completa** | | **$231–246/mes** |

### Plan para caber en $200/mes

**Total: $231–246/mes — supera el presupuesto por $31–46/mes.**

| Servicio | Inicio ($/mes) | Futuro ($/mes) |
|---|---|---|
| 3 × Droplet s-4vcpu-8gb | $144 | $144 |
| Load Balancer | $12 | $12 |
| Managed PostgreSQL **Essentials** (sin standby) | **$25** | → $45–60 (upgrade) |
| SeaweedFS en droplet App-3 (no dedicado) | **$0** | → $24 (droplet propio) |
| Bastión SSH | **$0** (diferido) | → $6 (cuando haya presupuesto) |
| **Total inicio** | **$181/mes** ✅ | → $231–246 (cuando crezca) |

> Managed PostgreSQL Essentials incluye backups automáticos y PITR pero **no tiene standby** (SPOF temporal). Para mitigar: monitorear activamente y hacer upgrade a Production + Standby en ≤3 meses.

---

## 6. Comparativa: Docker Compose 3 droplets vs DOKS

| Criterio | Docker Compose (3 droplets) | DOKS (3 nodos) |
|---|---|---|
| Costo mensual | $231–246 | $207–222 |
| Auto-scaling | No | Sí (HPA + Cluster Autoscaler) |
| Gestión de secretos | Manual (`secrets.env` por droplet) | Kubernetes Secrets centralizado |
| Despliegue | Ansible playbook | Helm upgrade |
| Rollback | Ansible con `image_tag` anterior | `helm rollback` |
| Alta disponibilidad app | LB + 3 droplets (nginx reinicia si cae) | DOKS reinicia pods automáticamente |
| Experiencia requerida | Ansible + Docker Compose | Kubernetes + Helm |
| Tiempo de setup | ~1–2 semanas | ~3–4 semanas |
| Sessions | Redis local (no sticky sessions gracias a LB) | Redis cluster o session affinity |

**Razón de la elección del usuario:** menor complejidad operacional (equipo ya conoce Docker Compose), mismo número de droplets, misma resiliencia a nivel de aplicación.

---

## 7. Riesgos residuales

| # | Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|---|
| KR-01 | Managed PostgreSQL Essentials = SPOF temporal (sin standby) | Alta | Alto | Monitoreo constante; upgrade a Production en ≤3 meses; PITR de Essentials mitiga pérdida de datos |
| KR-02 | SeaweedFS en droplet App-3 = bottleneck de I/O si mucho tráfico de archivos | Media | Medio | Monitorear I/O; migrar a droplet dedicado cuando haya presupuesto |
| KR-03 | Sin bastión, acceso SSH directo a droplets (riesgo si IP admin cambia) | Media | Medio | Solicitar IP fija a la entidad; o VPN de DigitalOcean ($10/mes) |
| KR-04 | Ansible playbook desincroniza los 3 droplets | Baja | Alto | Probar siempre en staging; snapshot de cada droplet antes de deploy |
| KR-05 | Docker compose up -d causa micro-cortes (< 5s) en cada restart | Baja | Medio | Implementar healthchecks + `docker compose up -d --no-deps` con rolling restart |
| KR-06 | Presupuesto ligeramente sobre ($181 vs $200 disponible) | Alta | Medio | Monitoreo de costos semanal; buscar optimización (droplet storage más pequeño si hay poco tráfico archivos) |

---

## 8. Firmas

- Usuario: ______________________ Fecha: __________
- Arquitecto: ___________________ Fecha: __________
