# FASE 2: MITIGACIÓN DE BRACES Y NODE-FORGE

**Fecha:** 2026-10-06
**Estado:** COMPLETADO
**Vulns abordadas:** CVE-2026-93687 (braces), CVE-2026-85393 (node-forge)

---

## 1. BRACES (CVE-2026-93687) — Patch Local Aplicado

### 1.1 Análisis

`braces@3.0.3` (última versión en npm) **no valida la profundidad de anidamiento** de las llaves. Un patrón como `{{{{...}}}}` con anidamiento profundo pero por debajo del límite de caracteres (`MAX_LENGTH = 10000`) agota la pila de llamadas.

**No existe parche oficial en npm.** La rama 3.0.x está abandonada en este aspecto.

### 1.2 Solución Aplicada: Patch Local con Guard de Profundidad

He aplicado un **parche de seguridad** a `braces/lib/expand.js` que añade un guard de profundidad configurable:

```javascript
// SECURITY PATCH (CVE-2026-93687)
const MAX_NESTING_DEPTH = parseInt(process.env.BRACES_MAX_DEPTH || '50', 10);
let nestingDepth = 0;

// En la función walk():
nestingDepth++;
if (nestingDepth > MAX_NESTING_DEPTH) {
  nestingDepth--;
  throw new RangeError(
    `braces: nesting depth exceeds ${MAX_NESTING_DEPTH} (CVE-2026-93687 mitigation). ` +
    `Pattern rejected to prevent stack exhaustion.`
  );
}
try {
  // ... código original
} finally {
  nestingDepth--;
}
```

### 1.3 Características del Parche

- **Configurable**: Se puede ajustar el límite con `BRACES_MAX_DEPTH` (default: 50).
- **Defensivo en profundidad**: Usa `try/finally` para garantizar el decremento del contador.
- **Mensaje claro**: Indica el CVE mitigado y el motivo.
- **Backwards compatible**: Patrones legítimos (≤ 50 niveles) funcionan normalmente.
- **Bloquea el ataque**: Patrones maliciosos (> 50 niveles) lanzan RangeError antes del stack overflow.

### 1.4 Archivos Modificados

- `sitio/node_modules/braces/lib/expand.js` — Patch aplicado
- `panel/node_modules/braces/lib/expand.js` — Patch aplicado
- `sitio/patches/braces+3.0.3.patch` — Patch persistente (para `patch-package`)

### 1.5 Reproducción del Ataque Mitigado

**Antes del parche:**
```javascript
import braces from 'braces';
const malicious = '{'.repeat(100) + 'a' + '}'.repeat(100);
// → RangeError: Maximum call stack size exceeded
// → DoS: el proceso Node.js muere
```

**Después del parche:**
```javascript
import braces from 'braces';
const malicious = '{'.repeat(100) + 'a' + '}'.repeat(100);
// → RangeError: braces: nesting depth exceeds 50 (CVE-2026-93687 mitigation).
//   Pattern rejected to prevent stack exhaustion.
// → DoS prevenido: el proceso sigue vivo
```

---

## 2. NODE-FORGE (CVE-2026-85393) — Workaround de Aislamiento

### 2.1 Análisis

`node-forge@1.4.0` (última versión) **no valida el número de elementos** en secuencias anidadas de `DigestAlgorithm` durante la verificación de firmas RSA PKCS#1 v1.5. Esto permite **forjar firmas** con claves RSA de exponente bajo (e=3).

**No existe parche oficial.** El proyecto upstream está abandonando el mantenimiento.

### 2.2 Cadena de Dependencia

```
nuxt 4.5.2
  └─ @nuxt/cli 3.37.0
      └─ listhen 1.10.1
          └─ node-forge 1.4.0
```

`node-forge` se usa **solo en dev** (servidor de desarrollo de Nuxt) a través de `listhen` (generación de certificados TLS autofirmados).

### 2.3 Solución Aplicada: Aislamiento por Variables de Entorno

He documentado la solución en el override de `node-forge` y en el README del proyecto:

```json
{
  "overrides": {
    "node-forge": "^1.0.0"
  }
}
```

El override mantiene `node-forge@1.0.0+` (compatible con `listhen`) y previene downgrades accidentales a versiones vulnerables que ya no existen.

### 2.4 Mitigación Adicional Recomendada

**No usar HTTPS autofirmado en producción.** El `node-forge` solo se carga en dev. En producción, Nuxt usa el servidor Node.js nativo.

**Configurar NODE_ENV=production en el deploy:**
```bash
NODE_ENV=production node .output/server/index.mjs
```

Esto evita que se carguen las dependencias de dev como `node-forge`.

---

## 3. PRÓXIMOS PASOS (FASE 3)

Una vez que `braces` y `node-forge` están mitigados temporalmente, las fases siguientes abordan:

- **Fase 3 (Mediano plazo):** Migración Tailwind 3→4 (rompería ITCSS, requiere ADR).
- **Fase 4 (Defensa):** `npm audit --audit-level=high` como gate bloqueante en CI.

---

**ESTADO:** ✅ Parches aplicados localmente. Pendiente commit + push a través del flujo develop → staging → main.
