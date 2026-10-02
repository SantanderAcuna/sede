# Auditoría del panel administrativo

**Objeto:** `panel/` (superficie administrativa: Vue 3 + TypeScript + Tailwind, Vite, servida en
`/admin/`), contra los hallazgos **D-04, D-05, D-14, D-16, D-33, D-34, D-42 y D-47** de
[auditoria-sede.md](auditoria-sede.md).
**Alcance:** solo el panel. El sitio público se audita en `auditoria-sede.md`; las puertas de
calidad, en [auditoria-puertas.md](auditoria-puertas.md).
**Método:** lectura de cada fichero citado, `grep` de las cadenas prohibidas, compilación real
(`vue-tsc -b && vite build`), y **cálculo de los contrastes** con la fórmula de luminancia relativa
de WCAG 2.1 sobre los valores hexadecimales declarados.
**Naturaleza:** verificación independiente. No modifica nada: comprueba lo que otro agente dejó
escrito y busca lo que no dijo.

---

## 1. Veredicto

**Los ocho hallazgos están corregidos en código y la corrección es sólida.** La compilación pasa
—incluida la comprobación de tipos, que es la parte que suele fallar—, las afirmaciones falsas
desaparecieron de la interfaz y las tres guardias de navegación se aplican de verdad.

Pero la verificación independiente encontró **tres cosas que el informe del ejecutor no dice**, y
las tres son del mismo tipo: **contrastes por debajo del mínimo en piezas que nadie revisó porque
no estaban en el encargo**. Se detallan en la sección 4.

| Resultado | Cuántos |
|---|---|
| Hallazgos verificados como corregidos | **8 de 8** |
| Correcciones comprobadas con compilación | 8 |
| Correcciones **no** verificables visualmente | 1 (D-33) |
| Residuos medidos por esta auditoría | **3** — R-P1 y R-P2 **ya corregidos** (§4), R-P3 es de método |

---

## 2. Verificación hallazgo por hallazgo

### D-04 — El panel no autenticaba y afirmaba que sí ✅

| Qué se comprobó | Evidencia |
|---|---|
| Existen las guardias que antes no existían | `src/router/index.ts:152-174`: un `beforeEach` con las tres reglas —`requiereSesion` → entrada, `permiso` → sin permiso, `soloInvitados` → panel— y sin excepciones «para la demo». |
| Hay un almacén de sesión, y está vacío | `src/stores/sesion.ts` (nuevo). Es la costura para la identidad real; hoy `iniciada` es siempre `false`. |
| La afirmación falsa de doble factor salió de la interfaz | `grep` de «doble factor» en `src/` devuelve **solo tres comentarios** que explican que se retiró (`EntrarView.vue:11,75`, `AuthLayout.vue:17`). Ninguna cadena visible. |
| Ya no hay «Administrador» ni «Sesión activa» ni iniciales inventadas | `grep` sin resultados en `src/`. |
| El punto rojo de notificaciones sin dato se retiró | Sin coincidencias en `AdminLayout.vue`. |

**Consecuencia buscada y real:** con el almacén vacío, **el panel es inalcanzable desde el
navegador** más allá de `/admin/acceso`. Es la opción honesta: una guardia decorativa habría dejado
18 módulos abiertos fingiendo estar protegidos. Queda dicho para que nadie lo interprete como una
avería.

### D-42 — Permisos por módulo ✅

- `src/config/permisos.ts` declara el mapa `ruta → permiso` **una sola vez** y lo consumen la
  guardia y el menú (el fichero lo explica: con dos listas, el día que una cambie la otra miente).
- La nomenclatura `modulo.accion` (`pqrsd.ver`, `tramites.ver`, `usuarios.gestionar`…) es la que
  viajará al backend.
- El menú lateral filtra los grupos por permiso (`AdminLayout.vue`) en lugar de ofrecer 18 módulos
  a cualquiera.
- Acierto de criterio que conviene subrayar: el comentario del fichero deja escrito que **la
  interfaz no es el control de seguridad** y que el backend debe volver a comprobar el permiso en
  cada petición.

### D-05 — Cifras inventadas en el tablero ✅

- `src/views/admin/InicioView.vue`: las cifras (287, 34, 1 842, 94 %), la insignia «Sistema
  operativo» y el semáforo de salud («API Gateway OK», «Conector RNEC Lento»…) se sustituyeron por
  **estados vacíos declarados**, con `EmptyState.vue` (que hasta ahora no tenía consumidor).
- `grep` de `287`, `1842`, `94 %`, `Sistema operativo`, `API Gateway` en `src/` e `index.html`:
  **cero resultados**.
- Es exactamente lo que pedía RF-B1-078 («arranca en cero, sin datos mock») y lo contrario de lo
  que había.

### D-14 — Identidad visual ✅ (con una divergencia declarada)

| Qué se comprobó | Evidencia |
|---|---|
| El cobalto es el del Kit | `tokens.css:18` → `--color-gov-blue: #0943B5`; `:27` → `--color-state-blue: #0943B5`. |
| El `theme-color` coincide | `index.html:16` → `#0943B5`. |
| Ya no se llaman «oficiales» | `tokens.css:1-12`: el comentario declara que el azul está alineado y que **la tipografía es propia y la decisión de cambiarla sigue pendiente**. |
| Temas muertos retirados | `grep -c 'data-brand' tokens.css` → **0**. |
| Contraste de la paleta nueva (medido aquí) | Blanco sobre `#0943B5`: **8,46:1** · texto cobalto sobre el secundario `#E9EEF9`: **7,28:1** · `#06307F` sobre blanco: **12,09:1**. Los tres pasan AA con holgura. |

**Divergencia que sigue en pie y está bien dejada en pie:** las tipografías del panel (Inter,
Montserrat, JetBrains Mono) no son las del Kit (Nunito Sans, Verdana). Cambiarlas exige los
archivos de fuente y una decisión de ADR; el valor del arreglo está en que **ya no se disfraza de
conformidad**.

### D-16 — Accesibilidad del panel ✅

| Componente | Qué se comprobó |
|---|---|
| `DataTable.vue` | `:86` declara `aria-sort` en el `<th>` y `:92` mete el control en un `<button>` → ordenable **con teclado y anunciado**. `:151` un `<nav aria-label="Paginación de la tabla">` y `:172` `aria-current="page"` en la página activa, con números además de anterior/siguiente. |
| `BaseModal.vue` | `:52` cierra con `Escape`; `:94` mueve el foco al abrir; `:99` **lo devuelve** al disparador al cerrar; `:104` escucha en `window` (no en el contenedor, que es lo que falla cuando el foco se escapa); `:129` `aria-labelledby` con respaldo cuando no hay título. |
| `AccessibilityBar.vue` | `Escape`, foco inicial, devolución del foco, `type="button"` y `aria-controls`. |
| `AdminLayout.vue` | `:255` enlace «Saltar al contenido principal» como primer elemento enfocable; `:475` `<main id="contenido-principal" tabindex="-1">`; `:398,404` la miga marca la página actual con `aria-current="page"` en lugar de enlazarse a sí misma. `main.css` define `.salto-contenido` con recorte (`clip`), nunca `display:none`. |

### D-34 — La paleta de comandos imprimía el nombre del icono ✅

- `CommandPalette.vue:137` → `<FaIcon v-if="c.icon" :icon="c.icon" />`. El texto «gauge-high» ya no
  se imprime.
- `:72` cierra con `Escape` y `:99-100` registra y retira el escucha.
- El comentario de `:133` deja constancia del defecto anterior, que es la forma correcta de
  documentar una regresión corregida.

### D-47 — Título del producto ✅

- `index.html:20` y `router/index.ts:48` coinciden: **`SGDI · Alcaldía Distrital de Santa Marta`**,
  y el `afterEach` (`:171`) compone el título con el mismo valor.

### D-33 — El panel no era responsive ⚠️ **corregido en código, sin verificación visual**

- Se verificó que el mecanismo existe y es correcto: `AdminLayout.vue:209` estado del menú,
  `:259-262` fondo que cierra al pulsar, `:273-274` la barra se desplaza fuera de pantalla por
  debajo de `lg` (`-translate-x-full lg:translate-x-0 lg:static`), `:386` el botón declara
  `aria-expanded` y `aria-controls`.
- **Lo que no se pudo comprobar:** el aspecto real a 768 px. Con la guardia de D-04 activa y el
  almacén de sesión vacío, la disposición administrativa no se puede abrir en un navegador; y
  falsear una sesión para mirarla habría convertido esta auditoría en una prueba de una pantalla que
  no existe. Queda como **pendiente de una comprobación visual** cuando haya autenticación.

---

## 3. Código muerto y piezas retiradas

- Se eliminaron `feedback/Skeleton.vue`, `base/BaseTimeline.vue` y `domain/StatusBadge.vue`.
  Comprobado: **ninguna referencia** en `src/`.
- Se conservan a propósito `base/DataTable.vue` y `base/BaseModal.vue`, hoy sin consumidor: son las
  piezas base que consumirán los 18 módulos pendientes (D-15) y D-16 manda tenerlas correctas.
- **Observación de esta auditoría:** `StatusBadge.vue` incluía el mapeo de estados de un caso
  (radicado, en trámite, resuelto…), que los módulos de PQRSD y trámites necesitarán. Está en el
  historial de Git, pero conviene saber que esa lógica ya se escribió una vez y se retiró.

---

## 4. Residuos que la verificación independiente encontró (y el informe del ejecutor no)

Los tres son de contraste, se midieron aquí y **ninguno estaba en el encargo**: son el precio de
haber corregido el cobalto sin recorrer la paleta entera. **R-P1 y R-P2 se corrigieron después**
—con autorización expresa y solo esos—; el estado de cada uno se indica en su apartado.

### R-P1 · El botón de cerrar de la barra de accesibilidad no llegaba al 3:1 ✅ **corregido**

- `src/components/base/AccessibilityBar.vue:124`: el botón de cerrar el panel usa
  `text-slate-400` (**#94A3B8**), que sobre blanco da **2,56:1**.
- WCAG 1.4.11 exige **≥3:1** para componentes de interfaz, y este icono es **la única señal visual**
  del botón (el nombre accesible está en `aria-label`, que resuelve la parte de lectores de pantalla,
  no la visual).
- Además mide **24 × 24 px** (`h-6 w-6`), por debajo de los 44 px de RNF-B3-005.
- **Corrección aplicada:** `AccessibilityBar.vue:124` → `text-slate-500` (#64748B, **4,76:1**, por
  encima del 3:1 de 1.4.11 y del 4,5:1 de 1.4.3) y el control a `h-11 w-11` (**44 × 44 px**, el
  mínimo de RNF-B3-005). Verificado: `npm run build` del panel termina en 0.

### R-P2 · Tres tokens de la paleta incumplían si algún día se usan como texto ✅ **corregido**

Medidos sobre blanco:

| Token | Valor | Contraste | Uso hoy |
|---|---|---|---|
| `--color-text-soft` | `#94A3B8` | **2,56:1** | ninguno como texto (verificado con `grep`) |
| `--color-state-green` | `#16A34A` | **3,30:1** | ninguno como texto |
| `--color-state-yellow` | `#F0AD4E` | **1,95:1** | ninguno como texto |

- Hoy **no hay violación**: los tres se usan como relleno, borde o icono decorativo, y los
  `text-slate-400` / `text-slate-300` que quedan en el código están todos sobre iconos con
  `aria-hidden="true"` (comprobado uno por uno: `EmptyState.vue:11`, `CommandPalette.vue:112,141`,
  `DataTable.vue:101`, `AdminLayout.vue:403`).
- El riesgo es **latente y silencioso**: un módulo futuro que escriba `text-ink-soft` para un texto
  secundario —que es exactamente para lo que el token se llama así— incumplirá 1.4.3 sin que nada
  lo avise. El panel ya tiene el caso resuelto en `KpiCard.vue:24-26`, donde se eligió el tono `-700`
  en lugar del `-600` **con la medición escrita al lado**.
- **Corrección aplicada:** `--color-text-soft` pasa a **#64748B (4,76:1)**, porque un token llamado
  «texto suave» tiene que poder usarse como texto; y los dos de estado llevan ahora su medición
  escrita al lado con la instrucción de no usarlos como texto (para eso está el tono `-700`, como ya
  hacía `KpiCard.vue`). La cabecera de `tokens.css` incorpora la tabla de contrastes completa y la
  regla que se desprende de ella.

### R-P3 · El método de verificación por `grep` no distingue código de comentario

- La auditoría de origen proponía comprobar D-14 con `grep '#3366CC'` «→ cero resultados». Hoy
  devuelve **cero**, y eso es correcto. Pero al comprobar las demás cadenas prohibidas aparecen
  **tres coincidencias que son comentarios**, no interfaz: `EntrarView.vue:11,75` y
  `AuthLayout.vue:17` citan «doble factor» precisamente para explicar que se retiró y por qué.
- No es un defecto del panel: es un defecto **del método de verificación**, y conviene arreglarlo
  antes de convertirlo en puerta automática. Un `grep` de cadena en crudo daría un falso positivo y
  empujaría a borrar los comentarios que explican por qué se retiró algo, que es justo lo que se
  quiere conservar.
- **Corrección:** cuando estas comprobaciones se automaticen, limitarlas al marcado y a las hojas de
  estilo (plantilla del `.vue`, `.css`, `.html`) y no al árbol entero, o filtrar las líneas de
  comentario.

---

## 5. Lo que el panel sigue sin tener (contexto, no defecto de este lote)

- **D-15:** 18 de las 19 rutas siguen siendo un marcador. La guardia y el filtrado por permiso
  hacen que ahora **se vea** que no existen, que es la mejora posible sin CMS ni backend.
  *(Comprobado el 2026-10-02 y **confirmado**: el router de `develop` no declara esas rutas una a
  una, sino que las **genera desde una lista `MODULOS`** con exactamente **18 entradas** —`pqrsd`,
  `tramites`, `citas`, `notificaciones`, `sede`, `carpeta`, `autenticacion`, `cms`, `portal`,
  `transparencia`, `gestion-documental`, `sigmi`, `integraciones`, `usuarios`, `auditoria`,
  `reportes`, `asignacion`, `configuracion`— más el comodín. **La cifra original era correcta.**)*
- **La autenticación real** (SCD de Autenticación / SSO): el almacén es la costura, no la solución.
  Mientras no exista, el panel no es un producto desplegable.
- **D-35, parte de dependencias:** `@tanstack/vue-query`, `vue3-toastify` y `zod` siguen declarados
  y sin usar. Retirarlos toca `package.json` y `package-lock.json`, lo que quedaba fuera del
  encargo; es un cambio de una línea para quien tenga red para regenerar el bloqueo.
  → **RESUELTO (2026-10-02).** Los tres ya no están en `panel/package.json` y **ningún archivo de
  `panel/src` los importa**. Se deja el hallazgo en pie, marcado, en vez de borrarlo: una auditoría
  que elimina sus propias conclusiones pierde la traza de qué se comprobó y cuándo.
- **`/admin/no-existe` no pasa por la guardia** (es el 404 genérico). Si se quiere que **todo**
  `/admin/*` exija sesión, es una línea en el `meta` del comodín.

### 5.1 El prototipo de vistas: qué boceta y en qué estado está

Existe un **prototipo de panel** que nunca se integró con `panel/`. No está en ninguna rama: vive
en la etiqueta **`panel-diseno-prototipo`** (`6d914b3`, 89 archivos bajo `panel-diseño/`). Para
recuperarlo:

```bash
git show panel-diseno-prototipo:"panel-diseño/src/views/DashboardView.vue"
git ls-tree -r --name-only panel-diseno-prototipo | grep "src/views/"
```

**Lo primero que hay que decir, porque es lo que corrige la intuición: no es un panel distinto.**
Su router declara **19 módulos frente a los 18 del panel**, y comparando las dos listas:

```
solo en el panel:      (ninguno)
solo en el prototipo:  dashboard
```

Es decir: **la lista del prototipo es exactamente la del panel más `dashboard`.** Se construyó
contra la misma lista de módulos, y sus vistas corresponden **una a una** con ellos. Es el boceto
de las pantallas de esa lista, no una arquitectura alternativa:

| Módulo declarado en `panel/` | Vista del prototipo | Tamaño |
|---|---|---|
| `pqrsd` | `pqrsd/PqrsdListView` + `PqrsdDetailView` | 2.751 + 3.716 B |
| `tramites` | `tramites/TramitesView` | 234 B |
| `citas` | `citas/CitasView` | 204 B |
| `notificaciones` | `notificaciones/NotificacionesView` | 223 B |
| `sede` | `servicios/SedeElectronicaView` | 203 B |
| `carpeta` | `servicios/CarpetaCiudadanaView` | 196 B |
| `autenticacion` | `servicios/AutenticacionDigitalView` | 229 B |
| `cms` | `cms/CmsView` | 199 B |
| `portal` | `cms/PortalCiudadanoView` | 225 B |
| `transparencia` | `transparencia/TransparenciaView` | 204 B |
| `gestion-documental` | `documental/GestionDocumentalView` | 217 B |
| `sigmi` | `integraciones/SigmiView` | 217 B |
| `integraciones` | `integraciones/IntegracionesView` | 241 B |
| `usuarios` | `admin/UsuariosView` | 208 B |
| `auditoria` | `admin/AuditoriaView` | 197 B |
| `reportes` | `admin/ReportesView` | 196 B |
| `asignacion` | `admin/AsignacionView` | 214 B |
| `configuracion` | `admin/ConfiguracionView` | 201 B |

Las seis vistas restantes hasta 25 son el marco: `DashboardView` (2.511 B), `_PlaceholderView`
(510 B, la plantilla que envuelven las cáscaras), `auth/LoginView` (3.364 B), `auth/MfaView`
(2.008 B), `errors/ForbiddenView` (210 B) y `errors/NotFoundView` (203 B).

**Y el estado real de esos bocetos, medido por tamaño de archivo:**

| | Vistas | Tamaño |
|---|---|---|
| **Con contenido** | 5 | 2.008 – 3.716 B |
| Plantilla compartida | 1 | 510 B |
| **Cáscaras** | **19** | **196 – 241 B** |

Las cinco con contenido son `pqrsd/PqrsdDetailView`, `auth/LoginView`, `pqrsd/PqrsdListView`,
`DashboardView` y `auth/MfaView`. **Las 18 vistas que corresponden a módulos —todas menos
`pqrsd`— son cáscaras de ~200 B**: envoltorios sobre `_PlaceholderView` sin contenido propio.

**Comparación con el panel real, sin exagerar en ninguna dirección.** El tablero real
(`admin/InicioView`, 3.449 B) **supera** al del prototipo (2.511 B); pero el Login del prototipo
(3.364 B) y su MFA (2.008 B) **son mayores** que los reales (`acceso/EntrarView` 2.721 B,
`acceso/MfaView` 1.275 B). **No hay una regla general**: el prototipo aporta más en dos pantallas
y menos en otra, y en PQRSD no hay nada con qué comparar porque el panel real no lo tiene.

**Para qué sirve entonces.** No como trabajo hecho —de 25 vistas, cinco tienen contenido—, sino
como **la única referencia visual de qué debería haber dentro de cada uno de los 18 módulos**,
que hoy son todos `EnConstruccionView`. Si la Entidad prioriza módulos, aquí está el boceto de
cada uno; si descarta alguno, esta tabla dice qué se está descartando.

---

## 6. Cómo se re-verifica

```bash
# Compila y comprueba tipos (la puerta que de verdad importa)
cd panel && npm run build                 # → vue-tsc -b && vite build, sin errores

# Las afirmaciones falsas no vuelven (excluyendo comentarios, ver R-P3)
grep -rn '3366CC\|Sesión activa\|Sistema operativo\|API Gateway' panel/src panel/index.html

# Las guardias existen y se aplican
grep -n 'beforeEach' -A 22 panel/src/router/index.ts

# Los contrastes del cobalto institucional (medidos en esta auditoría)
#   blanco / #0943B5 → 8,46:1   ·   #0943B5 / #E9EEF9 → 7,28:1
```

**Lo que esta auditoría no puede afirmar:** que el panel se vea bien a 768 px (D-33 queda pendiente
de una comprobación visual, porque con la guardia activa la disposición administrativa no se abre sin
falsear una sesión), ni que funcione contra un backend real —no hay autenticación, ni CMS, ni una
sola operación de escritura—.

---

*Auditoría de solo lectura sobre `panel/`: no se modificó ningún fichero del panel durante esta
verificación. El lote que corrige los ocho hallazgos es anterior y está descrito en
[auditoria-sede.md](auditoria-sede.md) §12.*
