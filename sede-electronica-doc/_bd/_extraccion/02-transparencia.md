# Extracción BD — Módulo 02 Transparencia

## 0. Cobertura

- Unidad leída íntegra: **SÍ**
- Archivo: `/var/www/proyect-doc/elicitacion/sede-electronica/02-transparencia/transparencia.md`
- Líneas totales: **160**
- Líneas leídas: **1–160**
- Omitidas: **0**

---

## 1. Entidades candidatas

| # | Nombre entidad | Descripción | ¿CANDIDATA-COMPARTIDA? | Fuente (sección/línea) |
|---|----------------|-------------|------------------------|------------------------|
| 1 | `transparencia_documento` | Supertipo abstracto para todo documento publicado en el módulo; portador de los atributos transversales: orden cronológico inverso, formato abierto, fuente única, versión | NO — supertipo interno al módulo | §2.1 L.19; §7 L.141–144; RN-02-D01 L.105 |
| 2 | `normativa` | Documento normativo publicado proactivamente: tipo, número, fechas, epígrafe, vigencia, descarga, enlace SUIN | NO | §2.3 L.35–36; §7 L.141; UC-B1-005 L.114 |
| 3 | `proyecto_norma` | Norma en elaboración con fecha máxima de comentarios y canal SUCOP | NO | §2.3 L.35; RN-B1-005 L.93 |
| 4 | `contrato` | Contrato adjudicado con objeto, monto, ejecución, pagos, otrosíes, enlace SECOP | NO | §2.4 L.42; §7 L.142; HU-B1-006 L.121 |
| 5 | `plan_adquisiciones` | Plan anual de adquisiciones; un registro por vigencia fiscal | NO | §2.4 L.42; §7 L.142 |
| 6 | `plan_accion` | Plan de Acción publicado antes del 31-ene; un registro por vigencia fiscal | NO | §2.5 L.48; RN-B1-003 L.91 |
| 7 | `informe_gestion` | Informe de gestión anual; publicado antes del 31-ene | NO | §2.5 L.49; RN-B1-003 L.91 |
| 8 | `informe_pqrsd` | Informe trimestral de PQRSD y acceso a la información | NO | §2.5 L.50; RN-B1-004 L.92 |
| 9 | `informe_control_interno` | Informe semestral de control interno | NO | §2.5 L.51; RN-B3-028 L.97 |
| 10 | `avance_proyecto_inversion` | Avance trimestral de proyectos de inversión e indicadores | NO | §2.5 L.48; RN-B1-004 L.92 |
| 11 | `servidor_publico` | Servidor público del directorio institucional (fuente: SIGEP) | **CANDIDATA-COMPARTIDA** (módulos RRHH, autenticación, etc.) | §2.2 L.28; §7 L.143; RN-B3-022 L.95 |
| 12 | `dependencia` | Unidad organizacional de la entidad (organigrama) | **CANDIDATA-COMPARTIDA** (módulos de estructura orgánica) | §2.2 L.27–28; §7 L.143 |
| 13 | `impuesto` | Tipo de impuesto distrital (predial, ICA, retenciones…) con sus elementos tributarios | NO | §2.6 L.57; §7 L.144; RN-B3-025 L.96; HU-B1-007 L.122 |
| 14 | `calendario_tributario` | Fechas de vencimiento de cada impuesto por vigencia fiscal | NO | §2.6 L.58; RF-B3-151 L.58; HU-B3-025 L.126 |
| 15 | `grupo_interes` | Categoría de público objetivo (ciudadanía, proveedores, medios, gremios) con su contenido asociado | NO | §2.2 L.29; RF-B1-096 L.29 |
| 16 | `version_documento` | Versión histórica de cualquier documento publicado; preserva la versión anterior sin romper URL | NO — subtipo de auditoria interna | RF-02-D01 L.68; RN-02-D01 L.105; HU-02-D01 L.134 |
| 17 | `alerta_cumplimiento` | Registro de alerta disparada N días antes de un plazo legal de publicación obligatoria | NO | RF-02-D02 L.69; RN-02-D02 L.106; HU-02-D02 L.135 |
| 18 | `log_integracion` | Registro de fallos/éxitos de integraciones externas (SECOP, SIGEP, SUIN, SUCOP, KOGUI) | NO | RF-02-D03 L.70; RN-02-D03 L.107 |
| 19 | `sincronizacion_sigep` | Registro de cada intento de sincronización con SIGEP (fecha/hora, resultado, estado) | NO | RNF-02-D01 L.84; RN-02-D04 L.108; HU-02-D04 L.137 |
| 20 | `agenda_regulatoria` | Listado de proyectos normativos programados para el período | NO | §2.3 RF-B1-014 L.36 |

---

## 2. Atributos/campos por entidad

> Regla aplicada: cada subsección del esquema de publicación con campos propios es entidad propia. No se colapsan en un genérico.

### 2.1 `normativa`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_normativa` | INTEGER | SÍ | secuencial | PK | §7 L.141; UC-B1-005 L.114 |
| `tipo` | VARCHAR(50) | SÍ | ej. Decreto, Resolución, Acuerdo, Circular | — | §2.3 L.35; §7 L.141 |
| `numero` | VARCHAR(30) | SÍ | número oficial de la norma | — | §2.3 L.35; §7 L.141 |
| `fecha_expedicion` | DATE | SÍ | fecha en que la autoridad la expidió | — | §2.3 L.35; §7 L.141 |
| `fecha_publicacion` | DATE | SÍ | fecha de publicación en la sede; ≤24 h tras expedición (RN-B1-005) | — | §2.3 L.35; RN-B1-005 L.93 |
| `epigrafe` | TEXT | SÍ | descripción/título de la norma | — | §2.3 L.35; §7 L.141 |
| `vigencia` | BOOLEAN o VARCHAR(20) | SÍ | vigente / derogada / suspendida | — | §2.3 L.35; §7 L.141 |
| `url_descarga` | VARCHAR(500) | SÍ | enlace de descarga en formato abierto | — | §2.3 L.35; RF-B1-013 L.35 |
| `url_suin` | VARCHAR(500) | NO | enlace al SUIN cuando exista | — | §2.3 L.36; RF-B1-014 L.36 |
| `formato_archivo` | VARCHAR(20) | SÍ | CSV/XML/RDF/JSON/ODF/PDF; CHECK formatos abiertos | — | RNF-B3-039 L.78 |
| `es_proyecto_norma` | BOOLEAN | SÍ | distingue norma vigente de proyecto en elaboración | — | §2.3 L.35; RF-B1-013 L.35 |
| `fecha_limite_comentarios` | DATE | COND. | obligatorio si `es_proyecto_norma = TRUE` | — | §2.3 L.35; RN-B1-005 L.93 |
| `id_agenda_regulatoria` | INTEGER | NO | FK cuando provenga de agenda regulatoria | FK → `agenda_regulatoria` | RF-B1-014 L.36 |
| `publicado_por` | INTEGER | SÍ | FK al administrador CMS que publicó | FK → `servidor_publico` o tabla usuario | UC-B1-006 L.115 |
| `fecha_creacion_registro` | TIMESTAMP | SÍ | [INFERIDO] auditoría; motiva log UC-B1-006 | — | UC-B1-006 L.115 |

### 2.2 `proyecto_norma`

> Subtipo especializado de normativa en elaboración; modela los campos exclusivos de normas en proceso de consulta ciudadana vía SUCOP.

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_proyecto_norma` | INTEGER | SÍ | PK | PK | §2.3 L.35 |
| `id_normativa` | INTEGER | NO | FK; si la norma es aprobada, se vincula al registro definitivo | FK → `normativa` | §2.3 L.35 |
| `titulo` | VARCHAR(300) | SÍ | título del proyecto | — | §2.3 L.35 |
| `fecha_inicio_consulta` | DATE | SÍ | fecha de apertura de comentarios | — | §2.3 L.35 |
| `fecha_limite_comentarios` | DATE | SÍ | fecha máxima de comentarios ciudadanos | — | §2.3 L.35; RN-B1-005 L.93 |
| `url_sucop` | VARCHAR(500) | NO | enlace al proceso en SUCOP | — | §2.3 RF-B1-014 L.36 |
| `estado` | VARCHAR(30) | SÍ | en_consulta / aprobado / archivado | — | §2.3 L.35 |

### 2.3 `contrato`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_contrato` | INTEGER | SÍ | PK | PK | §2.4 L.42; §7 L.142 |
| `numero_contrato` | VARCHAR(50) | SÍ | UNIQUE por vigencia fiscal [INFERIDO] | UNIQUE | §2.4 L.42 |
| `objeto` | TEXT | SÍ | descripción del objeto contractual | — | §2.4 L.42; §7 L.142; HU-B1-006 L.121 |
| `monto` | DECIMAL(18,2) | SÍ | valor total adjudicado | — | §2.4 L.42; §7 L.142 |
| `honorarios` | DECIMAL(18,2) | NO | honorarios cuando aplique | — | §7 L.142 |
| `fecha_inicio` | DATE | SÍ | inicio de ejecución | — | §2.4 L.42; §7 L.142 |
| `fecha_fin` | DATE | SÍ | fin previsto de ejecución | — | §2.4 L.42; §7 L.142 |
| `valor_ejecutado` | DECIMAL(18,2) | NO | valor ejecutado a la fecha | — | §2.4 L.42; §7 L.142 |
| `porcentaje_ejecutado` | DECIMAL(5,2) | NO | CHECK 0.00–100.00 | — | §2.4 L.42; §7 L.142 |
| `pagos_realizados` | DECIMAL(18,2) | NO | suma de pagos efectuados | — | §2.4 L.42; §7 L.142 |
| `pagos_pendientes` | DECIMAL(18,2) | NO | saldo por pagar | — | §2.4 L.42; §7 L.142 |
| `tiene_otrosi` | BOOLEAN | SÍ | indica si existen otrosíes | — | §2.4 L.42; §7 L.142 |
| `url_secop` | VARCHAR(500) | SÍ | enlace directo al proceso en SECOP I o II | — | §2.4 L.42; HU-B3-014 L.124 |
| `tipo_secop` | VARCHAR(10) | SÍ | SECOP_I / SECOP_II | — | §2.4 L.42 |
| `id_plan_adquisiciones` | INTEGER | NO | FK al plan anual que lo origina | FK → `plan_adquisiciones` | §2.4 L.42 |
| `vigencia_fiscal` | INTEGER | SÍ | año fiscal del contrato | — | §2.4 L.42 [INFERIDO] |

### 2.4 `otrosi`

> Entidad separada para los otrosíes de un contrato (son documentos propios con número, fecha, objeto de la modificación).

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_otrosi` | INTEGER | SÍ | PK | PK | §2.4 L.42 ("otrosíes") |
| `id_contrato` | INTEGER | SÍ | FK | FK → `contrato` | §2.4 L.42 |
| `numero_otrosi` | VARCHAR(20) | SÍ | número secuencial dentro del contrato | — | §2.4 L.42 |
| `fecha_firma` | DATE | SÍ | fecha de firma del otrosí | — | §2.4 L.42 |
| `objeto_modificacion` | TEXT | SÍ | descripción de la modificación | — | §2.4 L.42 |
| `nuevo_valor` | DECIMAL(18,2) | NO | valor resultante si hubo modificación de monto | — | §2.4 L.42 [INFERIDO] |
| `nueva_fecha_fin` | DATE | NO | fecha fin si hubo prórroga | — | §2.4 L.42 [INFERIDO] |

### 2.5 `plan_adquisiciones`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_plan_adquisiciones` | INTEGER | SÍ | PK | PK | §2.4 L.42 |
| `vigencia_fiscal` | INTEGER | SÍ | UNIQUE; año de la vigencia | UNIQUE | §2.4 L.42 |
| `fecha_publicacion` | DATE | SÍ | fecha en que se publica en la sede | — | §2.4 L.42 |
| `url_archivo` | VARCHAR(500) | SÍ | enlace de descarga en formato abierto | — | §2.4 L.42 |
| `formato_archivo` | VARCHAR(20) | SÍ | formato abierto (RNF-B3-039) | — | RNF-B3-039 L.78 |

### 2.6 `plan_accion`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_plan_accion` | INTEGER | SÍ | PK | PK | §2.5 L.48; RN-B1-003 L.91 |
| `vigencia_fiscal` | INTEGER | SÍ | UNIQUE | UNIQUE | §2.5 L.48 |
| `fecha_publicacion` | DATE | SÍ | CHECK fecha_publicacion ≤ '31-ene' de vigencia_fiscal | — | RN-B1-003 L.91; HU-B3-015 L.125 |
| `url_archivo` | VARCHAR(500) | SÍ | enlace de descarga en formato abierto | — | §2.5 L.48 |
| `formato_archivo` | VARCHAR(20) | SÍ | formato abierto | — | RNF-B3-039 L.78 |

### 2.7 `informe_gestion`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_informe_gestion` | INTEGER | SÍ | PK | PK | §2.5 L.49; RN-B1-003 L.91 |
| `vigencia_fiscal` | INTEGER | SÍ | UNIQUE | UNIQUE | §2.5 L.49 |
| `fecha_publicacion` | DATE | SÍ | CHECK fecha_publicacion ≤ '31-ene' de vigencia_fiscal+1 | — | RN-B1-003 L.91; RF-B3-084 L.49 |
| `url_archivo` | VARCHAR(500) | SÍ | enlace de descarga en formato abierto | — | §2.5 L.49 |
| `formato_archivo` | VARCHAR(20) | SÍ | formato abierto | — | RNF-B3-039 L.78 |

### 2.8 `informe_pqrsd`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_informe_pqrsd` | INTEGER | SÍ | PK | PK | §2.5 L.50; RN-B1-004 L.92 |
| `trimestre` | INTEGER | SÍ | CHECK 1–4 | — | §2.5 L.50; RN-B1-004 L.92 |
| `vigencia_fiscal` | INTEGER | SÍ | año | — | §2.5 L.50 |
| `cantidad_pqrsd` | INTEGER | SÍ | total de PQRSD del período | — | §2.5 L.50 |
| `tipo_breakdown` | JSONB o tabla relacionada | SÍ | desglose por tipo (P/Q/R/S/D) [INFERIDO de "tipo"] | — | §2.5 L.50 |
| `estado_breakdown` | JSONB o tabla relacionada | SÍ | desglose por estado (resuelto, en trámite…) [INFERIDO de "estado"] | — | §2.5 L.50 |
| `tiempo_promedio_respuesta` | DECIMAL(6,2) | SÍ | días hábiles promedio | — | §2.5 L.50 |
| `url_kogui` | VARCHAR(500) | NO | enlace a defensa pública en KOGUI si aplica | — | §2.5 L.50 |
| `fecha_publicacion` | DATE | SÍ | debe publicarse antes del día 15 del mes siguiente al cierre | — | §2.5 L.50 |
| `url_archivo` | VARCHAR(500) | SÍ | enlace de descarga en formato abierto | — | §2.5 L.50 |
| `formato_archivo` | VARCHAR(20) | SÍ | formato abierto | — | RNF-B3-039 L.78 |

### 2.9 `informe_control_interno`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_informe_control_interno` | INTEGER | SÍ | PK | PK | §2.5 L.51; RN-B3-028 L.97 |
| `semestre` | INTEGER | SÍ | CHECK 1–2 | — | §2.5 L.51; RN-B3-028 L.97 |
| `vigencia_fiscal` | INTEGER | SÍ | año | — | §2.5 L.51 |
| `fecha_publicacion` | DATE | SÍ | dentro de los 5 días hábiles tras cierre del semestre | — | §2.5 L.51; RF-B3-152 L.51 |
| `url_archivo` | VARCHAR(500) | SÍ | enlace de descarga | — | §2.5 L.51 |
| `formato_archivo` | VARCHAR(20) | SÍ | formato abierto | — | RNF-B3-039 L.78 |

### 2.10 `avance_proyecto_inversion`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_avance` | INTEGER | SÍ | PK | PK | §2.5 L.48; RN-B1-004 L.92 |
| `id_proyecto` | INTEGER | SÍ | FK al proyecto de inversión [INFERIDO — módulo de planeación] | FK → `proyecto_inversion` (módulo externo) | §2.5 L.48 |
| `trimestre` | INTEGER | SÍ | CHECK 1–4 | — | §2.5 L.48; RN-B1-004 L.92 |
| `vigencia_fiscal` | INTEGER | SÍ | año | — | §2.5 L.48 |
| `descripcion_avance` | TEXT | SÍ | descripción narrativa del avance | — | §2.5 L.48 |
| `porcentaje_meta_lograda` | DECIMAL(5,2) | SÍ | CHECK 0.00–100.00 | — | §2.5 L.48 |
| `fecha_publicacion` | DATE | SÍ | en los 10 primeros días hábiles del trimestre | — | RN-B1-004 L.92 |
| `url_archivo` | VARCHAR(500) | NO | descarga del informe si hay | — | §2.5 L.48 |

### 2.11 `servidor_publico` (CANDIDATA-COMPARTIDA)

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_servidor` | INTEGER | SÍ | PK | PK | §2.2 L.28; §7 L.143 |
| `codigo_sigep` | VARCHAR(50) | SÍ | UNIQUE; clave en SIGEP | UNIQUE | §7 L.143; RN-B3-022 L.95 |
| `nombre_completo` | VARCHAR(200) | SÍ | nombre y apellidos | — | §2.2 L.28; §7 L.143 |
| `cargo` | VARCHAR(150) | SÍ | cargo en la entidad | — | §2.2 L.28; §7 L.143 |
| `correo_institucional` | VARCHAR(200) | SÍ | UNIQUE; dominio institucional | UNIQUE | §2.2 L.28; §7 L.143 |
| `telefono` | VARCHAR(30) | NO | número de contacto | — | §2.2 L.28; §7 L.143 |
| `extension` | VARCHAR(10) | NO | extensión telefónica | — | §2.2 L.28; §7 L.143 |
| `id_dependencia` | INTEGER | SÍ | FK a dependencia/unidad organizacional | FK → `dependencia` | §2.2 L.28; §7 L.143 |
| `activo` | BOOLEAN | SÍ | estado en la entidad | — | RN-B3-022 L.95 [INFERIDO] |
| `fecha_vinculacion` | DATE | NO | fecha de ingreso [INFERIDO por la regla de actualización en tiempo real] | — | RF-B1-017 L.28 |
| `fecha_desvinculacion` | DATE | NO | fecha de salida; NULL si activo | — | RF-B1-017 L.28 [INFERIDO] |

### 2.12 `dependencia` (CANDIDATA-COMPARTIDA)

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_dependencia` | INTEGER | SÍ | PK | PK | §2.2 L.27; §7 L.143 |
| `nombre` | VARCHAR(200) | SÍ | nombre oficial de la dependencia | — | §2.2 L.27; §7 L.143 |
| `responsable_id` | INTEGER | NO | FK al servidor que la dirige | FK → `servidor_publico` | §2.2 L.27 [INFERIDO] |
| `activa` | BOOLEAN | SÍ | si la dependencia existe actualmente | — | §2.2 L.27 [INFERIDO] |

### 2.13 `impuesto`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_impuesto` | INTEGER | SÍ | PK | PK | §2.6 L.57; §7 L.144; RN-B3-025 L.96 |
| `nombre` | VARCHAR(100) | SÍ | UNIQUE; predial / ICA / retenciones | UNIQUE | §2.6 L.57; §7 L.144 |
| `sujeto_activo` | VARCHAR(200) | SÍ | entidad que cobra el impuesto (ej. Alcaldía Distrital) | — | §2.6 L.57; RN-B3-025 L.96; HU-B1-007 L.122 |
| `sujeto_pasivo` | TEXT | SÍ | descripción del contribuyente obligado | — | §2.6 L.57; RN-B3-025 L.96; HU-B1-007 L.122 |
| `hecho_generador` | TEXT | SÍ | circunstancia que origina la obligación tributaria | — | §2.6 L.57; RN-B3-025 L.96; HU-B1-007 L.122 |
| `hecho_imponible` | TEXT | SÍ | manifestación de la capacidad económica gravada | — | §2.6 L.57; RF-B1-018 L.57 |
| `causacion` | TEXT | SÍ | momento en que se causa la obligación | — | §2.6 L.57; RF-B1-018 L.57 |
| `base_gravable` | TEXT | SÍ | descripción de la base de liquidación | — | §2.6 L.57; RN-B3-025 L.96; HU-B1-007 L.122 |
| `tarifa` | TEXT | SÍ | tarifa(s) aplicable(s); puede ser rango o tabla de tarifas | — | §2.6 L.57; RN-B3-025 L.96; HU-B1-007 L.122 |
| `proceso_recaudo` | TEXT | NO | descripción del proceso de recaudo de rentas locales | — | §2.6 L.57; RF-B1-018 L.57 |
| `url_formulario_liquidacion` | VARCHAR(500) | NO | enlace al formulario de liquidación | — | HU-B1-007 L.122 |
| `vigencia_desde` | INTEGER | SÍ | año fiscal desde el que aplica esta versión | — | §2.6 L.57 [INFERIDO: las tarifas cambian por vigencia] |
| `vigencia_hasta` | INTEGER | NO | año fiscal hasta el que aplica; NULL si vigente | — | §2.6 L.57 [INFERIDO] |

### 2.14 `calendario_tributario`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_calendario` | INTEGER | SÍ | PK | PK | §2.6 L.58; RF-B3-151 L.58 |
| `id_impuesto` | INTEGER | SÍ | FK | FK → `impuesto` | §2.6 L.58 |
| `vigencia_fiscal` | INTEGER | SÍ | año | — | §2.6 L.58 |
| `descripcion_vencimiento` | VARCHAR(300) | SÍ | descripción del plazo (ej. "Primera cuota predial") | — | §2.6 L.58; HU-B3-025 L.126 |
| `fecha_vencimiento` | DATE | SÍ | fecha límite de pago | — | §2.6 L.58; HU-B3-025 L.126 |
| `fecha_publicacion` | DATE | SÍ | fecha en que se publica en la sede | — | RF-B3-151 L.58 |

### 2.15 `grupo_interes`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_grupo` | INTEGER | SÍ | PK | PK | §2.2 L.29; RF-B1-096 L.29 |
| `nombre` | VARCHAR(100) | SÍ | UNIQUE; ciudadanía / proveedores / medios / gremios | UNIQUE | §2.2 L.29 |
| `descripcion` | TEXT | NO | descripción de las necesidades del grupo | — | §2.2 L.29 |
| `contenido_asociado` | TEXT | NO | descripción del contenido específico para este grupo [AMBIGUO: ver §9 A-07] | — | RF-B1-096 L.29 |

### 2.16 `version_documento`

> Implementa RN-02-D01: historial inmutable de cualquier documento publicado sin romper la URL de fuente única.

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_version` | INTEGER | SÍ | PK | PK | RF-02-D01 L.68; RN-02-D01 L.105 |
| `entidad_tipo` | VARCHAR(50) | SÍ | nombre de la entidad dueña (normativa/plan_accion/informe_gestion…) — polimorfismo por tipo | — | RF-02-D01 L.68 |
| `entidad_id` | INTEGER | SÍ | ID del registro en la entidad dueña | — | RF-02-D01 L.68 |
| `numero_version` | INTEGER | SÍ | secuencial dentro del documento; v1, v2… | — | RF-02-D01 L.68; HU-02-D01 L.134 |
| `url_archivo_historico` | VARCHAR(500) | SÍ | URL donde se conserva el archivo de esta versión | — | RF-02-D01 L.68 |
| `fecha_reemplazo` | TIMESTAMP | SÍ | cuándo fue reemplazada por la siguiente versión | — | RF-02-D01 L.68 |
| `publicado_por` | INTEGER | SÍ | FK al usuario que publicó | FK → `servidor_publico` | UC-B1-006 L.115 [INFERIDO] |
| `activa` | BOOLEAN | SÍ | TRUE solo para la versión vigente | — | HU-02-D01 L.134 |

### 2.17 `alerta_cumplimiento`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_alerta` | INTEGER | SÍ | PK | PK | RF-02-D02 L.69; RN-02-D02 L.106 |
| `tipo_publicacion_obligatoria` | VARCHAR(100) | SÍ | plan_accion / informe_gestion / informe_pqrsd / informe_control_interno / avance_inversion | — | RF-02-D02 L.69; RN-02-D02 L.106 |
| `vigencia_fiscal` | INTEGER | SÍ | año al que corresponde la obligación | — | RF-02-D02 L.69 |
| `plazo_legal` | DATE | SÍ | fecha límite según norma | — | RN-02-D02 L.106 |
| `dias_anticipacion` | INTEGER | SÍ | N días antes del plazo en que se disparó la alerta | — | RF-02-D02 L.69; HU-02-D02 L.135 |
| `norma_citada` | VARCHAR(300) | SÍ | referencia legal que origina el plazo | — | RN-02-D02 L.106 |
| `responsable_id` | INTEGER | SÍ | FK al servidor responsable de cumplir | FK → `servidor_publico` | HU-02-D02 L.135 |
| `fecha_generacion` | TIMESTAMP | SÍ | cuándo se generó la alerta | — | RF-02-D02 L.69 |
| `estado` | VARCHAR(30) | SÍ | pendiente / cumplida / incumplida / escalada | — | HU-02-D02 L.135 |
| `fecha_cumplimiento` | TIMESTAMP | NO | fecha en que se publicó el documento (cierra la alerta) | — | HU-02-D02 L.135 |

### 2.18 `log_integracion`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_log` | INTEGER | SÍ | PK | PK | RF-02-D03 L.70; RN-02-D03 L.107 |
| `sistema_externo` | VARCHAR(30) | SÍ | SECOP_I / SECOP_II / SIGEP / SUIN / SUCOP / KOGUI / DATOS_GOV | — | §8 L.148–153; RF-02-D03 L.70 |
| `url_consultada` | VARCHAR(500) | SÍ | URL o endpoint que falló o respondió | — | RF-02-D03 L.70 |
| `timestamp_intento` | TIMESTAMP | SÍ | fecha/hora del intento | — | RF-02-D03 L.70 |
| `resultado` | VARCHAR(20) | SÍ | exito / fallo | — | RF-02-D03 L.70 |
| `codigo_http` | INTEGER | NO | código de respuesta HTTP si aplica | — | RF-02-D03 L.70 [INFERIDO] |
| `mensaje_error` | TEXT | NO | descripción del error cuando falla | — | RF-02-D03 L.70 |
| `mensaje_usuario_mostrado` | TEXT | NO | mensaje mostrado al ciudadano (RN-02-D03) | — | RN-02-D03 L.107; HU-02-D03 L.136 |

### 2.19 `sincronizacion_sigep`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_sync` | INTEGER | SÍ | PK | PK | RNF-02-D01 L.84; RN-02-D04 L.108 |
| `timestamp_inicio` | TIMESTAMP | SÍ | inicio del proceso de sincronización | — | RNF-02-D01 L.84 |
| `timestamp_fin` | TIMESTAMP | NO | fin del proceso; NULL si en curso | — | RNF-02-D01 L.84 |
| `resultado` | VARCHAR(20) | SÍ | exitoso / fallido / parcial | — | RNF-02-D01 L.84; RN-02-D04 L.108 |
| `registros_actualizados` | INTEGER | NO | cantidad de servidores actualizados | — | RNF-02-D01 L.84 [INFERIDO] |
| `mensaje_error` | TEXT | NO | detalle del fallo si lo hubo | — | RN-02-D04 L.108 |
| `alerta_disparada` | BOOLEAN | SÍ | TRUE si esta sync generó alerta por demora >24 h | — | RN-02-D04 L.108; HU-02-D04 L.137 |

### 2.20 `agenda_regulatoria`

| Campo | Tipo implícito | Obligatorio | Dominio / restricción | PK/FK | Fuente |
|-------|---------------|-------------|----------------------|-------|--------|
| `id_agenda` | INTEGER | SÍ | PK | PK | §2.3 RF-B1-014 L.36 |
| `vigencia_fiscal` | INTEGER | SÍ | año al que pertenece la agenda | — | RF-B1-014 L.36 |
| `descripcion` | TEXT | SÍ | descripción de la agenda regulatoria del período | — | RF-B1-014 L.36 |
| `fecha_publicacion` | DATE | SÍ | fecha de publicación en la sede | — | RF-B1-014 L.36 |
| `url_archivo` | VARCHAR(500) | NO | descarga si existe documento | — | RF-B1-014 L.36 |

---

## 3. Reglas de negocio con impacto en datos

| ID RN | Impacto en BD | Implementación sugerida | Fuente |
|-------|--------------|------------------------|--------|
| RN-B1-002 | Todo documento publicado debe estar en formato abierto | CHECK `formato_archivo` IN ('CSV','XML','RDF','JSON','ODF') en todas las tablas con `url_archivo`; RNF-B3-039 exige ≥90% | RNF-B3-039 L.78 |
| RN-B1-003 / RN-B3-020 | `plan_accion.fecha_publicacion` ≤ 31 de enero de `vigencia_fiscal`; `informe_gestion.fecha_publicacion` ≤ 31 de enero de `vigencia_fiscal+1` | CHECK en columnas fecha_publicacion; trigger o alerta vía `alerta_cumplimiento` | §4 L.91 |
| RN-B1-004 / RN-B3-021 | `informe_pqrsd.fecha_publicacion` ≤ día 15 del mes siguiente al cierre del trimestre; `avance_proyecto_inversion.fecha_publicacion` ≤ 10 primeros días hábiles del trimestre | CHECK / trigger | §4 L.92 |
| RN-B1-005 / RN-B3-023 | `normativa.fecha_publicacion` - `normativa.fecha_expedicion` ≤ 1 día hábil | CHECK / trigger | §4 L.93; §2.3 L.35 |
| RN-B1-018 / RN-B3-024 | `contrato.url_secop` NOT NULL; debe apuntar a SECOP válido | NOT NULL; log_integracion para verificar estado | §4 L.94; §2.4 L.42 |
| RN-B3-022 | Directorio actualizado desde SIGEP; alerta si `sincronizacion_sigep` lleva >24 h sin éxito | Trigger/job: si NOW() - MAX(timestamp_fin WHERE resultado='exitoso') > INTERVAL '24 hours' → alerta | §4 L.95; RNF-02-D01 L.84 |
| RN-B3-025 | `impuesto`: los 7 campos tributarios (sujeto activo, sujeto pasivo, hecho generador, hecho imponible, causacion, base_gravable, tarifa) son NOT NULL | NOT NULL en los 7 campos | §4 L.96; §2.6 L.57 |
| RN-B3-028 | `informe_control_interno.fecha_publicacion` dentro de los 5 días hábiles tras cierre del semestre | CHECK / trigger | §4 L.97 |
| RN-02-D01 | Reemplazar un documento publicado crea un registro en `version_documento` (historial inmutable); la URL original no cambia (fuente única) | INSERT en `version_documento` antes de UPDATE; `activa=FALSE` en la versión anterior, `activa=TRUE` en la nueva | L.105 |
| RN-02-D02 | Alertas de vencimiento disparadas N días antes del plazo legal; si el plazo vence sin publicación → estado='incumplida' y escalada | Job periódico que evalúa `alerta_cumplimiento`; UPDATE estado | L.106 |
| RN-02-D03 | Fallo de integración externa gestionado (mensaje al usuario + log) NO cuenta como vínculo roto | INSERT en `log_integracion`; UI lee el último registro y muestra mensaje si resultado='fallo' | L.107 |
| RN-02-D04 | Directorio "desactualizado" si sync SIGEP > 24 h sin éxito; `alerta_disparada=TRUE` en `sincronizacion_sigep` | Evaluado por job; UNIQUE `(resultado='exitoso', timestamp_fin)` más reciente | L.108 |
| RF-B3-087 | Orden cronológico inverso en la vista de todos los documentos de transparencia | ORDER BY `fecha_publicacion DESC` (query, no constraint de BD) | §2.1 L.20 |
| RF-B3-088 | Fuente única: un documento vive en una sola tabla; otros módulos referencian por FK o URL | UNIQUE constraints en URLs de documentos; sin duplicados cross-table | §2.1 L.21 |
| RNF-B1-037 | Calidad de información: auténtica, íntegra, fiable | Restricciones NOT NULL, integridad referencial, campos de auditoría | §3 L.76 |

---

## 4. Cardinalidades y relaciones

| Entidad A | Relación | Entidad B | Cardinalidad | Notas | Fuente |
|-----------|----------|-----------|-------------|-------|--------|
| `dependencia` | tiene | `servidor_publico` | 1:N | Una dependencia tiene N servidores | §7 L.143 |
| `servidor_publico` | dirige | `dependencia` | 0..1:1 | Un servidor puede dirigir 0 o 1 dependencia | §2.2 L.27 [INFERIDO] |
| `plan_adquisiciones` | origina | `contrato` | 1:N | Un plan puede originar N contratos | §2.4 L.42 |
| `contrato` | tiene | `otrosi` | 1:N | Un contrato puede tener N otrosíes | §2.4 L.42 |
| `normativa` | pertenece_a | `agenda_regulatoria` | N:0..1 | Una norma puede provenir de una agenda | RF-B1-014 L.36 |
| `proyecto_norma` | aprobado_como | `normativa` | 0..1:0..1 | Un proyecto puede aprobarse como norma | §2.3 L.35 |
| `impuesto` | tiene_vencimientos | `calendario_tributario` | 1:N | Un impuesto tiene N fechas de vencimiento por vigencia | §2.6 L.58 |
| `avance_proyecto_inversion` | reporta | proyecto externo | N:1 | N avances por proyecto de inversión | §2.5 L.48 |
| `alerta_cumplimiento` | notifica_a | `servidor_publico` | N:1 | N alertas pueden notificar al mismo responsable | RF-02-D02 L.69 |
| `version_documento` | versiona | (polimórfico) | N:1 | N versiones por documento de cualquier tipo | RF-02-D01 L.68 |
| `sincronizacion_sigep` | actualiza | `servidor_publico` (masivo) | 1:N implícito | Cada sync puede actualizar N servidores | RN-B3-022 L.95 |

---

## 5. Jerarquías ISA / polimorfismo relacional

### 5.1 Polimorfismo en `version_documento`

**Problema:** la entidad `version_documento` debe versionar documentos de tipos distintos: `normativa`, `plan_accion`, `informe_gestion`, `informe_pqrsd`, `informe_control_interno`, `avance_proyecto_inversion`, `plan_adquisiciones`, `contrato`.

**Evidencia en el documento:** RF-02-D01 (L.68) establece que "editar/reemplazar un documento publicado debe conservar la versión anterior"; RN-02-D01 (L.105) lo generaliza para "cualquier documento de transparencia".

**Antipatrón que NO se usará:** columnas `(entidad_tipo VARCHAR, entidad_id INTEGER)` sin integridad referencial declarativa — exactamente la FK genérica sin FK real descrita en el skill jose-bd como prohibida.

**Estrategia elegida: supertipo común `transparencia_publicacion`**

Se introduce una tabla supertipo que unifica todos los documentos publicables bajo transparencia. Cada tabla de documento concreto tiene PK que es también FK al supertipo (patrón class-table / supertype-subtype). `version_documento` apunta a `transparencia_publicacion.id_publicacion`, preservando integridad referencial completa.

```
transparencia_publicacion (id_publicacion PK, subtipo VARCHAR, fecha_publicacion, url_archivo, formato_archivo, publicado_por FK→servidor_publico, activo BOOLEAN)
    ← normativa            (id_normativa PK FK→transparencia_publicacion, tipo, numero, fecha_expedicion, epigrafe, vigencia, url_suin, url_descarga, es_proyecto_norma, fecha_limite_comentarios, id_agenda_regulatoria FK)
    ← plan_adquisiciones   (id_plan_adquisiciones PK FK→transparencia_publicacion, vigencia_fiscal UNIQUE)
    ← plan_accion          (id_plan_accion PK FK→transparencia_publicacion, vigencia_fiscal UNIQUE)
    ← informe_gestion      (id_informe_gestion PK FK→transparencia_publicacion, vigencia_fiscal UNIQUE)
    ← informe_pqrsd        (id_informe_pqrsd PK FK→transparencia_publicacion, trimestre, vigencia_fiscal, cantidad_pqrsd, tiempo_promedio_respuesta, url_kogui)
    ← informe_control_interno (id_informe_control_interno PK FK→transparencia_publicacion, semestre, vigencia_fiscal)
    ← avance_proyecto_inversion (id_avance PK FK→transparencia_publicacion, id_proyecto, trimestre, vigencia_fiscal, porcentaje_meta_lograda)
    ← contrato             (id_contrato PK FK→transparencia_publicacion, numero_contrato UNIQUE, objeto, monto, honorarios, fecha_inicio, fecha_fin, ...)

version_documento (id_version PK, id_publicacion FK→transparencia_publicacion, numero_version, url_archivo_historico, fecha_reemplazo, publicado_por FK, activa)
```

**Restricción de disyunción:** total disjoint — cada `transparencia_publicacion` es exactamente un subtipo. Implementar con CHECK `subtipo IN ('normativa','plan_adquisiciones','plan_accion','informe_gestion','informe_pqrsd','informe_control_interno','avance_proyecto_inversion','contrato')` y constraint que garantiza exactamente una tabla hija tiene FK hacia este supertipo.

**Justificación formal:** Fuente §4.3.1 del skill jose-bd (class-table/supertype-subtype): "máxima integridad, requiere joins". Preserva integridad referencial completa desde `version_documento` sin antipatrón. FD que origina la jerarquía: `id_publicacion → subtipo` (el tipo del documento lo determina el supertipo).

### 5.2 Jerarquía implícita `informe_pqrsd` — desglose por tipo/estado

El campo `tipo_breakdown` y `estado_breakdown` en `informe_pqrsd` contiene subestructura. Se recomienda tabla relacionada `informe_pqrsd_detalle` en lugar de JSONB, para preservar consultabilidad y 1FN.

```
informe_pqrsd_detalle (id_detalle PK, id_informe_pqrsd FK, categoria VARCHAR(30) — 'tipo'/'estado', valor VARCHAR(100), cantidad INTEGER)
```

Fuente: §2.5 L.50 ("cantidad, tipo, estado, tiempo de respuesta").

---

## 6. Inferencias [INFERIDO] con justificación

| # | Campo o regla inferida | Justificación | Entidad afectada | Fuente base |
|---|------------------------|--------------|------------------|-------------|
| 1 | `servidor_publico.activo` | La regla RN-B3-022 exige actualización ante ingresos/desvinculaciones (L.28); se necesita saber si el servidor está activo o desvinculado | `servidor_publico` | RF-B1-017 L.28 |
| 2 | `servidor_publico.fecha_vinculacion` y `fecha_desvinculacion` | El directorio debe reflejar cambios "en tiempo real"; para auditoría y cumplimiento SIGEP se requieren fechas de ingreso/salida | `servidor_publico` | RF-B1-017 L.28 |
| 3 | `contrato.vigencia_fiscal` | La contratación pública colombiana se organiza por vigencias fiscales; necesario para filtrar y asociar al plan de adquisiciones correcto | `contrato` | §2.4 L.42 |
| 4 | `contrato.numero_contrato` UNIQUE por vigencia | Convención estándar de contratación pública colombiana; el documento no lo explicita pero es implícito | `contrato` | §2.4 L.42 |
| 5 | `otrosi.nuevo_valor` y `nueva_fecha_fin` | Los otrosíes típicamente modifican valor o plazo; la fuente menciona su existencia pero no desglosa sus campos | `otrosi` | §2.4 L.42 |
| 6 | `impuesto.vigencia_desde` / `vigencia_hasta` | Las tarifas tributarias cambian por acuerdo municipal año a año; se necesita historial de tarifas por vigencia | `impuesto` | §2.6 L.57 |
| 7 | `dependencia.responsable_id` | El organigrama publicado (RF-B1-097 L.27) incluye "cargo y contacto de dependencias", lo que implica un responsable por dependencia | `dependencia` | §2.2 L.27 |
| 8 | `version_documento.publicado_por` | El UC-B1-006 (L.115) exige log de auditoría al publicar; la versión también debe registrar quién la reemplazó | `version_documento` | UC-B1-006 L.115 |
| 9 | `sincronizacion_sigep.registros_actualizados` | Para auditoría de la sincronización es necesario saber cuántos registros cambiaron, no solo si fue exitosa | `sincronizacion_sigep` | RNF-02-D01 L.84 |
| 10 | `log_integracion.codigo_http` | El diagnóstico técnico de fallos de integración requiere el código HTTP de respuesta | `log_integracion` | RF-02-D03 L.70 |
| 11 | Supertipo `transparencia_publicacion` | Necesario para que `version_documento` tenga integridad referencial real sin el antipatrón de FK genérica (entidad_tipo + entidad_id sin FK) | `transparencia_publicacion` | RF-02-D01 L.68; RN-02-D01 L.105 |
| 12 | `informe_pqrsd_detalle` como tabla separada | Los campos tipo_breakdown y estado_breakdown violan 1FN si se guardan como JSONB; la fuente los enumera como atributos multivaluados | `informe_pqrsd_detalle` | §2.5 L.50 |

---

## 7. Ambigüedades documentadas [AMBIGUO]

| Ref | Descripción | Fuente |
|-----|-------------|--------|
| A-07 | "Grupos de interés": la norma exige caracterizarlos "conforme a su caracterización" sin definir el criterio formal; el campo `contenido_asociado` en `grupo_interes` puede requerir una relación N:M con las tablas de documentos en lugar de un campo texto | §9 L.157 |
| A-04 | Tensión datos.gov.co vs. archivo digital: la integración con datos.gov.co se remite al módulo 11; los campos de `plan_adquisiciones`, `normativa`, etc. podrían necesitar un flag `publicado_datos_gov` | §9 L.158 |
| — | `grupo_interes` puede necesitar una tabla puente `grupo_interes_documento` para relacionar grupos con documentos publicados específicos, en lugar del campo texto libre `contenido_asociado` | RF-B1-096 L.29 |
| — | El campo `tarifa` en `impuesto` puede ser un valor simple o una tabla de rangos (ej. ICA por actividad económica); se requiere aclaración del estatuto tributario distrital | §2.6 L.57; RN-B3-025 L.96 |
