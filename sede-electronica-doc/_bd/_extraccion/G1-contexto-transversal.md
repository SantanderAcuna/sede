# Extracción BD — Global G1 Contexto Transversal

## 0. Cobertura

Unidades leídas íntegras: **5 de 5, 0 omitidas**.

| Archivo | Líneas leídas |
|---------|--------------|
| `_global/contexto-transversal.md` | 1–237 |
| `README.md` | 1–155 |
| `_global/auditoria-cobertura.md` | 1–83 |
| `_levantamiento/solicitud-inventario-micrositios.md` | 1–70 |
| `_levantamiento/borrador-solicitud-AND.md` | 1–75 |

Líneas totales cubiertas: **620**.

---

## 1. Entidades candidatas NÚCLEO

| # | Nombre entidad | Descripción | CANDIDATA-COMPARTIDA | Fuente |
|---|---------------|-------------|----------------------|--------|
| E01 | **Sede Electrónica** | Portal único del Distrito; URL original, enmascaramiento GOV.CO, institución, categoría, sector, estado de integración | CANDIDATA-COMPARTIDA | contexto-transversal §1 #241,#209 |
| E02 | **Tramite** (Trámite/OPA/Consulta) | Unidad de servicio registrada en SUIT; incluye trámites, OPAs y consultas de la Alcaldía | CANDIDATA-COMPARTIDA | contexto-transversal §1 #128,#152,#172,#131 |
| E03 | **Solicitud / Radicado** | Instancia de ejecución de un trámite o PQRSD; tiene número único y ciclo de vida propio | CANDIDATA-COMPARTIDA | contexto-transversal §1 #128,#143,#152 |
| E04 | **PQRSD** | Petición, Queja, Reclamo, Sugerencia o Denuncia presentada por un ciudadano | CANDIDATA-COMPARTIDA | contexto-transversal §1 #239,#225,#209 |
| E05 | **Ciudadano / Usuario** | Persona natural o jurídica que interactúa con la sede; titular de datos personales | CANDIDATA-COMPARTIDA | contexto-transversal §1 #122,#124,#143; README §6 |
| E06 | **DatoPersonalSensible** | Subtipo de dato del ciudadano: salud, biometría, origen étnico, orientación política/sexual, religión, sindicatos | CANDIDATA-COMPARTIDA | contexto-transversal §1 #122 |
| E07 | **ResultadoTramite** | Acto administrativo o documento de facultación que cierra un trámite; URL en Carpeta Ciudadana | CANDIDATA-COMPARTIDA | contexto-transversal §1 #128,#152 |
| E08 | **ExpedienteElectronico** | Conjunto de documentos con metadatos, foliado, índice firmado, TRD y ciclo vital | CANDIDATA-COMPARTIDA | contexto-transversal §1 #131,#217,#251 |
| E09 | **TokenOIDC** | Artefacto de autenticación delegada (SCD): id_token, access_token, refresh_token, authorization_code, client_id, client_secret | CANDIDATA-COMPARTIDA | contexto-transversal §1 #124 |
| E10 | **MensajeXRoad** | Unidad de intercambio del bus de interoperabilidad PDI/X-Road entre entidades | CANDIDATA-COMPARTIDA | contexto-transversal §1 #124,#126 |
| E11 | **CarpetaCiudadana** | Espacio digital de documentos y comunicaciones del ciudadano custodiado por la AND | CANDIDATA-COMPARTIDA | contexto-transversal §1 #124 |
| E12 | **CertificadoDigital** | Certificado X.509 emitido por CA (ONAC/GSE); con estado OCSP y vencimiento | CANDIDATA-COMPARTIDA | contexto-transversal §1 #124,#140 |
| E13 | **Cookie** | Objeto de rastreo/sesión web; tiene tipo, finalidad, origen, gestor, periodo de conservación y consentimiento | CANDIDATA-COMPARTIDA | contexto-transversal §1 #241,#70 |
| E14 | **Normativa** | Acto jurídico (ley, decreto, resolución); tipo, número, fechas, epígrafe, enlace, vigencia | CANDIDATA-COMPARTIDA | contexto-transversal §1 #7,#225 |
| E15 | **Contratacion** | Ítem del plan anual de adquisiciones o contrato público (SECOP); objeto, monto, ejecución | CANDIDATA-COMPARTIDA | contexto-transversal §1 #225 |
| E16 | **DirectorioInstitucional** (Dependencia) | Dependencia de la Alcaldía con responsable, cargo, correo, teléfono, código SIGEP | CANDIDATA-COMPARTIDA | contexto-transversal §1 #239,#225 |
| E17 | **InformacionTributaria** | Ficha de impuesto distrital: sujetos, hecho generador, base, tarifa, liquidación | CANDIDATA-COMPARTIDA | contexto-transversal §1 #239,#225 |
| E18 | **Dataset** | Activo de datos abiertos publicado en datos.gov.co o en la sede | CANDIDATA-COMPARTIDA | contexto-transversal §1 #8,#242 |
| E19 | **CanalAtencion** | Canal físico o digital de atención al ciudadano: dirección, horario, teléfono, correo | CANDIDATA-COMPARTIDA | contexto-transversal §1 #239 |
| E20 | **Cita** | Reserva de atención presencial o virtual: dependencia, fecha, hora, contacto, código, estado | CANDIDATA-COMPARTIDA | contexto-transversal §1 #225,#241 |
| E21 | **AuditoriaITA** | Registro de cumplimiento ITA por módulo/norma; puntuación, avance %, responsable | CANDIDATA-COMPARTIDA | contexto-transversal §1 #239 |
| E22 | **LogAuditoria** | Evento auditable: actor, timestamp Hora Legal, IP, hash; incluye log de consentimiento | CANDIDATA-COMPARTIDA | contexto-transversal §1 #156,#122 |
| E23 | **EvaluacionSUS** | Cuestionario de usabilidad System Usability Scale; 10 ítems Likert, puntaje | CANDIDATA-COMPARTIDA | contexto-transversal §1 #190 |
| E24 | **Arquetipo** (proto-persona) | Perfil de usuario para diseño UX: edad, ubicación, nivel digital, necesidades, frustraciones | CANDIDATA-COMPARTIDA | contexto-transversal §1 #60,#182 |
| E25 | **PlanIntegracion** | Plan de convergencia de micrositios/apps al portal único; trámites, dominios web, otros medios | CANDIDATA-COMPARTIDA | contexto-transversal §1 #181 |
| E26 | **SistemaExterno** (Integración) | Sistema del Estado con el que la sede se integra (SUIT, SECOP, X-Road, etc.) | CANDIDATA-COMPARTIDA | contexto-transversal §2 |
| E27 | **Micrositio** | Portal, micrositio o aplicación independiente del Distrito a convergir/enlazar | CANDIDATA-COMPARTIDA | solicitud-inventario-micrositios §2 |
| E28 | **Rol** | Rol de actor interno del sistema (administrador, editor, aprobador, funcionario, etc.) | CANDIDATA-COMPARTIDA | README §7; contexto-transversal §11.3 |
| E29 | **CalendarioHabil** | Fuente única de días hábiles del Distrito (excluye sábados, domingos, festivos nacionales/distritales) | CANDIDATA-COMPARTIDA | contexto-transversal §10 RN-TX-D01 |
| E30 | **Consentimiento** | Registro de aceptación de política de datos por un ciudadano; versión de política, timestamp TSA | CANDIDATA-COMPARTIDA | contexto-transversal §1 #122,#156 |

---

## 2. Atributos / campos por entidad

### E01 — Sede Electrónica
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_sede | INTEGER | Sí | PK autoincremental | PK | [INFERIDO] |
| url_original | VARCHAR(500) | Sí | URL válida | — | contexto-transversal §1 #241 |
| palabra_clave | VARCHAR(200) | No | — | — | contexto-transversal §1 #241 |
| url_enmascarada_govco | VARCHAR(500) | No | Formato `https://www.gov.co/...` | — | contexto-transversal §1 #241,#209 |
| institucion | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #241 |
| categoria | VARCHAR(100) | No | — | — | contexto-transversal §1 #241 |
| sector | VARCHAR(100) | No | — | — | contexto-transversal §1 #241 |
| estado_integracion | VARCHAR(50) | Sí | ('pendiente','en_proceso','integrada','desactivada') | — | contexto-transversal §1 #241 |

### E02 — Tramite
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_tramite | INTEGER | Sí | PK autoincremental | PK | [INFERIDO] |
| id_suit | VARCHAR(20) | Sí | Formato `TXX`; UNIQUE | UNIQUE | contexto-transversal §1 #128 |
| nombre | VARCHAR(500) | Sí | — | — | contexto-transversal §1 #128 |
| modalidad | VARCHAR(100) | No | — | — | contexto-transversal §1 #128 |
| descripcion | TEXT | No | — | — | contexto-transversal §1 #128 |
| requisitos | TEXT | No | — | — | contexto-transversal §1 #128 |
| pasos | TEXT | No | — | — | contexto-transversal §1 #128 |
| costo | DECIMAL(15,2) | No | ≥ 0 | — | contexto-transversal §1 #128 |
| tiempo_estimado | VARCHAR(100) | No | — | — | contexto-transversal §1 #128 |
| documentos_requeridos | TEXT | No | — | — | contexto-transversal §1 #128 |
| resultado_descripcion | TEXT | No | — | — | contexto-transversal §1 #128 |
| grupo_objetivo | VARCHAR(200) | No | — | — | contexto-transversal §1 #128 |
| nivel_transformacion | SMALLINT | No | CHECK (1–6) | — | contexto-transversal §1 #172 |
| url_tramite | VARCHAR(500) | No | — | — | contexto-transversal §1 #128 |
| georreferenciacion | VARCHAR(200) | No | — | — | contexto-transversal §1 #131 |
| estado_estandarizado | VARCHAR(50) | Sí | — | — | contexto-transversal §1 #128 |
| nivel_autenticacion_minimo | VARCHAR(20) | Sí | ('bajo','medio','alto','muy_alto') | — | contexto-transversal §10 RN-TX-D04; C-08 |
| tipo_silencio_administrativo | VARCHAR(20) | Sí | ('negativo','positivo') | — | contexto-transversal §8 #19; RN-03-D08 |
| id_dependencia | INTEGER | No | FK → DirectorioInstitucional | FK | contexto-transversal §1 #128 |

### E03 — Solicitud / Radicado
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_solicitud | INTEGER | Sí | PK autoincremental | PK | [INFERIDO] |
| numero_radicado | VARCHAR(30) | Sí | Formato `SM-{dep}-{año}-{seq6}`; UNIQUE; atómico | UNIQUE | contexto-transversal §8 #11 (`SM-CAT-2026-000123`) |
| fecha_hora_radicado | TIMESTAMP WITH TIME ZONE | Sí | Hora Legal Colombia (INM); sellado TSA | — | contexto-transversal §10 RN-TX-D02 |
| estado | VARCHAR(50) | Sí | — | — | contexto-transversal §1 #128 |
| tiempo_estimado_respuesta | INTERVAL | No | — | — | contexto-transversal §1 #152 |
| pago_requerido | BOOLEAN | Sí | DEFAULT false | — | contexto-transversal §1 #128 |
| emisor_tipo | VARCHAR(20) | Sí | ('ciudadano','funcionario','sistema') | — | contexto-transversal §1 #128 [INFERIDO] |
| id_ciudadano | INTEGER | No | FK → Ciudadano; NULL si anónimo | FK | contexto-transversal §1 #143 |
| id_tramite | INTEGER | No | FK → Tramite | FK | contexto-transversal §1 #128 |
| id_dependencia_destino | INTEGER | No | FK → DirectorioInstitucional | FK | contexto-transversal §1 #143 |
| datos_formulario | JSONB | No | — | — | contexto-transversal §1 #128 |
| fecha_vencimiento | DATE | No | Calculada sobre CalendarioHabil | — | contexto-transversal §10 RN-TX-D01 |
| fecha_expiracion_borrador | DATE | No | 30 días desde creación si es borrador | — | contexto-transversal §8 #10 |

### E04 — PQRSD
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_pqrsd | INTEGER | Sí | PK | PK | [INFERIDO] |
| tipo | VARCHAR(20) | Sí | ('peticion','queja','reclamo','sugerencia','denuncia') | — | contexto-transversal §1 #239 |
| es_anonima | BOOLEAN | Sí | DEFAULT false | — | contexto-transversal §1 #239 |
| identificacion_peticionario | VARCHAR(30) | No | NULL si anónima | — | contexto-transversal §1 #239 |
| correo | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| telefono | VARCHAR(20) | No | — | — | contexto-transversal §1 #239 |
| direccion | VARCHAR(300) | No | — | — | contexto-transversal §1 #239 |
| canal | VARCHAR(50) | Sí | ('web','presencial','telefono','correo','chat') | — | contexto-transversal §1 #239 |
| id_dependencia | INTEGER | No | FK → DirectorioInstitucional | FK | contexto-transversal §1 #239 |
| objeto | TEXT | Sí | CHECK (length ≤ 2000) | — | contexto-transversal §1 #239 |
| estado | VARCHAR(50) | Sí | — | — | contexto-transversal §1 #239 |
| id_radicado | INTEGER | Sí | FK → Solicitud | FK | contexto-transversal §1 #128 |
| traslado_dependencia | INTEGER | No | FK → DirectorioInstitucional | FK | contexto-transversal §1 #239 |
| identidad_reservada | BOOLEAN | Sí | DEFAULT false; relación con Procuraduría | — | contexto-transversal §2 #239 |

### E05 — Ciudadano / Usuario
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_ciudadano | INTEGER | Sí | PK | PK | [INFERIDO] |
| tipo_documento | VARCHAR(10) | Sí | ('CC','CE','TI','PEP','NIT') | — | contexto-transversal §1 #122,#124 |
| numero_documento | VARCHAR(30) | Sí | UNIQUE con tipo_documento | UNIQUE | contexto-transversal §1 #124 |
| nombre_completo | VARCHAR(300) | No | NULL si anónimo | — | contexto-transversal §1 #122 |
| correo | VARCHAR(200) | No | — | — | contexto-transversal §1 #122 |
| telefono | VARCHAR(20) | No | — | — | contexto-transversal §1 #122 |
| direccion | VARCHAR(300) | No | — | — | contexto-transversal §1 #122 |
| nivel_autenticacion | VARCHAR(20) | No | ('bajo','medio','alto','muy_alto') | — | contexto-transversal §2 #119 |
| biometria_requerida | BOOLEAN | Sí | DEFAULT false; aplicable para alto/muy alto | — | contexto-transversal §1 #122,#124 |
| id_version_politica | INTEGER | No | FK → Consentimiento | FK | contexto-transversal §1 #122 |
| es_persona_juridica | BOOLEAN | Sí | DEFAULT false; NIT → true | — | README §6 [INFERIDO] |

### E06 — DatoPersonalSensible
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_dato_sensible | INTEGER | Sí | PK | PK | [INFERIDO] |
| id_ciudadano | INTEGER | Sí | FK → Ciudadano | FK | contexto-transversal §1 #122 |
| categoria | VARCHAR(50) | Sí | ('salud','biometria','etnia','orientacion_politica','orientacion_sexual','religion','sindicato') | — | contexto-transversal §1 #122 |
| valor_cifrado | BYTEA | No | Almacenado cifrado | — | contexto-transversal §1 #122 [INFERIDO] |
| fecha_registro | TIMESTAMP WITH TIME ZONE | Sí | — | — | [INFERIDO] |

### E07 — ResultadoTramite
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_resultado | INTEGER | Sí | PK | PK | [INFERIDO] |
| id_solicitud | INTEGER | Sí | FK → Solicitud | FK | contexto-transversal §1 #128 |
| tipo_resultado | VARCHAR(50) | Sí | ('acto_administrativo','documento_facultacion','certificado','otro') | — | contexto-transversal §1 #128,#152 |
| url_carpeta_ciudadana | VARCHAR(500) | No | URL en CCD | — | contexto-transversal §1 #128 |
| fecha_emision | TIMESTAMP WITH TIME ZONE | Sí | Sellado TSA | — | contexto-transversal §10 RN-TX-D02 |
| id_expediente | INTEGER | No | FK → ExpedienteElectronico | FK | contexto-transversal §1 #131 |

### E08 — ExpedienteElectronico
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_expediente | INTEGER | Sí | PK | PK | [INFERIDO] |
| foliado | INTEGER | Sí | Secuencial dentro del expediente | — | contexto-transversal §1 #217 |
| indice_firmado | BOOLEAN | Sí | DEFAULT false | — | contexto-transversal §1 #131 |
| trd_codigo | VARCHAR(50) | No | FK → TRD (entidad externa) | — | contexto-transversal §1 #217 |
| ciclo_vital | VARCHAR(30) | Sí | ('activo','semiactivo','historico') | — | contexto-transversal §1 #217 |
| meta_autenticidad | BOOLEAN | Sí | — | — | contexto-transversal §1 #217 |
| meta_integridad | BOOLEAN | Sí | — | — | contexto-transversal §1 #217 |
| meta_fiabilidad | BOOLEAN | Sí | — | — | contexto-transversal §1 #217 |
| meta_disponibilidad | BOOLEAN | Sí | — | — | contexto-transversal §1 #217 |
| id_solicitud | INTEGER | No | FK → Solicitud | FK | contexto-transversal §1 #131 |

### E09 — TokenOIDC
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_token_oidc | INTEGER | Sí | PK | PK | [INFERIDO] |
| id_token | TEXT | Sí | JWT; UNIQUE | UNIQUE | contexto-transversal §1 #124 |
| access_token | TEXT | Sí | — | — | contexto-transversal §1 #124 |
| refresh_token | TEXT | No | — | — | contexto-transversal §1 #124 |
| authorization_code | VARCHAR(200) | No | — | — | contexto-transversal §1 #124 |
| client_id | VARCHAR(100) | Sí | — | — | contexto-transversal §1 #124 |
| client_secret | VARCHAR(200) | Sí | Almacenado cifrado | — | contexto-transversal §1 #124 |
| id_ciudadano | INTEGER | No | FK → Ciudadano | FK | contexto-transversal §1 #124 |
| fecha_expiracion | TIMESTAMP WITH TIME ZONE | Sí | — | — | contexto-transversal §1 #124 [INFERIDO] |

### E10 — MensajeXRoad
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_mensaje | INTEGER | Sí | PK | PK | [INFERIDO] |
| instancia | VARCHAR(100) | Sí | — | — | contexto-transversal §1 #124 |
| member_class | VARCHAR(10) | Sí | ('GOB','PRIV') [AMBIGUO: también 'CO' en guía 2019] | — | contexto-transversal §7 C-01-xroad |
| member_code | VARCHAR(50) | Sí | Sigla-SIGEP | — | contexto-transversal §1 #124 |
| subsystem_code | VARCHAR(100) | Sí | — | — | contexto-transversal §1 #124 |
| service_code | VARCHAR(100) | Sí | — | — | contexto-transversal §1 #124 |
| service_version | VARCHAR(20) | No | — | — | contexto-transversal §1 #124 |
| firma | TEXT | No | RSA-SHA512 | — | contexto-transversal §2 #124 |
| estampa_tsa | TIMESTAMP WITH TIME ZONE | No | RFC 3161 / GSE | — | contexto-transversal §2 #140 |
| log_referencia | INTEGER | No | FK → LogAuditoria | FK | contexto-transversal §1 #124 |

### E11 — CarpetaCiudadana
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_carpeta | INTEGER | Sí | PK | PK | [INFERIDO] |
| id_mensaje | VARCHAR(100) | Sí | UNIQUE; ID del mensaje en la CCD | UNIQUE | contexto-transversal §1 #124 |
| asunto | VARCHAR(500) | Sí | — | — | contexto-transversal §1 #124 |
| texto_mensaje | TEXT | No | — | — | contexto-transversal §1 #124 |
| url_descargue_adjuntos | VARCHAR(500) | No | — | — | contexto-transversal §1 #124 |
| fecha_mensaje | TIMESTAMP WITH TIME ZONE | Sí | — | — | contexto-transversal §1 #124 |
| historial_tramites | JSONB | No | REST GET desde SCD | — | contexto-transversal §2 #124 |
| historial_solicitudes | JSONB | No | REST GET desde SCD | — | contexto-transversal §2 #124 |
| id_ciudadano | INTEGER | Sí | FK → Ciudadano | FK | contexto-transversal §1 #124 |

### E12 — CertificadoDigital
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_certificado | INTEGER | Sí | PK | PK | [INFERIDO] |
| tipo | VARCHAR(50) | Sí | ('institucional','personal','servidor') | — | contexto-transversal §1 #124 |
| estado_ocsp | VARCHAR(20) | Sí | ('valido','revocado','desconocido') | — | contexto-transversal §1 #124 |
| fecha_vencimiento | DATE | Sí | — | — | contexto-transversal §1 #124 |
| ca_emisor | VARCHAR(20) | Sí | ('ONAC','GSE') | — | contexto-transversal §1 #124,#140 |
| numero_serie | VARCHAR(100) | No | UNIQUE | UNIQUE | [INFERIDO] |

### E13 — Cookie
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_cookie | INTEGER | Sí | PK | PK | [INFERIDO] |
| tipo | VARCHAR(50) | Sí | ('esencial','analitica','marketing','preferencia') | — | contexto-transversal §1 #241,#70 |
| finalidad | VARCHAR(300) | Sí | — | — | contexto-transversal §1 #241 |
| origen | VARCHAR(200) | No | — | — | contexto-transversal §1 #241 |
| gestor | VARCHAR(200) | No | — | — | contexto-transversal §1 #241 |
| periodo_conservacion | INTERVAL | No | — | — | contexto-transversal §1 #241 |
| requiere_consentimiento | BOOLEAN | Sí | DEFAULT true | — | contexto-transversal §1 #241 |

### E14 — Normativa
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_normativa | INTEGER | Sí | PK | PK | [INFERIDO] |
| tipo | VARCHAR(50) | Sí | ('ley','decreto','resolucion','directiva','circular') | — | contexto-transversal §1 #7,#225 |
| numero | VARCHAR(50) | Sí | — | — | contexto-transversal §1 #225 |
| fecha_expedicion | DATE | No | — | — | contexto-transversal §1 #225 |
| fecha_publicacion | DATE | No | — | — | contexto-transversal §1 #225 |
| epigrafe | VARCHAR(500) | No | — | — | contexto-transversal §1 #225 |
| enlace | VARCHAR(500) | No | URL al Diario Oficial / SUIN | — | contexto-transversal §1 #225 |
| vigente | BOOLEAN | Sí | DEFAULT true | — | contexto-transversal §1 #7 |

### E15 — Contratacion
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_contratacion | INTEGER | Sí | PK | PK | [INFERIDO] |
| tipo | VARCHAR(30) | Sí | ('plan_adquisiciones','contrato') | — | contexto-transversal §1 #225 |
| objeto | TEXT | Sí | — | — | contexto-transversal §1 #225 |
| monto | DECIMAL(18,2) | No | — | — | contexto-transversal §1 #225 |
| estado_ejecucion | VARCHAR(50) | No | — | — | contexto-transversal §1 #225 |
| enlace_secop | VARCHAR(500) | No | URL en SECOP I/II | — | contexto-transversal §2 #225 |

### E16 — DirectorioInstitucional (Dependencia)
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_dependencia | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre_dependencia | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #239,#225 |
| responsable | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| cargo | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| correo | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| telefono | VARCHAR(20) | No | — | — | contexto-transversal §1 #239 |
| codigo_sigep | VARCHAR(50) | No | — | — | contexto-transversal §1 #225 |
| id_sede | INTEGER | No | FK → SedeElectronica | FK | [INFERIDO] |

### E17 — InformacionTributaria
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_impuesto | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre_impuesto | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #239 |
| sujeto_activo | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #239 |
| sujeto_pasivo | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #239 |
| hecho_generador | TEXT | No | — | — | contexto-transversal §1 #239 |
| base_gravable | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| tarifa | VARCHAR(100) | No | — | — | contexto-transversal §1 #239 |
| liquidacion | TEXT | No | — | — | contexto-transversal §1 #239 |

### E18 — Dataset
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_dataset | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre | VARCHAR(300) | Sí | — | — | contexto-transversal §1 #8 |
| descripcion | TEXT | No | — | — | contexto-transversal §1 #8 |
| categoria | VARCHAR(100) | No | — | — | contexto-transversal §1 #8 |
| publicador | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #242 |
| fecha_publicacion | DATE | No | — | — | contexto-transversal §1 #8 |
| fecha_actualizacion | DATE | No | — | — | contexto-transversal §1 #8 |
| formato | VARCHAR(50) | No | ('CSV','JSON','XLS','XML','PDF','otro') | — | contexto-transversal §1 #8 |
| licencia | VARCHAR(100) | No | — | — | contexto-transversal §1 #8 |
| url_datos | VARCHAR(500) | No | — | — | contexto-transversal §1 #8 |
| criticidad | VARCHAR(20) | No | ('alta','media','baja') | — | contexto-transversal §1 #242 |
| version | INTEGER | Sí | DEFAULT 1 | — | contexto-transversal §1 [INFERIDO desde HU-11-D01] |

### E19 — CanalAtencion
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_canal | INTEGER | Sí | PK | PK | [INFERIDO] |
| canal | VARCHAR(50) | Sí | ('presencial','telefono','web','correo','chat','app') | — | contexto-transversal §1 #239 |
| direccion | VARCHAR(300) | No | — | — | contexto-transversal §1 #239 |
| codigo_postal | VARCHAR(10) | No | — | — | contexto-transversal §1 #239 |
| horario | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| telefono | VARCHAR(20) | No | Formato +57XXXXXXXXXX | — | contexto-transversal §1 #239 |
| correo | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| id_dependencia | INTEGER | No | FK → DirectorioInstitucional | FK | contexto-transversal §1 #239 [INFERIDO] |

### E20 — Cita
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_cita | INTEGER | Sí | PK | PK | [INFERIDO] |
| id_dependencia | INTEGER | Sí | FK → DirectorioInstitucional | FK | contexto-transversal §1 #225 |
| id_ciudadano | INTEGER | No | FK → Ciudadano; NULL si sin cuenta | FK | contexto-transversal §1 #225 |
| fecha_cita | DATE | Sí | — | — | contexto-transversal §1 #225 |
| hora_inicio | TIME | Sí | — | — | contexto-transversal §1 #225 |
| hora_fin | TIME | No | — | — | [INFERIDO] |
| contacto | VARCHAR(200) | No | — | — | contexto-transversal §1 #225 |
| codigo_confirmacion | VARCHAR(50) | Sí | UNIQUE | UNIQUE | contexto-transversal §1 #225 |
| estado | VARCHAR(30) | Sí | ('reservada','confirmada','cancelada','no_show','reprogramada') | — | contexto-transversal §1 #225; contexto-transversal §11.2 HU-06-D04 |

### E21 — AuditoriaITA
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_auditoria_ita | INTEGER | Sí | PK | PK | [INFERIDO] |
| modulo | VARCHAR(100) | Sí | — | — | contexto-transversal §1 #239 |
| norma | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #239 |
| items_cumplidos | INTEGER | Sí | — | — | contexto-transversal §1 #239 |
| items_total | INTEGER | Sí | — | — | contexto-transversal §1 #239 |
| puntuacion | DECIMAL(5,2) | No | — | — | contexto-transversal §1 #239 |
| avance_pct | DECIMAL(5,2) | No | CHECK (0–100) | — | contexto-transversal §1 #239 |
| estado | VARCHAR(50) | No | — | — | contexto-transversal §1 #239 |
| responsable | VARCHAR(200) | No | — | — | contexto-transversal §1 #239 |
| fecha_calculo | TIMESTAMP WITH TIME ZONE | Sí | Autogenerado al publicar contenido | — | contexto-transversal §8 #2 (RF-B1-078) |

### E22 — LogAuditoria
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_log | INTEGER | Sí | PK | PK | [INFERIDO] |
| evento | VARCHAR(200) | Sí | — | — | contexto-transversal §1 #156 |
| actor_tipo | VARCHAR(30) | Sí | ('ciudadano','funcionario','sistema') | — | contexto-transversal §1 #156 [INFERIDO] |
| actor_id | INTEGER | No | ID del actor (ciudadano o funcionario) | — | contexto-transversal §1 #156 [INFERIDO] |
| timestamp_hora_legal | TIMESTAMP WITH TIME ZONE | Sí | Hora Legal Colombia (INM); TSA RFC 3161 | — | contexto-transversal §10 RN-TX-D02 |
| ip | VARCHAR(45) | No | IPv4 o IPv6 | — | contexto-transversal §1 #156 |
| version_politica | VARCHAR(20) | No | — | — | contexto-transversal §1 #122 |
| hash_evento | VARCHAR(64) | Sí | SHA-256; append-only | — | contexto-transversal §11.1 HU-B2-008 (RNF-09-D01) |
| modulo_origen | VARCHAR(50) | No | — | — | [INFERIDO] |

### E23 — EvaluacionSUS
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_evaluacion | INTEGER | Sí | PK | PK | [INFERIDO] |
| fecha | DATE | Sí | — | — | contexto-transversal §1 #190 |
| item_1 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_2 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_3 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_4 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_5 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_6 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_7 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_8 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_9 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| item_10 | SMALLINT | Sí | CHECK (1–5 Likert) | — | contexto-transversal §1 #190 |
| puntaje_sus | DECIMAL(5,2) | Sí | Calculado; benchmark ≥ 68 | — | contexto-transversal §1 #190; §8 #14 |
| tasa_exito_tareas | DECIMAL(5,2) | No | CHECK (0–100); umbral ≥ 90% | — | contexto-transversal §8 #14 (RF-08-D01/RN-08-D01) |

### E24 — Arquetipo
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_arquetipo | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre | VARCHAR(100) | Sí | — | — | contexto-transversal §1 #60 |
| edad | SMALLINT | No | — | — | contexto-transversal §1 #60 |
| ubicacion | VARCHAR(200) | No | — | — | contexto-transversal §1 #60 |
| nivel_digital | VARCHAR(30) | No | ('bajo','medio','alto') | — | contexto-transversal §1 #182 |
| necesidades | TEXT | No | — | — | contexto-transversal §1 #60 |
| frustraciones | TEXT | No | — | — | contexto-transversal §1 #60 |

### E25 — PlanIntegracion
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_plan | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre_plan | VARCHAR(300) | Sí | — | — | contexto-transversal §1 #181 |
| tramites_incluidos | JSONB | No | IDs de trámites | — | contexto-transversal §1 #181 |
| dominios_web | JSONB | No | — | — | contexto-transversal §1 #181 |
| otros_medios | TEXT | No | apps, chatbots, PQR | — | contexto-transversal §1 #181 |
| estado | VARCHAR(30) | No | ('borrador','aprobado','en_ejecucion') | — | [INFERIDO] |

### E26 — SistemaExterno (Integración)
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_sistema | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre | VARCHAR(100) | Sí | UNIQUE | UNIQUE | contexto-transversal §2 |
| proposito | TEXT | Sí | — | — | contexto-transversal §2 |
| modulos_relacionados | VARCHAR(200) | No | — | — | contexto-transversal §2 |
| protocolo | VARCHAR(50) | No | ('REST','SOAP','X-Road','OIDC','SECOP','otro') | — | contexto-transversal §2 |
| estado_integracion | VARCHAR(30) | Sí | ('no_integrado','en_proceso','integrado') | — | contexto-transversal §8 #9 |

### E27 — Micrositio
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_micrositio | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre | VARCHAR(200) | Sí | — | — | solicitud-inventario-micrositios §2 |
| url | VARCHAR(500) | No | — | — | solicitud-inventario-micrositios §2 |
| tipo | VARCHAR(50) | No | ('portal','micrositio','app','sistema') | — | solicitud-inventario-micrositios §3 |
| id_dependencia_duena | INTEGER | No | FK → DirectorioInstitucional | FK | solicitud-inventario-micrositios §3 |
| proveedor_tecnologia | VARCHAR(200) | No | — | — | solicitud-inventario-micrositios §3 |
| hosting | VARCHAR(100) | No | ('DigitalOcean','Cloudflare','otro') | — | solicitud-inventario-micrositios §3 |
| tiene_login | BOOLEAN | Sí | DEFAULT false | — | solicitud-inventario-micrositios §3 |
| maneja_pagos | BOOLEAN | Sí | DEFAULT false | — | solicitud-inventario-micrositios §3 |
| maneja_datos_personales | BOOLEAN | Sí | DEFAULT false | — | solicitud-inventario-micrositios §3 |
| accion_propuesta | VARCHAR(30) | No | ('converger','enlazar','retirar') | — | solicitud-inventario-micrositios §3 |
| estado_detectado | VARCHAR(50) | No | — | — | solicitud-inventario-micrositios §2 |

### E28 — Rol
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_rol | INTEGER | Sí | PK | PK | [INFERIDO] |
| nombre_rol | VARCHAR(50) | Sí | UNIQUE | UNIQUE | README §7; contexto-transversal §11.3 |
| descripcion | VARCHAR(300) | No | — | — | README §7 |
| ambito | VARCHAR(20) | Sí | ('ciudadano','interno','externo') | — | README §7; contexto-transversal §11.3 [INFERIDO] |
| requiere_mfa | BOOLEAN | Sí | DEFAULT false; true para roles internos CMS | — | contexto-transversal §7 C-07 |

### E29 — CalendarioHabil
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_calendario | INTEGER | Sí | PK | PK | [INFERIDO] |
| fecha | DATE | Sí | UNIQUE | UNIQUE | contexto-transversal §10 RN-TX-D01 |
| es_habil | BOOLEAN | Sí | — | — | contexto-transversal §10 RN-TX-D01 |
| motivo_no_habil | VARCHAR(100) | No | ('sabado','domingo','festivo_nacional','festivo_distrital') | — | contexto-transversal §10 RN-TX-D01 |
| anio | SMALLINT | Sí | [INFERIDO] para particionado | — | [INFERIDO] |

### E30 — Consentimiento
| Campo | Tipo | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|------|-------------|----------------------|-------|--------|
| id_consentimiento | INTEGER | Sí | PK | PK | [INFERIDO] |
| id_ciudadano | INTEGER | Sí | FK → Ciudadano | FK | contexto-transversal §1 #122 |
| version_politica | VARCHAR(20) | Sí | — | — | contexto-transversal §1 #122 |
| timestamp_hora_legal | TIMESTAMP WITH TIME ZONE | Sí | Hora Legal Colombia (INM); TSA RFC 3161 | — | contexto-transversal §10 RN-TX-D02 |
| ip | VARCHAR(45) | No | — | — | contexto-transversal §1 #156 |
| hash_log | VARCHAR(64) | Sí | SHA-256 | — | contexto-transversal §1 #156 |
| canal_aceptacion | VARCHAR(50) | No | ('web','app','presencial') | — | [INFERIDO] |

---

## 3. Reglas de negocio transversales con impacto en datos

| ID | Regla | Impacto en datos | Fuente |
|----|-------|-----------------|--------|
| RN-TX-D01 | Calendario hábil único del Distrito: todos los cómputos de plazos legales usan CalendarioHabil como única fuente de verdad; excluye sábados, domingos y festivos nacionales/distritales | Toda FK o campo de vencimiento que depende de días hábiles referencia CalendarioHabil.fecha | contexto-transversal §10; Ley 1755/2015; Ley 1437/2011 |
| RN-TX-D02 | Sellado temporal con Hora Legal Colombia (INM): toda evidencia con valor legal usa timestamp de Hora Legal + TSA RFC 3161; no se admiten timestamps de reloj local | Campos TIMESTAMP WITH TIME ZONE en Solicitud, LogAuditoria, Consentimiento, ResultadoTramite, MensajeXRoad deben sellarse con TSA GSE | contexto-transversal §10; RFC 3161; Decreto 4175/2011 |
| RN-TX-D03 | Retención y purga de datos personales por categoría (TRD + Ley 1581 minimización): vencido el período de retención se purga o anonimiza; períodos concretos por categoría pendientes del Oficial de Protección de Datos | Requiere campo fecha_expiracion_retencion o tabla de políticas de retención por tipo de entidad | contexto-transversal §10; Ley 1581/2012 Art.4; Ley 594/2000 |
| RN-TX-D04 | Nivel de autenticación mínimo por trámite: el sistema bloquea el inicio si nivel del ciudadano < nivel requerido; matriz trámite ↔ nivel construida con G-CIO + AND | Tramite.nivel_autenticacion_minimo vs Ciudadano.nivel_autenticacion; validación antes de crear Solicitud | contexto-transversal §10 C-08; Decreto 620/2020 |
| RN-TX-D05 | Fallback al castellano en contenidos traducidos: DIFERIDO (Could); cuando se active, contenido sin traducción muestra castellano con marcador | Impacta tabla de contenidos multilingüe (futura) | contexto-transversal §10; Ley 1712/2014 |
| RN-03-D08 | Silencio administrativo negativo por defecto (CPACA Art.83); SAP solo por norma especial por trámite | Tramite.tipo_silencio_administrativo distingue cada caso | contexto-transversal §8 #19; CPACA Art.83 |
| RN-04-D06 | Identidad reservada en PQRSD: funcionario de back-office no puede ver datos del peticionario si queja es anónima/reservada | PQRSD.es_anonima / identidad_reservada controlan visibilidad; se requiere enmascaramiento en capa de aplicación | contexto-transversal §11.1 HU-B1-012 |
| RN-09-D02 | Segregación de Obligaciones (SoD): quien crea un contenido no puede aprobarlo; quien solicita una eliminación no puede aprobarla | Flujo editorial requiere actor_creador ≠ actor_aprobador en estados de la Solicitud/Contenido | contexto-transversal §11.1 HU-B1-011; HU-B1-021 |
| RN | Número de radicado atómico: prefijo dependencia + año + consecutivo asignado atómicamente para garantizar unicidad bajo concurrencia | Solicitud.numero_radicado; unicidad garantizada por secuencia PostgreSQL o mecanismo atómico | contexto-transversal §8 #11 (RNF-04-D01) |
| RN | Borrador de trámite expira en 30 días: vencido el plazo el borrador se purga/anonimiza | Solicitud.fecha_expiracion_borrador | contexto-transversal §8 #10 (RNF-03-D02) |
| RN | Reembolso solo si el trámite NO inició la gestión: una vez iniciada, el derecho/tasa no se reembolsa | Estado de Solicitud debe distinguir 'iniciada_gestion' como punto de no-retorno para pagos | contexto-transversal §8 #18 (UC-043) |
| RN | Log de auditoría append-only: nadie puede modificar un registro de log existente | LogAuditoria.hash_evento + restricción de no-UPDATE/DELETE vía RULE o trigger en PostgreSQL | contexto-transversal §11.1 HU-B2-008 (RNF-09-D01) |
| RN | Disponibilidad ≥98% para trámites/OPA; ≥95% para sede/portal; resolución: ≥98% (Anexo 1 prevalente) | No impacta tablas directamente pero fija el SLA del monitoreo (RNF-TX-D01) | contexto-transversal §7 C-02; README §8 |
| RN | MFA obligatorio para todos los roles internos del CMS (editor, aprobador, administrador, seguridad) | Rol.requiere_mfa = true para ambito = 'interno' | contexto-transversal §7 C-07 |
| RN | SUIT: actualizar ficha de trámite ≤ 3 días hábiles tras acto administrativo | Tramite.id_suit + fecha de última sincronización SUIT | README §9 |
| RN | Reporte de incidente grave al CSIRT ≤ 24 horas | LogAuditoria + sistema de alertas | README §9 |
| RN | Adjuntos PQRSD: no rechazar por tipo/tamaño/cantidad; aplicar antivirus + límite técnico de servidor alto documentado; diferente a trámites que sí validan MIME, tamaño y antivirus | Metadatos de adjuntos en Solicitud/PQRSD deben registrar resultado del antivirus, no tipo/tamaño como criterio de rechazo | contexto-transversal §7 C-06 |

---

## 4. Cardinalidades y relaciones entre entidades núcleo

| Relación | Cardinalidad | Notas | Fuente |
|----------|-------------|-------|--------|
| Ciudadano — Solicitud | 1:N (un ciudadano puede tener muchas solicitudes) | Solicitud puede ser anónima (id_ciudadano NULL) | contexto-transversal §1 #128 |
| Tramite — Solicitud | 1:N (un trámite origina muchas solicitudes) | FK Solicitud.id_tramite → Tramite | contexto-transversal §1 #128 |
| Solicitud — PQRSD | 1:1 (una PQRSD tiene exactamente un radicado) | FK PQRSD.id_radicado → Solicitud | contexto-transversal §1 #128,#239 |
| Solicitud — ResultadoTramite | 1:0..N (una solicitud puede generar 0 o más resultados) | FK ResultadoTramite.id_solicitud → Solicitud | contexto-transversal §1 #128 |
| Solicitud — ExpedienteElectronico | 1:0..1 (cada solicitud puede tener un expediente) | FK ExpedienteElectronico.id_solicitud → Solicitud | contexto-transversal §1 #131 |
| Ciudadano — Consentimiento | 1:N (un ciudadano puede registrar múltiples versiones de consentimiento) | FK Consentimiento.id_ciudadano → Ciudadano | contexto-transversal §1 #122 |
| Ciudadano — CarpetaCiudadana | 1:N (un ciudadano tiene mensajes en su carpeta) | FK CarpetaCiudadana.id_ciudadano → Ciudadano | contexto-transversal §1 #124 |
| Ciudadano — TokenOIDC | 1:N (un ciudadano puede tener varios tokens activos) | FK TokenOIDC.id_ciudadano → Ciudadano | contexto-transversal §1 #124 |
| Ciudadano — DatoPersonalSensible | 1:N | FK DatoPersonalSensible.id_ciudadano → Ciudadano | contexto-transversal §1 #122 |
| Ciudadano — Cita | 1:N | FK Cita.id_ciudadano → Ciudadano (nullable) | contexto-transversal §1 #225 |
| DirectorioInstitucional — Tramite | 1:N (una dependencia gestiona muchos trámites) | FK Tramite.id_dependencia → DirectorioInstitucional | contexto-transversal §1 #128 |
| DirectorioInstitucional — CanalAtencion | 1:N | FK CanalAtencion.id_dependencia → DirectorioInstitucional | contexto-transversal §1 #239 |
| DirectorioInstitucional — Cita | 1:N | FK Cita.id_dependencia → DirectorioInstitucional | contexto-transversal §1 #225 |
| DirectorioInstitucional — PQRSD | M:N via traslado (una PQRSD puede trasladarse entre dependencias) | PQRSD.id_dependencia + PQRSD.traslado_dependencia | contexto-transversal §1 #239 |
| ExpedienteElectronico — ResultadoTramite | 1:N | FK ResultadoTramite.id_expediente → ExpedienteElectronico | contexto-transversal §1 #131 |
| MensajeXRoad — LogAuditoria | N:1 (cada mensaje puede registrar un log) | FK MensajeXRoad.log_referencia → LogAuditoria | contexto-transversal §1 #124 |
| Tramite — SistemaExterno (SUIT) | N:1 (muchos trámites se registran en SUIT) | Tramite.id_suit como referencia externa | contexto-transversal §2 #241 |
| Micrositio — DirectorioInstitucional | N:1 | FK Micrositio.id_dependencia_duena → DirectorioInstitucional | solicitud-inventario-micrositios §3 |
| Ciudadano — Rol | M:N (un ciudadano puede tener múltiples roles; un rol puede asignarse a múltiples usuarios) | Tabla puente CiudadanoRol [INFERIDO] | README §7; contexto-transversal §11.3 |

---

## 5. Jerarquías ISA / Polimorfismo

### 5.1 Jerarquía ISA — Actor (supertipo Usuario/Persona)

**Supertipo:** `Ciudadano` (E05) — toda persona que interactúa con la sede, identificada o no.

**Subtipos** (especialización parcial, overlapping en algunos casos):

| Subtipo | Discriminador | Atributos diferenciales | Restricción | Fuente |
|---------|--------------|------------------------|-------------|--------|
| `PersonaNatural` | tipo_documento IN ('CC','CE','TI','PEP') | nombre_completo, biometria | — | contexto-transversal §1 #122 |
| `PersonaJuridica` | tipo_documento = 'NIT' | razon_social, representante_legal | — | README §6 |
| `CiudadanoAnonimo` | id_ciudadano NULL en Solicitud/PQRSD | sin identificacion | Solo para trámites de bajo nivel de autenticación | contexto-transversal §11.3; contexto-transversal §1 #239 |

**Estrategia de mapeo recomendada:** Tabla por subtipo (supertype-subtype): `Ciudadano` como supertipo con PK compartida; `PersonaNatural` y `PersonaJuridica` con tablas propias que comparten PK con `Ciudadano`. El anónimo se modela como Solicitud sin FK a Ciudadano. Justificación: la integridad referencial sobre `Ciudadano` es mandatoria para todos los módulos; los atributos diferenciales son suficientes para justificar tablas separadas sin costo prohibitivo de JOIN.

**Restricción de disyunción:** PersonaNatural y PersonaJuridica son disjuntas (un tipo_documento no puede ser CC y NIT simultáneamente). Completitud: parcial (un anónimo no tiene fila en Ciudadano).

### 5.2 Jerarquía ISA — Rol de Actor interno

**Subtipos** identificados explícitamente en la documentación:

| Subtipo rol | Módulo | Fuente |
|-------------|--------|--------|
| `Administrador / CMS` | 12 | README §7 |
| `Editor` | 12 | contexto-transversal §11.3 |
| `Aprobador` | 12 | contexto-transversal §11.3; SoD |
| `Funcionario de dependencia (back-office)` | 03, 04 | contexto-transversal §11.3 |
| `Oficial de seguridad / auditor` | 09 | contexto-transversal §11.3 |
| `Equipo técnico (interoperabilidad)` | 10 | contexto-transversal §11.3 |
| `Oficial de cumplimiento (ITA)` | 12 | contexto-transversal §11.3 |
| `Representante legal de menor` | 12 | contexto-transversal §11.3 |
| `G-CIO / Director TI` | Transversal | README §7 |
| `Oficial de Protección de Datos` | 09 | README §7 |

**Estrategia de mapeo:** tabla única por jerarquía (`Rol`) con discriminador `nombre_rol` y campo `ambito`. No se justifican tablas por subtipo porque los atributos diferenciales son mínimos (requiere_mfa cubre la principal diferencia funcional).

### 5.3 Polimorfismo relacional — Adjunto / Documento

**Problema:** tanto Solicitud como PQRSD y ExpedienteElectronico pueden tener adjuntos/documentos asociados.

**Modelado propuesto:** supertipo común `Documento` como entidad con PK propia; las relaciones Solicitud, PQRSD y ExpedienteElectronico referencian `Documento` mediante FK directas (no arco exclusivo genérico sin integridad). Esto evita el antipatrón `(entity_type, entity_id)` sin FK declarativa.

| Campo Documento | Tipo | Fuente |
|----------------|------|--------|
| id_documento | INTEGER PK | [INFERIDO] |
| nombre_archivo | VARCHAR(300) | contexto-transversal §1 #239 |
| mime_type | VARCHAR(100) | contexto-transversal §7 C-06 |
| tamano_bytes | BIGINT | contexto-transversal §7 C-01 |
| resultado_antivirus | VARCHAR(20) | contexto-transversal §7 C-06 |
| hash_integridad | VARCHAR(64) | contexto-transversal §1 #217 (meta_integridad) |
| fecha_carga | TIMESTAMP WITH TIME ZONE | [INFERIDO] |
| id_solicitud | INTEGER FK nullable | contexto-transversal §1 #128 |
| id_pqrsd | INTEGER FK nullable | contexto-transversal §1 #239 |
| id_expediente | INTEGER FK nullable | contexto-transversal §1 #217 |

Restricción: CHECK exactamente una FK no-NULL (arco exclusivo con CHECK o constraint de aplicación). [AMBIGUO]: si un documento puede pertenecer simultáneamente a solicitud y expediente (probable, dado que el expediente agrupa documentos de solicitudes); se recomienda resolver en el diseño detallado del módulo 12.

---

## 6. Normativa citada con impacto directo en el modelo de datos

| Norma | Impacto en BD | Fuente |
|-------|--------------|--------|
| Ley 1581/2012 (datos personales) | Ciudadano, DatoPersonalSensible, Consentimiento; principio de minimización; RNBD; bases de datos en nube extranjera requieren declaración SIC | contexto-transversal §1 #122; README §10 |
| Ley 594/2000 (TRD / Archivo) | ExpedienteElectronico.trd_codigo; ciclo vital; política de retención | contexto-transversal §10 RN-TX-D03 |
| Ley 1712/2014 (transparencia) | Normativa, Contratacion, Dataset; publicación obligatoria | README §2; README §10 |
| Ley 1437/2011 (CPACA) | CalendarioHabil; Solicitud.fecha_vencimiento; silencio administrativo | contexto-transversal §10 RN-TX-D01 |
| Ley 1755/2015 (derecho de petición) | Plazos PQRSD calculados sobre CalendarioHabil | contexto-transversal §10 RN-TX-D01 |
| Decreto 620/2020 (SCD) | TokenOIDC, MensajeXRoad, CarpetaCiudadana; niveles de confianza autenticación | contexto-transversal §1 #124; §2 |
| Decreto 088/2022 (digitalización) | Tramite.nivel_transformacion (1-6); plazos may/2028–abr/2037 | contexto-transversal §8 #1; README §9 |
| Decreto 4175/2011 (Hora Legal) | Todo TIMESTAMP WITH TIME ZONE usa Hora Legal INM | contexto-transversal §10 RN-TX-D02 |
| RFC 3161 (TSA) | MensajeXRoad.estampa_tsa; LogAuditoria.timestamp_hora_legal; Consentimiento.timestamp_hora_legal | contexto-transversal §2 #140 |
| WCAG 2.1 AA | Impacta metadatos de contenidos multimedia (alt, subtítulos) verificados por AuditoriaITA | README §10 |
| Resolución MinTIC 1519/2020 (Anexos 1-4) | Disponibilidad ≥98%; seguridad; datos abiertos; accesibilidad | README §10; contexto-transversal §7 C-02 |

---

## 7. Inferencias [INFERIDO]

| # | Inferencia | Justificación | Entidad/campo afectado |
|---|-----------|--------------|----------------------|
| I-01 | Los PKs de todas las entidades son INTEGER autoincrementales | No especificado en la documentación; práctica estándar PostgreSQL 15+ con GENERATED ALWAYS AS IDENTITY | Todas las entidades |
| I-02 | Tabla puente `CiudadanoRol` para la relación M:N Ciudadano ↔ Rol | README §7 lista múltiples roles posibles por actor; contexto §11.3 lista variaciones por rol | E05, E28 |
| I-03 | `Documento` como entidad con supertipo común (arco exclusivo) para adjuntos de Solicitud, PQRSD y ExpedienteElectronico | La documentación menciona adjuntos en los tres contextos sin unificarlos en una entidad | §5.3 |
| I-04 | `valor_cifrado BYTEA` en DatoPersonalSensible | Ley 1581 + MSPI exigen protección de datos sensibles; cifrado en reposo es mandatorio | E06 |
| I-05 | `version INTEGER` en Dataset para versionado | HU-11-D01 menciona versionado/frescura; sin atributo explícito en el catálogo | E18 |
| I-06 | `anio SMALLINT` en CalendarioHabil para particionado declarativo por rango en PostgreSQL | Tabla de alta consulta con volumen predecible y crecimiento anual | E29 |
| I-07 | `hora_fin TIME` en Cita | No mencionada explícitamente pero necesaria para gestión de franjas sin solapamiento (RF-06-D01) | E20 |
| I-08 | `ambito VARCHAR` en Rol distingue ciudadano/interno/externo | Documentación lista roles claramente en tres ámbitos sin formalizar el discriminador | E28 |
| I-09 | DatoPersonalSensible es una especialización de los datos del Ciudadano, no una entidad completamente separada; podría modelarse como subtipo ISA de Ciudadano | La documentación los lista por separado pero conceptualmente forman una jerarquía | E05, E06 |
| I-10 | El traslado de PQRSD entre dependencias implica una tabla de historial de traslados, no solo el campo traslado_dependencia actual | El contexto menciona traslado como proceso, con posibilidad de reasignación múltiple (RF-04-D01) | E04 — vacio confirmado |

---

## 8. Vacíos detectados (información esperada ausente)

| Vacío | Descripción | Fuente donde debería estar |
|-------|-------------|--------------------------|
| V-01 | Períodos concretos de retención por categoría de dato (borrador, cita, log, consentimiento) — pendiente del Oficial de Protección de Datos | contexto-transversal §10 RN-TX-D03 [PREGUNTA ABIERTA] |
| V-02 | Matriz completa trámite ↔ nivel de autenticación mínimo — pendiente G-CIO + AND | contexto-transversal §7 C-08; §10 RN-TX-D04 |
| V-03 | Inventario completo de micrositios/portales/apps del Distrito — respuesta Dirección TIC pendiente | solicitud-inventario-micrositios §3; contexto-transversal §8 #8 |
| V-04 | Estructura interna de los estados del ciclo de vida de Solicitud/PQRSD (máquina de estados completa) | contexto-transversal §1 #128 menciona estados pero no los enumera todos |
| V-05 | Tabla de historial de traslados de PQRSD (auditoría de reasignaciones entre dependencias) | contexto-transversal §8 #6; RF-04-D01 |
| V-06 | Catálogo de normas habilitantes de Silencio Administrativo Positivo por trámite | contexto-transversal §8 #19 — pendiente Secretaría Jurídica |
| V-07 | Estructura de los 4 endpoints REST de la CarpetaCiudadana Digital (información usuario, alertas, historial trámites, historial solicitudes) y su mapeo a columnas | contexto-transversal §2 #124 menciona 4 GET pero sin schema |
| V-08 | Schema de los campos `campoDato/valorDato` de CarpetaCiudadana — pares clave-valor sin estructura definida | contexto-transversal §1 #124 |
| V-09 | Confirmación oficial del tier Decreto 088 (Alcaldía-Avanzado adoptado como supuesto; no publicado en datos.gov.co) | README §4; contexto-transversal §8 #1 |
| V-10 | Nombre y correo institucional del Director de TIC | borrador-solicitud-AND §notas |
