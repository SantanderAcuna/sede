# Extracción BD — Módulo 07 Accesibilidad

## 0. Cobertura

Íntegra: SÍ
Archivo: `/var/www/proyect-doc/elicitacion/sede-electronica/07-accesibilidad/accesibilidad.md`
Líneas leídas: 1–174 (íntegras, 0 omitidas)

---

## 1. Entidades candidatas

El módulo es **mayormente RNF/presentación**. La sección §7 del documento nombra explícitamente dos entidades de datos. Solo una de ellas genera persistencia propia en BD; la segunda es **CANDIDATA-COMPARTIDA** con el módulo de gestión de contenidos/CMS.

### 1.1 `user_accessibility_preference` — PROPIA DE ESTE MÓDULO

**Descripción:** almacena las preferencias de accesibilidad elegidas por el usuario en la barra de accesibilidad y que el sistema debe persistir entre páginas y sesiones.

**Fuente:** §2.1 RF-B1-044/RF-B2-043/RF-B3-054 (línea 20): *"guarda la preferencia del usuario"*; §7 (línea 157): *"Preferencias de accesibilidad del usuario: tamaño de fuente (A/A+/A++), alto contraste (persistidas)"*; HU-B1-015/HU-B3-002 (línea 142): *"la preferencia persiste en páginas siguientes"*.

**¿CANDIDATA-COMPARTIDA?** No. Las preferencias de accesibilidad son datos específicos de este módulo ligados al perfil del usuario, pero el `usuario` al que se referencian es la entidad de autenticación (Módulo 01 u equivalente). Esta tabla vincula con `users` vía FK — no duplica esa entidad.

---

### 1.2 `accessible_media_resource` — CANDIDATA-COMPARTIDA

**Descripción:** metadatos de accesibilidad adjuntos a un recurso multimedia institucional: si tiene subtítulos (SRT), audiodescripción, LSC, transcripción; tipo de contenido que determina la obligatoriedad de LSC.

**Fuente:** §7 (línea 158): *"Recurso multimedia accesible: video, subtítulos (SRT), audiodescripción, LSC, transcripción"*; RN-B3-002 (línea 113): subtítulos en 100% de videos nuevos; RN-B3-003 (línea 115): LSC obligatoria para 4 tipos de contenido; RN-07-D01 (línea 123): bloqueo de publicación si faltan subtítulos/LSC; UC-B3-015 (línea 133): el sistema verifica subtítulos al subir; HU-07-D01 (línea 151): el CMS bloquea la publicación sin .SRT.

**¿CANDIDATA-COMPARTIDA?** **SÍ.** El recurso multimedia base (archivo, URL, tipo MIME, título, fechas) pertenece al módulo de Contenidos/CMS. Esta entidad representa los **metadatos de accesibilidad** de ese recurso — es una extensión o tabla satélite de la entidad multimedia del CMS. Debe coordinarse con ese módulo en el modelo consolidado.

---

## 2. Atributos/campos por entidad

### 2.1 `user_accessibility_preference`

| Campo | Tipo PostgreSQL | Restricciones | Fuente |
|-------|----------------|---------------|--------|
| `id` | `BIGINT GENERATED ALWAYS AS IDENTITY` | PK | [INFERIDO] — clave técnica estándar |
| `user_id` | `BIGINT` | NOT NULL, FK → `users.id` ON DELETE CASCADE, UNIQUE (una fila por usuario) | línea 20: preferencia por usuario; [INFERIDO] unicidad |
| `font_size` | `VARCHAR(4)` | NOT NULL, DEFAULT `'A'`, CHECK (`font_size IN ('A', 'A+', 'A++')`) | línea 20 y 157: valores exactos documentados: A / A+ / A++ |
| `high_contrast` | `BOOLEAN` | NOT NULL, DEFAULT FALSE | línea 20: "alto contraste"; HU-B1-015 (línea 142): activo/inactivo |
| `created_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT `now()` | [INFERIDO] — auditoría temporal estándar |
| `updated_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT `now()` | [INFERIDO] — necesario para persistencia entre sesiones |

**Nota sobre `skip_link` y `relay_center_link`:** la barra de accesibilidad contiene también el enlace "Saltar al contenido" y el enlace al Centro de Relevo (línea 20). Ambos son elementos de presentación HTML fijos, no preferencias elegibles por el usuario — no se persisten en BD.

---

### 2.2 `accessible_media_resource`

| Campo | Tipo PostgreSQL | Restricciones | Fuente |
|-------|----------------|---------------|--------|
| `id` | `BIGINT GENERATED ALWAYS AS IDENTITY` | PK | [INFERIDO] |
| `media_resource_id` | `BIGINT` | NOT NULL, FK → `media_resources.id` ON DELETE CASCADE, UNIQUE (1:1 con el recurso base) | [INFERIDO] vínculo con la entidad de contenidos/CMS |
| `content_type` | `VARCHAR(50)` | NOT NULL, CHECK (`content_type IN ('video_general', 'alocucion_alcalde', 'emergencia', 'seguridad_ciudadana', 'rendicion_cuentas', 'solo_audio')`) | línea 115 (RN-B3-003): 4 tipos que obligan LSC; línea 158: también `solo_audio` (requiere transcripción) |
| `has_subtitles` | `BOOLEAN` | NOT NULL, DEFAULT FALSE | línea 113 (RN-B3-002): subtítulos obligatorios en videos nuevos |
| `subtitles_file_path` | `TEXT` | NULL, CHECK (`has_subtitles = FALSE OR subtitles_file_path IS NOT NULL`) | líneas 87, 151: se sube archivo .SRT; NULL si `has_subtitles = FALSE` |
| `has_audio_description` | `BOOLEAN` | NOT NULL, DEFAULT FALSE | línea 158: "audiodescripción" como componente del recurso accesible |
| `has_lsc` | `BOOLEAN` | NOT NULL, DEFAULT FALSE | línea 115 (RN-B3-003): LSC para 4 tipos de contenido; línea 123 (RN-07-D01): bloqueo si falta |
| `lsc_resource_path` | `TEXT` | NULL, CHECK (`has_lsc = FALSE OR lsc_resource_path IS NOT NULL`) | línea 151: "ventana LSC" como recurso adjunto; [INFERIDO] path del video/overlay LSC |
| `has_transcript` | `BOOLEAN` | NOT NULL, DEFAULT FALSE | línea 158: transcripción listada; línea 28: "transcripción de solo-audio" |
| `transcript_text` | `TEXT` | NULL | línea 28: RF-B1-046 menciona transcripción para contenido de solo-audio; puede ser larga |
| `is_live` | `BOOLEAN` | NOT NULL, DEFAULT FALSE | línea 113 (RN-B3-002): excepción de subtítulos para contenido en vivo |
| `published_at` | `TIMESTAMPTZ` | NULL | [INFERIDO] — el bloqueo de publicación (RN-07-D01) implica un estado pre-publicación |
| `accessibility_status` | `VARCHAR(20)` | NOT NULL, DEFAULT `'pendiente'`, CHECK (`accessibility_status IN ('pendiente', 'bloqueado', 'conforme')`) | RN-07-D01 (línea 123): el CMS bloquea si faltan subtítulos/LSC; UC-B3-015 (línea 133): flujo de publicación |
| `created_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT `now()` | [INFERIDO] |
| `updated_at` | `TIMESTAMPTZ` | NOT NULL, DEFAULT `now()` | [INFERIDO] |

---

## 3. Reglas de negocio con impacto en datos

| ID regla | Regla | Impacto en BD | Fuente |
|----------|-------|---------------|--------|
| RN-B3-002 | Subtítulos obligatorios en 100% de videos nuevos (excepción: en vivo). | `has_subtitles = TRUE` requerido cuando `content_type LIKE 'video%' AND is_live = FALSE`; enforced por CHECK o trigger. | línea 113 |
| RN-B3-003 | LSC obligatoria para 4 tipos: alocución, emergencias, seguridad ciudadana, rendición de cuentas. | `has_lsc = TRUE` requerido cuando `content_type IN ('alocucion_alcalde','emergencia','seguridad_ciudadana','rendicion_cuentas')`; enforced por trigger o constraint diferido. | línea 115 |
| RN-07-D01 | CMS bloquea publicación de multimedia sin subtítulos / sin LSC cuando aplica. | `accessibility_status = 'bloqueado'` impide publicación; transición a `'conforme'` solo si se cumplen RN-B3-002 y RN-B3-003. Implementable como CHECK de estado + trigger de transición. | línea 123 |
| RN-07-D02 | Captcha accesible obligatorio. | No genera tabla propia — es una restricción de configuración del proveedor de captcha. **Sin impacto directo en BD.** | línea 124 |
| RF-B1-044/RF-B2-043/RF-B3-054 | La preferencia de barra de accesibilidad persiste entre páginas. | Requiere fila en `user_accessibility_preference` por usuario autenticado; para usuarios anónimos, la persistencia es por cookie/localStorage — **fuera de BD**. | línea 20 |

**Restricción de integridad multi-columna** (candidata a trigger en PostgreSQL 15+):

```
-- Pseudocódigo de la restricción derivada de RN-B3-002 y RN-B3-003:
CONSTRAINT chk_subtitles_required
  CHECK (
    is_live = TRUE
    OR content_type = 'solo_audio'
    OR has_subtitles = TRUE
  )

CONSTRAINT chk_lsc_required
  CHECK (
    content_type NOT IN ('alocucion_alcalde','emergencia','seguridad_ciudadana','rendicion_cuentas')
    OR has_lsc = TRUE
  )
```

Nota: si el dominio requiere que el bloqueo sea *previo* a la publicación (no retroactivo), las restricciones deben evaluarse en el momento de actualizar `accessibility_status` a `'conforme'`, no al insertar — se recomienda trigger `BEFORE UPDATE`.

---

## 6. Fuera de BD (RNF/presentación) — lista justificada

Todos los ítems siguientes son requisitos de presentación, comportamiento de cliente, configuración de servidor o estándar de markup. No generan tablas ni columnas propias.

| Ítem | Justificación |
|------|---------------|
| WCAG 2.1 AA — 52 criterios (CC1-CC32) en general | Son criterios de evaluación del HTML/CSS/JS entregado; no hay estado persistible por criterio en BD. | 
| Estructura HTML semántica (`header/nav/main/article/footer`, H1-H6) | Regla de markup; estado en el DOM, no en BD. | 
| `alt` descriptivo (≤150 car.) en imágenes | Atributo HTML del recurso; si el CMS almacena el `alt` como campo de `media_resources`, es responsabilidad de ese módulo — no de accesibilidad. |
| Contraste de colores (≥4.5:1 / ≥3:1) | Propiedad del sistema de diseño/CSS; no hay valor de contraste que persista en BD por usuario. |
| Navegación por teclado, orden de foco, trampas de foco, `outline` | Comportamiento del DOM/CSS; sin persistencia. |
| Atajos de teclado desactivables/reasignables | Configuración de UI en cliente (localStorage); no requiere tabla propia salvo decisión futura de sincronización con perfil. |
| Textos de enlace descriptivos, prohibición de "Clic aquí" | Regla editorial de contenidos; no es dato persistible en accesibilidad — aplica al módulo de contenidos. |
| `<title>` descriptivo por página | Regla de plantilla/CMS; no en BD de accesibilidad. |
| HTML válido, IDs únicos, anidación correcta | Regla de compilación/lint; no en BD. |
| ARIA (roles, estados, propiedades en componentes) | Atributos HTML generados por el componente; no en BD. |
| `role="status"` / `aria-live` en mensajes de estado | Comportamiento del DOM en runtime; no en BD. |
| Responsive, zoom 400%, orientación | CSS/viewport; no en BD. |
| Tap-target ≥44×44 px | CSS; no en BD. |
| Sin destellos >3/seg (PEAT) | Regla de asset multimedia; no en BD de accesibilidad (podría ser metadato en `media_resources` del CMS). |
| Controles de pausa/parada para audio >3 s | Comportamiento del reproductor; no en BD. |
| Codificación UTF-8 (`<meta charset>`) | Configuración del servidor/CMS; no en BD. |
| Límite de tiempo <20 h: ajustar/extender | Comportamiento de sesión/UI; la sesión puede estar en BD de autenticación (Módulo 01), no aquí. |
| Sin texto justificado, 60-80 car./línea, sin pop-ups automáticos | Reglas de CSS/editorial; no en BD. |
| Tooltips con ESC, hover persistente | Comportamiento de componente UI; no en BD. |
| Mapa del sitio XML en footer | Archivo estático generado; no en BD de accesibilidad. |
| Sin vínculos rotos (W3C Link Checker) | Resultado de auditoría externa; podría persistirse en un log de calidad/auditoría del CMS, fuera del scope de accesibilidad como entidad propia. |
| `lang="es-CO"` en `<html>` | Atributo de plantilla; no en BD. |
| Alternativa a gestos multidedo, `pointerup`, movimiento del dispositivo | Comportamiento de evento JS; no en BD. |
| Modo alto contraste altera todos los colores | CSS (`prefers-color-scheme`/clase toggle); no en BD salvo la preferencia del usuario ya modelada en §2.1. |
| Compatible con NVDA, JAWS, VoiceOver (≥90% tareas completables) | RNF de testing; resultado de auditoría, no dato de BD. |
| `prefers-reduced-motion` | CSS media query; no en BD. |
| NTC 5854 nivel AA | Marco normativo de evaluación; no genera tablas. |
| Centro de Relevo (integración externa) | Enlace externo; no hay datos propios que persistir. |
| MinTIC, INCI (integraciones) | Servicios externos referenciados; sin tablas propias en este módulo. |

---

## 7. Inferencias [INFERIDO]

| # | Inferencia | Base | Riesgo si es incorrecta |
|---|-----------|------|------------------------|
| I-01 | `user_accessibility_preference` aplica **solo a usuarios autenticados**. Para anónimos, la persistencia es por cookie/localStorage, fuera de BD. | RF-B1-044 dice "guarda la preferencia del usuario" sin distinguir; pero una tabla BD con FK a `users` implica autenticación. | Bajo — el comportamiento para anónimos se puede manejar por capa de presentación sin cambiar el modelo. |
| I-02 | `media_resources` existe como entidad en el módulo de Contenidos/CMS y `accessible_media_resource` la referencia con FK. | UC-B3-015 describe el flujo completo de subida de video; el CMS tiene el recurso base. | Medio — si el módulo CMS no existe o tiene otro nombre, la FK debe ajustarse en el modelo consolidado. |
| I-03 | El campo `accessibility_status` ('pendiente'/'bloqueado'/'conforme') modeliza el bloqueo de publicación descrito en RN-07-D01. | RN-07-D01 y HU-07-D01 describen el bloqueo pero no especifican cómo se representa en datos. | Medio — el CMS podría usar su propio campo de estado de publicación. Se recomienda confirmar si el estado de accesibilidad es columna separada o parte del estado general del recurso. |
| I-04 | `lsc_resource_path` guarda la ruta al video/overlay de LSC como archivo adjunto, análogo a `subtitles_file_path`. | HU-07-D01 menciona "ventana LSC" pero no especifica su formato (video embebido, overlay, archivo separado). | Medio — si LSC es un archivo separado (video), `TEXT` es correcto; si es embedded en el video principal, el campo no aplica y debería eliminarse. |
| I-05 | `transcript_text` se almacena como `TEXT` en la misma tabla para contenido `solo_audio`. | RF-B1-046 (línea 28) menciona "transcripción de solo-audio" sin especificar formato ni ubicación. | Bajo — si la transcripción es un archivo externo (PDF/TXT), se debería usar `transcript_file_path TEXT` en lugar de o además de `transcript_text`. |

---

**Resumen ejecutivo:**

- Entidades persistibles identificadas: **2**
- `user_accessibility_preference`: 6 campos (4 de dominio + 2 de auditoría)
- `accessible_media_resource`: 14 campos (11 de dominio + 3 de auditoría/estado)
- CANDIDATA-COMPARTIDA: **1** (`accessible_media_resource` — coordinar con módulo Contenidos/CMS)
- Resto del módulo (WCAG, HTML, CSS, ARIA, herramientas de testing, integraciones externas): **FUERA DE BD**
