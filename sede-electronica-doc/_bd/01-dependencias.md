# Análisis de Dependencias — BD Sede Electrónica (Esquema Único Integrado)

> Fase 2 del pipeline jose-bd. Insumo: `00-inventario.md`. Marco teórico exclusivo: modelo relacional (Codd, Date, Maier, Bernstein, Silberschatz). Cubre criterio **C5**.

## 0. Método y alcance

- **Marco teórico exclusivo:** modelo relacional (Codd 1970/1990; Date 2015; Maier 1983; Bernstein 1976; Silberschatz 2019). Nada de POO/SOLID.
- **Insumo:** `00-inventario.md` íntegro: 135 entidades canónicas, 612 campos, 64 RN, 13 jerarquías ISA/polimórficas, 16 conflictos, 26 inferencias, 23 vacíos.
- **Principio de claves (mandato del encargo):** se justifican **claves naturales lógicas**. Los surrogates (`id`, `BIGINT IDENTITY`, `UUID`) son decisión FÍSICA posterior (C-13, I-01) y NO se usan para justificar la clave candidata. Donde el inventario solo declara surrogate, la clave natural se deriva de los atributos `UNIQUE` reales; si no existe ninguna, se marca **[CLAVE NATURAL AUSENTE — surrogate obligatorio]** y se deriva a investigación.
- **Cobertura de FD:** se formalizan en profundidad las 49 NÚCLEO + asociativas + entidades con clave compuesta/no trivial. Las LOCAL puramente "satélite" (1 FK propietaria + atributos descriptivos) se trazan por **familia de patrón FD** (P-Sat) para no inventar atributos no enumerados.
- **Notación:** `→` FD, `↠` MVD, `⋈`/JD reunión, `X⁺` cierre. Atributos citados textualmente del inventario (con línea de origen).

---

## 1. Dependencias funcionales (FD) por entidad — X→Y, trazadas a campo/RN/fuente

> Convención de IDs: `FD-Cnn-k`. Cada FD cita el/los atributo(s) del inventario y la RN/fuente que la sustenta. Lado derecho mostrado agrupado (se descompone a singleton en §5).

### 1.1 `sede_electronica` (C01) — L231-247

- **FD-C01-1**: `url_original → palabra_clave, url_enmascarada_gov, nombre_institucion, categoria, sector, estado_integracion, paso_proceso_actual, fecha_activacion, soporte_ipv4, soporte_ipv6` — RN-B3-004 "una sede electrónica por entidad" hace de `url_original` (UNIQUE, L235) identificador natural del portal.
- **FD-C01-2**: `palabra_clave → url_original, url_enmascarada_gov` — `palabra_clave` UNIQUE asignada MinTIC (RF-B2-001, L236); determina el enmascaramiento GOV.CO.
- **FD-C01-3**: `url_enmascarada_gov → palabra_clave` — derivada (`gov.co/[palabra_clave]`, L237); UNIQUE.
- Claves candidatas naturales: `{url_original}`, `{palabra_clave}`, `{url_enmascarada_gov}` (parcial, nullable → descartada como PK por integridad de entidad).

### 1.2 `tramite` (C16, catálogo SUIT) — L249-280

- **FD-C16-1**: `codigo_suit → nombre, tipo_servicio, modalidad, descripcion, requisitos, pasos_procedimiento, costo, es_gratuito, tiempo_resolucion_dias, resultado_esperado, grupo_objetivo, nivel_transformacion, url_digital, estado_estandarizado, nivel_autenticacion_requerido, aplica_sap, termino_sap_dias, tipo_silencio_administrativo, id_bloque_digitalizacion, id_fase_digitalizacion_actual, solicitudes_por_anio, fecha_ultima_actualizacion_suit, requiere_concepto_dafp, id_dependencia, version_ficha` — `codigo_suit` UNIQUE `T{código}` (L253) es la clave natural SUIT (RN-B1-006).
- **FD-C16-2** (derivada de negocio): `costo → es_gratuito` — RN-03-D03/B2-001: `costo=0 → es_gratuito=TRUE` (L261). Es una FD real (`es_gratuito` funcionalmente determinado por `costo`); candidata a eliminación como atributo derivado en BCNF.
- **FD-C16-3** (parcial/condicional): `aplica_sap → termino_sap_dias, tipo_silencio_administrativo` — RN-03-D08 (L270-272). `termino_sap_dias` NOT NULL solo si `aplica_sap` (dependencia condicional, no FD pura total).
- **FD-C16-4**: `url_digital → codigo_suit` — patrón `gov.co/servicios-y-tramites/T{código}` (L266) contiene el código; FD inversa derivada.
- Clave candidata natural: `{codigo_suit}`.

### 1.3 `solicitud` (C20, instancia/radicado) — L282-310

- **FD-C20-1**: `numero_radicado → id_tramite, id_ciudadano, id_sesion, fecha_hora_radicacion, estado, etapa_actual, tiempo_estimado_resolucion, fecha_vencimiento, datos_formulario, autorizo_datos_personales, acepto_terminos, canal_ingreso, requiere_pago, fecha_desistimiento, motivo_desistimiento, fecha_resolucion, efecto_sap, fecha_sap_aplicado, es_borrador, paso_actual_borrador, fecha_expiracion_borrador, inicio_gestion` — `numero_radicado` UNIQUE (L286, RF-B2-030) identifica unívocamente la ejecución.
- **FD-C20-2**: `clave_idempotencia → numero_radicado` — RN-03-D01 (L298): token único por intento garantiza idempotencia → un solo radicado por intento.
- **FD-C20-3** (derivada): `id_tramite → requiere_pago` vía `tramite.costo>0` — RF-B1-029 (L300). FD transitiva por catálogo: `numero_radicado → id_tramite → (tramite.costo) → requiere_pago`. **`requiere_pago` es derivado** → candidato a normalización (potencial violación 3FN si se materializa).
- **FD-C20-4** (condicional): `efecto_sap → fecha_sap_aplicado`; `estado=DESISTIDO → fecha_desistimiento` (L301,305) — RN-03-D08/D07.
- Clave candidata natural: `{numero_radicado}`. (`clave_idempotencia` es UNIQUE pero su rol es anti-duplicación de intento, no identidad de negocio → clave candidata alterna técnica.)

### 1.4 `pqrsd` (C32) — L312-337

- **FD-C32-1**: `id_radicado → id_tipo_pqrsd, es_anonima, es_identidad_reservada, id_ciudadano, nombre_razon_social, id_tipo_documento, numero_documento, correo, telefono, direccion_notificacion, canal_respuesta, id_dependencia, objeto, acepta_condiciones, acepta_privacidad, estado, fecha_hora_recepcion, fecha_estimada_respuesta, cumplimiento_plazo, id_expediente, ip_origen` — `id_radicado` FK UNIQUE (L331, RN-04-D07) es la clave natural (vía `radicado.numero_radicado`).
- **FD-C32-2** (condicional ISA): `es_anonima=TRUE → nombre_razon_social=NULL, id_tipo_documento=NULL, numero_documento=NULL, direccion_notificacion=NULL` (L320-325). Dependencia de existencia (subtipo), no FD pura.
- **FD-C32-3** (derivada): `id_tipo_pqrsd → (plazo) → fecha_estimada_respuesta` vía `tipo_pqrsd.plazo_dias_habiles` + `calendario_habil` — RN-04-D01 (L735). `cumplimiento_plazo` derivado de `estado` + fechas.
- **RN-B1-009**: `id_tipo_pqrsd ∈ {acceso_información} → es_anonima=FALSE` (L732). Restricción CHECK, no FD.
- Clave candidata natural: `{id_radicado}` (≡ `numero_radicado`).

### 1.5 `radicado` (C39) — L341-357 — **CLAVE COMPUESTA DIGNA DE ATENCIÓN**

- **FD-C39-1**: `numero_radicado → consecutivo_anual, anio, fecha_hora_radicacion, emisor_nombre, emisor_correo, id_destinatario_interno, destinatario_externo, tipo_documento, canal_recepcion, acuse_enviado, fecha_acuse, id_expediente` — `numero_radicado` UNIQUE (L345, RNF-04-D01).
- **FD-C39-2** (clave natural compuesta real): `{prefijo_dependencia(id_dependencia), anio, consecutivo_anual} → numero_radicado` — el formato `SM-{dep}-{año}-{seq6}` (L345-346) descompone el número. `consecutivo_anual` es UNIQUE **dentro del año** (RNF-04-D02): la unicidad es **(anio, consecutivo_anual)**, NO `consecutivo_anual` solo. Esto es una clave candidata compuesta no trivial.
- **FD-C39-3**: `numero_radicado → anio, consecutivo_anual` (proyección/parseo del propio número).
- Claves candidatas naturales: `{numero_radicado}` y `{anio, consecutivo_anual, prefijo_dependencia}`. (El prefijo es necesario porque el consecutivo es por dependencia+año; ver §3 cierre y §7 conflicto formalizado.)

### 1.6 `ciudadano` (C62) — L359-387 — **CLAVE COMPUESTA**

- **FD-C62-1**: `{id_tipo_documento, numero_documento} → nombre_completo, correo, telefono, direccion, nivel_confianza, auth_source, scd_sub, identificador_scd, biometric_enrolled, ani_validated, ani_validated_at, es_persona_juridica, razon_social, representante_legal_id, fecha_nacimiento, is_minor, estado_cuenta, canal_notificacion_preferido, direccion_procesal_electronica, failed_login_count, locked_until, id_version_politica` — UNIQUE(tipo,numero) (L364, RF-B3-074), validado vs ANI. **Clave natural compuesta.**
- **FD-C62-2**: `correo → {id_tipo_documento, numero_documento}` — `correo` UNIQUE para registrados (L366, login). FD parcial (nullable si anónimo → no PK).
- **FD-C62-3**: `scd_sub → {id_tipo_documento, numero_documento}` — UNIQUE parcial si `auth_source=scd_oidc` (L371). `identificador_scd` (L372) idem.
- **FD-C62-4** (derivada): `fecha_nacimiento → is_minor` vía edad <18 — RN-B2-007/12-D03 (L380, L756). `is_minor` derivado.
- **FD-C62-5** (ISA H2): `es_persona_juridica=TRUE → razon_social NOT NULL` (L376-377). Dependencia de subtipo.
- Claves candidatas: `{id_tipo_documento, numero_documento}` (PK natural), `{correo}` (alterna parcial), `{scd_sub}`, `{identificador_scd}`.

### 1.7 `usuario_interno` (C61) — L389-411

- **FD-C61-1**: `{id_tipo_documento, numero_documento} → nombre_completo, correo, contrasena_hash, contrasena_temporal, mfa_habilitado, estado, is_active, failed_login_count, locked_until, deactivated_at, acepto_tyc, fecha_acepto_tyc, id_firma_registro, fecha_nacimiento, id_sigep` — UNIQUE per tipo (L394, RF-B3-074).
- **FD-C61-2**: `correo → {id_tipo_documento, numero_documento}` — `correo` UNIQUE (L396).
- **FD-C61-3**: `id_sigep → {id_tipo_documento, numero_documento}` — cruce con SIGEP (L409, I-23); UNIQUE parcial (clave de integración C-09).
- **RN-09-D04**: `mfa_habilitado` determinado por `rol.requiere_mfa` (L399,753) → FD inter-entidad (vía usuario_rol): restricción.
- Claves candidatas: `{id_tipo_documento, numero_documento}`, `{correo}`, `{id_sigep}` (parcial).

### 1.8 `log_auditoria` (C68, append-only) — L413-435

- **FD-C68-1**: `record_hash → event_type, actor_type, id_usuario_interno, id_ciudadano, id_sesion, ip_address, accion_detalle, componente, entidad_tipo, entidad_id, resultado, detalle, previous_hash, version_politica, occurred_at, retencion_hasta` — `record_hash` SHA-256 del registro (L430, RN-09-D05) es identificador natural inmutable.
- **FD-C68-2** (cadena): `record_hash → previous_hash` y `previous_hash` referencia el `record_hash` del registro anterior (encadenamiento, L429-430). Estructura de lista enlazada por hash; no es FK clásica pero es dependencia funcional verificable.
- **FD-C68-3**: `{actor_type, id_usuario_interno|id_ciudadano}` — arco exclusivo (H1/H13): exactamente una de las dos FK no nula según `actor_type` (L418-420). Restricción, no FD pura.
- Clave candidata natural: `{record_hash}` (inmutable, único por contenido).

### 1.9 `documento_electronico` (C113, supertipo + polimórfico) — L437-467

- **FD-C113-1**: `hash_integridad → nombre_original, tipo_documental, mime_type_declarado, mime_type_real, tamano_bytes, ruta_almacenamiento, estado_antivirus, es_valido, ...` — `hash_integridad` SHA-256 (L452, RN-B1-015) identifica el contenido (deduplicación documental). **[ATENCIÓN: hash colisión de contenido idéntico en contextos distintos → no es PK por sí solo; clave natural = `{hash_integridad, contexto, id_propietario}`].**
- **FD-C113-2** (arco exclusivo H6): exactamente una de `{id_solicitud, id_pqrsd, id_expediente}` no nula según `contexto` (L443-446, C-06). CHECK.
- **FD-C113-3** (condicional): `es_carga_manual_excepcion=TRUE → id_falla_interop NOT NULL` (L457-458, RN-03-D06); `tipo_origen=SUBSANACION → id_requerimiento NOT NULL` (L459).
- **FD-C113-4** (derivada): `(mime_type_declarado ≠ mime_type_real) OR (estado_antivirus=infectado) → es_valido=FALSE` — RN-03-D04 (L455,726). `es_valido` derivado.
- Clave candidata natural: `{hash_integridad, contexto}` parcial; **clave natural completa ausente → surrogate `id` necesario** (registrado para C3).

### 1.10 `notificacion` (C109, polimórfica H11) — L469-489

- **FD-C109-1**: `id (surrogate) → entidad_tipo, entidad_id, tipo, ...` — **[CLAVE NATURAL AUSENTE]**: no hay atributo UNIQUE natural; el par polimórfico `{entidad_tipo, entidad_id, tipo, canal, fecha_creacion}` no garantiza unicidad (reintentos). Surrogate obligatorio.
- **FD-C109-2** (polimorfismo): `{entidad_tipo, entidad_id}` identifica el objeto notificado (L473-474); arco lógico hacia PQRSD/TRAMITE/CITA/CONTENIDO/ACTO. Antipatrón aceptado documentado (H11).

### 1.11 `expediente_electronico` (C112) — L493-513

- **FD-C112-1**: `numero_expediente → titulo, id_trd_serie, estado_ciclo_vital, folio_actual, numero_folio_inicio, numero_folio_fin, indice_firmado, id_indice_firma, trd_codigo, metadatos_autenticidad, estado_integridad, sgdea_referencia, id_responsable, fecha_apertura, fecha_cierre, id_solicitud` — `numero_expediente` UNIQUE formato AGN/Orfeo (L497, RF-B1-077).
- **FD-C112-2**: `id_solicitud → numero_expediente` — UNIQUE 1:1 (L498, RF-B1-092): cada solicitud tiene a lo sumo un expediente.
- **FD-C112-3**: `sgdea_referencia → numero_expediente` — id externo Orfeo (L510), UNIQUE de integración.
- Claves candidatas: `{numero_expediente}`, `{id_solicitud}` (parcial 1:1), `{sgdea_referencia}`.

### 1.12 Catálogos NÚCLEO con clave natural `code/codigo` (FD simples X→resto)

| Entidad | FD clave | Atributo natural (UNIQUE) | Fuente |
|---|---|---|---|
| `tipo_documento_identidad` (C34) | `codigo → descripcion, patron_validacion` | `codigo` (CC/NUIP/CE/NIT/Pasaporte/TI/PEP) | L541, C-16 |
| `categoria_cookie` (C09) | `nombre → descripcion, es_esencial` | `nombre` | L525 |
| `grupo_interes` (C45) | `code → label, descripcion` | `code` | L547 |
| `categoria_dato_sensible` (C73) | `code → label, requires_explicit_consent, legal_basis` | `code` | L565 |
| `permiso` (C76) | `codigo → modulo, descripcion` | `codigo` (`'contenido:crear'`) | L571 |
| `rol` (C75) | `nombre_rol → descripcion, nivel, ambito, requiere_mfa, es_sistema, puede_crear, puede_aprobar, puede_administrar_usuarios, es_rol_cms_interno` | `nombre_rol` | L569 |
| `formato_abierto` (C101) | `code/nombre → ...` | nombre | L669 |
| `licencia_datos` (C102) | `code → permite_reutilizacion, ...` | code | L670 |
| `interop_external_system` (C90) | `system_key → system_name, responsible_entity, ...` | `system_key` | L577 |
| `impuesto` (C117) | `nombre → sujeto_activo, sujeto_pasivo, hecho_generador, ...` | `nombre` UNIQUE | L593 |
| `certificado_digital` (C95) | `serial_number → cert_type, ca_provider, subject_dn, issued_at, expires_at, ocsp_status, ...` | `serial_number` UNIQUE | L579 |
| `servidor_publico` (C116) | `codigo_sigep → nombre_completo, cargo, correo_institucional, ...` ; `correo_institucional → codigo_sigep` | `codigo_sigep`, `correo_institucional` (ambos UNIQUE) | L591 |
| `politica_documento` (C08) | `{tipo, version_number} → titulo, url_descarga, fecha_vigencia_desde, ...` | clave compuesta | L523 |
| `solicitud_arco` (C74) | `radicado → arco_type, status, deadline_at, ...` ; **derivada** `arco_type → plazo_atencion_dias` (C-18) | `radicado` UNIQUE | L567, C-18 |
| `carpeta_ciudadana` (C88) | `id_mensaje → id_ciudadano, asunto, texto_mensaje, ...` | `id_mensaje` UNIQUE | L575 |
| `interop_xroad_transaction` (C84) | **[CLAVE NATURAL AUSENTE]** alto volumen, BIGSERIAL (I-17); candidata `{id_service, id_client_subsystem, transaction_timestamp, request_hash}` | request_hash + timestamp | L573 |
| `dataset` (C96) | `id → nombre, ...`; sin UNIQUE natural declarado salvo `nombre`+`entidad_publicadora` [POR CONFIRMAR] | — | L581 |

### 1.13 `firma_electronica` (C111, polimórfica H12) — L589

- **FD-C111-1**: `hash_documento + timestamp_firma + id_firmante → resultado_ocsp, algoritmo, firma_valor, certificado_serial, ...`
- **FD-C111-2** (arco exclusivo H12/I-13): exactamente una de `{id_documento, id_expediente_indice, id_usuario_registro}` no nula (L589, L856). CHECK.
- **[CLAVE NATURAL AUSENTE]** → surrogate `id` (UUID).

### 1.14 Familia P-Sat (entidades satélite: FK propietaria → atributos)

Patrón general: `id_propietario → {atributos descriptivos propios}`, con `id_propietario` UNIQUE cuando la relación es 1:1, o NO único (1:N) cuando es histórico/detalle. Aplica a:

- **1:1 (FK UNIQUE = clave natural):** `resultado_tramite` (C28: `id_solicitud` UNIQUE → tipo_acto, ...); `nivel_auth_tramite` (C19: `id_tramite` → nivel_requerido); `recurso_multimedia_accesible` (C57: `id_contenido` 1:1 → has_subtitles, ...); `dato_personal_sensible` (C63: `{id_ciudadano, id_categoria}` → valor_cifrado); `preferencia_accesibilidad` (C56: `id_usuario` → ...); `mfa_enrollment` (C65: `id_usuario` → ...); `resultado_participacion` (C44: `id_mecanismo` UNIQUE → consolidado, RN-05-D02); `informe_pqrsd_detalle` (C127: `{id_informe, tipo_pqrsd, estado}` → conteo).
- **1:N histórico (FK NO única; clave natural compuesta con fecha/secuencia):** `asignacion_dependencia` (C35: `{id_pqrsd, fecha_asignacion}` → id_dependencia, motivo); `prorroga` (C37: `{id_pqrsd, fecha_registro}` → nueva_fecha_limite); `dataset_version` (C97: `{id_dataset, numero_version}` → ...); `version_contenido` (C106: `{id_contenido, version}` → ...); `version_documento_transparencia` (C130: `{id_publicacion, version}` → ...); `intento_notificacion` (C110: `{id_notificacion, numero_intento}` → resultado); `intento_login` (C71: `{id_usuario|ip, occurred_at}` → resultado); `otrosi` (C122: `{id_contrato, numero_otrosi}` → ...); `recordatorio_cita` (C54: `{id_cita}` → fecha_envio); `accion_incidente` (C70: `{id_incidente, secuencia}` → ...); `calendario_tributario` (C118: `{id_impuesto, vigencia_fiscal}` → fecha_vencimiento).

### 1.15 Asociativas puras (M:N) — clave = par de FK

- **FD-C77** (`rol_permiso`): `{id_rol, id_permiso} → ∅` (relación pura, sin atributo no-clave) — L651. Clave = todo.
- **FD-C78** (`usuario_rol`): `{id_usuario_interno, id_rol} → fecha_asignacion, asignado_por` — L652. Atributos de auditoría dependen del par completo.
- **FD-C79** (`ciudadano_rol`): `{id_ciudadano, id_rol} → ∅` — L653.
- **FD-C99** (`dataset_formato`): `{id_dataset, id_formato} → ∅` — L668 (C-19 N:N).
- **FD-C83** (`interop_xroad_service_permission`): `{id_service, id_client_subsystem} → ...` — L657.
- **FD-C12** (`consentimiento_categoria`): `{id_consentimiento, id_categoria} → decision` — L703 (RN-01-D01b).

---

## 2. Dependencias multivaluadas (MVD) y de reunión (JD) — candidatas 4FN/5FN

### 2.1 MVD no triviales

1. **`dataset ↠ id_formato | dataset_metadata(clave,valor)`** (L581-582, L667-668). Un dataset publica simultáneamente en múltiples formatos (CSV+JSON+XML, C-19) **independientemente** de sus pares metadato clave-valor extensibles. MVD: `id_dataset ↠ id_formato | {clave_metadato, valor_metadato}`. Justifica la descomposición en `dataset_formato` (C99) y `dataset_metadata` (C98) → **4FN**. Sin ella, una tabla universal `dataset(formato, metadato)` generaría producto cartesiano espurio (anomalía clásica MVD).
2. **`pqrsd ↠ documento_electronico | asignacion_dependencia`** — Una PQRSD tiene un conjunto de adjuntos independiente de su conjunto histórico de asignaciones a dependencias (L443-445, L628). MVD `id_pqrsd ↠ {documentos} | {asignaciones}`. Ya descompuesta (entidades separadas) → 4FN satisfecha.
3. **`expediente_electronico ↠ documento_electronico | firma_electronica`** — Los documentos foliados de un expediente y las firmas aplicadas son multivaluados independientes (L443, L589). Descompuesto.
4. **`canal_atencion ↠ horario_canal(dia, apertura, cierre)`** — I-10 (L853): el horario atómico viola 1FN; cada canal tiene N franjas día/apertura/cierre. MVD `id_canal ↠ {dia, hora_apertura, hora_cierre}` → tabla `horario_canal` (C49). 4FN.
5. **`sede_fisica ↠ canal_atencion | recurso_inclusivo`** — Una sede tiene canales y recursos inclusivos como conjuntos independientes (L521, L551, L641). Descompuesto.
6. **`ciudadano ↠ dato_personal_sensible | consentimiento_datos`** — Datos sensibles (por categoría) y consentimientos son multivaluados independientes del ciudadano (L557, L527). 4FN.

### 2.2 Dependencias de reunión (JD) — candidatas 5FN

- **JD-1 (RBAC):** `usuario_interno ⋈ rol ⋈ permiso` vía `usuario_rol` y `rol_permiso`. La relación ternaria usuario–rol–permiso se reconstruye SIN pérdida por la reunión de las dos binarias `usuario_rol(usuario,rol)` y `rol_permiso(rol,permiso)` porque el permiso efectivo depende del rol, no directamente del usuario: `*[{usuario,rol},{rol,permiso}]`. Esta JD **SÍ está implicada por la clave** de `rol_permiso` (FD `rol → permisos`) — por tanto **NO es una JD no trivial que exija 5FN adicional**; es la descomposición lossless estándar de RBAC (Maier, cap. 5). 4FN/BCNF suficiente.
- **JD-2 (trámite–nivel auth–dependencia):** no se detecta JD cíclica que exija 5FN; las relaciones son funcionales (FD), no de reunión.
- **Conclusión:** **No se identifican JD NO implicadas por claves** en el corpus. Toda descomposición multivaluada detectada se explica por MVD (4FN) o por FD (3FN/BCNF). No hay candidatas genuinas a 5FN/PJ-NF. (Date 2015, cap. 14: la mayoría de esquemas en 4FN ya están en 5FN salvo restricciones de reunión "no obvias"; aquí ninguna RN del inventario describe una restricción ternaria cíclica.)

**Total MVD no triviales: 6. Total JD no implicadas por claves: 0.**

---

## 3. Cierres de atributos X⁺ y justificación de claves candidatas

> Se muestra el cómputo iterativo (Algoritmo de cierre, Bernstein/Maier). `F` = FD locales de la entidad.

### 3.1 `sede_electronica`
`F`: url_original→{resto}; palabra_clave→{url_original,...}.
- `{url_original}⁺` = url_original ∪ {palabra_clave, url_enmascarada_gov, nombre_institucion, categoria, sector, estado_integracion, paso_proceso_actual, fecha_activacion, soporte_ipv4, soporte_ipv6} = **R**. ⇒ clave.
- `{palabra_clave}⁺`: + url_original (FD-C01-2) → luego cierre de url_original → **R**. ⇒ clave.
- **Claves candidatas:** `{url_original}`, `{palabra_clave}`. PK = `url_original`.

### 3.2 `tramite`
- `{codigo_suit}⁺` = codigo_suit → {todos los demás campos por FD-C16-1} = **R**. ⇒ clave única.
- `{costo}⁺` = {costo, es_gratuito} ≠ R ⇒ NO clave (solo determina derivado).
- `{nombre}⁺` = {nombre} (nombre no es UNIQUE) ⇒ NO clave.
- **Clave candidata única:** `{codigo_suit}`.

### 3.3 `solicitud`
- `{numero_radicado}⁺` = + todos (FD-C20-1) = **R**. ⇒ clave.
- `{clave_idempotencia}⁺` = + numero_radicado (FD-C20-2) → + R = **R**. ⇒ clave.
- **Claves candidatas:** `{numero_radicado}`, `{clave_idempotencia}`. PK = `numero_radicado` (clave de negocio legal; idempotencia es técnica).

### 3.4 `radicado` (compuesta)
- `{numero_radicado}⁺` = **R** (FD-C39-1). ⇒ clave.
- `{anio, consecutivo_anual}⁺`: ¿determina prefijo_dependencia? El consecutivo es por dependencia+año → `{anio, consecutivo_anual}` **NO** determina la dependencia salvo que el consecutivo sea global. El inventario dice "UNIQUE dentro del año" (L346) → ambiguo. Si el secuencial es **por dependencia**: `{prefijo_dependencia, anio, consecutivo_anual}⁺` = + numero_radicado (FD-C39-2) → **R**. ⇒ clave compuesta.
- `{anio, consecutivo_anual}⁺` (si secuencial **global anual**) → numero_radicado → **R**. ⇒ clave.
- **Resolución (ver §7):** se adopta la lectura literal RNF-04-D02 "UNIQUE dentro del año" ⇒ clave candidata `{anio, consecutivo_anual}`; el prefijo de dependencia es **formato**, no parte de la unicidad del consecutivo. **PK natural = `{numero_radicado}`** (atómico, contiene todo).
- **Claves candidatas:** `{numero_radicado}`, `{anio, consecutivo_anual}`.

### 3.5 `ciudadano` (compuesta)
- `{id_tipo_documento, numero_documento}⁺` = + todos (FD-C62-1) = **R**. ⇒ clave.
- `{correo}⁺` = + {id_tipo_documento, numero_documento} (FD-C62-2) → **R**. ⇒ clave (parcial: nullable para anónimo → no PK).
- `{scd_sub}⁺` = + clave natural → **R**. ⇒ clave (parcial).
- `{numero_documento}⁺` (sin tipo) = {numero_documento} ⇒ **NO** clave (un mismo número puede repetirse entre tipos: CC vs CE).
- **Claves candidatas:** `{id_tipo_documento, numero_documento}`, `{correo}`, `{scd_sub}`, `{identificador_scd}`. **PK = `{id_tipo_documento, numero_documento}`** (clave natural total y obligatoria).

### 3.6 `usuario_interno`
- `{id_tipo_documento, numero_documento}⁺` = **R**. ⇒ clave.
- `{correo}⁺` = + clave natural → **R**. ⇒ clave.
- `{id_sigep}⁺`: parcial (nullable) → + clave natural → **R** cuando presente ⇒ clave parcial.
- **PK = `{id_tipo_documento, numero_documento}`**; alterna `{correo}`.

### 3.7 `expediente_electronico`
- `{numero_expediente}⁺` = **R**. ⇒ clave.
- `{id_solicitud}⁺` = + numero_expediente (FD-C112-2) → **R** ⇒ clave (parcial 1:1, nullable hasta apertura).
- `{sgdea_referencia}⁺` = + numero_expediente → **R** ⇒ clave.
- **PK = `{numero_expediente}`**; alternas `{id_solicitud}`, `{sgdea_referencia}`.

### 3.8 `log_auditoria`
- `{record_hash}⁺` = **R** (FD-C68-1). ⇒ clave única (inmutable por contenido). PK natural = `{record_hash}`.

### 3.9 `pqrsd`
- `{id_radicado}⁺` = **R** (FD-C32-1). ⇒ clave única. PK = `{id_radicado}`.

### 3.10 Catálogos (cierres triviales X⁺=R con X el code/UNIQUE)
- `{codigo}⁺=R` para `tipo_documento_identidad`; `{nombre}⁺=R` (categoria_cookie); `{code}⁺=R` (grupo_interes, categoria_dato_sensible); `{codigo}⁺=R` (permiso); `{nombre_rol}⁺=R` (rol); `{system_key}⁺=R` (interop_external_system); `{nombre}⁺=R` (impuesto); `{serial_number}⁺=R` (certificado_digital); `{codigo_sigep}⁺=R` y `{correo_institucional}⁺=R` (servidor_publico); `{tipo,version_number}⁺=R` (politica_documento); `{radicado}⁺=R` (solicitud_arco); `{id_mensaje}⁺=R` (carpeta_ciudadana).

### 3.11 Asociativas
- `{id_rol,id_permiso}⁺=R` (todo es clave); `{id_usuario_interno,id_rol}⁺ = +fecha_asignacion,asignado_por = R`; `{id_dataset,id_formato}⁺=R`; `{id_consentimiento,id_categoria}⁺=+decision=R`.

### 3.12 Entidades con **[CLAVE NATURAL AUSENTE]** (cierre no alcanza R sin surrogate)
- `documento_electronico`: `{hash_integridad}⁺` = atributos de contenido, pero NO determina `contexto`/propietario unívocamente (mismo archivo en dos contextos) ⇒ ≠R. `{hash_integridad, contexto, id_solicitud|id_pqrsd|id_expediente}⁺=R` ⇒ clave compuesta natural posible, pero el arco exclusivo la vuelve impráctica → **surrogate `id` (decisión física, registrada para C3)**.
- `notificacion`, `firma_electronica`, `interop_xroad_transaction`: ninguna combinación de atributos del inventario garantiza unicidad (reintentos, alto volumen) ⇒ surrogate obligatorio. **Derivado a investigación** (no hay clave natural documentada).

**Total claves candidatas con cierre demostrado: 38** (incluye las 4 entidades sin clave natural, demostradas como tales por cierre que NO alcanza R).

---

## 4. Claves primarias elegidas y atributos primos/no primos

> Criterio de elección: (1) clave natural total y obligatoria (NOT NULL siempre); (2) estabilidad/inmutabilidad legal; (3) mínima. Surrogates = decisión física (C-13), NO justifican la PK lógica.

| Entidad | PK natural elegida | Criterio | Atributos PRIMOS | No primos (resumen) |
|---|---|---|---|---|
| sede_electronica | `url_original` | UNIQUE, obligatorio, estable (RN-B3-004) | url_original, palabra_clave | resto 8 |
| tramite | `codigo_suit` | clave SUIT oficial, NOT NULL | codigo_suit | resto 24 |
| solicitud | `numero_radicado` | identidad legal del radicado | numero_radicado, clave_idempotencia | resto |
| radicado | `numero_radicado` | atómico, contiene formato | numero_radicado, anio, consecutivo_anual | resto |
| pqrsd | `id_radicado` (≡numero_radicado) | identidad legal | id_radicado | resto |
| ciudadano | `{id_tipo_documento, numero_documento}` | natural total ANI | id_tipo_documento, numero_documento, correo, scd_sub, identificador_scd | resto |
| usuario_interno | `{id_tipo_documento, numero_documento}` | natural total | id_tipo_documento, numero_documento, correo, id_sigep | resto |
| expediente_electronico | `numero_expediente` | formato AGN/Orfeo | numero_expediente, id_solicitud, sgdea_referencia | resto |
| log_auditoria | `record_hash` | inmutable por contenido | record_hash, previous_hash | resto |
| tipo_documento_identidad | `codigo` | catálogo | codigo | descripcion, patron_validacion |
| rol | `nombre_rol` | catálogo RBAC | nombre_rol | resto 8 |
| permiso | `codigo` | catálogo | codigo | modulo, descripcion |
| politica_documento | `{tipo, version_number}` | versionado | tipo, version_number | resto |
| servidor_publico | `codigo_sigep` | SIGEP | codigo_sigep, correo_institucional | resto |
| impuesto | `nombre` (+ `{nombre,vigencia_desde}` si versionado) | UNIQUE | nombre, vigencia_desde | resto |
| certificado_digital | `serial_number` | X.509 | serial_number | resto |
| dataset | surrogate (nombre+publicadora [POR CONFIRMAR]) | clave natural sin confirmar | — | todos |
| documento_electronico | **surrogate** (`id`) | clave natural ausente | (id) | todos |
| notificacion | **surrogate** | clave natural ausente | (id) | todos |
| firma_electronica | **surrogate** | clave natural ausente | (id) | todos |
| interop_xroad_transaction | **surrogate** | alto volumen, sin natural | (id) | todos |
| rol_permiso | `{id_rol, id_permiso}` | asociativa | ambos | ninguno |
| usuario_rol | `{id_usuario_interno, id_rol}` | asociativa | ambos | fecha_asignacion, asignado_por |
| dataset_formato | `{id_dataset, id_formato}` | asociativa | ambos | ninguno |
| consentimiento_categoria | `{id_consentimiento, id_categoria}` | asociativa | ambos | decision |
| dato_personal_sensible | `{id_ciudadano, id_categoria}` | composición sensible | ambos | valor_cifrado, fecha_registro |
| dataset_version | `{id_dataset, numero_version}` | versión histórica | ambos | resto |
| version_contenido | `{id_contenido, version}` | versión | ambos | resto |
| calendario_habil | `fecha` | natural temporal (L543) | fecha | es_habil, motivo_no_habil, anio, tipo_festivo |
| informe_pqrsd_detalle | `{id_informe, tipo_pqrsd, estado}` | desglose (I-08) | los 3 | conteo |

**Atributos primos del esquema:** todos los listados en columna PRIMOS (códigos naturales, números de radicado/expediente, hashes inmutables, pares de FK en asociativas, fechas-clave en históricos). **No primos:** la gran mayoría (atributos descriptivos, de estado, fechas de evento, flags derivados). **Derivados (candidatos a no almacenar / CHECK):** `tramite.es_gratuito` (←costo), `solicitud.requiere_pago` (←tramite.costo), `ciudadano.is_minor` (←fecha_nacimiento), `documento_electronico.es_valido` (←mime/antivirus), `pqrsd.cumplimiento_plazo` (←fechas).

---

## 5. Cobertura mínima (minimal cover) — procedimiento completo (a)(b)(c) con pruebas por cierre

> Algoritmo canónico (Bernstein 1976; Maier 1983 §5.5) sobre el conjunto F de FD **inter-atributo no triviales** (se excluyen las puramente identificadoras `clave→R` que se descomponen y las restricciones CHECK que no son FD).

### Conjunto de partida F (FD significativas, lado derecho ya agrupado)

```
f1:  url_original → palabra_clave
f2:  palabra_clave → url_original
f3:  palabra_clave → url_enmascarada_gov
f4:  url_enmascarada_gov → palabra_clave
f5:  codigo_suit → costo, es_gratuito, nivel_autenticacion_requerido, aplica_sap, termino_sap_dias
f6:  costo → es_gratuito
f7:  clave_idempotencia → numero_radicado
f8:  numero_radicado → id_tramite, estado, requiere_pago
f9:  id_tramite → costo
f10: costo → requiere_pago
f11: correo_ciudadano → id_tipo_documento, numero_documento
f12: {id_tipo_documento, numero_documento} → correo_ciudadano
f13: fecha_nacimiento → is_minor
f14: id_radicado → numero_radicado
f15: {anio, consecutivo_anual} → numero_radicado
f16: numero_radicado → anio, consecutivo_anual
f17: id_solicitud → numero_expediente
f18: numero_expediente → id_solicitud
f19: arco_type → plazo_atencion_dias
f20: serial_number → ocsp_status, expires_at
```

### (a) Lado derecho singleton (axioma de descomposición, Armstrong)

```
f3:  palabra_clave → url_enmascarada_gov
f5a: codigo_suit → costo
f5b: codigo_suit → es_gratuito
f5c: codigo_suit → nivel_autenticacion_requerido
f5d: codigo_suit → aplica_sap
f5e: codigo_suit → termino_sap_dias
f8a: numero_radicado → id_tramite
f8b: numero_radicado → estado
f8c: numero_radicado → requiere_pago
f16a: numero_radicado → anio
f16b: numero_radicado → consecutivo_anual
f20a: serial_number → ocsp_status
f20b: serial_number → expires_at
... (resto ya singleton: f1,f2,f4,f6,f7,f9,f10,f11,f13,f14,f15,f17,f18,f19; f12 se descompone f12a/f12b)
```

### (b) Eliminación de atributos extraños del lado izquierdo (prueba por cierre)

Solo hay LHS compuestos en `f11/f12` (ciudadano) y `f15`.
- **f12** `{id_tipo_documento, numero_documento} → correo`: ¿`id_tipo_documento` extraño? `{numero_documento}⁺` bajo F = {numero_documento} (no contiene correo, porque un número puede repetirse entre tipos). ¿`numero_documento` extraño? `{id_tipo_documento}⁺` = {id_tipo_documento}. **Ningún atributo es extraño** ⇒ LHS irreducible.
- **f15** `{anio, consecutivo_anual} → numero_radicado`: `{consecutivo_anual}⁺` = {consecutivo_anual} (no único entre años); `{anio}⁺` = {anio}. **Ninguno extraño** ⇒ irreducible.
- Resto de FD: LHS unitario ⇒ sin atributos extraños por definición.

### (c) Eliminación de FD redundantes (prueba por cierre sin la FD candidata)

- **f6** `costo → es_gratuito`: `{costo}⁺` con F\{f6} = {costo, requiere_pago} (vía f10), **no** contiene es_gratuito ⇒ **f6 NO es redundante** (mantener).
- **f5b** `codigo_suit → es_gratuito`: `{codigo_suit}⁺` con F\{f5b} ⊇ {costo} → por f6 → {es_gratuito}. **SÍ es redundante** ⇒ **ELIMINAR f5b** (cadena codigo_suit→costo→es_gratuito).
- **f8c** `numero_radicado → requiere_pago`: `{numero_radicado}⁺` con F\{f8c} = {numero_radicado, id_tramite, costo, requiere_pago, es_gratuito, anio, consecutivo_anual, estado,...} ⊇ {requiere_pago}. **SÍ es redundante** ⇒ **ELIMINAR f8c** (derivado transitivo — viola 3FN si se materializa sin justificación de rendimiento).
- **f1↔f2** y **f3/f4**: ciclo de equivalencia. `{url_enmascarada_gov}⁺` con F\{f4} no recupera palabra_clave ⇒ **f4 NO redundante**, mantener.
- **f16a/f16b** vs **f15**: inversas no redundantes (clave compuesta alterna). Mantener.
- Resto: probadas no redundantes.

### Cobertura mínima resultante (Fc)

```
url_original → palabra_clave
palabra_clave → url_original
palabra_clave → url_enmascarada_gov
url_enmascarada_gov → palabra_clave
codigo_suit → costo
codigo_suit → nivel_autenticacion_requerido
codigo_suit → aplica_sap
codigo_suit → termino_sap_dias
costo → es_gratuito
costo → requiere_pago
clave_idempotencia → numero_radicado
numero_radicado → id_tramite
numero_radicado → estado
numero_radicado → anio
numero_radicado → consecutivo_anual
id_tramite → costo
correo_ciudadano → id_tipo_documento
correo_ciudadano → numero_documento
{id_tipo_documento, numero_documento} → correo_ciudadano
fecha_nacimiento → is_minor
id_radicado → numero_radicado
{anio, consecutivo_anual} → numero_radicado
id_solicitud → numero_expediente
numero_expediente → id_solicitud
arco_type → plazo_atencion_dias
serial_number → ocsp_status
serial_number → expires_at
```

**Tamaño de la cobertura mínima del subconjunto significativo: 27 FD** (tras eliminar f5b y f8c por redundancia demostrada). Las FD `clave_natural → R` de cada entidad (identificadoras puras, una por entidad) se mantienen aparte como generadoras de esquema en la síntesis 3NF (C3); no entran en la reducción porque su LHS es la clave y su RHS es disjunto entre sí.

---

## 6. Traza RN → FD

| RN-id (inventario) | Traducción formal | Tipo |
|---|---|---|
| RN-B3-004 | `url_original → R(sede)` (una sede por entidad) | FD/UNIQUE |
| RF-B2-001 / RF-B1-070 | `palabra_clave → url_enmascarada_gov` | FD |
| RN-B1-006 / 03 §7 | `codigo_suit → R(tramite)` | FD/UNIQUE |
| RN-03-D03/B2-001 | `costo → es_gratuito`; `pago.monto = tramite.costo` | FD derivada + CHECK |
| RF-B1-029 | `costo → requiere_pago` (transitiva vía id_tramite) | FD derivada |
| RN-03-D01 | `clave_idempotencia → numero_radicado` (idempotencia) | FD/UNIQUE |
| RN-03-D08 | `aplica_sap → termino_sap_dias, tipo_silencio_administrativo` | FD condicional |
| RNF-04-D01 / RNF-04-D02 | `numero_radicado → R(radicado)`; `{anio,consecutivo_anual}→numero_radicado` | FD/UNIQUE compuesta |
| RN-04-D07 / HU-03-D13 | radicado atómico SEQUENCE ⇒ unicidad de `{anio,consecutivo_anual}` | UNIQUE/SEQ |
| RF-B3-074 | `{id_tipo_documento,numero_documento}→R`; `correo→clave_natural` (ciudadano/usuario) | FD/UNIQUE compuesta |
| RN-B2-007/12-D03 | `fecha_nacimiento → is_minor` | FD derivada |
| RF-B1-077 / RF-B1-092 | `numero_expediente→R`; `id_solicitud→numero_expediente` (1:1) | FD/UNIQUE |
| RN-09-D05/RNF-09-D01 | `record_hash→R(log)`; cadena `previous_hash`; append-only | FD + inmutabilidad |
| RN-09-D02 | `creado_por ≠ aprobado_por` (SoD) | CHECK (no FD) |
| RN-09-D04 | `rol.requiere_mfa → usuario_interno.mfa_habilitado` (inter-entidad vía usuario_rol) | restricción derivada |
| RN-03-D04 | `(mime_decl≠mime_real ∨ antivirus=infectado) → es_valido=FALSE` | FD derivada + CHECK |
| RN-03-D06 | `es_carga_manual_excepcion → id_falla_interop NOT NULL` | FD condicional |
| RN-04-D01 / C-18 | `id_tipo_pqrsd → plazo`; `arco_type → plazo_atencion_dias` | FD |
| RF-B1-009 | `(tipo) WHERE es_vigente` → un vigente por tipo | UNIQUE parcial |
| RN-05-D02 | `id_mecanismo → resultado_participacion` (1:1, UNIQUE) | FD/UNIQUE |
| RN-10-D03 / RN-10-D02 | `serial_number → expires_at`; transacción sin TSA no COMPLETED | FD + CHECK |
| C-19 / HU-B2-018 | MVD `id_dataset ↠ id_formato \| metadata` | MVD (4FN) |
| RN-12-D01 | máquina de estados `contenido.estado` (no FD; transición) | TRG |
| RN-TX-D01 | `fecha → calendario_habil` (fuente única de cómputo) | FD (fecha es PK natural) |

Las RN de tipo TRG/CK/CARD/APP (máquinas de estado, atomicidad de cupos RN-06-D01, encadenamiento de hash) **no son FD** y se trazan a restricciones de integridad en C3.

---

## 7. Notas de conflictos formalizados

- **C-05 (catálogo vs instancia):** confirmado. `tramite` y `solicitud` tienen FD **disjuntas** con LHS distintos (`codigo_suit→...` vs `numero_radicado→...`). Mezclarlas crea dependencia parcial de `numero_radicado` sobre atributos de la ficha → **violación de 2FN/3FN**. Resolución: dos entidades (C16/C20). FK: `solicitud.id_tramite → tramite.codigo_suit`.
- **C-13 (PK UUID vs BIGINT):** **no afecta la clave candidata lógica** (natural: codigo, numero_radicado, documento). El surrogate es decisión física. Las 4 entidades con clave natural ausente (`documento_electronico`, `notificacion`, `firma_electronica`, `interop_xroad_transaction`) **requieren surrogate por necesidad lógica** (cierre ≠ R), no por conveniencia.
- **C-16 (tipo_documento_identidad):** la unión de dominios (CC/NUIP/CE/NIT/Pasaporte/TI/PEP) **no altera la FD** `codigo→R`; solo amplía el dominio de `codigo`.
- **C-18 (plazo ARCO):** se formaliza como `arco_type → plazo_atencion_dias` (FD, no constante única). El plazo depende funcionalmente del tipo → correcto en 3FN.
- **Radicado / consecutivo (RNF-04-D02, ambigüedad secuencial global vs por dependencia):** el inventario dice "UNIQUE dentro del año" pero el formato incluye `{dep}`. **Resolución adoptada:** clave candidata `{anio, consecutivo_anual}`, con `prefijo_dependencia` como **formato**. **Si el consecutivo fuese por dependencia** (P-08), la clave correcta sería `{prefijo_dependencia, anio, consecutivo_anual}`. **[POR CONFIRMAR — P-08]**: afecta la cardinalidad de la SEQUENCE.
- **C-06 (documento_electronico):** el discriminador `contexto` y el arco exclusivo son restricción de existencia, no FD. La clave natural depende del contexto → refuerza la necesidad de surrogate.
- **C-11b (consentimiento por tipo):** `consent_type` discrimina subtipos; condiciona qué columnas aplican (dependencia de existencia).
- **C-20 (member_class X-Road):** `member_class` sin dominio fijo **[POR CONFIRMAR — P-07]**; la clave de `interop_xroad_member` es `member_code` (RN-B3-032: `member_code = sigla-código_SIGEP`), FD `member_code→R`.
- **`dataset` sin clave natural confirmada:** `{nombre, entidad_publicadora}` candidata plausible no declarada UNIQUE → **[POR CONFIRMAR]**, surrogate provisional.

---

## Resumen ejecutivo Fase 2

- **FD formalizadas:** 70+ (por entidad NÚCLEO + asociativas + familia P-Sat).
- **MVD no triviales:** 6 (4FN). **JD no implicadas por claves:** 0 (no hay 5FN genuina).
- **Claves candidatas con cierre demostrado:** 38 (incluye 4 demostradas SIN clave natural).
- **Cobertura mínima (subconjunto significativo):** 27 FD (eliminadas por redundancia probada f5b y f8c; LHS sin atributos extraños).
- **Entidades de atención:** `radicado` (clave compuesta + P-08); `ciudadano`/`usuario_interno` (PK natural compuesta `{tipo_doc, numero_doc}`); `documento_electronico`/`notificacion`/`firma_electronica`/`interop_xroad_transaction` (clave natural ausente → surrogate lógicamente necesario); claves de versionado (`politica_documento`, `dataset_version`, `version_contenido`, etc.).
- **Derivados que violarían 3FN si se materializan:** `tramite.es_gratuito`, `solicitud.requiere_pago`, `ciudadano.is_minor`, `documento_electronico.es_valido`, `pqrsd.cumplimiento_plazo`.

---

# DELTA — 2ª pasada profunda de dependencias

> Re-lectura íntegra de inventario + 1ª pasada + fuentes (rn-delta, casos-uso, módulos 03/04/08/09/12, dominio tributario). Entrega correcciones y enriquecimiento, NO reescritura. **Las correcciones marcadas 🔴 son BLOQUEANTES: la normalización (C3) debe integrarlas.**

## A. FD nuevas/corregidas

- **A.1 — `evaluacion_sus.puntaje_sus` derivado [NUEVA FD-C59-1]**: `{item_1..item_10} → puntaje_sus`, fórmula SUS `(Σ_impar(itemᵢ−1)+Σ_par(5−itemᵢ))×2.5` (usabilidad.md L49,L100). Cierre `{item_1..item_10}⁺` incluye `puntaje_sus` y este no aparece a la izquierda → no-primo derivado. **Añadir a lista de derivados 3FN.** Nota: los 10 ítems no están desglosados como columnas en el inventario (C59=16 campos) → vacío de campo a resolver en C3 (no JSONB).
- **A.2 — `pago.monto` derivado [NUEVA FD-C22-1, transitiva]**: `pago.id_solicitud → solicitud.id_tramite → tramite.costo → pago.monto` (inventario L535,L725). Materializarlo viola 3FN. La 1ª pasada solo capturó `requiere_pago`, omitió `monto`. **Añadir a derivados 3FN.**
- **A.3 — `solicitud_arco` plazo doble [CORRECCIÓN FD-C74-1]**: `arco_type → plazo_base_dias, dias_prorroga_max` (C-18: acceso/consulta 10+5; rectificación/cancelación/oposición 15+8). La FD original `arco_type → plazo_atencion_dias` colapsaba dos hechos. `deadline_at` es derivado (`received_at + plazo_base` sobre `calendario_habil`).
- **A.4 — catálogo `tipo_pqrsd` [NUEVA FD-C33-1]**: `id_tipo_pqrsd → plazo_dias_habiles, es_prorrogable, dias_prorroga_max, es_gratuita` (RN-04-D01/D08). Fuente determinante de `pqrsd.fecha_estimada_respuesta`/`cumplimiento_plazo`.
- **A.5 — `franja_horaria.cupos_disponibles` agregado [NUEVA FD-C51-1]**: `cupos_disponibles = cupos_total − COUNT(cita activa)` (RN-06-D01/D02). Dependencia de agregación; materialización es excepción de concurrencia (`SELECT … FOR UPDATE`), a justificar en C3.
- **A.6 — `log_auditoria.record_hash` también derivado [NUEVA FD-C68-4]**: `{todos los campos no-hash, previous_hash} → record_hash` (SHA-256, RN-09-D05). `record_hash` es a la vez clave Y derivado → se computa por trigger al insertar (refuerza inmutabilidad append-only).
- **A.7 — `numero_expediente ↔ id_solicitud` NO es 1:1 universal [CORRECCIÓN]**: existen expedientes de PQRSD (`pqrsd.id_expediente`) y documentales puros sin `id_solicitud`. `id_solicitud → numero_expediente` es FD **parcial** (clave alterna parcial nullable, no plena). FD simétrica parcial `id_expediente → id_pqrsd` no registrada.

## B. MVD/JD re-evaluadas

- **B.1 [NUEVA MVD]**: `id_usuario_interno ↠ {mfa_enrollment} | {usuario_rol}` — ya descompuesta, 4FN. (RN-09-D04 acopla `rol.requiere_mfa` con existencia de MFA: restricción inter-conjunto, no MVD acoplada.)
- **B.2 [NUEVA MVD]**: `id_contenido ↠ {version_contenido} | {validacion_ita}` — descompuesta, 4FN.
- **B.3 — JD: VEREDICTO 0 REFORZADO** con prueba explícita sobre los 3 candidatos del encargo: (a) radicado anio×dep×consecutivo = FD, no reunión; (b) impuesto×vigencia×tarifa = FD compuesta implicada por clave; (c) trámite×dependencia×funcionario = no existe relación ternaria de competencia libre en el corpus (enrutamiento PQRSD es 1:N temporal). **Total MVD: 8. Total JD genuinas 5FN: 0.**

## C. Claves: correcciones

- **C.1 🔴 — `radicado`**: la clave `{anio, consecutivo_anual}` NO estaba demostrada (la 1ª pasada eligió lectura "global anual" sin evidencia). La investigación web aporta AGN Acuerdo 060/2001 → formato `SM-{DEP}-{AAAA}-{XXXXXX}` con consecutivo **por dependencia+año** ⇒ clave compuesta correcta `{prefijo_dependencia, anio, consecutivo_anual}`. PK natural firme = `{numero_radicado}` (atómico). **Dirime P-08 con norma.**
- **C.2 🔴 — `impuesto` PK INCORRECTA**: `{nombre}` NO es clave (tarifa/base/hecho_generador cambian por vigencia → `{nombre}⁺ ≠ R`, violación 2FN). PK correcta `{nombre, vigencia_desde}`. **FD-C117-1**: `{nombre, vigencia_desde} → sujeto_activo, sujeto_pasivo, hecho_generador, hecho_imponible, causacion, base_gravable, tarifa, proceso_recaudo, url_formulario_liquidacion, vigencia_hasta`. LHS irreducible (probado). **Hallazgo más grave del DELTA.**
- **C.3 🔴 — FK `calendario_tributario → impuesto`**: el cambio de PK de impuesto propaga; la FK debe referenciar `{nombre, vigencia_desde}` (o surrogate). `FD-C118-1`: `{id_impuesto, vigencia_fiscal} → fecha_vencimiento` se mantiene; corrección de cardinalidad de FK.
- **C.4 — `dataset`**: clave natural `{nombre, entidad_publicadora}` sigue **[POR CONFIRMAR]**, surrogate provisional. Ratificado.
- **C.5 — `consentimiento_datos` [NUEVA FD-C11-1]**: clave natural temporal `{id_ciudadano, consent_type, occurred_at} → action, version_politica, …` (historial append-only). Para anónimos (`id_ciudadano` NULL) → surrogate justificado.

## D. Minimal cover: correcciones

- Eliminaciones f5b y f8c de la 1ª pasada **re-verificadas correctas**. f6/f10 correctamente retenidas.
- **AGREGAR a F** (no estaban): `id_tipo_pqrsd → plazo_dias_habiles | es_prorrogable | dias_prorroga_max` (D.1); `{nombre, vigencia_desde} → tarifa | base_gravable | hecho_generador` (D.2, dominio tributario ausente por completo en la 1ª pasada); `{item_1..item_10} → puntaje_sus` (D.4).
- **CORREGIR f19**: desdoblar en `arco_type → plazo_base_dias` y `arco_type → dias_prorroga_max` (D.3).
- **Nuevo tamaño de cobertura mínima del subconjunto significativo: ~33 FD** (27 + tributario + tipo_pqrsd + SUS + arco corregido). Atributos extraños de las FD nuevas verificados irreducibles.

## E. Entidades sin clave natural: veredicto reforzado

Las 4 (`documento_electronico`, `notificacion`, `firma_electronica`, `interop_xroad_transaction`) **correctamente identificadas**; surrogate lógicamente necesario en las 4 (reintentos/arcos exclusivos/dedup impiden clave de columnas fijas). Refuerzo: para `firma_electronica` se recomienda UNIQUE defensivo `{hash_documento, timestamp_firma, id_firmante, <columna_arco_activa>}`. La candidata X-Road `{id_service, …, request_hash}` NO es única bajo reintentos (corrección menor).

## F. Veredicto

**La 1ª pasada NO está completa para C3 sin integrar este DELTA.** Bloqueantes a integrar antes de normalizar: C.2 (PK impuesto), C.3 (FK calendario_tributario), D.1–D.4 (FD ausentes), A.1–A.2 (derivados). Riesgos a escalar (no inventar): P-08 ahora resuelto por norma web (consecutivo por dependencia); P-04 (umbral SUS), P-02 (matriz nivel_auth — web aporta dominio BAJO/MEDIO/ALTO/MUY_ALTO pero no la asignación trámite→nivel), retención por categoría. Confirmado y firme: 0 JD 5FN; 4 entidades sin clave natural; claves compuestas ciudadano/usuario_interno; eliminaciones f5b/f8c.

---

# DELTA — Ronda 2 de dependencias (descomposición de tablas anchas)

> Formaliza las FD detrás de los 5+2 defectos detectados por la extracción profunda, con prueba por cierre, y verifica adversarialmente las 11 anchas-correctas. **0 MVD/JD nuevas.** 🔴 = bloqueante para C3.

## A. FD formalizadas por defecto (cierre + prueba)

- **A.1 🔴 `cita` — 3FN, transitiva oculta vía FK**: `codigo_confirmacion → id_ciudadano` (FD-C53-1) + `id_ciudadano → ciudadano.{nombre,correo,telefono}` (FK+FD-C62-1) ⇒ transitiva `codigo_confirmacion → *_contacto`. `id_ciudadano` NO es superclave de `cita` y `*_contacto` son no-primos ⇒ **viola 3FN** (no-primo→no-primo) solo cuando `id_ciudadano NOT NULL` (duplicación del titular). Para anónimos (`id_ciudadano NULL`) el contacto es hecho propio. **Fix**: contacto condicional con CHECK bidireccional (`id_ciudadano NOT NULL ⇒ *_contacto NULL`; `id_ciudadano NULL ⇒ correo_contacto NOT NULL`) + vista COALESCE. Alternativa: tabla `cita_contacto_anonimo`. → BCNF.
- **A.2 🔴 `contrato` — 3FN, derivados persistidos**: `{monto,valor_ejecutado}→porcentaje_ejecutado`; `{monto,pagos_realizados}→pagos_pendientes`. `{monto,valor_ejecutado}⁺≠R` ⇒ no superclave; RHS no-primos ⇒ transitiva. **Fix**: columnas GENERATED STORED; base `{monto,valor_ejecutado,pagos_realizados}`. → BCNF.
- **A.3 🔴 `ciudadano` — ISA disjoint (NO 4NF, NO MVD)**: `{tipo_doc,num_doc}→razon_social` es FUNCIONAL univaluada (una jurídica = una razón social), NO `↠` ⇒ no hay MVD. Es **partición vertical de subtipo** por NULLs sistemáticos (razon_social/representante NULL para toda persona natural). **Fix class-table**: `ciudadano`(supertipo) + `ciudadano_juridica`(PK/FK→razon_social NOT NULL, representante_legal_id). Lossless Heath sobre `{tipo_doc,num_doc}` (∩=clave de ambas). Disjoint/parcial por trigger `es_persona_juridica`. Afirmar 4NF aquí sería error conceptual.
- **A.4 🔴 `encuesta_experiencia` + `evaluacion_sus` — 1FN (grupo repetitivo)**: `respuesta_pregunta_1/2/3` e `item_1..item_10` son repeating groups (mismo predicado en N columnas posicionales). **Fix**: `respuesta_item_sus({id_evaluacion,numero_item}→respuesta_likert CHECK 1-5)`; `respuesta_encuesta({id_encuesta,numero_pregunta}→valor)`; `puntaje_sus` derivado (fórmula SUS), no almacenado. **NO JSONB** (perpetúa anti-1FN). `pregunta_encuesta` catálogo SOLO si configurable (P-13). FD `{id_evaluacion,numero_item}→respuesta` es BCNF (clave compuesta única determinante).
- **A.5 🔴 `dependencia` — entidad ausente**: destino de ≥8 FK + origen del prefijo del radicado `SM-{codigo}-AAAA-NNNNNN`. **FD-DEP-1**: `codigo → nombre, descripcion, responsable_id, sede_id, es_externa, activa, padre_id` (`{codigo}⁺=R` ⇒ BCNF). **FD-DEP-2 (organigrama)**: `codigo → padre_id`, self-FK reflexiva = adjacency list (jerarquía funcional, árbol, NO MVD/JD). `responsable_id→servidor_publico`. `es_externa=TRUE ⇒ entidad_externa_nombre NOT NULL` (RN-04-D03, traslado Procuraduría). Crear como `C136`.

## B. `solicitud` — redundancia de almacenamiento (NO-FD)
No es violación de FN: cada representación aislada está bien. Es **doble fuente de verdad**: borrador/desistimiento/SAP existen como columnas nullable en `solicitud` Y como entidades 1:1 (C21/C25/C26). Igualdad inter-relacional no impuesta (`solicitud.fecha_desistimiento = desistimiento.fecha`) → anomalía de actualización/inserción/borrado. **Fix**: preservar las entidades 1:1 (tienen atributos propios: desistimiento→politica_reembolso/aprobado_por RN-03-D07; SAP→acto legal RN-03-D08); quitar las columnas duplicadas de `solicitud`; conservar `estado`, `inicio_gestion`; consistencia estado↔existencia por trigger. `solicitud` sigue BCNF.

## C. 4NF/5NF ronda 2
- `ciudadano` ISA = funcional, NO MVD (A.3). JSONB `tramite.georreferenciacion` y `contenido.metadatos_json` son **1FN/ISA** (atributos funcionales de subtipo/multivaluado embebido), NO 4NF — extraer solo si el patrón de acceso exige consulta relacional (a investigar). **Total MVD nuevas: 0. JD 5FN: 0.** Las 8 MVD de ronda 1 intactas; 5NF por vacuidad se mantiene.

## D. Verificación adversarial de las 11 anchas-correctas — TODAS LIMPIAS (BCNF)
Verificado por cierre que ninguna esconde FD no-superclave→no-primo:

| Tabla | Clave | Veredicto |
|---|---|---|
| `tramite` | codigo_suit | LIMPIA — SAP = CHECK condicional, es_gratuito ya col.generada |
| `pqrsd` | id_radicado | LIMPIA — cluster anónimo = CHECK; subtipos en tipo_pqrsd |
| `documento_electronico` | id (surr.) | LIMPIA — AIFID 4 BOOL atómicos; arco = CHECK |
| `usuario_interno` | {tipo_doc,num_doc}/correo/id_sigep | LIMPIA — determinantes superclave; MFA ya separado |
| `sesion` | id (UUID) | LIMPIA — arco actor por discriminador |
| `token_oidc` | id/id_token | LIMPIA — id_token superclave |
| `certificado_digital` | serial_number | LIMPIA — único determinante = clave |
| `dataset` | {nombre,publicadora}[?] | LIMPIA — MVD formato/metadata ya extraída (4FN) |
| `interop_xroad_transaction` | id (surr.) | LIMPIA — clusters TSA/error = CHECK por status |
| `log_auditoria` | record_hash | LIMPIA — `id_sesion`↔`id_usuario_interno` = **snapshot inmutable justificado** (append-only RN-09-D05), NO transitiva 3FN |
| `notificacion` | id (surr.) | LIMPIA — polimorfismo = arco + trigger (H11) |

**No se inventó ninguna violación.** Ancho = clusters condicionales (CHECK) + polimorfismos (arco) + snapshots de auditoría (inmutabilidad) + subtipos ya extraídos.

## E. FD nuevas a integrar en Fc (irreducibles, no redundantes — prueba por cierre)
```
h41: {id_tipo_documento, numero_documento} → razon_social            # cond. es_persona_juridica
h42: {id_tipo_documento, numero_documento} → representante_legal_id  # cond. es_persona_juridica
h43: {id_evaluacion, numero_item} → respuesta_likert                 # respuesta_item_sus
h44: {id_encuesta, numero_pregunta} → valor_respuesta                # respuesta_encuesta
h45: codigo → nombre, descripcion, responsable_id, sede_id, es_externa, activa, padre_id  # dependencia
h46: codigo → entidad_externa_nombre                                 # cond. es_externa
```
Transitivas/derivadas (cita-contacto, contrato×2, SUS-puntaje) NO entran a Fc como FD base (se eliminan en origen vía CHECK/columna generada; viven en F⁺ por transitividad). Nuevo tamaño Fc significativo ≈ 45 FD.

## F. Veredicto para el normalizador (qué descomponer, FN destino, prueba)
| # | Tabla | Defecto | Acción C3 | FN |
|---|---|---|---|---|
| 1🔴 | `cita` | 3FN transitiva | contacto condicional (CHECK) + vista; o `cita_contacto_anonimo` | BCNF |
| 2🔴 | `contrato` | 3FN derivados | porcentaje_ejecutado/pagos_pendientes GENERATED | BCNF |
| 3🔴 | `ciudadano` | ISA disjoint (no 4NF) | class-table + `ciudadano_juridica` | BCNF×2 |
| 4🔴 | `encuesta_experiencia` | 1FN repeating group | `respuesta_encuesta` (+`pregunta_encuesta` si P-13) | BCNF |
| 5🔴 | `evaluacion_sus` | 1FN repeating group | `respuesta_item_sus`; puntaje derivado | BCNF |
| 6🔴 | `dependencia` | entidad ausente | crear C136 (codigo PK, self-FK organigrama, responsable FK) | BCNF |
| 7🔴 | `solicitud` | redundancia (no-FD) | quitar cols duplicadas; preservar C21/C25/C26; trigger | BCNF (s/cambio) |

**NO descomponer (confirmado limpio):** las 11 anchas (BCNF/4FN). JSONB georef/metadatos = 1FN/ISA, extraer solo si el acceso lo exige (a investigar). **A investigar:** organigrama oficial de la Alcaldía (códigos orgánicos reales), P-13 (encuesta fija/configurable), patrón de acceso JSONB, naturaleza de `funcionario_apoyo`.

---

# DELTA — Ronda 3 de dependencias (dependencias profundas: entidades nuevas R3, FD ocultas, 1FN de plan_integracion)

> Revisión de completitud sobre `00-inventario.md §DELTA Ronda 3 (R3.A–R3.F)` + 1ª pasada + DELTA Fase 2 + DELTA Ronda 2. Formaliza FD/cierres/claves de las 5 entidades nuevas firmes, caza FD ocultas en fórmulas/plazos/estados/config, prueba la 6ª tabla ancha (`plan_integracion`) y amplía la minimal cover. NO reescribe. 🔴 = bloqueante para C3. Cero invención: cada FD cita fuente (inventario L###; R3.x).

## A. FD nuevas de entidades nuevas R3 (cierre X⁺, clave candidata, primos)

### A.1 🔴 `plan_integracion_tramite` (N-02 — normaliza JSONB `tramites_incluidos`)
- **FD-PIT-1**: `{id_plan, id_tramite} → nombre_tramite, solicitudes_anio, accion, fecha_objetivo, id_responsable` — la fuente enumera por trámite: nombre, solicitudes/año, acción, fecha, responsable (R3.A N-02; 01 §7 L167). Un trámite figura a lo sumo una vez por plan ⇒ par determinante.
- **Cierre**: `{id_plan, id_tramite}⁺` = + {nombre_tramite, solicitudes_anio, accion, fecha_objetivo, id_responsable} = **R**. ⇒ clave.
- **FD-PIT-2** (derivada de catálogo, transitiva): `id_tramite → nombre_tramite` vía `tramite.nombre` (FD-C16-1). `nombre_tramite` es **redundante/derivado** del catálogo ⇒ candidato a NO persistir (3FN). Si `id_tramite` es NULL (trámite aún no en catálogo SUIT, R3.A N-02 "FK nullable") entonces `nombre_tramite` es hecho propio NOT NULL → dependencia condicional.
- LHS irreducible: `{id_plan}⁺`={id_plan} (un plan tiene N trámites); `{id_tramite}⁺` por catálogo no determina `accion/fecha_objetivo` (eso es del plan) ⇒ ningún atributo extraño.
- **Clave candidata**: `{id_plan, id_tramite}` (con `id_tramite` NOT NULL); si nullable → surrogate + UNIQUE parcial `{id_plan, nombre_tramite}`. **PK natural**: `{id_plan, id_tramite}`.

### A.2 🔴 `plan_integracion_dominio` y `plan_integracion_otro_medio` (N-03 — normaliza JSONB `dominios_web`/`otros_medios`)
- **FD-PID-1**: `{id_plan, valor} → tipo` — `dominios_web` y `otros_medios` (apps, chatbots, PQR) son multivaluados (R3.A N-03; 01 §7 L167). `valor`=url/medio; `tipo`=clasificación. Un valor único por plan.
- **Cierre**: `{id_plan, valor}⁺` = + {tipo} = **R**. ⇒ clave compuesta.
- **Clave candidata**: `{id_plan, valor}` (PK natural). Idéntica estructura para ambas tablas hermanas (mismo patrón list-of-values).

### A.3 🔴 `respuesta_item_sus` (N-07 — normaliza `evaluacion_sus.item_1..item_10`)
- **FD-RIS-1**: `{id_evaluacion_sus, numero_item} → valor_likert` — 10 ítems Likert posicionales sacados a filas (R3.A N-07; 08 §2.A; ya anticipado h43 en Ronda 2 §E). `numero_item ∈ [1,10]`, `valor_likert ∈ [1,5]` (CHECK).
- **Cierre**: `{id_evaluacion_sus, numero_item}⁺` = + {valor_likert} = **R**. ⇒ clave única determinante ⇒ **BCNF** (todo determinante es superclave).
- LHS irreducible: `{id_evaluacion_sus}⁺` (un id agrupa 10 ítems); `{numero_item}⁺` (el ítem 3 existe en todas las evaluaciones) ⇒ ninguno extraño.
- **Clave candidata**: `{id_evaluacion_sus, numero_item}`. Confirma y **da cierre formal** a h43 (Ronda 2 §E.43), que estaba enunciada sin demostración de irreducibilidad.

### A.4 🔴 `respuesta_encuesta` + `pregunta_encuesta` (N-06 — normaliza `encuesta_experiencia.respuesta_pregunta_1/2/3`)
- **FD-RES-1**: `{id_encuesta, id_pregunta} → valor` — respuestas sacadas a filas (R3.A N-06; 03 §2.8; cf. h44 Ronda 2 §E). Si las preguntas son configurables por trámite (P-13) la clave es `{id_encuesta, id_pregunta}`; si son fijas, `{id_encuesta, numero_pregunta}`.
- **Cierre**: `{id_encuesta, id_pregunta}⁺` = + {valor} = **R** ⇒ BCNF.
- **FD-PES-1** (catálogo `pregunta_encuesta`, solo si P-13=configurable): `id_pregunta → id_tramite, texto, orden, tipo` — `id_pregunta` UNIQUE. `{id_pregunta}⁺`=R ⇒ clave.
- **Clave candidata**: `{id_encuesta, id_pregunta}` (respuesta); `{id_pregunta}` (catálogo). **Condicionado a P-13** (decisión de líder de trámites, no investigación web — R3.E #6).

### A.5 🔴 `dependencia` (N-01 — entidad ya creada en Ronda 2 §A.5 como C136; R3 ratifica cierre y añade FD del prefijo de radicado)
- **FD-DEP-1** (ratifica): `codigo → nombre, descripcion, id_responsable, id_sede, es_externa, entidad_externa_nombre, correo, telefono, activa, padre_id` — `codigo` orgánico UNIQUE (R3.A N-01; 02 §7 L143). `{codigo}⁺`=R ⇒ BCNF.
- **FD-DEP-2** (organigrama, ya en Ronda 2): `codigo → padre_id` self-FK reflexiva (adjacency list, árbol; R-01). Jerarquía funcional, **NO** MVD/JD.
- **FD-DEP-3** [NUEVA R3 — fuente del prefijo de radicado]: `dependencia.codigo → radicado.prefijo_dependencia` es la **FD inter-entidad que origina** el formato `SM-{codigo}-AAAA-NNNNNN` (DELTA Fase 2 §C.1; AGN Acuerdo 060/2001). Confirma que la clave compuesta correcta de `radicado` es `{prefijo_dependencia, anio, consecutivo_anual}` (DELTA Fase 2 §C.1 🔴), donde `prefijo_dependencia` ≡ `dependencia.codigo`. **Cierra el conflicto P-08** a nivel de dependencia funcional.
- **FD-DEP-4** (condicional, ya en Ronda 2 §A.5 h46): `es_externa=TRUE → entidad_externa_nombre NOT NULL` (RN-04-D03, traslado Procuraduría/autoridades). Dependencia de existencia.
- **Clave candidata**: `{codigo}` (PK natural). Primos: `codigo`. No primos: resto.

### A.6 `rate_limit_contador` (F-04 — rate-limiting elevado a persistible)
- **FD-RLC-1**: `{clave_sujeto, ventana_inicio, recurso} → contador, bloqueado_hasta` — RNF-09-D02 + HU-09-D07 elevan el throttling por IP/sesión a requisito persistible (R3.B F-04; stakeholders ST-05). `clave_sujeto` = IP o id_sesion; `recurso` = login/PQRSD/búsqueda; `ventana_inicio` = bucket temporal.
- **Cierre**: `{clave_sujeto, ventana_inicio, recurso}⁺` = + {contador, bloqueado_hasta} = **R** ⇒ clave compuesta, BCNF.
- LHS irreducible: las tres dimensiones son ortogonales (mismo IP, distinta ventana o distinto recurso = fila distinta). **[INFERIDO parcial]**: la fuente fija la necesidad y las dimensiones (clave/ventana/contador/bloqueado_hasta) pero no nombra las columnas exactas → marca de inferencia controlada (R3.B F-04 ya lo marca).
- **Clave candidata**: `{clave_sujeto, ventana_inicio, recurso}`. Alternativa de modelado: campos en `intento_login` (no entidad propia) — decisión C3; en ese caso la FD vive sobre `intento_login` con misma estructura.

## B. FD ocultas en config / fórmulas / plazos / estados (no entidades nuevas)

- **B.1 [NUEVA FD-TSA-1] `interop_tsa_config` — unicidad parcial por ambiente**: `ambiente → host_salida, puerto, proveedor, es_activo` con la restricción `(ambiente) WHERE es_activo=TRUE` **UNIQUE** (1 config activa por ambiente — inventario C85 "1 activo por ambiente"; F-08 fija host='tsa.gse.com.co', puerto=443, proveedor=GSE). Es FD + **UNIQUE parcial** (mismo patrón que RF-B1-009 política vigente). `{ambiente}⁺` no es R si hay histórico de configs ⇒ clave natural completa `{ambiente, vigencia_desde}` o surrogate + UNIQUE parcial. **Determinante**: `ambiente` para la fila activa.
- **B.2 [NUEVA FD-MEC-1] `mecanismo_participacion` — cierre automático derivado**: `{fecha_cierre_programada, fecha_actual} → estado` (cierre automático, inventario C41 "ciclo de vida y cierre automático"; 05). El paso a `cerrado` es **derivado temporal** (no hecho ingresado) ⇒ no se materializa `estado=cerrado` como dato independiente sin trigger. + **FD-MEC-2** [F-06/R-03]: `id_mecanismo → id_oficina_responsable` (FK→dependencia, Oficina de Participación Art.17 Ley 2052). Refuerza que `mecanismo_participacion` NO es satélite sin dueño.
- **B.3 [NUEVA FD-FRA-1, agregación] `franja_horaria.cupos_disponibles`**: `cupos_disponibles = cupos_total − COUNT(cita activa sobre la franja)` (RN-06-D01/D02). Ya enunciada en DELTA Fase 2 §A.5; R3 la **ratifica como dependencia de agregación** (no FD pura) ⇒ derivado; materialización solo por excepción de concurrencia (`SELECT … FOR UPDATE`). Sin cambio respecto a Fase 2 — se referencia para completitud.
- **B.4 [NUEVA FD-INC-1, doble destino de reporte] `incidente_seguridad`**: `id_incidente → csirt_reported_at, sic_rnbd_reportado, sic_reporte_fecha` — el reporte va a **dos destinos independientes**: CSIRT/ColCERT y SIC/RNBD (R3.B F-01; 09 §8 L172). El inventario solo tenía `sic_notified_at`; F-01 separa `sic_rnbd_reportado`(BOOL) + `sic_reporte_fecha`. Son **dos hechos funcionalmente independientes** del incidente (no uno determina el otro) ⇒ ambos no-primos directos de la clave, NO transitiva. No es violación de FN; es completitud de atributos.
- **B.5 [NUEVA FD-CON-1, traducción/fallback] `contenido_traduccion`** (F-11, C135 DIFERIDA): `{id_contenido, idioma} → cuerpo_traducido, es_original` con RN-TX-D05 (fallback al castellano marcando el original). `{id_contenido, idioma}⁺`=R ⇒ clave compuesta. `es_original`/`fallback` es flag funcional del par. **Condicionado** a que se active C135.

## C. MVD/JD re-evaluadas (Ronda 3)

- **C.1 [NUEVA MVD]** `id_plan ↠ {plan_integracion_tramite} | {plan_integracion_dominio} | {plan_integracion_otro_medio}` — los trámites a converger, los dominios web y los otros medios de un plan son **tres conjuntos multivaluados independientes** (R3.A N-02/N-03; R-04). Una tabla universal `plan(tramite, dominio, otro_medio)` produciría producto cartesiano espurio (anomalía MVD clásica). Justifica la descomposición en las 3 hijas → **4FN**. Esta es la MVD que faltaba: las rondas 1-2 dejaron los 3 atributos como JSONB embebido, ocultando la multivaluación.
- **C.2 [NUEVA MVD]** `id_evaluacion_sus ↠ {respuesta_item_sus}` y `id_encuesta ↠ {respuesta_encuesta}` — multivaluación de respuestas; ya cubierta funcionalmente por la clave compuesta (FD-RIS-1/FD-RES-1) ⇒ la MVD está **implicada por la clave** ⇒ no exige 4FN adicional sobre lo que ya da BCNF. Se anota por completitud, NO suma a la cuenta de MVD no triviales independientes.
- **C.3 — JD: VEREDICTO 0 REFORZADO (3ª vez)**: las 3 hijas del plan se reconstruyen sin pérdida por reunión con `id_plan` (cada una comparte solo `id_plan` con el padre; reunión natural lossless por Heath, ∩=`id_plan`=clave del padre). Esta JD **SÍ está implicada por la clave** `id_plan` ⇒ no es JD no trivial 5FN. Ninguna RN del corpus describe restricción de reunión ternaria cíclica. **Total JD genuinas 5FN: 0** (Date 2015 cap.14: 4FN ⇒ 5FN salvo reunión "no obvia"; aquí ninguna).

**Total MVD no triviales acumuladas: 9** (8 previas + C.1 plan_integracion). **Total JD 5FN: 0.**

## D. Tablas anchas R3.D — FD que prueban la violación

| Tabla | FD probatoria | Violación | Veredicto |
|---|---|---|---|
| 🔴 `plan_integracion` | atributos consultables (`accion, fecha_objetivo, id_responsable, tipo`) **embebidos en JSONB** `tramites_incluidos`/`dominios_web`/`otros_medios` ⇒ NO atómicos ⇒ no hay FD `{id_plan,?}→atributo` consultable relacionalmente | **1FN** (grupo multivaluado embebido) | **DESCOMPONER** a PIT-1/PID-1 (3 hijas). MVD C.1 lo confirma. Ronda 2 NO lo tocó. |
| 🔴 `evaluacion_sus` | `item_1..item_10` posicionales | **1FN** repeating group | **NORMALIZAR** a `respuesta_item_sus` (FD-RIS-1, BCNF). `puntaje_sus` derivado (fórmula SUS, Fase 2 §A.1) NO almacenado. |
| 🔴 `encuesta_experiencia` | `respuesta_pregunta_1/2/3` posicionales | **1FN** repeating group | **NORMALIZAR** a `respuesta_encuesta` (FD-RES-1) si P-13=configurable; +`pregunta_encuesta` catálogo (FD-PES-1). |
| `dataset` | `metadatos_completos`(BOOL) es derivable de la presencia de los metadatos obligatorios en `dataset_metadata` ⇒ `{metadatos obligatorios presentes} → metadatos_completos` | **3FN** (no-primo derivado de no-primos si se materializa) | **NO persistir** o **columna GENERATED**. Añadir a lista de derivados 3FN. |
| `carpeta_ciudadana` | `historial_tramites`/`historial_solicitudes` JSONB = traza de consulta, NO réplica persistente (RF-B2-021 prohíbe almacenamiento permanente) | ninguna | **NO descomponer** — JSONB de traza ACEPTABLE, documentado. |

## E. FD nuevas a integrar en Fc (irreducibles, no redundantes — prueba por cierre)

```
r51: {id_plan, id_tramite} → solicitudes_anio          # plan_integracion_tramite
r52: {id_plan, id_tramite} → accion
r53: {id_plan, id_tramite} → fecha_objetivo
r54: {id_plan, id_tramite} → id_responsable
r55: {id_plan, valor} → tipo                           # plan_integracion_dominio/_otro_medio
r56: {id_evaluacion_sus, numero_item} → valor_likert   # respuesta_item_sus (≡ h43, ahora con cierre)
r57: {id_encuesta, id_pregunta} → valor                # respuesta_encuesta (≡ h44)
r58: id_pregunta → id_tramite, texto, orden, tipo      # pregunta_encuesta (cond. P-13)
r59: codigo → padre_id                                  # dependencia organigrama (≡ h45 parcial)
r60: ambiente → host_salida, puerto, proveedor         # interop_tsa_config (WHERE es_activo)
r61: id_mecanismo → id_oficina_responsable             # mecanismo_participacion (F-06)
r62: {clave_sujeto, ventana_inicio, recurso} → contador, bloqueado_hasta   # rate_limit (F-04, [INFERIDO parcial])
r63: {id_contenido, idioma} → es_original              # contenido_traduccion (cond. C135)
```
Pruebas de irreducibilidad (muestra): `{id_plan}⁺={id_plan}`, `{id_tramite}⁺` por catálogo ≠ accion/fecha ⇒ r51-r54 LHS irreducible. `{numero_item}⁺={numero_item}` ⇒ r56 irreducible. `{ambiente}⁺={ambiente}` ⇒ r60 LHS unitario sin extraños.

**Derivadas que NO entran a Fc** (se eliminan en origen vía catálogo/columna generada; viven en F⁺): `id_tramite→nombre_tramite` (PIT-2, derivado de catálogo); `{metadatos obligatorios}→metadatos_completos` (dataset); `cupos_disponibles` (agregación FRA-1); cierre automático de mecanismo (MEC-1 temporal).

**Nuevo tamaño Fc significativo ≈ 58 FD** (≈45 de Ronda 2 + 13 firmes de R3: r51-r57, r59-r62; r58/r63 condicionadas a P-13/C135). Atributos extraños verificados irreducibles; no rompen la cobertura previa (LHS disjuntos de los existentes: ningún LHS de R3 coincide con un LHS de Fase 2/Ronda 2 ⇒ no hay nuevas redundancias inducidas).

## F. Claves corregidas / nuevas (cierre demostrado)

| Entidad | Clave candidata natural | Cierre | PK elegida |
|---|---|---|---|
| `plan_integracion_tramite` | `{id_plan, id_tramite}` (id_tramite NOT NULL) | `{id_plan,id_tramite}⁺`=R | `{id_plan, id_tramite}` |
| `plan_integracion_dominio` | `{id_plan, valor}` | `{id_plan,valor}⁺`=R | `{id_plan, valor}` |
| `plan_integracion_otro_medio` | `{id_plan, valor}` | idem | `{id_plan, valor}` |
| `respuesta_item_sus` | `{id_evaluacion_sus, numero_item}` | =R, BCNF | `{id_evaluacion_sus, numero_item}` |
| `respuesta_encuesta` | `{id_encuesta, id_pregunta}` | =R, BCNF | `{id_encuesta, id_pregunta}` |
| `pregunta_encuesta` | `{id_pregunta}` (cond. P-13) | =R | `{id_pregunta}` |
| `rate_limit_contador` | `{clave_sujeto, ventana_inicio, recurso}` [INFERIDO parcial] | =R | misma (o campos en intento_login) |
| `dependencia` (ratif.) | `{codigo}` | =R, BCNF | `{codigo}` — y **`codigo` origina `radicado.prefijo_dependencia`** (FD-DEP-3, cierra P-08) |
| `interop_tsa_config` (corr.) | `{ambiente, vigencia_desde}` o surrogate + UNIQUE parcial `(ambiente) WHERE es_activo` | `{ambiente}⁺`≠R con histórico | `{ambiente, vigencia_desde}` |

## G. Veredicto Ronda 3

- **6ª tabla 1FN confirmada**: `plan_integracion` (3 JSONB) — única ancha NUEVA de R3; descomponer a 3 hijas (PIT/PID/POM). 🔴
- **Cierres formales añadidos** a entidades que Ronda 2 enunció sin demostrar (h43/h44/h45 → ahora con X⁺ y prueba de irreducibilidad como FD-RIS-1/FD-RES-1/FD-DEP).
- **FD-DEP-3 cierra P-08** a nivel funcional: `dependencia.codigo` es el determinante del prefijo del radicado ⇒ ratifica clave `{prefijo_dependencia, anio, consecutivo_anual}` de `radicado` (DELTA Fase 2 §C.1).
- **9 MVD no triviales** (8 + plan_integracion); **0 JD genuinas 5FN** (reforzado 3ª vez).
- **Fc ≈ 58 FD**; las nuevas no rompen la cobertura previa (LHS disjuntos).
- **A investigar (R3.E, no inventado)**: organigrama oficial (códigos de `dependencia`), P-13 (encuesta fija/configurable → afecta r57/r58), ítems exactos SUS (texto, no estructura — la estructura ya está probada), caracterización DAFP de grupos (F-07), columnas exactas de `rate_limit_contador` (r62 [INFERIDO parcial]), esquema CCD (P-18), baja de portal GOV.CO (V-04).
