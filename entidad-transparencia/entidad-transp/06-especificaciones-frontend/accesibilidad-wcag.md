# Accesibilidad WCAG 2.1 AA — Estrategia y Verificación

> **Marco:** WCAG 2.1 nivel AA (W3C Recommendation 2018-06-05) + Res. MinTIC 1519/2020 Anexo 3.
> **RNF trazables:** RNF-ACES-01, RNF-ACES-02.
> **Verificación:** axe-core (Playwright) + Lighthouse + auditoría manual anual.

---

## 1. Principios WCAG y su implementación

### 1.1 Perceptible

| Criterio | Nivel | Implementación |
|---|---|---|
| 1.1.1 Non-text Content | A | Atributo `alt` obligatorio en `<img>`; icono SVG con `aria-hidden="true"`; iconos interactivos con `aria-label` |
| 1.3.1 Info and Relationships | A | HTML semántico (`<header>`, `<nav>`, `<main>`, `<aside>`, `<footer>`, `<article>`); tablas con `<caption>` y `scope`; listas con `<ul>`/`<ol>`; headings jerárquicos |
| 1.3.2 Meaningful Sequence | A | Orden DOM coherente con presentación visual |
| 1.3.3 Sensory Characteristics | A | Instrucciones no dependen solo de color/forma/posición |
| 1.4.1 Use of Color | A | Contraste reforzado con texto/iconos, nunca solo color |
| 1.4.3 Contrast (Minimum) | AA | Texto ≥4.5:1; texto grande ≥3:1 (validado en tokens CSS) |
| 1.4.4 Resize Text | AA | Soporte de zoom 200% sin pérdida de funcionalidad |
| 1.4.5 Images of Text | AA | Evitar; usar texto real estilizado (Kit UI Nunito Sans) |
| 1.4.10 Reflow | AA | Sin scroll horizontal a 320 CSS px de ancho |
| 1.4.11 Non-text Contrast | AA | Componentes UI y estados focus con ≥3:1 |
| 1.4.12 Text Spacing | AA | Letter-spacing, line-height, word-spacing respetados |
| 1.4.13 Content on Hover or Focus | AA | Tooltip persistente hasta mouseleave/focusout |

### 1.2 Operable

| Criterio | Nivel | Implementación |
|---|---|---|
| 2.1.1 Keyboard | A | Toda funcionalidad accesible por teclado |
| 2.1.2 No Keyboard Trap | A | Foco se puede escapar con Tab/Shift+Tab desde cualquier elemento |
| 2.1.4 Character Key Shortcuts | A | Accesos por letra deshabilitables o remapeables |
| 2.2.1 Timing Adjustable | A | Sin timeouts restrictivos (sesión 30 min, advertencia 2 min antes) |
| 2.2.2 Pause, Stop, Hide | A | Carrusel con control Pausar/Reanudar (RF-01-014) |
| 2.3.1 Three Flashes | A | Sin contenido parpadeante >3 Hz |
| 2.4.1 Bypass Blocks | A | Skip link "Saltar al contenido principal" |
| 2.4.2 Page Titled | A | `<title>` único por página |
| 2.4.3 Focus Order | A | Orden DOM = orden visual |
| 2.4.4 Link Purpose (In Context) | A | Texto del enlace describe destino (sin "clic aquí") |
| 2.4.5 Multiple Ways | AA | Menú, breadcrumb, sitemap, buscador |
| 2.4.6 Headings and Labels | AA | Headings jerárquicos + labels descriptivos |
| 2.4.7 Focus Visible | AA | Outline 3 px Cobalt en `:focus-visible` |
| 2.5.1 Pointer Gestures | A | Gestos complejos tienen alternativa simple |
| 2.5.2 Pointer Cancellation | A | Click se activa en `mouseup` (no `mousedown`) |
| 2.5.3 Label in Name | A | `aria-label` incluye texto visible |
| 2.5.4 Motion Actuation | A | Sin funciones activadas por movimiento del dispositivo |
| 2.5.5 Target Size (Enhanced) | AAA | Botones ≥44×44 px (tomamos AAA como estándar) |

### 1.3 Understandable

| Criterio | Nivel | Implementación |
|---|---|---|
| 3.1.1 Language of Page | A | `<html lang="es-CO">` |
| 3.1.2 Language of Parts | A | `<span lang="en">` en palabras sueltas en otro idioma |
| 3.2.1 On Focus | A | Cambio de foco no dispara eventos inesperados |
| 3.2.2 On Input | A | Cambio de input no dispara navegación automática |
| 3.2.3 Consistent Navigation | AA | Menú y breadcrumbs consistentes entre páginas |
| 3.2.4 Consistent Identification | AA | Mismos iconos para misma función |
| 3.3.1 Error Identification | A | Errores en texto (no solo color) + `aria-invalid` + `aria-describedby` |
| 3.3.2 Labels or Instructions | A | Labels asociados a inputs con `<label for>` o `aria-label` |
| 3.3.3 Error Suggestion | AA | Sugerencias concretas de corrección |
| 3.3.4 Error Prevention (Legal, Financial) | AA | Confirmación antes de acciones destructivas |

### 1.4 Robust

| Criterio | Nivel | Implementación |
|---|---|---|
| 4.1.1 Parsing | A | HTML válido (Nuxt valida) |
| 4.1.2 Name, Role, Value | A | Roles ARIA correctos; estado (`aria-expanded`, `aria-selected`) comunicado |
| 4.1.3 Status Messages | AA | `aria-live="polite"` para toasts, "Loading…", resultados de búsqueda |

---

## 2. Tokens de contraste validados

Paleta del Kit UI GOV.CO con ratios verificados:

| Color fondo | Color texto | Ratio | Uso |
|---|---|---|---|
| `#FFFFFF` | `#1A1A1A` | 16.1:1 | Texto principal |
| `#FFFFFF` | `#0943B5` (Cobalt) | 8.6:1 | Enlaces, títulos |
| `#0943B5` | `#FFFFFF` | 8.6:1 | Botón primario, top bar |
| `#00568D` | `#FFFFFF` | 8.9:1 | Footer, texto oscuro sobre fondo oscuro |
| `#FEE697` | `#1A1A1A` | 14.8:1 | Alerta de cookies |
| `#1D3557` | `#FFFFFF` | 11.6:1 | Enlace visitado sobre blanco |
| `#DC2626` | `#FFFFFF` | 4.83:1 | Botón peligro (cumple AA para texto grande y no-texto) |
| `#16A34A` | `#FFFFFF` | 3.05:1 | Éxito (NO cumple AA para texto <18pt; usar solo fondo) |

**Implementación Tailwind:**
```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        govco: {
          cobalt: '#0943B5',
          celeste: '#00ADE7',
          oscuro: '#00568D',
          texto: '#1A1A1A',
          secundario: '#4C4C4C',
          delft: '#1D3557',
          alerta: '#FEE697',
          error: '#DC2626',
          exito: '#16A34A',
        },
      },
    },
  },
};
```

**Validación CSS:**
```css
/* Contraste reforzado en focus */
:focus-visible {
  outline: 3px solid #0943B5;
  outline-offset: 2px;
}

/* Modo alto contraste */
.acc-contraste-alto {
  background-color: #000 !important;
  color: #FFF !important;
}
.acc-contraste-alto a {
  color: #FFFF00 !important;
  text-decoration: underline !important;
}
```

---

## 3. Componentes accesibles (cheatsheet)

### 3.1 Skip Link

```vue
<!-- app/components/accesibilidad/SkipLink.vue -->
<script setup lang="ts">
// Componente que aparece al recibir foco
</script>

<template>
  <a href="#contenido-principal" class="skip-link">
    Saltar al contenido principal
  </a>
</template>

<style scoped>
.skip-link {
  position: absolute;
  top: -40px;
  left: 1rem;
  background: #0943B5;
  color: #fff;
  padding: 0.75rem 1.5rem;
  z-index: 10000;
  text-decoration: none;
  border-radius: 4px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
}
.skip-link:focus {
  top: 1rem;
}
</style>
```

### 3.2 Modal con focus trap y ARIA

```vue
<!-- app/components/ui/Modal.vue -->
<script setup lang="ts">
import { ref, watch } from 'vue';
import { useFocusTrap } from '~/composables/useFocusTrap';

interface Props {
  abierto: boolean;
  titulo: string;
  tamano?: 'sm' | 'md' | 'lg' | 'xl';
}
const props = withDefaults(defineProps<Props>(), { tamano: 'md' });
const emit = defineEmits<{ (e: 'cerrar'): void }>();

const modalRef = ref<HTMLElement | null>(null);
useFocusTrap(modalRef);

watch(() => props.abierto, (abierto) => {
  if (typeof document !== 'undefined') {
    document.body.style.overflow = abierto ? 'hidden' : '';
  }
});

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') emit('cerrar');
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="abierto"
        class="modal-overlay"
        @click.self="emit('cerrar')"
        @keydown="onKeydown"
      >
        <div
          ref="modalRef"
          role="dialog"
          aria-modal="true"
          :aria-labelledby="`modal-titulo-${titulo}`"
          class="modal-contenido"
          :class="`modal-${tamano}`"
        >
          <header class="modal-cabecera">
            <h2 :id="`modal-titulo-${titulo}`">{{ titulo }}</h2>
            <button
              type="button"
              aria-label="Cerrar modal"
              class="modal-cerrar"
              @click="emit('cerrar')"
            >
              <span aria-hidden="true">✕</span>
            </button>
          </header>
          <div class="modal-cuerpo">
            <slot />
          </div>
          <footer v-if="$slots.footer" class="modal-pie">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>
```

### 3.3 Tabla accesible

```vue
<script setup lang="ts">
interface Columna {
  clave: string;
  etiqueta: string;
  sortable?: boolean;
  ancho?: string;
}

interface Props {
  columnas: Columna[];
  filas: Array<Record<string, any>>;
  caption: string;
  orden?: { clave: string; direccion: 'asc' | 'desc' };
  cargando?: boolean;
}
const props = defineProps<Props>();
const emit = defineEmits<{
  (e: 'ordenar', columna: string): void;
  (e: 'seleccionar', fila: Record<string, any>): void;
}>();

function onSort(col: Columna) {
  if (col.sortable) emit('ordenar', col.clave);
}
</script>

<template>
  <table class="tabla-accesible" role="table">
    <caption class="sr-only">{{ caption }}</caption>
    <thead>
      <tr>
        <th
          v-for="col in columnas"
          :key="col.clave"
          :scope="'col'"
          :style="col.ancho ? { width: col.ancho } : {}"
          :aria-sort="
            orden?.clave === col.clave
              ? (orden.direccion === 'asc' ? 'ascending' : 'descending')
              : (col.sortable ? 'none' : undefined)
          "
        >
          <button
            v-if="col.sortable"
            type="button"
            class="tabla-orden-btn"
            @click="onSort(col)"
          >
            {{ col.etiqueta }}
            <span aria-hidden="true">
              {{ orden?.clave === col.clave
                ? (orden.direccion === 'asc' ? '▲' : '▼')
                : '↕' }}
            </span>
          </button>
          <template v-else>{{ col.etiqueta }}</template>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-if="cargando">
        <td :colspan="columnas.length" role="status" aria-live="polite">
          Cargando datos...
        </td>
      </tr>
      <tr v-else-if="filas.length === 0">
        <td :colspan="columnas.length">No hay datos para mostrar</td>
      </tr>
      <tr
        v-for="(fila, idx) in filas"
        v-else
        :key="idx"
        @click="emit('seleccionar', fila)"
      >
        <td v-for="col in columnas" :key="col.clave">
          {{ fila[col.clave] }}
        </td>
      </tr>
    </tbody>
  </table>
</template>

<style scoped>
.tabla-accesible {
  width: 100%;
  border-collapse: collapse;
}
.tabla-accesible th,
.tabla-accesible td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid #E5E5E5;
}
.tabla-accesible th {
  background: #F5F5F5;
  font-weight: 700;
}
.tabla-orden-btn {
  background: none;
  border: none;
  font-weight: 700;
  cursor: pointer;
  padding: 0;
}
</style>
```

### 3.4 Formulario accesible

```vue
<script setup lang="ts">
interface Campo {
  nombre: string;
  etiqueta: string;
  tipo: 'text' | 'email' | 'date' | 'select' | 'textarea' | 'file';
  requerido?: boolean;
  ayuda?: string;
  opciones?: Array<{ valor: string; etiqueta: string }>;
  errores?: string[];
}

interface Props {
  campos: Campo[];
}
const props = defineProps<Props>();
const valores = reactive<Record<string, any>>({});

function ariaDescribedBy(campo: Campo): string | undefined {
  const ids: string[] = [];
  if (campo.ayuda) ids.push(`ayuda-${campo.nombre}`);
  if (campo.errores?.length) ids.push(`error-${campo.nombre}`);
  return ids.length > 0 ? ids.join(' ') : undefined;
}
</script>

<template>
  <form novalidate>
    <div v-for="campo in campos" :key="campo.nombre" class="campo-form">
      <label :for="`campo-${campo.nombre}`">
        {{ campo.etiqueta }}
        <span v-if="campo.requerido" aria-label="obligatorio" class="req">*</span>
      </label>

      <p v-if="campo.ayuda" :id="`ayuda-${campo.nombre}`" class="ayuda">
        {{ campo.ayuda }}
      </p>

      <input
        v-if="campo.tipo === 'text' || campo.tipo === 'email' || campo.tipo === 'date'"
        :id="`campo-${campo.nombre}`"
        v-model="valores[campo.nombre]"
        :type="campo.tipo"
        :required="campo.requerido"
        :aria-required="campo.requerido"
        :aria-invalid="campo.errores?.length ? 'true' : 'false'"
        :aria-describedby="ariaDescribedBy(campo)"
      />

      <textarea
        v-else-if="campo.tipo === 'textarea'"
        :id="`campo-${campo.nombre}`"
        v-model="valores[campo.nombre]"
        :required="campo.requerido"
        :aria-required="campo.requerido"
        :aria-invalid="campo.errores?.length ? 'true' : 'false'"
        :aria-describedby="ariaDescribedBy(campo)"
      />

      <select
        v-else-if="campo.tipo === 'select'"
        :id="`campo-${campo.nombre}`"
        v-model="valores[campo.nombre]"
        :required="campo.requerido"
        :aria-required="campo.requerido"
        :aria-invalid="campo.errores?.length ? 'true' : 'false'"
        :aria-describedby="ariaDescribedBy(campo)"
      >
        <option v-for="op in campo.opciones" :key="op.valor" :value="op.valor">
          {{ op.etiqueta }}
        </option>
      </select>

      <input
        v-else-if="campo.tipo === 'file'"
        :id="`campo-${campo.nombre}`"
        type="file"
        :required="campo.requerido"
        :aria-required="campo.requerido"
        :aria-describedby="ariaDescribedBy(campo)"
      />

      <p
        v-if="campo.errores?.length"
        :id="`error-${campo.nombre}`"
        class="error"
        role="alert"
      >
        {{ campo.errores.join('. ') }}
      </p>
    </div>
  </form>
</template>
```

---

## 4. Tests automatizados de accesibilidad

### 4.1 axe-core con Playwright (CI)

```typescript
// tests/e2e/accesibilidad.spec.ts
import { test, expect } from '@playwright/test';
import AxeBuilder from '@axe-core/playwright';

const rutasCriticas = [
  '/',
  '/transparencia',
  '/transparencia/informacion-de-la-entidad',
  '/transparencia/normativa',
  '/directorio',
  '/noticias',
  '/buscar?q=plan',
  '/politicas/privacidad',
];

for (const ruta of rutasCriticas) {
  test(`A11y: ${ruta}`, async ({ page }) => {
    await page.goto(ruta);

    const accesibilidad = await new AxeBuilder({ page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    // Reportar todas las violaciones
    if (accesibilidad.violations.length > 0) {
      console.error(`Violaciones en ${ruta}:`, accesibilidad.violations);
    }

    // Cero violaciones serias o críticas
    const criticas = accesibilidad.violations.filter(
      v => v.impact === 'serious' || v.impact === 'critical'
    );
    expect(criticas).toEqual([]);
  });
}
```

### 4.2 Lighthouse en CI

```yaml
# .github/workflows/a11y.yml
- name: Lighthouse Accessibility
  uses: treosh/lighthouse-ci-action@v10
  with:
    urls: |
      https://staging.santamarta.gov.co/
      https://staging.santamarta.gov.co/transparencia
      https://staging.santamarta.gov.co/transparencia/normativa
    budgetPath: ./lighthouse-budget.json
    uploadArtifacts: true
```

```json
// lighthouse-budget.json
[
  {
    "path": "/*",
    "resourceSizes": [
      { "resourceType": "script", "budget": 100 },
      { "resourceType": "image", "budget": 300 }
    ],
    "timings": [
      { "metric": "interactive", "budget": 3500 },
      { "metric": "first-contentful-paint", "budget": 2000 }
    ],
    "scores": [
      { "score": "accessibility", "value": 0.95 }
    ]
  }
]
```

---

## 5. Auditoría manual anual

### 5.1 Plan de pruebas con usuarios

| Perfil | Tareas |
|---|---|
| Ciego (lector de pantalla JAWS/NVDA) | Navegar menú, abrir transparencia, descargar documento |
| Baja visión (zoom 200%) | Leer una noticia sin scroll horizontal |
| Sordo (sin audio) | Acceder a video del home (debe tener subtítulos) |
| Discapacidad motriz (solo teclado) | Llenar formulario de contacto |
| Discapacidad cognitiva (lenguaje claro) | Encontrar el Plan de Acción vigente |

### 5.2 Checklist del auditor

- [ ] Navegación completa con teclado (Tab/Shift+Tab/Enter/Espacio/Flechas/ESC).
- [ ] Lectores de pantalla: NVDA + Chrome, VoiceOver + Safari, TalkBack + Chrome Android.
- [ ] Zoom 200% sin pérdida de funcionalidad.
- [ ] Contraste en modo alto contraste de Windows.
- [ ] Subtítulos en todos los videos (transcripción sincronizada).
- [ ] Formularios: cada input con label; errores anunciados por lector.
- [ ] Tablas: caption, scope, navegación con flechas cuando aplica.
- [ ] Modales: focus trap funcional, ESC cierra, foco vuelve al disparador.
- [ ] Navegación consistente en todas las páginas.
- [ ] Idioma de la página y de partes en otro idioma marcado.

---

## 6. Recursos y formación del equipo

| Recurso | URL |
|---|---|
| WCAG 2.1 | https://www.w3.org/TR/WCAG21/ |
| WAI-ARIA Authoring Practices | https://www.w3.org/WAI/ARIA/apg/ |
| axe-core | https://github.com/dequelabs/axe-core |
| Lighthouse | https://developers.google.com/web/tools/lighthouse |
| Colorable (contrast checker) | https://colorable.jxnblk.com/ |
| WebAIM Contrast Checker | https://webaim.org/resources/contrastchecker/ |
| Inclusive Components (Heydon Pickering) | https://inclusive-components.design/ |
| A11y Project Checklist | https://www.a11yproject.com/checklist/ |

---

## 7. Tablero ITA — criterios de accesibilidad

Los siguientes ítems del tablero ITA se calculan automáticamente y validan este RNF:

- RNF-ACES-01: cada imagen con `alt`, cada video con subtítulos, cada enlace con texto descriptivo, foco visible, contraste mínimo, navegación con teclado.
- RNF-ACES-02: barra de accesibilidad visible, persistencia en localStorage, contraste 4 modos, tamaño letra 3 niveles.

Implementación en `ItaCalculatorService` (ver `04-diseno-bd/migraciones-seeders.md`):

```php
class ItaCalculatorService
{
    public function calcular(): array
    {
        return [
            'imagen_alt' => $this->validarImagenesConAlt(),
            'video_subtitulos' => $this->validarVideosConSubtitulos(),
            'enlace_descriptivo' => $this->validarEnlacesDescriptivos(),
            'foco_visible' => $this->validarFocoVisible(),
            'contraste_minimo' => $this->validarContraste(),
            'navegacion_teclado' => $this->validarNavegacionTeclado(),
            'barra_accesibilidad' => $this->validarBarraAccesibilidad(),
        ];
    }
}
```
