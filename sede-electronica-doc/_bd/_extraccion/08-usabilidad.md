# Extracción BD — Módulo 08 Usabilidad

## 0. Cobertura

- Unidad: `/var/www/proyect-doc/elicitacion/sede-electronica/08-usabilidad/usabilidad.md`
- Rango leído: líneas 0–140 (141 líneas totales)
- Íntegra: **SÍ**; líneas 0–140 leídas íntegras, **0 omitidas**
- Secciones cubiertas: §1 Descripción, §2 RF (2.1, 2.2, 2.3, 2.D Delta), §3 RNF (3.D Delta), §4 RN (4.D Delta), §5 UC, §6 HU (6.D Delta), §7 Datos/Entidades, §8 Integraciones, §9 Ambigüedades

---

## 1. Entidades candidatas

El documento identifica explícitamente tres candidatas en §7 (líneas 123–127). Solo dos de ellas tienen atributos con suficiente definición para persistir en BD. La tercera es instrumental de analítica externa.

### 1.A — EvaluacionSUS

**Fuente:** §7 línea 125, UC-B1-013/UC-B2-014 (línea 100), HU-B2-019 (línea 111), HU-08-D02 (línea 121), RNF-08-D01 (línea 77), RF-B1-087/RF-B2-083 (línea 49).

Descripción: registro de una instancia de aplicación de la escala SUS (System Usability Scale) a un participante o como resultado agregado de una ronda. El documento menciona almacenamiento, cálculo automático del puntaje y exportación histórica (CSV).

**CANDIDATA-COMPARTIDA:** No hay evidencia en otros módulos de que esta entidad ya exista. Candidata propia del módulo 08. Sin embargo, la ronda SUS podría relacionarse con una entidad `PruebaDeUsuario` o `IteracionDesarrollo` si otro módulo la define. [INFERIDO]

### 1.B — Arquetipo (proto-persona)

**Fuente:** §7 línea 126. Fuentes originales #60, #153, #182.

Descripción: persona ficticia que representa un segmento de usuarios. Atributos explícitamente enumerados en el documento. Uso: herramienta de DCU para decisiones de diseño.

**CANDIDATA-COMPARTIDA:** Podría ser consumida por módulos de accesibilidad (07) y participación ciudadana (05) si modelan perfiles de usuario. Sin información en los archivos disponibles para confirmar. [AMBIGUO]

### 1.C — HistorialBusquedas

**Fuente:** §7 línea 127. Fuentes #118, #153.

Descripción: el documento menciona "historial de búsquedas y analítica de uso" como entidad del módulo, pero no define ningún atributo. La integración con Google Analytics/Hotjar (§8, línea 131) sugiere que la analítica primaria es externa. No hay RF que ordene almacenarla en la BD propia del sistema.

**Decisión: EXCLUIDA de BD propia.** El documento no especifica atributos ni reglas de persistencia local. La analítica de uso se delega a Google Analytics/Search Console/Hotjar (herramientas externas). Si en el futuro se requiere persistencia local, se necesita especificación adicional.

---

## 2. Atributos/campos por entidad

### 2.A — EvaluacionSUS

Todos los atributos que siguen tienen respaldo documental explícito.

| Campo | Tipo genérico | Obligatorio | Fuente | Notas |
|-------|--------------|-------------|--------|-------|
| `id_evaluacion_sus` | INTEGER (PK) | Sí | [INFERIDO] | Clave surrogate; el documento no define PK explícita |
| `id_ronda` | INTEGER (FK) | Sí | [INFERIDO] | Agrupador de ronda de evaluación; necesario para histórico comparable (HU-08-D02, línea 121) |
| `fecha_aplicacion` | DATE | Sí | [INFERIDO] | Requerido para histórico comparable (HU-08-D02) |
| `item_01` | SMALLINT | Sí | §7 línea 125; UC-B1-013 línea 100 | Likert 1-5 |
| `item_02` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_03` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_04` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_05` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_06` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_07` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_08` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_09` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `item_10` | SMALLINT | Sí | §7 línea 125 | Likert 1-5 |
| `puntaje_calculado` | DECIMAL(5,2) | Sí | §7 línea 125; UC-B1-013 línea 100 | Escala 0-100; calculado automáticamente |
| `benchmark` | DECIMAL(5,2) | Sí | §7 línea 125; RNF-B1-030 línea 64 | Valor de referencia; el documento fija 68 como objetivo |
| `cumple_benchmark` | BOOLEAN | Sí | [INFERIDO] | Derivado: puntaje_calculado >= benchmark; necesario para disparar plan de mejora (UC-B1-013) |

**Nota sobre modelo de rondas:** El documento exige "histórico comparable" (HU-08-D02, línea 121) y "exportable a CSV". Esto implica una entidad `RondaSUS` separada que agrupa evaluaciones individuales. Se modela abajo.

#### Entidad auxiliar: RondaSUS [INFERIDO por requerimiento de histórico]

| Campo | Tipo genérico | Obligatorio | Fuente | Notas |
|-------|--------------|-------------|--------|-------|
| `id_ronda` | INTEGER (PK) | Sí | [INFERIDO] | |
| `fecha_inicio` | DATE | Sí | [INFERIDO] | Necesario para comparar rondas históricas |
| `fecha_cierre` | DATE | No | [INFERIDO] | Nula mientras la ronda está abierta |
| `estado` | VARCHAR(20) | Sí | [INFERIDO] | Dominio: ABIERTA / CERRADA (RNF-08-D01 menciona "cerrada una ronda") |
| `puntaje_promedio` | DECIMAL(5,2) | No | §7 línea 125 | Promedio calculado al cerrar la ronda |
| `iteracion_desarrollo` | VARCHAR(100) | No | HU-B2-019 línea 111 | Sprint o versión asociada ("al cierre de una iteración") |
| `plan_mejora` | TEXT | No | UC-B1-013 línea 100; RF-B1-087 línea 49 | Si puntaje < 68, el equipo presenta plan de mejora |

### 2.B — Arquetipo (proto-persona)

| Campo | Tipo genérico | Obligatorio | Fuente | Notas |
|-------|--------------|-------------|--------|-------|
| `id_arquetipo` | INTEGER (PK) | Sí | [INFERIDO] | |
| `nombre_ficticio` | VARCHAR(100) | Sí | §7 línea 126 | Explícito en el documento |
| `edad` | SMALLINT | No | §7 línea 126 | Explícito en el documento |
| `ubicacion` | VARCHAR(200) | No | §7 línea 126 | Explícito en el documento |
| `nivel_digital` | VARCHAR(50) | No | §7 línea 126 | Explícito; dominio no acotado en el documento |
| `necesidades` | TEXT | No | §7 línea 126 | Explícito en el documento |
| `motivaciones` | TEXT | No | §7 línea 126 | Explícito en el documento |
| `frustraciones` | TEXT | No | §7 línea 126 | Explícito en el documento |
| `escenarios` | TEXT | No | §7 línea 126 | Explícito en el documento |

**Nota:** El documento no establece unicidad ni clave natural para `Arquetipo`. No hay regla de negocio que exija unicidad de nombre ficticio. [AMBIGUO]

---

## 3. Reglas de negocio con impacto en datos

Solo se listan las que tienen efecto directo en constraints, valores almacenados o lógica de persistencia.

| ID Regla | Enunciado con impacto en datos | Fuente | Impacto en BD |
|----------|-------------------------------|--------|---------------|
| RNF-B1-030 | Puntaje SUS promedio objetivo ≥68 (escala 0-100) | §3 línea 64; §7 línea 125 | `CHECK (puntaje_calculado BETWEEN 0 AND 100)` en `EvaluacionSUS`; campo `benchmark` fija valor 68 |
| UC-B1-013 | Si puntaje < 68, el equipo dispara plan de mejora | §5 línea 100 | `plan_mejora` en `RondaSUS` se activa condicionalmente; `cumple_benchmark` como flag derivado |
| RNF-08-D01 | Cerrada una ronda → resultados almacenados y exportables (CSV) con histórico comparable | §3.D línea 77 | Estado `CERRADA` en `RondaSUS`; puntaje_promedio calculado al cierre; necesidad de índice temporal en `fecha_cierre` |
| HU-08-D02 (negativo) | Si falla el almacenamiento al exportar, notificar error sin perder datos de la ronda | §6.D línea 121 | Transaccionalidad ACID en el cierre de ronda; la exportación CSV debe ser idempotente |
| RN-08-D01 | Tarea crítica declarada conforme solo si SUS ≥68 Y tasa_exito ≥ umbral Y tiempo_en_tarea dentro de rango | §4.D línea 94 | Requeriría campos `tasa_exito` y `tiempo_en_tarea` en `EvaluacionSUS` o en una entidad `TareaCritica`; **PREGUNTA ABIERTA** (umbral sin definir por Equipo UX) — NO se modela hasta resolver A-14 |
| RF-08-D01 | Criterio cuantitativo de aceptación: además de SUS ≥68, tasa de éxito (≥X%) y tiempo en tarea | §2.D línea 57 | Misma situación que RN-08-D01; **PREGUNTA ABIERTA**, umbral pendiente |

---

## 6. Fuera de BD (RNF/presentación) — justificado

Todo lo que sigue es comportamiento de UI, configuración de frontend/CSS, norma técnica de renderizado o integración con herramientas externas. No genera tablas ni columnas propias.

| Requisito | ID | Razón de exclusión |
|-----------|----|--------------------|
| Migas de pan coherentes con jerarquía | RF-B1-080/RF-B2-038 (línea 19) | Presentación/navegación; la jerarquía de páginas ya existe en la entidad de contenidos (módulo 01/estructura del sitio) |
| URLs limpias, SEO-friendly, jerárquicas | RF-B1-081/RF-B2-073/RF-B3-144 (línea 20) | Regla de formato de URL; si las URLs se almacenan, el campo ya existe en las entidades de contenido de otros módulos |
| Vínculos visitados diferenciados | RF-B1-082/RF-B2-042 (línea 21) | CSS/estado de navegador; sin persistencia en BD |
| Navegación global consistente (mismo menú) | RF-B2-072 (línea 22) | RNF de consistencia UI; la estructura del menú se modela en módulo 01 |
| Botón "atrás" del navegador funcional | RF-B2-074 (línea 23) | Comportamiento de sesión/SPA; sin persistencia |
| Página de inicio orientada a tareas (3 trámites a ≤2 clics) | RF-B2-071 (línea 24) | Configuración editorial/CMS; si hay configuración de "trámites destacados", pertenece a módulo de trámites o CMS, no a usabilidad |
| Procesos largos con stepper numerado | RF-B2-077 (línea 26) | Componente UI (wizard/stepper); sin persistencia propia en este módulo |
| Sin pop-ups no solicitados | RF-B1-086/RF-B2-078/RF-B3-051 (línea 27) | Regla de presentación |
| Formularios con validación dinámica en línea | RF-B1-084/RF-B2-024-026/RF-B3-143 (línea 33) | Comportamiento de frontend/Kit UI; sin persistencia en BD de usabilidad |
| Carga de archivos con tipos aceptados y tamaño máximo | RF-B3-077 (línea 34) | Configuración de componente UI; el archivo adjunto se modela en el módulo del trámite/PQRSD correspondiente |
| Desplegables con filtro de búsqueda | RF-B3-078 (línea 35) | Componente UI; sin persistencia |
| Entradas de texto con estados visuales | RF-B3-079 (línea 36) | CSS/estados de componente |
| Checkboxes/radios con label | RF-B3-080 (línea 37) | HTML semántico; sin persistencia |
| Buscador con autocompletado | RF-B1-006/RF-B3-140/141 (línea 42) | Estructura definida en módulo 01; el buscador es funcional, no un dato de usabilidad |
| Diseño responsive (breakpoints) | RF-B1-085/RF-B2-079/RF-B3-034 (línea 43) | CSS/media queries; sin persistencia |
| Código HTML/CSS válido W3C | RF-B1-089/RF-B2-075/RF-B3-138/143 (línea 44) | Norma de calidad de código; sin persistencia en BD |
| Títulos semánticos H1-H6 | RF-B1-076/RF-B2-076 (línea 45) | HTML semántico; sin persistencia |
| SEO (meta-descripción, sitemap XML, posición en Google) | RF-B1-088/RF-B2-082 (línea 46) | Configuración técnica de SEO; el sitemap XML es un archivo generado, no una tabla |
| Texto alineado izquierda, 60-80 caracteres/línea | RF-B2-081/RF-B3-050 (línea 47) | CSS/tipografía |
| Lenguaje claro, siglas explicadas, guía de voz/tono | RF-B3-146/147 (línea 49) | Editorial/norma de redacción; sin persistencia específica de usabilidad |
| Páginas de confirmación con nº referencia y próximos pasos | RF-B1-083/RF-B2-080/RF-B3-145 (línea 25) | Presentación post-transacción; el nº de referencia pertenece a la entidad Trámite/PQRSD del módulo correspondiente |
| Google Analytics / Search Console / Hotjar | §8 línea 131 | Herramientas SaaS externas; sin tabla propia |
| Validadores W3C | §8 línea 132 | Herramienta externa de calidad; sin persistencia |
| RNF de texto (60-80 chars/línea) | RNF-B1-031/RNF-B2-018 (línea 65) | CSS; sin persistencia |
| RNF resolución base y responsive hasta 320px | RNF-B1-032/RNF-B2-020/RNF-B3-035 (línea 66) | CSS/frontend |
| RNF ancho mínimo buscador | RNF-B2-019 (línea 67) | CSS |
| RNF rendimiento FCP ≤2.5s | RNF-B2-007/RNF-B3-010 (línea 68) | Infraestructura/CDN; sin persistencia de usabilidad |
| RNF SEO posición ≤10 | RNF-B2-021/RNF-B3-038 (línea 69) | Métrica externa (Google); sin persistencia directa |
| RNF Clean Code, cobertura unitaria ≥70%, Git+CI/CD | RNF-B1-043/RNF-B3-042/043 (línea 70) | Proceso de desarrollo; sin persistencia |
| RNF ≤7 ítems menú principal | RNF-B3-036 (línea 71) | UI/CMS; si existe tabla de menú en módulo 01, es allí donde aplica |
| RNF Índice Fernández-Huerta ≥60 | RNF-B3-037 (línea 72) | Norma editorial; sin persistencia específica |
| RN texto subrayado solo para hipervínculos | RN-B2-026 (línea 84) | CSS/norma de estilo |
| RN contenidos en lenguaje claro (Guía DNP) | RN-B1-038/RN-B3-037 (línea 85) | Norma editorial |
| RN HTML/CSS cumple W3C | RN-B2-029 (línea 86) | Norma de código |
| UC-B2-017 Actualizar contenido del portal | §5 línea 101 | Flujo editorial; la entidad "Contenido/Publicación" pertenece al módulo CMS (módulo 01 o equivalente) |

---

## 7. Inferencias [INFERIDO]

1. **Entidad `RondaSUS`** — El documento exige "histórico comparable" y exportación CSV (HU-08-D02, RNF-08-D01). Esto implica necesariamente agrupar evaluaciones individuales en rondas. El documento no define esta entidad explícitamente, pero es consecuencia directa del requisito. [INFERIDO]

2. **Campo `id_ronda` en `EvaluacionSUS`** — Derivado de la necesidad de agrupar y comparar rondas. [INFERIDO]

3. **Campo `cumple_benchmark`** en `EvaluacionSUS` — Derivado de la regla de disparo de plan de mejora (UC-B1-013). Podría ser una columna calculada o una vista; se propone como columna persistida para facilitar consultas y exportación CSV. [INFERIDO]

4. **Campos `tasa_exito` y `tiempo_en_tarea`** — Requeridos por RN-08-D01 y RF-08-D01 pero con umbral explícitamente abierto (PREGUNTA ABIERTA A-14). NO se modelan hasta resolución con Equipo UX. Si se definen, irían en `EvaluacionSUS` o en una entidad `ResultadoTareaCritica` separada. [INFERIDO, PENDIENTE]

5. **Claves primarias surrogates** — El documento no define claves naturales para ninguna entidad. Se infieren surrogates `INTEGER` por convención de sistemas similares. [INFERIDO]

6. **`Arquetipo` como entidad de solo lectura/referencia** — El documento no menciona CRUD de arquetipos dentro del sistema de la sede. Es probable que sea un artefacto de diseño gestionado externamente (planilla, Notion, etc.) y no una entidad operacional del sistema. Persiste en BD como referencia de diseño UX, no como dato operacional. [AMBIGUO — confirmar con Equipo UX si requiere gestión en el sistema]

---

## Vacíos detectados

1. **Umbral de tasa de éxito y tiempo en tarea** (A-14, RF-08-D01, RN-08-D01): sin definir. Bloquea el modelado completo de la evaluación de conformidad de tareas críticas. Debe resolverse con Equipo UX antes de la fase de diseño.

2. **Actor que completa la encuesta SUS**: el UC (línea 100) dice "Ciudadano, Equipo UX". No queda claro si cada fila de `EvaluacionSUS` corresponde a un ciudadano individual (anónimo o identificado) o a un evaluador del equipo. Esto afecta si se necesita FK a una entidad `Usuario` o `Participante`. [AMBIGUO]

3. **Número de participantes por ronda**: la fuente #190 se cita para la encuesta SUS pero no se incluye el contenido de esa fuente en el documento. No se sabe si hay mínimo/máximo de participantes por ronda requerido. HU-B2-019 menciona "10 usuarios" como ejemplo pero sin fijarlo como restricción formal.

4. **Relación `Arquetipo` con otros módulos**: sin información de módulos 01, 07 u otros sobre si `Arquetipo` ya existe o se planea como entidad compartida.

5. **`HistorialBusquedas` local vs. externo**: el documento lo lista como entidad del módulo pero delega la analítica a herramientas externas (Google Analytics, Hotjar). No hay RF que ordene persistirlo en la BD propia. Requiere decisión arquitectónica explícita.
