# Auditoría de Cumplimiento C1–C13 — BD Sede Electrónica

> Fase de auditoría del pipeline jose-bd. Auditor estricto (no complaciente) sobre los 7 artefactos + 16 extracciones. Marco: §6 de `jose-bd.md`.

## Veredicto: ✅ APROBADO (con observaciones menores)

Los 13 criterios se cumplen con evidencia concreta y rigor demostrativo real. **Ningún incumplimiento bloqueante.** Observaciones menores de pulido + vacíos `[PENDIENTE]` legítimos de negocio (correctamente marcados).

## Checklist C1–C13

| Criterio | Resultado | Evidencia |
|---|---|---|
| **C1** Corpus orquestado, "N de N, 0 omitidas" | ✅ PASA | `00-inventario.md` §0.1 "16 de 16 unidades, 0 omitidas"; `_extraccion/` tiene exactamente 16 archivos. |
| **C2** Inventario 100%, deduplicado, conflictos | ✅ PASA | §0.2 resolución de identidad (unión, no intersección); §0.3 tabla sinónimos→canónico; 16 conflictos C-05…C-20 reportados; 612/612 campos mapeados. |
| **C3** Toda tabla con todos sus campos trazados | ✅ PASA (obs.) | Modelo lógico con ficha campo-a-campo + columna `traza` (`archivo:línea`/`[WEB]`/FD); diagrama lógico enumera las 138 tablas. Obs: el inventario delega el detalle de ~70 LOCAL "por bloque"; mitigado porque el modelo lógico sí las enumera. |
| **C4** Cero invención; [INFERIDO] marcado | ✅ PASA | `[INFERIDO]`/`[I-xx]` sistemático; `dependencia` (nueva) justificada por integridad referencial, no inventada en silencio. |
| **C5** FD/MVD/JD; claves con X⁺; minimal cover | ✅ PASA | `01-dependencias.md` §1–§5: cierres iterativos, 4 sin clave natural demostradas por cierre≠R, minimal cover con (a)(b)(c) y pruebas; DELTA corrige PK impuesto. |
| **C6** FN objetivo; lossless + preservación demostradas | ✅ PASA | `02-normalizacion.md` §3 Heath con intersección identificada; §4 `(∪Fᵢ)⁺=F⁺`; §5 Fagin (12 filas espurias). DELTA refuerza pruebas de arcos por rama y cobertura ISA. |
| **C7** BCNF vs 3FN justificada | ✅ PASA | §2: BCNF y preservación no entran en conflicto (determinantes superclave; derivados→catálogo/columna generada). DELTA reclasifica `contrato`. |
| **C8** Herencia/polimorfismo mapeada | ✅ PASA | §6.1 ISA class-table (transparencia) + single-table (documento); arcos exclusivos con CHECK; antipatrón `(entidad_tipo,entidad_id)` con trigger. |
| **C9** Físico, tipos concretos, índices por álgebra | ✅ PASA | `04-modelo-fisico.md` §2 índices con operación σ/⋈/π; BTREE/GIN/BRIN justificados, 0 HASH consciente; DELTA agrega FK `id_dependencia` faltantes. |
| **C10** Tres diagramas Mermaid válidos | ✅ PASA | `05` §1 conceptual, §2 lógico (10 subdiagramas), §3 físico; 12 bloques `erDiagram` válidos. |
| **C11** Aislamiento + independencia física/lógica | ✅ PASA | `04` §4 aislamiento por operación (anomalía nombrada); §6 vistas + tablespaces + particionado. |
| **C12** Trazabilidad requisito→tabla/columna | ✅ PASA | `05` §4 matriz por módulo; 0 requisitos con dato persistente sin mapear; presentación/infra fuera de BD justificada. |
| **C13** Investigación web citada | ✅ PASA | `_investigacion-web.md` 8 vacíos resueltos por norma `[WEB]`/`[LEY]`, integrados aguas abajo (AGN 060/2001, Decreto 620/2020, etc.). |

## Incumplimientos bloqueantes
**Ninguno.**

## Observaciones menores (pulido, no rechazo)
1. **Inventario delega detalle de campos LOCAL** (§ L515-517): trazar fila-por-fila las ~70 LOCAL también en el inventario (ya enumeradas en modelo lógico/diagrama). Mitigado.
2. **Conteo entidades diagrama conceptual** (35 declarado vs ~33 en el bloque): alinear el número.
3. **Conteo MVD** (6→8→9): el DELTA norm §E.2 ya detectó la 9ª (`expediente ↠ documentos|radicados`); unificar a 8 MVD descompuestas + 1 ya resuelta estructuralmente. No afecta FN.
4. **`dataset` clave natural** `{nombre, entidad_publicadora}` [POR CONFIRMAR]: surrogate provisional; validar con la Alcaldía.
5. **`radicado` FK a `dependencia`**: verificar que `prefijo_dependencia` (o `codigo_dependencia`) sea la columna referenciable.

## Vacíos [PENDIENTE] legítimos (de negocio, estructura existe — NO incumplimientos)
P-01 retención por categoría; P-02 matriz trámite→nivel_auth (dominio web aportado, asignación de la entidad); P-03 umbral incidente grave; P-04 umbral SUS; P-21/P-22 reintentos/timeout X-Road y TTL TSA (PDF AND no legible); P-23 RTO/RPO; RF-B3-150 criterio de priorización de bloques.

## Cierre
Paquete de calidad alta: demostraciones reales (Heath/Fagin/Bernstein), decisión BCNF/3FN argumentada con las FD en juego, y DELTA por fase actuando como auto-auditoría adversarial que capturó errores genuinos (PK impuesto 2FN, contrato 3FN, FK dependencia, notificacion.entidad_id, FK entrantes a tablas particionadas). Vacíos restantes son de negocio, honestamente marcados. **Veredicto: APROBADO.**

---

# Auditoría FINAL C1–C13 (post ronda 2 — descomposición de tablas anchas)

## Veredicto: ✅ APROBADO

Tras la 2ª ronda profunda completa (extracción → dependencias → normalización → modelo → físico → diagramas), los 13 criterios siguen cumpliendo y el diseño quedó **más fino**. **Ningún incumplimiento bloqueante.** 1 observación de C10 (ya corregida) + vacíos [PENDIENTE] de negocio.

## Verificación de la ronda 2 — las 7 descomposiciones, todas CORRECTAS

| # | Tabla | Defecto | ¿Prueba real? |
|---|---|---|---|
| 1 | `cita` | 3FN transitiva contacto | ✅ cierre `{id_ciudadano}⁺≠R`; CHECK bidireccional; descartó sobre-normalización |
| 2 | `contrato` | 3FN derivados | ✅ GENERATED STORED; `tiene_otrosi`→vista (subquery no admite GENERATED) |
| 3 | `ciudadano` | ISA disjoint class-table **NO 4NF** | ✅ honestidad correcta; Heath caso fuerte; preservación h41/h42 |
| 4 | `evaluacion_sus` | 1FN grupo repetitivo | ✅ `respuesta_item_sus`; NO JSONB; puntaje vista |
| 5 | `encuesta_experiencia` | 1FN grupo repetitivo | ✅ `respuesta_encuesta`; `pregunta_encuesta` CONDICIONAL P-13 |
| 6 | `dependencia` | entidad ausente (FK rota ×≥11) | ✅ `{codigo}⁺=R`; adjacency list (NO MVD/JD); cierra integridad |
| 7 | `solicitud` | redundancia (NO-FD) | ✅ honestidad correcta; Heath caso fuerte; trigger estado↔existencia |

Las 11 anchas-BCNF-legítimas re-verificadas adversarialmente por cierre. **No se inventó ninguna violación.**

## Coherencia inter-capa (lógico R2 vs físico R2) — RESUELTA
La PK de `dependencia`: el lógico R2 puso PK natural `codigo`; el físico R2 la **revirtió a surrogate BIGINT + UNIQUE(codigo)** con FK BIGINT ON UPDATE RESTRICT (evita cascada masiva y FK anchas). Está declarado explícitamente como **independencia física/lógica de Codd** (el lógico expresa la clave natural, el físico la implementa como UNIQUE+surrogate), NO como inconsistencia colgante. Correcto. Idem `ciudadano_juridica` PK=FK al surrogate (clase E canónica).

## Checklist C1–C13: TODOS PASA
C1 (16/16, 0 omitidas) · C2 (612/612 campos, conflictos reportados) · C3 (tablas nuevas con todos los campos trazados; traza LOCAL cerrada) · C4 (cero invención; CONDICIONAL/[INFERIDO] marcados) · C5 (X⁺, minimal cover, h41-h46 irreducibles) · C6 (lossless Heath caso fuerte + preservación, reales) · C7 (BCNF vs 3FN argumentada; contrato reclasificado) · C8 (ISA ciudadano class-table + adjacency list dependencia) · C9 (índices por álgebra; 9 FK→dependencia + idx_dependencia_padre; triggers nuevos) · C10 (3+9 diagramas Mermaid válidos; 1 arista corregida) · C11 (aislamiento por operación + advisory lock organigrama; independencia) · C12 (13 filas R2, 0 requisitos sin mapear) · C13 (web citada: AGN 060/2001, art.9 Ley 1712).

## Observaciones menores
1. **C10 — arista Mermaid espuria** (`05` L2965 `ciudadano_juridica ||--o| ciudadano_juridica`) → **CORREGIDA** a `ciudadano ||--o{ ciudadano_juridica`.
2. **Conteo de tablas unificado** (resolución de la inconsistencia narrativa): **141 tablas firmes + 1 condicional** (`pregunta_encuesta`, solo si P-13 confirma preguntas configurables) = **hasta 142**. Diferencias previas (138/144) eran por contar o no `tipo_arco`/`respuesta_item_sus`(=item_evaluacion_sus)/condicionales.
3. **Nombre canónico:** `respuesta_item_sus` (alias `item_evaluacion_sus`) — usar el primero.

## Vacíos [PENDIENTE] legítimos (poblamiento/valor, NO estructura)
P-13 (pregunta configurable → PK de respuesta_encuesta), P-01/P-19 (retención), P-02 (matriz auth — dominio dado, asignación pendiente), P-03/P-04 (umbrales), estado_ejecucion (enum SECOP), P-17/P-21/P-22 (SIN-EVIDENCIA pública), organigrama oficial (códigos de dependencia), funcionario_apoyo. Ninguno impide BCNF/4FN ni bloquea el DDL.

---

# AUDITORÍA — Ronda 3 (C1–C13)

> Auditor estricto (no complaciente). Alcance: estado FINAL del diseño tras la RONDA 3 PROFUNDA
> (cuerpos R1/R2 + secciones §DELTA Ronda 3 de los 6 artefactos). Marco: §6 de `jose-bd.md`.
> Método: verificación cruzada inventario↔modelo, validación de que las "demostraciones" §C6 son
> reales, y rastreo de las 2 inconsistencias internas c.5 hasta la fuente (no a su mención en el DELTA).

## Veredicto (al cierre de la auditoría): ❌ RECHAZADO → ✅ SANEADO (ver nota de cierre)

El trabajo NETO de R3 es sólido y honesto (anti-recuento correcto, lossless/preservación demostrados
de verdad, trazabilidad campo-a-campo). El rechazo NO fue por el diseño nuevo: fue porque la
**inconsistencia c.5 — origen de la queja del usuario — seguía presente en la fuente** al momento de auditar.
Tras la auditoría se aplicaron las 2 ediciones quirúrgicas bloqueantes (ver "Nota de cierre" al final).

## Tabla criterio por criterio

| Criterio | Resultado | Evidencia |
|---|---|---|
| **C1** Corpus orquestado, "N de N, 0 omitidas" | ✅ CUMPLE | `00 §0.1` "16 de 16, 0 omitidas"; R3.A re-lee además los 5 deltas de `_global/`. |
| **C2** Inventario 100%, dedup, conflictos | ✅ CUMPLE | `00 §0.2/§0.3`; R3 añade con dedup explícito y anti-recuento. |
| **C3** Toda tabla con todos sus campos trazados | 🟡→✅ | 5 tablas nuevas R3 con fichas campo-a-campo. El PARCIAL era por las fichas R1 §1.1/§1.5 contradictorias → SANEADO (nota de cierre). |
| **C4** Cero invención; [INFERIDO] marcado | ✅ CUMPLE | `[INFERIDO]` sistemático; R3.E manda 7 vacíos al investigador. |
| **C5** FD/MVD/JD; X⁺; minimal cover | ✅ CUMPLE | `01 §A` cierres reales; FD-DEP-3 cierra P-08; Fc≈58 con LHS disjuntos. |
| **C6** FN objetivo; lossless+preservación DEMOSTRADAS | ✅ CUMPLE | `02 §A.1` prueba real Fagin/Heath para PIT/PID/POM; 9 MVD, 0 JD (4ª verificación con argumento). |
| **C7** BCNF vs 3FN justificada | ✅ CUMPLE | `02 §A`; derivados 3FN no-materializados con la FD nombrada. |
| **C8** Herencia/polimorfismo mapeada | ✅ CUMPLE | `dependencia.padre_id` adjacency list (NO MVD/JD), demostrado. |
| **C9** Físico, tipos concretos, índices por álgebra | ✅ CUMPLE | `04 §a` 5 CREATE PG15 (TIMESTAMPTZ, fillfactor=70); `04 §c` cada índice atado a σ/⋈/π. |
| **C10** Tres diagramas Mermaid válidos | ✅ CUMPLE | `05 §DELTA(a)` sub-diagrama R3 válido. Pendiente operativo: aplicar edges `§DELTA(b)` al diagrama global. |
| **C11** Aislamiento + independencia física/lógica | ✅ CUMPLE | `04 §DELTA(e)` 3 ops READ COMMITTED con anomalía nombrada; `§DELTA(f)` 4 vistas. |
| **C12** Trazabilidad requisito→tabla/columna | ✅ CUMPLE | `05 §DELTA(c)` 26 filas con `archivo:sección`. |
| **C13** Investigación web citada | ✅ CUMPLE | `00 §R3.E` 7 vacíos al investigador; AGN 060/2001 citado. |

**Al auditar: 11 CUMPLE, 1 PARCIAL (C3) bloqueante. Tras saneamiento: 13 CUMPLE.**

## Incumplimientos bloqueantes detectados (y su resolución)

1. 🔴→✅ **(C3/C4) Ficha `dependencia` duplicada y contradictoria.** `03 §1.1` definía PK surrogate
   `id_dependencia` + `codigo_dependencia` mientras R2 §A.1 define PK natural `codigo`. **SANEADO:**
   §1.1 editada para reflejar la definición física vigente (`id_dependencia BIGINT IDENTITY` PK +
   `UNIQUE(codigo)`), marcada como reconciliada con R2 §A.1 / físico clase A.
2. 🔴→✅ **(C3) FK `radicado.prefijo_dependencia` a columna inexistente.** `03 §1.5` y mapa de FK
   apuntaban a `dependencia(codigo_dependencia)`. **SANEADO:** re-apuntadas a `dependencia(codigo)`.

## Acciones correctivas NO bloqueantes (pulido) — TODAS RESUELTAS R3

3. 🟡→✅ `mecanismo_participacion.id_oficina_responsable` unificado a **`BIGINT → dependencia(id_dependencia)`** en el lógico (§DELTA b.3), alineado con el físico (independencia de Codd). Eliminada la divergencia VARCHAR/BIGINT.
4. 🟡→✅ `interop_tsa_config.url_tsa` **DROPeada** (descompuesta en `host_salida`+`puerto`, 3FN); endpoint reconstruido on-read en la vista `v_interop_tsa_config` (físico §DELTA b.4/§f).
5. 🟡→✅ Edges R3 **aplicados a los subdiagramas globales**: §2.1 (3 hijas de `plan_integracion` + aristas, eliminados los 3 JSONB), §2.4 (`mecanismo.id_oficina_responsable` + arista), §2.10 (`contenido_traduccion` + `idioma_origen` + arista). Corregida arista obsoleta §2.1 (`codigo_dependencia`→`codigo`). **Bonus:** detectado y corregido tipo de `contenido_traduccion.id_contenido` (BIGINT→**UUID**, porque `contenido` es clase B) en lógico, físico y diagramas.

**Estado final tras pulidos: ✅ APROBADO C1–C13, sin pendientes estructurales.**

## Ronda 3.1 — Incorporación de hallazgos de investigación web

Ejecutada la pasada de investigación web (`_investigacion-web.md §Ronda 3`) y elevados a tabla los 2 hallazgos con respaldo documental (decisión del usuario):
- ✅ **`carpeta_autorizacion_acceso`** (C141, NORM-BACKED Anexo 1 SCD §10): autorizaciones de la Carpeta Ciudadana como entidad 1:N (titular+entidad+conjunto+acción+vigencia). Cierra una tabla que faltaba con respaldo normativo.
- ✅ **`variable_caracterizacion` + `grupo_interes_caracterizacion`** (C142/C143, [INFERIDO] Guía DAFP v5): normalización de la caracterización DAFP a catálogo + puente valor.
- Materializadas en lógico (§a.6/a.7), físico (§DELTA R3.1, DDL PG15 + 4 índices) y diagramas (§DELTA R3.1, sub-ER + 6 filas de trazabilidad). Tipos de FK verificados (todas BIGINT clase A). **Total esquema ≈ 149 tablas.**

**Datos semilla disponibles** (poblamiento, no estructura): 10 ítems SUS en ES (R3 E-2), variables DAFP por dimensión (R3 E-3).

**Vacíos sin fuente pública que NO bloquean la estructura** (requieren a la Alcaldía/AND):
- Códigos orgánicos y jerarquía de `dependencia` → **Decreto 312/2016** (no publicado en HTML). Sin él, `dependencia.codigo` y la FK recursiva `id_dependencia_padre` quedan sin poblar.
- Contrato REST campo-a-campo de la CCD → fuera de alcance normativo (diseño del Articulador/AND).
- Baja/desintegración de portal GOV.CO → no regulada (la norma solo cubre el alta).

## Nota de cierre (post-auditoría)

Tras emitir el RECHAZADO, se aplicaron las 2 ediciones quirúrgicas bloqueantes sobre el cuerpo de
`03-modelo-logico.md` (§1.1 `dependencia` y §1.5 `radicado`), eliminando la doble definición y la FK a
columna inexistente. Con eso, el documento deja de contradecirse y los 13 criterios quedan en CUMPLE.
**Estado final: ✅ APROBADO C1–C13.** Quedan 3 pulidos no bloqueantes (ítems 3–5) y los vacíos de
investigación web (R3.E) que no afectan la estructura.

**Conclusión:** diseño de calidad alta, con auto-auditoría adversarial por fase que capturó errores genuinos en ambas rondas. **APROBADO.**
