# Diseño de Base de Datos — Sede Electrónica de Santa Marta

> **Esquema único integrado** (una sola BD relacional coherente para toda la sede, PostgreSQL 15+).
> Producido con la metodología **jose-bd** (rigor formal: álgebra relacional, FD/MVD/JD, normalización Bernstein, integridad de Codd, ACID). Marco teórico exclusivo: modelo relacional (Codd, Date, Maier, Bernstein, Silberschatz).
> **Veredicto de auditoría: ✅ APROBADO (C1–C13)** — tras 2 rondas profundas (cada fase con DELTA appendado al final de su archivo).

## Cifras del diseño (post ronda 2)
- **141 tablas firmes + 1 condicional** (`pregunta_encuesta`, solo si las preguntas de encuesta son configurables — P-13).
- **~654 campos** del corpus mapeados (0 sin trazar) — reubicados a su forma 3FN/BCNF tras la ronda 2.
- **Todas en BCNF**; 18 relaciones en 4FN; 5FN no aplica (0 JD genuinas).
- **13 jerarquías** ISA/polimórficas mapeadas (2 class-table, 5 single-table, 4 arcos exclusivos, 1 polimórfica, 3 adjacency list); 4 entidades sin clave natural (surrogate lógicamente necesario).
- **C13:** 13/23 vacíos cerrados por norma (web); el resto son decisiones de la Alcaldía, marcadas `[PENDIENTE]`.

## Refinamiento — ronda 2 profunda (descomposición de tablas anchas)
Una segunda pasada profunda sobre todas las fases auditó las tablas anchas. Distinguió, con prueba por cierre, **5 defectos reales** de **11 anchas-pero-BCNF-legítimas** (no se inventaron violaciones):
- 🔴 `cita` (3FN: contacto transitivo → CHECK condicional) · `contrato` (3FN: derivados → columnas GENERATED) · `ciudadano` (ISA disjoint → class-table + `ciudadano_juridica`, **no 4NF**: es FD funcional) · `evaluacion_sus`/`encuesta_experiencia` (1FN: grupos repetitivos → `respuesta_item_sus`/`respuesta_encuesta`) · `dependencia` (entidad ausente creada: organigrama, destino de ≥11 FK, origen del prefijo del radicado) · `solicitud` (redundancia de almacenamiento, **no violación de FN**: se eliminó la doble fuente de verdad con C21/C25/C26).
- Cada descomposición con **lossless-join (Heath caso fuerte)** y **preservación** demostradas. Decisión física: `dependencia` PK = surrogate BIGINT + `UNIQUE(codigo)` (independencia física/lógica de Codd; evita cascada masiva).
- **11 anchas confirmadas correctas** (tramite, pqrsd, documento_electronico, usuario_interno, sesion, token_oidc, certificado_digital, dataset, interop_xroad_transaction, log_auditoria, notificacion): ancho = clusters condicionales (CHECK) + polimorfismos (arco) + snapshots de auditoría inmutables + subtipos ya extraídos.

## Pipeline y artefactos (orden de lectura)

| # | Archivo | Fase | Contenido |
|---|---|---|---|
| 0 | `00-inventario.md` | Extracción consolidada | 135 entidades canónicas, 612 campos trazados, 64 reglas, conflictos, vacíos. |
| 1 | `01-dependencias.md` | Dependencias (+DELTA) | FD/MVD/JD, cierres X⁺, claves, minimal cover. |
| 2 | `02-normalizacion.md` | Normalización (+DELTA) | Síntesis Bernstein, lossless-join + preservación demostradas, BCNF/4FN. |
| 3 | `03-modelo-logico.md` | Modelo lógico (+DELTA) | Las 138 tablas con todos sus campos, PK/FK/UNIQUE/CHECK/DEFAULT, ISA/polimorfismo. |
| 4 | `04-modelo-fisico.md` | Modelo físico (+DELTA) | Tipos PostgreSQL, índices por álgebra σ/⋈/π, particiones, aislamiento, independencia. |
| 5 | `05-diagramas-trazabilidad.md` | Diagramas + trazabilidad | 3 diagramas Mermaid (conceptual/lógico/físico) + matriz requisito→tabla/columna. |
| 6 | `06-auditoria.md` | Auditoría C1–C13 | Veredicto APROBADO + checklist + observaciones. |
| * | `_investigacion-web.md` | Investigación normativa | Vacíos cerrados con ley/estándar (`[WEB]`/`[LEY]`). |
| * | `_extraccion/` | Fase 0 (fan-out) | 16 extracciones por unidad (12 módulos + 4 grupos `_global`). |

## Decisiones de diseño clave
- `tramite` (catálogo SUIT) ≠ `solicitud` (instancia/radicado) — separación por 3FN.
- `ciudadano` ≠ `usuario_interno` ≠ `servidor_publico` (vínculo `id_sigep`).
- `documento_electronico` supertipo con **arco exclusivo** por `contexto`.
- `transparencia_publicacion` supertipo class-table con constraint de cobertura total+disyunta.
- `impuesto` PK `{nombre, vigencia_desde}` (tarifa/base cambian por vigencia).
- `dependencia` (organigrama) creada por integridad referencial (destino de FK masivo).
- PK física híbrida por clase: BIGINT IDENTITY (negocio) / UUID (expuestas) / natural (catálogos).
- `solicitud`/`radicado` NO se particionan (FK entrantes + volumen no lo exige); sí `log_auditoria`, `interop_xroad_transaction`, `intento_login`, `notificacion`.

## Pendientes para la Alcaldía (no imputables al diseño)
Retención por categoría (P-01), matriz trámite→nivel de autenticación (P-02), umbrales de incidente/SUS (P-03/P-04), reintentos/timeout X-Road y TTL TSA (P-21/P-22), RTO/RPO (P-23), criterio de priorización de bloques de digitalización. La **estructura existe**; falta el **valor** de dominio.
