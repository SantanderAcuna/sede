# Política de Seguridad de Dependencias

**Fecha:** 2026-10-06
**Versión:** 1.0
**Estado:** ACTIVA

---

## 1. Principios

1. **Defensa en profundidad:** Múltiples capas de protección (overrides, parches locales, CI gates, auditorías manuales).
2. **Tolerancia cero para vulnerabilidades corregibles:** Si hay parche, se aplica. Si no hay parche, se documenta y se mitiga.
3. **Visibilidad:** Todas las decisiones de dependencias quedan registradas en ADRs.
4. **Automatización con oversight humano:** Las herramientas automatizan, las personas deciden.

## 2. Niveles de Severidad y Respuesta

| Severidad | CVSS | Acción Requerida | SLA |
|-----------|------|------------------|-----|
| **Critical** | ≥ 9.0 | Bloquea deploy inmediatamente | 24h |
| **High** | 7.0-8.9 | Bloquea PR si es corregible | 7 días |
| **Medium** | 4.0-6.9 | Documentar y planificar | 30 días |
| **Low** | < 4.0 | Revisar en próximo sprint | 90 días |

## 3. Estrategia de Remediación

### 3.1 Vulnerabilidades con parche oficial
- **Acción:** Aplicar via `overrides` en `package.json` o actualizar dependencia directa.
- **Tiempo:** Inmediato.
- **Revisión:** PR + CI 7/7 PASS.

### 3.2 Vulnerabilidades sin parche oficial
- **Acción:** Patch local via `scripts/patch-deps.mjs` (postinstall hook).
- **Fallback:** Aislamiento por `overrides` + análisis de superficie de ataque.
- **Último recurso:** Whitelist en `permitidas` del CI con justificación documentada.

### 3.3 Vulnerabilidades que requieren major upgrade
- **Acción:** ADR formal (ver `docs/adr/`).
- **Tiempo:** 1-2 sprints.
- **Revisión:** Manual + visual testing (captura visual para sitio).

## 4. Overrides Actuales

### 4.1 `sitio/package.json`
```json
{
  "overrides": {
    "node-forge": "^1.0.0",
    "source-map-js": "^1.2.2",
    "simple-git": "^4.0.2",
    "postcss-selector-parser": ">=7.1.6"
  }
}
```

### 4.2 `panel/package.json`
```json
{
  "overrides": {
    "chokidar": "^3.1.0",
    "source-map-js": "^1.2.2",
    "postcss-selector-parser": ">=7.1.6"
  }
}
```

## 5. Parches Locales

### 5.1 `braces` (CVE-2026-93687)
- **Parche:** `scripts/patch-deps.mjs` añade guard de profundidad.
- **Hook:** `preinstall` en sitio/ y panel/.
- **Límite:** 50 niveles (configurable via `BRACES_MAX_DEPTH`).
- **Test:** `npm run test:braces`.

## 6. CI Gates

### 6.1 GitHub Actions
- **Job:** `Dependencias`
- **Script:** `.github/scripts/auditar-dependencias.py`
- **Permitidas:** GHSAs documentados en `.github/workflows/ci.yml`
- **Falla si:** Hay vulns corregibles o injustificadas.

### 6.2 Pull Requests
- ✅ CI debe pasar 7/7 checks.
- ✅ Coverage ≥95% (Backend y Frontend).
- ✅ Sin nuevas vulns corregibles.

## 7. Auditorías Programadas

- **Diaria:** Dependabot (vulns nuevas en PRs automáticos).
- **Semanal:** Revisión manual de `npm audit` en develop.
- **Mensual:** Revisión de ADRs y roadmap de migraciones.
- **Trimestral:** Auditoría completa de la superficie de dependencias.

## 8. Inventario de Vulnerabilidades Conocidas

| Paquete | CVE | Estado | Plan |
|---------|-----|--------|------|
| `postcss-selector-parser` | CVE-2026-104844 | ✅ Resuelto (override) | - |
| `simple-git` | 3 GHSAs critical | ✅ Resuelto (override `^4.0.2`) | - |
| `node-forge` | CVE-2026-85393 | ⏳ Sin parche | Esperar nuxt fix |
| `braces` | CVE-2026-93687 | 🛡️ Parche local | ADR-0001 (Tailwind 4) |
| `tailwindcss 3.x` | 6 vulns high | 📋 Plan | ADR-0001 |
| `stylelint 17.x` | 5 vulns high | 📋 Plan | Sprint futuro |
| `vue-router 5.x` | 1 vuln high | 📋 Plan | Sprint futuro |

## 9. Referencias

- [OWASP Top 10 — A06: Vulnerable Components](https://owasp.org/Top10/A06_2021-Vulnerable_and_Outdated_Components/)
- [npm overrides documentation](https://docs.npmjs.com/cli/v10/configuring-npm/package-json#overrides)
- [patch-package](https://www.npmjs.com/package/patch-package)
- ADRs en `docs/adr/`

## 10. Cambios a esta Política

| Versión | Fecha | Cambio |
|---------|-------|--------|
| 1.0 | 2026-10-06 | Creación inicial con estrategia de Fase 1-4 |
