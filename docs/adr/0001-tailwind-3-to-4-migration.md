# ADR-0001: Migración de Tailwind CSS 3.4 a 4.x

> **Estado:** PROPUESTO
> **Fecha:** 2026-10-06
> **Decisores:** Equipo de desarrollo
> **Tags:** frontend, css, dependencies, security

## Contexto

El proyecto `sitio/` y `panel/` actualmente usan **Tailwind CSS 3.4.19**, que arrastra múltiples vulnerabilidades transitivas de severidad alta:

- `braces@3.0.3` — CVE-2026-93687 (stack exhaustion DoS)
- `chokidar@3.6.0` — Vulnerabilidades conocidas
- `fast-glob` — Múltiples issues de seguridad
- `micromatch` — Cadena vulnerable
- `postcss-selector-parser@6.1.4` — CVE-2026-104844 (resuelto via override)
- `tailwindcss@3.4.19` — Deprecada, sin parches de seguridad

**Tailwind CSS 4.x** ya está disponible y elimina la mayoría de estas vulnerabilidades al introducir una nueva arquitectura basada en CSS-first, Lightning CSS y un motor de compilación nativo en Rust.

## Decisión

**Migrar a Tailwind CSS 4.3+ en los próximos 2 sprints**, siguiendo la estrategia gradual:

### Fase 1 (inmediata)
- ✅ **Parche temporal de `braces`** con guard de profundidad (CVE-2026-93687) — aplicado
- ✅ **Override de `postcss-selector-parser >= 7.1.6`** — aplicado
- ⏳ **Override de `simple-git@^4.0.2`** — aplicado (PR #209)

### Fase 2 (esta semana)
- 📋 Auditoría completa del CSS actual para identificar todas las directivas usadas
- 📋 Pruebas visuales con `scripts/captura-visual.mjs` (ya existe en sitio/)
- 📋 Inventario de plugins y herramientas de Tailwind 3 usadas

### Fase 3 (próximos 2 sprints)
- 🔄 Migrar `panel/` primero (más simple, sin ITCSS custom)
- 🔄 Migrar `sitio/` después (con precaución por ITCSS según AGENTS.md §6)
- 🔄 Actualizar dependencias de testing (Vitest config)
- 🔄 Validar capturas visuales (11 vistas en sitio)

## Consecuencias

### Positivas
- ✅ Elimina 6+ vulnerabilidades transitivas de severidad alta
- ✅ Mejora el rendimiento de compilación (Lightning CSS)
- ✅ Acceso a nuevas features (`@theme`, `@source`, etc.)
- ✅ Mejor DX con CSS-first config
- ✅ Reduce superficie de ataque (menos deps transitivas)

### Negativas
- ⚠️ Breaking changes en la API de configuración
- ⚠️ Requiere reescritura de `tailwind.config.js` → CSS `@theme`
- ⚠️ Posibles ajustes en JIT compilation
- ⚠️ Riesgo de regresiones visuales (mitigado con captura visual)
- ⚠️ Requiere re-testing exhaustivo

### Neutras
- 🔄 El cambio en `sitio/` puede afectar la arquitectura ITCSS (AGENTS.md §6)
- 🔄 El equipo debe aprender el nuevo modelo CSS-first

## Alternativas Consideradas

### A. Mantener Tailwind 3 + parchear todo
- ❌ Tailwind 3 está en mantenimiento mínimo
- ❌ Múltiples vulns requerirían patches manuales
- ❌ No hay parches oficiales para `braces` ni `node-forge`

### B. Migrar a CSS modules puro
- ❌ Cambio demasiado radical
- ❌ Pérdida de la productividad de Tailwind
- ❌ Reescritura completa del panel

### C. Migrar a UnoCSS u otro framework
- ❌ Curva de aprendizaje adicional
- ❌ Migración desde Tailwind igualmente costosa
- ❌ No resuelve el problema fundamental (vulns en deps)

### D. **Migrar a Tailwind 4 (ELEGIDA)**
- ✅ Solución oficial y mantenida
- ✅ Resuelve las vulns estructurales
- ✅ Camino de migración bien documentado
- ✅ Inversión a largo plazo

## Plan de Migración Detallado

### Sprint 1: Preparación
- [ ] Instalar `tailwindcss@4.3.3` y `@tailwindcss/vite@4.3.3`
- [ ] Eliminar `postcss.config.js` y `tailwind.config.js`
- [ ] Mover configuración a CSS con `@theme` y `@source`
- [ ] Validar build de `panel/` (más simple)

### Sprint 2: Migración de panel/
- [ ] Convertir clases utility-first usadas a sintaxis Tailwind 4
- [ ] Actualizar tests de Vitest
- [ ] Validar visualmente con captura
- [ ] Documentar cambios en CHANGELOG

### Sprint 3: Migración de sitio/
- [ ] Evaluar impacto en ITCSS (AGENTS.md §6)
- [ ] Decidir entre Tailwind 4 vs mantener vendorización
- [ ] Si se migra: pruebas exhaustivas con captura visual
- [ ] Validar 11 vistas de referencia

### Sprint 4: Cierre
- [ ] Eliminar deps antiguas (chokidar 3, fast-glob 3, micromatch 4)
- [ ] Auditar con `npm audit` — debe quedar 0 high
- [ ] Documentar lecciones aprendidas
- [ ] Celebrar 🎉

## Referencias

- [Tailwind CSS v4.0 Release Notes](https://tailwindcss.com/blog/tailwindcss-v4)
- [Tailwind v4 Upgrade Guide](https://tailwindcss.com/docs/upgrade-guide)
- [OWASP Top 10 — A06: Vulnerable Components](https://owasp.org/Top10/A06_2021-Vulnerable_and_Outdated_Components/)
- [CVE-2026-93687 — braces](https://github.com/advisories/GHSA-grv7-fg5c-xmjg)
- AGENTS.md §6 — Reglas de CSS en sitio/

## Notas de Revisión

Esta ADR será revisada en cada sprint para ajustar el plan según los hallazgos.
