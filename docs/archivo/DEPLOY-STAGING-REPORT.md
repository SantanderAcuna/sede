# REPORTE DE DEPLOY A STAGING

**Fecha:** 2026-10-05
**Rama actual:** `feature/ci-cobertura-frontend`
**Commit:** `9658376`
**Estado:** ⚠️ PUSH BLOQUEADO POR REGLAS DEL REPO

---

## 1. COMMIT REALIZADO ✅

```
9658376 ci: agregar cobertura frontend y linters (Panel/Sitio)
```

### Archivos modificados
| Archivo | Líneas | Cambio |
|---------|--------|--------|
| `.github/workflows/ci.yml` | 343→377 | +34 |
| `AUDITORIA-CRUZADA-COMPLETA.md` | 0→855 | +855 (nuevo) |

---

## 2. ESTADO DEL PUSH ⚠️

### Intentos de push realizados:
1. `git push origin staging` → RECHAZADO
2. `git push origin feature/ci-cobertura-frontend` → RECHAZADO

### Causa del rechazo:
```
remote: - Changes must be made through a pull request.
remote: - refusing to allow an OAuth App to create or update workflow
         `.github/workflows/ci.yml` without `workflow` scope
```

**Restricción:** El token OAuth del usuario actual **no tiene scope `workflow`**, requerido para modificar archivos en `.github/workflows/`. Las reglas del repositorio de GitHub prohíben estos cambios sin ese scope.

---

## 3. VALIDACIONES LOCALES REALIZADAS ✅

### Sintaxis YAML
- ✅ YAML válido (validado con PyYAML)

### Estructura del workflow
| Job | Steps | Cambios |
|-----|-------|---------|
| Contrato | 3 | Sin cambios |
| Backend | 7 | Sin cambios |
| **Panel** | **7** | **+ Vitest coverage, + ESLint** |
| **Sitio** | **8** | **+ Vitest coverage, + Stylelint** |
| Pila | 3 | Sin cambios |
| Dependencias | 5 | Sin cambios |
| Credenciales | 4 | Sin cambios |

### Steps agregados
**Panel job:**
- ✅ `npx vitest run --coverage` con umbrales 95% (TEST-01..TEST-10)
- ✅ `npx eslint src --ext .vue,.ts --max-warnings 0` (Vue-01..Vue-10)

**Sitio job:**
- ✅ `npx vitest run --coverage` con umbrales 95% (TEST-01..TEST-10)
- ✅ `npx stylelint "app/assets/css/*.css"` (AGENTS.md §6)

---

## 4. CÓMO COMPLETAR EL PUSH

### Opción A: Token con scope `workflow`
```bash
# Configurar un nuevo token con scope "workflow"
gh auth login --scopes "repo,workflow"

# Luego hacer push
GIT_SSH_COMMAND="ssh -F /dev/null -o BatchMode=yes" git push origin feature/ci-cobertura-frontend

# Crear PR
gh pr create --base staging --title "ci: cobertura frontend + linters" --body "..."
```

### Opción B: Admin del repo aplica los cambios
1. Compartir el commit `9658376` con un admin
2. El admin hace push directo con scope workflow

### Opción C: Aplicar cambios manualmente via GitHub UI
1. Ir a https://github.com/SantanderAcuna/sede/edit/staging/.github/workflows/ci.yml
2. Pegar el contenido del archivo (377 líneas)
3. Commit directo en GitHub

---

## 5. PRÓXIMOS PASOS

1. **Generar un token con scope `workflow`** o pedir a un admin
2. **Push de la rama `feature/ci-cobertura-frontend`**
3. **Crear PR contra staging**
4. **Esperar a que el CI pase en staging** con los nuevos steps
5. **Revisar coverage report** en GitHub Actions
6. **Merge a staging**

---

**FIRMA:** Agente Cruzado
**FECHA:** 2026-10-05
**ESTADO:** ⚠️ COMMIT HECHO, PUSH BLOQUEADO POR SCOPE
