# SDD Init — Sede Electrónica

**Fecha:** 2026-10-02
**Proyecto:** sede
**Working Directory:** /home/sacunapolo/Documentos/sede
**Artifact Store:** docs/ (excepción Guide Maestra §0.1.2)

---

## Stack Detectado

| Componente | Tecnología | Versión |
|------------|-----------|---------|
| Backend | Laravel | ^13.17 |
| Frontend Sitio | Nuxt 4 | ^4.5.2 |
| Frontend Panel | Vue 3 + Vite | ^3.5.42 / ^8.3.0 |
| Lenguaje Backend | PHP | ^8.5 |
| Base de Datos | PostgreSQL | 18 (objetivo) |
| Autenticación | Laravel Sanctum | ^4.0 |
| Permisos | Spatie Permission | ^8.3 |
| HTTP Client (Panel) | Axios | ^1.20.0 |
| Estado (Panel) | Pinia | ^4.0.3 |

---

## Estructura del Proyecto

```
sede/
├── backend/              # Laravel 13 API REST
│   ├── app/Http/Controllers/Api/V1/
│   ├── app/Models/
│   ├── app/Services/
│   ├── app/Repositories/
│   ├── app/Contracts/
│   ├── app/Policies/
│   ├── database/migrations/
│   └── routes/api.php
├── sitio/               # Nuxt 4 SSR (público, solo index/show)
│   ├── app/pages/
│   └── app/assets/css/
├── panel/               # Vue 3 + Vite SPA (CMS admin)
│   ├── src/views/
│   ├── src/stores/
│   ├── src/router/
│   └── src/components/
├── contract/            # OpenAPI fuente de verdad
│   └── openapi.yaml
├── entidad-transparencia/  # Documentación módulos
│   ├── 02-requisitos/
│   ├── 03-propuesta/
│   ├── 04-diseno-bd/
│   └── 05-especificaciones-api/
└── docs/
    ├── docs-security/   # Guia Maestra Seguridad
    └── sdd/             # SDD init docs
```

---

## Estado Actual

### Backend
- ✅ Laravel 13 instalado con Sanctum, Spatie, DomPDF, MediaLibrary, ActivityLog
- ✅ Migraciones existentes: users, cache, jobs, personal_access_tokens, tramites
- ✅ Modelo Tramite con relaciones
- ✅ Rutas API: `/api/v1/tramites` (listar/mostrar) - 2 operaciones
- ✅ Permissions tables creadas (spatie)
- ❌ Falta: Entidad, Transparencia, Usuarios, Auth completo

### Frontend Sitio
- ✅ Nuxt 4 con TypeScript
- ✅ Páginas: index, transparencia, tramites, buscar, pqrsd, normativa, etc.
- ❌ Falta: consumir endpoint /entidad

### Frontend Panel
- ✅ Vue 3 + Vite + TypeScript
- ✅ Estructura básica: views, stores, router, components
- ❌ Falta: implementación completa de CRUD

---

## Convenciones Detectadas

### Backend (AGENTS.md)
- `declare(strict_types=1)` obligatorio
- Flat Envelope: `{success, message, data, errors}` con `application/json`
- PATCH para actualizaciones (nunca PUT)
- Clean Architecture: Controller → FormRequest → Service → Repository → Model
- Policy con `$user->can(...)`
- Super-admin con `Gate::before()` returning `true`

### Frontend Sitio (AGENTS.md)
- Tokens CSS en 3 niveles (primitivos, semánticos, componentes)
- GovCo Kit vendorizado byte-a-byte (no editar)
- `<script setup lang="ts">` con 0 `any`
- Captura visual para detectar cambios de diseño

### Frontend Panel
- Vue 3 Composition API
- TailwindCSS
- Pinia para estado
- Axios con Flat Envelope

---

## Artefactos Creados

| Artefacto | Ubicación |
|-----------|-----------|
| SDD Init 001 | `docs/sdd/001-sdd-init-sede.md` |

---

## Próximos Pasos Recomendados

1. **Unificar contratos OpenAPI** - Existe `contract/openapi.yaml` y `entidad-transparencia/05-especificaciones-api/openapi-3.1.yaml`
2. **Vertical Slice 1: Entidad** - Endpoint GET /entidad (TopBar/Footer)
3. **Vertical Slice 2: Auth Panel** - Login/Logout con Sanctum
4. **Vertical Slice 3: Tramites CRUD** - CRUD completo desde panel
5. **Vertical Slice 4: Transparencia** - Módulo Ley 1712

---

## Notas de la Guía Maestra Aplicadas

- **Excepción Engram:** Se usa `docs/sdd/` en lugar de mem_save
- **Vertical Slice:** Cada funcionalidad se completa antes de pasar a la siguiente
- **Contract-First:** OpenAPI es fuente de verdad antes de implementar
- **Checkpoints:** 25 puntos de auditoría al final de cada slice
