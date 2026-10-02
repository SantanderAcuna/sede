# Endpoints — Módulo 01 (Estructura-Identidad)

> **Total endpoints módulo 01:** 18 públicos + 5 panel autenticado.

---

## API Pública (sitio) — 18 endpoints

### `GET /api/v1/identidad/top-bar`
- **Implementa:** RF-01-001
- **Auth:** Pública (`security: []`)
- **Cache:** TTL 1 hora (ADR-014)
- **Descripción:** retorna la configuración del top bar GOV.CO.

**Respuesta 200 OK:**
```json
{
  "data": {
    "type": "top-bar",
    "id": "1",
    "attributes": {
      "logo_path": "https://cdn.santamarta.gov.co/govco-logo.svg",
      "url_govco": "https://www.gov.co/home/",
      "altura_px": 56,
      "idioma_default": "es-CO"
    },
    "relationships": {
      "items": {
        "data": [
          { "type": "top-bar-item", "id": "1" },
          { "type": "top-bar-item", "id": "2" }
        ]
      }
    }
  },
  "included": [
    {
      "type": "top-bar-item",
      "id": "1",
      "attributes": {
        "etiqueta": "ES",
        "url": "?lang=es",
        "orden": 1
      }
    },
    {
      "type": "top-bar-item",
      "id": "2",
      "attributes": {
        "etiqueta": "EN",
        "url": "?lang=en",
        "orden": 2
      }
    }
  ]
}
```

### `GET /api/v1/identidad/footer`
- **Implementa:** RF-01-002, RF-01-003, RF-01-004, RF-01-018
- **Cache:** TTL 1 hora.

### `GET /api/v1/identidad/menu?rol={rol}`
- **Implementa:** RF-01-007, RF-01-008
- **Parámetros:**
  - `rol` (opcional): filtra el menú por rol del usuario autenticado.
- **Cache:** TTL 15 min, invalidado en evento `MenuActualizado`.

**Respuesta:**
```json
{
  "data": [
    {
      "type": "menu-item",
      "id": "1",
      "attributes": {
        "slug": "inicio",
        "etiqueta": "Inicio",
        "ruta": "/",
        "orden": 1,
        "visible": true,
        "tipo": "interno"
      }
    },
    {
      "type": "menu-item",
      "id": "2",
      "attributes": {
        "slug": "transparencia",
        "etiqueta": "Transparencia y acceso a la información pública",
        "ruta": "/transparencia",
        "orden": 2,
        "visible": true,
        "tipo": "interno"
      },
      "relationships": {
        "hijos": {
          "data": [
            { "type": "menu-item", "id": "21" },
            { "type": "menu-item", "id": "22" }
          ]
        }
      }
    }
  ]
}
```

### `GET /api/v1/noticias`
- **Implementa:** RF-01-013, RF-01-014
- **Parámetros query (JSON:API):**
  - `page[number]`, `page[size]`
  - `filter[categoria]`, `filter[destacada]`
  - `sort=-fecha_publicacion`
  - `include=imagenes,categorias`

### `GET /api/v1/noticias/{slug}`
- **Implementa:** RF-01-013 (detalle)

### `GET /api/v1/politicas`
- **Implementa:** RF-01-018

### `GET /api/v1/politicas/{codigo}`
- **Implementa:** RF-01-019, RF-01-020, RF-01-021 (cada código es una política)
- **Códigos válidos:** `terminos`, `privacidad`, `cookies`, `derechos-autor`, `accesibilidad`

### `GET /api/v1/buscar?q={query}&autocompletar=true`
- **Implementa:** RF-01-009
- **Parámetros:**
  - `q` (requerido): término de búsqueda, ≥3 caracteres
  - `autocompletar` (boolean, default false): si true, retorna sugerencias (≤10)
  - `limite` (int, default 10, max 10)

**Respuesta:**
```json
{
  "data": [
    {
      "type": "sugerencia",
      "id": "1",
      "attributes": {
        "texto": "Transparencia y acceso a la información pública",
        "url": "/transparencia",
        "tipo_recurso": "documento",
        "score": 0.95
      }
    }
  ],
  "meta": {
    "termino_original": "transparenci",
    "termino_corregido": "transparencia",
    "tiempo_ms": 12
  }
}
```

### `GET /api/v1/sitemap.xml`
- **Implementa:** RF-01-010
- **Descripción:** Sitemap XML para indexadores.
- **Generación:** automática vía job; regenerada al cambiar navegación.

---

## Panel Admin — 5 endpoints

### `POST /api/v1/panel/login`
- **Implementa:** RNF-SEG-02
- **Auth:** Pública (sin sesión)
- **Rate limit:** 5 req / 15 min por IP+email
- **Body:** JSON:API estándar con `email`, `password`.

**Respuestas:**
- `200 OK` con `{require_mfa: false, csrf_token, user}` → sesión iniciada.
- `200 OK` con `{require_mfa: true, mfa_token}` → continuar con `/panel/mfa`.
- `401` credenciales inválidas.
- `422` body malformado.
- `429` rate limit excedido.

### `POST /api/v1/panel/mfa`
- **Implementa:** RNF-SEG-02
- **Body:** `{mfa_token, code (6 dígitos)}`.

### `POST /api/v1/panel/logout`
- **Implementa:** logout
- **Respuesta:** `204 No Content`.

### `CRUD /api/v1/panel/identidad/top-bar`
- **Implementa:** gestión del top bar (admin)
- **Permisos:** `administrador`

### `CRUD /api/v1/panel/identidad/footer`
- **Implementa:** gestión del footer (admin)
- **Permisos:** `administrador`

### `CRUD /api/v1/panel/identidad/menu`
- **Implementa:** gestión del menú (admin)
- **Permisos:** `administrador`

### `CRUD /api/v1/panel/plan-integracion`
- **Implementa:** RF-01-022
- **Permisos:** `administrador`, `gcio`

---

## Resumen de códigos HTTP usados

| Código | Cuándo |
|---|---|
| 200 | GET exitoso |
| 201 | POST exitoso (recurso creado) |
| 204 | DELETE / logout exitoso |
| 400 | Body malformado |
| 401 | No autenticado |
| 403 | Sin permisos |
| 404 | Recurso no existe |
| 409 | Conflicto (ej. Idempotency-Key duplicada con body distinto) |
| 422 | Validación Form Request fallida |
| 429 | Rate limit excedido |
| 500 | Error interno no controlado |
| 503 | Servicio degradado (BD caída, etc.) |

---

## Patrones transversales

- **Idempotency-Key** en operaciones críticas de escritura.
- **ETag / If-None-Match** para GET condicional.
- **X-Request-ID** en cada request para trazabilidad.
- **Headers de seguridad:** HSTS, CSP, X-Content-Type-Options, X-Frame-Options.
