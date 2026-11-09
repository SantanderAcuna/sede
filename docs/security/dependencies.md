# Política de Seguridad de Dependencias

**Fecha:** 2026-10-06
**Versión:** 1.1
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
| `stylelint` | 5 vulns high (17.x) | ✅ Resuelto (18.0.0 + `stylelint-config-standard` 41, PR #449) | - |
| `minimatch` / `glob` | GHSA-g3hv-75wg-m249 / GHSA-3g3m-2fjr-6pw4 (moderadas) | ✅ Resuelto (overrides `minimatch` ^11.0.1 y `glob` ^14.0.2, PR #447) | - |
| `deepmerge` | CVE-2026-93753 (GHSA-gcqx-w48m-w2h4, baja) | ⚠️ Riesgo aceptado (sin parche) | Ver §8.1 |
| `vue-router 5.x` | 1 vuln high | 📋 Plan | Sprint futuro |

### 8.1 Riesgo aceptado: `deepmerge` <= 4.3.1 (alerta de Dependabot #25)

**Qué dice el aviso.** `mergeObject()` no valida las claves que escribe y permite contaminar el prototipo del objeto devuelto. Afecta a `>= 4.2.0, <= 4.3.1`. Severidad **baja** (CVSS v4: integridad baja, sin impacto en confidencialidad ni disponibilidad). **No hay versión corregida**: la 4.3.1 es la última publicada.

**Por qué no se puede corregir hoy.**
- Cadena: `nuxt` → `@nuxt/nitro-server` → `nitropack` 2.13.6 → `@rollup/plugin-node-resolve` 16.0.3 → `deepmerge` ^4.2.2 (única dependencia del bloqueo del sitio que lo pide).
- `@rollup/plugin-node-resolve` 16.0.3 es la última versión y `nitropack` 2.13.6 la última, que pide `^16.0.3`: no existe una versión aguas arriba que lo evite.
- No se usa un `override` hacia un fork de terceros (riesgo de cadena de suministro y API incompatible) ni una copia parcheada versionada en el repositorio (carga de mantenimiento desproporcionada para una severidad baja).

**Por qué el riesgo es tolerable** (medido sobre el código y la construcción, no supuesto):
1. **No recibe datos externos.** En todo el plugin hay un único uso: `const DEFAULTS = deepFreeze(deepMerge({}, defaults))`, al cargar el módulo, con una constante del propio plugin. El defecto del aviso exige que un atacante controle un objeto fuente; aquí nadie puede hacerlo.
2. **Sólo se ejecuta al construir**, dentro de Rollup. No forma parte del código del servidor: el artefacto desplegado (`.output`) contiene **0** referencias a `deepmerge`.
3. **El proceso en ejecución no lo carga.** La imagen del sitio arranca con `node .output/server/index.mjs`. El paquete sí viaja en `/app/node_modules` (sobrevive a `npm prune --omit=dev` porque `nuxt` es dependencia de producción), pero nada lo importa en ejecución.

**Qué falta para cerrarlo de verdad.** Que `deepmerge` publique una versión > 4.3.1, o que `nitropack`/`@rollup/plugin-node-resolve` dejen de depender de él. Se revisa cuando Dependabot proponga cualquiera de las dos cosas (revisión semanal).

**Decisión.** Alerta #25 descartada en GitHub con el motivo «El riesgo es tolerable para este proyecto» y un comentario que remite a esta sección. La puerta de dependencias no necesita lista de excepciones: sólo falla ante vulnerabilidades altas o críticas **corregibles**, y esta es baja y sin corrección.

## 9. Referencias

- [OWASP Top 10 — A06: Vulnerable Components](https://owasp.org/Top10/A06_2021-Vulnerable_and_Outdated_Components/)
- [npm overrides documentation](https://docs.npmjs.com/cli/v10/configuring-npm/package-json#overrides)
- [patch-package](https://www.npmjs.com/package/patch-package)
- ADRs en `docs/adr/`

## 10. Cambios a esta Política

| Versión | Fecha | Cambio |
|---------|-------|--------|
| 1.0 | 2026-10-06 | Creación inicial con estrategia de Fase 1-4 |
| 1.1 | 2026-11-09 | Inventario al día (stylelint, minimatch, glob) y registro del riesgo aceptado de `deepmerge` (§8.1) |
