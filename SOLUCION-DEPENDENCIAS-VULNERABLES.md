# SOLUCIÓN A VULNERABILIDADES DE DEPENDENCIAS

**Fecha:** 2026-10-06
**Estado:** PR #200 mergeado a producción con vulnerabilidades pre-existentes
**Prioridad:** MEDIA — las vulns requieren major version upgrades

---

## 1. PROBLEMA

El job de CI `Dependencias` falla por 6 vulnerabilidades **críticas** y 14 **high** en `sitio/` y `panel/`. Las críticas son:

| Paquete | Severidad | Fix Disponible |
|---------|-----------|----------------|
| `@nuxt/cli` | high | nuxt 3.7.4 (major) |
| `@nuxt/devtools` | critical | nuxt 3.7.4 (major) |
| `@nuxt/nitro-server` | critical | nuxt 3.7.4 (major) |
| `@nuxt/vite-builder` | critical | nuxt 3.7.4 (major) |
| `@simple-git/argv-parser` | critical | nuxt 3.7.4 (major) |
| `simple-git` | critical | nuxt 3.7.4 (major) |
| `braces` | high | tailwindcss 4.3.3 (major) |
| `chokidar` | high | tailwindcss 4.3.3 (major) |
| `fast-glob` | high | tailwindcss 4.3.3 (major) |
| `globby` | high | stylelint 7.7.0 (downgrade raro) |
| `listhen` | high | nuxt 3.7.4 (major) |
| `micromatch` | high | tailwindcss 4.3.3 (major) |
| `nitropack` | high | nuxt 3.7.4 (major) |
| `node-forge` | high | nuxt 3.7.4 (major) |
| `nuxt` | critical | nuxt 3.7.4 (downgrade a v3) |

**Causa raíz:** El proyecto usa `nuxt: ^4.5.2` pero las vulns se arreglan en `nuxt 3.7.4`. El equipo está en Nuxt 4 mientras que las fixes están en Nuxt 3.

---

## 2. ESTADO ACTUAL

- ✅ **PR #200 mergeado a producción** (commit `e53e997`)
- ⚠️ **Job `Dependencias` fallando** por vulns pre-existentes (no introducidas por este PR)
- ✅ **Los demás jobs pasan**: Backend, Panel, Sitio, Contrato, Credenciales, Pila
- ⚠️ **El check de Dependencias está fallando en main**

---

## 3. OPCIONES DE SOLUCIÓN

### Opción A: Actualizar Nuxt 3.7.4 (NO recomendado)
- Es un **downgrade** de Nuxt 4 a Nuxt 3
- Rompería el resto del proyecto
- El equipo debe mantenerse en Nuxt 4

### Opción B: Actualizar a Nuxt 4.x latest (recomendado)
- Verificar la última versión de Nuxt 4 sin estas vulns
- Puede que ya estén parchadas en una versión más reciente de Nuxt 4
- Probar: `npm install nuxt@latest` y `npm audit`

### Opción C: Overrides específicos (parcial)
- Agregar overrides en `sitio/package.json`:
```json
{
  "overrides": {
    "@nuxt/devtools": "^3.4.2",
    "@simple-git/argv-parser": "<2.0.1",
    "simple-git": "<2.0.1"
  }
}
```
- Esto puede resolver las críticas pero no las de `tailwindcss` y `stylelint`

### Opción D: Actualizar Tailwind CSS a v4 (recomendado)
- `tailwindcss@3.4.19` → `tailwindcss@4.3.3`
- Esto es una major version, pero los cambios son manejables
- Resuelve: braces, chokidar, fast-glob, micromatch

### Opción E: Whitelist en workflow de CI (temporal)
- Agregar las vulns a la lista `permitidas` en `.github/workflows/ci.yml`
- Solo para que el CI pase mientras se actualizan las deps
- Líneas a modificar en ci.yml:
```yaml
permitidas="GHSA-86w9-cpqp-85rv GHSA-vfj7-8cjw-p6xm GHSA-XXXX GHSA-YYYY ..."
```

---

## 4. RECOMENDACIÓN

**Aplicar Opción B + Opción C + Opción D** en este orden:

1. **Opción B**: Verificar si Nuxt 4 tiene una versión más reciente sin estas vulns
2. **Opción C**: Agregar overrides en `package.json` para los paquetes críticos
3. **Opción D**: Planear actualización de Tailwind a v4 (puede ser otro PR)

---

## 5. ARCHIVOS A MODIFICAR

### `sitio/package.json`
```json
"overrides": {
  "node-forge": "^1.0.0",
  "source-map-js": "^1.2.2",
  "@nuxt/devtools": "^3.4.2",
  "@simple-git/argv-parser": "<2.0.1",
  "simple-git": "<2.0.1"
}
```

### `panel/package.json` (mismos overrides si aplica)
```json
"overrides": {
  "chokidar": "^3.1.0",
  "source-map-js": "^1.2.2"
}
```

### `package-lock.json` (regenerar con `npm install`)

---

## 6. PRÓXIMOS PASOS

1. **Crear rama `fix/deps-vulnerabilidades-criticas`**
2. **Aplicar Opción C** (overrides)
3. **Probar build** con `npm run build` en sitio
4. **Hacer push** de la rama (no toca workflow, no necesita scope workflow)
5. **Crear PR a develop**
6. **Esperar CI** — debería pasar el job Dependencias
7. **Merge a staging → main** siguiendo el flujo normal

---

**FIRMA:** Agente Cruzado
**FECHA:** 2026-10-06
**PR en producción:** #200 (mergeado)
**Próximo PR recomendado:** `fix/deps-vulnerabilidades-criticas`
