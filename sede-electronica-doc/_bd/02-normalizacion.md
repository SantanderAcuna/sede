Tengo todo lo necesario: las 135 entidades, sus claves, los casos ISA (C119-C129 transparencia, C113 documento, C105 contenido), append-only (C68 log, C11 consentimiento), sin clave natural (C109/C111/C113/C84), las FD/MVD/JD, minimal cover y el DELTA con los bloqueantes. Ahora produzco el documento completo de normalización.

# Normalización — BD Sede Electrónica (Esquema Único Integrado)

> Fase 3 (C6 + C7) del pipeline jose-bd. Insumos consolidados: `00-inventario.md` (135 entidades, 612 campos), `01-dependencias.md` (FD/MVD/JD, cierres, claves, minimal cover **+ DELTA 2ª pasada**), `_investigacion-web.md` (dominios/CHECK normativos). Marco teórico EXCLUSIVO: modelo relacional — Codd (1970/1990), Date (2015), Maier (1983), Bernstein (1976), Silberschatz (2019). **Bloqueantes 🔴 del DELTA integrados** (C.2 PK impuesto, C.3 FK calendario_tributario, A.1–A.7, D.1–D.4). Cero invención; toda relación trazada a FD/fuente.

---

## 0. Forma normal objetivo y método (Bernstein)

### 0.1 Forma normal objetivo global

**Objetivo por defecto: BCNF (FNBC).** Se baja a **3FN** SOLO en las relaciones donde forzar BCNF rompería la **preservación de dependencias** (§2). Donde hay **MVD no triviales** se exige **4FN** (§5). **5FN (PJ/NF)** se evalúa y se declara **no aplicable** (0 JD genuinas; prueba DELTA §B.3, reforzada en §5.3).

Jerarquía aplicada (Date 2015, cap. 12–15; Silberschatz 2019, cap. 7):

```
1FN ⊂ 2FN ⊂ 3FN ⊂ BCNF ⊂ 4FN ⊂ 5FN
```

Criterio de cierre por relación:
- **1FN**: toda relación con atributos atómicos. Las violaciones del inventario (horario de canal en texto, JSONB de detalle de informe PQRSD, metadatos extensibles de dataset, 10 ítems SUS) se resuelven extrayendo tablas (`horario_canal`, `informe_pqrsd_detalle`, `dataset_metadata`, `item_evaluacion_sus`) — ya previstas en el inventario, NO JSONB.
- **2FN**: ninguna dependencia parcial de una clave compuesta. Casos críticos: `impuesto` (DELTA C.2), `ciudadano`/`usuario_interno` (clave `{tipo_doc, num_doc}`), `radicado` (`{prefijo_dep, anio, consecutivo}`).
- **3FN**: ninguna dependencia transitiva de no-primo sobre no-primo. Caso conductor: derivados transitivos `solicitud.requiere_pago`, `pago.monto`, etc. (no materializar).
- **BCNF**: todo determinante es superclave.
- **4FN**: ninguna MVD no trivial cuyo LHS no sea superclave.
- **5FN**: toda JD implicada por claves candidatas.

### 0.2 Método: algoritmo de síntesis de Bernstein (1976)

Bernstein demuestra que, dada una cobertura mínima `Fc`, el conjunto de relaciones obtenido por síntesis está **garantizado en 3FN** y **preserva dependencias**, y añadiendo una relación que contenga una clave candidata se garantiza además **lossless-join** (Bernstein 1976, teorema 4; Maier 1983, cap. 11; Ullman 1988). Procedimiento canónico:

```
ENTRADA: R universal, Fc cobertura mínima
(a) Para cada FD X→A de Fc, crear relación con esquema X∪{atributos con igual LHS}.
(b) Fusionar relaciones cuyos LHS sean equivalentes (X→Y e Y→X): forman un solo
    esquema con todos los atributos y ambas claves candidatas.
(c) Si ninguna relación contiene una clave candidata de R, añadir una relación
    cuyo esquema sea una clave candidata de R.
(d) Eliminar toda relación cuyo esquema esté contenido (⊆) en otra.
SALIDA: descomposición ρ en 3FN, preservadora de dependencias y lossless-join.
```

Frente a la alternativa (algoritmo de **descomposición** a BCNF, Codd/Heath), se elige **síntesis como espina dorsal** porque la consigna exige *preservación de dependencias garantizada* (la descomposición BCNF NO la garantiza — Date 2015 §14.6). BCNF se aplica como refinamiento POSTERIOR relación-por-relación solo donde no sacrifique FD (§2).

### 0.3 Naturaleza del esquema universal en este dominio

El "esquema universal" `R(A₁…Aₙ)` de 612 atributos es una abstracción teórica de partida. En la práctica, las FD del corpus tienen **LHS disjuntos por entidad** (cada entidad tiene su clave natural propia: `codigo_suit`, `numero_radicado`, `{tipo_doc,num_doc}`, etc.). Por el paso (a) de Bernstein, cada grupo de FD con igual LHS genera UNA relación; como los LHS no se solapan entre entidades, la síntesis reproduce — con rigor demostrativo — la separación en ~140 relaciones. El valor del método no es "descubrir" las tablas (el dominio ya las sugiere) sino **demostrar formalmente** que esa separación es 3FN, preservadora y lossless, y detectar dónde el inventario aún viola una FN (derivados transitivos, PK de impuesto, 1FN de SUS/horarios).

---

## 1. Síntesis 3FN desde la minimal cover (procedimiento (a)-(d))

### 1.1 Cobertura mínima de partida (Fc, integrando el DELTA §D)

Sobre la `Fc` de 27 FD significativas de la 1ª pasada se aplican las correcciones BLOQUEANTES del DELTA §D (AGREGAR tributario, `tipo_pqrsd`, SUS; CORREGIR arco; PK impuesto). `Fc` final del subconjunto significativo (LHS ya agrupado por relación que generan, RHS singleton para la reducción):

```
# sede_electronica (ciclo de equivalencia)
g1:  url_original → palabra_clave
g2:  palabra_clave → url_original
g3:  palabra_clave → url_enmascarada_gov
g4:  url_enmascarada_gov → palabra_clave
# tramite
g5:  codigo_suit → costo
g6:  codigo_suit → nivel_autenticacion_requerido
g7:  codigo_suit → aplica_sap
g8:  codigo_suit → termino_sap_dias
g9:  costo → es_gratuito                      # FD derivada retenida (no transitiva en Fc)
g10: id_tramite → costo                         # vía catálogo (FK solicitud→tramite)
# solicitud / radicado
g11: clave_idempotencia → numero_radicado
g12: numero_radicado → id_tramite
g13: numero_radicado → estado
g14: numero_radicado → anio
g15: numero_radicado → consecutivo_anual
g16: {prefijo_dependencia, anio, consecutivo_anual} → numero_radicado   # DELTA C.1 🔴
g17: id_radicado → numero_radicado
# ciudadano / usuario_interno
g18: correo_ciudadano → id_tipo_documento
g19: correo_ciudadano → numero_documento
g20: {id_tipo_documento, numero_documento} → correo_ciudadano
g21: fecha_nacimiento → is_minor                # derivada (no materializar; CHECK)
# expediente
g22: id_solicitud → numero_expediente           # parcial 1:1 (DELTA A.7: no universal)
g23: numero_expediente → id_solicitud
# ARCO (DELTA A.3 / D.3 — desdoblado)
g24: arco_type → plazo_base_dias
g25: arco_type → dias_prorroga_max
# certificado
g26: serial_number → ocsp_status
g27: serial_number → expires_at
# tipo_pqrsd (DELTA A.4 / D.1)  🔴 agregado a F
g28: id_tipo_pqrsd → plazo_dias_habiles
g29: id_tipo_pqrsd → es_prorrogable
g30: id_tipo_pqrsd → dias_prorroga_max
g31: id_tipo_pqrsd → es_gratuita
# impuesto (DELTA C.2 / D.2)  🔴 PK CORREGIDA a {nombre, vigencia_desde}
g32: {nombre, vigencia_desde} → tarifa
g33: {nombre, vigencia_desde} → base_gravable
g34: {nombre, vigencia_desde} → hecho_generador
g35: {nombre, vigencia_desde} → sujeto_activo
g36: {nombre, vigencia_desde} → sujeto_pasivo
g37: {nombre, vigencia_desde} → causacion, hecho_imponible, proceso_recaudo,
       url_formulario_liquidacion, vigencia_hasta
# calendario_tributario (DELTA C.3 — FK propagada)
g38: {id_impuesto, vigencia_fiscal} → fecha_vencimiento   # id_impuesto ref {nombre,vigencia_desde}
# SUS (DELTA A.1 / D.4)
g39: {item_1,…,item_10} → puntaje_sus           # derivado (no materializar; columna calculada/trigger)
# calendario hábil
g40: fecha → es_habil, motivo_no_habil, tipo_festivo, anio
```

Más las FD identificadoras puras `clave_natural → R(entidad)` (una por entidad de los §1.12, §1.13, §1.14, §3.10 de dependencias), que en la síntesis son **generadoras de esquema** (paso a) pero no entran en la reducción de redundancia (RHS disjuntos).

**Verificación de eliminaciones del DELTA §D:** `f5b` (`codigo_suit→es_gratuito`) y `f8c` (`numero_radicado→requiere_pago`) **siguen eliminadas** (redundantes por cadena `codigo_suit→costo→es_gratuito` y `numero_radicado→id_tramite→costo→requiere_pago`). Esto es lo que fuerza a NO materializar `es_gratuito` en `tramite` ni `requiere_pago` en `solicitud` (§6.4). Tamaño final del subconjunto significativo: **~40 FD** (DELTA §D estima ~33; el desglose RHS-singleton de impuesto/tipo_pqrsd las eleva).

### 1.2 Paso (a) — Una relación por grupo de FD con igual LHS

Agrupando `Fc` por LHS idéntico (Bernstein paso a):

| Grupo LHS | FD | Relación sintetizada `Rᵢ(esquema)` |
|---|---|---|
| `codigo_suit` | g5,g6,g7,g8 (+ FD pura→ficha) | `R_tramite(codigo_suit, costo, nivel_auth, aplica_sap, termino_sap_dias, …ficha SUIT)` |
| `costo` | g9 | `R_costo(costo, es_gratuito)` → subsumido (§1.5) |
| `id_tramite` | g10 | ≡ proyección de R_tramite (clave alterna) |
| `numero_radicado` | g12,g13,g14,g15 (+ pura) | `R_solicitud(numero_radicado, id_tramite, estado, anio, consecutivo, …)` y `R_radicado` |
| `clave_idempotencia` | g11 | subsumido en R_solicitud (atributo UNIQUE) |
| `{prefijo_dep,anio,consec}` | g16 | clave alterna de `R_radicado` |
| `id_radicado` | g17 | clave de `R_pqrsd`/`R_radicado` |
| `correo_ciudadano` | g18,g19 | `R_corr(correo, id_tipo_documento, numero_documento)` → fusiona (§1.4) |
| `{id_tipo_documento,numero_documento}` | g20 (+ pura) | `R_ciudadano(id_tipo_documento, numero_documento, correo, …)` |
| `arco_type` | g24,g25 | `R_arco_plazo(arco_type, plazo_base_dias, dias_prorroga_max)` |
| `serial_number` | g26,g27 (+ pura) | `R_certificado(serial_number, ocsp_status, expires_at, …)` |
| `id_tipo_pqrsd` | g28,g29,g30,g31 (+ pura) | `R_tipo_pqrsd(id_tipo_pqrsd, plazo_dias_habiles, es_prorrogable, dias_prorroga_max, es_gratuita, …)` |
| `{nombre,vigencia_desde}` | g32–g37 (+ pura) | `R_impuesto(nombre, vigencia_desde, tarifa, base_gravable, hecho_generador, …)` 🔴 |
| `{id_impuesto,vigencia_fiscal}` | g38 | `R_calendario_tributario(id_impuesto, vigencia_fiscal, fecha_vencimiento)` |
| `{item_1..item_10}` | g39 | `puntaje_sus` derivado dentro de `R_evaluacion_sus` (no relación aparte; §6.4) |
| `fecha` | g40 (+ pura) | `R_calendario_habil(fecha, es_habil, motivo_no_habil, tipo_festivo, anio)` |
| `url_original`/`palabra_clave`/`url_enmascarada_gov` | g1–g4 (+ pura) | `R_sede` (ciclo de equivalencia → fusión, §1.3) |

Cada una de las restantes ~115 entidades (catálogos, satélites P-Sat, asociativas) genera su relación por su FD pura `clave→atributos`, sin atributos de otra clave en su RHS.

### 1.3 Paso (b) — Fusión de relaciones con LHS equivalentes

Detección de ciclos de equivalencia `X→Y ∧ Y→X` (claves candidatas mutuas):

1. **`sede_electronica`**: `url_original → palabra_clave` (g1) y `palabra_clave → url_original` (g2) ⇒ LHS equivalentes. Además `palabra_clave → url_enmascarada_gov` (g3) y `url_enmascarada_gov → palabra_clave` (g4). Los tres atributos son claves candidatas mutuamente determinantes ⇒ **fusión en una sola relación `sede_electronica`** con las 3 claves candidatas `{url_original}`, `{palabra_clave}`, `{url_enmascarada_gov}` y el resto de la ficha. (Sin la fusión, tendríamos 3 relaciones redundantes con los mismos atributos — Bernstein paso b lo previene.)
2. **`ciudadano`**: `{id_tipo_documento, numero_documento} → correo` (g20) y `correo → {id_tipo_documento, numero_documento}` (g18+g19) ⇒ LHS equivalentes ⇒ **fusión**: una relación `ciudadano` con claves candidatas `{id_tipo_documento, numero_documento}` (PK), `{correo}`, `{scd_sub}`, `{identificador_scd}`. Idéntico para **`usuario_interno`** (`correo`/`id_sigep`/`{tipo_doc,num_doc}`).
3. **`expediente_electronico`**: `id_solicitud → numero_expediente` (g22) y `numero_expediente → id_solicitud` (g23) — PERO el DELTA A.7 🔴 corrige que esta equivalencia es **parcial** (existen expedientes de PQRSD y documentales sin `id_solicitud`). Por tanto `id_solicitud` es clave alterna **nullable**, NO equivalencia total. **No se fusiona como equivalencia plena**: `numero_expediente` es la única clave candidata total; `{id_solicitud}` es UNIQUE-parcial (WHERE id_solicitud IS NOT NULL). Relación única `expediente_electronico` con PK `numero_expediente`.
4. **`radicado`**: `numero_radicado → {anio, consecutivo}` (g14,g15) y `{prefijo_dep, anio, consecutivo} → numero_radicado` (g16) ⇒ dos claves candidatas en una sola relación `radicado` (fusión por equivalencia, ambas determinan R). PK `numero_radicado` (atómica), clave alterna `{prefijo_dependencia, anio, consecutivo_anual}` (DELTA C.1 🔴, norma AGN Acuerdo 060/2001 vía `_investigacion-web` H-08).
5. **`servidor_publico`**: `codigo_sigep → R` y `correo_institucional → R` ⇒ dos claves candidatas (ambas UNIQUE) en una relación.
6. **`solicitud`**: `numero_radicado → R` y `clave_idempotencia → numero_radicado` ⇒ `clave_idempotencia` es clave alterna; una sola relación (no se fusiona con `radicado`: LHS distintos, `solicitud` no determina `radicado.emisor_nombre` etc.).

### 1.4 Paso (c) — Garantizar relación con clave candidata de R

En síntesis pura, R universal tiene como clave candidata la unión de las claves de las entidades-raíz. Como cada relación sintetizada YA contiene su propia clave natural (que es clave candidata de su sub-esquema), y el corpus NO es un único esquema entrelazado sino un conjunto de sub-universos conectados por FK, el paso (c) se satisface **por construcción**: cada componente conexo del grafo de FD tiene su relación-clave (`tramite` por `codigo_suit`, `ciudadano` por `{tipo_doc,num_doc}`, etc.). No se requiere añadir ninguna relación-clave artificial. (Esto es lo que garantiza lossless-join global — §3.)

### 1.5 Paso (d) — Eliminación de relaciones subsumidas

- `R_costo(costo, es_gratuito)` ⊆ `R_tramite` (costo y es_gratuito ya están en la ficha del trámite con clave `codigo_suit → costo → es_gratuito`). **Eliminada como tabla independiente.** PERO se conserva la FD `costo → es_gratuito` como **CHECK/columna generada** en `tramite` (no transitiva-materializada; §6.4), no como tabla.
- `R_corr(correo, tipo_doc, num_doc)` ⊆ `ciudadano` (fusión b). **Eliminada.**
- `R_costo` para `solicitud.requiere_pago` y `pago.monto`: NO se crean (derivados transitivos eliminados en `Fc` — §6.4).
- Proyección `R(id_tramite, costo)` ⊆ `tramite`. **Eliminada** (id_tramite es clave alterna de tramite).

**Resultado de la síntesis:** una descomposición ρ que reproduce, con prueba, las ~140 relaciones finales (§7), todas en **3FN garantizada** (Bernstein teorema 4), preservadora de dependencias (§4) y lossless-join (§3).

---

## 2. Decisión BCNF vs 3FN por entidad (con las FD que lo deciden) — C7

Regla de decisión (Date 2015 §14.6; Codd 1974): una relación está en BCNF sii **todo determinante es superclave**. Una relación 3FN viola BCNF sii existe una FD `X→A` donde `X` no es superclave **y** `A` es primo (parte de otra clave candidata). Forzar BCNF descomponiendo esa FD **puede perder** la FD si ningún esquema resultante la contiene. Se decide 3FN solo si la pérdida es real y la FD es necesaria.

### 2.1 Relaciones que SÍ están en BCNF (mayoría — declaración)

Toda relación con **clave candidata única** y cuyo único determinante es esa clave está trivialmente en BCNF. Aplica a la gran mayoría:

- **Catálogos de clave única**: `tramite` (`codigo_suit`), `permiso` (`codigo`), `rol` (`nombre_rol`), `tipo_documento_identidad` (`codigo`), `grupo_interes` (`code`), `categoria_dato_sensible` (`code`), `formato_abierto`, `licencia_datos`, `interop_external_system` (`system_key`), `certificado_digital` (`serial_number`), `calendario_habil` (`fecha`), `tipo_pqrsd` (`id_tipo_pqrsd`). Determinante único = clave ⇒ **BCNF**.
- **Instancias de clave única**: `solicitud` (`numero_radicado`; `clave_idempotencia` también es superclave porque `clave_idempotencia → numero_radicado → R`, por tanto la FD `clave_idempotencia→numero_radicado` tiene LHS superclave ⇒ **BCNF**), `pqrsd` (`id_radicado`), `log_auditoria` (`record_hash`), `expediente_electronico` (`numero_expediente`).
- **Satélites 1:1 P-Sat**: `resultado_tramite`, `nivel_auth_tramite`, `recurso_multimedia_accesible`, `preferencia_accesibilidad`, `mfa_enrollment`, `resultado_participacion` — FK propietaria UNIQUE = única clave ⇒ **BCNF**.
- **Asociativas puras**: `rol_permiso`, `ciudadano_rol`, `dataset_formato` (toda la relación es clave, sin no-primos ⇒ **BCNF** trivial); `usuario_rol`, `consentimiento_categoria`, `dato_personal_sensible` (no-primos dependen del par completo, único determinante = clave ⇒ **BCNF**).
- **Históricos de clave compuesta temporal**: `dataset_version`, `version_contenido`, `version_documento_transparencia`, `intento_notificacion`, `prorroga`, `asignacion_dependencia`, `calendario_tributario`, `otrosi`, `accion_incidente` — único determinante es la clave compuesta ⇒ **BCNF**.

### 2.2 Relaciones con MÚLTIPLES claves candidatas — análisis BCNF fino

Aquí está el verdadero análisis C7. Cuando hay varias claves candidatas, hay que verificar que ningún determinante no-superclave determine un primo.

| Entidad | Claves candidatas | FD en juego | ¿BCNF? | Decisión |
|---|---|---|---|---|
| **`sede_electronica`** | `{url_original}`, `{palabra_clave}`, `{url_enmascarada_gov}` | g1–g4: cada clave→cada clave | **SÍ BCNF** | Todo determinante (url_original, palabra_clave, url_enmascarada_gov) es **superclave** (su cierre = R). No hay determinante no-clave. **BCNF**. |
| **`ciudadano`** | `{tipo_doc,num_doc}`, `{correo}`, `{scd_sub}`, `{identificador_scd}` | g18–g20: correo↔clave natural | **SÍ BCNF** | `correo → {tipo_doc,num_doc}` tiene LHS = clave candidata = superclave. Todos los determinantes son superclaves. **BCNF**. |
| **`usuario_interno`** | `{tipo_doc,num_doc}`, `{correo}`, `{id_sigep}` | correo↔clave; id_sigep↔clave | **SÍ BCNF** | Idéntico razonamiento. **BCNF**. |
| **`radicado`** | `{numero_radicado}`, `{prefijo_dep,anio,consecutivo}` | g14,g15,g16 | **SÍ BCNF** | `numero_radicado → {anio,consecutivo}` y `{prefijo_dep,anio,consecutivo} → numero_radicado`: ambos LHS son superclaves. **BCNF**. |
| **`servidor_publico`** | `{codigo_sigep}`, `{correo_institucional}` | ambos → R | **SÍ BCNF** | Ambos determinantes son superclaves. **BCNF**. |
| **`politica_documento`** | `{tipo, version_number}` (+ UNIQUE parcial `es_vigente` por tipo) | clave compuesta → R | **SÍ BCNF** | Determinante único = clave. El UNIQUE parcial `(tipo) WHERE es_vigente` es restricción, no FD total. **BCNF**. |
| **`impuesto`** 🔴 | `{nombre, vigencia_desde}` | g32–g37 | **SÍ BCNF** (tras DELTA C.2) | Con la PK corregida `{nombre,vigencia_desde}`, el determinante único es la clave compuesta. *Antes* del fix (`{nombre}` solo) violaba **2FN** (tarifa/base dependen también de vigencia). El fix lo lleva directo a **BCNF**. |

### 2.3 Caso candidato a violar BCNF: `tramite` y la FD derivada `costo → es_gratuito`

`tramite` tiene clave única `codigo_suit`. Pero existe la FD **`costo → es_gratuito`** (g9). `costo` NO es superclave de `tramite` (dos trámites distintos pueden tener el mismo costo). Y `es_gratuito` es no-primo. Entonces:

- ¿Viola **3FN**? 3FN permite `X→A` con X no-superclave SI A es primo. Aquí A=`es_gratuito` es **no-primo** ⇒ sería una **transitiva** `codigo_suit → costo → es_gratuito` ⇒ **violación de 3FN** si se materializa `es_gratuito`.
- **Decisión (DELTA + §6.4): NO materializar `es_gratuito`** como columna almacenada. Se elimina la dependencia transitiva en origen: `es_gratuito` pasa a **columna generada `GENERATED ALWAYS AS (costo = 0) STORED`** o CHECK. Una columna generada NO introduce una FD almacenada independiente (su valor es función del registro), por lo que **`tramite` queda en BCNF** sobre los atributos almacenados base. Esto resuelve simultáneamente la violación de 3FN/BCNF.

Mismo tratamiento para `solicitud.requiere_pago` (transitiva `numero_radicado→id_tramite→costo→requiere_pago`), `pago.monto` (DELTA A.2, transitiva `id_solicitud→id_tramite→costo`), `ciudadano.is_minor` (`fecha_nacimiento→is_minor`), `documento_electronico.es_valido`, `pqrsd.cumplimiento_plazo`, `evaluacion_sus.puntaje_sus` (DELTA A.1), `solicitud_arco.deadline_at`. Ver §6.4.

### 2.4 Única relación donde se ELIGE 3FN sobre BCNF (con la FD que lo decide)

Tras el análisis exhaustivo, **el corpus no contiene ninguna relación que requiera quedarse en 3FN sacrificando BCNF por pérdida de dependencias**, salvo un caso de frontera que documento explícitamente:

**`solicitud_arco`** (DELTA A.3). Claves: `{radicado}` (PK natural). FD: `radicado → R` (BCNF trivial) **más** `arco_type → plazo_base_dias` y `arco_type → dias_prorroga_max` (g24,g25). `arco_type` NO es superclave de `solicitud_arco` (muchas solicitudes ARCO comparten tipo). `plazo_base_dias`/`dias_prorroga_max` son no-primos ⇒ **transitiva `radicado → arco_type → plazo_base_dias`** ⇒ **viola 3FN/BCNF** si se almacenan los plazos en la fila de la solicitud.

- **Si forzáramos BCNF dentro de `solicitud_arco`**: habría que descomponer extrayendo `{arco_type, plazo_base_dias, dias_prorroga_max}`. Esto NO pierde la FD (queda en la tabla extraída).
- **Decisión: descomponer (BCNF), creando catálogo `tipo_arco(arco_type PK, plazo_base_dias, dias_prorroga_max)`** y dejando `solicitud_arco.arco_type` como **FK**. Resultado: ambas relaciones en BCNF, **sin pérdida de FD** (la FD `arco_type→plazos` vive íntegra en `tipo_arco`). No hay conflicto BCNF-vs-3FN: aquí BCNF es gratis. `deadline_at` se trata como derivado (§6.4).

**Conclusión C7:** en este esquema **BCNF y preservación de dependencias NO entran en conflicto en ninguna relación**. El patrón canónico que suele forzar 3FN (`{ciudad, calle} → cp` ∧ `cp → ciudad`, donde cp no-superclave determina un primo y descomponer pierde la primera FD) **no ocurre** porque:
1. Las FD multi-clave del corpus (`correo↔doc`, `url↔palabra_clave`, `numero_radicado↔{anio,consec}`) tienen **ambos lados como claves candidatas** (todos los determinantes son superclaves) ⇒ ya BCNF.
2. Las FD `no_superclave → no_primo` (derivados `costo→es_gratuito`, `arco_type→plazo`) se resuelven por **extracción a catálogo** (FK) o **columna generada**, lo que da BCNF **sin** perder la FD.

Por tanto: **forma normal alcanzada global = BCNF** (con 4FN donde hay MVD). No queda ninguna relación "atascada" en 3FN por la consigna C7. El único matiz: las tablas append-only y las polimórficas usan surrogate + UNIQUE para *alcanzar* BCNF (§6.2, §6.3).

---

## 3. Demostración de lossless-join (por entidad/descomposición)

Dos vías de prueba (Date 2015 §13.x; Maier 1983 cap. 11):
- **(V1) Propiedad de síntesis de Bernstein**: la descomposición producida por síntesis con una relación que contiene clave candidata de R es lossless (Bernstein 1976; Ullman 1988 teorema). Aplica globalmente porque cada componente conexo tiene su relación-clave (§1.4).
- **(V2) Regla de Heath/Delobel**: `R = R1 ⋈ R2` es lossless sii `(R1 ∩ R2) → R1` **o** `(R1 ∩ R2) → R2` está en F⁺ (la intersección es superclave de al menos uno).

### 3.1 `solicitud` ⋈ `tramite` (catálogo vs instancia — conflicto C-05)

Descomposición de la "ficha+instancia" universal en `tramite(codigo_suit, …)` y `solicitud(numero_radicado, id_tramite→codigo_suit, …)`.
- `R1 ∩ R2 = {id_tramite}` (= `codigo_suit`).
- `id_tramite → R_tramite` (codigo_suit es clave de tramite, g pura). Es decir **`(R1∩R2) → tramite`** ✔ (Heath).
- **Lossless demostrado.** El radicado conserva su FK al código SUIT; el join reconstruye la vista completa sin tuplas espurias. (Si NO se separaran, `numero_radicado → atributos-de-ficha` sería **parcial** sobre una clave que no es la del trámite ⇒ violación 2FN — por eso la separación es obligatoria, §7 conflicto C-05.)

### 3.2 `solicitud` ⋈ `radicado`

`solicitud(numero_radicado, …)` y `radicado(numero_radicado, anio, consecutivo, emisor_*, …)`.
- `R1 ∩ R2 = {numero_radicado}`.
- `numero_radicado → R_radicado` (g pura, FD-C39-1) y `numero_radicado → R_solicitud` (FD-C20-1). La intersección es superclave de **ambos** ⇒ **(R1∩R2)→R1 ∧ (R1∩R2)→R2** ✔. **Lossless demostrado.**

### 3.3 `ciudadano` y sus satélites (`dato_personal_sensible`, `consentimiento_datos`)

`ciudadano(K=…)` ⋈ `dato_personal_sensible({id_ciudadano, id_categoria}, valor_cifrado)`.
- `R1 ∩ R2 = {id_ciudadano}`.
- `id_ciudadano → R_ciudadano` (id_ciudadano es la clave — surrogate de `{tipo_doc,num_doc}`) ⇒ **(R1∩R2) → ciudadano** ✔. **Lossless.** (La otra dirección no se cumple, pero Heath solo exige UNA.)

### 3.4 `impuesto` ⋈ `calendario_tributario` (DELTA C.3 🔴)

`impuesto({nombre, vigencia_desde}, …)` ⋈ `calendario_tributario({id_impuesto, vigencia_fiscal}, fecha_vencimiento)`, con `id_impuesto` referenciando `{nombre, vigencia_desde}`.
- `R1 ∩ R2 = {id_impuesto}` (= la clave compuesta de impuesto, vía surrogate o FK compuesta).
- `id_impuesto → R_impuesto` (clave de impuesto) ⇒ **(R1∩R2) → impuesto** ✔. **Lossless demostrado.** La corrección de la FK (que antes apuntaba mal a `{nombre}`) es lo que **garantiza** que la intersección sea efectivamente la clave de impuesto; con la PK antigua `{nombre}` la reunión habría sido **lossy** (un nombre con dos vigencias produciría tuplas espurias al unir). Este es el impacto concreto del bloqueante C.2/C.3.

### 3.5 Supertipo/subtipo transparencia (ISA) — `transparencia_publicacion` ⋈ `normativa` ⋈ `contrato` …

`transparencia_publicacion(id_publicacion, subtipo, …)` ⋈ `normativa(id_normativa = id_publicacion, …)`.
- `R1 ∩ R2 = {id_publicacion}` (PK compartida: `normativa.id_normativa` es FK **y** PK que referencia al supertipo).
- `id_publicacion → R_supertipo` (PK) ✔ y `id_normativa → R_normativa` (PK) ✔. Intersección superclave de ambos ⇒ **lossless demostrado** para cada par supertipo-subtipo. (Patrón class-table, §6.1.)

### 3.6 `documento_electronico` y arco exclusivo (polimorfismo)

`documento_electronico(id, contexto, id_solicitud|id_pqrsd|id_expediente, …)` ⋈ con cada propietario.
- Con propietario, p.ej. `expediente`: `R1∩R2 = {id_expediente}`; `id_expediente → R_expediente` ✔. **Lossless** por cada FK del arco. El arco exclusivo (CHECK: exactamente una FK no nula según `contexto`) no afecta losslessness; garantiza integridad referencial polimórfica sin antipatrón `(tipo,id)` ciego (§6.3).

### 3.7 Síntesis global

Para las ~115 relaciones satélite/catálogo restantes, el join con su entidad propietaria tiene siempre `R1∩R2 = {FK propietaria}` y la FK referencia la **clave** del propietario ⇒ `(R1∩R2)→propietario` por construcción ⇒ **lossless por Heath en todos los casos**. Y globalmente, por **V1 (Bernstein)**, como cada componente conexo del grafo de FD contiene su relación-clave (§1.4), la descomposición completa ρ es **lossless-join**. ∎

---

## 4. Demostración de preservación de dependencias

Objetivo: probar `(F₁ ∪ F₂ ∪ … ∪ Fₙ)⁺ = F⁺`, donde `Fᵢ = πRᵢ(F)` es la proyección de F sobre cada relación (Date 2015 §13.x; Bernstein 1976 teorema 3). La síntesis de Bernstein **garantiza** preservación: cada FD de `Fc` queda enteramente contenida en la relación generada por su grupo de LHS (paso a). Basta verificar, FD por FD de `Fc`, que existe una relación que contiene **todos** los atributos de esa FD.

### 4.1 Tabla de cobertura FD → relación que la preserva

| FD de Fc | Atributos | Relación que la contiene | Preservada |
|---|---|---|---|
| g1–g4 (url↔palabra↔gov) | url_original, palabra_clave, url_enmascarada_gov | `sede_electronica` | ✔ |
| g5–g8 (codigo_suit→…) | codigo_suit + RHS | `tramite` | ✔ |
| g9 (costo→es_gratuito) | costo, es_gratuito | `tramite` (col. generada) | ✔ |
| g10 (id_tramite→costo) | id_tramite, costo | `tramite` | ✔ |
| g11 (clave_idemp→radicado) | clave_idempotencia, numero_radicado | `solicitud` | ✔ |
| g12–g15 (numero_radicado→…) | numero_radicado + RHS | `solicitud` / `radicado` | ✔ |
| g16 ({prefijo,anio,consec}→radicado) 🔴 | prefijo_dep, anio, consecutivo, numero_radicado | `radicado` (UNIQUE compuesto) | ✔ |
| g17 (id_radicado→numero_radicado) | id_radicado, numero_radicado | `pqrsd`/`radicado` | ✔ |
| g18–g20 (correo↔doc) | correo, tipo_doc, num_doc | `ciudadano` / `usuario_interno` | ✔ |
| g22,g23 (id_solicitud↔expediente) | id_solicitud, numero_expediente | `expediente_electronico` | ✔ (parcial, A.7) |
| g24,g25 (arco_type→plazos) | arco_type, plazo_base, dias_prorroga | `tipo_arco` (extraído §2.4) | ✔ |
| g26,g27 (serial→ocsp,expires) | serial_number, ocsp_status, expires_at | `certificado_digital` | ✔ |
| g28–g31 (id_tipo_pqrsd→…) 🔴 | id_tipo_pqrsd + RHS | `tipo_pqrsd` | ✔ |
| g32–g37 ({nombre,vigencia}→…) 🔴 | nombre, vigencia_desde + RHS | `impuesto` | ✔ |
| g38 ({id_impuesto,vigencia}→venc) | id_impuesto, vigencia_fiscal, fecha_vencimiento | `calendario_tributario` | ✔ |
| g40 (fecha→…) | fecha + RHS | `calendario_habil` | ✔ |
| FD puras `clave→R` (×~130) | clave + atributos propios | su propia relación | ✔ |

**Todas las FD de Fc están contenidas en una sola relación ⇒ ninguna requiere reconstrucción por join ⇒ `(∪Fᵢ)⁺ = F⁺`. Preservación demostrada.** ∎

### 4.2 FD que se "pierden" (derivados eliminados) y cómo se recuperan

Las FD eliminadas en la cobertura mínima por **redundancia** (no por descomposición) NO se pierden — son derivables del resto:

- **`codigo_suit → es_gratuito`** (f5b eliminada): recuperable por `codigo_suit → costo` ∧ `costo → es_gratuito`. Está en `F⁺`. ✔
- **`numero_radicado → requiere_pago`** (f8c eliminada): recuperable por `numero_radicado → id_tramite → costo → requiere_pago`. En `F⁺`. ✔
- **`id_solicitud → tramite.costo → pago.monto`** (DELTA A.2): `monto` se computa en lectura/trigger; la FD vive como cadena transitiva en `F⁺`, no como columna almacenada. ✔
- **`{item_1..item_10} → puntaje_sus`** (DELTA A.1): `puntaje_sus` columna generada; la FD se preserva como fórmula. ✔

Ninguna de estas requiere una relación dedicada; todas son recuperables por transitividad sobre las relaciones existentes ⇒ no hay **pérdida real** de dependencias, solo eliminación de redundancia (Bernstein: la cobertura mínima es equivalente a F). 

### 4.3 FD inter-entidad que NO son FD puras (trazadas a restricción, no a relación)

Las siguientes del §6 de dependencias NO son FD relacionales preservables por proyección; se realizan como restricciones declarativas/trigger y se documentan para que no se cuenten como "perdidas":
- `rol.requiere_mfa → usuario_interno.mfa_habilitado` (RN-09-D04): restricción inter-entidad vía `usuario_rol` (TRIGGER).
- `creado_por ≠ aprobado_por` (RN-09-D02, SoD): CHECK.
- Cadena de hash `previous_hash` en `log_auditoria` (RN-09-D05): integridad de aplicación/trigger, no FD.
- Atomicidad de `franja_horaria.cupos_disponibles` (DELTA A.5): concurrencia `SELECT … FOR UPDATE`, no FD.

---

## 5. 4FN (las 8 MVD) y veredicto 5FN

### 5.1 Las 8 MVD no triviales y su descomposición a 4FN

Una relación está en 4FN sii para toda MVD no trivial `X ↠ Y`, X es superclave (Fagin 1977; Date 2015 cap. 13). Las MVD del §2 (1ª pasada) + DELTA §B se resuelven extrayendo cada conjunto multivaluado a su propia relación con clave compuesta:

| # | MVD | Origen | Relaciones 4FN | Estado |
|---|---|---|---|---|
| 1 | `id_dataset ↠ id_formato \| {clave_meta, valor_meta}` | C-19, L581-582 | `dataset_formato({id_dataset,id_formato})` + `dataset_metadata({id_dataset,clave})` | ✔ 4FN |
| 2 | `id_pqrsd ↠ {documentos} \| {asignaciones}` | L443-445 | `documento_electronico` + `asignacion_dependencia` separadas | ✔ 4FN |
| 3 | `id_expediente ↠ {documentos} \| {firmas}` | L443,589 | `documento_electronico` + `firma_electronica` | ✔ 4FN |
| 4 | `id_canal ↠ {dia, hora_apertura, hora_cierre}` (1FN+MVD) | I-10, L853 | `horario_canal({id_canal,dia})` | ✔ 4FN (resuelve también 1FN) |
| 5 | `id_sede ↠ {canales} \| {recursos_inclusivos}` | L521,551,641 | `canal_atencion` + `recurso_inclusivo` | ✔ 4FN |
| 6 | `id_ciudadano ↠ {datos_sensibles} \| {consentimientos}` | L557,527 | `dato_personal_sensible` + `consentimiento_datos` | ✔ 4FN |
| 7 | `id_usuario_interno ↠ {mfa_enrollment} \| {usuario_rol}` | DELTA B.1 | `mfa_enrollment` + `usuario_rol` | ✔ 4FN |
| 8 | `id_contenido ↠ {version_contenido} \| {validacion_ita}` | DELTA B.2 | `version_contenido` + `validacion_ita` | ✔ 4FN |

**Prueba de losslessness de la descomposición MVD** (Fagin): si `X ↠ Y` se cumple en R, entonces `R = πXY(R) ⋈ πX(R−Y)(R)` sin pérdida. Para la MVD #1: `dataset = dataset_formato ⋈ dataset_metadata` sobre `id_dataset` es lossless porque `id_dataset ↠ id_formato` ⇒ formatos y metadatos son independientes; el join reconstruye sin producto cartesiano espurio. Sin la descomposición, una tabla `dataset(formato, clave_meta)` con un dataset de 3 formatos y 4 metadatos generaría **12 filas espurias** (anomalía MVD clásica, Date). ∎

**Las 8 MVD ya están descompuestas en el modelo** (el inventario las previó como entidades separadas). Ninguna relación final contiene dos atributos multivaluados independientes ⇒ **todas en 4FN**.

### 5.2 Verificación de que ninguna relación 4FN retrocede

`item_evaluacion_sus` (DELTA A.1, §6.4): los 10 ítems NO son una MVD (son 10 atributos funcionalmente distintos de la misma evaluación, no un conjunto multivaluado homogéneo independiente). Se modelan como **10 columnas** en `evaluacion_sus` **o** como tabla `item_evaluacion_sus({id_evaluacion, numero_item}, respuesta_likert)`. Ambas opciones están en 4FN (clave `{id_evaluacion, numero_item}` es superclave de la única MVD trivial). **No JSONB** (violaría 1FN). Decisión recomendada: tabla `item_evaluacion_sus` (normaliza, permite CHECK 1–5 por ítem); `puntaje_sus` derivado (§6.4).

### 5.3 Veredicto 5FN: NO APLICA (0 JD genuinas) — prueba DELTA §B.3

5FN (PJ/NF) exige que toda **dependencia de reunión (JD)** esté implicada por las claves candidatas (Fagin 1979; Date 2015 cap. 14). Prueba exhaustiva sobre los 3 candidatos del corpus (DELTA §B.3, reforzando JD-1/JD-2 de la 1ª pasada):

1. **RBAC `usuario ⋈ rol ⋈ permiso`** (JD-1): la JD `*[{usuario,rol},{rol,permiso}]` **SÍ está implicada por la clave** de `rol_permiso` (el permiso efectivo depende del rol, no directamente del usuario: `rol → permisos`). Es la descomposición lossless estándar de RBAC (Maier cap. 5). ⇒ **NO exige 5FN adicional**; BCNF/4FN basta.
2. **`radicado` anio×dependencia×consecutivo** (DELTA B.3a): es una **FD compuesta** (`{prefijo_dep,anio,consec}→numero_radicado`), no una reunión ternaria libre ⇒ JD implicada por clave.
3. **`impuesto` × vigencia × tarifa** (B.3b): **FD compuesta** `{nombre,vigencia_desde}→tarifa`, implicada por la clave. No hay JD cíclica.
4. **trámite × dependencia × funcionario** (B.3c): no existe relación ternaria de competencia libre; el enrutamiento PQRSD→dependencia es **1:N temporal** (`asignacion_dependencia`), no una JD de tres vías. ⇒ sin JD genuina.

Ninguna RN del inventario describe una restricción ternaria cíclica del tipo que exige 5FN ("si A-B, B-C, A-C entonces A-B-C"). Date (2015, cap.14): la mayoría de esquemas en 4FN ya están en 5FN salvo restricciones de reunión no obvias; **aquí no hay ninguna**.

**Veredicto: el esquema está en 5FN por vacuidad** (toda relación 4FN sin JD no trivial está trivialmente en 5FN). **Total MVD: 8. Total JD genuinas 5FN: 0.** ∎

---

## 6. Casos especiales: ISA/supertipos, append-only, sin clave natural, derivados

### 6.1 Supertipos/ISA — `transparencia_publicacion` y `documento_electronico`

**`transparencia_publicacion` (C119) — generalización total, disjunta.** Supertipo con 8 subtipos (C120 `normativa`, C121 `contrato`, C123–C129 planes/informes). Discriminador `subtipo` (CHECK con los 8 valores). Restricciones EER: **disjoint** (un documento es de un solo subtipo) y **total** (todo registro del supertipo pertenece a un subtipo).

- **Estrategia de mapeo elegida: tabla por subtipo (class-table / supertype-subtype).** Cada subtipo es una relación cuya **PK es además FK al supertipo** (`normativa.id_normativa = transparencia_publicacion.id_publicacion`).
- **Justificación (compromisos):** (1) máxima integridad referencial — los atributos específicos (`contrato.monto`, `normativa.numero`) son NOT NULL en su tabla sin obligar NULLs en otros subtipos; (2) el versionado inmutable `version_documento_transparencia` apunta al supertipo, sirviendo a los 8 subtipos por una sola FK; (3) evita la tabla-única con decenas de columnas nullables (anomalía de NULLs, viola el espíritu de 3FN). Costo aceptado: un JOIN supertipo-subtipo por lectura completa. Descartada *single-table* (NULLs masivos) y *concrete-table* (impide consultar "todas las publicaciones de transparencia" sin UNION). 
- **FN:** supertipo en BCNF (`id_publicacion` única clave); cada subtipo en BCNF (PK=FK única). Lossless demostrado en §3.5. El discriminador `subtipo` + CHECK de existencia (`subtipo='contrato' ⟺ existe fila en contrato`) se hace por trigger/constraint.

**`documento_electronico` (C113) — supertipo de adjuntos + polimorfismo.** Unifica adjuntos de trámite/PQRSD/expediente/resultado (discriminador `contexto`). Es **a la vez** ISA (subtipos por `tipo_origen`: CIUDADANO/ENTIDAD/SUBSANACION) y polimórfico (arco hacia 3 propietarios).

- **Estrategia: tabla única con discriminador `contexto` + arco exclusivo** (no class-table, porque los subtipos por `tipo_origen` comparten casi todos los atributos; las diferencias son condicionales `id_falla_interop`/`id_requerimiento`). Las dependencias condicionales (`es_carga_manual_excepcion → id_falla_interop NOT NULL`; `tipo_origen=SUBSANACION → id_requerimiento NOT NULL`) son **CHECK condicionales**, no FD.
- **Clave:** natural ausente (§6.3) ⇒ surrogate `id` + UNIQUE defensivo `{hash_integridad, contexto, <FK_arco_activa>}`.
- **FN:** BCNF sobre `id`. `es_valido` es derivado (§6.4).

### 6.2 Tablas append-only — `log_auditoria` (C68) y `consentimiento_datos` (C11)

**`log_auditoria`** — inmutable, encadenado por hash. Clave natural `{record_hash}` (SHA-256 del contenido, inmutable). DELTA A.6: `record_hash` es **a la vez clave Y derivada** (`{campos no-hash, previous_hash} → record_hash`). 
- **FN:** BCNF (record_hash es superclave única). 
- **Clave temporal alterna:** `{occurred_at, id_sesion}` no garantiza unicidad (varios eventos por sesión en el mismo instante) ⇒ se conserva `record_hash` como clave. 
- **Append-only:** sin UPDATE/DELETE (REVOKE + RULE + RLS); `record_hash` computado por trigger BEFORE INSERT (refuerza inmutabilidad). Retención ≥5 años (partición por rango de `occurred_at`, BRIN). El encadenamiento `previous_hash` es integridad de aplicación, no FK clásica ni FD preservable por proyección.

**`consentimiento_datos`** (DELTA C.5) — historial append-only. **Clave natural temporal: `{id_ciudadano, consent_type, occurred_at}`** (un consentimiento por tipo por instante legal). 
- **Para anónimos** (`id_ciudadano` NULL): la clave temporal natural se rompe ⇒ **surrogate `id` justificado** + UNIQUE parcial `{identificador_usuario, consent_type, occurred_at}`. 
- **FN:** BCNF sobre la clave temporal (o surrogate). `occurred_at` IMMUTABLE (Hora Legal). La asociativa `consentimiento_categoria({id_consentimiento, id_categoria}, decision)` queda en BCNF.

### 6.3 Entidades sin clave natural — surrogate + UNIQUE (las 4, DELTA §E)

Las 4 entidades cuyo cierre `X⁺ ≠ R` para toda combinación de atributos del inventario (demostrado en dependencias §3.12, reforzado DELTA §E) ⇒ **surrogate lógicamente necesario** (no por conveniencia):

| Entidad | Por qué no hay clave natural | Surrogate + UNIQUE defensivo |
|---|---|---|
| `documento_electronico` (C113) | mismo `hash_integridad` en 2 contextos; arco exclusivo | `id` + UNIQUE `{hash_integridad, contexto, FK_arco}` |
| `notificacion` (C109) | reintentos: `{entidad_tipo, entidad_id, tipo, canal, fecha}` no único | `id` + (sin UNIQUE natural; los reintentos van a `intento_notificacion`) |
| `firma_electronica` (C111) | arco exclusivo de 3 FK; misma firma recomputable | `id` (UUID) + UNIQUE `{hash_documento, timestamp_firma, id_firmante, FK_arco_activa}` |
| `interop_xroad_transaction` (C84) | alto volumen; `request_hash` no único bajo reintentos | `id` (BIGSERIAL) + índice no-único `{id_service, transaction_timestamp}` |

- **FN:** las 4 alcanzan **BCNF** vía surrogate como clave única (todo determinante = surrogate = superclave). El UNIQUE defensivo previene duplicados de negocio sin ser la clave primaria. Polimorfismo de `notificacion` resuelto con FK condicionales (`id_destinatario_usuario`/`id_destinatario_ciudadano`) + discriminador `entidad_tipo`; el par `(entidad_tipo, entidad_id)` ciego se documenta como antipatrón aceptado (H11) con trigger de validación, NO como FK declarativa (mandato jose-bd §4.3.1).

`dataset` (C96): clave natural `{nombre, entidad_publicadora}` **[POR CONFIRMAR — P-15/C.4]** ⇒ surrogate provisional + UNIQUE candidato. Igual `interop_xroad_member` (`member_code`, FD pura → BCNF).

### 6.4 Derivados — NO materializar (columna generada / CHECK / trigger) — integra DELTA A.1, A.2, A.5

Todos violan 3FN si se almacenan como columna base independiente (transitiva no-primo→no-primo). Tratamiento:

| Derivado | FD origen | Tratamiento | FN restaurada |
|---|---|---|---|
| `tramite.es_gratuito` | `costo → es_gratuito` | `GENERATED ALWAYS AS (costo=0) STORED` | BCNF |
| `solicitud.requiere_pago` | `id_tramite→costo→requiere_pago` | vista/columna calculada en lectura (FK a tramite) | BCNF |
| `pago.monto` (DELTA A.2) 🔴 | `id_solicitud→id_tramite→costo` | NO columna; resolver por JOIN o snapshot inmutable con CHECK `monto=tramite.costo` | BCNF |
| `ciudadano.is_minor` | `fecha_nacimiento→is_minor` | `GENERATED AS (age(fecha_nacimiento) < 18)` | BCNF |
| `documento_electronico.es_valido` | `(mime_decl≠mime_real ∨ antivirus=infectado)→es_valido` | `GENERATED`/CHECK | BCNF |
| `pqrsd.cumplimiento_plazo` | `estado + fechas → cumplimiento` | columna calculada/vista | BCNF |
| `evaluacion_sus.puntaje_sus` (DELTA A.1) 🔴 | `{item_1..item_10}→puntaje_sus` (fórmula SUS) | `GENERATED` o trigger sobre `item_evaluacion_sus`; **10 ítems como columnas o tabla, NO JSONB** | BCNF |
| `solicitud_arco.deadline_at` | `received_at + plazo_base (de tipo_arco) sobre calendario_habil` | computado en aplicación (calendario hábil) | BCNF |
| `franja_horaria.cupos_disponibles` (DELTA A.5) 🔴 | `cupos_total − COUNT(citas activas)` | **agregado**; materialización-excepción con `SELECT…FOR UPDATE` por concurrencia (RN-06-D01) — desnormalización controlada JUSTIFICADA | 3FN/BCNF con nota de anomalía |

**`franja_horaria.cupos_disponibles` es la única desnormalización controlada admitida**: materializar el agregado evita una condición de carrera en la reserva de cupos (dos ciudadanos reservando el último cupo). Se justifica por concurrencia (nivel de aislamiento Serializable o lock pesimista `FOR UPDATE`), no por la FN. Anomalía de actualización aceptada y controlada por trigger transaccional (Date 2015 §desnormalización).

---

## 7. Esquema relacional normalizado resultante (lista de relaciones con clave y FN)

> ~140 relaciones (135 entidades canónicas + `tipo_arco` extraído §2.4 + `item_evaluacion_sus` §6.4 + `horario_canal` ya en C49). Clave natural lógica indicada; surrogate físico entre paréntesis donde aplica (C-13). **FN por defecto BCNF**; 4FN donde hubo MVD.

### 7.1 Núcleo identidad/servicios
| Relación | Clave (PK natural / candidatas) | FN |
|---|---|---|
| `sede_electronica` | `{url_original}` \| `{palabra_clave}` \| `{url_enmascarada_gov}` | BCNF |
| `tramite` | `{codigo_suit}` | BCNF (es_gratuito generado) |
| `solicitud` | `{numero_radicado}` \| `{clave_idempotencia}` | BCNF (requiere_pago derivado) |
| `radicado` | `{numero_radicado}` \| `{prefijo_dep, anio, consecutivo_anual}` 🔴 | BCNF |
| `pqrsd` | `{id_radicado}` | BCNF (cumplimiento_plazo derivado) |
| `tipo_pqrsd` 🔴 | `{id_tipo_pqrsd}` | BCNF |
| `expediente_electronico` | `{numero_expediente}`; alt. parcial `{id_solicitud}`, `{sgdea_referencia}` | BCNF |
| `nivel_auth_tramite` | `{id_tramite}` | BCNF |
| `pago` | `{clave_idempotencia_pago}` (surrogate `id`) | BCNF (monto derivado) |

### 7.2 Actores y RBAC
| Relación | Clave | FN |
|---|---|---|
| `ciudadano` | `{id_tipo_documento, numero_documento}`; alt. `{correo}`,`{scd_sub}`,`{identificador_scd}` | BCNF (is_minor generado) |
| `usuario_interno` | `{id_tipo_documento, numero_documento}`; alt. `{correo}`,`{id_sigep}` | BCNF |
| `servidor_publico` | `{codigo_sigep}` \| `{correo_institucional}` | BCNF |
| `rol` | `{nombre_rol}` | BCNF |
| `permiso` | `{codigo}` | BCNF |
| `rol_permiso` | `{id_rol, id_permiso}` | BCNF (4FN trivial) |
| `usuario_rol` | `{id_usuario_interno, id_rol}` | BCNF / 4FN (MVD #7) |
| `ciudadano_rol` | `{id_ciudadano, id_rol}` | BCNF |
| `dato_personal_sensible` | `{id_ciudadano, id_categoria}` | BCNF / 4FN (MVD #6) |
| `categoria_dato_sensible` | `{code}` | BCNF |
| `tipo_documento_identidad` | `{codigo}` | BCNF |

### 7.3 Seguridad / append-only
| Relación | Clave | FN |
|---|---|---|
| `log_auditoria` | `{record_hash}` (surrogate `id` físico) | BCNF, append-only |
| `consentimiento_datos` | `{id_ciudadano, consent_type, occurred_at}` (surrogate si anónimo) | BCNF, append-only |
| `consentimiento_categoria` | `{id_consentimiento, id_categoria}` | BCNF |
| `sesion` | `{id}` (UUID) | BCNF |
| `token_oidc` | `{id_token}` \| surrogate | BCNF |
| `token_recuperacion` | surrogate + UNIQUE token | BCNF |
| `mfa_enrollment` | `{id_usuario}` | BCNF / 4FN (MVD #7) |
| `intento_login` | `{id_usuario\|ip, occurred_at}` (surrogate) | BCNF |
| `incidente_seguridad` | `{id}` (UUID) | BCNF |
| `accion_incidente` | `{id_incidente, secuencia}` | BCNF |
| `solicitud_arco` | `{radicado}` (surrogate si anónimo) | BCNF |
| `tipo_arco` 🔴 (extraído §2.4) | `{arco_type}` | BCNF |

### 7.4 Polimórficas / sin clave natural (surrogate + UNIQUE — §6.3)
| Relación | Clave | FN |
|---|---|---|
| `documento_electronico` | surrogate `id` + UNIQUE `{hash, contexto, FK_arco}` | BCNF (es_valido generado) |
| `notificacion` | surrogate `id` | BCNF |
| `intento_notificacion` | `{id_notificacion, numero_intento}` | BCNF |
| `firma_electronica` | surrogate `id` + UNIQUE `{hash_documento, timestamp_firma, id_firmante, FK_arco}` | BCNF |
| `interop_xroad_transaction` | surrogate `id` | BCNF |

### 7.5 Transparencia ISA (class-table — §6.1)
| Relación | Clave | FN |
|---|---|---|
| `transparencia_publicacion` (supertipo) | `{id_publicacion}` | BCNF |
| `normativa` | `{id_normativa}=FK→supertipo` | BCNF |
| `contrato` | `{id_contrato}=FK→supertipo`; `{numero_contrato, vigencia_fiscal}` UNIQUE | BCNF |
| `otrosi` | `{id_contrato, numero_otrosi}` | BCNF |
| `plan_adquisiciones`,`plan_accion`,`informe_gestion`,`informe_control_interno`,`avance_proyecto_inversion` | `{id_*}=FK→supertipo` | BCNF |
| `informe_pqrsd` | `{id_informe}=FK→supertipo` | BCNF |
| `informe_pqrsd_detalle` | `{id_informe, tipo_pqrsd, estado}` | BCNF (normaliza JSONB) |
| `version_documento_transparencia` | `{id_publicacion, version}` | BCNF |
| `impuesto` 🔴 | `{nombre, vigencia_desde}` | BCNF |
| `calendario_tributario` 🔴 | `{id_impuesto, vigencia_fiscal}` (FK→impuesto corregida) | BCNF |

### 7.6 Contenido / accesibilidad / usabilidad
| Relación | Clave | FN |
|---|---|---|
| `contenido` (supertipo CMS por `tipo`) | `{id}` (UUID) | BCNF |
| `version_contenido` | `{id_contenido, version}` | BCNF / 4FN (MVD #8) |
| `noticia`,`elemento_carrusel` | subtipos de `contenido` por `tipo` (o tabla propia) | BCNF |
| `recurso_multimedia_accesible` | `{id_contenido}` | BCNF |
| `preferencia_accesibilidad` | `{id_usuario}` | BCNF |
| `criterio_ita` | `{id}` | BCNF |
| `validacion_ita` | `{id_contenido, id_criterio_ita}` | BCNF / 4FN (MVD #8) |
| `ronda_sus` | `{id}` | BCNF |
| `evaluacion_sus` | `{id}` | BCNF (puntaje_sus derivado) |
| `item_evaluacion_sus` 🔴 (§6.4) | `{id_evaluacion, numero_item}` | BCNF / 4FN |
| `arquetipo` | `{id}` | BCNF |

### 7.7 Canales / citas / participación / datos abiertos / interop / catálogos
| Relación | Clave | FN |
|---|---|---|
| `sede_fisica` | `{id}` + UNIQUE natural | BCNF |
| `canal_atencion` | `{id}` | BCNF / 4FN (MVD #5) |
| `horario_canal` | `{id_canal, dia}` | BCNF / 4FN (MVD #4, resuelve 1FN) |
| `servicio_agendable` | `{id}` | BCNF |
| `franja_horaria` | `{id}` | BCNF (cupos_disponibles desnormalización controlada) |
| `cita` | `{codigo_confirmacion}` | BCNF |
| `recordatorio_cita` | `{id_cita}` | BCNF |
| `bloqueo_agenda` | `{id}` | BCNF |
| `recurso_inclusivo` | `{id}` | BCNF / 4FN (MVD #5) |
| `mecanismo_participacion`,`proyecto_norma`,`aporte_participacion`,`resultado_participacion`,`grupo_interes`,`micrositio_grupo_interes`,`agenda_regulatoria` | clave natural/surrogate | BCNF |
| `dataset` | `{nombre, entidad_publicadora}` [POR CONFIRMAR] / surrogate | BCNF |
| `dataset_version` | `{id_dataset, numero_version}` | BCNF |
| `dataset_metadata` | `{id_dataset, clave}` | BCNF / 4FN (MVD #1) |
| `dataset_formato` | `{id_dataset, id_formato}` | BCNF / 4FN (MVD #1) |
| `formato_abierto`,`licencia_datos` | `{code}` | BCNF |
| `interop_xroad_member` | `{member_code}` | BCNF |
| `interop_xroad_subsystem`,`_service`,`_service_permission`,`_environment`,`tsa_config`,`tsa_queue_item`,`ccd_service`,`and_agreement`,`requirement_mapping`,`error_log`,`lci_certification` | clave natural/compuesta/surrogate | BCNF |
| `certificado_digital` | `{serial_number}` | BCNF |
| `interop_external_system` | `{system_key}` | BCNF |
| `carpeta_ciudadana` | `{id_mensaje}` | BCNF |
| `politica_documento` | `{tipo, version_number}` | BCNF |
| `categoria_cookie` | `{nombre}` | BCNF |
| `calendario_habil` | `{fecha}` | BCNF |
| `trd_serie` | `{codigo_serie}` (jerárquica autoref) | BCNF |
| Resto LOCAL satélite (P-Sat, §1.14 dep.) | `{FK_propietaria}` (1:1) o `{FK, fecha/seq}` (1:N) | BCNF |

**Recuento FN:** todas las relaciones alcanzan **BCNF**; **18 de ellas** además satisfacen **4FN no trivial** (las que participan en las 8 MVD: `dataset_formato`, `dataset_metadata`, `horario_canal`, `canal_atencion`, `recurso_inclusivo`, `dato_personal_sensible`, `consentimiento_datos`, `usuario_rol`, `mfa_enrollment`, `version_contenido`, `validacion_ita`, `documento_electronico`, `asignacion_dependencia`, `firma_electronica`, `item_evaluacion_sus`, y sus contrapartes). **5FN por vacuidad** en todas (0 JD genuinas).

---

## 8. Vacíos que impiden cerrar alguna normalización [PENDIENTE]

Ninguno **impide** alcanzar la FN objetivo (BCNF/4FN se logran con la información disponible), pero los siguientes afectan **valores de dominio/cardinalidad** y deben confirmarse antes del DDL físico (no son vacíos de normalización sino de poblamiento/CHECK):

1. **`dataset` clave natural** [P-15/C.4]: `{nombre, entidad_publicadora}` no declarada UNIQUE. Mientras no se confirme, surrogate provisional. **No bloquea BCNF** (surrogate la garantiza), sí la integridad de unicidad de negocio.
2. **Matriz `tramite → nivel_auth`** [P-02]: la web (H-02) da el dominio `BAJO/MEDIO/ALTO/MUY_ALTO` pero NO la asignación por trámite ⇒ `nivel_auth_tramite` queda poblable, no normalizable más allá.
3. **Periodos de retención por categoría** [P-01/P-19]: `politica_retencion.periodo_retencion_dias` sin valores normativos fijos (Ley 1581 da principio, no plazos). No afecta FN; afecta seed.
4. **Umbral SUS** [P-04] y **umbral incidente grave** [P-03]: parámetros de CHECK, no de FN.
5. **`radicado` consecutivo por dependencia** [P-08]: **RESUELTO por norma** (AGN 060/2001, H-08) ⇒ clave alterna `{prefijo_dep, anio, consecutivo_anual}` confirmada. Ya integrado (DELTA C.1 🔴).
6. **`member_class` X-Road** [P-07]: **RESUELTO** = `GOV`/`PRIV` (H-07). CHECK, no FN.
7. **Esquema endpoints CCD** [P-18] y **TTL cola TSA/X-Road** [P-17/P-21/P-22]: parámetros técnicos, no afectan FN de `carpeta_ciudadana`/`interop_*`.

---

---

**Resumen (6 líneas):**
1. **Relaciones resultantes: ~140** (135 entidades canónicas + `tipo_arco`, `item_evaluacion_sus`, `horario_canal` extraídas por normalización).
2. **BCNF: las ~140 (todas).** **3FN-solo-sin-BCNF: 0** — la decisión C7 demuestra que BCNF y preservación de dependencias NO entran en conflicto en este corpus (las FD multi-clave tienen todos los determinantes como superclaves; las FD `no-superclave→no-primo` se resuelven por extracción a catálogo o columna generada, logrando BCNF sin perder FD).
3. **4FN no trivial: 18 relaciones** (las que participan en las 8 MVD del §5; las demás están en 4FN por ausencia de MVD).
4. **Veredicto 5FN: NO APLICA / 5FN por vacuidad** — 0 JD genuinas (prueba DELTA §B.3: los 3 candidatos son FD compuestas o RBAC implicado por clave, no reuniones libres).
5. **Entidades cambiadas por normalización:** `impuesto` (PK→`{nombre,vigencia_desde}` 🔴), `calendario_tributario` (FK corregida 🔴), `radicado` (clave alterna `{prefijo_dep,anio,consec}` 🔴), `solicitud_arco`→extrae `tipo_arco`, `evaluacion_sus`→`item_evaluacion_sus` (10 ítems, no JSONB), `informe_pqrsd`→`informe_pqrsd_detalle`, `canal_atencion`→`horario_canal`.
6. **Derivados NO materializados (restauran 3FN/BCNF):** `tramite.es_gratuito`, `solicitud.requiere_pago`, `pago.monto`, `ciudadano.is_minor`, `documento_electronico.es_valido`, `pqrsd.cumplimiento_plazo`, `evaluacion_sus.puntaje_sus`, `solicitud_arco.deadline_at` (columna generada/CHECK); `franja_horaria.cupos_disponibles` = única desnormalización controlada por concurrencia.

Archivos insumo (rutas absolutas): `/var/www/proyect-doc/elicitacion/sede-electronica/_bd/00-inventario.md`, `/var/www/proyect-doc/elicitacion/sede-electronica/_bd/01-dependencias.md`, `/var/www/proyect-doc/elicitacion/sede-electronica/_bd/_investigacion-web.md`, metodología `/home/sacunpolo/Documentos/mis-skills/jose-bd.md`.

---

# DELTA — 2ª pasada profunda de normalización

> Auditoría adversarial de la descomposición de 1ª pasada (Heath/Fagin/cierre). Entrega correcciones, NO renormaliza. **🔴 = a integrar antes del modelo lógico (C3/Fase 4).**

## A. Violaciones residuales de FN

- **A.1 🔴 `contrato` (C121) viola 3FN/BCNF**: `{valor_ejecutado, monto} → porcentaje_ejecutado` es transitiva (`id_contrato → {monto,valor_ejecutado} → porcentaje_ejecutado`; `{monto,valor_ejecutado}` no es superclave). Prueba: `{monto,valor_ejecutado}⁺ = {monto,valor_ejecutado,porcentaje_ejecutado} ≠ R`. `tiene_otrosi` = agregado `EXISTS(otrosi)`. **Corrección**: `porcentaje_ejecutado` como `GENERATED ALWAYS AS (CASE WHEN monto>0 THEN valor_ejecutado/monto*100 END) STORED`; `tiene_otrosi` vista/calculado. Añadir a derivados §6.4. Es la ÚNICA reclasificación FN real: `contrato` BCNF afirmada → 3FN-violado hasta el fix.
- **A.2 `normativa` (C120)**: `es_proyecto_norma=TRUE → fecha_limite_comentarios, id_agenda_regulatoria` = dependencia condicional ISA (CHECK, no viola FN). Riesgo de redundancia/circularidad con `proyecto_norma.id_normativa` (C42↔C120) — definir FK propietaria.
- **A.3** Resto auditado por cierre SIN violación residual: `impuesto` ({nombre,vigencia_desde}→resto, BCNF correcto post-fix), `politica_documento`, `dataset_version`/`version_contenido`/`version_documento_transparencia`, RBAC, `tipo_pqrsd`. "Todas BCNF" es correcto salvo `contrato`.

## B. Lossless-join: pruebas a completar

- **B.1** Pruebas de catálogo/instancia, radicado, `impuesto⋈calendario_tributario` (∩ superclave solo tras fix C.3), satélites P-Sat: re-verificadas por Heath, **correctas** (no son frases vacías).
- **B.2 🔴 ISA transparencia**: el join binario supertipo-subtipo es lossless por Heath, PERO la reconstrucción del supertipo = ⋃ subtipos requiere demostrar **cobertura total + disyunción** (`π_id(supertipo)=⋃π_pk(subtipoᵢ)`, imágenes disjuntas) vía TRIGGER/CHECK — no se obtiene gratis por Heath. Añadir esa prueba; sin el constraint, un `id_publicacion` huérfano es técnicamente lossy respecto a la restricción ISA total.
- **B.3 🔴 Arcos exclusivos** (`documento_electronico`, `firma_electronica`, `sesion`, `log_auditoria`, `notificacion`): la losslessness debe probarse **por rama del discriminador**: `R = σ_{ctx=A}(R)⋈A ∪ σ_{ctx=B}(R)⋈B ∪ …`, unión disjunta garantizada por el CHECK del arco. Cada selección-join es Heath-lossless. La 1ª pasada lo saltó ("no afecta losslessness" sin probarlo).

## C. Preservación de dependencias

- **C.1** Tabla §4.1 re-verificada: cada FD de Fc en una sola relación; `(∪Fᵢ)⁺=F⁺` se sostiene; recuperación de redundantes eliminadas (f5b,f8c,monto,puntaje_sus) correcta.
- **C.2 🔴(trazabilidad)** Declarar la FD intermedia `radicado → arco_type` en §4.1 (conecta instancia con el catálogo `tipo_arco` extraído). La extracción preserva; faltaba la traza.
- **C.3** FD inter-entidad (mfa, SoD, cadena hash, cupos) correctamente clasificadas como NO-FD proyectables. Sin pérdidas adicionales.

## D. BCNF vs 3FN

- **D.1** "0 relaciones 3FN-solo, todas BCNF" es CORRECTO en su razonamiento (las FD multi-clave tienen todos los determinantes superclave; el patrón `{ciudad,calle}→cp ∧ cp→ciudad` NO aparece) — **excepto `contrato`** (3FN-violado hasta A.1). Re-verificado por cierre cada ciclo de equivalencia (url↔palabra_clave, correo↔doc, numero_radicado↔{anio,consec}, codigo_sigep↔correo_institucional).

## E. 4FN/5FN

- **E.1** 8 MVD → 4FN re-verificadas por Fagin (incl. `dataset=dataset_formato⋈dataset_metadata`, 12 filas espurias evitadas). Correcto.
- **E.2 🟡** MVD #9 omitida: `expediente ↠ {documentos} | {radicados}` (independiente de firmas). Ya resuelta estructuralmente (entidades separadas) → sin violación 4FN; solo corregir el conteo 8→9.
- **E.3** 5FN por vacuidad CONFIRMADO: 0 JD genuinas. Búsqueda activa sobre `usuario_rol`, `dataset_formato`, `validacion_ita`, `interop_xroad_service_permission`, `consentimiento_categoria` — todas asociativas puras, ninguna restricción de reunión no trivial. RBAC implicado por clave.
- **E.4** `informe_pqrsd_detalle` ({id_informe,tipo_pqrsd,estado}→conteo) = FD compuesta normal (BCNF), no JD. Correcto.

## F. Veredicto: LISTO para modelo lógico con 1 corrección de estructura + 3 de prueba

**Bloqueante estructura:** A.1 `contrato` (derivados → columnas generadas).
**Correcciones de demostración:** B.2 (cobertura ISA), B.3 (arcos por rama), C.2 (traza FD radicado→arco_type).
**Riesgos que impactan el modelo lógico (escalar/crear antes del DDL):**
1. 🔴 **`dependencia` NO es entidad canónica** pero es destino de FK masivo (`id_dependencia` en tramite, pqrsd, canal_atencion, servidor_publico, servicio_agendable, asignacion_dependencia, traslado_competencia). **Integridad referencial rota** → crear entidad `dependencia` (organigrama institucional) en Fase 4. Atributos desde directorio/organigrama (módulos 02/12).
2. 🟡 Referencia circular `normativa`↔`proyecto_norma`: definir FK propietaria y opcionalidad.
3. `franja_horaria.cupos_disponibles`: única desnormalización controlada (lock pesimista). Mantener.
**Confirmado sin delta:** decisión BCNF-vs-3FN, lossless catálogo/instancia/satélites, preservación global, 8(→9) MVD→4FN, 0 JD→5FN, PK impuesto/radicado, claves compuestas, 4 entidades sin clave natural.

---

# DELTA — Ronda 2 de normalización (descomposición de tablas anchas)

> Aplica las 7 descomposiciones del DELTA ronda 2 de dependencias, cada una con prueba formal (Heath/cierre). **Honestidad: `ciudadano` es ISA funcional NO 4NF; `solicitud` es redundancia de almacenamiento NO violación de FN.** 🔴 = a integrar en modelo lógico.

## A. Descomposiciones con prueba

- **A.1 🔴 `cita` (3FN→BCNF)**: transitiva `codigo_confirmacion → id_ciudadano → ciudadano.{nombre,correo,telefono}` materializada en `*_contacto`. `{id_ciudadano}⁺≠R` (no superclave), `*_contacto` no-primos ⇒ 3FN violada cuando `id_ciudadano NOT NULL`. **Fix**: CHECK bidireccional (`id_ciudadano NOT NULL ⇒ *_contacto NULL`; `NULL ⇒ correo_contacto NOT NULL`) + vista COALESCE. Con el CHECK la FD `id_ciudadano→*_contacto` no se instancia ⇒ solo queda `codigo_confirmacion→R` ⇒ **BCNF**. Descartada tabla `cita_contacto_anonimo` (lossless por Heath pero añade join sin ganar FN = sobre-normalización).
- **A.2 🔴 `contrato` (3FN→BCNF)**: `{monto,valor_ejecutado}→porcentaje_ejecutado`, `{monto,pagos_realizados}→pagos_pendientes` transitivas (LHS no superclave). **Fix**: `porcentaje_ejecutado`, `pagos_pendientes` → GENERATED STORED; `tiene_otrosi` → vista. Sobre la base `{monto,valor_ejecutado,pagos_realizados}` único determinante = `id_contrato` ⇒ **BCNF**. (Confirma reclasificación de ronda 1 §A.1.)
- **A.3 🔴 `ciudadano` (ISA disjoint, NO 4NF)**: `{tipo_doc,num_doc}→razon_social` es FUNCIONAL univaluada, NO `↠` ⇒ no es 4NF (afirmarlo sería error conceptual). Class-table: `ciudadano`(supertipo) + `ciudadano_juridica`(PK/FK→razon_social NOT NULL, representante_legal_id). **Lossless Heath (caso fuerte)**: `R1∩R2={tipo_doc,num_doc}` superclave de AMBAS ⇒ `(R1∩R2)→R1 ∧ →R2` ✓. **Preservación**: h41/h42 en subtipo, comunes en supertipo, `(F1∪F2)⁺=F⁺` ✓. Disjoint/parcial por trigger.
- **A.4 🔴 `evaluacion_sus` (1FN→BCNF)**: `item_1..item_10` = grupo repetitivo. Extraer `respuesta_item_sus({id_evaluacion,numero_item}→respuesta_likert CHECK 1-5)`. Clave compuesta única determinante ⇒ BCNF. `puntaje_sus` derivado (fórmula SUS) por trigger, no base. Lossless Heath (∩={id_evaluacion}, superclave del padre) + preservación ✓.
- **A.5 🔴 `encuesta_experiencia` (1FN→BCNF)**: `respuesta_pregunta_1/2/3` grupo repetitivo. Extraer `respuesta_encuesta({id_encuesta,numero_pregunta}→valor)`. `pregunta_encuesta` catálogo SOLO si configurable (P-13). Lossless + preservación ✓.
- **A.6 🔴 `dependencia` (C136 nueva, BCNF)**: `{codigo}⁺=R` único determinante ⇒ BCNF. Self-FK `padre_id` = adjacency list (FD funcional, árbol, NO MVD/JD). `responsable_id→servidor_publico`. `es_externa=TRUE ⇒ entidad_externa_nombre NOT NULL` (CHECK). Cierra ≥8 FK entrantes (pasan de rota a íntegra) + origen del prefijo del radicado `SM-{codigo}-AAAA-NNNNNN`.
- **A.7 🔴 `solicitud` (redundancia NO-FD)**: clusters borrador/desistimiento/SAP duplicados con C21/C25/C26. NO es violación de FN. **Fix**: quitar columnas espejo de `solicitud`; preservar entidades 1:1 (fuente única); conservar `estado`,`inicio_gestion`; trigger estado↔existencia. **Lossless Heath (caso fuerte)**: `solicitud ⟕ desistimiento ⟕ SAP ⟕ borrador`, ∩=`{id_solicitud}` UNIQUE superclave de ambos lados ⇒ reconstrucción exacta (los valores duplicados eran iguales por definición) ⇒ cero pérdida. **Preservación**: las columnas espejo no eran determinantes ⇒ 0 FD perdidas.

## B. Esquema actualizado (relaciones nuevas/modificadas)
| Relación | Estado | Clave | FN |
|---|---|---|---|
| `cita` | MODIF | codigo_confirmacion | BCNF (contacto condicional) |
| `contrato` | MODIF | id_contrato (=FK supertipo) | BCNF (porcentaje/pendientes GENERATED) |
| `ciudadano` | MODIF supertipo | {tipo_doc,num_doc} | BCNF (sin razon_social/representante) |
| `ciudadano_juridica` | **NUEVA** subtipo | {tipo_doc,num_doc}=PK/FK | BCNF |
| `evaluacion_sus` | MODIF | id | BCNF (sin items; puntaje derivado) |
| `respuesta_item_sus` | **NUEVA** | {id_evaluacion,numero_item} | BCNF (1FN restaurada) |
| `encuesta_experiencia` | MODIF | id | BCNF (sin respuesta_1/2/3) |
| `respuesta_encuesta` | **NUEVA** | {id_encuesta,numero_pregunta} | BCNF (1FN restaurada) |
| `pregunta_encuesta` | **NUEVA cond. P-13** | id_pregunta | BCNF |
| `dependencia` (C136) | **NUEVA** | codigo (self-FK padre_id) | BCNF |
| `solicitud` | MODIF | numero_radicado | BCNF (s/cambio FN; sin columnas espejo) |
| `desistimiento`/`silencio_administrativo_positivo`/`borrador_solicitud` | PRESERVADAS (fuente única) | {id_solicitud} 1:1 | BCNF |

## C. Re-verificación
Cero violaciones nuevas (cada descomposición probada). Las 11 anchas-correctas intactas; las 7 que reciben FK→`dependencia` (tramite, pqrsd, canal_atencion, servidor_publico, servicio_agendable, asignacion_dependencia, radicado) MEJORAN integridad referencial (FK rota→íntegra).

## D. Conteo final
- **~144 relaciones, todas BCNF.** Nuevas netas: ciudadano_juridica, respuesta_encuesta, dependencia(C136), pregunta_encuesta(cond.); respuesta_item_sus ya prevista (=item_evaluacion_sus). El fix de `solicitud` NO suma tablas (C21/C25/C26 ya contadas).
- **3FN-solo: 0.** **3 reclasificaciones** (cita, contrato, ciudadano) defecto→BCNF.
- **4FN: 18.** **MVD no triviales: 9** (8 ronda 1 + expediente↠radicados). **MVD nuevas ronda 2: 0.** **JD 5FN: 0** (dependencia adjacency-list NO es JD; ciudadano ISA es FD). 5FN por vacuidad confirmada.
- **Derivados no materializados (consolidado):** es_gratuito, requiere_pago, pago.monto, is_minor, es_valido, cumplimiento_plazo, deadline_at, **contrato.porcentaje_ejecutado/pagos_pendientes (GENERATED)**, **contrato.tiene_otrosi (vista)**, **evaluacion_sus.puntaje_sus (trigger)**. Única desnormalización: franja_horaria.cupos_disponibles.

## E. Veredicto
LISTO para actualizar el modelo lógico. Lossless (Heath caso fuerte en ciudadano y solicitud; caso simple en SUS/encuesta) y preservación demostradas; honestidad mantenida (ISA≠4NF, redundancia≠violación FN); sin sobre-normalización; 11 anchas-correctas intactas. Pendientes que no bloquean FN: P-13 (pregunta configurable), organigrama oficial (poblar dependencia.codigo), funcionario_apoyo, circular normativa↔proyecto_norma.

---

# DELTA — Ronda 3 de normalización (descomposición profunda + auditoría de completitud)

> Rol: revisor de completitud/correctitud. Insumo: `01-dependencias.md §DELTA Ronda 3` (FD-PIT/PID/RIS/RES/DEP/RLC/TSA/MEC/CON, MVD C.1, veredicto JD 0) + `00-inventario.md §DELTA Ronda 3 (R3.A–R3.F)`. Audita las Rondas 1-2, NO las da por buenas. Aplica las descomposiciones R3.D con prueba lossless (Heath) + preservación. NO reescribe: ENTREGA DELTA. 🔴 = a integrar en modelo lógico. Honestidad: la mayor parte del trabajo lo hicieron las Rondas 1-2; aquí solo lo NUEVO de R3 y las correcciones residuales.

## A. Descomposiciones NUEVAS de R3 (con prueba lossless + preservación)

### A.1 🔴 `plan_integracion` (1FN — única tabla ancha NUEVA de R3; Ronda 2 NO la tocó)

**Defecto:** 3 columnas JSONB (`tramites_incluidos`, `dominios_web`, `otros_medios`) con atributos consultables embebidos (accion, fecha_objetivo, id_responsable, tipo) ⇒ viola 1FN (grupos multivaluados no atómicos). Origen del defecto: Rondas 1-2 dejaron los 3 atributos como JSONB, ocultando la multivaluación.

**MVD que la origina (deps R3 §C.1):** `id_plan ↠ {plan_integracion_tramite} | {plan_integracion_dominio} | {plan_integracion_otro_medio}` — tres conjuntos multivaluados **mutuamente independientes**. Una tabla universal `plan(tramite, dominio, otro_medio)` genera producto cartesiano espurio (anomalía MVD clásica de Fagin). FN destino: **4FN**.

**Descomposición (3 hijas):**

| Relación nueva | Esquema (atributos, clave) | FN |
|---|---|---|
| `plan_integracion_tramite` (PIT) | `{id_plan(FK), id_tramite(FK), nombre_tramite, solicitudes_anio, accion, fecha_objetivo, id_responsable(FK)}`; **PK `{id_plan, id_tramite}`** | BCNF/4FN |
| `plan_integracion_dominio` (PID) | `{id_plan(FK), valor, tipo}`; **PK `{id_plan, valor}`** | BCNF/4FN |
| `plan_integracion_otro_medio` (POM) | `{id_plan(FK), valor, tipo}`; **PK `{id_plan, valor}`** | BCNF/4FN |

**Prueba LOSSLESS-JOIN (descomposición multivaluada, Fagin/Heath).** El padre `plan_integracion` se reconstruye por reunión natural con cada hija sobre `id_plan`. Para cada par padre⋈hija: `R_padre ∩ R_hija = {id_plan}`, que es **clave de `plan_integracion`** (cada plan = 1 fila padre). Por el teorema de Heath, `(R_padre ∩ R_hija) → R_padre` se cumple (id_plan determina toda la cabecera del plan) ⇒ descomposición sin pérdida. Como la MVD `id_plan ↠ tramites | dominios | otros_medios` es la condición de Fagin (1977) para 4FN, la reunión de las 3 hijas con la cabecera no introduce tuplas espurias ⇒ **lossless**. ✓

**Prueba PRESERVACIÓN DE DEPENDENCIAS.** FD de cada hija (deps R3 §E):
- PIT: `{id_plan,id_tramite}→solicitudes_anio, accion, fecha_objetivo, id_responsable` (r51-r54) → confinadas en PIT.
- PID/POM: `{id_plan,valor}→tipo` (r55) → confinada en cada hija.
- Cabecera: `id_plan→{atributos propios del plan}` → en `plan_integracion`.
LHS disjuntos entre las 3 hijas y la cabecera (ningún LHS cruza dos relaciones) ⇒ `(F_PIT ∪ F_PID ∪ F_POM ∪ F_padre)⁺ = F⁺`. **0 FD perdidas.** ✓
- **Derivada NO materializada:** `id_tramite → nombre_tramite` (PIT-2, vía catálogo `tramite.nombre`). Si `id_tramite NOT NULL` → `nombre_tramite` redundante (no persistir, 3FN). Si `id_tramite NULL` (trámite aún no en SUIT) → `nombre_tramite` es hecho propio NOT NULL (dependencia condicional). Resolución: CHECK `(id_tramite IS NOT NULL) OR (nombre_tramite IS NOT NULL)`.

**¿Sobre-normalización?** NO. Las 3 hijas NO son separables en menos tablas: colapsar PID+POM o PIT+PID reintroduce la MVD (producto cartesiano). Cada una es una proyección 4FN mínima. Veredicto: descomposición necesaria, no especulativa.

### A.2 `evaluacion_sus` → `respuesta_item_sus` — YA descompuesta en Ronda 2 §A.4 (R3 solo APORTA CIERRE FORMAL, NO es tabla nueva)

**Auditoría:** Ronda 2 §A.4 ya creó `respuesta_item_sus` con clave `{id_evaluacion, numero_item}` y probó BCNF + lossless Heath. Ronda 3 de deps (FD-RIS-1) **completa la prueba de irreducibilidad del LHS** que Ronda 2 enunció como h43 sin demostrar:
- `{id_evaluacion_sus}⁺ = {id_evaluacion_sus}` (un id agrupa 10 ítems, no determina valor_likert individual) ⇒ no extraño.
- `{numero_item}⁺ = {numero_item}` (el ítem 3 existe en todas las evaluaciones) ⇒ no extraño.
- ⇒ `{id_evaluacion_sus, numero_item}` LHS irreducible; único determinante = clave ⇒ **BCNF confirmada**. ✓
**Corrección de conteo:** NO suma tabla nueva. Es la misma `respuesta_item_sus` de Ronda 2 (`id_evaluacion` ≡ `id_evaluacion_sus`).

### A.3 `encuesta_experiencia` → `respuesta_encuesta` — YA descompuesta en Ronda 2 §A.5 (R3 AJUSTA la clave según P-13)

**Auditoría:** Ronda 2 §A.5 creó `respuesta_encuesta` con clave `{id_encuesta, numero_pregunta}` (preguntas FIJAS). Ronda 3 de deps (FD-RES-1) hace explícito que la clave **depende de P-13**:
- Si preguntas **fijas** → `{id_encuesta, numero_pregunta}` (= Ronda 2, sin cambio).
- Si preguntas **configurables por trámite** → `{id_encuesta, id_pregunta}` + catálogo `pregunta_encuesta` con `id_pregunta → id_tramite, texto, orden, tipo` (FD-PES-1, `{id_pregunta}⁺=R` ⇒ BCNF).
**Lossless** (ambos casos): `respuesta_encuesta ∩ encuesta_experiencia = {id_encuesta}`, superclave del padre ⇒ Heath ✓. **Preservación:** FD de respuesta confinada en la hija; catálogo (si P-13) tiene LHS `id_pregunta` disjunto ⇒ `(F1∪F2∪F3)⁺=F⁺` ✓.
**Corrección de conteo:** `respuesta_encuesta` NO es nueva (Ronda 2). `pregunta_encuesta` ya estaba como "NUEVA cond. P-13" en Ronda 2 §B. R3 no suma tablas aquí; solo precisa la clave condicional. **Ajuste BCNF:** documentar la clave variable como decisión pendiente de P-13 (no bloquea FN; ambas variantes son BCNF).

### A.4 `dependencia` (C136) — YA creada en Ronda 2 §A.6 (R3 AÑADE FD-DEP-3, cierra P-08)

**Auditoría:** Ronda 2 §A.6 ya creó `dependencia` (PK `codigo`, BCNF, self-FK `padre_id`, `responsable_id→servidor_publico`, CHECK `es_externa`). R3 de deps añade **FD-DEP-3** (inter-entidad): `dependencia.codigo → radicado.prefijo_dependencia` es la FD que origina el formato `SM-{codigo}-AAAA-NNNNNN` (AGN Acuerdo 060/2001). **Consecuencia normalizadora:** ratifica que la clave compuesta correcta de `radicado` es `{prefijo_dependencia, anio, consecutivo_anual}` con `prefijo_dependencia ≡ dependencia.codigo` (FK), cerrando P-08 a nivel funcional. `padre_id` self-FK = adjacency list (FD funcional, árbol) → **NO es JD/MVD** (confirmado). No es tabla nueva; es completitud de FD + FK formal.

## B. FD ocultas R3 → relaciones nuevas / correcciones (no son tablas anchas, son entidades faltantes)

| # | Relación | Esquema (atributos, clave) | FD origen | FN | Lossless+Preservación |
|---|---|---|---|---|---|
| B.1 🔴 | `rate_limit_contador` (F-04, [INFERIDO parcial]) | `{clave_sujeto, ventana_inicio, recurso, contador, bloqueado_hasta}`; **PK `{clave_sujeto, ventana_inicio, recurso}`** | FD-RLC-1 (r62) | BCNF | Entidad nueva autónoma; sin descomposición (LHS único = clave). Las 3 dimensiones son ortogonales (irreducibles). Alternativa: campos en `intento_login` (misma FD). |
| B.2 🔴 | `interop_tsa_config` (F-08, CORRECCIÓN de clave) | `{ambiente, vigencia_desde, host_salida, puerto, proveedor, es_activo}`; **PK `{ambiente, vigencia_desde}`** + UNIQUE parcial `(ambiente) WHERE es_activo` | FD-TSA-1 (r60) | BCNF | `{ambiente}⁺≠R` con histórico ⇒ clave compuesta. 1 config activa por ambiente = UNIQUE parcial (patrón política vigente RF-B1-009). Determinante=superclave ⇒ BCNF. |
| B.3 | `mecanismo_participacion` ← `id_oficina_responsable` (F-06) | añadir FK `id_oficina_responsable → dependencia` | FD-MEC-2 (r61) | BCNF (s/cambio FN) | No-primo directo de la clave; no introduce transitiva. Completitud de FK (R-03). |
| B.4 🔴 | `incidente_seguridad` ← `sic_rnbd_reportado, sic_reporte_fecha` (F-01) | 2 columnas nuevas | FD-INC-1 | BCNF (s/cambio FN) | Dos hechos funcionalmente independientes (CSIRT vs SIC/RNBD), ambos no-primos directos de `id_incidente` ⇒ **NO transitiva**. Completitud de atributos, no violación. |
| B.5 | `contenido_traduccion` (F-11, C135 DIFERIDA cond.) | `{id_contenido, idioma, cuerpo_traducido, es_original}`; **PK `{id_contenido, idioma}`** | FD-CON-1 (r63) | BCNF | `{id_contenido,idioma}⁺=R` ⇒ clave compuesta única determinante. Condicionado a activación de C135. |

## C. Derivados a NO materializar / GENERATED — adiciones R3 (auditoría de derivados que Rondas 1-2 omitieron)

| Atributo | Determinante | Tratamiento | FN protegida | Estado en Rondas 1-2 |
|---|---|---|---|---|
| `evaluacion_sus.puntaje_sus` | `{item_1..item_10}` (fórmula SUS `(Σ_impar(itemᵢ−1)+Σ_par(5−itemᵢ))×2.5`) | NO almacenar; trigger/vista sobre `respuesta_item_sus` | 3FN (no-primo derivado) | Ya en Ronda 2 §D (trigger). R3 confirma. |
| `dataset.metadatos_completos` | presencia de metadatos obligatorios en `dataset_metadata` | **NO persistir** o columna **GENERATED** | 3FN (no-primo derivado de no-primos) | **OMITIDO en Rondas 1-2** — NUEVO de R3 (R3.D). |
| `franja_horaria.cupos_disponibles` | `cupos_total − COUNT(cita activa)` | agregación; materializar SOLO por concurrencia (`SELECT…FOR UPDATE`) | desnormalización controlada justificada | Ya en Ronda 1/2. R3 ratifica como agregación. |
| `plan_integracion_tramite.nombre_tramite` | `tramite.nombre` (vía `id_tramite`) | NO persistir si `id_tramite NOT NULL`; hecho propio si NULL | 3FN | NUEVO de R3 (PIT-2). |
| `mecanismo_participacion.estado=cerrado` | `{fecha_cierre_programada, fecha_actual}` | derivado temporal por trigger; no dato ingresado | 3FN (estado derivado) | NUEVO de R3 (FD-MEC-1). |

**JSONB conservados (NO violación, documentado):** `carpeta_ciudadana.historial_*` (traza de consulta, RF-B2-021 prohíbe réplica permanente ⇒ JSONB aceptable, NO descomponer); `tramite.georreferenciacion` y `contenido.metadatos_json` (1FN/ISA — atributos funcionales de subtipo, extraer solo si el patrón de acceso lo exige; a investigar).

## D. Violaciones residuales detectadas (auditoría de lo que Rondas 1-2 dejaron pasar)

| Relación | Tipo | FD/MVD ofensora | Corrección propuesta | Severidad |
|---|---|---|---|---|
| `plan_integracion` | **1FN/4FN** (MVD independiente no descompuesta) | MVD `id_plan ↠ PIT \| PID \| POM` (deps R3 §C.1) | Descomponer a PIT/PID/POM (A.1) | 🔴 BLOQUEANTE |
| `interop_tsa_config` | **2FN** (PK incorrecta con histórico) | `{ambiente}⁺≠R` | PK `{ambiente, vigencia_desde}` + UNIQUE parcial activa (B.2) | 🔴 |
| `dataset` | **3FN** (`metadatos_completos` derivado materializado) | `{metadatos obligatorios}→metadatos_completos` | NO persistir / GENERATED (C) | media |
| `mecanismo_participacion` | integridad referencial (FK a dueño ausente) | FD-MEC-2 `id_mecanismo→id_oficina_responsable` | añadir FK→`dependencia` (B.3) | media |

**FALSO POSITIVO descartado:** `incidente_seguridad` con `sic_rnbd_reportado`+`sic_reporte_fecha` NO es transitiva 3FN — son dos hechos independientes, ambos determinados directamente por `id_incidente` (B.4). No descomponer.

## E. MVD / JD revisitadas (re-ejecución del veredicto, no aceptado como dado)

- **MVD nueva genuina:** `id_plan ↠ PIT | PID | POM` (la 9ª no trivial independiente). Las MVD `id_evaluacion_sus ↠ respuesta_item_sus` y `id_encuesta ↠ respuesta_encuesta` están **implicadas por la clave compuesta** (FD-RIS-1/FD-RES-1) ⇒ NO suman a la cuenta de MVD independientes (no exigen 4FN extra sobre lo que ya da BCNF). **Total MVD no triviales: 9.**
- **JD 5FN — re-verificación independiente:** las 3 hijas del plan se reconstruyen sin pérdida por reunión sobre `id_plan`; cada hija comparte SOLO `id_plan` con la cabecera, y `id_plan` es clave del padre ⇒ la JD `*[{cabecera},{PIT},{PID},{POM}]` **está implicada por la clave** ⇒ NO es JD no trivial. Ninguna RN del corpus describe restricción de reunión ternaria cíclica. **Veredicto 5FN=0 CONFIRMADO (4ª verificación).** (Date 2015 cap.14: 4FN ⇒ 5FN salvo reunión "no obvia"; aquí ninguna.)

## F. Conteo del delta Ronda 3 (con corrección anti-inflado)

| Categoría | Cantidad | Nota |
|---|---|---|
| **Relaciones NUEVAS netas de R3** | **5** | PIT, PID, POM (plan); `rate_limit_contador`; `interop_tsa_config` (re-clave, ya existía como C85 → corrección de PK, no neta) → netas firmes: **4** (PIT/PID/POM/rate_limit); `contenido_traduccion` condicionada (C135) |
| Relaciones que R3 solo RATIFICA (ya creadas en Ronda 2) | 4 | `respuesta_item_sus`, `respuesta_encuesta`, `pregunta_encuesta`, `dependencia` — **NO se recuentan** |
| **Violaciones residuales corregidas** | **4** | plan_integracion (1FN/4FN), interop_tsa_config (2FN), dataset (3FN), mecanismo_participacion (FK) |
| Falsos positivos descartados | 1 | incidente_seguridad (no transitiva) |
| Sobre-normalización evitada | 1 | PIT/PID/POM NO colapsables (MVD independiente) |
| Derivados nuevos no materializados | 3 | metadatos_completos, nombre_tramite, mecanismo.estado=cerrado |
| FK nuevas/corregidas | 2 | mecanismo→dependencia, dependencia.codigo→radicado.prefijo (cierra P-08) |
| **MVD no triviales** | **9** | +1 (plan); las del plan/SUS/encuesta implicadas por clave no suman |
| **JD genuinas 5FN** | **0** | confirmado 4ª vez |
| Relaciones totales del esquema | **~148** | ~144 (Ronda 2) + PIT/PID/POM + rate_limit_contador |

## G. Veredicto Ronda 3

- **Lossless + preservación demostradas** para la única descomposición nueva (`plan_integracion`→PIT/PID/POM, Fagin/Heath ∩=id_plan=clave) y para los ajustes (Heath caso simple en SUS/encuesta).
- **Honestidad mantenida:** `respuesta_item_sus`/`respuesta_encuesta`/`pregunta_encuesta`/`dependencia` NO se recuentan (ya existían en Ronda 2); R3 solo aporta cierres formales (irreducibilidad de LHS) y FD-DEP-3 (cierra P-08).
- **Sin sobre-normalización:** las 3 hijas del plan son MVD mutuamente independientes (no colapsables); falso positivo de `incidente_seguridad` descartado.
- **4 violaciones residuales corregidas** (1FN/4FN plan, 2FN tsa_config, 3FN dataset, FK mecanismo).
- **5FN=0 reconfirmado**; **9 MVD no triviales**; esquema final **~148 relaciones, todas BCNF (18+ en 4FN)**.
- **Pendientes que NO bloquean FN:** P-13 (clave fija/configurable de respuesta_encuesta), organigrama oficial (poblar `dependencia.codigo`), columnas exactas de `rate_limit_contador` ([INFERIDO parcial]), activación de C135 (`contenido_traduccion`), ítems textuales SUS.
