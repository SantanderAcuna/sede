# Catálogo de fuentes — Trámites y servicios

> **Sede Electrónica** — Alcaldía Distrital de Santa Marta
> **Carpeta:** `entidad-transparencia/ciudadano/`
> **Auditoría:** [`auditoria-tramites-ficha.md`](./auditoria-tramites-ficha.md)
>
> **La Sede no inventa contenido institucional.** Todo lo que publique debe venir de una fuente trazable: una API, un documento, un acto administrativo o la Entidad misma. Este catálogo lista **qué dice cada fuente, qué campos entrega, y por qué algunos huecos existen hoy**.

---

## 1. Resumen ejecutivo

| Fuente | ¿La usa hoy la Sede? | Cubre la ficha al | De dónde sale | Confianza |
|---|---|---|---|---|
| **SUIT — Función Pública (entidad 0043)** | ✅ Sí, sembrador | ~40 % | `database/datos/tramites-0043-suit.json` (copia congelada) | Alta — es la fuente oficial |
| **GOV.CO** (`gov.co/ficha-tramites-y-servicios/Txxxx`) | ⚠️ Parcial, sólo el enlace | 0 % como dato, 100 % como redirección | SPA Angular, no expone API | Media — es la ficha oficial pero sin datos accesibles |
| **DAFP — visor de SUIT** | ❌ No se usa hoy | 0 % | Aplicación autenticada del Ministerio | Alta pero inaccesible |
| **Acto administrativo de la Entidad** (decreto, resolución) | ❌ No se usa hoy | n/a | Anexo a un PDF que aprueba la Entidad | Alta — es la fuente por excelencia para `normativa` y `pasos` |
| **Entidad (panel de edición)** | ❌ No existe todavía | 0 % | Editor humano autorizado | Alta cuando exista |
| **Carpeta Ciudadana Digital (CCD)** | ❌ No se usa hoy | n/a | API de GOV.CO (no pública) | — |

> **El hueco central de la ficha es `requisitos`, `pasos`, `normativa`, `resultado`, `perfiles`, `puntos_atencion` y `canales_consulta_estado`**. La fuente oficial (SUIT) no los entrega como dato en su API pública. La Sede no puede inventarlos.

---

## 2. SUIT — Función Pública (entidad 0043)

### 2.1 ¿Qué es?

El **Sistema Único de Información de Trámites (SUIT)** es el registro oficial de todos los trámites del Estado colombiano. Cada entidad tiene un código: la Alcaldía Distrital de Santa Marta es `0043`. SUIT es administrado por la **Función Pública (DAFP)**.

### 2.2 ¿Qué campos entrega la API pública de SUIT (lo que la Sede ingiere hoy)?

| Campo SUIT | Campo contrato | Observación |
|---|---|---|
| `id` | `codigo` (columna interna) | T-código |
| `titulo` | `nombre` | nombre oficial del trámite |
| `proposito` | `resumen` | propósito en 1-3 frases; recortado a 500 caracteres |
| `enLinea` (`NO`/`PARCIAL`/`SI`) | `modalidad` | convertido a enum (`presencial`/`parcialmente_en_linea`/`en_linea`) |
| `costo` (`NO`/`SI`) | `tiene_costo` | convertido a enum (`gratuito`/`con_costo`) |
| `tiempoObtencion` (texto: «2 HORA(S)», «3 MES(ES)») | `tiempo_solucion_dias` (entero) | convertido por `TiempoEnDias::desde()` |
| `urlTramiteEnLinea` | `canal_inicio`, `url_inicio` | URL al flujo en línea, si existe |
| `link_govco` | `url_ficha_gov_co` | URL de la ficha oficial en GOV.CO |

> **Nueve campos.** Y todos vienen en texto libre o con valores restringidos. SUIT no entrega `requisitos`, `pasos`, `normativa`, `resultado`, `perfiles`, `puntos_atencion` ni `canales_consulta_estado` por su API pública.

### 2.3 Lo que la Sede le agrega (no viene de SUIT)

| Campo | Razón | Valor |
|---|---|---|
| `consulta_estado` | Es un dato de la Sede, no del trámite | constante `'/seguimiento?radicado=…'` |
| `requisitos` | SUIT no entrega requisitos por la API pública | **placeholder**: «Los que declara la ficha oficial del trámite en GOV.CO (SUIT)» + documento que apunta a la ficha |
| `documentos` | idem | «Ficha oficial del trámite en GOV.CO (SUIT)» con URL |
| `categoria_slug` / `categoria_nombre` | SUIT no clasifica los trámites en categorías | `null` |
| `procedencia_fuente`, `procedencia_url`, `procedencia_obtenido_en` | auditoría de la Ley 1712 Art.11.b | `SUIT — Función Pública (entidad 0043)` |
| `publicado_en` | marca de publicación | `now()` |

### 2.4 Huella y verificación

- **Origen del archivo:** `database/datos/tramites-0043-suit.json`
- **Fecha de la recolección:** 2026-09-30
- **Trámites en la copia:** 124 (123 publicados + 1 descartado por falta de `tiempo_solucion_dias`)
- **Trazabilidad:** cada `Tramite` lleva la **marca de la siembra** (`procedencia_fuente === 'SUIT — Función Pública (entidad 0043)'`). Si un editor humano lo modifica, la marca se reemplaza por la fuente de la Entidad y la siembra no lo vuelve a tocar (esto protege el trabajo de las personas).

### 2.5 ¿Por qué no se scrapea el visor de SUIT?

El visor de SUIT es una SPA autenticada del Ministerio. No es un sitio público y no se debe scrapear: hacerlo sería:
1. Violar los términos de uso del DAFP.
2. Crear una dependencia operativa de un sistema que no controlamos.
3. Exponer al equipo a una integración que se rompería cada vez que el Ministerio cambie un ID de selector.

La Sede opta por la copia congelada dentro del proyecto (`database/datos/tramites-0043-suit.json`), que es la misma decisión que tomó DAFP y otros distritos.

---

## 3. GOV.CO — `https://www.gov.co/ficha-tramites-y-servicios/Txxxx`

### 3.1 ¿Qué es?

Es la **ficha oficial** del trámite, publicada por el Ministerio de Comercio, Industria y Turismo en el portal GOV.CO. La ve el ciudadano cuando hace clic en «Ver en GOV.CO» desde nuestra galería.

### 3.2 ¿Qué muestra cada ficha?

Visualmente (de arriba a abajo):

1. **Información general** — qué es el trámite.
2. **¿Qué necesito para hacer mi trámite?** — requisitos y documentos.
3. **¿Cómo hago mi trámite?** — pasos.
4. **¿Cuál es el horario y los puntos de atención?** — dónde se hace.
5. **¿Cuánto cuesta?** — tarifa o gratuidad.
6. **¿Cuál es la normativa relacionada con este trámite?** — leyes / decretos / resoluciones.
7. **¿Qué resultado obtengo luego de hacer mi trámite?** — entregable.
8. **¿Cuál es el tiempo de respuesta?** — plazo.
9. **¿Quién puede realizarlo?** — grupo objetivo.
10. **Canales de atención** — cómo contactar a la Entidad.
11. **Iniciar trámite en línea** — botón al flujo.

### 3.3 ¿Por qué la Sede no ingiere estos datos?

- **No hay API pública.** GOV.CO es una **SPA Angular** servida desde `gov.co`. El HTML inicial es `<app-root></app-root>`, sin datos, y el contenido se carga con JavaScript contra endpoints internos del Ministerio.
- **Los endpoints internos no son públicos** y no están documentados.
- **Scraping con navegador** (Puppeteer, Playwright) sería técnicamente posible, pero:
  - Depende de un tercero que puede cambiar IDs y romper la integración de un día para otro.
  - El scraping a gran escala de un sitio gubernamental es, por decir lo menos, una **decisión institucional** que la Sede no debe tomar por su cuenta.
  - No es sostenible: cada cambio de GOV.CO requiere un fix en producción.

### 3.4 ¿Qué hace la Sede hoy con GOV.CO?

Lo trata como **origen de la redirección**, no como origen del dato. La Sede:

- En cada elemento de la galería, **enlaza** a la ficha de GOV.CO.
- En la ficha individual (`/tramites/{slug}`), muestra la URL de GOV.CO de forma prominente.
- **No** scrapea, **no** cachea, **no** muestra datos de GOV.CO sin fuente trazable.

### 3.5 Ruta correcta (no implementada hoy)

Cuando la Entidad quiera que la Sede muestre los datos de la ficha de GOV.CO **sin scraping**, hay tres caminos:

| Camino | Cómo | Costo | Plazo |
|---|---|---|---|
| **A. Edición en el panel** (P1.1 de la auditoría) | un editor de la Entidad carga los datos a mano | bajo, recurrente | inmediato |
| **B. Ingesta manual aprobada** (P2.1) | la Entidad publica un JSON con la ficha y la Sede lo ingiere por un endpoint de la Entidad | medio, único | depende de la Entidad |
| **C. API oficial de GOV.CO** | gestión política con MinTIC / Ministerio para que GOV.CO exponga un endpoint de consulta por T-código | alto, político | > 6 meses |

> La Sede implementa el camino A (panel) en este sprint y deja A y C documentados para la Entidad.

---

## 4. Acto administrativo de la Entidad

### 4.1 ¿Qué es?

Toda creación o modificación de un trámite en la Alcaldía está soportada por un **acto administrativo**: un decreto, una resolución, un acuerdo, una circular. Este documento es la **fuente oficial** de:

- La **normativa** del trámite (cita el acto que lo crea).
- Los **pasos** (a veces anexados en una «Guía del trámite»).
- Los **requisitos** detallados (cuando se anexan en un PDF).
- Los **puntos de atención** (dirección, horario, teléfono).
- El **resultado** del trámite (qué documento se entrega al ciudadano).

### 4.2 ¿Por qué la Sede no los carga hoy?

Porque hacerlo requiere:

1. Un catálogo de actos administrativos con su texto completo, en formato consultable.
2. Un editor de la Entidad que traduzca el acto en los campos del contrato.
3. Un versionado: cuando un acto se deroga o modifica, la Sede debe reflejarlo en menos de 3 días hábiles (RN-B1-006).

Esto es **P1** en la auditoría. Hoy no existe, pero es la ruta correcta.

### 4.3 Huella esperada

- Cada campo que se llene desde un acto lleva `procedencia.fuente = 'Entidad — Decreto NNN de AAAA-MM-DD'`.
- El bloque «Procedencia» de la ficha lo publica al final, junto con la fecha de modificación.

---

## 5. Entidad (panel de edición)

### 5.1 ¿Qué es?

El **panel de administración** de la Sede (`panel/`), donde un usuario autorizado (rol `tramites-editor` o superior) edita los campos de un trámite. Es la **puerta humana** para que la Entidad publique lo que las fuentes automáticas no entregan.

### 5.2 Implementación (P1.1 de la auditoría)

- Vista `panel/src/views/admin/tramites/EditarTramiteView.vue`.
- Endpoint `PATCH /api/v1/panel/tramites/{slug}` con `FormRequest`, `Service`, `Policy` y `Repository` (las cinco capas del backend).
- Audit log: cada cambio se registra con usuario, fecha, valor anterior y valor nuevo.
- La marca de la siembra (SUIT) se reemplaza por la marca de la Entidad cuando un humano edita; la siembra ya no toca esa fila.

### 5.3 Diferencia con la «carga de la Entidad»

- **Acto administrativo:** la fuente es el documento oficial firmado por el alcalde (o su delegado). El editor lo **transcribe** al panel.
- **Edición del panel:** la fuente es **el editor mismo**. Adecuado para correcciones de erratas, agregar normativa no documentada, etc. La marca de procedencia debe ser específica: `Entidad — Corrección editorial de AAAA-MM-DD`.

---

## 6. Carpeta Ciudadana Digital (CCD) — pendiente

La CCD es donde el ciudadano ve los **resultados** de los trámites. Es consumo de la Sede, no fuente: la Sede **publica** sus resultados en la CCD, no los lee de ahí.

Documentado en el módulo 10 de la elicitación (interoperabilidad).

---

## 7. Resumen: qué falta y quién lo trae

| Campo | Lo trae hoy | Lo debería traer | Quién debe hacerlo |
|---|---|---|---|
| `nombre`, `resumen`, `modalidad`, `tiene_costo`, `tiempo_solucion_dias`, `canal_inicio`, `url_inicio`, `url_ficha_gov_co` | ✅ SUIT | — | sembrador |
| `consulta_estado` | ✅ Constante de la Sede | — | sembrador |
| `requisitos`, `documentos` | ⚠️ placeholder (enlace a GOV.CO) | ficha oficial por trámite | **panel (P1)** |
| `pasos` | ❌ | guía del trámite | **panel (P1)** |
| `normativa` | ❌ | acto administrativo | **panel (P1)** |
| `resultado` | ❌ | ficha oficial / acto | **panel (P1)** |
| `perfiles` | ❌ | ficha oficial | **panel (P1)** |
| `puntos_atencion` | ❌ | ficha oficial / acto | **panel (P1)** |
| `canales_consulta_estado` | ❌ | ficha oficial | **panel (P1)** |
| `costo.valor`, `costo.moneda`, `costo.cuentas[]` | ❌ | ficha oficial / acto | **panel (P1)** |
| `categoria` | ❌ | ficha oficial | **panel (P1)** — y, mientras tanto, mostrar el desplegable vacío |

> **El 100 % del contenido institucional se publica por el panel de la Entidad**, una vez construido (P1). La Sede aporta la estructura y las reglas; la Entidad, el contenido.

---

## 8. Riesgos identificados

| Riesgo | Mitigación |
|---|---|
| SUIT cambia su API y rompe la siembra | la copia congelada dentro del proyecto (`tramites-0043-suit.json`) protege la Sede hasta que se actualice la siembra |
| GOV.CO cambia su SPA y rompe el enlace | el `url_ficha_gov_co` está versionado y no es derivado: la Sede no depende de él para mostrar contenido |
| La Entidad no carga el panel durante meses | la ficha **debe** seguir mostrando los datos que la fuente oficial ya entrega, y declarar el resto como «consulte GOV.CO» |
| Un editor de la Entidad carga contenido incorrecto | audit log + P1.1, y la posibilidad de revertir a la marca SUIT |
| Doble fuente para un mismo dato | la marca de procedencia lo resuelve: si un campo tiene `origen_por_campo.requisitos === 'fuente'`, viene de SUIT; si es `'entidad'`, del panel |
