# Extracción BD — Módulo 01 Estructura e Identidad

## 0. Cobertura

- Unidad leída íntegra: SÍ. Líneas totales: 182.
- Archivo fuente: `/var/www/proyect-doc/elicitacion/sede-electronica/01-estructura-identidad/estructura-identidad.md`
- Rango cubierto: líneas 1–182 leídas íntegras, 0 omitidas.

---

## 1. Entidades candidatas

| # | Entidad | Descripción | Transversal / Compartida | Fuente (sección/línea) |
|---|---------|-------------|--------------------------|------------------------|
| 1 | **SedeElectronica** | Representa la sede web de la Alcaldía: su URL propia, la palabra clave asignada por MinTIC, la URL enmascarada en GOV.CO, su estado de integración y metadatos institucionales. | CANDIDATA-COMPARTIDA (módulos 03, 06, 10) | §7 línea 164; §2.6 líneas 81–82 |
| 2 | **ContactoEntidad** | Datos de contacto oficiales de la entidad: dirección(es), teléfonos, correos, redes sociales, horarios. Puede tener hasta 3 locaciones físicas. | CANDIDATA-COMPARTIDA (cualquier módulo que requiera datos de la entidad) | §7 línea 166; RF-B1-002 línea 20 |
| 3 | **Locacion** | Cada sede/locación física de la entidad (hasta 3 según el documento); tiene dirección, código postal, municipio, departamento y horario propio. | CANDIDATA-COMPARTIDA | §7 línea 166; RF-B1-002 línea 20 |
| 4 | **MenuNavegacion** | Ítem de menú de primer o segundo nivel; soporta jerarquía padre–hijo. Tiene orden, estado publicado/despublicado, etiqueta, URL destino y nivel. | LOCAL (módulo 01) | RF-B1-003 línea 31; RF-01-D02 línea 95; RN-01-D02 línea 135 |
| 5 | **Noticia** | Noticia publicada en la página de inicio: imagen, título, descripción, fecha, estado de publicación. | CANDIDATA-COMPARTIDA (módulo 04 posiblemente) | RF-B1-011 línea 43; RF-01-D02 línea 95 |
| 6 | **ElementoCarrusel** | Ítem del carrusel de la página de inicio: imagen, texto alternativo (accesibilidad), orden, estado y URL de destino. | LOCAL (módulo 01) | RF-B1-042 línea 44; RF-01-D02 línea 95 |
| 7 | **PoliticaDocumento** | Documento de política publicado en el footer (Términos y condiciones, Política de privacidad, Derechos de autor, Política de cookies, Accesibilidad). Tiene tipo, versión, fecha de vigencia, URL de descarga y formato. | CANDIDATA-COMPARTIDA (módulo 09 Seguridad; Ley 1581) | RF-B1-009 línea 53; RF-B2-008 línea 54; RF-B2-009 línea 55; RF-B2-010 línea 56 |
| 8 | **ConsentimientoCookie** | Registro por usuario/sesión del consentimiento de cookies: categorías aceptadas/rechazadas, versión de política aplicada, fecha, estado (vigente/caducado). | CANDIDATA-COMPARTIDA (módulo 09 Seguridad) | RF-01-D01 línea 94; RN-01-D01 línea 134; HU-01-D01 línea 156 |
| 9 | **CategoriaCookie** | Catálogo de categorías de cookies (esencial, analítica, funcional, marketing, etc.) con su descripción, finalidad, origen y gestor. | CANDIDATA-COMPARTIDA | §7 línea 165; RF-B1-008 línea 47 |
| 10 | **CookieCatalogo** | Registro técnico de cada cookie individual: nombre, tipo, finalidad, dominio/origen, gestor, periodo de conservación. Asociada a una CategoriaCookie. | LOCAL (módulo 01) | §7 línea 165 |
| 11 | **DominioConfianza** | Lista blanca de dominios de confianza administrable; cuando el destino de un enlace coincide con un dominio de esta lista, se omite el modal de "aviso de salida". | LOCAL (módulo 01) | RF-01-D03 línea 96; RN-01-D03 línea 136; HU-01-D03 línea 159 |
| 12 | **PlanIntegracion** | Plan de integración a GOV.CO: actividades, responsables, fechas, presupuesto, avance mensual. Se incorpora al PETI. | CANDIDATA-COMPARTIDA (módulo de gobierno/PETI) | RF-B1-100 línea 80; §7 línea 167 |
| 13 | **TramiteIntegracion** | Trámite individual incluido en el Plan de Integración: nombre, solicitudes/año, acción requerida, fecha meta, responsable, ID SUIT, URL en GOV.CO, estados estandarizados de trámite. | CANDIDATA-COMPARTIDA (módulo 03 Servicios, módulo 10 SUIT) | §7 línea 167; RF-B3-126 línea 84; RF-B3-127 línea 85 |
| 14 | **RedSocial** | Catálogo de redes sociales de la entidad: plataforma, URL, nombre de perfil. | CANDIDATA-COMPARTIDA | RF-B1-002 línea 20; §7 línea 166 |

---

## 2. Atributos/campos por entidad

### 2.1 SedeElectronica

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] clave surrogate estándar |
| url_original | VARCHAR(2048) | Sí | URL válida; dominio propio de la Alcaldía | UNIQUE | §7 línea 164; RF-B2-001 línea 81 |
| palabra_clave | VARCHAR(255) | Sí | Asignada por MinTIC; p.ej. `santa-marta` | UNIQUE | §7 línea 164; RF-B2-001 línea 81 |
| url_enmascarada_gov | VARCHAR(2048) | Sí | Patrón `https://www.gov.co/[palabra_clave]` | UNIQUE | §7 línea 164; RF-B2-001 línea 81 |
| nombre_institucion | VARCHAR(500) | Sí | Nombre oficial de la Alcaldía | — | §7 línea 164 |
| categoria | VARCHAR(100) | Sí | Ej. "Alcaldía Distrital" | — | §7 línea 164 |
| sector | VARCHAR(100) | No | Ej. "Gobierno territorial" | — | §7 línea 164 |
| estado_integracion | VARCHAR(50) | Sí | ENUM: pendiente / en_proceso / activa / suspendida | — | §7 línea 164; RF-B2-001 línea 81 |
| paso_proceso_actual | SMALLINT | No | 1–7; trazado al proceso de 7 pasos | CHECK (1–7) | RF-B1-070 línea 79 |
| fecha_activacion | DATE | No | Fecha en que MinTIC activó el enmascaramiento | — | RF-B1-070 línea 79 |
| soporte_ipv4 | BOOLEAN | Sí | true = habilitado | DEFAULT true | RF-B1-099 línea 86 |
| soporte_ipv6 | BOOLEAN | Sí | true = habilitado | DEFAULT false | RF-B1-099 línea 86 |
| created_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] auditoría estándar |
| updated_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] auditoría estándar |

### 2.2 ContactoEntidad

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| sede_electronica_id | INTEGER | Sí | FK a SedeElectronica | FK → SedeElectronica.id | §7 línea 166 |
| nombre_entidad | VARCHAR(500) | Sí | Nombre oficial de la autoridad | — | RF-B1-002 línea 20; §7 línea 166 |
| conmutador | VARCHAR(30) | No | Formato +57 + indicativo + número | CHECK (formato +57) | RF-B1-002 línea 20; RN-B1-017 línea 125 |
| linea_gratuita | VARCHAR(30) | No | Ej. 018000xxxxxx | — | RF-B1-002 línea 20 |
| linea_anticorrupcion | VARCHAR(30) | No | 018000 xxx xxx (exento de prefijo +57) | — | RF-B1-002 línea 20; RF-B2-006 línea 21 |
| correo_institucional | VARCHAR(320) | Sí | Formato email RFC 5321 | — | RF-B1-002 línea 20; §7 línea 166 |
| correo_notif_judicial | VARCHAR(320) | No | Formato email RFC 5321 | — | RF-B1-002 línea 20; §7 línea 166 |
| updated_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] auditoría |

> Nota: hasta 3 locaciones físicas → tabla separada `Locacion` (1:N con ContactoEntidad).

### 2.3 Locacion

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| contacto_entidad_id | INTEGER | Sí | FK a ContactoEntidad | FK → ContactoEntidad.id | §7 línea 166 |
| orden | SMALLINT | Sí | 1–3; cardinalidad máx. 3 locaciones | CHECK (1–3) | §7 línea 166 |
| direccion | VARCHAR(500) | Sí | Dirección postal completa | — | §7 línea 166; RF-B1-002 línea 20 |
| codigo_postal | VARCHAR(10) | No | Código postal colombiano (6 dígitos) | — | RF-B1-002 línea 20 |
| municipio | VARCHAR(150) | Sí | Nombre del municipio | — | RF-B1-002 línea 20; §7 línea 166 |
| departamento | VARCHAR(150) | Sí | Nombre del departamento | — | RF-B1-002 línea 20; §7 línea 166 |
| horario | TEXT | No | Descripción textual de horario de atención | — | RF-B1-002 línea 20; §7 línea 166 |
| es_principal | BOOLEAN | Sí | Indica la locación primaria | DEFAULT false | [INFERIDO] al haber hasta 3 locaciones, se necesita distinguir la principal |

### 2.4 MenuNavegacion

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| padre_id | INTEGER | No | NULL = ítem de primer nivel; valor = ítem padre | FK → MenuNavegacion.id (self-ref.) | RF-B1-003 línea 31; RN-01-D02 línea 135 |
| etiqueta | VARCHAR(200) | Sí | Texto visible del ítem | — | RF-B1-003 línea 31 |
| url_destino | VARCHAR(2048) | Sí | URL interna o externa | — | RF-B1-003 línea 31 |
| orden | SMALLINT | Sí | Posición dentro de su nivel | — | RF-01-D02 línea 95 |
| nivel | SMALLINT | Sí | 1 o 2; CHECK máx. 2 | CHECK (nivel IN (1,2)) | RF-B1-003 línea 31; RN-01-D02 línea 135 |
| es_obligatorio | BOOLEAN | Sí | TRUE para los 4 ítems fijos (Inicio, Transparencia, Atención, Participa) | DEFAULT false | RF-B1-003 línea 31 |
| aria_label | VARCHAR(300) | No | Atributo ARIA para accesibilidad | — | RF-B1-003 línea 31 |
| estado | VARCHAR(20) | Sí | ENUM: publicado / despublicado / eliminado | DEFAULT 'publicado' | RF-01-D02 línea 95 |
| created_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_by | INTEGER | No | FK a tabla de usuarios/editores | FK → Usuario.id | HU-01-D02 línea 158 |

> RN-01-D02 (línea 135): CHECK en BD: COUNT de ítems nivel=1 ≤ 7 por menú; nivel ≤ 2.

### 2.5 Noticia

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| titulo | VARCHAR(150) | Sí | Máx. 150 caracteres | CHECK (LENGTH ≤ 150) | RF-B1-011 línea 43 |
| descripcion | VARCHAR(200) | No | Máx. 200 caracteres | CHECK (LENGTH ≤ 200) | RF-B1-011 línea 43 |
| url_imagen | VARCHAR(2048) | Sí | URL de imagen en ratio 4:3 o 16:9 | — | RF-B1-011 línea 43 |
| ratio_imagen | VARCHAR(10) | No | ENUM: '4:3' / '16:9' | CHECK (ratio IN ('4:3','16:9')) | RF-B1-011 línea 43 |
| fecha_publicacion | DATE | Sí | Fecha de publicación; orden cronológico inverso | — | RF-B1-011 línea 43 |
| estado | VARCHAR(20) | Sí | ENUM: publicada / despublicada / eliminada | DEFAULT 'publicada' | RF-01-D02 línea 95 |
| created_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_by | INTEGER | No | FK a tabla de usuarios/editores | FK → Usuario.id | HU-01-D02 línea 158 |

### 2.6 ElementoCarrusel

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| url_imagen | VARCHAR(2048) | Sí | URL de la imagen del carrusel | — | RF-B1-042 línea 44 |
| texto_alternativo | VARCHAR(500) | Sí | Alt-text accesibilidad | NOT NULL | RF-B1-042 línea 44; RNF-B3-005 |
| url_destino | VARCHAR(2048) | No | URL al hacer clic en el carrusel | — | RF-B1-042 línea 44 |
| orden | SMALLINT | Sí | Posición en el carrusel | — | RF-01-D02 línea 95 |
| estado | VARCHAR(20) | Sí | ENUM: activo / inactivo / eliminado | DEFAULT 'activo' | RF-01-D02 línea 95 |
| created_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_by | INTEGER | No | FK a tabla de usuarios/editores | FK → Usuario.id | HU-01-D02 línea 158 |

### 2.7 PoliticaDocumento

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| tipo | VARCHAR(50) | Sí | ENUM: terminos_condiciones / privacidad / derechos_autor / cookies / accesibilidad | UNIQUE (tipo, vigente) combinado | RF-B1-009 línea 53 |
| version | VARCHAR(20) | Sí | Ej. "v3", "2026-01" | — | RF-01-D01 línea 94; RN-01-D01 línea 134 |
| titulo | VARCHAR(300) | Sí | Título formal del documento | — | RF-B2-008 línea 54 |
| url_descarga | VARCHAR(2048) | Sí | URL del archivo descargable en formato abierto | — | RF-B1-009 línea 53 |
| formato_descarga | VARCHAR(10) | Sí | Ej. PDF, ODF, HTML | — | RF-B1-009 línea 53 |
| fecha_vigencia_desde | DATE | Sí | Fecha de entrada en vigor | — | RF-B2-009 línea 55 |
| fecha_vigencia_hasta | DATE | No | NULL = vigente indefinidamente | — | [INFERIDO] ciclo de vida de versiones |
| es_vigente | BOOLEAN | Sí | Solo un documento por tipo puede ser vigente simultáneamente | UNIQUE (tipo) WHERE es_vigente = true | RF-B1-009 línea 53 |
| marco_legal | VARCHAR(500) | No | Cita normativa (Ley 1581/2012, Ley 1712/2014, etc.) | — | RF-B2-009 línea 55 |
| created_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |

### 2.8 ConsentimientoCookie

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| identificador_usuario | VARCHAR(500) | Sí | Huella o ID anónimo de sesión/usuario (no PII por defecto) | — | RF-01-D01 línea 94; HU-01-D01 línea 156 |
| politica_id | INTEGER | Sí | FK a PoliticaDocumento (tipo = cookies) con su versión | FK → PoliticaDocumento.id | RN-01-D01 línea 134 |
| fecha_consentimiento | TIMESTAMP | Sí | Momento en que se otorgó/actualizó el consentimiento | NOT NULL | RF-01-D01 línea 94 |
| fecha_expiracion | TIMESTAMP | Sí | fecha_consentimiento + 12 meses | NOT NULL; CHECK (fecha_expiracion > fecha_consentimiento) | RN-01-D01 línea 134 |
| estado | VARCHAR(20) | Sí | ENUM: vigente / caducado / revocado | — | RN-01-D01 línea 134; HU-01-D01 línea 156 |

> La relación muchos-a-muchos entre ConsentimientoCookie y CategoriaCookie se resuelve via tabla intermedia **ConsentimientoCategoria**.

### 2.9 ConsentimientoCategoria (tabla asociativa)

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| consentimiento_id | INTEGER | Sí | FK a ConsentimientoCookie | PK (compuesta) + FK → ConsentimientoCookie.id | RF-B1-008 línea 47; RF-01-D01 línea 94 |
| categoria_id | INTEGER | Sí | FK a CategoriaCookie | PK (compuesta) + FK → CategoriaCookie.id | RF-B1-008 línea 47 |
| decision | VARCHAR(20) | Sí | ENUM: aceptada / rechazada | NOT NULL | RF-B1-008 línea 47 |

### 2.10 CategoriaCookie

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| nombre | VARCHAR(100) | Sí | Ej. "esencial", "analítica", "funcional", "marketing" | UNIQUE | §7 línea 165 |
| descripcion | TEXT | No | Explicación en lenguaje claro para el ciudadano | — | RF-B1-008 línea 47 |
| es_esencial | BOOLEAN | Sí | TRUE = no requiere consentimiento (no desactivable) | DEFAULT false | RF-B1-008 línea 47 |

### 2.11 CookieCatalogo

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| categoria_id | INTEGER | Sí | FK a CategoriaCookie | FK → CategoriaCookie.id | §7 línea 165 |
| nombre_cookie | VARCHAR(200) | Sí | Nombre técnico de la cookie | — | §7 línea 165 |
| finalidad | TEXT | Sí | Para qué se usa | — | §7 línea 165 |
| dominio_origen | VARCHAR(255) | Sí | Dominio que la establece | — | §7 línea 165 |
| gestor | VARCHAR(200) | No | Empresa/servicio gestor | — | §7 línea 165 |
| periodo_conservacion | VARCHAR(100) | Sí | Ej. "sesión", "12 meses", "persistente" | — | §7 línea 165 |
| tipo | VARCHAR(50) | Sí | ENUM: sesion / persistente | — | §7 línea 165 |

### 2.12 DominioConfianza

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| dominio | VARCHAR(255) | Sí | Dominio o subdominio confiable (ej. `gov.co`, `articulador.gov.co`) | UNIQUE | RF-01-D03 línea 96; RN-01-D03 línea 136 |
| descripcion | VARCHAR(500) | No | Motivo por el que es confiable | — | RN-01-D03 línea 136 |
| activo | BOOLEAN | Sí | Habilitado/deshabilitado | DEFAULT true | RF-01-D03 línea 96 |
| created_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |
| updated_by | INTEGER | No | FK a tabla de usuarios/administradores | FK → Usuario.id | HU-01-D03 línea 159 |

### 2.13 PlanIntegracion

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| sede_electronica_id | INTEGER | Sí | FK a SedeElectronica | FK → SedeElectronica.id | RF-B1-100 línea 80 |
| version | VARCHAR(20) | Sí | Versión o número del plan | — | RF-B1-100 línea 80 |
| fecha_creacion | DATE | Sí | Fecha de elaboración | — | RF-B1-100 línea 80 |
| incorporado_peti | BOOLEAN | Sí | ¿Incluido en el PETI? | DEFAULT false | RF-B1-100 línea 80 |
| fecha_envio_gobierno_digital | DATE | No | Fecha de envío a Dir. de Gobierno Digital | — | RF-B1-100 línea 80 |
| avance_porcentaje | SMALLINT | No | 0–100 | CHECK (0–100) | RF-B1-100 línea 80 |
| fecha_ultimo_avance | DATE | No | Última actualización mensual | — | RF-B1-100 línea 80 |
| created_at | TIMESTAMP | Sí | — | DEFAULT now() | [INFERIDO] |

### 2.14 TramiteIntegracion

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| plan_integracion_id | INTEGER | Sí | FK a PlanIntegracion | FK → PlanIntegracion.id | §7 línea 167 |
| nombre_tramite | VARCHAR(500) | Sí | Nombre oficial del trámite | — | §7 línea 167 |
| id_suit | VARCHAR(50) | No | ID en el sistema SUIT / DAFP | UNIQUE (cuando asignado) | RF-B3-126 línea 84 |
| solicitudes_anio | INTEGER | No | Volumen estimado de solicitudes/año | CHECK (> 0) | §7 línea 167 |
| accion_requerida | VARCHAR(200) | No | Qué hay que hacer para integrarlo | — | §7 línea 167 |
| fecha_meta | DATE | No | Fecha objetivo de integración | — | §7 línea 167 |
| responsable | VARCHAR(200) | No | Persona/área responsable | — | §7 línea 167 |
| url_ficha_gov | VARCHAR(2048) | No | URL de la ficha en GOV.CO una vez integrado | — | RF-B3-126 línea 84 |
| estado_tramite | VARCHAR(50) | No | ENUM: solicitud_registrada / recibida_a_satisfaccion / en_tramite / resuelta | CHECK (estado IN (…)) | RF-B3-127 línea 85 |
| momento_acceso | TEXT | No | Descripción del momento "acceso" (4 momentos GOV.CO) | — | RF-B3-126 línea 84 |
| momento_solicitud | TEXT | No | Descripción del momento "solicitud" | — | RF-B3-126 línea 84 |
| momento_resolucion | TEXT | No | Descripción del momento "resolución" | — | RF-B3-126 línea 84 |
| momento_resultado | TEXT | No | Descripción del momento "resultado" | — | RF-B3-126 línea 84 |
| tipo_recurso | VARCHAR(50) | No | ENUM: portal / app / chatbot / pqr / vu | §7 línea 167 |

### 2.15 RedSocial

| Campo | Tipo implícito | Obligatorio | Dominio / Valores | PK/FK candidata | Fuente (sección/línea) |
|-------|---------------|-------------|-------------------|-----------------|------------------------|
| id | INTEGER / UUID | Sí | Autogenerado | PK | [INFERIDO] |
| contacto_entidad_id | INTEGER | Sí | FK a ContactoEntidad | FK → ContactoEntidad.id | RF-B1-002 línea 20 |
| plataforma | VARCHAR(100) | Sí | Ej. "Twitter/X", "Facebook", "Instagram", "YouTube" | — | RF-B1-002 línea 20 |
| url_perfil | VARCHAR(2048) | Sí | URL al perfil oficial | — | RF-B1-002 línea 20 |
| nombre_perfil | VARCHAR(200) | No | Nombre del perfil (@handle) | — | §7 línea 166 |
| activo | BOOLEAN | Sí | — | DEFAULT true | [INFERIDO] |

---

## 3. Reglas de negocio con impacto en datos

| ID | Condición | Restricción técnica | Fuente |
|----|-----------|---------------------|--------|
| RN-B1-017 | Todo teléfono publicado que NO sea línea 018000/019000 | CHECK: campo `conmutador` debe iniciar con `+57`; columnas de líneas gratuitas/anticorrupción exentas de ese CHECK | RN-B1-017 línea 125; RF-B2-006 línea 21 |
| RN-01-D02 | El menú de navegación principal no puede superar 7 ítems de nivel 1 ni 2 niveles de profundidad | CHECK a nivel de aplicación/trigger: COUNT(id) WHERE padre_id IS NULL ≤ 7 para la sede; CHECK nivel IN (1,2) a nivel de columna | RN-01-D02 línea 135; RF-B1-003 línea 31 |
| RN-01-D01 | El consentimiento de cookies caduca a los 12 meses o cuando cambia la versión de la política | CHECK: fecha_expiracion = fecha_consentimiento + INTERVAL '12 months'; estado pasa a 'caducado' por job/trigger | RN-01-D01 línea 134; RF-01-D01 línea 94 |
| RN-01-D01 | Ninguna cookie no esencial puede reactivarse con consentimiento caducado | CHECK: categoría no esencial requiere ConsentimientoCategoria.decision = 'aceptada' con estado = 'vigente' | RN-01-D01 línea 134 |
| RN-01-D03 | El modal de salida a sitio externo no se muestra para dominios en DominioConfianza | Lógica de aplicación consulta DominioConfianza antes de renderizar; no es CHECK de BD, pero la tabla es el artefacto persistido | RN-01-D03 línea 136; RF-01-D03 línea 96 |
| RF-B1-009 | Solo puede haber un documento vigente por cada tipo de política | UNIQUE parcial en PostgreSQL: `UNIQUE (tipo) WHERE es_vigente = true` en PoliticaDocumento | RF-B1-009 línea 53 |
| RF-B1-011 | Título de noticia ≤ 150 caracteres; descripción ≤ 200 caracteres | `CHECK (LENGTH(titulo) <= 150)` y `CHECK (LENGTH(descripcion) <= 200)` | RF-B1-011 línea 43 |
| RF-B3-126 | Trámites integrados tienen exactamente 4 momentos (acceso, solicitud, resolución, resultado) | Los 4 campos momento_* deben ser NOT NULL cuando estado_tramite = 'resuelta' [INFERIDO] | RF-B3-126 línea 84 |
| RF-B3-127 | Los estados de trámite siguen exactamente la secuencia GOV.CO | `CHECK (estado_tramite IN ('solicitud_registrada','recibida_a_satisfaccion','en_tramite','resuelta'))` | RF-B3-127 línea 85 |
| RN-B3-004 | Toda autoridad pública tiene exactamente una sede electrónica | UNIQUE en SedeElectronica por entidad institucional [INFERIDO] | RN-B3-004 línea 119 |
| RF-B2-002 | Todos los portales, apps y plataformas de la Alcaldía se integran a la sede | Cardinalidad: TramiteIntegracion.tipo_recurso cubre portal, app, chatbot, pqr, vu | RF-B2-002 línea 82; §7 línea 167 |

---

## 4. Cardinalidades y relaciones

| Relación | Cardinalidad | Notas | Fuente |
|----------|-------------|-------|--------|
| SedeElectronica → ContactoEntidad | 1 : 1 | Una sede tiene un único bloque de datos de contacto | §7 línea 166 |
| ContactoEntidad → Locacion | 1 : N (máx. 3) | Hasta 3 locaciones físicas | §7 línea 166; RF-B1-002 línea 20 |
| ContactoEntidad → RedSocial | 1 : N | Sin límite explícito | RF-B1-002 línea 20 |
| SedeElectronica → PlanIntegracion | 1 : N | Puede haber revisiones del plan | RF-B1-100 línea 80 |
| PlanIntegracion → TramiteIntegracion | 1 : N | Un plan contiene N trámites | §7 línea 167 |
| MenuNavegacion → MenuNavegacion | 1 : N (self, máx. 2 niveles) | Un ítem padre tiene ≤N hijos (nivel 2); hijos sin hijos propios | RF-B1-003 línea 31; RN-01-D02 línea 135 |
| ConsentimientoCookie → CategoriaCookie | N : M (via ConsentimientoCategoria) | Cada consentimiento cubre varias categorías con decisión por categoría | RF-B1-008 línea 47 |
| ConsentimientoCookie → PoliticaDocumento | N : 1 | Cada consentimiento referencia la versión de política vigente al momento | RN-01-D01 línea 134 |
| CategoriaCookie → CookieCatalogo | 1 : N | Una categoría agrupa N cookies concretas | §7 línea 165 |
| PoliticaDocumento (tipo=cookies) | historificada | Múltiples versiones; solo una es_vigente | RF-B1-009 línea 53; RN-01-D01 línea 134 |

---

## 5. Jerarquías ISA / Polimorfismo relacional

### 5.1 Jerarquía ISA detectada: ninguna explícita en este módulo

El documento no define una jerarquía ISA formal. Se identifica sin embargo un patrón de especialización **parcial y solapante** en `TramiteIntegracion.tipo_recurso` (portal, app, chatbot, PQR, VU): en el estado actual no se justifica una tabla por subtipo porque los atributos específicos de cada recurso son escasos en este módulo. Si los módulos 03 o 10 los enriquecen, se revisará la estrategia de mapeo.

### 5.2 Polimorfismo relacional: MenuNavegacion (auto-referencia)

`MenuNavegacion` usa una **FK self-referencial** (`padre_id → MenuNavegacion.id`) para modelar la jerarquía de dos niveles. No se usa el antipatrón `(entity_type, entity_id)`. La restricción de máximo 2 niveles se impone con un CHECK o trigger, no con una tabla por nivel (que haría costosa la consulta del menú completo).

---

## 6. Marcado de PRESENTACIÓN (fuera de BD)

Los siguientes elementos del módulo son puramente de capa de presentación/front-end y NO requieren persistencia en tablas propias:

| Elemento | Justificación |
|----------|---------------|
| Top bar GOV.CO (altura 56 px, color Cobalt #0943B5, área activa ≥44×44 px) | Estilo CSS/Kit UI; ningún valor varía por configuración almacenada en BD | RF-B1-001 línea 19 |
| Tipografía Nunito Sans + Verdana | Definida en el Kit UI GOV.CO; no configurable en BD | RF-B1-098 línea 23 |
| Paleta de color Cobalt #0943B5 | Constante del Kit UI; inamovible | RNF-B3-045 línea 104 |
| Breadcrumb (migas de pan) | Componente generado dinámicamente del árbol de rutas; la estructura viene de MenuNavegacion en BD, no una tabla propia | RF-B2-038 línea 36 |
| Botón flotante "Volver arriba" | Comportamiento CSS/JS puro; sin estado persistido | RF-B3-061 línea 25 |
| Acordeón (RF-B3-062) | Componente UI sin estado persistido en BD | RF-B3-062 línea 64 |
| Alerta modal (RF-B3-063) | Componente UI renderizado en cliente | RF-B3-063 línea 65 |
| Toast (RF-B3-064) | Notificación efímera del cliente | RF-B3-064 línea 66 |
| Botones Kit UI (RF-B3-066) | Estilos; ningún atributo de botón se guarda en BD | RF-B3-066 línea 67 |
| Galería de aplicaciones (RF-B3-068) | Catálogo externo (GOV.CO, CIIU); links estáticos o configurables como CookieCatalogo/DominioConfianza pero sin tabla nueva | RF-B3-068 línea 68 |
| Indicador de carga / spinner (RF-B3-070) | Componente UI efímero | RF-B3-070 línea 69 |
| Paginación (RF-B3-075) | Componente UI; el parámetro `page` es de query-string, no de BD | RF-B3-075 línea 70 |
| Tablas con ordenamiento (RF-B3-076) | El ordenamiento es parámetro de consulta SQL; no tabla propia | RF-B3-076 línea 71 |
| Cuadrícula Bootstrap 5.0 / breakpoints (RF-B3-060) | Layout CSS; sin persistencia | RF-B3-060 línea 63 |
| Aviso modal de salida a sitio externo (HTML/JS) | El **modal** es presentación; lo que persiste es la **tabla DominioConfianza** que alimenta la lógica | RF-B1-071 línea 46 |
| Botón de idioma (RF-B3-055) — DIFERIDO | Función diferida; si se implementara, persistiría como preferencia de usuario, no en este módulo | RF-B3-055 línea 24 |
| Página 404 personalizada (contenido estático) | El contenido del 404 es plantilla; los enlaces alternativos vienen de MenuNavegacion | RF-B1-007 línea 45 |
| Sitemap.xml | Generado dinámicamente del árbol de páginas/menú; no tabla propia | RF-B1-010 línea 35 |
| Mapa del sitio (página HTML navegable) | Renderizado del árbol MenuNavegacion en tiempo de petición | RF-B1-010 línea 35 |
| Doble pila IPv4/IPv6 | Configuración de infraestructura de red/DNS; no tabla de BD | RF-B1-099 línea 86 |
| Proxy GOV.CO / enmascaramiento de URL | Configuración de red MinTIC; el endpoint queda en SedeElectronica.url_enmascarada_gov | §8 línea 171 |
| CDN GOV.CO / Kit UI v9.2 / Biblioteca GOV.CO | Recursos externos; el fallback es configuración de despliegue | §8 línea 172; RNF-01-D01 línea 112 |
| Comportamiento responsive / hamburguesa <768 px | CSS/JS; sin persistencia | RF-B1-004 línea 32 |
| Autocompletado del buscador (≤10 sugerencias, tolerancia ortográfica) | Funcionalidad del motor de búsqueda (Elasticsearch/Solr/pg_trgm); no una tabla de este módulo | RF-B1-006 línea 34 |

---

## 7. Inferencias [INFERIDO]

| # | Inferencia | Justificación técnica | Basada en |
|---|-----------|----------------------|-----------|
| I-01 | `SedeElectronica` necesita campos `soporte_ipv4` y `soporte_ipv6` para registrar el cumplimiento del RNF de doble pila | RNF-B1-027 exige doble pila; debe poder auditarse el estado real en BD | RF-B1-099 línea 86 |
| I-02 | `Locacion.es_principal` distingue la sede principal de las secundarias | Si hay hasta 3 locaciones, se necesita saber cuál es la oficial para el footer | §7 línea 166; RF-B1-002 línea 20 |
| I-03 | `MenuNavegacion.updated_by` y `ElementoCarrusel.updated_by` → FK a tabla de usuarios | HU-01-D02 exige log de cambios; implica una tabla de usuarios/editores (módulo 09 o transversal) | HU-01-D02 línea 158 |
| I-04 | `PoliticaDocumento` guarda historial completo de versiones (no solo la vigente) | RN-01-D01 exige referenciar la versión de política en cada consentimiento; sin historial no es posible validar el estado pasado | RN-01-D01 línea 134 |
| I-05 | `ConsentimientoCookie.identificador_usuario` es un identificador anónimo (UUID de sesión o fingerprint), no una FK a un usuario autenticado | Ley 1581 aplica a todos los visitantes, incluidos los anónimos; no se puede asumir login | RN-01-D01 línea 134; Ley 1581 |
| I-06 | El tope de 7 ítems de primer nivel en `MenuNavegacion` se impone mediante trigger o constraint de aplicación porque un simple `CHECK` de columna no puede contar filas de la misma tabla en PostgreSQL estándar | RN-01-D02 lo eleva a invariante; en PostgreSQL 15+ se puede usar un trigger BEFORE INSERT/UPDATE | RN-01-D02 línea 135 |
| I-07 | `TramiteIntegracion` tiene 4 campos `momento_*` TEXT; si el volumen y la reutilización entre trámites crecen, podrían normalizarse a una tabla `MomentoTramite` (tramite_id, tipo_momento, descripcion). Por ahora los 4 campos en la misma fila son suficientes dado que son 4 exactos y no extensibles (los define GOV.CO) | RF-B3-126 define exactamente 4 momentos; no hay indicación de extensión | RF-B3-126 línea 84 |
| I-08 | `SedeElectronica.paso_proceso_actual` (1–7) permite auditar en qué paso del proceso de integración se encuentra la sede | El proceso de 7 pasos es una secuencia ordenada y auditable | RF-B1-070 línea 79 |

---

## 8. Normativa citada

| Norma | Impacto en datos | Fuente |
|-------|-----------------|--------|
| Ley 1437/2011 Art.60 | Toda autoridad pública debe tener al menos una sede electrónica → unicidad de SedeElectronica por entidad | RN-B3-004 línea 119 |
| Decreto 2106/2019 Art.14 | La sede integra TODOS los portales/plataformas/VU → TramiteIntegracion.tipo_recurso exhaustivo | RN-B3-005 línea 120 |
| Decreto 2106/2019 Art.15 | Integración obligatoria por proxy/enmascaramiento + plazo para VU | RN-B1-001 línea 118; RN-B3-007 línea 126 |
| Decreto 1078/2015 Art.2.2.17.1.2 | Obligación de integración | RN-B1-001 línea 118 |
| Ley 1581/2012 | Consentimiento de cookies versionado y caducable; derechos ARCO en política de privacidad | RN-01-D01 línea 134; RF-B2-009 línea 55 |
| Ley 1712/2014 | Política de privacidad debe incluir finalidades, derechos, contacto | RF-B2-009 línea 55 |
| Res. 1519/2020 Anexo 2, §2.2.1-2.2.2 | Teléfonos con prefijo +57 e indicativo CRC; excepción 018000/019000 | RN-B1-017 línea 125 |
| Directiva Presidencial 03/2019 | Kit UI GOV.CO obligatorio; sin publicidad ajena | RN-B2-029 línea 122; RN-B1-014 línea 124 |
| Res. 2893/2020 | Kit UI obligatorio en trámites integrados | RN-B2-029 línea 122 |
| Ley 1712/2014; Decreto 2106/2019; Art.10 CP | La sede opera en castellano | RN-B1-012 línea 123 |

---

## 9. Vacíos / preguntas para el orquestador

| ID | Información esperada que NO aparece | Impacto en BD |
|----|-------------------------------------|---------------|
| V-01 | No se define el catálogo de categorías de cookies (nombres exactos) | CategoriaCookie.nombre necesita un catálogo normativo; bloqueante para poblar dominio | §7 línea 165 |
| V-02 | No se especifica si los ítems obligatorios del menú (Inicio, Transparencia, Atención, Participa) deben tener URLs fijas o configurables | Impacta si MenuNavegacion.url_destino es configurable o constante para is_obligatorio=true | RF-B1-003 línea 31 |
| V-03 | No se define la estructura interna del módulo "buscador": si indexa en BD (pg_trgm, FTS) o en motor externo | Define si hay tablas de índice de búsqueda o integración externa | RF-B1-005/006 líneas 33–34 |
| V-04 | No se define el proceso de baja (des-integración) de un portal de GOV.CO ni el estado de SedeElectronica en ese caso | El ENUM estado_integracion necesita el valor correcto para baja | §9 línea 180 |
| V-05 | No se especifica si PlanIntegracion es un documento único o versionado históricamente | Si es versionado, el 1:N ya está cubierto; si es singleton, requiere UNIQUE (sede_electronica_id) | RF-B1-100 línea 80 |
| V-06 | La tabla de usuarios/editores (rol que actualiza menú, noticias, carrusel, dominios de confianza) no está definida en este módulo | Módulo 09 (Seguridad) debería proveerla; hasta entonces updated_by queda como FK nullable | HU-01-D02 línea 158; HU-01-D03 línea 159 |
| V-07 | [AMBIGUO] RF-B3-055 (botón de idioma) está DIFERIDO — si se implementa, ¿la preferencia de idioma persiste por usuario autenticado, por cookie o solo por sesión? | Define si se requiere tabla PreferenciaIdioma o columna en ConsentimientoCookie | RF-B3-055 línea 24 |
