# Informe de arquitectura de seguridad — Capítulo 03 (partes A y B)

**Alcance:** únicamente lo que dicen `capitulo-03-parte-a.md` (409 líneas) y `capitulo-03-parte-b.md` (485 líneas), leídos completos. Cuando algo no aparece en ellos se escribe **no consta**. Los bloques de configuración se reproducen literales. Las afirmaciones externas a los documentos (verificación contra nginx.org, RFC, etc.) se marcan explícitamente como **[verificación externa]**.

---

## 1. nginx

### 1.1 Versión, repositorio oficial e instalación (literal)

Declaración de stack: Ubuntu 24.04 LTS (noble), **nginx 1.29.x mainline**, OpenSSL 3.0.x, Let's Encrypt vía Certbot, Cloudflare (plan Free o superior), Laravel 13 como API en `api.dominio.tld`, Vue 3 + TypeScript en `app.dominio.tld` — `capitulo-03-parte-a.md:3`. Repetido en `capitulo-03-parte-a.md:17` (`nginx 1.29 mainline`) y `capitulo-03-parte-a.md:30` (título de sección `nginx 1.29 mainline en Ubuntu 24.04`).

La decisión de no usar el paquete de Ubuntu es explícita: el `nginx` de Ubuntu 24.04 "compila con OpenSSL del sistema y suele tener uno o más ciclos de retraso frente a mainline", con el argumento de CVEs sin parchear (familia HTTP/2 Rapid Reset, CVE-2023-44487) — `capitulo-03-parte-a.md:34`. Decisión [A]: instalar desde el repositorio oficial nginx.org y fijar la versión mainline estable (1.29.x) — `capitulo-03-parte-a.md:36`.

Instalación literal (`capitulo-03-parte-a.md:40-65`):

```bash
# 1. Prerrequisitos
sudo apt install -y curl gnupg2 ca-certificates lsb-release

# 2. Clave GPG del repo oficial nginx.org
curl -fsSL https://nginx.org/keys/nginx_signing.key | \
  sudo gpg --dearmor -o /usr/share/keyrings/nginx-archive-keyring.gpg

# 3. Fuente APT firmada
echo "deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] \
http://nginx.org/packages/mainline/ubuntu $(lsb_release -cs) nginx" | \
  sudo tee /etc/apt/sources.list.d/nginx.list

# 4. Pinning para que el repo oficial gane al de Ubuntu
cat | sudo tee /etc/apt/preferences.d/99nginx <<'EOF'
Package: *
Pin: origin nginx.org
Pin: release o=nginx
Pin-Priority: 900
EOF

# 5. Instalar
sudo apt update
sudo apt install -y nginx
nginx -v   # debe decir nginx/1.29.x
```

Justificación del pinning [F]: "sin el `Pin-Priority: 900`, Ubuntu 24.04 puede seguir dando prioridad a su propio paquete `nginx` aunque haya una versión más reciente en nginx.org" — `capitulo-03-parte-a.md:67`.

### 1.2 systemd unit hardening (COMPLETO y literal)

`capitulo-03-parte-a.md:71`: "El unit que instala nginx.org no trae endurecimiento por defecto." Se crea un drop-in:

```bash
sudo mkdir -p /etc/systemd/system/nginx.service.d
```
— `capitulo-03-parte-a.md:74`.

`/etc/systemd/system/nginx.service.d/override.conf` — `capitulo-03-parte-a.md:77-102`, literal y completo:

```ini
[Service]
# Filesystem
ProtectSystem=strict
ProtectHome=yes
PrivateTmp=yes
ReadWritePaths=/var/cache/nginx /var/log/nginx /var/lib/nginx
# Procesos y privilegios
NoNewPrivileges=yes
ProtectKernelTunables=yes
ProtectKernelModules=yes
ProtectControlGroups=yes
RestrictAddressFamilies=AF_UNIX AF_INET AF_INET6
RestrictNamespaces=yes
RestrictRealtime=yes
LockPersonality=yes
MemoryDenyWriteExecute=yes
# Capacidades mínimas: solo bind en 80/443
CapabilityBoundingSet=CAP_NET_BIND_SERVICE
AmbientCapabilities=CAP_NET_BIND_SERVICE
# Red
RestrictSUIDSGID=yes
SystemCallArchitectures=native
```

Aplicación y verificación (`capitulo-03-parte-a.md:108-112`):

```bash
sudo systemctl daemon-reload
sudo systemctl restart nginx
sudo systemctl show nginx | grep -E "ProtectSystem|NoNewPrivileges|CapabilityBoundingSet"
```

Verificación esperada: "la última línea debe contener `ProtectSystem=strict`, `NoNewPrivileges=yes`, `CapabilityBoundingSet=CAP_NET_BIND_SERVICE`" — `capitulo-03-parte-a.md:114`. Referencias dadas: `man systemd.exec` y `man systemd.service` — `capitulo-03-parte-a.md:104`.

### 1.3 Estructura de directorios (literal)

`capitulo-03-parte-a.md:120`: se usa "jerarquía explícita con `conf.d/` para globales y `sites-available/` para virtuales", con snippets "archivos versionados, no symlinks".

```
/etc/nginx/
├── nginx.conf                # master
├── conf.d/
│   ├── ssl-params.conf       # protocolos, cifrados, stapling
│   ├── security-headers.conf # todos los headers de seguridad
│   ├── rate-limit.conf       # zonas limit_req / limit_conn
│   └── cloudflare-realip.conf# set_real_ip_from rangos CF
├── snippets/
│   └── php-fpm.conf          # location ~ \.php$ reusado
├── sites-available/
│   ├── api.dominio.tld.conf
│   └── app.dominio.tld.conf
└── sites-enabled/            # symlinks a sites-available
    ├── api.dominio.tld.conf -> ../sites-available/api.dominio.tld.conf
    └── app.dominio.tld.conf -> ../sites-available/app.dominio.tld.conf
```
— `capitulo-03-parte-a.md:122-138`.

Activación (`capitulo-03-parte-a.md:142-147`):

```bash
sudo ln -s /etc/nginx/sites-available/api.dominio.tld.conf \
           /etc/nginx/sites-enabled/api.dominio.tld.conf
sudo nginx -t   # validar sintaxis
sudo systemctl reload nginx
```

### 1.4 Server block API Laravel (`api.dominio.tld`) — COMPLETO

`/etc/nginx/sites-available/api.dominio.tld.conf` — `capitulo-03-parte-a.md:155-243`:

```nginx
# Redirección HTTP → HTTPS (excepto ACME challenge)
server {
    listen 80;
    listen [::]:80;
    server_name api.dominio.tld;

    # Let's Encrypt HTTP-01 challenge: debe responder en HTTP
    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
        default_type "text/plain";
    }

    location / {
        return 301 https://api.dominio.tld$request_uri;
    }
}

# HTTPS principal
server {
    listen 443 ssl;
    http2 on;
    listen [::]:443 ssl;
    http2 on;

    server_name api.dominio.tld;

    # ================ Certificados y TLS ================
    ssl_certificate     /etc/letsencrypt/live/api.dominio.tld/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.dominio.tld/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/api.dominio.tld/chain.pem;
    include /etc/nginx/conf.d/ssl-params.conf;

    # ================ Authenticated Origin Pulls (mTLS Cloudflare) ================
    ssl_client_certificate /etc/nginx/certs/cloudflare-authenticated-origin-pull.ca.pem;
    ssl_verify_client on;

    # ================ Logging ================
    access_log /var/log/nginx/api.dominio.tld.access.log;
    error_log  /var/log/nginx/api.dominio.tld.error.log warn;

    # ================ Real IP detrás de Cloudflare ================
    include /etc/nginx/conf.d/cloudflare-realip.conf;

    # ================ Rate limiting ================
    include /etc/nginx/conf.d/rate-limit.conf;
    limit_req zone=api burst=20 nodelay;
    limit_req_status 429;

    # ================ Hardening general ================
    server_tokens off;
    more_clear_input_headers "X-Powered-By";  # requiere headers-more-nginx-module

    root /var/www/api.dominio.tld/public;
    index index.php;

    charset utf-8;

    # ================ Denegar acceso a archivos sensibles ================
    location ~ /\.(?!well-known).* {
        deny all;
        return 404;
    }

    location ~* (?:^|/)(?:\.env|\.env\..*|artisan|composer\.json|composer\.lock|package\.json|package-lock\.json|yarn\.lock|\.git(?:ignore|attributes)?|\.user\.invml|node_modules|vendor/(?!laravel/framework/src/Illuminate/.*\.php)) {
        deny all;
        return 404;
    }

    # ================ Laravel routing ================
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # ================ PHP-FPM ================
    include /etc/nginx/snippets/php-fpm.conf;

    # ================ Seguridad adicional ================
    location ~* \.(?:css|js|jpg|jpeg|gif|png|ico|svg|woff2?|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable" always;
        access_log off;
    }

    # ================ Errores personalizados ================
    error_page 404 /index.php;
    error_page 500 502 503 504 /index.php;
}
```

Puntos clave declarados [F] (`capitulo-03-parte-a.md:245-249`):
- `ssl_verify_client on` activa mTLS contra Cloudflare (Authenticated Origin Pulls); la CA es la publicada por Cloudflare.
- `http2 on` "está deprecado en nginx 1.27+; ahora se usa `listen 443 ssl; http2 on;` o `listen 443 ssl http2;`".
- `more_clear_input_headers "X-Powered-By"` "solo funciona si compilaste nginx con `headers-more-nginx-module`. Si no, quita esa línea."

### 1.5 Server block frontend (`app.dominio.tld`) — COMPLETO

`/etc/nginx/sites-available/app.dominio.tld.conf` — `capitulo-03-parte-a.md:255-323`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name app.dominio.tld;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/letsencrypt;
        default_type "text/plain";
    }
    location / {
        return 301 https://app.dominio.tld$request_uri;
    }
}

server {
    listen 443 ssl;
    http2 on;
    listen [::]:443 ssl;
    http2 on;

    server_name app.dominio.tld;

    ssl_certificate     /etc/letsencrypt/live/app.dominio.tld/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.dominio.tld/privkey.pem;
    ssl_trusted_certificate /etc/letsencrypt/live/app.dominio.tld/chain.pem;
    include /etc/nginx/conf.d/ssl-params.conf;

    ssl_client_certificate /etc/nginx/certs/cloudflare-authenticated-origin-pull.ca.pem;
    ssl_verify_client on;

    access_log /var/log/nginx/app.dominio.tld.access.log;
    error_log  /var/log/nginx/app.dominio.tld.error.log warn;

    include /etc/nginx/conf.d/cloudflare-realip.conf;

    root /var/www/frontend/dist;
    index index.html;

    server_tokens off;
    charset utf-8;

    # SPA fallback: cualquier ruta no existente → index.html
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Assets con hash en el nombre → cache 1 año
    location ~* \.(?:js|css|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|webp|ico)$ {
        expires 365d;
        add_header Cache-Control "public, max-age=31536000, immutable" always;
        access_log off;
        try_files $uri =404;
    }

    # index.html NUNCA debe cachearse agresivamente
    location = /index.html {
        add_header Cache-Control "no-cache, no-store, must-revalidate" always;
        add_header Pragma "no-cache" always;
        add_header Expires "0" always;
    }

    # Denegar acceso al resto
    location ~ /\.(?!well-known).* {
        deny all;
        return 404;
    }
}
```

Decisión [A]: el `index.html` se sirve con `no-cache` siempre; los assets con hash se cachean un año ("Cache immutability", vitejs.dev/guide/assets) — `capitulo-03-parte-a.md:325`.

### 1.6 Snippet PHP-FPM, con la defensa anti-upload-shell (literal)

`/etc/nginx/snippets/php-fpm.conf` — `capitulo-03-parte-a.md:333-354`:

```nginx
# Bloquea la ejecución de PHP en archivos subidos (defensa anti-upload-shell)
location ~* \.(?:ph(p[3457]?|t|tml|ar))$ {
    try_files $uri =404;
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param PATH_INFO $fastcgi_path_info;
    fastcgi_param HTTPS on;

    # Cabeceras de seguridad por respuesta (heredadas del server block)
    fastcgi_hide_header X-Powered-By;

    # Timeouts anti-Slowloris
    fastcgi_connect_timeout 5s;
    fastcgi_send_timeout    30s;
    fastcgi_read_timeout    30s;
    fastcgi_buffers         16 16k;
    fastcgi_buffer_size     32k;
}
```

Justificación de `try_files $uri =404` **antes** de `fastcgi_pass`: "bloquea que un atacante suba un archivo `evil.jpg` que contenga PHP embebido y lo ejecute nombrándolo como `evil.jpg/something.php`. Sin esa línea, nginx puede pasar a PHP-FPM cualquier archivo cuya extensión parezca PHP." — `capitulo-03-parte-a.md:356`.

### 1.7 Organización de los `include`

Incluidos dentro de los server blocks:
- API: `ssl-params.conf` (`capitulo-03-parte-a.md:186`), `cloudflare-realip.conf` (`:197`), `rate-limit.conf` (`:200`), `snippets/php-fpm.conf` (`:230`).
- Frontend: `ssl-params.conf` (`:281`), `cloudflare-realip.conf` (`:289`). **No incluye `rate-limit.conf` ni `security-headers.conf`.**
- Snippet PHP-FPM: `include fastcgi_params;` (`capitulo-03-parte-a.md:339`).

Master `nginx.conf`: sólo se describe en el diagrama Mermaid, con `include /etc/nginx/conf.d/*.conf` e `include /etc/nginx/sites-enabled/*` — `capitulo-03-parte-a.md:387-388`. **El contenido de `nginx.conf` y la línea que añade `sites-enabled` no constan** en ninguno de los dos archivos.

Jerarquía declarada en el diagrama (`capitulo-03-parte-a.md:386-403`): `A --> B (conf.d/*.conf)` y `A --> C (sites-enabled/*)`; `B --> B1 ssl-params.conf`, `B --> B2 security-headers.conf`, `B --> B3 rate-limit.conf`, `B --> B4 cloudflare-realip.conf`; `S1 --> SP php-fpm.conf`, `S1 --> B1`, `S1 --> B2`, `S1 --> B3`, `S1 --> B4`; `S2 --> B1`, `S2 --> B2`, `S2 --> B4`. Decisión [A]: "los `include` desde los server blocks referencian la fuente única" — `capitulo-03-parte-a.md:405`.

`capitulo-03-parte-b.md:3` asume que "nginx 1.29 está instalado, los server blocks existen y los `include` referencian archivos en `/etc/nginx/conf.d/` y `/etc/nginx/snippets/`".

---

## 2. TLS

### 2.1 `ssl-params.conf` COMPLETO y literal

`/etc/nginx/conf.d/ssl-params.conf` — `capitulo-03-parte-b.md:107-153`:

```nginx
# ================ Protocolos ================
# TLS 1.0 y 1.1 están deprecados por RFC 8996 (2021-03). Excluidos.
ssl_protocols TLSv1.2 TLSv1.3;

# ================ Cipher suites TLS 1.2 ================
# Mozilla "intermediate" — equilibrio compatibilidad/seguridad
# Generado en https://ssl-config.mozilla.org/#server=nginx&config=intermediate&openssl=3.0.x
ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;

# En TLS 1.3 los cipher suites no son configurables (están en el estándar)
ssl_conf_command Ciphersuites TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256;

# Preferencia de cifrado del lado servidor (no aplica a TLS 1.3 pero sí a 1.2)
ssl_prefer_server_ciphers on;

# ================ Key exchange curves ================
ssl_ecdh_curve X25519:secp384r1;

# ================ Session resumption ================
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 1d;
ssl_session_tickets off;   # off para forward secrecy perfecto entre sesiones

# ================ OCSP stapling ================
ssl_stapling on;
ssl_stapling_verify on;
resolver 1.1.1.1 8.8.8.8 valid=300s;
resolver_timeout 5s;

# ================ TLS 1.3 0-RTT ================
ssl_early_data on;
# Mitigación de replay: NO reenviar early data a menos que el método sea seguro
# (GET, HEAD, OPTIONS). Los POST/PUT pueden reenviarse y eso es replay.
# Aplicado en el server block:
#   proxy_set_header Early-Data $ssl_early_data;
#   if ($ssl_early_data = "1") { set $early_data "1"; }
#   if ($request_method !~ ^(GET|HEAD|OPTIONS)$) { set $early_data ""; }
#   proxy_set_header Early-Data $early_data;

# ================ TLS en HTTP/2 ================
# http2 ya está en listen 443 ssl http2; (server block)

# ================ HSTS ================
# max-age de 2 años, subdominios incluidos, apto para hstspreload.org
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
```

### 2.2 Valores exactos de cada directiva

| Directiva | Valor literal | Cita |
|---|---|---|
| `ssl_protocols` | `TLSv1.2 TLSv1.3` | `capitulo-03-parte-b.md:110` |
| `ssl_ciphers` | `ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384` | `capitulo-03-parte-b.md:115` |
| `ssl_conf_command Ciphersuites` | `TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256` | `capitulo-03-parte-b.md:118` |
| `ssl_prefer_server_ciphers` | `on` | `capitulo-03-parte-b.md:121` |
| `ssl_ecdh_curve` | `X25519:secp384r1` | `capitulo-03-parte-b.md:124` |
| `ssl_session_cache` | `shared:SSL:10m` | `capitulo-03-parte-b.md:127` |
| `ssl_session_timeout` | `1d` | `capitulo-03-parte-b.md:128` |
| `ssl_session_tickets` | `off` | `capitulo-03-parte-b.md:129` |
| `ssl_stapling` / `ssl_stapling_verify` | `on` / `on` | `capitulo-03-parte-b.md:132-133` |
| `resolver` / `resolver_timeout` | `1.1.1.1 8.8.8.8 valid=300s` / `5s` | `capitulo-03-parte-b.md:134-135` |
| `ssl_early_data` | `on` | `capitulo-03-parte-b.md:138` |
| `add_header Strict-Transport-Security` | `max-age=63072000; includeSubDomains; preload` (`always`) | `capitulo-03-parte-b.md:152` |
| `ssl_dhparam` | **no consta** (ausente en ambos archivos) | — |
| `ssl_trusted_certificate` | `/etc/letsencrypt/live/<dominio>/chain.pem` (por sitio) | `capitulo-03-parte-a.md:185`, `:280` |
| `ssl_certificate` / `ssl_certificate_key` | `.../fullchain.pem` / `.../privkey.pem` | `capitulo-03-parte-a.md:183-184`, `:278-279` |
| `ssl_client_certificate` / `ssl_verify_client` | `/etc/nginx/certs/cloudflare-authenticated-origin-pull.ca.pem` / `on` | `capitulo-03-parte-a.md:189-190`, `:283-284` |

Justificaciones dadas [F]/[A]: TLS 1.0/1.1 deprecados por RFC 8996 y penalizados por Qualys y Mozilla Observatory; clientes actuales hablan TLS 1.2 mínimo — `capitulo-03-parte-b.md:157`. `ssl_session_tickets off` "para forward secrecy perfecto entre sesiones" (los tickets introducen estado) — `capitulo-03-parte-b.md:129` y `:159`. La cipher list "coincide con el perfil intermediate" de Mozilla; con el perfil "modern" se perdería compatibilidad con Windows 7 / Java 8 — `capitulo-03-parte-b.md:155`.

**0-RTT y mitigación de replay:** `ssl_early_data on;` (`capitulo-03-parte-b.md:138`), con mitigación declarada **comentada** (no activa) en `capitulo-03-parte-b.md:141-145`: no reenviar early data salvo método `GET|HEAD|OPTIONS`. El documento advierte explícitamente que "los POST/PUT pueden reenviarse y eso es replay" (`capitulo-03-parte-b.md:139-140`).

**HTTP/2:** comentario literal "`http2 ya está en listen 443 ssl http2; (server block)`" — `capitulo-03-parte-b.md:148`. En los server blocks reales lo que hay es `http2 on;` (`capitulo-03-parte-a.md:176`, `:178`, `:272`, `:274`).

### 2.3 Let's Encrypt + Certbot

Instalación (`capitulo-03-parte-b.md:13-17`), con la recomendación de `snap` sobre `apt` porque "trae los plugins DNS actualizados" (`capitulo-03-parte-b.md:11`):

```bash
sudo snap install --classic certbot
sudo ln -sf /snap/bin/certbot /usr/bin/certbot
certbot --version   # debe decir certbot 3.x o superior
```

Emisión HTTP-01 con plugin nginx (`capitulo-03-parte-b.md:23-28`):

```bash
sudo certbot --nginx \
    -d api.dominio.tld \
    -d app.dominio.tld \
    --no-redirect   # controlamos la redirección en el server block, no aquí
```

Rutas resultantes (`capitulo-03-parte-b.md:32-38`):

```
/etc/letsencrypt/live/api.dominio.tld/
├── fullchain.pem      ← ssl_certificate
├── privkey.pem        ← ssl_certificate_key
├── chain.pem          ← ssl_trusted_certificate
└── cert.pem
```

Validación (`capitulo-03-parte-b.md:40`): `openssl x509 -in /etc/letsencrypt/live/api.dominio.tld/fullchain.pem -noout -subject -issuer -dates`, que debe mostrar Subject `CN = api.dominio.tld` (o wildcard si se pidió así), Issuer `CN = R10` o `CN = R11`, y Not After 90 días en el futuro — `capitulo-03-parte-b.md:42-44`.

Renovación automática (`capitulo-03-parte-b.md:48-57`): "El timer corre dos veces al día; solo renueva si faltan <30 días".

```bash
sudo systemctl enable --now certbot.timer
sudo systemctl list-timers certbot.timer
```

```bash
sudo certbot renew --dry-run
```

Hook post-renew, `/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh` (`capitulo-03-parte-b.md:63-74`):

```bash
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
```

```bash
sudo chmod +x /etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh
```
— `capitulo-03-parte-b.md:76-78`.

### 2.4 Wildcard con DNS-01 y `certbot-dns-cloudflare`

`capitulo-03-parte-b.md:82`: si se necesita `*.dominio.tld`, el challenge debe ser DNS-01. Comandos y permisos del token (`capitulo-03-parte-b.md:84-97`):

```bash
sudo snap install certbot-dns-cloudflare
# Crear un token de API de Cloudflare con permisos DNS edit
echo "dns_cloudflare_api_token = YOUR_CLOUDFLARE_API_TOKEN" | \
    sudo tee /etc/letsencrypt/cloudflare.ini
sudo chmod 600 /etc/letsencrypt/cloudflare.ini

sudo certbot certonly \
    --dns-cloudflare \
    --dns-cloudflare-credentials /etc/letsencrypt/cloudflare.ini \
    --dns-cloudflare-propagation-seconds 20 \
    -d "*.dominio.tld" \
    -d "dominio.tld"
```

Permisos del token: únicamente "token de API de Cloudflare con permisos DNS edit" (`capitulo-03-parte-b.md:86`); permisos de archivo `chmod 600` (`:89`). El alcance del token por zona, el tipo exacto de permiso (Zone → DNS → Edit) y la rotación **no constan**. Justificación: HTTP-01 sólo cubre un FQDN exacto y no funciona para wildcard; DNS-01 crea un `_acme-challenge` TXT — `capitulo-03-parte-b.md:99`.

---

## 3. Cabeceras de seguridad

### 3.1 `security-headers.conf` COMPLETO y literal

`/etc/nginx/conf.d/security-headers.conf` — `capitulo-03-parte-b.md:167-202`:

```nginx
# ================ Clickjacking ================
add_header X-Frame-Options "DENY" always;

# ================ MIME sniffing ================
add_header X-Content-Type-Options "nosniff" always;

# ================ Referrer leakage ================
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# ================ APIs y permisos del navegador ================
# Bloquea cámara, micrófono, geolocalización, pago, USB, etc.
add_header Permissions-Policy "accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()" always;

# ================ Content-Security-Policy ================
# API Laravel: solo JSON. Sin scripts, sin iframes, sin objetos.
# (Ajustar según server block; aquí va la versión API)
add_header Content-Security-Policy "default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'" always;

# ================ Cross-Origin isolation ================
add_header Cross-Origin-Opener-Policy "same-origin" always;
add_header Cross-Origin-Resource-Policy "same-origin" always;
# Cross-Origin-Embedder-Policy requiere que TODOS los recursos sean
# CORS-compatibles. Habilitar solo si el frontend lo soporta.
# add_header Cross-Origin-Embedder-Policy "require-corp" always;

# ================ XSS Protection (deprecated pero inofensivo) ================
# X-XSS-Protection está deprecado por OWASP; los navegadores modernos lo ignoran
# y Chrome lo elimina por completo. Lo dejamos en 0 por compatibilidad defensiva.
add_header X-XSS-Protection "0" always;

# ================ Eliminación de Server header ================
server_tokens off;
more_clear_headers "Server";        # requiere headers-more-nginx-module
more_clear_headers "X-Powered-By";  # PHP delata la versión si no se quita
```

Cada cabecera con su valor exacto queda en la tabla siguiente:

| Cabecera | Valor literal | Cita |
|---|---|---|
| `X-Frame-Options` | `DENY` | `capitulo-03-parte-b.md:169` |
| `X-Content-Type-Options` | `nosniff` | `capitulo-03-parte-b.md:172` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | `capitulo-03-parte-b.md:175` |
| `Permissions-Policy` | `accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=(), interest-cohort=()` | `capitulo-03-parte-b.md:179` |
| `Content-Security-Policy` (API) | `default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'` | `capitulo-03-parte-b.md:184` |
| `Cross-Origin-Opener-Policy` | `same-origin` | `capitulo-03-parte-b.md:187` |
| `Cross-Origin-Resource-Policy` | `same-origin` | `capitulo-03-parte-b.md:188` |
| `Cross-Origin-Embedder-Policy` | **comentada**: `require-corp` | `capitulo-03-parte-b.md:191` |
| `X-XSS-Protection` | `0` | `capitulo-03-parte-b.md:196` |

### 3.2 HSTS: `max-age`, `includeSubDomains`, `preload`

Valor exacto: `max-age=63072000; includeSubDomains; preload` — incluye **ambos** modificadores, con `always`, definido dentro de `ssl-params.conf` (`capitulo-03-parte-b.md:152`). Comentario asociado: "max-age de 2 años, subdominios incluidos, apto para hstspreload.org" (`capitulo-03-parte-b.md:151`). No hay ningún otro `max-age` de HSTS en los dos archivos. Nota: el resumen final lo describe como "HSTS 2 años" (`capitulo-03-parte-b.md:480`).

### 3.3 CSP completa directiva por directiva y uso de nonce

- **API (Laravel, JSON):** `default-src 'none'`; `frame-ancestors 'none'`; `base-uri 'none'`; `form-action 'none'` — sin scripts, sin iframes, sin objetos (`capitulo-03-parte-b.md:182-184`). **Sin nonce.** Justificación: para un endpoint JSON que sólo responde a `fetch`, el CSP debe ser muy restrictivo, aunque el navegador no aplique estas directivas a una respuesta que no es documento, "las herramientas de auditoría las miden igual" — `capitulo-03-parte-b.md:204`.
- **Frontend Vue 3:** [A] "se construye con **nonce por respuesta**, no con `'unsafe-inline'`. El backend Laravel genera un nonce en cada `index.html` y nginx lo inserta vía `sub_filter` o el propio Laravel lo inyecta en el template." — `capitulo-03-parte-b.md:206`. Ejemplo mostrado (como cabecera suelta, sin `add_header`) — `capitulo-03-parte-b.md:208-219`:

```
Content-Security-Policy: default-src 'self';
    script-src 'self' 'nonce-{NONCE}';
    style-src 'self' 'nonce-{NONCE}';
    img-src 'self' data: https://api.dominio.tld;
    font-src 'self' data:;
    connect-src 'self' https://api.dominio.tld;
    frame-ancestors 'none';
    base-uri 'none';
    form-action 'self';
    object-src 'none';
```

No consta ningún `add_header`/`sub_filter` concreto para esa CSP del frontend, ni el generador del nonce, ni cómo se garantiza que coincida con el `index.html` servido (que es estático, `capitulo-03-parte-a.md:291`).

### 3.4 Notas sobre COOP/CORP, Permissions-Policy, X-XSS-Protection

- COOP `same-origin` y CORP `same-origin` activos; COEP `require-corp` comentada porque "requiere que TODOS los recursos sean CORS-compatibles. Habilitar solo si el frontend lo soporta." — `capitulo-03-parte-b.md:189-191`.
- Permissions-Policy: "Bloquea cámara, micrófono, geolocalización, pago, USB, etc." — `capitulo-03-parte-b.md:178`.
- X-XSS-Protection: "está deprecado por OWASP; los navegadores modernos lo ignoran y Chrome lo elimina por completo. Lo dejamos en 0 por compatibilidad defensiva." — `capitulo-03-parte-b.md:194-196`.
- `more_clear_headers "Server"` y `"X-Powered-By"` requieren headers-more-nginx-module — `capitulo-03-parte-b.md:200-201`. Adicionalmente, en el server block API aparece `more_clear_input_headers "X-Powered-By"` con la misma advertencia — `capitulo-03-parte-a.md:206`.

### 3.5 Rate limiting (contexto necesario para las cabeceras/estáticos)

`/etc/nginx/conf.d/rate-limit.conf` — `capitulo-03-parte-b.md:227-238`:

```nginx
# ================ API rate limit ================
# 10 req/s por IP, con ráfaga de 20; nodelay = no encola
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

# ================ Frontend rate limit (más permisivo) ================
limit_req_zone $binary_remote_addr zone=app:10m rate=30r/s;

# ================ Conexiones concurrentes por IP ================
limit_conn_zone $binary_remote_addr zone=conn:10m;
limit_conn conn 50;
```

Aplicación en server block (declarada como "ya mostrada en cap-03-parte-a") — `capitulo-03-parte-b.md:242-246`:

```nginx
limit_req zone=api burst=20 nodelay;
limit_req_status 429;
limit_conn conn 50;
```

Justificación [A]: 10 req/s con ráfaga de 20 "es suficiente para una SPA interactiva... sin permitir scraping"; `limit_conn 50` evita 50+ conexiones TCP simultáneas — `capitulo-03-parte-b.md:250`.

---

## 4. Cloudflare

### 4.1 Modo SSL/TLS: Full (Strict)

Dashboard de Cloudflare → **SSL/TLS → Overview** → Modo: **Full (Strict)** — `capitulo-03-parte-b.md:298-300`. Única configuración indicada. Justificación [F] de no usar Flexible: "el modo Flexible hace TLS entre el cliente y Cloudflare, pero la conexión entre Cloudflare y tu origin va en HTTP plano" — `capitulo-03-parte-b.md:302`. El diagrama de arquitectura lo etiqueta como "(Full Strict + Authenticated Origin Pulls)" — `capitulo-03-parte-a.md:12`.

**No constan** otras opciones de zona (Always Use HTTPS, Minimum TLS Version, TLS 1.3 toggle, Opportunistic Encryption, HSTS de Cloudflare, Universal SSL, proxy naranja vs gris).

### 4.2 Authenticated Origin Pulls (mTLS)

- Activación: Dashboard → **SSL/TLS → Origin Server → Authenticated Origin Pulls → ON** — `capitulo-03-parte-b.md:306`.
- Efecto: "cada conexión desde Cloudflare incluye un certificado client firmado por la CA de Cloudflare. Un atacante que conozca tu IP y bypass-e CF NO puede conectar porque no tiene el cert client" — `capitulo-03-parte-b.md:308`.
- Descarga del certificado (`capitulo-03-parte-b.md:312-316`):

```bash
sudo mkdir -p /etc/nginx/certs
sudo curl -o /etc/nginx/certs/cloudflare-authenticated-origin-pull.ca.pem \
    https://developers.cloudflare.com/ssl/static/authenticated_origin_pull_ca.pem
```

- "Y ya está incluido en los server blocks de la parte A (`ssl_client_certificate` y `ssl_verify_client on`)" — `capitulo-03-parte-b.md:318`. Efectivamente: `capitulo-03-parte-a.md:189-190` (API) y `:283-284` (frontend), ambos con la ruta `/etc/nginx/certs/cloudflare-authenticated-origin-pull.ca.pem`.
- Verificación del lado TLS en el diagrama: `N->>N: ssl_verify_client on<br/>verifica contra cloudflare-authenticated-origin-pull.ca.pem` — `capitulo-03-parte-b.md:362`.
- AOP por hostname con certificado propio (mTLS por hostname) **no consta**; el documento usa el AOP de zona con la CA compartida de Cloudflare.

### 4.3 WAF, Bot Fight Mode / Bot Management, Rate Limiting L7

WAF — Dashboard → **Security → WAF** (`capitulo-03-parte-b.md:322-328`):
- Managed Rules: **Cloudflare Managed Ruleset** ON.
- OWASP ModSecurity Core Rule Set: **ON**.
- Rate Limiting Rules, regla custom:
  - **If:** `http.request.uri.path eq "/api/v1/auth/*"`
  - **Then:** Block cuando **>5 requests / 1 minuto por IP**

Ese 5 req/min por IP es **el único umbral numérico** de Cloudflare en los dos capítulos.

Bot Fight Mode (`capitulo-03-parte-b.md:330-334`): Dashboard → **Security → Bots → Bot Fight Mode** o **Super Bot Fight Mode (plan Pro+)**. "Para una API JSON, 'Super Bot Fight Mode' con **Definitely Automated = Block** es la postura correcta. Para el frontend... si necesitas indexar con Googlebot, usa 'Verified Bots = Allow'". No constan umbrales de bot score ni acciones por categoría.

### 4.4 Page Rules / Cache Rules para estáticos

Dashboard → **Rules → Page Rules** (`capitulo-03-parte-b.md:338-342`):

1. `app.dominio.tld/assets/*` → Cache Level: Cache Everything, Edge TTL: 1 year
2. `app.dominio.tld/*` (sin assets) → Cache Level: Standard
3. `api.dominio.tld/*` → Cache Level: Bypass (es API dinámica)

No consta ninguna "Cache Rule" (el capítulo usa Page Rules). El lado nginx del cacheo de estáticos: `expires 365d` + `Cache-Control "public, max-age=31536000, immutable"` (`capitulo-03-parte-a.md:305`) e `index.html` con `no-cache, no-store, must-revalidate` (`capitulo-03-parte-a.md:312`).

### 4.5 Restricción del origen a los rangos de Cloudflare

Mecanismo declarado: **"combinar con nftables (Capítulo 2) que solo acepta TCP/443 desde estos mismos rangos. Si no, un atacante puede saltarse Cloudflare y llegar al droplet con `X-Forwarded-For` falsificado."** — `capitulo-03-parte-b.md:290`. La arquitectura lo afirma como "TCP/80, 443 (nftables allow solo desde rangos Cloudflare)" — `capitulo-03-parte-a.md:15`.

La lista de IPs que aparece en estos capítulos sirve para `set_real_ip_from` (no como reglas de firewall); las reglas nftables concretas **no constan** en estos dos archivos (se delegan al Capítulo 2).

Lista literal de rangos, "actualizado 2025-09" — `capitulo-03-parte-b.md:258-288`:

```nginx
# ================ Rangos de Cloudflare (actualizado 2025-09) ================
# Documentación oficial: https://developers.cloudflare.com/fundamentals/setup/update-ip-versions/
# IPv4
set_real_ip_from 173.245.48.0/20;
set_real_ip_from 103.21.244.0/22;
set_real_ip_from 103.22.200.0/22;
set_real_ip_from 103.31.4.0/22;
set_real_ip_from 141.101.64.0/18;
set_real_ip_from 108.162.192.0/18;
set_real_ip_from 190.93.240.0/20;
set_real_ip_from 188.114.96.0/20;
set_real_ip_from 197.234.240.0/22;
set_real_ip_from 198.41.128.0/17;
set_real_ip_from 162.158.0.0/15;
set_real_ip_from 104.16.0.0/13;
set_real_ip_from 104.24.0.0/14;
set_real_ip_from 172.64.0.0/13;
set_real_ip_from 131.0.72.0/22;
# IPv6
set_real_ip_from 2400:cb00::/32;
set_real_ip_from 2606:4700::/32;
set_real_ip_from 2803:f800::/32;
set_real_ip_from 2405:b500::/32;
set_real_ip_from 2405:8100::/32;
set_real_ip_from 2a06:98c0::/29;
set_real_ip_from 2c0f:f248::/32;

# ================ Header que usaremos como IP real ================
real_ip_header CF-Connecting-IP;
```

Son 15 rangos IPv4 y 7 IPv6. `real_ip_recursive` **no consta**. No consta ningún `log_format` con `$http_cf_connecting_ip`.

### 4.6 ModSecurity + OWASP CRS (opcional, incluido en el capítulo)

Instalación y verificación (`capitulo-03-parte-b.md:374-378`):

```bash
sudo apt install -y libnginx-mod-http-modsecurity
# Verificar que el módulo está habilitado
nginx -V 2>&1 | grep -o with-http_modsecurity_module
```

`/etc/nginx/modsecurity/modsecurity.conf` — `capitulo-03-parte-b.md:384-393`:

```
SecRuleEngine On
SecRequestBodyAccess On
SecResponseBodyAccess On
SecResponseBodyLimit 1048576
SecResponseBodyLimitAction ProcessPartial
SecAuditEngine RelevantOnly
SecAuditLog /var/log/nginx/modsec_audit.log
SecAuditLogFormat JSON
```

`/etc/nginx/modsecurity/exclude.conf` — `capitulo-03-parte-b.md:399-411`:

```
# Laravel Sanctum / Fortify suelen disparar falsos positivos
SecRule REQUEST_HEADERS:User-Agent "@contains Insomnia" "id:1001,phase:1,pass,nolog,noauditlog,ctl:ruleEngine=Off"
SecRule REQUEST_HEADERS:User-Agent "@contains Postman" "id:1002,phase:1,pass,nolog,noauditlog,ctl:ruleEngine=Off"

# PHPMyAdmin / shell detection
SecRule ARGS "@contains base64_decode" "id:1010,phase:2,pass,nolog,noauditlog"
SecRule ARGS "@rx <\\?php" "id:1011,phase:2,pass,nolog,noauditlog"

# Desactivar reglas que rompen JSON-API legítimo
SecRule REQUEST_HEADERS:Content-Type "@streq application/vnd.api+json" \
    "id:1020,phase:1,pass,nolog,noauditlog,ctl:ruleEngine=Off"
```

**No consta** ningún `modsecurity on;` ni `modsecurity_rules_file` en los server blocks.

---

## 5. Auditoría

### 5.1 Pruebas automáticas (literal, `capitulo-03-parte-b.md:421-439`)

```bash
# 1. Sintaxis nginx
sudo nginx -t

# 2. Configuración TLS efectiva
openssl s_client -connect api.dominio.tld:443 -tls1_3 -servername api.dominio.tld \
  </dev/null 2>&1 | grep -E "Protocol|Cipher|Verify"

# 3. Headers de seguridad
curl -sI https://api.dominio.tld/ -H "Accept: application/vnd.api+json" | \
  grep -iE "strict-transport|x-frame|content-security|x-content-type|referrer|cross-origin"

# 4. OCSP stapling
openssl s_client -connect api.dominio.tld:443 -status -servername api.dominio.tld \
  </dev/null 2>&1 | grep -A 5 "OCSP Response Status"

# 5. HSTS preload eligibility
curl -sI https://api.dominio.tld/ | grep -i strict-transport-security
```

### 5.2 Pruebas externas y resultados esperados

Tabla literal `capitulo-03-parte-b.md:443-449`:

| Test | URL | Objetivo |
|---|---|---|
| SSL Labs | https://www.ssllabs.com/ssltest/analyze.html?d=api.dominio.tld | **A+** |
| Mozilla Observatory | https://observatory.mozilla.org/analyze/api.dominio.tld | **A+** |
| securityheaders.com | https://securityheaders.com/?q=api.dominio.tld | **A+** |
| HSTS Preload | https://hstspreload.org/?domain=api.dominio.tld | **Eligible** |
| SSL Labs (frontend) | https://www.ssllabs.com/ssltest/analyze.html?d=app.dominio.tld | **A+** |

Son "5 tests externos" según el resumen — `capitulo-03-parte-b.md:485`. Las tres garantías del capítulo son A+ en Qualys SSL Labs, A+ en securityheaders.com y CSP estricta sin `'unsafe-inline'` ni `'unsafe-eval'` — `capitulo-03-parte-a.md:24-26`.

### 5.3 Comprobación final del flujo (literal, `capitulo-03-parte-b.md:453-470`)

```bash
# 1. Visita frontend
curl -sI https://app.dominio.tld/
# Debe devolver 200 con headers de seguridad y Cache-Control correcto

# 2. Visita API
curl -s https://api.dominio.tld/health -H "Accept: application/vnd.api+json"
# Debe devolver JSON:API shape: {"data": {"type":"health","attributes":{"status":"ok"}}}

# 3. Confirma que la IP real se ve
grep CF-Connecting-IP /var/log/nginx/api.dominio.tld.access.log | tail -1
# El log debe mostrar la IP del usuario final, no la IP de Cloudflare

# 4. Intenta conectar directamente al droplet (debería fallar por mTLS)
curl -v --resolve api.dominio.tld:443:<IP_DEL_DROPLET> \
    https://api.dominio.tld/ 2>&1 | grep -E "alert|error|verify"
# Debe fallar con "ssl_client_certificate" o similar
```

---

## 6. Defectos y contradicciones

### A. Contradicciones internas entre las dos partes

1. **Deprecación de HTTP/2 invertida.** `capitulo-03-parte-a.md:248` afirma: "`http2 on` está deprecado en nginx 1.27+; ahora se usa `listen 443 ssl; http2 on;` o `listen 443 ssl http2;`" — la frase se contradice a sí misma (da `http2 on` como deprecado y como la forma correcta). **[verificación externa]** La documentación oficial de `ngx_http_v2_module` dice lo contrario: la directiva `http2 on|off` apareció en **1.25.1**, con contexto `http, server`, y su ejemplo canónico es `listen 443 ssl;` + `http2 on;`; lo deprecado desde 1.25.1 es el parámetro `http2` de `listen` ([nginx.org/en/docs/http/ngx_http_v2_module.html](https://nginx.org/en/docs/http/ngx_http_v2_module.html), [SpinupWP](https://spinupwp.com/doc/deprecated-http2-directive-nginx/)). El enlace que el capítulo cita para justificar su afirmación es precisamente esa página.
2. **Tres descripciones distintas de cómo está configurado HTTP/2.** Los server blocks usan `http2 on;` (`capitulo-03-parte-a.md:176`, `:272`); `capitulo-03-parte-b.md:148` dice "`http2 ya está en listen 443 ssl http2; (server block)`"; y el diagrama de jerarquía describe `server {443 ssl http2;}` (`capitulo-03-parte-a.md:393-394`). Dos de las tres no corresponden al código real.
3. **`security-headers.conf` nunca se incluye.** El fichero se declara en la estructura (`capitulo-03-parte-a.md:127`) y el diagrama Mermaid afirma que cuelga de ambos server blocks (`capitulo-03-parte-a.md:390` `B --> B2`, `:397` `S1 --> B2`, `:401` `S2 --> B2`), pero los `include` reales de los server blocks son sólo `ssl-params.conf`, `cloudflare-realip.conf`, `rate-limit.conf` y el snippet php-fpm (`capitulo-03-parte-a.md:186`, `:197`, `:200`, `:230`, `:281`, `:289`). Consecuencia: **ninguna de las cabeceras de §3.1 llega al cliente**, lo que hace inalcanzable el objetivo A+ de securityheaders.com (`capitulo-03-parte-b.md:447`) y contradice el resumen (`capitulo-03-parte-b.md:481`).
4. **Certbot emite un linaje que el frontend no usa.** `capitulo-03-parte-b.md:24-28` emite **un solo** certificado con `-d api.dominio.tld -d app.dominio.tld` (sin `--cert-name`), lo que produce un único linaje en `/etc/letsencrypt/live/api.dominio.tld/` (así lo documenta el árbol de `capitulo-03-parte-b.md:32-38`), mientras el server block del frontend apunta a `/etc/letsencrypt/live/app.dominio.tld/fullchain.pem` (`capitulo-03-parte-a.md:278-280`). Ese path no se crea nunca con ese comando: nginx fallaría con `cannot load certificate ... No such file or directory`.
5. **Catch-22 de arranque TLS.** Los server blocks referencian certificados de Let's Encrypt que aún no existen (`capitulo-03-parte-a.md:183-185`, `:278-280`) y el orden del capítulo instala nginx (3.1) → escribe los server blocks (3.3/3.4) → recién después ejecuta `certbot --nginx` (3.8.2). Con `ssl_certificate` apuntando a un fichero inexistente, `nginx -t` falla y el plugin nginx de Certbot no puede operar. El capítulo **no muestra ningún paso de bootstrap** (certificado autofirmado, bloque sólo-HTTP, o `certbot certonly --webroot`): **no consta**.
6. **Webroot declarado pero nunca usado ni creado.** Los bloques HTTP usan `location ^~ /.well-known/acme-challenge/ { root /var/www/letsencrypt; ... }` (`capitulo-03-parte-a.md:163-166`, `:261-264`), pero la emisión se hace con `--nginx` (`capitulo-03-parte-b.md:24`), que no usa webroot; y `/var/www/letsencrypt` no se crea en ningún comando. Configuración efectivamente muerta (salvo que se use `--webroot`, que no consta).
7. **CSP del API vs CSP del frontend sobre un mismo archivo.** `security-headers.conf` contiene el CSP de API "muy restrictivo" (`capitulo-03-parte-b.md:182-184`) con el comentario "(Ajustar según server block; aquí va la versión API)" (`capitulo-03-parte-b.md:183`); si se incluyera en el server block del frontend —como el diagrama exige (`capitulo-03-parte-a.md:401`)— el SPA Vue quedaría bloqueado (`default-src 'none'`). El capítulo no define un CSP de frontend como directiva nginx, sólo como cabecera de ejemplo (`capitulo-03-parte-b.md:208-219`), ni explica el mecanismo de selección por sitio.
8. **Quién inyecta el nonce se contradice.** `capitulo-03-parte-b.md:206` dice que "el backend Laravel genera un nonce en cada `index.html` y nginx lo inserta vía `sub_filter`", pero el `index.html` del frontend es un estático servido por nginx desde `/var/www/frontend/dist` (`capitulo-03-parte-a.md:291`, `:311-315`): no consta que Laravel sirva ese HTML. Además la garantía de "CSP estricta sin `'unsafe-inline'`" (`capitulo-03-parte-a.md:26`) queda sin implementación reproducible (el `{NONCE}` del ejemplo es un placeholder literal).
9. **Plan de Cloudflare.** El stack se declara "Cloudflare (plan Free o superior)" (`capitulo-03-parte-a.md:3`), pero la postura recomendada usa **Super Bot Fight Mode "plan Pro+"** (`capitulo-03-parte-b.md:332`) y exige WAF Managed Ruleset + OWASP CRS + reglas de rate limiting custom (`capitulo-03-parte-b.md:324-328`). El capítulo no indica en qué planes están disponibles esas features ni reconcilia la diferencia de plan. **[verificación externa]** los managed rulesets de WAF y las reglas de rate limiting tienen disponibilidad por plan en Cloudflare; los documentos no la declaran.
10. **Terminología AOP.** `capitulo-03-parte-b.md:310` dice "El cert client de CF debe descargarse", pero lo que descarga (`:314-315`) es la **CA** de Authenticated Origin Pulls, y así se nombra el archivo (`...-authenticated-origin-pull.ca.pem`, `capitulo-03-parte-a.md:189`). El origin necesita la CA, no el certificado cliente: el texto induce a error aunque el comando sea el correcto.
11. **Tabla de auditoría vs. modo de despliegue.** Los 5 tests externos (`capitulo-03-parte-b.md:443-449`) apuntan a `api.dominio.tld`/`app.dominio.tld`, que en esta arquitectura están **detrás de Cloudflare** (`capitulo-03-parte-a.md:11-13`). **[verificación externa]** con el registro proxied, SSL Labs/Observatory/securityheaders miden el **edge de Cloudflare**, no la configuración de nginx que el capítulo construye; y si se apunta directo al origen (DNS-only), `ssl_verify_client on` (`capitulo-03-parte-a.md:190`) hace que los escáneres —que no presentan certificado de cliente— no puedan completar el handshake y no emitan nota. En ningún caso el "A+" mide lo que el capítulo afirma. El capítulo no menciona pausar el proxy durante la auditoría: **no consta**.

### B. Configuración que no cargaría / no arrancaría tal como está escrita

12. **`http2 on;` duplicado en cada server block HTTPS** (`capitulo-03-parte-a.md:175-178` y `:271-274`): la directiva `http2` tiene contexto `http, server` y no admite repetición en el mismo contexto — **[verificación externa]** el error real es `nginx: [emerg] "http2" directive is duplicate` ([ejemplo real en nginx 1.26.3](https://wenku.csdn.net/answer/145gypbqq5)), por lo que `sudo nginx -t` del propio capítulo (`capitulo-03-parte-b.md:423`) **falla** con la configuración publicada. Basta declararlo una vez por server block.
13. **`rate-limit.conf` incluido dentro de un `server`** (`capitulo-03-parte-a.md:200`) aunque contiene `limit_req_zone`, `limit_conn_zone` (`capitulo-03-parte-b.md:230`, `:233`, `:236`), directivas válidas **sólo** en contexto `http`: `nginx: [emerg] "limit_req_zone" directive is not allowed here`. Además, según la propia estructura el archivo también se carga desde `include /etc/nginx/conf.d/*.conf` (`capitulo-03-parte-a.md:387`), lo que produciría **definición duplicada de la zona `api`**. Lo correcto es dejar las zonas en `http` y en el server block sólo `limit_req`/`limit_conn`.
14. **`more_clear_input_headers` / `more_clear_headers` sin el módulo instalado.** `capitulo-03-parte-a.md:206`, `:249` y `capitulo-03-parte-b.md:200-201` usan directivas de `headers-more-nginx-module`, pero la instalación del capítulo (nginx.org mainline, `capitulo-03-parte-a.md:40-65`) no las aporta y ningún paso del capítulo compila/instala el módulo (**no consta**): nginx fallaría al arrancar con `unknown directive "more_clear_headers"`. El propio capítulo lo advierte de forma condicional ("si no, quita esa línea", `capitulo-03-parte-a.md:249`), pero la versión publicada del archivo las incluye activas.
15. **ModSecurity incompatible con el pinning e incompleto.** `capitulo-03-parte-b.md:375` instala `libnginx-mod-http-modsecurity`, paquete de Ubuntu construido contra el nginx de Ubuntu; con el pin a nginx.org al 900 (`capitulo-03-parte-a.md:54-58`) el resultado es un módulo binariamente incompatible (o la instalación del nginx de Ubuntu, anulando el pin). Además **no consta ningún `modsecurity on;` / `modsecurity_rules_file`** en los server blocks, así que ModSecurity no quedaría habilitado aunque el módulo cargara. **[verificación externa]** el `grep -o with-http_modsecurity_module` de `capitulo-03-parte-b.md:377` busca un flag de compilación estática que no aparece así en `nginx -V` cuando el módulo es dinámico; conviene verificar con `nginx -V 2>&1 | tr ' ' '\n' | grep -i modsecurity` o el listado de `/usr/lib/nginx/modules/`.
16. **HSTS y cabeceras se pierden en los `location` con `add_header` propio.** `add_header` sólo se hereda "si y sólo si no hay directivas `add_header` en el nivel actual" — **[verificación externa]** (documentación de `ngx_http_headers_module`). El HSTS está en `ssl-params.conf` incluido a nivel de `server` (`capitulo-03-parte-b.md:152`), pero `capitulo-03-parte-a.md:235` (estáticos del API) y `:305`, `:312-314` (`index.html` y assets del frontend) declaran sus propios `add_header` y **pierden HSTS y todas las cabeceras de seguridad** exactamente en el shell del SPA y en los assets. Remedio disponible en la propia versión declarada: `add_header_inherit merge;`, añadido en nginx 1.29.3 — no se usa (**no consta**).
17. **`sites-enabled` no está incluido por el `nginx.conf` del paquete.** El capítulo crea symlinks en `sites-enabled/` (`capitulo-03-parte-a.md:143-146`) y el diagrama afirma `A --> C["include /etc/nginx/sites-enabled/*"]` (`capitulo-03-parte-a.md:388`), pero **no consta** el contenido de `nginx.conf` ni el comando que añade esa línea: el `nginx.conf` que entrega nginx.org sólo incluye `conf.d/*.conf`. Siguiendo los pasos al pie de la letra, los server blocks podrían no cargarse nunca (el `nginx -t` pasaría igual, sin sitios).
18. **Pinning de versión inexistente.** El pin es de **origen** (`Pin: origin nginx.org`, `Pin: release o=nginx`, `Pin-Priority: 900`, `capitulo-03-parte-a.md:55-58`), no de versión, mientras el comentario espera `nginx -v # debe decir nginx/1.29.x` (`capitulo-03-parte-a.md:64`). **[verificación externa]** el repositorio `packages/mainline` sirve siempre la rama mainline vigente: a la fecha del changelog de nginx.org la mainline es **1.31.6** (15 Sep 2026) y 1.29.8 quedó en 07 Apr 2026 ([nginx.org/en/CHANGES](https://nginx.org/en/CHANGES)), de modo que `apt install nginx` hoy no instala 1.29.x. Para fijar una versión hace falta `nginx=1.29.x-1~noble` o `apt-mark hold`, que **no consta**.
19. **Hardening systemd sin permiso de escritura para el PID.** El drop-in usa `ProtectSystem=strict` (`capitulo-03-parte-a.md:82`) con `ReadWritePaths=/var/cache/nginx /var/log/nginx /var/lib/nginx` (`:85`), pero no añade `/run` ni `RuntimeDirectory=`. **[verificación externa]** `ProtectSystem=strict` monta en sólo lectura toda la jerarquía (salvo `/dev`, `/proc`, `/sys`), y el unit de nginx.org escribe `PIDFile=/run/nginx.pid`; sin `ReadWritePaths=/run/nginx.pid` (o `RuntimeDirectory=nginx`) el arranque falla con `open() "/run/nginx.pid" failed (30: Read-only file system)`. Debe verificarse en el host tras aplicar el override, porque el capítulo afirma que el arranque queda bien (`capitulo-03-parte-a.md:108-114`).
20. **`MemoryDenyWriteExecute=yes` vs `pcre_jit`.** El drop-in incluye `MemoryDenyWriteExecute=yes` (`capitulo-03-parte-a.md:95`). **[verificación externa]** si el `nginx.conf` (cuyo contenido **no consta**) habilita `pcre_jit on;` —habitual en builds con PCRE2—, el JIT necesita memoria ejecutable escribible y el worker fallará/abortará. Verificar `grep pcre_jit /etc/nginx/nginx.conf` antes de mantener esa directiva.

### C. Configuración que carga pero degrada/no cumple lo prometido

21. **Zona `app` declarada y nunca usada.** `limit_req_zone ... zone=app:10m rate=30r/s;` (`capitulo-03-parte-b.md:233`) no se aplica en ningún server block: el del frontend sólo incluye ssl-params y cloudflare-realip (`capitulo-03-parte-a.md:281-289`). Configuración muerta.
22. **La mitigación de replay de 0-RTT es inoperante por tres motivos.** (a) Está **comentada** (`capitulo-03-parte-b.md:141-145`), luego no se aplica; (b) usa `proxy_set_header` (`:142`, `:145`) cuando el backend del API se sirve por `fastcgi_pass unix:/run/php/php8.3-fpm.sock` (`capitulo-03-parte-a.md:337`): `proxy_set_header` sólo afecta a peticiones enviadas con `proxy_pass`, así que no tendría efecto alguno sobre PHP-FPM (para eso haría falta `fastcgi_param`); (c) `ssl_early_data on;` (`capitulo-03-parte-b.md:138`) combinado con `ssl_session_tickets off;` (`:129`) deja 0-RTT sin mecanismo de resumption PSK, por lo que la directiva quedaría inerte — y el comentario de `:129` ("forward secrecy perfecto entre sesiones") está en tensión directa con el de `:138`. **[verificación externa]** la resumption de TLS 1.3 es siempre por PSK de ticket de sesión.
23. **Cifrados DHE anunciados sin `ssl_dhparam`.** `ssl_ciphers` incluye `DHE-RSA-AES128-GCM-SHA256` y `DHE-RSA-AES256-GCM-SHA384` (`capitulo-03-parte-b.md:115`) pero `ssl_dhparam` **no consta** en ninguno de los dos archivos. **[verificación externa]** desde nginx 1.11.0 "to use DHE ciphers it is now required to specify parameters using the `ssl_dhparam` directive" ([nginx.org/en/CHANGES](https://nginx.org/en/CHANGES)), de modo que esos dos suites nunca podrán negociarse.
24. **La lista de cifrados no coincide con el perfil que dice seguir.** `capitulo-03-parte-b.md:155` afirma que "la cipher list coincide con el perfil intermediate" de ssl-config.mozilla.org (`:114`), pero la lista publicada omite `DHE-RSA-CHACHA20-POLY1305`, que el perfil intermediate de Mozilla sí incluye. **[verificación externa]** conviene regenerar en [ssl-config.mozilla.org](https://ssl-config.mozilla.org/) y comparar literalmente.
25. **Curvas distintas del perfil citado.** `ssl_ecdh_curve X25519:secp384r1;` (`capitulo-03-parte-b.md:124`) frente al valor del perfil intermediate de Mozilla, que es `X25519:prime256v1:secp384r1`. **[verificación externa]** se omite `prime256v1`, lo que reduce interoperabilidad con clientes restringidos a P-256.
26. **HSTS `preload` sin dominio raíz.** El objetivo "HSTS Preload → Eligible" se fija sobre `api.dominio.tld` (`capitulo-03-parte-b.md:448`), pero `preload` exige que el **dominio raíz** (`dominio.tld`) sirva la cabecera, y en los dos capítulos sólo existen `server_name api.dominio.tld` y `app.dominio.tld`; **no consta** ningún server block para `dominio.tld` ni para `*.dominio.tld`. El certificado wildcard de `capitulo-03-parte-b.md:91-97` (`*.dominio.tld`, `dominio.tld`) generaría el linaje `/etc/letsencrypt/live/dominio.tld/` que **ningún** server block referencia. Tampoco consta la advertencia operativa de que `preload` es difícilmente reversible.
27. **Regla de Rate Limiting de Cloudflare con sintaxis que nunca coincidirá.** "**If:** `http.request.uri.path eq "/api/v1/auth/*"`" (`capitulo-03-parte-b.md:327`): `eq` es comparación exacta; con el asterisco literal la condición no coincide con ninguna ruta real, por lo que la regla nunca dispararía. Debería usarse comparación por prefijo/`matches`. Umbral: `>5 requests / 1 minuto por IP` (`:328`).
28. **Umbrales nginx vs Cloudflare sin reconciliar.** nginx permite 10 req/s con ráfaga 20 (`capitulo-03-parte-b.md:230`, `capitulo-03-parte-a.md:201`) mientras Cloudflare bloquea a >5 req/min en `/api/v1/auth/*` (`capitulo-03-parte-b.md:328`); el capítulo no explica la relación entre ambos ni cuál prevalece en logs/errores (429 vs 403 de Cloudflare).
29. **Page Rule de estáticos sin ruta verificable.** `app.dominio.tld/assets/*` (`capitulo-03-parte-b.md:340`) presupone un prefijo `/assets/` en la salida del build; el capítulo no declara la ruta real de los assets del SPA (sólo `root /var/www/frontend/dist`, `capitulo-03-parte-a.md:291`) y nginx cachea por **extensión en cualquier ruta** (`capitulo-03-parte-a.md:303`). Puede haber desajuste entre el cacheo de borde y el real.
30. **Sin `default_server` en el puerto 80.** Ambos bloques usan `listen 80;` sin `default_server` ni catch-all que devuelva 444/404 (`capitulo-03-parte-a.md:158-159`, `:257-258`). **[verificación externa]** el primer server del conjunto pasa a ser el default, por lo que cualquier `Host` desconocido dirigido a la IP recibe el 301 a `https://api.dominio.tld...`; además Cloudflare es entonces la única defensa contra esa enumeración.
31. **`error_page 404/5xx /index.php`** en el bloque API (`capitulo-03-parte-a.md:240-241`): convierte los 404 y los 502/503/504 reales en respuestas generadas por Laravel, oculta el estado del upstream y, si la propia URI `/index.php` vuelve a devolver 404, encadena redirecciones internas. El capítulo no documenta ese efecto colateral.
32. **`try_files $uri =404` desactiva el `PATH_INFO`** que el mismo snippet declara soportar (`capitulo-03-parte-a.md:338`, `:341`): una petición `/index.php/foo` devolverá 404 porque `try_files` comprueba la URI completa. No es un fallo de seguridad (es precisamente la defensa anti-upload-shell), pero la pareja de directivas es contradictoria en su intención declarada.
33. **Cabecera `Server` y `X-Powered-By` no se eliminan.** Sin headers-more (ítem 14), el objetivo de borrar `Server` (`capitulo-03-parte-b.md:200`) no se cumple: `server_tokens off` sólo lo deja como `Server: nginx` (sin versión). El `fastcgi_hide_header X-Powered-By` del snippet (`capitulo-03-parte-a.md:345`) sí actúa para las respuestas FPM.
34. **Verificación de IP real incorrecta.** `grep CF-Connecting-IP /var/log/nginx/api.dominio.tld.access.log` (`capitulo-03-parte-b.md:463`) busca el **nombre de la cabecera** dentro del log; con el `log_format` por defecto el log contiene la IP ya reescrita por `real_ip`, no la cadena `CF-Connecting-IP`, así que el comando no encuentra nada. Haría falta que el log contuviera `$http_cf_connecting_ip`, y **no consta** ningún `log_format` en el capítulo.
35. **Resultado esperado irreal en el test de bypass.** `capitulo-03-parte-b.md:469` espera que el intento directo falle "con `ssl_client_certificate` o similar"; **[verificación externa]** curl nunca imprime el nombre de una directiva nginx: mostraría un alerta TLS (`certificate required`) o el cuerpo `400 No required SSL certificate was sent`. Además, con el firewall del Capítulo 2 (`capitulo-03-parte-b.md:290`) la conexión directa podría ni establecerse, y el capítulo no fija ese criterio.
36. **Verificación del módulo ModSecurity poco fiable** (ver ítem 15) y **`certbot.timer` sobre instalación snap.** El capítulo instala Certbot por snap (`capitulo-03-parte-b.md:14`) y luego habilita `certbot.timer` (`:49`). **[verificación externa]** la distribución snap gestiona la renovación con su propio timer de snapd; la unidad `certbot.timer` existe típicamente en instalaciones por paquete del sistema. El propio `systemctl list-timers certbot.timer` (`:50`) es la única comprobación que ofrece el capítulo, y **no consta** un plan alternativo si la unidad no existe. Verificar en el host.

### D. Resumen de "no consta" que el plan de construcción debe resolver

`ssl_dhparam`; contenido completo de `nginx.conf` (worker/pid/events/include de `sites-enabled`); `include` de `security-headers.conf`; `modsecurity on;` y `modsecurity_rules_file`; CSP del frontend como directiva nginx y mecanismo del nonce; server block y HSTS para el dominio raíz; `real_ip_recursive`; `log_format` con IP real; límites de subida (`client_max_body_size`) y timeouts de cliente; reglas nftables concretas (delegadas al Capítulo 2); plan de Cloudflare requerido por cada feature; configuración de Cloudflare distinta de Full (Strict) + AOP + WAF + Bots + Page Rules; `ssl_reject_handshake`/catch-all default; monitoreo/alertas; y orden de bootstrap de certificados.

---

## 7. Hechos verificados

1. El stack objetivo se declara Ubuntu 24.04 (noble), nginx 1.29.x mainline, OpenSSL 3.0.x, Certbot, Cloudflare (Free o superior), Laravel 13 en `api.dominio.tld` y Vue 3 + TS en `app.dominio.tld` — `capitulo-03-parte-a.md:3`.
2. Se decide instalar nginx desde el repositorio oficial nginx.org y fijar mainline 1.29.x — `capitulo-03-parte-a.md:36`.
3. El repositorio APT se añade como `deb [signed-by=/usr/share/keyrings/nginx-archive-keyring.gpg] http://nginx.org/packages/mainline/ubuntu $(lsb_release -cs) nginx` — `capitulo-03-parte-a.md:49-51`.
4. El pinning APT es `Package: *` / `Pin: origin nginx.org` / `Pin: release o=nginx` / `Pin-Priority: 900` — `capitulo-03-parte-a.md:55-58`.
5. La verificación de versión declarada es `nginx -v # debe decir nginx/1.29.x` — `capitulo-03-parte-a.md:64`.
6. El drop-in systemd añade 18 directivas endurzadas, entre ellas `ProtectSystem=strict`, `PrivateTmp=yes`, `NoNewPrivileges=yes`, `MemoryDenyWriteExecute=yes` y `RestrictAddressFamilies=AF_UNIX AF_INET AF_INET6` — `capitulo-03-parte-a.md:79-102`.
7. Las capacidades se limitan a `CapabilityBoundingSet=CAP_NET_BIND_SERVICE` y `AmbientCapabilities=CAP_NET_BIND_SERVICE` — `capitulo-03-parte-a.md:97-98`.
8. La verificación del hardening esperada es que `systemctl show nginx` contenga `ProtectSystem=strict`, `NoNewPrivileges=yes` y `CapabilityBoundingSet=CAP_NET_BIND_SERVICE` — `capitulo-03-parte-a.md:111-114`.
9. La estructura de directorios usa `conf.d/` para globales, `snippets/` para reutilizables y `sites-available/` + `sites-enabled/` con symlinks — `capitulo-03-parte-a.md:122-138`.
10. El bloque HTTPS del API declara `listen 443 ssl;` seguido de `http2 on;` para IPv4 y lo repite para IPv6 — `capitulo-03-parte-a.md:175-178`.
11. El API activa mTLS con `ssl_client_certificate /etc/nginx/certs/cloudflare-authenticated-origin-pull.ca.pem;` y `ssl_verify_client on;` — `capitulo-03-parte-a.md:189-190`.
12. El API aplica `limit_req zone=api burst=20 nodelay;` y `limit_req_status 429;` — `capitulo-03-parte-a.md:201-202`.
13. El frontend sirve desde `root /var/www/frontend/dist;` con `index index.html;` y fallback SPA `try_files $uri $uri/ /index.html;` — `capitulo-03-parte-a.md:291-300`.
14. Los assets del frontend se cachean con `expires 365d;` y `Cache-Control "public, max-age=31536000, immutable"` — `capitulo-03-parte-a.md:305`.
15. `index.html` se sirve con `Cache-Control "no-cache, no-store, must-revalidate"`, `Pragma "no-cache"` y `Expires "0"` — `capitulo-03-parte-a.md:312-314`.
16. El snippet PHP-FPM usa el socket `unix:/run/php/php8.3-fpm.sock` y `try_files $uri =404;` como defensa anti-upload-shell — `capitulo-03-parte-a.md:336-337`, justificada en `:356`.
17. Los timeouts FastCGI anti-Slowloris son `connect 5s`, `send 30s`, `read 30s`, con buffers `16 16k` y `fastcgi_buffer_size 32k` — `capitulo-03-parte-a.md:348-352`.
18. `ssl_protocols TLSv1.2 TLSv1.3;` con la justificación del RFC 8996 — `capitulo-03-parte-b.md:110`, `:157`.
19. La suite TLS 1.2 sigue el perfil Mozilla intermediate con 8 cifrados declarados — `capitulo-03-parte-b.md:113-115`.
20. Los ciphers TLS 1.3 se fijan con `ssl_conf_command Ciphersuites TLS_AES_128_GCM_SHA256:TLS_AES_256_GCM_SHA384:TLS_CHACHA20_POLY1305_SHA256;` — `capitulo-03-parte-b.md:118`.
21. `ssl_ecdh_curve X25519:secp384r1;` y `ssl_session_cache shared:SSL:10m;` con `ssl_session_timeout 1d;` — `capitulo-03-parte-b.md:124`, `:127-128`.
22. `ssl_session_tickets off;` justificado por forward secrecy; `ssl_stapling on;` + `ssl_stapling_verify on;` con `resolver 1.1.1.1 8.8.8.8 valid=300s;` — `capitulo-03-parte-b.md:129-135`.
23. `ssl_early_data on;` está activo y su mitigación anti-replay está comentada — `capitulo-03-parte-b.md:138-145`.
24. HSTS se define una sola vez: `add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;` — `capitulo-03-parte-b.md:152`.
25. Las cabeceras de seguridad exactas son: `X-Frame-Options "DENY"`, `X-Content-Type-Options "nosniff"`, `Referrer-Policy "strict-origin-when-cross-origin"`, `X-XSS-Protection "0"` — `capitulo-03-parte-b.md:169-196`.
26. El CSP del API es `default-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'none'` — `capitulo-03-parte-b.md:184`; el del frontend se describe con nonce por respuesta, sin `'unsafe-inline'` — `capitulo-03-parte-b.md:206`.
27. COOP y CORP quedan en `same-origin`; COEP `require-corp` queda comentada — `capitulo-03-parte-b.md:187-191`.
28. Las zonas de rate limit son `api:10m rate=10r/s` y `app:10m rate=30r/s`, más `limit_conn conn 50` — `capitulo-03-parte-b.md:230-237`.
29. Certbot se instala por snap con `sudo snap install --classic certbot` y symlink a `/usr/bin/certbot` — `capitulo-03-parte-b.md:14-15`.
30. La emisión es `sudo certbot --nginx -d api.dominio.tld -d app.dominio.tld --no-redirect` — `capitulo-03-parte-b.md:24-27`.
31. La renovación se documenta como `certbot.timer` (dos veces al día, renueva si faltan <30 días) y `certbot renew --dry-run` — `capitulo-03-parte-b.md:49-56`.
32. El hook deploy ejecuta `nginx -t` y `systemctl reload nginx` sólo si `$RENEWED_LINEAGE` está definido — `capitulo-03-parte-b.md:63-74`.
33. El wildcard usa `certbot certonly --dns-cloudflare` con `--dns-cloudflare-propagation-seconds 20`, credenciales en `/etc/letsencrypt/cloudflare.ini` con `chmod 600`, token "con permisos DNS edit" — `capitulo-03-parte-b.md:85-96`.
34. Los rangos de Cloudflare listados son 15 IPv4 y 7 IPv6, con `real_ip_header CF-Connecting-IP;` — `capitulo-03-parte-b.md:262-287`.
35. La restricción del origen se delega a nftables del Capítulo 2, aceptando sólo desde esos rangos — `capitulo-03-parte-b.md:290`.
36. Cloudflare se configura en modo **Full (Strict)** desde SSL/TLS → Overview — `capitulo-03-parte-b.md:298-300`.
37. Authenticated Origin Pulls se activa en SSL/TLS → Origin Server y la CA se descarga con `curl` a `/etc/nginx/certs/cloudflare-authenticated-origin-pull.ca.pem` — `capitulo-03-parte-b.md:306-315`.
38. El WAF activa Cloudflare Managed Ruleset y OWASP CRS, más una regla custom que bloquea a >5 req/min por IP en `/api/v1/auth/*` — `capitulo-03-parte-b.md:324-328`.
39. El bot management recomendado es Super Bot Fight Mode (plan Pro+) con Definitely Automated = Block — `capitulo-03-parte-b.md:332-334`.
40. Las Page Rules son: `assets/*` Cache Everything con Edge TTL 1 year; el resto de `app` Standard; `api.dominio.tld/*` Bypass — `capitulo-03-parte-b.md:340-342`.
41. La auditoría incluye `nginx -t`, `openssl s_client -tls1_3`, `curl -sI` con grep de cabeceras, `-status` para OCSP stapling y grep de HSTS — `capitulo-03-parte-b.md:421-439`.
42. Los objetivos medibles son A+ en SSL Labs (api y app), A+ en Mozilla Observatory, A+ en securityheaders.com y Eligible en hstspreload.org — `capitulo-03-parte-b.md:443-449`.
43. La comprobación de flujo incluye `curl` al frontend, `/health` con forma JSON:API `{"data": {"type":"health","attributes":{"status":"ok"}}}`, grep de IP real en el access log y un intento de conexión directa al droplet que debe fallar — `capitulo-03-parte-b.md:453-470`.
44. ModSecurity se instala con `libnginx-mod-http-modsecurity` y se documentan `modsecurity.conf` (con `SecRuleEngine On`, `SecResponseBodyLimit 1048576`) y `exclude.conf` con exclusiones para Laravel Sanctum/Fortify — `capitulo-03-parte-b.md:375-411`.
45. Las tres garantías del capítulo son A+ en SSL Labs, A+ en securityheaders.com y CSP estricta sin `'unsafe-inline'` ni `'unsafe-eval'` — `capitulo-03-parte-a.md:24-26`.

---

### Fuentes externas usadas sólo para validar los defectos (§6)

- [nginx.org — ngx_http_v2_module](https://nginx.org/en/docs/http/ngx_http_v2_module.html): la directiva `http2` apareció en 1.25.1, contexto `http, server`, ejemplo `listen 443 ssl; http2 on;`.
- [nginx.org — CHANGES](https://nginx.org/en/CHANGES): 1.11.0 "to use DHE ciphers it is now required to specify parameters using the `ssl_dhparam` directive"; 1.29.3 añade `add_header_inherit`; mainline vigente 1.31.6 (15 Sep 2026) y 1.29.8 (07 Apr 2026).
- [SpinupWP — Deprecated HTTP/2 Directive in Nginx 1.25.1+](https://spinupwp.com/doc/deprecated-http2-directive-nginx/): lo deprecado desde 1.25.1 es el parámetro `http2` de `listen`.
- [Ejemplo real de `"http2" directive is duplicate` en nginx 1.26.3](https://wenku.csdn.net/answer/145gypbqq5): confirma el fallo de `nginx -t` por `http2 on;` repetido.
