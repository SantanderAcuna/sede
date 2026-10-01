# Extracción BD — Módulo 05 Participa

> Fuente: `/var/www/proyect-doc/elicitacion/sede-electronica/05-participa/participa.md`
> Extractor: jose-bd — analista documental de élite
> Fecha de extracción: 2026-06-07

---

## 0. Cobertura

- **Íntegra: SÍ**
- Líneas leídas: 1–94 (94 líneas totales)
- Omitidas: 0
- Declaración formal: 1 de 1 archivo leído íntegramente, 0 omitidos.

---

## 1. Entidades candidatas

| # | Nombre canónico | Descripción | ¿CANDIDATA-COMPARTIDA? | Fuente |
|---|-----------------|-------------|------------------------|--------|
| E1 | `participation_mechanism` | Espacio/mecanismo/acción de participación ciudadana estructurado en 4 fases (Diagnóstico, Planeación, Ejecución, Evaluación) con ciclo de vida (Abierta → Cerrada → Archivada). | NO — propio del módulo 05 | participa.md:17, :27, :81 |
| E2 | `regulatory_project` | Proyecto de norma en elaboración publicado con plazo de comentarios; integrado con SUCOP (DNP). Puede coincidir con documentos normativos del módulo Transparencia. | **CANDIDATA-COMPARTIDA** (Transparencia / Normativa fuente única, línea 87) | participa.md:18, :80 |
| E3 | `interest_group_microsite` | Micrositio diferenciado para un grupo de valor del Distrito; contiene información accesible en lenguaje claro. | NO — propio del módulo 05 | participa.md:19, :82 |
| E4 | `interest_group` | Catálogo de grupos de interés reconocidos: NNA, mujeres, personas con discapacidad, adultos mayores, comunidades étnicas, LGBTIQ+. | [AMBIGUO] — podría ser lookup compartido con otros módulos futuros | participa.md:19, :82 |
| E5 | `regulatory_agenda` | Agenda regulatoria publicada; relacionada con proyectos normativos. Solo se menciona su publicación; puede ser una vista/sección y no tabla independiente. | [AMBIGUO] — podría fusionarse con E2 o ser entidad propia | participa.md:18, :87 |
| E6 | `participation_result` | Consolidado de observaciones recibidas y respuesta de la entidad (incorporadas o no) publicado al cierre de una consulta normativa. | NO — propio del módulo 05 | participa.md:28, :53, :76 |
| E7 | `citizen` | Ciudadano que participa en consultas y accede a micrositios. | **CANDIDATA-COMPARTIDA** (actor transversal en toda la sede) | participa.md:59, :65, :66, :67 |
| E8 | `participation_admin` | Administrador de participación (back-office); gestiona ciclo de vida de consultas. | [INFERIDO] — deducido de HU-05-D01 (línea 75); puede ser rol de `users` compartido | participa.md:75 |

---

## 2. Atributos/campos por entidad

### E1 — `participation_mechanism`

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| `phase` | VARCHAR(20) | SI | `Diagnóstico`, `Planeación`, `Ejecución`, `Evaluación` | — | participa.md:17, :81 |
| `name` | VARCHAR(255) | SI | texto libre | — | participa.md:81 |
| `description` | TEXT | SI | texto libre | — | participa.md:81 |
| `validity_start` | DATE | SI | fecha de inicio de vigencia/convocatoria | — | participa.md:17 (criterio: "fechas de vigencia") |
| `validity_end` | DATE | SI | fecha máxima de comentarios/cierre | — | participa.md:17, :27, :52 |
| `status` | VARCHAR(20) | SI | `Abierta`, `Cerrada`, `Archivada` | — | participa.md:27, :52, :75 |
| `auto_close_triggered_at` | TIMESTAMP | NO | marca temporal del cierre automático por vencimiento | — | participa.md:27, :52 (RN-05-D01) |
| `created_at` | TIMESTAMP | SI | — | — | [INFERIDO] |
| `updated_at` | TIMESTAMP | SI | — | — | [INFERIDO] |

### E2 — `regulatory_project`

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| `title` | VARCHAR(500) | SI | texto del proyecto normativo | — | participa.md:80 |
| `body_text` | TEXT | SI | texto íntegro del proyecto | — | participa.md:80 |
| `publication_date` | DATE | SI | fecha de publicación | — | participa.md:80 |
| `comment_deadline` | DATE | SI | fecha máxima de comentarios | — | participa.md:80, :44, :52 |
| `sucop_url` | VARCHAR(2048) | SI | enlace externo a SUCOP (DNP) | — | participa.md:80 |
| `status` | VARCHAR(20) | SI | `Abierta`, `Cerrada` | — | participa.md:27, :52, :65 (criterio negativo HU-B1-016) |
| `auto_close_triggered_at` | TIMESTAMP | NO | marca temporal del cierre automático | — | participa.md:52 (RN-05-D01) |
| `regulatory_agenda_id` | INTEGER | NO | FK a `regulatory_agenda` si se modela por separado | FK | participa.md:87 |
| `created_at` | TIMESTAMP | SI | — | — | [INFERIDO] |
| `updated_at` | TIMESTAMP | SI | — | — | [INFERIDO] |

**Nota CANDIDATA-COMPARTIDA:** el campo `sucop_url` y los campos de texto del proyecto pueden solaparse con entidades del módulo Transparencia/Normativa (fuente única según línea 87). El orquestador debe arbitrar si `regulatory_project` vive en Transparencia y este módulo solo referencia por FK, o si se duplica con restricción de integridad.

### E3 — `interest_group_microsite`

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| `interest_group_id` | INTEGER | SI | FK a `interest_group` | FK | participa.md:82 |
| `slug` | VARCHAR(100) | SI | identificador de URL del micrositio | UNIQUE | [INFERIDO] |
| `content_html` | TEXT | SI | contenido accesible en lenguaje claro (WCAG 2.1 AA) | — | participa.md:19, :82 |
| `has_alt_texts` | BOOLEAN | SI | indica si el contenido cumple textos alternativos | — | participa.md:19, :67 (criterio: "textos alternativos") |
| `language_level` | VARCHAR(50) | SI | `lenguaje_claro` (Guía DNP) | — | participa.md:37 (RNF-B1-038) |
| `is_active` | BOOLEAN | SI | micrositio publicado/despublicado | — | [INFERIDO] |
| `created_at` | TIMESTAMP | SI | — | — | [INFERIDO] |
| `updated_at` | TIMESTAMP | SI | — | — | [INFERIDO] |

### E4 — `interest_group`

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| `code` | VARCHAR(50) | SI | `NNA`, `mujeres`, `discapacidad`, `adultos_mayores`, `etnicos`, `LGBTIQ+` | UNIQUE | participa.md:19, :82 |
| `label` | VARCHAR(255) | SI | etiqueta legible | — | participa.md:19, :82 |
| `description` | TEXT | NO | descripción normativa del grupo | — | participa.md:92 [A-07: no definida normativamente] |

**Nota [AMBIGUO]:** la caracterización normativa de los grupos no está definida (línea 92, [A-07]). El dominio del campo `code` es una enumeración derivada exclusivamente de la lista explícita del documento.

### E5 — `regulatory_agenda`

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| `period` | VARCHAR(20) | SI | año o periodo de vigencia | — | [INFERIDO] |
| `publication_date` | DATE | SI | fecha de publicación de la agenda | — | [INFERIDO] |
| `document_url` | VARCHAR(2048) | NO | enlace al documento de agenda | — | participa.md:18, :87 |
| `created_at` | TIMESTAMP | SI | — | — | [INFERIDO] |

**Nota [AMBIGUO]:** la fuente solo menciona "publicar Agenda Regulatoria" (línea 18) y que "se enlaza desde Transparencia" (línea 87). Sus atributos internos no se describen. Si Transparencia ya la modela, E5 puede colapsar a una FK desde E2. Requiere arbitraje del orquestador.

### E6 — `participation_result`

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| `regulatory_project_id` | INTEGER | SI | FK a `regulatory_project` | FK | participa.md:28, :53, :76 |
| `published_at` | TIMESTAMP | SI | fecha/hora de publicación del consolidado | — | participa.md:28, :76 |
| `observations_document` | TEXT | SI | texto consolidado de observaciones recibidas | — | participa.md:28, :76 |
| `entity_response` | TEXT | SI | respuesta de la entidad: cómo se incorporaron (o no) y motivo | — | participa.md:28, :53, :76 |
| `alert_pending` | BOOLEAN | SI | `true` si la consulta está cerrada pero sin resultado publicado | — | participa.md:76 (criterio negativo HU-05-D02) |
| `created_at` | TIMESTAMP | SI | — | — | [INFERIDO] |
| `updated_at` | TIMESTAMP | SI | — | — | [INFERIDO] |

### E7 — `citizen` (CANDIDATA-COMPARTIDA)

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| *(demás atributos definidos por módulo compartido)* | — | — | — | — | participa.md:59, :65, :66, :67 |

**Nota:** este módulo solo menciona al ciudadano como actor. Sus atributos completos deben resolverse en la entidad compartida de identidad/autenticación de la sede.

### E8 — `participation_admin` (CANDIDATA-COMPARTIDA / [INFERIDO])

| Campo | Tipo | Obligatorio | Dominio / valores | PK/FK | Fuente |
|-------|------|-------------|-------------------|-------|--------|
| `id` | INTEGER (SERIAL) | SI | secuencial | PK | [INFERIDO] |
| *(atributos de usuario resueltos en entidad compartida de roles)* | — | — | — | — | participa.md:75 (HU-05-D01) |

**Nota [INFERIDO]:** el rol "administrador de participación" emerge de HU-05-D01 (línea 75) pero no se describe como entidad. Se modela como rol dentro del sistema de usuarios compartido de la sede.

---

## 3. Reglas de negocio con impacto en datos

| ID | Regla | Impacto en datos | Cita normativa | Fuente |
|----|-------|-----------------|----------------|--------|
| RN-B1-025 | La sección "Participa" debe publicarse con espacios de las 4 fases DAFP. | El campo `phase` de `participation_mechanism` debe restringirse al dominio `{Diagnóstico, Planeación, Ejecución, Evaluación}` con `CHECK`. | Res. 2893/2020 Anexo 2, 4.1.2.3 | participa.md:43 |
| RN-B1-039 | Habilitar consulta pública de proyectos de norma con fecha máxima de comentarios; integración con SUCOP. | `regulatory_project.comment_deadline` NOT NULL; `sucop_url` NOT NULL. | Ley 1712/2014; lineamientos DNP | participa.md:44 |
| RN-05-D01 | **Cierre automático por vencimiento:** al expirar `comment_deadline`, el mecanismo/proyecto pasa a estado `Cerrada`; no se aceptan aportes extemporáneos. | Requiere proceso/trigger que evalúe `NOW() >= comment_deadline` y actualice `status = 'Cerrada'` + `auto_close_triggered_at = NOW()`. Restricción de escritura condicional al estado. | Ley 1712/2014; lineamientos DNP/SUCOP | participa.md:52 |
| RN-05-D02 | **Publicación obligatoria del resultado:** cerrada una consulta, se publica consolidado de observaciones y respuesta de la entidad. | Genera registro en `participation_result`. `alert_pending = true` si `regulatory_project.status = 'Cerrada'` y no existe `participation_result` asociado. | Decreto 1081/2015 Art.2.1.2.1.14; Ley 1757/2015 | participa.md:53 |
| RNF-B1-038 | Contenidos de micrositios en lenguaje claro (Guía DNP). | Campo `language_level` con valor controlado; `has_alt_texts = true` obligatorio para publicar micrositio. | Guía DNP lenguaje claro; WCAG 2.1 AA | participa.md:37 |
| [PREGUNTA ABIERTA] | ¿Los aportes se reciben dentro de la sede o íntegramente en SUCOP? | Si hay CRUD propio: se necesita entidad `citizen_comment` con FK a `regulatory_project`, `citizen`, timestamp y texto. Si solo redirección: no hay tabla de comentarios; solo el `sucop_url`. | — | participa.md:30 |

---

## 4. Cardinalidades y relaciones

| Relación | Cardinalidad | Descripción | Fuente |
|----------|-------------|-------------|--------|
| `regulatory_project` → `participation_result` | 1:0..1 | Un proyecto normativo puede tener cero o un resultado publicado. | participa.md:28, :53, :76 |
| `regulatory_project` → `regulatory_agenda` | N:0..1 | Varios proyectos pueden pertenecer a una agenda regulatoria; la agenda puede estar vacía. [AMBIGUO] | participa.md:18, :87 |
| `interest_group_microsite` → `interest_group` | N:1 | Cada micrositio pertenece a exactamente un grupo de interés; un grupo puede tener cero o más micrositios (aunque en la práctica 1). [INFERIDO] | participa.md:82 |
| `citizen` → `regulatory_project` | M:N (a través de SUCOP) | El ciudadano aporta comentarios a proyectos normativos; la relación física reside en SUCOP. Si se modela internamente: tabla `citizen_comment`. [AMBIGUO — depende de pregunta abierta] | participa.md:59, :65, :66, :30 |
| `participation_admin` → `participation_mechanism` | 1:N [INFERIDO] | Un administrador gestiona el ciclo de vida de múltiples mecanismos. | participa.md:75 |
| `participation_admin` → `regulatory_project` | 1:N [INFERIDO] | Un administrador gestiona el ciclo de vida de múltiples proyectos normativos. | participa.md:75 |

---

## 5. Jerarquías ISA / polimorfismo

### 5.1 Jerarquía ISA potencial: `participable_item`

El documento describe dos tipos de entidades que comparten un ciclo de vida con estado (`Abierta → Cerrada`), fecha máxima de comentarios y cierre automático por vencimiento:

- `participation_mechanism` (fases DAFP)
- `regulatory_project` (normas en consulta SUCOP)

Ambas comparten: `status`, `validity_end` / `comment_deadline`, `auto_close_triggered_at`.

**Evaluación ISA:**
- Supertipo candidato: `participable_item` con atributos comunes: `status`, `deadline`, `auto_close_triggered_at`.
- Subtipos: `participation_mechanism` (agrega `phase`) y `regulatory_project` (agrega `sucop_url`, `body_text`, `publication_date`).
- Restricción de disyunción: **disjoint** (un ítem es mecanismo o proyecto, no ambos).
- Completitud: **parcial** (el supertipo podría extenderse a futuros tipos).
- **[INFERIDO]** — el documento no nombra esta jerarquía explícitamente; emerge del análisis de atributos compartidos y la regla RN-05-D01 que aplica a ambos.

**Decisión de mapeo recomendada:** tabla por subtipo (supertype-subtype) para preservar integridad referencial de `participation_result` contra el supertipo común. Requiere confirmación del orquestador.

### 5.2 Polimorfismo: `participation_result` referencia a `regulatory_project`

La entidad `participation_result` actualmente referencia solo a `regulatory_project`. Si en el futuro aplica también a `participation_mechanism`, se convierte en asociación polimórfica. Por ahora: FK directa y determinista. No hay polimorfismo activo en la versión actual del documento.

---

## 6. Inferencias [INFERIDO]

| # | Inferencia | Justificación | Campo/tabla afectada |
|---|-----------|---------------|----------------------|
| I1 | Campos `created_at` / `updated_at` en todas las entidades persistentes. | Trazabilidad exigida por Decreto 1081/2015 Art.2.1.2.1.14 (rendición); buena práctica de auditoría en OLTP. | Todas las entidades |
| I2 | Campo `id` SERIAL como PK sustituta en todas las entidades. | Ninguna entidad tiene clave natural completa y no ambigua declarada en el documento. | Todas las entidades |
| I3 | Campo `is_active` en `interest_group_microsite`. | Los micrositios pueden despublicarse sin eliminarse; el documento solo menciona acceso, no eliminación. | `interest_group_microsite` |
| I4 | Campo `slug` UNIQUE en `interest_group_microsite`. | Acceso por URL directa citado en criterio negativo HU-B1-016/HU-B3-027 (líneas 65, 66). | `interest_group_microsite` |
| I5 | Entidad `citizen_comment` (condicional). | Si los aportes se reciben en la sede (pregunta abierta, línea 30): necesita tabla con FK a `regulatory_project`, `citizen`, timestamp y texto del aporte. Si solo redirección a SUCOP, no existe. | Pendiente resolución pregunta abierta |
| I6 | Jerarquía ISA `participable_item` como supertipo de `participation_mechanism` y `regulatory_project`. | Atributos y reglas de negocio comunes (RN-05-D01). Ver §5.1. | Diseño lógico |
| I7 | Rol `participation_admin` como rol dentro del sistema de usuarios compartido, no entidad independiente. | HU-05-D01 describe acciones back-office pero no atributos específicos del administrador. | Sistema de roles compartido |
| I8 | `participation_result.alert_pending` como campo derivable por query, pero materializado para alertas en tiempo real al responsable. | Criterio negativo HU-05-D02 (línea 76): "el sistema alerta el pendiente al responsable". | `participation_result` |

---

## 7. Vacíos (información esperada ausente)

| # | Vacío | Impacto en BD | Fuente |
|---|-------|---------------|--------|
| V1 | **¿Los aportes ciudadanos se reciben dentro de la sede o solo en SUCOP?** (pregunta abierta, línea 30) | Determina si existe la tabla `citizen_comment`. Sin resolución, el esquema queda incompleto para ese flujo. | participa.md:30 |
| V2 | **Atributos completos de `regulatory_agenda`**: solo se menciona su existencia y publicación. | `regulatory_agenda` podría ser tabla propia o solo un documento enlazado desde Transparencia. | participa.md:18, :87 |
| V3 | **Mecanismos de participación específicos del Distrito de Santa Marta y su calendario** ([PREGUNTA ABIERTA], línea 93). | Sin esta info no se puede validar si `phase` cubre todos los valores del dominio real. | participa.md:93 |
| V4 | **Caracterización normativa de grupos de interés** ([A-07], línea 92). | El dominio de `interest_group.code` se infiere de la lista del documento; podría requerir extensión normativa. | participa.md:92 |
| V5 | **Modelo de datos compartido de `citizen`**: el módulo lo usa como actor pero no define sus atributos. | Se necesita la entidad `citizen` del módulo transversal de identidad/autenticación. | participa.md:59, :65 |
| V6 | **Relación exacta entre `regulatory_project` y el módulo Transparencia**: si es fuente única (línea 87), ¿vive la entidad en Transparencia y aquí es FK? | Evita duplicación de datos y determina ownership de la entidad `regulatory_project`. | participa.md:87 |

---

*Unidad: `/var/www/proyect-doc/elicitacion/sede-electronica/05-participa/participa.md` — líneas 1–94 leídas íntegras, 0 omitidas.*
