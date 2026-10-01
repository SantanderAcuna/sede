# DESIGN.md — Sede Electrónica Alcaldía Distrital de Santa Marta

> **Versión:** 1.0
> **Fecha:** 2025-01-15
> **Superficie:** `sitio/` (portal público Nuxt 4)
> **Auditoría:** `auditoria-sede.md` (3.ª edición)

---

## 1. Dirección visual

### 1.1 Mundo elegido

**Gobierno digital colombiano —的形式 formal y accesible.**

El portal de una alcaldía distrital no es una landing page ni una app de consumo: es un servicio público que debe proyectar **confianza institucional**, **transparencia** y **accesibilidad universal**. El diseño se ancla en:

- La tradición visual de las sedes electrónicas gubernamentales colombianas (estándar GOV.CO)
- El Kit UI oficial v9.2 del Estado colombiano
- Los criterios de la Resolución 1519 de 2020 (accesibilidad WCAG 2.1 AA)

### 1.2 Paleta

| Rol | Color | Hex | Uso |
|---|---|---|---|
| Primario | Cobalt | `#0943B5` | Barra superior, enlaces, botones primarios |
| Entidad (celeste) | Azul celeste A | `#00ADE7` | Banda inferior del pie, carrusel |
| Entidad (oscuro) | Azul noche | `#00568D` | Sombras, texto sobre celeste |
| Fondo principal | Blanco | `#FFFFFF` | Superficie de contenido |
| Fondo secundario | Gris claro | `#F5F5F5` | Secciones destacadas |
| Texto principal | Negro | `#1A1A1A` | Cuerpo de texto |
| Texto secundario | Matterhorn | `#4C4C4C` | Metadatos, información de contacto |
| Alto contraste (enlaces) | Delft | `#1D3557` | Estado `:visited` de enlaces |
| Alerta | Amarillo | `#FEE697` | Fondos de notificación |
| Error | Rojo | `#DC2626` | Estados de error |
| Éxito | Verde | `#16A34A` | Estados de éxito |

### 1.3 Tipografía

| Rol | Fuente | Peso | Tamaño base |
|---|---|---|---|
| Títulos | Nunito Sans | 600–800 | 22–32px |
| Cuerpo | Verdana | 400 | 16px |
| Navegación | Nunito Sans | 600 | 15px |
| Botones | Nunito Sans | 600 | 15px |
| Metadatos | Verdana | 400 | 14px |

**Restricción de línea:** 60–80 caracteres por línea (RNF-B1-031). El contenedor de contenido usa `col-lg-8` con `max-width: 65ch` en párrafos.

### 1.4 Sistema de espaciado

- **Unidad base:** 8px
- **Espaciado de componentes:** 16px, 24px, 32px, 48px
- **Margen de sección:** 48px–64px
- **Padding interno de tarjetas:** 24px

### 1.5 Iconografía

**Biblioteca:** SVG sprites del Kit UI GOV.CO (`govco-svg`)

Todos los iconos son SVG inline, nunca emoji ni Unicode. Los iconos del Kit se invocan con la clase `govco-{nombre}` en un `<span>` con `aria-hidden="true"`.

### 1.6 Sombras y profundidad

- **Tarjetas elevadas:** `box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.08)`
- **Modales:** `box-shadow: 0 1rem 3rem rgba(0,0,0,0.3)`
- **Banner de cookies:** `box-shadow: 0 -0.25rem 1rem rgba(0,0,0,0.15)`

---

## 2. Componentes

### 2.1 BarraSuperior

- **Apariencia:** franja azul cobalt de 56px de altura
- **Contenido:** logo GOV.CO que enlaza a `https://www.gov.co/`
- **Estado hover:** fondo ligeramente más oscuro
- **Accesibilidad:** `aria-label` descriptivo

### 2.2 BarraAccesibilidad

- **Posición:** fija a la derecha, centrada verticalmente
- **Controles:** contraste (toggle), reducir letra, aumentar letra
- **Persistencia:** preferencias guardadas en `localStorage` (D-11)
- **Visibilidad:** oculta en pantallas < 992px (`d-none d-lg-flex`)
- **Foco visible:** contorno amarillo de 3px en modo alto contraste

### 2.3 CabeceraGovco

- **Elementos:** logo de la entidad (enlazado a inicio), buscador, botón de sesión
- **Logo entidad:** máximo 80px de alto, proporcional
- **Buscador:** ancho mínimo 27 caracteres, campo con autocompletado
- **Botón sesión:** borde cobalt, radio 1.5rem, área táctil ≥ 44px

### 2.4 MenuNavegacionGovco

- **Estructura:** hasta 7 ítems de primer nivel, máximo 2 niveles de submenú
- **Despliegue:** al pulsar, no al pasar el ratón
- **Indicador activo:** texto en negrita y subrayado
- **ARIA:** `aria-expanded`, `aria-controls`, `aria-haspopup`, `aria-label`
- **Teclado:** flechas, Inicio/Fin, Escape para cerrar

### 2.5 CarruselGovco

- **Autoplay por defecto:** `false` (pausado) — **D-12 corregido**
- **Controles:** indicadores, flechas, botón Play/Stop
- **Contraste:** todos los controles en blanco sobre cobalt (8,46:1)
- **Animación:** `prefers-reduced-motion` detiene el movimiento automático
- **ARIA:** `aria-live="polite"` cuando está pausado, `role="region"`

### 2.6 PiePaginaGovco

- **Banda superior:** información de contacto, logos de marca país
- **Banda inferior:** logos GOV.CO + Colombia.CO, texto informativo
- **Logos:** alineados a la izquierda, asociados visualmente
- **Colores:** banda inferior en azul celeste `#00ADE7` con texto negro (8,12:1)

### 2.7 BannerCookies (NUEVO — D-01)

- **Posición:** fija en la parte inferior, por encima del contenido
- **Categorías:** estrictamente necesarias (siempre activas), analítica, preferencias
- **Acciones:** Aceptar todas, Rechazar opcionales, Guardar preferencias
- **Persistencia:** consentimiento guardado en `localStorage` con versión y fecha
- **Caducidad:** 12 meses o cambio de política de cookies

### 2.8 ModalAvisoSalida (NUEVO — D-02)

- **Disparador:** clic en enlace externo con `target="_blank"`
- **Lista blanca:** gov.co, scd.gov.co, secop.gov.co, suin.gov.co, etc.
- **Contenido:** nombre del destino, entidad responsable, URL, mensaje contextual
- **Cierre:** botón Cancelar, tecla Escape, clic fuera
- **Enfoque:** atrapado en el modal, restaurado al cerrar

### 2.9 VolverArriba

- **Posición:** fija inferior derecha
- **Visibilidad:** aparece al 75% del scroll
- **Animación:** `prefers-reduced-motion` elimina la animación
- **Accesibilidad:** botón con `aria-label` descriptivo

### 2.10 Página de Accesibilidad — Declaración de Conformidad (D-23)

**Propósito:** Página que implementa los 12 elementos mínimos del Anexo 1 num. 9.3 de la Resolución 1519 de 2020.

**Estructura de contenido:**
1. Entidad + NIT ✅
2. Sede Electrónica (URL) ✅
3. Norma de referencia (Res. 1519 + WCAG 2.1 AA) ✅
4. Fecha de evaluación → Pendiente de auditoría
5. Alcance (URLs o sitemap) → Pendiente de auditoría
6. Herramientas utilizadas + versión → Pendiente de auditoría
7. Auditor → Pendiente de auditoría
8. Estado de cumplimiento → Pendiente de auditoría
9. Criterios no aplicables → Pendiente de auditoría
10. Excepciones documentadas → Pendiente de auditoría
11. Hallazgos pendientes → Pendiente de auditoría
12. Fecha próxima revisión (≤12 meses) → Pendiente de auditoría

**Diseño de la tabla de conformidad:**
- Elementos completados: fondo verde suave (#f0fdf4), badge verde
- Elementos pendientes: fondo amarillo suave (#fefce8), badge amarillo
- Badges con `bg-success` / `bg-warning text-dark`

**Estados de badges:**
- `bg-success` → elemento completado
- `bg-warning text-dark` → pendiente de auditoría

### 2.11 Página 404 — Error personalizado (RF-B1-007)

**Propósito:** Página de error 404/500 que ofrece ≥3 opciones de navegación cuando el ciudadano llega a una dirección inexistente.

**Opciones de navegación (≥3 exigidas):**
1. Volver a la portada — enlace principal con icono de casa
2. Buscar en la sede — enlace al buscador
3. Mapa del sitio — enlace al mapa
4. Secciones populares — Transparencia, Servicios, Participa (enlaces secundarios)

**Diseño:**
- Icono de alerta centrado con mensaje descriptivo
- Código de estado visible para quien deba reportarlo
- Tarjetas de navegación con icono + título + descripción
- Enlaces de sección popular con borde cobalt
- Texto adicional indicando páginas en preparación

**Estados de error:**
- 404: "Página no encontrada" — la dirección no existe
- Otro: "No se pudo completar la operación" — error genérico

### 2.12 Galería de aplicaciones en cabecera (RF-B3-068)

**Propósito:** Galería desplegable en la cabecera con acceso rápido a portales externos (Portal GOV.CO, Carpeta Ciudadana SECOP, CIIU DIAN).

**Ubicación:** Slot `acciones` de `CabeceraGovco` (junto al enlace "Iniciar sesión").

**Aplicaciones configuradas:**
| Aplicación | URL | Icono Kit UI |
|---|---|---|
| Portal del Estado Colombiano | https://www.gov.co | govco |
| Carpeta Ciudadana | https://www.secop.gov.co | carpeta |
| CIIU — Clasificación Industrial | https://www.dian.gov.co | ciiu |

**Estados:**
- Cerrado: botón con flecha hacia abajo
- Abierto: bandeja con rejilla 3 columnas, navegación con flechas (↑↓←→)
- Entrada activa: icono de verificación
- Navegación por teclado: Enter abre, Esc cierra, flechas mueven foco

**Dominios en lista blanca (no muestran modal de aviso):**
`gov.co`, `secop.gov.co`, `secopii.gov.co`, `suin.gov.co`, `dian.gov.co`

### 2.13 Mapa del sitio autoactualizado (RF-B1-010)

**Propósito:** El mapa del sitio se genera automáticamente desde la configuración centralizada de rutas (`config/sitemap.ts`), garantizando que siempre refleja la navegación real.

**Componentes:**
- `app/config/sitemap.ts` — configuración centralizada de todas las rutas públicas
- `app/pages/mapa-del-sitio.vue` — mapa HTML para ciudadanos
- `server/routes/sitemap.xml.ts` — sitemap.xml para buscadores

**Estructura de rutas en config:**
- `rutasSueltas[]` — rutas de primer nivel con metadatos (publicada, orden, prioridad, frecuencia)
- `secciones[]` — grupos temáticos con sub-enlaces (p. ej. Participa con sus 6 subcategorías)
- Políticas — las 5 políticas del pie de página

**Sincronización automática:**
Cuando se añade, modifica o elimina una ruta en `config/sitemap.ts`:
- El menú de navegación se actualiza automáticamente
- El mapa del sitio HTML se actualiza automáticamente
- El `sitemap.xml` se actualiza automáticamente

---

## 3. Estados de componentes

### 3.1 Estados de enlaces

| Estado | Color | Decoración |
|---|---|---|
| Normal | `#0943B5` (cobalt) | Subrayado |
| Hover | `#4672C8` (havelock blue) | Subrayado |
| **Visitado (NUEVO — D-25)** | `#1D3557` (delft) | Subrayado |
| Foco | Contorno cobalt 2px | — |
| Active | `#072f6e` (cobalt oscuro) | — |

### 3.2 Estados de botones

| Estado | Fondo | Borde | Texto |
|---|---|---|---|
| Normal | transparent | cobalt | cobalt |
| Hover | `#e5ecf8` (solitude) | cobalt | cobalt |
| Primary | `#0943B5` | `#0943B5` | blanco |
| Primary hover | `#072f6e` | `#072f6e` | blanco |

### 3.3 Estados de formulario

| Estado | Borde | Foco |
|---|---|---|
| Normal | `#ced4da` | — |
| Foco | `#0943B5` | Glow cobalt |
| Error | `#dc2626` | Glow rojo |
| Válido | `#16a34a` | — |
| Deshabilitado | `#e9ecef` | — |

---

## 4. Responsividad

### 4.1 Puntos de quiebre

| Breakpoint | Ancho | Comportamiento |
|---|---|---|
| XS | < 576px | Una columna, menú hamburguesa |
| SM | 576–767px | Dos columnas, menú hamburguesa |
| MD | 768–991px | Tres columnas, menú colapsado |
| LG | 992–1199px | Menú expandido |
| XL | ≥ 1200px | Contenido máximo 1140px centrado |

### 4.2 Reflujo a 320px

- **Buscador:** concede espacio con `flex: 1 1 0`, `min-width: 0`
- **Miga de pan:** nombres largos en varias líneas con `overflow-wrap: anywhere`
- **Tarjetas:** pasan a ancho completo

### 4.3 Objetivos táctiles

**Mínimo 44×44px** en todos los controles interactivos (CAG-23):
- Botones de navegación del carrusel
- Enlaces del pie
- Campos de formulario
- Controles de accesibilidad

---

## 5. Accesibilidad

### 5.1 Navegación por teclado

| Elemento | Tecla | Acción |
|---|---|---|
| Menú | ↑/↓ | Navegar entre ítems |
| Menú | Enter/Espacio | Abrir submenú |
| Menú | Escape | Cerrar submenú, devolver foco |
| Carrusel | ←/→ | Cambiar diapositiva |
| Modal | Tab | Navegar entre controles |
| Modal | Escape | Cerrar modal |
| Enlace skip | Enter | Saltar al contenido |

### 5.2 Contraste mínimo

| Elemento | Ratio mínimo | Valor |
|---|---|---|
| Texto normal sobre fondo blanco | 4,5:1 | `#0943B5` sobre `#FFFFFF` = 8,46:1 |
| Texto grande (≥18px) | 3:1 | `#4C4C4C` sobre `#FFFFFF` = 7,89:1 |
| Foco visible | 3:1 | Contorno `#ffffff` sobre `#0943B5` |
| Alto contraste | 7:1 | Texto `#ffff00` sobre `#000000` = 19:1 |

### 5.3 ARIA obligatorio

| Componente | Atributos ARIA |
|---|---|
| Botón circular de accesibilidad | `aria-haspopup="dialog"`, `aria-expanded`, `aria-controls`; nombre accesible en texto recortado, no en `aria-label` |
| Panel de accesibilidad | `<dialog>` nativo + `aria-labelledby`; grupos con `<fieldset>`/`<legend>` y títulos; interruptores con `role="switch"`; una sola región `role="status" aria-live="polite"` |
| Menú de navegación | `aria-expanded`, `aria-controls`, `aria-haspopup`, `aria-label` |
| Carrusel | `role="region"`, `aria-roledescription="carrusel"`, `aria-live` |
| Migas de pan | `aria-current="page"` en último nivel |
| Breadcrumb | `aria-label="Breadcrumb"` |
| Enlace skip | `href="#contenido-principal"`, `tabindex="-1"` |

### 5.3.1 Botón circular de accesibilidad

| Propiedad | Valor |
|---|---|
| Forma | Círculo (`border-radius: 50%`, ancho = alto) |
| Posición | `fixed`, `top: 50%`, `translateY(-50%)`, pegado al borde derecho |
| Tamaños | 56 px (≥992) · 52 px (768–991) · 48 px (<768) · 46 px (pantallas bajas) — siempre ≥44 px |
| Colores | Fondo Cobalt `#0943B5`, icono blanco (8,46:1), borde blanco, sombra de elevación |
| Foco | Doble anillo: `#1A1A1A` interior + `#FFBF00` exterior |
| Icono | SVG en línea con `fill="currentColor"` — sobrevive al modo de alto contraste, que fuerza `background-color: transparent` |
| Franja reservada | `#contenido-principal { padding-right: 3.5rem }` por debajo de 576 px, para no tapar texto |
| Independencia | No consulta dónde está «Volver arriba»; ocupan franjas distintas del borde derecho |

### 5.4 Alto contraste

**Clase:** `.contraste-govco`

| Elemento | Fondo | Texto | Borde |
|---|---|---|---|
| Todo | `#000000` | `#FFFFFF` | `#FFFFFF` |
| Enlaces y botones | — | `#FFFF00` (amarillo) | — |
| Foco | — | — | `#FFFF00` 3px |
| Imágenes | — | — | Borde 1px blanco |
| Botón de accesibilidad | `#000000` | `#FFFFFF` | `#FFFFFF` |
| Panel de accesibilidad | `#000000` | `#FFFFFF` | `#FFFFFF` |

### 5.5 Modos de contraste: dónde se aplica el `filter`

Los modos **Colores invertidos** (`.contraste-inverso-govco`) y **Escala de grises**
(`.contraste-grises-govco`) aplican `filter` a `.contenido-filtrable`, un envoltorio
que contiene la página pero **no** los controles flotantes.

| Regla | Motivo |
|---|---|
| El filtro va en el envoltorio, no en `html`/`body`/`#__nuxt` | Un `filter` crea bloque contenedor de los descendientes con `position: fixed`: en la raíz, desancla todos los flotantes |
| El filtro es **uno solo**, no uno por sección | Cada `filter` crea un contexto de apilamiento; con uno por bloque, el contenido taparía los desplegables del menú |
| Imágenes, vídeo y logotipos se re-invierten | Un negativo de una fotografía institucional no comunica nada; el logotipo de GOV.CO es marca normada |
| Los controles flotantes quedan fuera | Conservan su posición **y su orden en el documento**; además no se invierten sus colores |

---

## 6. Animación y movimiento

### 6.1 Principios

- **Una authored moment:** no efectos dispersos
- **Exponential ease-out:** desde un estado visible, no desde invisible
- **prefers-reduced-motion:** detiene toda animación automáticamente

### 6.2 Transiciones aplicadas

| Elemento | Propiedad | Duración | Easing |
|---|---|---|---|
| Hover de enlaces | `color` | 150ms | ease |
| Hover de botones | `background-color`, `border-color` | 150ms | ease |
| Foco visible | `outline` | instantáneo | — |
| Banner de cookies | `transform` (slide-up) | 300ms | ease-out |
| Modal | `opacity`, `transform` | 200ms | ease-out |

---

## 7. Decisions contracts

### 7.1 Banner de cookies

**THESIS:** El banner de cookies debe informar sin interrumpir, ofreciendo control real sobre las categorías.

**OWN-WORLD:** Fondo blanco con franja cobalt superior, tarjetas por categoría, tres botones de acción.

**FIRST VIEWPORT:** Banner fijo en la parte inferior, 100% de ancho, con tres acciones claras.

### 7.2 Modal de aviso de salida

**THESIS:** Un usuario que está a punto de salir de la sede debe entender a dónde va y confirmar.

**OWN-WORLD:** Modal centrado con fondo oscurecido, icono de alerta, información del destino y dos acciones.

**FIRST VIEWPORT:** Fondo oscurecido + modal centrado con mensaje de advertencia y botones Confirmar/Cancelar.

### 7.3 Carrusel

**THESIS:** El carrusel presenta secciones del sitio sin motion no solicitado; comienza pausado.

**OWN-WORLD:** Barra de controles en cobalt debajo de la imagen, controles blancos, indicadores circulares.

**FIRST VIEWPORT:** Imagen + leyenda centrada + barra de controles inferior en cobalt.

---

## 8. Antipatterns evitados

1. **No carrusel con autoplay al cargar** — violate WCAG 2.1 y molesta a usuarios con movimiento reducido
2. **No modal para todo** — interrupciones injustificadas destruyen flujo de tarea
3. **No `:visited` sin distinguir** — viola RF-B1-082
4. **No gradientes en texto** — decoración que distrae de contenido
5. **No botones sin área táctil de 44px** — fails CAG-23 en móvil
6. **No enlaces externos sin aviso** — violate RF-B1-071

---

*Este documento se genera a partir del código implementado y debe mantenerse sincronizado.*
