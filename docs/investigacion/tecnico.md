# Informe técnico — Sitio oficial Alcaldía Distrital de Santa Marta

**Objetivo:** https://www.santamarta.gov.co/
**Fecha de consulta:** 2026-09-30 (UTC)
**Método:** `curl 8.18.0` (OpenSSL 3.5.8), `openssl 3.5.8`, `dig 9.18.50`, `python3` para parseo de HTML/CSS, `web_fetch` para documentación externa.
**Evidencia cruda:** todos los volcados están en `investigacion/evidencia/` (57 archivos).
**Regla aplicada:** toda afirmación lleva el comando/URL que la respalda. Lo no comprobado se marca **NO VERIFICADO** / **NO ENCONTRADO**.

---

## 0. Resumen ejecutivo

| Punto | Hallazgo | Severidad |
|---|---|---|
| CMS | **Drupal 7.103** (última versión publicada de D7, 2024-12-04) sobre la distribución **govCMS** (gobierno de Australia) | **Crítica** — D7 en EOL desde 2025-01-05 |
| Exposición de versión | `CHANGELOG.txt` público con la versión exacta (`HTTP 200`, 119.644 bytes) | Media-Alta |
| CDN/WAF | **Cloudflare** (NS + cabeceras `cf-*` + `cdn-cgi/trace`) | — |
| TLS | Grado **B** en Qualys SSL Labs (4 endpoints). HSTS presente. certificado wildcard Google Trust Services | Media |
| Kit UI gov.co | **NO se usa.** Solo un `#36c` aislado en `.header-gov` (equivalente a `#3366CC`), 1 regla CSS | Alta (incumplimiento estándar) |
| Declaración de accesibilidad WCAG | **NO EXISTE.** La ruta `/accesibilidad` es una página de trámites de discapacidad, no una declaración | **Alta** (Ley 1712 art. 9 / Res. MinTIC 1519-2020) |
| Mixed content | **29 recursos** con `http://` en la portada | Media |
| Frameworks JS obsoletos | jQuery 2.2.4, Bootstrap 3.3.7, Font Awesome 4.7.0 (todos EOL) | Media |

---

## 1. Tecnología / stack

### 1.1 Cabeceras HTTP completas

**Comando**
```bash
curl -sSI https://www.santamarta.gov.co/
curl -sS -D- -o /dev/null -L https://www.santamarta.gov.co/
```

**Salida real (`curl -sSI https://www.santamarta.gov.co/`)** — evidencia: `evidencia/head_root.txt`
```
HTTP/2 200
date: Wed, 30 Sep 2026 10:41:20 GMT
content-type: text/html; charset=utf-8
server: cloudflare
strict-transport-security: max-age=31536000; includeSubDomains
x-frame-options: SAMEORIGIN
x-frame-options: SameOrigin
x-content-type-options: nosniff
x-xss-protection: 1; mode=block
referrer-policy: strict-origin-when-cross-origin
x-generator: Drupal 7 (http://drupal.org) + govCMS (http://govcms.gov.au)
x-drupal-cache: HIT
report-to: {"group":"cf-nel","max_age":604800,"endpoints":[{"url":"https://a.nel.cloudflare.com/report/v4?s=NXMuvERL..."}]}
content-language: es
link: <https://www.santamarta.gov.co/>; rel="canonical",<https://www.santamarta.gov.co/>; rel="shortlink",<https://www.santamarta.gov.co/sites/all/themes/bootstrap/favicon.ico>; rel="shortcut icon"
cache-control: public, max-age=3600
last-modified: Wed, 30 Sep 2026 08:46:11 GMT
expires: Sun, 19 Nov 1978 05:00:00 GMT
vary: Accept-Encoding
speculation-rules: "/cdn-cgi/speculation"
nel: {"report_to":"cf-nel","success_fraction":0.0,"max_age":604800}
cf-cache-status: DYNAMIC
cf-ray: a4329d54bd5ff027-MIA
alt-svc: h3=":443"; ma=86400
```

**Lectura cabecera por cabecera**

| Cabecera buscada | Resultado | Evidencia |
|---|---|---|
| `x-generator` | `Drupal 7 (http://drupal.org) + govCMS (http://govcms.gov.au)` | ver arriba |
| `server` | `cloudflare` | ver arriba |
| `x-drupal-cache` | `HIT` en `/`; `MISS` en una URL 404 | `head_root.txt`, `err404` headers |
| `set-cookie` | **AUSENTE** en la portada anónima (`curl -sS -D- -o /dev/null -c /tmp/cj.txt …` → sin `Set-Cookie`) | sesión |
| `via` | **AUSENTE** — coherente con Cloudflare como edge directo, no proxy encadenado | `head_root.txt` |
| `cf-cache-status` | `DYNAMIC` (HTML no se cachea en el edge; sólo se sirve desde caché interna de Drupal) | `head_root.txt` |
| `cf-ray` | `a4329d54bd5ff027-MIA` (PoP Miami) | `head_root.txt` |
| `content-security-policy` | **AUSENTE** | `curl -sS -D- -o /dev/null … \| grep -i content-security-policy` → sin resultados |
| `permissions-policy` | **AUSENTE** | ídem |
| `x-powered-by` | **AUSENTE** (no revela PHP) | ídem |

**Cabecera `link`:** el `rel="shortcut icon"` apunta a `/sites/all/themes/bootstrap/favicon.ico`, es decir el favicon del **tema Bootstrap**.

### 1.2 ¿Drupal 7? ¿govCMS? ¿Qué temas?

**Sí, Drupal 7 + govCMS.** Tres evidencias independientes:

**(a) Meta generator y cabecera**
```bash
grep -o -i '<meta[^>]*generator[^>]*>' home.html
```
```
<meta name="generator" content="Drupal 7 (http://drupal.org) + govCMS (http://govcms.gov.au)" />
```

**(b) Rutas de temas en el HTML**
```bash
grep -oE '/sites/all/themes/[A-Za-z0-9_\-]+' home.html | sort | uniq -c | sort -rn
```
```
      5 /sites/all/themes/bootstrap
```
→ **Un único tema en uso: `bootstrap`** (tema base [Bootstrap para Drupal](https://www.drupal.org/project/bootstrap), versión 7.x-3.3.5 según la ruta `css/3.3.5/overrides.min.css`). **No hay subtema propio**: el sitio personaliza el tema base directamente (`sites/all/themes/bootstrap/img/logoGovCO.png`, `css/hacer.css`, etc.). Esto es un antipatrón de mantenimiento.

**(c) El perfil `govCMS` está realmente instalado** — las rutas del perfil aparecen en el CSS agregado y en `Drupal.settings`:
```bash
cat *.css | grep -o -i -E '/profiles/govcms[A-Za-z0-9_/.-]*' | sort | uniq -c
```
```
      2 /profiles/govcms/modules/contrib/ctools/images/status-active.gif
      1 /profiles/govcms/libraries/superfish/images/shadow.png
      1 /profiles/govcms/libraries/superfish/images/arrows-ffffff-rtl.png
      1 /profiles/govcms/libraries/superfish/images/arrows-ffffff.png
```
Y en el objeto `Drupal.settings` del HTML (`home.html`):
```
profiles/govcms/modules/contrib/spamspan/spamspan.js
profiles/govcms/modules/contrib/toc_filter/toc_filter.js
profiles/govcms/modules/contrib/google_analytics/googleanalytics.js
profiles/govcms/libraries/superfish/jquery.hoverIntent.minified.js
```
Además, el HTML declara metadatos **AGLS** (estándar de gobierno australiano — huella inequívoca de govCMS):
```bash
grep -o -i -E '<link[^>]*agls[^>]*>' home.html
```
```
<link rel="schema.AGLSTERMS" href="https://www.agls.gov.au/agls/terms/" />
```
> **Hallazgo relevante:** el portal de una alcaldía colombiana corre sobre la distribución **govCMS del gobierno de Australia**, no sobre un perfil del Estado colombiano. La documentación de gobierno digital de Colombia ([Kit UI / Biblioteca Digital de Componentes](https://cdn.www.gov.co/v5/)) no tiene relación con govCMS.

**(d) Módulos de terceros identificables** (`/sites/all/modules/`)
```bash
grep -oE '/sites/all/modules/[A-Za-z0-9_\-]+' home.html | sort | uniq -c | sort -rn
```
```
      6 /sites/all/modules/glazed_builder
      3 /sites/all/modules/revslider
      1 /sites/all/modules/jquery_update
```
- `glazed_builder` = page-builder comercial (soho/Glazed Builder).
- `revslider` = **Slider Revolution**, componente comercial con historial de vulnerabilidades.
- `jquery_update` = actualizador de jQuery de Drupal 7.
- Otros visibles en `Drupal.settings`: `views`, `panels`, `ctools`, `media`, `date`, `superfish`, `video_filter`, `toc_filter`, `google_analytics`, `noticias` (módulo propio, `sites/all/modules/noticias/css/styles.css`).

### 1.3 Backend de PHP / versión

**VERSIÓN DE PHP: NO VERIFICADA.** Motivo explícito:

1. No hay cabecera `x-powered-by` (`curl -sS -D- -o /dev/null … | grep -i x-powered-by` → sin resultados).
2. La página 404 de Drupal (`https://www.santamarta.gov.co/no-existe-pagina-xyz-12345`, `HTTP 404`, 73.379 bytes) no contiene cadena `PHP/x.y.z` ni `x-powered-by` (`grep -o -i -E 'x-powered-by|PHP/[0-9.]+' err404.html` → sin coincidencias). Drupal 7 solo revela el error detallado en modo `MAINTENANCE`/debug.
3. `sites/default/settings.php` → **HTTP 500** (el intérprete PHP procesa el archivo pero falla al ejecutarlo, en lugar de servirlo como texto plano). Esto **confirma que el backend es PHP** (ejecuta código en el servidor) pero **no** expone la versión:
```
HTTP/2 500
content-type: text/html; charset=UTF-8
server: cloudflare
```
4. `core/lib/Drupal.php` y `core/CHANGELOG.txt` → **HTTP 404** (no es Drupal 8+, consistente con D7).

**Conclusión parcial verificable:** backend PHP (por el comportamiento de `settings.php` y la 7.x), **versión concreta NO VERIFICADA**. Drupal 7.103 exige mínimo PHP 5.6 y recomienda ≥ 8.x (`probe_INSTALL.txt`, línea 18: *"PHP 5.6 (at least, PHP 8.x or greater recommended)"*).

### 1.4 Versión exacta de Drupal — `CHANGELOG.txt` expuesto

**Comando**
```bash
curl -sS --max-time 25 -o probe_CHANGELOG.txt \
  -w "HTTP=%{http_code} SIZE=%{size_download} CT=%{content_type}\n" \
  https://www.santamarta.gov.co/CHANGELOG.txt
head -1 probe_CHANGELOG.txt
```
**Salida**
```
HTTP=200 SIZE=119644 CT=text/plain
Drupal 7.103, 2024-12-04
------------------------
- So Long, and Thanks for All the Fish

Drupal 7.102, 2024-11-20
------------------------
- Fixed security issues:
   - SA-CORE-2024-005
   - SA-CORE-2024-008
```

**Versión confirmada: Drupal 7.103**, publicada el **2024-12-04**, que es la **última versión de Drupal 7 existente**. Verificación cruzada de disponibilidad de archivos:

| Ruta | HTTP | Interpretación |
|---|---|---|
| `/CHANGELOG.txt` | **200** (119.644 B) | expuesto |
| `/README.txt` | 200 (5.382 B) | expuesto |
| `/INSTALL.txt` | 200 (18.052 B) | expuesto |
| `/robots.txt` | 200 (2.189 B) | expuesto |
| `/core/CHANGELOG.txt` | 404 | — |
| `/core/README.txt` | 404 | — |

**Impacto:** `CHANGELOG.txt` es un vector clásico de fingerprinting: un atacante obtiene la versión de núcleo exacta **sin escanear**, y puede cruzar contra el catálogo público de *Security Advisories* de Drupal. `robots.txt` no lo bloquea:
```bash
grep -i -E 'CHANGELOG|README|INSTALL' probe_robots.txt
```
→ sin coincidencias (no hay `Disallow` para estos archivos).

### 1.5 Drupal 7 está EOL — fecha oficial

**Fuente:** [Drupal 7 End of Life Officially Announced for 5 January 2025 — Drupal Association, 14 ago 2023](https://www.drupal.org/association/blog/drupal-7-end-of-life-officially-announced-for-5-january-2025) (consultada vía `web_fetch`, HTTP 200):

> *"The Drupal project has announced that Drupal 7 will officially reach its End of Life on **5 January 2025**. This date marks the 14-year anniversary since Drupal 7 was released on 5 January 2011. This will be the final extension of support for Drupal 7, meaning that after this date, if your site still runs on Drupal 7 it may become more susceptible to security vulnerabilities if no action is taken."*

También: [PSA-2023-06-07 — End of life announcement and changes to Drupal 7 support](https://www.drupal.org/psa-2023-06-07) y la página [Drupal 7 End of Life](https://www.drupal.org/about/drupal-7/d7eol).

**Situación del sitio al 2026-09-30:** corre **Drupal 7.103**, cuya fecha de publicación es **2024-12-04**, es decir la última entrega del proyecto. Han pasado **~21 meses desde el EOL oficial** (2025-01-05 → 2026-09-30). Desde el 5 de enero de 2025 **el Drupal Security Team no emite SA-CORE para la rama 7.x**; cualquier vulnerabilidad descubierta en el núcleo 7.x queda sin parche de la comunidad (existe soporte extendido comercial vía [Drupal 7 Extended Support](https://www.drupal.org/about/drupal-7/d7eol), sin evidencia de que este sitio lo tenga).

### 1.6 CDN / WAF — Cloudflare (confirmado por 4 vías)

**(1) DNS** — `dig +short NS santamarta.gov.co`
```
roman.ns.cloudflare.com.
ullis.ns.cloudflare.com.
```
`dig +short www.santamarta.gov.co` → `104.21.62.188`, `172.67.138.96` (rangos anycast de Cloudflare).
`dig +short santamarta.gov.co` → los mismos dos IPs (apex proxied vía CNAME flattening).

**(2) Cabeceras** — `server: cloudflare`, `cf-ray: …-MIA`, `cf-cache-status: DYNAMIC`, `report-to: {"group":"cf-nel",…}`, `nel: {"report_to":"cf-nel",…}` (Network Error Logging de Cloudflare), `speculation-rules: "/cdn-cgi/speculation"`.

**(3) `cdn-cgi/trace`** — `curl -sS https://www.santamarta.gov.co/cdn-cgi/trace`
```
fl=981f25
h=www.santamarta.gov.co
ip=181.51.88.15
ts=1790764905.000
visit_scheme=https
uag=curl/8.18.0
colo=MIA
sliver=none
http=http/2
loc=CO
tls=TLSv1.3
sni=plaintext
warp=off
gateway=off
rbi=off
kex=X25519MLKEM768
```
> Nota: el `HEAD` a `/cdn-cgi/trace` devuelve **HTTP 404**, pero el `GET` devuelve 200 con el cuerpo de arriba (el endpoint solo responde a GET). Evidencia: `trace_body.txt`, `trace_headers.txt`.

**(4) `curl --http3`** → `HTTP=200 VERSION=3`, y `alt-svc: h3=":443"; ma=86400` → HTTP/3 habilitado en el edge.

**Extra:** el HTML incluye `/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js` y `/cdn-cgi/challenge-platform/scripts/precursor/main.js` — scripts inyectados por Cloudflare (ofuscación de e-mail y plataforma de retos).

**WAF:** Cloudflare está presente como proxy inverso y CDN. La existencia de un **WAF activo con reglas** es **NO VERIFICADO** (no realicé pruebas intrusivas; no corresponde a un análisis pasivo). El dominio confirmado de gestión es Cloudflare; el plan contratado (Free/Pro/Business) **NO VERIFICADO**.

### 1.7 JavaScript y recursos de terceros en la portada

**Inventario completo** (parseo de etiquetas `link`/`script`/`iframe`/`img` de `home.html`, excluyendo el propio dominio) — evidencia: `evidencia/recursos_externos.txt`:

| Dominio | Recurso | Tipo | Estado HTTP |
|---|---|---|---|
| `cdn.jsdelivr.net` | `bootstrap/3.3.7/css/bootstrap.min.css` | CSS | 200 (121.200 B) |
| `cdn.jsdelivr.net` | `bootstrap/3.3.7/js/bootstrap.min.js` | JS | 200 (37.045 B) |
| `cdn.jsdelivr.net` | `html5shiv/3.7.3/html5shiv-printshiv.min.js` | JS | 200 (4.366 B) |
| `maxcdn.bootstrapcdn.com` | `font-awesome/4.7.0/css/font-awesome.min.css` | CSS | 200 (31.000 B) |
| `ajax.googleapis.com` | `jquery/2.2.4/jquery.min.js` | JS | 200 (85.578 B) |
| `fonts.googleapis.com` | `css?family=Arvo:400\|Roboto:500,900\|Open+Sans:400\|Raleway:400` | CSS | 200 (1.091 B) |
| `cdn.pulse.is` | `livechat/loader.js` (`data-live-chat-id="678171b56a51c08f610ecef5"`) | JS | 200 (3.265 B) |
| `embed.tawk.to` | `5d3736749b94cd38bbe8e143/default` | JS (inyectado) | **HTTP 200** (1.157 B) |
| `embed.tawk.to` | `5aff50b75f7cdf4f0534598f/default` | JS | **HTTP 404**, y además **comentado en el HTML** |
| `www.googletagmanager.com` | `gtag/js?id=G-1T9M1X76QZ` (GA4) | JS | 200 (529.851 B) |
| `www.google-analytics.com` | `analytics.js` (`UA-121052958-1`) | JS (inyectado) | — |
| `connect.facebook.net` | `es_LA/sdk.js#xfbml=1&version=v2.12&appId=158421817688855` | JS (inyectado) | 200 (12.469 B) |
| `cdn.lightwidget.com` | `widgets/49f4bc1238c555358a2b8bb9a6eb94d1.html` (feed Instagram) | iframe | 200 (739 B) |
| `www.w3.org` | `1999/xhtml/vocab` | link (RDFa) | — |
| `www.agls.gov.au` | `agls/terms/` | link (metadato AGLS) | — |

**Verificación de estados** (`curl -sS -o /dev/null -L -w "HTTP=%{http_code} SIZE=%{size_download}\n"`):

**Tawk.to — detalle importante.** El HTML de la portada contiene **dos** bloques de Tawk.to:
- Bloque 1 (widget `5aff50b75f7cdf4f0534598f`): **desactivado** — el cierre es `</script-->` y todo el bloque está dentro de un comentario HTML:
  ```html
  <!--Start of Tawk.to Script-->
  <!--script type="text/javascript">
      ...
      s1.src = 'https://embed.tawk.to/5aff50b75f7cdf4f0534598f/default';
      ...
    })();
  </script-->
  ```
  Coherente con el 404 del endpoint. Es **código muerto** dejado en producción.
- Bloque 2 (widget `5d3736749b94cd38bbe8e143`): **activo**, es el chat realmente cargado. Confirmado con `curl` al endpoint → **HTTP 200**.

**Otros terceros observados con ambos sistemas de analítica a la vez:** GA4 (`G-1T9M1X76QZ`) **y** Universal Analytics (`UA-121052958-1`) en la misma página — UA está descontinuado por Google desde 2023; el `ga()` inyectado ya no envía datos.

**Recursos CARGADOS por JS (no en etiquetas)** — visibles en `home.html`:
```bash
grep -o -E "(https?:)?//[a-z0-9.-]+\.(net|com|to|is|io|co|org)/[^\"' )]*" home.html \
  | grep -v -E 'santamarta|w3\.org|purl\.org|xmlns|ogp\.me|rdfs\.org|schema' \
  | sed -E 's#^(https:)?//##' | awk -F/ '{print $1}' | sort | uniq -c | sort -rn
```
Incluye `embed.tawk.to` (2), `cdn.pulse.is`, `cdn.lightwidget.com`, `connect.facebook.net`, `www.google-analytics.com`, `www.googletagmanager.com`.

**Fuentes tipográficas propias** (autohospedadas, no del kit gov.co):
```bash
grep -o -i -E '@font-face\{[^}]{0,300}' css_5EVHxQAVSp7HU2JYp1CzPiRDra3iiKNmwl71J1foG1E.css | grep -o -i -E "font-family:[^;]*" | sort -u
```
```
font-family:'alcaldia'
font-family:'fontello'
font-family:'hacer'
font-family:'icomoon'
```
Y en `css/3.3.5/overrides.min.css` / `_zPAqx…css` aparecen `'Pluto'`, `'Pe-icon-7-stroke'`, `revicons`, `FontAwesome`. Fuente base del cuerpo: `proxima-nova, "Helvetica Neue", Helvetica, Arial, sans-serif`.

---

## 2. HTTPS y certificado

### 2.1 Certificado

**Comando**
```bash
echo | openssl s_client -connect www.santamarta.gov.co:443 \
  -servername www.santamarta.gov.co 2>/dev/null \
  | openssl x509 -noout -subject -issuer -dates -ext subjectAltName
```
**Salida**
```
subject=CN=santamarta.gov.co
issuer=C=US, O=Google Trust Services, CN=WE1
notBefore=Sep 17 19:20:32 2026 GMT
notAfter=Dec 16 20:20:29 2026 GMT
X509v3 Subject Alternative Name:
    DNS:santamarta.gov.co, DNS:*.santamarta.gov.co
```

| Campo | Valor |
|---|---|
| **Subject** | `CN=santamarta.gov.co` |
| **Emisor** | `C=US, O=Google Trust Services, CN=WE1` (raíz intermedia; raíz `GTS Root R4`) |
| **Válido desde** | 2026-09-17 19:20:32 UTC |
| **Válido hasta** | 2026-12-16 20:20:29 UTC (**~77 días restantes** al 2026-09-30) |
| **SAN** | `santamarta.gov.co`, `*.santamarta.gov.co` |
| **¿Wildcard?** | **SÍ** — `*.santamarta.gov.co` cubre `www.` y cualquier subdominio de un nivel |
| **Algoritmo** | `SHA256withECDSA` (SSL Labs) |
| **CRL** | `http://c.pki.goog/we1/FaKuUrdSOxI.crl` |

Verificación cruzada con `openssl s_client -tls1_2` (cadena): `GTS Root R4` → `WE1` → `santamarta.gov.co`, `verify return:1` en los tres niveles.

> El certificado es **gestionado por Cloudflare con Universal SSL de Google Trust Services**, no por un emisor local ni por el Estado. Es un cert de **renovación automática** (Cloudflare), por lo que la fecha de expiración no implica riesgo operativo — pero es un dato verificable del hardening.

### 2.2 HSTS

**Sí hay HSTS.** Cabecera presente en todas las respuestas de la portada y de la 404:
```
strict-transport-security: max-age=31536000; includeSubDomains
```
Y confirmado por SSL Labs API: `hstsPolicy.status = "present"`, `maxAge = 31536000`, `includeSubDomains = true`.

**Ausencias relevantes:**
- **No** incluye `preload` → no es elegible para la lista de precarga de Chrome.
- SSL Labs confirma que **no está en ninguna lista de precarga**: `hstsPreloads: [{source: 'Chrome', status: 'absent'}, {source: 'Edge', status: 'absent'}, {source: 'Firefox', status: 'absent'}, {source: 'IE', status: 'absent'}]`.
- **No envía `Expect-CT`** (cabecera ausente; hoy obsoleta, se menciona por completitud).

### 2.3 Redirección HTTP → HTTPS

**Comando**
```bash
curl -sSI http://www.santamarta.gov.co/
```
**Salida**
```
HTTP/1.1 301 Moved Permanently
Date: Wed, 30 Sep 2026 10:41:23 GMT
Content-Type: text/html; charset=UTF-8
Connection: keep-alive
Location: https://www.santamarta.gov.co/
Speculation-Rules: "/cdn-cgi/speculation"
Server: cloudflare
CF-RAY: a4329d6c7b33745e-MIA
alt-svc: h3=":443"; ma=86400
```
→ **Redirección 301 correcta** a HTTPS, ejecutada en el edge de Cloudflare ("Always Use HTTPS"). Evidencia: `head_http.txt`.

### 2.4 TLS 1.0 / 1.1 — deshabilitados

**Comandos (locales)**
```bash
echo | openssl s_client -connect www.santamarta.gov.co:443 -tls1 2>&1 | head -5
echo | openssl s_client -connect www.santamarta.gov.co:443 -tls1_1 2>&1 | head -5
```
**Salida local**
```
Connecting to 104.21.62.188
00637A7B3E7F0000:error:0A0000BF:SSL routines:tls_setup_handshake:no protocols available:ssl/statem/statem_lib.c:155:
CONNECTED(00000003)
---
no peer certificate available
```
(Idéntico para `-tls1_1`, contra `172.67.138.96`.)

> ⚠️ **Este resultado local NO es concluyente.** El error `no protocols available` es del **cliente**, no del servidor: OpenSSL 3.5.8 de este sistema tiene compilados **0 cifrados** para TLS 1.0/1.1 (`openssl ciphers -v -s -tls1` → 0 líneas), y en `/etc/pki/tls/openssl.cnf` no se puede bajar el `MinProtocol`. La petición **nunca llegó a negociar con el servidor**. Por lo tanto **NO VERIFICADO por esta vía**.

**Verificación independiente (SSL Labs API v3)** — sí concluyente:
```bash
curl -sS "https://api.ssllabs.com/api/v3/analyze?host=www.santamarta.gov.co&all=done&maxAge=1" \
  -H "email: research@example.org" -o ssllabs_full.json
```
```json
"protocols":[{"id":769,"name":"TLS","version":"1.0"},{"id":770,"name":"TLS","version":"1.1"},
             {"id":771,"name":"TLS","version":"1.2"},{"id":772,"name":"TLS","version":"1.3"}]
```
Los 4 endpoints (2 IPv4 + 2 IPv6) reportan **TLS 1.0, 1.1, 1.2 y 1.3 habilitados**. Complementado con:
```
supportsRc4: False
forwardSecrecy: 4          (>= 128-bit, suites modernas)
supportsAead: True
compressionMethods: 0      (CRIME no aplicable — no comprime)
poodle: False              (TLS)
heartbleed: False
freak / logjam: False
openSslCcs: 1              (no vulnerable)
ticketbleed: 1             (no vulnerable)
bleichenbacher: 1          (no vulnerable)
vulnBeast: (sin hallazgo)
```
El mapeo `sims` muestra `Android 2.3.7` rechazado con `handshake_failure` y clientes modernos aceptados.

**Interpretación honesta:** aunque SSL Labs enumera TLS 1.0/1.1 como soportados, **los clientes locales no pueden negociarlos** (por limitación del cliente, no del servidor). **Recomendación de verificación zanjable:** ejecutar `nmap --script ssl-enum-ciphers` o `testssl.sh` desde una máquina con OpenSSL ≤ 1.1.1 compilado con `enable-weak-ssl-ciphers`, y/o consultar el detalle por protocolo en la página HTML de SSL Labs (`https://www.ssllabs.com/ssltest/analyze.html?d=www.santamarta.gov.co`). **Conclusión: TLS 1.0/1.1 aparentemente HABILITADOS según SSL Labs; deshabilitación NO confirmable con las herramientas locales disponibles.**

### 2.5 Calificación SSL Labs

**URL:** https://www.ssllabs.com/ssltest/analyze.html?d=www.santamarta.gov.co
**Fecha del test:** 2026-09-30 ~10:37–10:40 UTC · **Engine** 2.4.3 · **Criteria** 2009q

**Comando (`curl` directo, sin JavaScript):**
```bash
curl -sS -L --max-time 60 -A 'Mozilla/5.0' \
  "https://www.ssllabs.com/ssltest/analyze.html?d=www.santamarta.gov.co&hideResults=on" \
  -o ssllabs3.html
```
**Salida (texto plano extraído)**
```
SSL Report: www.santamarta.gov.co
Server                              Test time                              Grade
1  172.67.138.96                    Wed, 30 Sep 2026 10:37:52 UTC  B
2  2606:4700:3034:0:0:0:6815:3ebc    Wed, 30 Sep 2026 10:38:53 UTC  B
3  104.21.62.188                    ...                            (en progreso en el volcado)
4  2606:4700:3033:0:0:0:ac43:8a60   ...                            (en progreso en el volcado)
```
**Confirmación vía API v3** (los 4 endpoints, `status: READY`):
```
ip=172.67.138.96                grade=B  gradeTrustIgnored=B  hasWarnings=False
ip=2606:4700:3034:0:0:0:6815:3ebc grade=B gradeTrustIgnored=B  hasWarnings=False
ip=104.21.62.188                grade=B  gradeTrustIgnored=B  hasWarnings=False
ip=2606:4700:3033:0:0:0:ac43:8a60 grade=B gradeTrustIgnored=B  hasWarnings=False
```

> **CALIFICACIÓN SSL LABS: B** (todos los endpoints, IPv4 e IPv6). No hay `hasWarnings` y el grado no se degrada por confianza (`gradeTrustIgnored = B`).
>
> **Causa del B — NO VERIFICADA explícitamente.** La hipótesis más consistente con los datos recogidos es el **soporte de TLS 1.0/1.1** (que en la matriz de grading de SSL Labs limita a B). No obtuve el campo "verdict"/motivo textual que SSL Labs muestra en el HTML renderizado por JavaScript; **no lo afirmo como causa confirmada**.
>
> **Mejoras evidentes y verificables** (`hstsPreloads: absent`, `ocspStapling: False`, `sessionResumption: 1`): añadir `preload` a HSTS, habilitar OCSP stapling y desactivar TLS 1.0/1.1 en el panel de Cloudflare (SSL/TLS → Edge Certificates → Minimum TLS Version = 1.2).

---

## 3. Kit UI de Gobierno Digital (gov.co)

### 3.1 Conclusión

## ❌ **NO, el sitio NO usa el Kit UI oficial del Gobierno Digital de Colombia.**

Evidencia concreta a continuación (todas las búsquedas se ejecutaron sobre `home.html` 215.243 B, los 3 CSS agregados de Drupal y los 6 JS agregados):

### 3.2 Búsqueda de los tokens exigidos por el Kit UI

**Comando**
```bash
for p in 'gov\.co' 'govco' 'kit-ui' 'kit_ui' 'govco-font' 'govco-colors' 'kit_gov_co' '@govco' \
         'Gobierno Digital' 'gobiernoenlinea'; do
  echo "--- patron: $p ---"; grep -o -i -E "$p" home.html | wc -l
done
```
**Salida**
```
--- patron: gov\.co ---            308
--- patron: govco ---                2
--- patron: kit-ui ---               0
--- patron: kit_ui ---               0
--- patron: govco-font ---           0
--- patron: govco-colors ---         0
--- patron: kit_gov_co ---           0
--- patron: @govco ---               0
--- patron: Gobierno Digital ---     0
--- patron: gobiernoenlinea ---      0
```

**Las 2 coincidencias de `govco` NO son el kit — son el logo.** Contexto real:
```html
<a href="https://www.gov.co/home/">
  <img src="/sites/all/themes/bootstrap/img/logoGovCO.png" id="Img959" alt="Logo gov" height="30" weight="140"></a>
...
<a href="https://www.gov.co/home/" target="_blank">
  <img src="/sites/all/themes/bootstrap/img/logoGovCO.png" alt="Logo Gobierno de Colombia" width="130px" class="img-fluid">
</a>
```
→ Un PNG llamado `logoGovCO.png` usado como imagen de cabecera y de pie. **El sitio enlaza a gov.co y muestra su logo, pero no implementa el kit.**

### 3.3 Búsqueda de variables / clases del kit

```bash
# variables CSS del kit
grep -o -E '\-\-govco[a-z0-9-]*' *.css home.html | wc -l          # → 0
# clases CSS .govco-*
grep -o -E '\.govco-[a-z0-9_-]+' *.css | wc -l                    # → 0
# clases govco en el HTML
grep -o -E 'class="[^"]*govco[^"]*"' home.html page_*.html | wc -l # → 0
# fuente govco-font
grep -c -i 'govco-font' home.html css_*.css                        # → 0 en los 4
```
**Resultado: `0` en todas.** El sitio no carga `govco-font`, no usa las clases `.govco-*`, no declara variables `--govco-*` y no referencia ninguna fuente tipográfica oficial.

### 3.4 Paleta — comparación contra el kit oficial

**El Kit UI oficial SÍ está publicado y SÍ lo descargué** para comparar:

```bash
curl -sS -L -o govco_all.css -w "HTTP=%{http_code} SIZE=%{size_download} CT=%{content_type}\n" \
  https://cdn.www.gov.co/layout/v4/all.css
```
```
HTTP=200 SIZE=288052 CT=text/css
```
(Descubierto desde `https://cdn.www.gov.co/kit-ui/` → `<link href="../layout/v4/all.css">`, la **Biblioteca Digital de Componentes de integración v4**, `https://cdn.www.gov.co/v5/`. `https://www.gov.co/kit-ui/` redirige a la SPA del portal.)

**Tokens del kit oficial (`govco_all.css`, 288.052 B, 16.666 líneas):**

```bash
for c in '#004884' '#3366cc' '#36c' '#f42f63' '#ff6c00' '#0b457f' '#3772ff'; do
  printf "%-12s : " "$c"; grep -o -i -F "$c" govco_all.css | wc -l
done
```
```
#004884      : 113
#3366cc      : 147
#36c         : 0
#f42f63      : 2
#ff6c00      : 4
#0b457f      : 6
#3772ff      : 2
```
Top colores del kit: `#3366cc` (138), `#ffffff` (119), **`#004884` (113)**, `#737373`, `#4b4b4b`, `#e6effd`, `#a80521`, `#068460`.

```bash
grep -o -i -E 'font-family:[^;}]{0,100}' govco_all.css | sort | uniq -c | sort -rn | head
grep -o -E '\.govco-[a-z0-9_-]+' govco_all.css | sort -u | wc -l
```
```
     57 font-family: "govco-font"
     41 font-family: WorkSans-Regular
     20 font-family: WorkSans-Medium
     16 font-family: Montserrat-SemiBold
      7 font-family: 'Works sans', sans-serif !important
      5 font-family: "govco-fontv2"
→ 1677 clases .govco-* únicas
```

**Tipografía exigida por el kit:** `govco-font` (fuente propietaria del Estado, autohospedada vía `cdn.www.gov.co`) + familia **Work Sans** (`WorkSans-Regular/Medium/SemiBold/Bold`) + **Montserrat** para títulos.

**Paleta institucional:**

| Token | Valor en el kit oficial | Uso en santamarta.gov.co |
|---|---|---|
| Azul institucional principal | **`#004884`** (113 usos en `all.css`) | **0 usos** |
| Azul secundario | **`#3366CC`** (147 usos, `#3366cc` = forma canónica) | **1 uso**, como abreviatura `#36c` |
| Rojo cardenal | `#F42F63` (2 usos) | **0 usos** |
| Naranja | `#FF6C00` (4 usos) | **0 usos** |
| Fuente | `govco-font` + Work Sans + Montserrat | `'alcaldia'`, `'hacer'`, `'Pluto'`, `proxima-nova`, Google Fonts (Arvo/Roboto/Open Sans/Raleway) |

### 3.5 El caso del `#36c` en `.header-gov` — análisis solicitado

**Confirmado:** la portada tiene
```html
<div class="header-gov" style="background-color: #36c;">
```
Y el origen está en un CSS del tema:
```bash
curl -sS -o theme/css_hacer.css \
  https://www.santamarta.gov.co/sites/all/themes/bootstrap/css/hacer.css
grep -n 'header-gov' theme/css_hacer.css
```
→ Archivo `hacer.css`, **36 líneas**, donde la ÚNICA referencia a gov.co es la última regla:
```css
/* hacer.css, líneas 1-32: definición de la fuente de iconos 'hacer' */

.header-gov {
    background-color: #36c;      /* línea 35 */
}
```

De 36 líneas del archivo, 32 son un `@font-face` de iconos propio del tema; la regla institucional es **1 línea pegada al final**.

**¿Coincide `#36c` con el azul institucional?** **SÍ, es el mismo color** — `#36c` es la notación corta de 3 dígitos equivalente a `#3366CC`, que es exactamente el azul secundario oficial del Kit UI (147 usos en `govco_all.css`). Por eso la comparación exacta de cadenas `grep -F '#3366cc' css_*.css` da **0** en el sitio: **está escrito en forma abreviada**.

**PERO** — y esto es lo determinante — `#36c` no es el azul **principal** del kit (`#004884`, 113 usos), y sobre todo:

**Contraste calculado** (`WCAG 2.x`, luminancia relativa):
```
#3366CC vs #FFFFFF = 5.37 : 1   → cumple AA para texto normal (≥4.5) y AA para texto grande
#004884 vs #FFFFFF = 9.29 : 1   → cumple AAA
#F42F63 vs #FFFFFF = 3.86 : 1   → NO cumple AA para texto normal
```
→ El `#36c` **no es un problema de contraste** (5.37:1 supera AA), pero **la paleta completa del sitio no es la institucional**.

**Paleta realmente dominante del sitio** (no del kit):
```bash
cat css_5EVHxQAVSp7HU2JYp1CzPiRDra3iiKNmwl71J1foG1E.css \
    css_zPAqxQZMrTa9ldNGJyaDW9YA0ajFJ_Q1JB4dFMAC70E.css \
  | grep -o -i -E '#[0-9a-f]{6}\b|#[0-9a-f]{3}\b' | tr 'A-F' 'a-f' \
  | sort | uniq -c | sort -rn | head
```
```
     66 #0d6fa5     ← azul propio del tema, NO institucional (#004884 es #9.29:1)
     60 #fff
     55 #333
     52 #000
     34 #ddd
     14 #009bdb
     12 #4c4e96
     10 #eee
     10 #c6d241
```
Y en `theme/css_style.css` (82.098 B): **`#0d6fa5` × 72**, `#333` × 20, `#fff` × 16, `#4c4e96` × 12. **Cero apariciones de `#004884` o `#3366cc` en los 3 CSS agregados y en los 7 CSS del tema** (verificado con `grep -c -i '3366cc' css_*.css theme/*` → `0` en todos):
```bash
for f in theme/*; do printf "%s -> #3366cc:%s  #36c:%s\n" "$f" \
  "$(grep -c -i '3366cc' $f)" "$(grep -o -i -E '#36c[^0-9a-f]' $f | wc -l)"; done
```
```
theme/css_3.3.5_overrides.min.css -> #3366cc:0  #36c:0
theme/css_custom-icons.css        -> #3366cc:0  #36c:0
theme/css_fontello.css            -> #3366cc:0  #36c:0
theme/css_fonts_pluto_pluto-fonts.css -> #3366cc:0  #36c:0
theme/css_hacer.css               -> #3366cc:0  #36c:1     ← único
theme/css_newfont.css             -> #3366cc:0  #36c:0
theme/css_style.css               -> #3366cc:0  #36c:0
```

### 3.6 Veredicto del Kit UI

| Criterio | Kit oficial exige | Sitio cumple |
|---|---|---|
| Hoja de estilos del kit (`layout/v4/all.css` o equivalente) | Sí | ❌ **NO** |
| Fuente `govco-font` | Sí | ❌ **NO** (usa `alcaldia`/`hacer`/`Pluto`/Google Fonts) |
| Clases `.govco-*` en el marcado | Sí | ❌ **NO** (0 ocurrencias) |
| Variables `--govco-*` | (patrón del kit) | ❌ **NO** (0 ocurrencias) |
| Azul institucional `#004884` | Sí | ❌ **NO** (0 usos; el tema usa `#0d6fa5`) |
| Azul secundario `#3366CC` | Sí | ⚠️ **Parcial** — 1 sola regla, escrita `#36c`, aislada en `header.css`/`hacer.css` |
| Tipografía oficial (Work Sans / Montserrat) | Sí | ❌ **NO** |
| Componentes Web gov.co (`govco-collection-webcomponents`) | Sí | ❌ **NO** (no referenciado) |

**Diagnóstico:** el sitio **imitó visualmente** el azul institucional (`#36c` = `#3366CC`) en un solo contenedor (`.header-gov`) **sin adoptar el Kit UI** en ningún otro aspecto: ni la fuente, ni los componentes, ni el azul principal `#004884`, ni las variables/clases del sistema de diseño. Es **cumplimiento aparente, no real** — y un observador externo que compare ambos sitios verá paletas y tipografías distintas.

---

## 4. Declaración de accesibilidad / WCAG

### 4.1 Conclusión

## ❌ **NO EXISTE una declaración de accesibilidad ni de conformidad WCAG en el sitio.** Reportado como ausencia confirmada.

### 4.2 Rutas probadas

```bash
for p in accesibilidad accesibilidad/ declaracion-de-accesibilidad politica-de-privacidad \
         politicas/politica-de-privacidad terminos-de-uso terminos-y-condiciones \
         transparencia atencion-al-ciudadano; do
  printf "%-38s -> " "$p"
  curl -sS -o /dev/null --max-time 30 -L \
    -w "HTTP=%{http_code} SIZE=%{size_download} FINAL=%{url_effective}\n" \
    "https://www.santamarta.gov.co/$p"
done
```
**Salida**
```
accesibilidad                          -> HTTP=200 SIZE=77857  FINAL=https://www.santamarta.gov.co/accesibilidad
accesibilidad/                         -> HTTP=200 SIZE=77857  FINAL=https://www.santamarta.gov.co/accesibilidad
declaracion-de-accesibilidad           -> HTTP=404 SIZE=73383  FINAL=https://www.santamarta.gov.co/declaracion-de-accesibilidad
politica-de-privacidad                 -> HTTP=200 SIZE=76737  FINAL=https://www.santamarta.gov.co/politica-de-privacidad
politicas/politica-de-privacidad       -> HTTP=404 SIZE=75090  FINAL=https://www.santamarta.gov.co/politicas/politica-de-privacidad
terminos-de-uso                        -> HTTP=200 SIZE=75622  FINAL=https://www.santamarta.gov.co/terminos-de-uso
terminos-y-condiciones                 -> HTTP=404 SIZE=82449  FINAL=https://www.santamarta.gov.co/terminos-y-condiciones
transparencia                          -> HTTP=404 SIZE=83437  FINAL=https://www.santamarta.gov.co/transparencia
atencion-al-ciudadano                  -> HTTP=200 SIZE=78897  FINAL=https://www.santamarta.gov.co/atencion-al-ciudadano
```

**Errores de carga con código HTTP (reportados según lo pedido):**
- `GET https://www.santamarta.gov.co/declaracion-de-accesibilidad` → **HTTP 404**
- `GET https://www.santamarta.gov.co/terminos-y-condiciones` → **HTTP 404**
- `GET https://www.santamarta.gov.co/transparencia` → **HTTP 404** (la ruta real es `/transparencia-y-acceso-la-informacion-publica`)
- `GET https://www.santamarta.gov.co/politicas/politica-de-privacidad` → **HTTP 404**

### 4.3 ¿Qué es `/accesibilidad` en realidad?

**El enlace "Accesibilidad" existe y la página carga (HTTP 200), pero NO es una declaración de accesibilidad.** Es una página de **servicios para personas con discapacidad**. Texto extraído del contenido principal (`evidencia/acc.html`, tras eliminar `<script>/<style>/<nav>/<footer>`):

> `Inicio → Accesibilidad → Accesibilidad`
> **Accesibilidad**
> - *Pasos para obtener el Certificado de Discapacidad* — "Desde la Oficina de Atención al Ciudadano de la Alcaldía Distrital de Santa Marta, ilustramos este Video explicativo sobre los pasos para obtener el 'Certificado de Discapacidad'…"
> - *Caracterización Persona Con Discapacidad* — "…como caracterizarse como persona con discapacidad en la Oficina de Discapacidad…"
> - *Inscripción para el Programa Colombia Mayor*
> - *Inscripción para Familias en Acción*
> - *Sisben Opción 1* / *Sisben Opción 2*

**Cero menciones a WCAG, conformidad, nivel AA/AAA, NTC 5854, Ley 1712, Decreto 1081 o Resolución 1519.** Tampoco hay fecha de última revisión ni mecanismo de contacto para reportar barreras de accesibilidad, que son elementos mínimos de una declaración.

El enlace aparece en la portada como icono y en el menú:
```html
<!-- Accesibilidad -->
<div>
  <a href="/accesibilidad" target="_blank">
    <img src="/sites/default/files/header/accesiblidad.svg" alt="Accesibilidad" ...>
  </a>
</div>
```
```html
<li id="menu-4002-4" ...><a href="/accesibilidad" class="sf-depth-3">Accesibilidad</a></li>
```
Nota: el SVG se llama `accesiblidad.svg` (**error tipográfico** en el nombre del archivo).

### 4.4 Búsquedas dirigidas de normativa

```bash
for q in "declaraci%C3%B3n" "Ley%201712" "NTC%205854" "1519" WCAG; do
  curl -sSL --max-time 40 -o "s_$q.html" \
    "https://www.santamarta.gov.co/search/node/$q"
done
```

| Búsqueda | URL consultada | Resultados |
|---|---|---|
| `accesibilidad` | `/search/node/accesibilidad` (HTTP 200) | 9 resultados: solo noticias y la propia `/accesibilidad`. **Ninguna declaración.** |
| `WCAG` | `/search/node/WCAG` (HTTP 200) | **0 resultados** |
| `NTC 5854` | `/search/node/NTC%205854` (HTTP 200) | **0 resultados** |
| `declaración de accesibilidad` | `/search/node/declaraci%C3%B3n%20de%20accesibilidad` (HTTP 200) | **0 resultados** (los 10 hits son "declaración juramentada", "declaración de industria y comercio", etc.) |
| `Ley 1712` | `/search/node/Ley%201712` (HTTP 200) | 4 resultados, todos de **transparencia**, ninguno de accesibilidad |
| `1519` | `/search/node/1519` (HTTP 200) | 2 resultados, ambos noticias no relacionadas |

El **sitemap oficial** (`https://www.santamarta.gov.co/sitemap-0`, HTTP 200, 89.042 B, 221 enlaces) confirma el universo de URLs:
```bash
pat=re.compile(r'acces|politic|termin|conform|privac|wcag|ley-1712|transparencia', re.I)
# 221 links totales → coincidencias:
  /accesibilidad
  /transparencia-y-acceso-la-informacion-publica
  https://www.santamarta.gov.co/accesibilidad
  https://www.santamarta.gov.co/politica-de-privacidad
  https://www.santamarta.gov.co/politicas
  https://www.santamarta.gov.co/terminos-de-uso
  https://www.santamarta.gov.co/transparencia-y-acceso-la-informacion-publica
```
→ **No hay ninguna URL** con `declaracion`, `wcag`, `conformidad` o `accesibilidad-web`.

### 4.5 Páginas legales: contienen declaración WCAG? No.

**`/politica-de-privacidad`** (HTTP 200, 76.737 B) — texto completo revisado: habla exclusivamente de tratamiento de datos personales, confidencialidad, clave de acceso y exención de responsabilidad. **Cero menciones a WCAG o accesibilidad.** Búsquedas:
```
'accesibilidad': 2   → ambas son el enlace del menú/icono, no contenido
'WCAG': 0 | 'NTC 5854': 0 | 'Ley 1712': 0 | 'Decreto 1081': 0 | '1519 de 2020': 0 | 'conformidad': 0
```

**`/terminos-de-uso`** (HTTP 200, 75.622 B) — condiciones de uso, licencia, calidad de la información, enlaces externos, soporte técnico y legislación aplicable. **Cero menciones a WCAG o accesibilidad**:
```
'accesibilidad': 2   → menú/icono
'WCAG': 0 | 'NTC 5854': 0 | 'Ley 1712': 0 | 'conformidad': 0 | 'discapacidad': 0
```

**`/politicas`** (HTTP 200) — índice de políticas: mismos enlaces, sin declaración de accesibilidad.

**`/101-informacion-minima-requerida-publicar`** (HTTP 200, 81.893 B) — reproduce los **artículos 9, 10 y 11 de la Ley 1712 de 2014** literalmente. Es el índice de transparencia. Búsquedas sobre el texto:
```
'accesib': 0 | 'WCAG': 0 | 'discapacidad': 0 | '1519': 0 | '1712': 1 | 'conformidad': 7
```
→ **Cita el art. 9 de la Ley 1712** (que es justamente el artículo que ordena publicar la información mínima **en formatos accesibles**) pero **no incluye ni enlaza ninguna declaración de accesibilidad ni compromiso de conformidad WCAG**. Las 7 apariciones de "conformidad" son de otro contexto (conformidad de la información contractual/presupuestal).

### 4.6 Marco normativo aplicable (fuentes externas)

- **Ley 1712 de 2014, art. 9** — información mínima obligatoria de estructura del sujeto obligado; el propio sitio la reproduce en `/101-informacion-minima-requerida-publicar` (evidencia arriba).
- **Resolución MinTIC 1519 de 2020, Anexo 1** — *Directrices de Accesibilidad Web*. Fuente verificada: [Compilación Normativa — Resolución MinTIC 1519 de 2020, Anexo 1](https://compilacionjuridica.shd.gov.co/compilacion/docs/resolucion_mintic_1519_2020.htm#ANEXO%201). La práctica del Estado colombiano es publicar un **"Certificado/Certificación de Cumplimiento Anexo Técnico 1 – Resolución 1519 del 2020"**, y existen múltiples ejemplos públicos: [Gobierno Bogotá 2025](https://www.gobiernobogota.gov.co/sites/default/files/2025-12/anexo-tecnico-1-certificacion-de-accesibilidad-portal-Gobierno%20Bogota-2025.pdf#1#1), [ANSV certificación Anexo 1 - ITA](https://www.ansv.gov.co/sites/default/files/Documentos/Agencia/CERTIFICADO%20DE%20ACCESIBILIDAD%20ANEXO%201%20ITA.pdf#1#1), [Capital Salud EPS](https://www.capitalsalud.gov.co/wp-content/uploads/2022/09/Certificado-Accesibilidad-Sitio-Web.pdf#1#1), [APC Colombia — informe de verificación Ley 1712 de 2014, Anexo Técnico 1 Accesibilidad Web](https://www.apccolombia.gov.co/sites/default/files/2021-11/Informe%20verificacio%cc%81n%20ley%201712%20de%202014.pdf#7#2).
- **Decreto 1081 de 2015** — reglamenta el acceso a la información pública; **su articulado específico sobre declaración de accesibilidad NO lo verifiqué en fuente primaria** → **NO VERIFICADO** (ver §5).

**Comparación:** entidades como Alcaldía de Bogotá, ANSV y Capital Salud **sí publican** su certificación de accesibilidad web. **La Alcaldía Distrital de Santa Marta no publica nada equivalente.**

### 4.7 Evidencia parcial de accesibilidad técnica (lo que SÍ existe)

Medido sobre `home.html` (215.243 B, 2.469 líneas) — evidencia positiva real, no nula:

**(a) Skip-link — PRESENTE y correcto** (nivel de marcado)
```html
<div id="skip-link">
  <a href="#main-content" class="element-invisible element-focusable">Pasar al contenido principal</a>
</div>
```
Y el destino existe: `id="main-content"` presente en la portada. **Presente también en las subpáginas** verificadas `/accesibilidad`, `/politica-de-privacidad`, `/terminos-de-uso`, `/101-informacion-minima-requerida-publicar`:
```bash
for f in acc.html page_politica-de-privacidad.html page_terminos-de-uso.html page_minima.html; do
  printf "%-36s -> " "$f"; grep -q 'id="skip-link"' "$f" && echo PRESENTE || echo AUSENTE
done
```
```
acc.html                             -> PRESENTE
page_politica-de-privacidad.html     -> PRESENTE
page_terminos-de-uso.html            -> PRESENTE
page_minima.html                     -> PRESENTE
```
> ⚠️ **Limitación honesta:** verifico la presencia del marcado, **no su funcionamiento con teclado/foco en un navegador real**. La utilidad real del skip-link depende de que `element-invisible`/`element-focusable` (de Drupal core) funcionen con los estilos del tema, cosa que **no probé en navegador** → **NO VERIFICADO**.

**(b) Atributos `alt`** — cobertura alta pero **incompleta**:
```
img total:        86
img con alt:      76   (88,4 %)
img alt vacío:     2   (correcto: imágenes decorativas)
```
**3 imágenes SIN `alt`** (violación WCAG 2.1 — 1.1.1 Contenido no textual, nivel A):
```html
<img src="/sites/default/files/styles/medium/public/icon-emprendimiento.png" width="87" height="89" align="middle">
<img src="/sites/default/files/styles/medium/public/icon-formalizacion.png"  width="87" height="89" align="middle">
<img src="/sites/default/files/styles/medium/public/icon-empleabilidad.png"  width="87" height="89" align="middle">
```
Las 2 con `alt=""` son el slider de fondo RevoSlider y un PNG transparente (`revslider/assets/admin/images/transparent.png`) → uso correcto.

**(c) ARIA** — total **50 atributos `aria-*`**:
```
     33 aria-hidden=
     10 aria-expanded=
      7 aria-controls=
```
Cobertura razonable para menús desplegables (Superfish/Bootstrap) y el slider (`aria-hidden`), pero **no hay `aria-label`, `aria-live`, ni `role`** en el marcado → no hay gestión de regiones dinámicas ni anuncios para lectores de pantalla. El slider de noticias y el carrusel —ambos contenido dinámico— no declaran `aria-live`.

**(d) Estructura de encabezados** — problema detectado:
```
h1: 1   → "principal"
h2: 18  → "Main menu", "Trámites y Servicios", "Sala de prensa", …
h3: 2   → "Transparencia y Acceso a la Información Pública", "¡Su opinión es importante!"
h4: 1   → "Instragram @santamartadtch"
```
El `h1` es literalmente **"principal"** (texto del skip-link o de un contenedor), no un título descriptivo de la página. Y el menú de navegación (`<h2>Main menu</h2>`) está en inglés y al mismo nivel jerárquico que las secciones de contenido — jerarquía semánticamente incorrecta para lectores de pantalla. Además `h2` aparece 18 veces y `h3` solo 2: salto de jerarquía h2→h4 en el pie (`Instagram @santamartadtch`).

**(e) Idioma — correcto**
```html
<html lang="es" dir="ltr" prefix="content: … rdfs: … xsd: …">
```

**(f) Viewport — correcto**
```html
<meta name="viewport" content="width=device-width, initial-scale=1.0">
```

**(g) Formularios — muy pobres**
```
<form: 1 | <label: 1 | <input: 4   →  3 de 4 inputs SIN atributo id
```
Sin `id` no hay asociación `label for=`/`input id=` posible, ni `aria-describedby`. WCAG 2.1 **3.3.2 Etiquetas o instrucciones (nivel A)** y **1.3.1 Información y relaciones (nivel A)** quedan comprometidos en el buscador/formulario del sitio.

### 4.8 Resumen accesibilidad

| Elemento | Estado | Nivel WCAG |
|---|---|---|
| Declaración de accesibilidad / conformidad | **NO EXISTE** | — (obligación legal CO) |
| Enlace "Declaración de accesibilidad" | **NO EXISTE** (solo un enlace "Accesibilidad" que va a otra cosa) | — |
| Mención de WCAG / NTC 5854 / Ley 1712 art. 9 / Res. 1519 | **AUSENTE en todo el sitio** | — |
| Skip-link | ✅ Presente (marcado) en portada y subpáginas | 2.4.1 (A) — parcial |
| `alt` en imágenes | ⚠️ 76/86 (88,4 %); **3 sin `alt`** | 1.1.1 (A) — incumple |
| `lang` del documento | ✅ `lang="es"` | 3.1.1 (A) — cumple |
| Jerarquía de encabezados | ❌ h1="principal", h2 en inglés, salto h2→h4 | 1.3.1 / 2.4.6 (A/AA) — incumple |
| Etiquetado de formularios | ❌ 3/4 inputs sin `id`, 1 `<label>` para 4 inputs | 3.3.2 / 1.3.1 (A) — incumple |
| Regiones dinámicas (`aria-live`) | ❌ 0 atributos `aria-live`/`role` | 4.1.3 (AA) — no verificable/ausente |
| Contraste (color institucional) | ✅ `#3366CC` sobre blanco = **5,37:1** (AA) | 1.4.3 (AA) — cumple ese par |

---

## 5. NO PUDE VERIFICAR

Lista explícita de lo que **no** quedó comprobado, con el motivo:

1. **Versión de PHP del backend.** Sin `x-powered-by`, sin cadena `PHP/x.y.z` en la página 404 (73.379 B revisados), sin página de error detallada de Drupal 7. `settings.php` responde 500 (confirma PHP, no su versión). *Motivo:* el servidor no la expone y no es correcto forzarla con técnicas intrusivas.

2. **Versión del servidor web de origen (Apache/nginx) tras Cloudflare.** Todas las respuestas muestran `server: cloudflare`; el origen está oculto. *Motivo:* Cloudflare sobrescribe la cabecera `Server`. (Drupal 7 `INSTALL.txt` recomienda Apache ≥2.0, pero eso es documentación del CMS, **no** un dato del sitio.)

3. **Plan y configuración del WAF de Cloudflare** (reglas, managed ruleset, plan contratado). *Motivo:* requeriría pruebas intrusivas, fuera del alcance de un análisis pasivo.

4. **Deshabilitación real de TLS 1.0 y 1.1.** El cliente local **no puede negociar** esos protocolos (OpenSSL 3.5.8 con 0 cifrados TLS1.0/1.1 y sin `MinProtocol` configurable en `/etc/pki/tls/openssl.cnf`); el error `no protocols available` es del cliente y la petición nunca llegó al servidor. SSL Labs sí los **enumera como soportados**. *Motivo:* limitación de la herramienta local + campo de motivo (verdict) de SSL Labs no obtenible sin renderizar JavaScript. **Zanjable con** `testssl.sh` o `nmap --script ssl-enum-ciphers`.

5. **Causa exacta de la calificación B de SSL Labs.** Tengo el grado (B, en 4 endpoints) y los datos de configuración, pero **no** el texto del "verdict" que SSL Labs muestra en la página renderizada. Mi hipótesis (TLS 1.0/1.1 habilitado) es **una inferencia, no un hecho verificado**. *Motivo:* `ssllabs.com/ssltest/analyze.html` construye el detalle por JavaScript; con `curl` solo obtuve la tabla resumen.

6. **Funcionamiento real del skip-link con teclado en navegador.** Verifiqué el marcado (`#skip-link` → `#main-content`), no el comportamiento de foco visual. *Motivo:* no ejecuté pruebas en navegador/lector de pantalla.

7. **Contraste real renderizado de todos los pares texto/fondo del sitio.** Calculé el contraste de los colores institucionales (`#3366CC`, `#004884`, `#F42F63` sobre blanco) por fórmula WCAG, pero **no** recorrí el árbol de estilos computados de la página renderizada. *Motivo:* sin motor de navegador no puedo obtener `getComputedStyle` ni resolver herencias/cascada reales.

8. **Auditoría WCAG formal / puntuación de conformidad (A / AA / AAA).** Sería pretencioso declarar un nivel. *Motivo:* una auditoría de conformidad exige evaluación manual con lector de pantalla, navegación por teclado y verificación de todos los criterios; lo aquí presentado es evidencia **parcial** de marcado.

9. **Decreto 1081 de 2015 — articulado específico sobre declaración de accesibilidad.** Cité la norma como marco aplicable por indicación del encargo y por la práctica documentada de otras entidades, pero **no leí el texto primario** del decreto. *Motivo:* no lo consulté en fuente oficial; queda como **NO VERIFICADO**.

10. **Contenido del PDF/documentos oficiales de accesibilidad** enlazados desde el sitio (p. ej. `Manual-de-atencion-de-PQRSD.pdf`, `oferta_institucional_…pdf`). *Motivo:* fuera del alcance del encargo (análisis web del portal, no de sus PDF).

11. **Cobertura real de `robots.txt` frente a los buscadores y existencia de sitemap XML para motores.** Revisé `robots.txt` (HTTP 200, 2.189 B) y `/sitemap-0` (HTTP 200, 89.042 B, 221 enlaces), pero no verifiqué si hay `sitemap.xml` aparte ni el `Disallow` completo. *Motivo:* no formaba parte del alcance solicitado.

12. **Comportamiento del sitio ante usuarios autenticados** (`/user/login` → HTTP 200, 73.235 B; `/user/password` → **HTTP 403**, 5.391 B). Las cabeceras y el HTML analizados son siempre de sesión anónima (sin `Set-Cookie`). *Motivo:* no dispongo de credenciales ni corresponde.

---

## 6. Anexo A — Índice de evidencia

Directorio: `/home/sacunapolo/Documentos/sede/investigacion/evidencia/`

| Archivo | Contenido | Comando de origen |
|---|---|---|
| `head_root.txt` | Cabeceras HEAD de portada | `curl -sSI https://www.santamarta.gov.co/` |
| `headers_root_GET.txt` | Cabeceras GET completas | `curl -sS -D- -o /dev/null -L …` |
| `head_http.txt` | Redirección 301 HTTP→HTTPS | `curl -sSI http://www.santamarta.gov.co/` |
| `trace_headers.txt` / `trace_body.txt` | `/cdn-cgi/trace` (HEAD 404 / GET 200) | `curl -sS https://…/cdn-cgi/trace` |
| `cert.txt` | subject/issuer/dates/SAN | `openssl s_client … \| openssl x509 -noout …` |
| `dig_www.txt`, `dig_apex.txt`, `dig_ns.txt` | IPs y nameservers | `dig +short …` |
| `home.html`, `home.fold.txt` | Portada (215.243 B) y versión plegada a 200 col. | `curl -sSL -A 'Mozilla/5.0' -o home.html …` |
| `probe_CHANGELOG.txt` | **Drupal 7.103, 2024-12-04** (119.644 B) | `curl …/CHANGELOG.txt` |
| `probe_README.txt`, `probe_INSTALL.txt`, `probe_robots.txt` | Documentos de Drupal expuestos | `curl …/{README,INSTALL,robots}.txt` |
| `err404.html` | Página 404 (73.379 B, sin datos de PHP) | `curl …/no-existe-pagina-xyz-12345` |
| `install.php.html` | "Drupal already installed" | `curl …/install.php` |
| `css_*.css` (3) | CSS agregados de Drupal (145.415 / 11.184 / 148.493 B) | `curl …/sites/default/files/css/<hash>.css` |
| `js_*.js` (6) | JS agregados de Drupal (hasta 511.563 B) | `curl …/sites/default/files/js/<hash>.js` |
| `theme/*.css` (7) | CSS del tema Bootstrap (`style.css` 82.098 B, **`hacer.css`** 901 B con `.header-gov{#36c}`) | `curl …/sites/all/themes/bootstrap/css/…` |
| `govco_all.css` | **Kit UI oficial gov.co** (288.052 B, 16.666 líneas) | `curl cdn.www.gov.co/layout/v4/all.css` |
| `gov_*.html` (4) | `gov.co/kit-ui/` y `cdn.www.gov.co/kit-ui/` | `curl -L …` |
| `ssllabs3.html`, `ssllabs_full.json` | Informe y API SSL Labs (grado **B**) | `curl ssllabs.com/ssltest/analyze.html` + `api.ssllabs.com/api/v3/analyze` |
| `page_accesibilidad.html` / `acc.html` | Página `/accesibilidad` (título y texto extraídos) | `curl …/accesibilidad` |
| `page_politica-de-privacidad.html`, `page_terminos-de-uso.html`, `page_politicas.html`, `page_minima.html` | Páginas legales y de transparencia | `curl …/{politica-de-privacidad,terminos-de-uso,politicas,101-informacion-minima-requerida-publicar}` |
| `search_*.html`, `s_*.html` (6) | Búsquedas internas de Drupal (`WCAG`, `NTC 5854`, `Ley 1712`, `1519`, `declaración`) | `curl …/search/node/<query>` |
| `sitemap.html` | Sitemap oficial (89.042 B, 221 enlaces) | `curl …/sitemap-0` |
| `login_pw.html` | `/user/password` → **HTTP 403** | `curl …/user/password` |
| `recursos_externos.txt` | Inventario de 9 dominios externos de la portada | script `python3` sobre `home.html` |

---

## 7. Anexo B — Hallazgos accionables (priorizados)

| # | Hallazgo | Evidencia | Acción recomendada |
|---|---|---|---|
| 1 | **Drupal 7.103 sobre govCMS**, EOL desde 2025-01-05 (~21 meses) | `x-generator` + `CHANGELOG.txt` + [drupal.org](https://www.drupal.org/association/blog/drupal-7-end-of-life-officially-announced-for-5-january-2025) | Migración a Drupal 10/11 o a un CMS soportado; sin parches de seguridad de la comunidad desde el EOL |
| 2 | **`CHANGELOG.txt` / `README.txt` / `INSTALL.txt` públicos** | `curl` → HTTP 200 (119.644 / 5.382 / 18.052 B) | Bloquear en el edge (Cloudflare Rule) o en `robots.txt` + retirar del docroot |
| 3 | **Sin declaración de accesibilidad ni conformidad WCAG** | `/declaracion-de-accesibilidad` → 404; `/search/node/WCAG` → 0 resultados; sitemap sin URL relacionada | Publicar declaración de accesibilidad + certificación Anexo 1 Res. MinTIC 1519/2020 |
| 4 | **Kit UI gov.co no implementado** (solo `#36c` aislado) | `grep` → 0 clases `.govco-*`, 0 `--govco-*`, 0 `govco-font`; `#004884` ausente; `hacer.css` línea 35 | Adoptar el kit: `cdn.www.gov.co/layout/v4/all.css`, fuente `govco-font`, azul `#004884` |
| 5 | **TLS 1.0/1.1 aparentemente habilitados** (SSL Labs) → grado **B** | `ssllabs_full.json` | Cloudflare → SSL/TLS → Edge Certificates → **Minimum TLS Version = 1.2** |
| 6 | **29 recursos `http://` en la portada** (mixed content / enlaces inseguros) | parseo `src|href="http://…"` sobre `home.html` | Migrar a HTTPS, en particular `siettsantamarta.com`, `avisos.fotomultasmr.com`, `186.1.183.78:8282` |
| 7 | **jQuery 2.2.4 + Bootstrap 3.3.7 + Font Awesome 4.7.0** (todos EOL, vía CDN de terceros) | inventario `recursos_externos.txt` | Actualizar; autohospedar con SRI (el sitio no usa `integrity` en ningún `<script>`/`<link>`) |
| 8 | **Sin `Content-Security-Policy` ni `Permissions-Policy`** | `curl -D- … \| grep -i content-security-policy` → vacío | Desplegar CSP (hay 9 dominios externos que declarar) |
| 9 | **HSTS sin `preload`, sin OCSP stapling** | SSL Labs `hstsPreloads: absent`, `ocspStapling: False` | Añadir `preload` y habilitar OCSP stapling |
| 10 | **3 `<img>` sin `alt`** en portada | `icon-emprendimiento.png`, `icon-formalizacion.png`, `icon-empleabilidad.png` | Añadir `alt` descriptivo (WCAG 1.1.1, nivel A) |
| 11 | **`h1` = "principal"**; `h2` "Main menu" en inglés; salto h2→h4 | extracción de encabezados de `home.html` | Corregir jerarquía semántica e internacionalizar el menú |
| 12 | **Formularios sin etiquetado**: 3/4 `<input>` sin `id`, 1 `<label>` | `grep -o '<input'` y `grep -o 'id=' ` | Añadir `id` + `label for=` |
| 13 | **Doble analítica** GA4 + Universal Analytics (`UA-121052958-1`) | `home.html` | Retirar UA (descontinuado) |
| 14 | **Código muerto**: bloque Tawk.to comentado apuntando a un endpoint 404 | `home.html` (`</script-->`) + `curl` al endpoint → 404 | Eliminar |
| 15 | **Slider Revolution** (componente comercial con historial de CVE) en Drupal 7 sin parches | `/sites/all/modules/revslider` ×3 en `home.html` | Evaluar retiro o sustitución |
| 16 | **Servicios enlazados sobre HTTP/IP sin TLS**: `http://186.1.183.78:8282/…` (5 enlaces), `http://siettsantamarta.com/…` (8), `http://avisos.fotomultasmr.com` (3) | `home.html` | Migrar a HTTPS: exfiltración de trámites y datos ciudadanos en claro |
| 17 | **Errores tipográficos en producción**: `accesiblidad.svg`, `weight="140"` (en vez de `height`) | `home.html` | Corregir |
