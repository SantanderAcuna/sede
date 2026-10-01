-- =============================================================================
-- Proyecto: BD Sede Electronica - Distrito de Santa Marta
-- Script: schema.sql (DDL consolidado y ejecutable - jose-bd metodologia §4.7)
-- SGBD: PostgreSQL 15+
-- Fecha: 2026-06-07
-- Fuentes: 03-modelo-logico.md (campos) + 04-modelo-fisico.md (tipos/indices/particiones)
-- Esquema UNICO INTEGRADO. ~149 tablas. Cero truncamiento.
--
-- Orden de ejecucion:
--   1. Extensiones
--   2. DOMAINs (correo_email, telefono_co)
--   3. Tablas sin dependencias -> orden topologico de FK
--   4. Indices (justificados por algebra relacional)
--   5. Vistas (independencia logica)
--   6. Triggers / funciones (cobertura ISA, anti-ciclo, cierre temporal, etc.)
--
-- Convenciones C-13 (politica PK por clase):
--   A = BIGINT GENERATED ALWAYS AS IDENTITY + UNIQUE(clave natural)
--   B = UUID gen_random_uuid() (expuesta/federada): usuario_interno,
--       expediente_electronico, documento_electronico, firma_electronica,
--       notificacion, sesion, incidente_seguridad, contenido
--   C = clave natural directa como PK (catalogos pequenos)
--   D = BIGINT IDENTITY append-only de alto volumen (particionadas)
--   E = subtipos ISA: PK = FK al supertipo
--   F = asociativas / historicos: PK natural compuesta
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1. EXTENSIONES
-- -----------------------------------------------------------------------------
-- gen_random_uuid() es builtin en PostgreSQL 13+; pgcrypto NO es estrictamente
-- necesario en PG15. Se incluye CREATE EXTENSION defensivo para entornos que
-- requieran funciones criptograficas adicionales (digest, gen_salt, etc.).
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- -----------------------------------------------------------------------------
-- 2. DOMAINs (modelo fisico §1.2)
-- -----------------------------------------------------------------------------
-- DOMAIN correo_email: validacion RFC 5322 simplificada, reutilizada en
-- ciudadano / usuario_interno / pqrsd / dependencia / contacto.
-- La unicidad case-insensitive se impone con indice funcional UNIQUE (lower(correo)).
CREATE DOMAIN correo_email AS VARCHAR(254)
    CHECK (VALUE ~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$');

-- DOMAIN telefono_co: formato Colombia +57 (RN-B1-017); exime lineas 018000/019000.
CREATE DOMAIN telefono_co AS VARCHAR(20)
    CHECK (VALUE ~ '^(\+57[0-9]{7,12}|01[89]000[0-9]{4,7})$');

-- =============================================================================
-- 3. TABLAS - NIVEL 0 (catalogos y entidades sin FK salientes)
-- =============================================================================

-- ---- sede_electronica (C01, clase A) ----------------------------------------
CREATE TABLE sede_electronica (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    url_original          VARCHAR(2048) NOT NULL UNIQUE,
    palabra_clave         VARCHAR(255)  NOT NULL UNIQUE,
    url_enmascarada_gov   VARCHAR(2048) UNIQUE,
    nombre_institucion    VARCHAR(500)  NOT NULL,
    categoria             VARCHAR(100)  NOT NULL,
    sector                VARCHAR(100),
    estado_integracion    VARCHAR(50)   NOT NULL DEFAULT 'pendiente',
    paso_proceso_actual   SMALLINT,
    fecha_activacion      DATE,
    soporte_ipv4          BOOLEAN       NOT NULL DEFAULT TRUE,
    soporte_ipv6          BOOLEAN       NOT NULL DEFAULT FALSE,
    -- R3 b.5: flags de cumplimiento + PETI
    declaracion_conformidad_sic BOOLEAN NOT NULL DEFAULT FALSE,
    peti_vigencia         VARCHAR(20)   DEFAULT '2024-2027',
    id_peti_referencia    VARCHAR(100),
    created_at            TIMESTAMPTZ   NOT NULL DEFAULT now(),
    updated_at            TIMESTAMPTZ   NOT NULL DEFAULT now(),
    CONSTRAINT chk_se_url_gov  CHECK (url_enmascarada_gov IS NULL OR url_enmascarada_gov ~ '^https://www\.gov\.co/'),
    CONSTRAINT chk_se_estado   CHECK (estado_integracion IN ('pendiente','en_proceso','integrada','activa','suspendida','desactivada')),
    CONSTRAINT chk_se_paso     CHECK (paso_proceso_actual IS NULL OR paso_proceso_actual BETWEEN 1 AND 7)
);

-- ---- sede_fisica (C03, clase A) ---------------------------------------------
CREATE TABLE sede_fisica (
    id                             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre                         VARCHAR(300) NOT NULL,
    direccion                      VARCHAR(500) NOT NULL,
    codigo_postal                  VARCHAR(10),
    municipio                      VARCHAR(150) NOT NULL,
    departamento                   VARCHAR(150) NOT NULL,
    telefono_principal             telefono_co,
    correo_sede                    correo_email,
    horario                        TEXT,
    es_principal                   BOOLEAN  NOT NULL DEFAULT FALSE,
    orden_footer                   SMALLINT UNIQUE,
    tiene_acceso_inclusivo         BOOLEAN  NOT NULL DEFAULT FALSE,
    cantidad_computadores_publicos SMALLINT,
    activa                         BOOLEAN  NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_sf_orden_footer CHECK (orden_footer IS NULL OR orden_footer BETWEEN 1 AND 5),
    CONSTRAINT chk_sf_computadores CHECK (cantidad_computadores_publicos IS NULL OR cantidad_computadores_publicos >= 2)
);

-- ---- tipo_documento_identidad (C34, clase C: PK natural codigo) -------------
CREATE TABLE tipo_documento_identidad (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo            VARCHAR(20) NOT NULL UNIQUE,
    descripcion       VARCHAR(200),
    patron_validacion VARCHAR(200),
    CONSTRAINT chk_tdi_codigo CHECK (codigo IN ('CC','NUIP','CE','NIT','Pasaporte','TI','PEP'))
);

-- ---- tipo_pqrsd (C33, catalogo) ---------------------------------------------
CREATE TABLE tipo_pqrsd (
    id_tipo_pqrsd     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo            VARCHAR(40)  NOT NULL UNIQUE,
    nombre            VARCHAR(200) NOT NULL,
    plazo_dias_habiles INTEGER     NOT NULL,
    es_prorrogable    BOOLEAN      NOT NULL DEFAULT TRUE,
    dias_prorroga_max INTEGER,
    es_gratuita       BOOLEAN      NOT NULL DEFAULT TRUE,
    naturaleza_dias   VARCHAR(20)  NOT NULL DEFAULT 'habiles',
    descripcion       TEXT,
    CONSTRAINT chk_tp_codigo CHECK (codigo IN ('PETICION','PETICION_INFORMACION','PETICION_DOCUMENTOS','CONSULTA','QUEJA','RECLAMO','DENUNCIA','PETICION_ENTRE_AUTORIDADES','URGENTE')),
    CONSTRAINT chk_tp_plazo  CHECK (plazo_dias_habiles > 0),
    CONSTRAINT chk_tp_prorr  CHECK (dias_prorroga_max IS NULL OR dias_prorroga_max >= 0),
    CONSTRAINT chk_tp_natur  CHECK (naturaleza_dias = 'habiles')
);

-- ---- tipo_arco (extraida, clase C: PK natural arco_type) --------------------
CREATE TABLE tipo_arco (
    arco_type        VARCHAR(20) PRIMARY KEY,
    plazo_base_dias  INTEGER NOT NULL,
    dias_prorroga_max INTEGER NOT NULL,
    CONSTRAINT chk_ta_tipo CHECK (arco_type IN ('acceso','rectificacion','cancelacion','oposicion'))
);

-- ---- calendario_habil (C40, clase C: PK natural fecha) ----------------------
CREATE TABLE calendario_habil (
    fecha            DATE PRIMARY KEY,
    es_habil         BOOLEAN  NOT NULL,
    motivo_no_habil  VARCHAR(30),
    descripcion      VARCHAR(300),
    anio             SMALLINT NOT NULL,
    tipo_festivo     VARCHAR(20),
    CONSTRAINT chk_ch_motivo  CHECK (motivo_no_habil IN ('sabado','domingo','festivo_nacional','festivo_distrital','ninguno')),
    CONSTRAINT chk_ch_festivo CHECK (tipo_festivo IN ('nacional','distrital','ninguno'))
);

-- ---- categoria_cookie (C09) -------------------------------------------------
CREATE TABLE categoria_cookie (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT,
    es_esencial BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---- categoria_dato_sensible (C73, catalogo) --------------------------------
CREATE TABLE categoria_dato_sensible (
    id                        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code                      VARCHAR(40)  NOT NULL UNIQUE,
    label                     VARCHAR(200) NOT NULL,
    requires_explicit_consent BOOLEAN NOT NULL DEFAULT TRUE,
    legal_basis               VARCHAR(200) DEFAULT '[LEY:Ley 1581/2012 art.5]',
    created_at                TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_cds_code CHECK (code IN ('health','biometric','ethnic_origin','political','sexual_orientation','religion','union_membership'))
);

-- ---- grupo_interes (C45, clase A) -------------------------------------------
CREATE TABLE grupo_interes (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code        VARCHAR(40)  NOT NULL UNIQUE,
    label       VARCHAR(200) NOT NULL,
    descripcion TEXT,
    -- R3 b.7: caracterizacion DAFP
    caracterizacion              TEXT,
    tiene_caracterizacion_formal BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT chk_gi_code CHECK (code IN ('NNA','mujeres','discapacidad','adultos_mayores','etnicos','LGBTIQ+'))
);

-- ---- arquetipo (C60) --------------------------------------------------------
CREATE TABLE arquetipo (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre        VARCHAR(200) NOT NULL,
    edad          SMALLINT,
    ubicacion     VARCHAR(200),
    nivel_digital VARCHAR(20),
    necesidades   TEXT,
    objetivos     TEXT,
    frustraciones TEXT,
    descripcion   TEXT,
    CONSTRAINT chk_arq_nivel CHECK (nivel_digital IN ('bajo','medio','alto'))
);

-- ---- ronda_sus (C58) --------------------------------------------------------
CREATE TABLE ronda_sus (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_ronda     VARCHAR(200) NOT NULL,
    fecha_inicio     DATE,
    fecha_fin        DATE,
    objetivo         TEXT,
    num_participantes INTEGER,
    estado           VARCHAR(20) NOT NULL DEFAULT 'abierta',
    CONSTRAINT chk_rs_estado CHECK (estado IN ('abierta','cerrada'))
);

-- ---- formato_abierto (C101, clase C) ----------------------------------------
CREATE TABLE formato_abierto (
    id        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code      VARCHAR(20)  NOT NULL UNIQUE,
    nombre    VARCHAR(100) NOT NULL,
    mime_type VARCHAR(100),
    es_abierto BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_fa_code CHECK (code IN ('CSV','XML','RDF','RSS','JSON','ODF','WMS','WFS'))
);

-- ---- licencia_datos (C102, clase C) -----------------------------------------
CREATE TABLE licencia_datos (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    code                 VARCHAR(40)  NOT NULL UNIQUE,
    nombre               VARCHAR(200) NOT NULL,
    url                  VARCHAR(500),
    permite_reutilizacion BOOLEAN NOT NULL DEFAULT TRUE,
    descripcion          TEXT
);

-- ---- criterio_ita (C107) ----------------------------------------------------
CREATE TABLE criterio_ita (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nivel_ita     SMALLINT     NOT NULL,
    nombre        VARCHAR(300) NOT NULL,
    categoria     VARCHAR(30),
    descripcion   TEXT,
    es_bloqueante BOOLEAN NOT NULL DEFAULT FALSE,
    peso          NUMERIC(5,2),
    CONSTRAINT chk_ci_nivel     CHECK (nivel_ita BETWEEN 1 AND 10),
    CONSTRAINT chk_ci_categoria CHECK (categoria IN ('accesibilidad','transparencia','formato'))
);

-- ---- permiso (C76, RBAC) ----------------------------------------------------
CREATE TABLE permiso (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo      VARCHAR(100) NOT NULL UNIQUE,
    modulo      VARCHAR(100),
    descripcion VARCHAR(300)
);

-- ---- rol (C75, clase A: PK natural nombre_rol; discriminador H8) ------------
CREATE TABLE rol (
    id                         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_rol                 VARCHAR(100) NOT NULL UNIQUE,
    descripcion                VARCHAR(300),
    nivel                      SMALLINT,
    ambito                     VARCHAR(20)  NOT NULL,
    requiere_mfa               BOOLEAN NOT NULL DEFAULT FALSE,
    es_sistema                 BOOLEAN NOT NULL DEFAULT FALSE,
    puede_crear                BOOLEAN NOT NULL DEFAULT FALSE,
    puede_aprobar              BOOLEAN NOT NULL DEFAULT FALSE,
    puede_administrar_usuarios BOOLEAN NOT NULL DEFAULT FALSE,
    es_rol_cms_interno         BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT chk_rol_nivel  CHECK (nivel IS NULL OR nivel >= 1),
    CONSTRAINT chk_rol_ambito CHECK (ambito IN ('ciudadano','interno','externo'))
);

-- ---- bloque_digitalizacion (C17) --------------------------------------------
CREATE TABLE bloque_digitalizacion (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    numero_bloque   SMALLINT NOT NULL,
    descripcion     TEXT,
    criterio_demanda TEXT,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_bd_bloque CHECK (numero_bloque BETWEEN 1 AND 3)
);

-- ---- fase_digitalizacion (C18) ----------------------------------------------
CREATE TABLE fase_digitalizacion (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_fase VARCHAR(200) NOT NULL,
    orden       SMALLINT
);

-- ---- clave_idempotencia (C23) -----------------------------------------------
CREATE TABLE clave_idempotencia (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    token            VARCHAR(128) NOT NULL UNIQUE,
    tipo_operacion   VARCHAR(20)  NOT NULL,
    fecha_creacion   TIMESTAMPTZ  NOT NULL DEFAULT now(),
    fecha_expiracion TIMESTAMPTZ,
    CONSTRAINT chk_cidem_op CHECK (tipo_operacion IN ('pago','radicado'))
);

-- ---- agenda_regulatoria (C47) -----------------------------------------------
CREATE TABLE agenda_regulatoria (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    vigencia_fiscal  SMALLINT,
    descripcion      TEXT,
    fecha_publicacion DATE,
    url_archivo      VARCHAR(500)
);

-- ---- impuesto (C117, clase A: clave natural {nombre, vigencia_desde}) -------
CREATE TABLE impuesto (
    id                       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre                   VARCHAR(200) NOT NULL,
    vigencia_desde           SMALLINT     NOT NULL,
    sujeto_activo            VARCHAR(300) NOT NULL,
    sujeto_pasivo            VARCHAR(300) NOT NULL,
    hecho_generador          TEXT         NOT NULL,
    hecho_imponible          TEXT         NOT NULL,
    causacion                TEXT         NOT NULL,
    base_gravable            TEXT         NOT NULL,
    tarifa                   TEXT         NOT NULL,
    proceso_recaudo          TEXT         NOT NULL,
    url_formulario_liquidacion VARCHAR(500),
    vigencia_hasta           SMALLINT,
    CONSTRAINT uq_impuesto_nombre_vig UNIQUE (nombre, vigencia_desde)
);

-- ---- interop_external_system (C90, clase C) ---------------------------------
CREATE TABLE interop_external_system (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    system_key        VARCHAR(40)  NOT NULL UNIQUE,
    system_name       VARCHAR(300),
    responsible_entity VARCHAR(300),
    contact_email     correo_email,
    integration_type  VARCHAR(30),
    module_reference  VARCHAR(100),
    base_url          VARCHAR(500),
    is_integrated     BOOLEAN NOT NULL DEFAULT FALSE,
    integration_notes TEXT,
    CONSTRAINT chk_ies_key CHECK (system_key IN ('SUIT','SIGEP','SECOP_I','SECOP_II','SGDEA','REGISTRADURIA_ANI','REGISTRADURIA_SIRC','REGISTRADURIA_ABIS','RUNT','RUAF','RUT','ONAC','GSE','AND','SUCOP','KOGUI','SAMI')),
    CONSTRAINT chk_ies_inttype CHECK (integration_type IN ('XROAD','DIRECT','API_REST','API_SOAP','OIDC','SECOP'))
);

-- ---- interop_server_environment (C92) ---------------------------------------
CREATE TABLE interop_server_environment (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    env_type             VARCHAR(20) NOT NULL,
    nombre               VARCHAR(200),
    url_servidor         VARCHAR(500),
    lci_certified        BOOLEAN NOT NULL DEFAULT FALSE,
    docker_standalone    BOOLEAN NOT NULL DEFAULT FALSE,
    version_xroad        VARCHAR(50),
    ip_address           INET,
    estado               VARCHAR(20) NOT NULL DEFAULT 'activo',
    fecha_instalacion    DATE,
    certificado_instalado BOOLEAN NOT NULL DEFAULT FALSE,
    responsable          VARCHAR(300),
    created_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_ise_env    CHECK (env_type IN ('QA','PREPROD','PROD')),
    CONSTRAINT chk_ise_estado CHECK (estado IN ('activo','inactivo')),
    CONSTRAINT chk_ise_docker CHECK (docker_standalone = FALSE OR env_type IN ('QA','PREPROD'))
);

-- ---- interop_xroad_member (C80) ---------------------------------------------
CREATE TABLE interop_xroad_member (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    member_code         VARCHAR(100) NOT NULL UNIQUE,
    member_class        VARCHAR(10)  NOT NULL,
    member_name         VARCHAR(300),
    instance_identifier VARCHAR(20)  DEFAULT 'CO',
    is_own_entity       BOOLEAN NOT NULL DEFAULT FALSE,
    responsible_entity  VARCHAR(300),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_ixm_class CHECK (member_class IN ('GOV','PRIV'))
);

-- ---- interop_ccd_service (C87) ----------------------------------------------
CREATE TABLE interop_ccd_service (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    endpoint_nombre     VARCHAR(300),
    url                 VARCHAR(500),
    metodo_http         VARCHAR(10),
    info_classification VARCHAR(20) NOT NULL DEFAULT 'PUBLIC',
    payload_schema      JSONB,
    version             VARCHAR(50),
    activo              BOOLEAN NOT NULL DEFAULT TRUE,
    descripcion         TEXT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_iccd_class CHECK (info_classification = 'PUBLIC')
);

-- ---- interop_and_agreement (C89) --------------------------------------------
CREATE TABLE interop_and_agreement (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo_acuerdo   VARCHAR(30),
    numero_acuerdo VARCHAR(100) NOT NULL UNIQUE,
    fecha_firma    DATE,
    estado         VARCHAR(20),
    objeto         TEXT,
    vigencia_desde DATE,
    vigencia_hasta DATE,
    url_documento  VARCHAR(500),
    id_responsable UUID,  -- FK->usuario_interno (clase B UUID); FK agregada via ALTER al final
    created_at     TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_iaa_tipo   CHECK (tipo_acuerdo IN ('entendimiento','vinculacion')),
    CONSTRAINT chk_iaa_estado CHECK (estado IN ('vigente','vencido','en_tramite'))
);

-- ---- variable_caracterizacion (C142, R3.1) ----------------------------------
CREATE TABLE variable_caracterizacion (
    id_variable     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dimension       VARCHAR(20)  NOT NULL,
    nombre_variable VARCHAR(120) NOT NULL,
    aplica_a        VARCHAR(10)  NOT NULL,
    CONSTRAINT chk_vc_dimension CHECK (dimension IN ('geografica','demografica','intrinseca','comportamiento','relacional','organizacional')),
    CONSTRAINT chk_vc_aplica    CHECK (aplica_a IN ('natural','juridica','ambos')),
    CONSTRAINT uq_vc_nombre     UNIQUE (dimension, nombre_variable)
);

-- =============================================================================
-- 3.1 TABLAS - NUCLEO DE ACTORES E IDENTIDAD
-- (FK declaradas por ALTER al final para evitar ciclos / forward refs)
-- =============================================================================

-- ---- usuario_interno (C61, clase B: UUID PK) --------------------------------
CREATE TABLE usuario_interno (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_tipo_documento   BIGINT       NOT NULL,
    numero_documento    VARCHAR(20)  NOT NULL,
    nombre_completo     VARCHAR(200) NOT NULL,
    correo              correo_email NOT NULL,
    contrasena_hash     VARCHAR(255) NOT NULL,
    contrasena_temporal BOOLEAN      NOT NULL DEFAULT TRUE,
    mfa_habilitado      BOOLEAN      NOT NULL DEFAULT FALSE,
    estado              VARCHAR(20)  NOT NULL DEFAULT 'activo',
    is_active           BOOLEAN      NOT NULL DEFAULT TRUE,
    failed_login_count  INTEGER      NOT NULL DEFAULT 0,
    locked_until        TIMESTAMPTZ,
    deactivated_at      TIMESTAMPTZ,   -- alias logico: fecha_baja (RN-12-D02)
    acepto_tyc          BOOLEAN      NOT NULL,
    fecha_acepto_tyc    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    id_firma_registro   UUID,          -- FK->firma_electronica
    fecha_nacimiento    DATE,
    id_sigep            VARCHAR(50),   -- FK->servidor_publico(codigo_sigep)
    created_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_ui_documento UNIQUE (id_tipo_documento, numero_documento),
    CONSTRAINT uq_ui_correo    UNIQUE (correo),
    CONSTRAINT chk_ui_estado   CHECK (estado IN ('activo','suspendido','dado_de_baja')),
    CONSTRAINT chk_ui_failed   CHECK (failed_login_count >= 0),
    CONSTRAINT chk_ui_tyc      CHECK (acepto_tyc = TRUE)
);

-- ---- servidor_publico (C116, clase A: clave natural codigo_sigep) -----------
CREATE TABLE servidor_publico (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_sigep         VARCHAR(50)  NOT NULL UNIQUE,
    nombre_completo      VARCHAR(300) NOT NULL,
    cargo                VARCHAR(200),
    correo_institucional correo_email UNIQUE,
    telefono             telefono_co,
    extension            VARCHAR(20),
    id_dependencia       BIGINT,   -- FK->dependencia(id_dependencia)
    activo               BOOLEAN  NOT NULL DEFAULT TRUE,
    fecha_vinculacion    DATE,
    fecha_desvinculacion DATE
);

-- ---- dependencia (C136 / §1.1, clase A: surrogate BIGINT + UNIQUE(codigo)) --
-- Politica fisica R2 §A: PK id_dependencia BIGINT IDENTITY; codigo VARCHAR(20)
-- UNIQUE; nombre UNIQUE. FK genericas entrantes -> id_dependencia (BIGINT).
-- Unica excepcion: radicado.prefijo_dependencia -> dependencia(codigo).
CREATE TABLE dependencia (
    id_dependencia         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo                 VARCHAR(20)  NOT NULL UNIQUE,
    nombre                 VARCHAR(300) NOT NULL UNIQUE,
    descripcion            TEXT,
    sigla                  VARCHAR(30),
    tipo_unidad            VARCHAR(40)  NOT NULL DEFAULT 'secretaria',
    id_dependencia_padre   BIGINT,   -- self FK
    id_responsable         BIGINT,   -- FK->servidor_publico(id)
    sede_id                BIGINT,   -- FK->sede_fisica(id)
    es_externa             BOOLEAN  NOT NULL DEFAULT FALSE,
    entidad_externa_nombre VARCHAR(300),
    correo_institucional   correo_email,
    telefono               telefono_co,
    ubicacion_fisica       VARCHAR(500),
    horario_atencion       TEXT,
    activa                 BOOLEAN  NOT NULL DEFAULT TRUE,
    created_at             TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at             TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_dep_tipo_unidad CHECK (tipo_unidad IN ('secretaria','oficina','direccion','subdireccion','despacho','grupo','otro')),
    CONSTRAINT chk_dep_no_self     CHECK (id_dependencia_padre IS NULL OR id_dependencia_padre <> id_dependencia),
    CONSTRAINT chk_dep_externa     CHECK (es_externa = FALSE OR entidad_externa_nombre IS NOT NULL)
);

-- ---- ciudadano (C62, clase A; supertipo ISA H2) -----------------------------
CREATE TABLE ciudadano (
    id_ciudadano                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_tipo_documento             BIGINT       NOT NULL,
    numero_documento              VARCHAR(30)  NOT NULL,
    nombre_completo               VARCHAR(300),
    correo                        correo_email,
    telefono                      VARCHAR(30),
    direccion                     VARCHAR(500),
    nivel_confianza               VARCHAR(20),
    auth_source                   VARCHAR(20)  NOT NULL DEFAULT 'local',
    scd_sub                       VARCHAR(255),
    identificador_scd             VARCHAR(100) UNIQUE,
    biometric_enrolled            BOOLEAN      NOT NULL DEFAULT FALSE,
    ani_validated                 BOOLEAN      NOT NULL DEFAULT FALSE,
    ani_validated_at              TIMESTAMPTZ,
    es_persona_juridica           BOOLEAN      NOT NULL DEFAULT FALSE,
    representante_legal_id         BIGINT,     -- self FK
    fecha_nacimiento              DATE,
    is_minor                      BOOLEAN,     -- NO GENERATED (current_date no IMMUTABLE); via vista/trigger
    estado_cuenta                 VARCHAR(20)  NOT NULL DEFAULT 'activa',
    canal_notificacion_preferido  VARCHAR(20),
    direccion_procesal_electronica VARCHAR(254),
    failed_login_count            INTEGER      NOT NULL DEFAULT 0,
    locked_until                  TIMESTAMPTZ,
    id_version_politica           BIGINT,      -- FK->politica_documento(id)
    created_at                    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at                    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT uq_ciud_documento  UNIQUE (id_tipo_documento, numero_documento),
    CONSTRAINT uq_ciud_correo     UNIQUE (correo),
    CONSTRAINT chk_ciud_confianza CHECK (nivel_confianza IS NULL OR nivel_confianza IN ('BAJO','MEDIO','ALTO','MUY_ALTO')),
    CONSTRAINT chk_ciud_authsrc   CHECK (auth_source IN ('local','scd_oidc')),
    CONSTRAINT chk_ciud_estado    CHECK (estado_cuenta IN ('activa','suspendida','eliminada_logica','pendiente_autorizacion')),
    CONSTRAINT chk_ciud_canalnot  CHECK (canal_notificacion_preferido IS NULL OR canal_notificacion_preferido IN ('correo','CCD','SMS','APP')),
    CONSTRAINT chk_ciud_failed    CHECK (failed_login_count >= 0)
);

-- ---- ciudadano_juridica (subtipo class-table clase E, PK=FK al surrogate) ---
CREATE TABLE ciudadano_juridica (
    id_ciudadano           BIGINT PRIMARY KEY,  -- PK=FK->ciudadano(id_ciudadano)
    razon_social           VARCHAR(300) NOT NULL,
    representante_legal_id  BIGINT       -- FK->ciudadano(id_ciudadano)
);

-- ---- politica_documento (C08, clase A: clave natural {tipo, version_number}) -
CREATE TABLE politica_documento (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo                 VARCHAR(30) NOT NULL,
    version_number       INTEGER     NOT NULL,
    titulo               VARCHAR(300),
    url_descarga         VARCHAR(500),
    formato_descarga     VARCHAR(20),
    fecha_vigencia_desde DATE,
    fecha_vigencia_hasta DATE,
    es_vigente           BOOLEAN NOT NULL DEFAULT FALSE,
    marco_legal          TEXT,
    document_hash        VARCHAR(64),
    effective_from       TIMESTAMPTZ,
    published_at         TIMESTAMPTZ,
    expands_scope        BOOLEAN NOT NULL DEFAULT FALSE,
    requires_new_consent BOOLEAN NOT NULL DEFAULT FALSE,
    id_creado_por        UUID,   -- FK->usuario_interno
    CONSTRAINT uq_polidoc_tipo_ver UNIQUE (tipo, version_number),
    CONSTRAINT chk_polidoc_tipo CHECK (tipo IN ('terminos','privacidad','derechos_autor','cookies','accesibilidad'))
);

-- =============================================================================
-- 3.2 MODULO 01 - identidad y estructura (resto)
-- =============================================================================

-- ---- contacto_entidad (C02) -------------------------------------------------
CREATE TABLE contacto_entidad (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_sede              BIGINT NOT NULL,
    conmutador           telefono_co,
    linea_gratuita       VARCHAR(20),
    linea_anticorrupcion VARCHAR(20),
    correo_institucional correo_email,
    correo_judicial      correo_email,
    direccion            VARCHAR(500),
    horario_atencion     TEXT
);

-- ---- red_social (C04) -------------------------------------------------------
CREATE TABLE red_social (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_sede       BIGINT NOT NULL,
    plataforma    VARCHAR(30)  NOT NULL,
    url           VARCHAR(500) NOT NULL,
    nombre_cuenta VARCHAR(200),
    activa        BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_rs_plataforma CHECK (plataforma IN ('facebook','twitter_x','instagram','youtube','tiktok','linkedin'))
);

-- ---- menu_navegacion (C05, self-FK) -----------------------------------------
CREATE TABLE menu_navegacion (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_sede           BIGINT,
    etiqueta          VARCHAR(200) NOT NULL,
    url               VARCHAR(500),
    padre_id          BIGINT,  -- self FK
    nivel             SMALLINT,
    orden             SMALLINT,
    icono             VARCHAR(100),
    abre_nueva_pestana BOOLEAN NOT NULL DEFAULT FALSE,
    visible           BOOLEAN NOT NULL DEFAULT TRUE,
    es_obligatorio    BOOLEAN NOT NULL DEFAULT FALSE,  -- R2 C
    aria_label        VARCHAR(300),                    -- R2 C
    created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_menu_nivel CHECK (nivel IS NULL OR nivel BETWEEN 1 AND 2)
);

-- ---- cookie_catalogo (C10) --------------------------------------------------
CREATE TABLE cookie_catalogo (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_categoria BIGINT       NOT NULL,
    nombre       VARCHAR(200) NOT NULL UNIQUE,
    proveedor    VARCHAR(200),
    proposito    TEXT,
    duracion     VARCHAR(100),
    tipo         VARCHAR(20),
    activa       BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_cc_tipo CHECK (tipo IN ('propia','terceros'))
);

-- ---- consentimiento_datos (C11, append-only, clave temporal) ----------------
CREATE TABLE consentimiento_datos (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_ciudadano          BIGINT,
    identificador_usuario VARCHAR(255),
    id_politica           BIGINT,
    consent_type          VARCHAR(40) NOT NULL,
    action                VARCHAR(20) NOT NULL,
    version_politica      VARCHAR(20),
    categorias_aceptadas  JSONB,
    fecha_consentimiento  TIMESTAMPTZ,
    fecha_expiracion      TIMESTAMPTZ,
    estado                VARCHAR(20),
    policy_hash           VARCHAR(64),
    ip_address            INET,
    id_sesion             UUID,
    is_sensitive_data     BOOLEAN NOT NULL DEFAULT FALSE,
    occurred_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    canal_aceptacion      VARCHAR(50),
    CONSTRAINT chk_consent_type   CHECK (consent_type IN ('general','sensitive_data','cookies_analytics','cookies_marketing')),
    CONSTRAINT chk_consent_action CHECK (action IN ('granted','revoked','updated')),
    CONSTRAINT chk_consent_estado CHECK (estado IS NULL OR estado IN ('activo','caducado','revocado'))
);

-- ---- consentimiento_categoria (C12, asociativa M:N) -------------------------
CREATE TABLE consentimiento_categoria (
    id_consentimiento BIGINT NOT NULL,
    id_categoria      BIGINT NOT NULL,
    decision          VARCHAR(20) NOT NULL,
    CONSTRAINT pk_conscat PRIMARY KEY (id_consentimiento, id_categoria),
    CONSTRAINT chk_conscat_decision CHECK (decision IN ('aceptada','rechazada'))
);

-- ---- dominio_confianza (C13) ------------------------------------------------
CREATE TABLE dominio_confianza (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    dominio       VARCHAR(255) NOT NULL UNIQUE,
    descripcion   TEXT,
    tipo          VARCHAR(20),
    activo        BOOLEAN NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ NOT NULL DEFAULT now(),
    id_creado_por UUID,
    CONSTRAINT chk_dc_tipo CHECK (tipo IN ('gov','aliado','sistema'))
);

-- ---- plan_integracion (C14) -------------------------------------------------
-- R3: descompuesto en plan_integracion_tramite/_dominio/_otro_medio.
-- Los 3 JSONB (tramites_incluidos, dominios_web, otros_medios) NO se materializan aqui.
CREATE TABLE plan_integracion (
    id                          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_sede                     BIGINT,
    version                     VARCHAR(20),
    fecha_creacion              DATE,
    incorporado_peti            BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_envio_gobierno_digital DATE,
    avance_porcentaje           NUMERIC(5,2),
    fecha_ultimo_avance         DATE,
    CONSTRAINT chk_pi_avance CHECK (avance_porcentaje IS NULL OR avance_porcentaje BETWEEN 0 AND 100)
);

-- ---- micrositio (C15) -------------------------------------------------------
CREATE TABLE micrositio (
    id                     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre                 VARCHAR(300),
    url                    VARCHAR(500),
    tipo                   VARCHAR(20),
    id_dependencia_duena   BIGINT,  -- FK->dependencia
    proveedor_tecnologia   VARCHAR(200),
    hosting                VARCHAR(200),
    tiene_login            BOOLEAN NOT NULL DEFAULT FALSE,
    maneja_pagos           BOOLEAN NOT NULL DEFAULT FALSE,
    maneja_datos_personales BOOLEAN NOT NULL DEFAULT FALSE,
    accion_propuesta       VARCHAR(20),
    estado_detectado       VARCHAR(100),
    CONSTRAINT chk_micrositio_tipo   CHECK (tipo IN ('portal','micrositio','app','sistema')),
    CONSTRAINT chk_micrositio_accion CHECK (accion_propuesta IS NULL OR accion_propuesta IN ('converger','enlazar','retirar'))
);

-- =============================================================================
-- 3.3 NUCLEO TRAMITES / RADICACION / PQRSD / EXPEDIENTE / DOCUMENTO
-- =============================================================================

-- ---- tramite (C16, clase A; discriminador ISA tipo_servicio H3) -------------
CREATE TABLE tramite (
    id_tramite                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_suit                   VARCHAR(20)  NOT NULL UNIQUE,
    nombre                        VARCHAR(500) NOT NULL,
    tipo_servicio                 VARCHAR(30)  NOT NULL,
    modalidad                     VARCHAR(30)  NOT NULL,
    descripcion                   TEXT         NOT NULL,
    requisitos                    TEXT         NOT NULL,
    pasos_procedimiento           TEXT         NOT NULL,
    costo                         NUMERIC(18,2) NOT NULL DEFAULT 0,
    es_gratuito                   BOOLEAN GENERATED ALWAYS AS (costo = 0) STORED,
    tiempo_resolucion_dias        INTEGER      NOT NULL,
    resultado_esperado            TEXT         NOT NULL,
    grupo_objetivo                VARCHAR(200),
    nivel_transformacion          SMALLINT     NOT NULL,
    url_digital                   VARCHAR(500),
    georreferenciacion            JSONB,
    estado_estandarizado          VARCHAR(30)  NOT NULL DEFAULT 'EN_DIGITALIZACION',
    nivel_autenticacion_requerido VARCHAR(20)  NOT NULL DEFAULT 'BAJO',
    aplica_sap                    BOOLEAN      NOT NULL DEFAULT FALSE,
    termino_sap_dias              INTEGER,
    tipo_silencio_administrativo  VARCHAR(20)  NOT NULL DEFAULT 'negativo',
    id_bloque_digitalizacion      BIGINT,
    id_fase_digitalizacion_actual BIGINT,
    solicitudes_por_anio          INTEGER,
    fecha_ultima_actualizacion_suit DATE       NOT NULL,
    requiere_concepto_dafp        BOOLEAN      NOT NULL DEFAULT FALSE,
    id_dependencia                BIGINT,
    version_ficha                 INTEGER      NOT NULL DEFAULT 1,
    created_at                    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    updated_at                    TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT chk_tram_suit       CHECK (codigo_suit ~ '^T'),
    CONSTRAINT chk_tram_tiposerv   CHECK (tipo_servicio IN ('TRAMITE','OPA','CONSULTA')),
    CONSTRAINT chk_tram_modalidad  CHECK (modalidad IN ('TOTALMENTE_EN_LINEA','PARCIAL','PRESENCIAL')),
    CONSTRAINT chk_tram_costo      CHECK (costo >= 0),
    CONSTRAINT chk_tram_tiempo     CHECK (tiempo_resolucion_dias > 0),
    CONSTRAINT chk_tram_nivtransf  CHECK (nivel_transformacion BETWEEN 1 AND 6),
    CONSTRAINT chk_tram_estado     CHECK (estado_estandarizado IN ('ACTIVO','EN_DIGITALIZACION','SUSPENDIDO','SUPRIMIDO')),
    CONSTRAINT chk_tram_nivauth    CHECK (nivel_autenticacion_requerido IN ('BAJO','MEDIO','ALTO','MUY_ALTO')),
    CONSTRAINT chk_tram_sap        CHECK (aplica_sap = FALSE OR termino_sap_dias IS NOT NULL),
    CONSTRAINT chk_tram_silencio   CHECK (tipo_silencio_administrativo IN ('negativo','positivo')),
    CONSTRAINT chk_tram_version    CHECK (version_ficha >= 1)
);

-- ---- nivel_auth_tramite (C19) -----------------------------------------------
CREATE TABLE nivel_auth_tramite (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_tramite        BIGINT NOT NULL UNIQUE,
    nivel_requerido   VARCHAR(20) NOT NULL,
    descripcion_riesgo TEXT,
    CONSTRAINT chk_nat_nivel CHECK (nivel_requerido IN ('BAJO','MEDIO','ALTO','MUY_ALTO'))
);

-- ---- expediente_electronico (C112, clase B: UUID PK) ------------------------
CREATE TABLE expediente_electronico (
    id                     UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    numero_expediente      VARCHAR(50) NOT NULL UNIQUE,
    id_solicitud           BIGINT,
    titulo                 VARCHAR(500) NOT NULL,
    id_trd_serie           UUID NOT NULL,
    estado_ciclo_vital     VARCHAR(30) NOT NULL DEFAULT 'apertura',
    folio_actual           INTEGER NOT NULL DEFAULT 0,
    numero_folio_inicio    INTEGER NOT NULL,
    numero_folio_fin       INTEGER,
    indice_firmado         BOOLEAN NOT NULL DEFAULT FALSE,
    id_indice_firma        UUID,
    trd_codigo             VARCHAR(50) NOT NULL,
    metadatos_autenticidad JSONB NOT NULL,
    estado_integridad      VARCHAR(20) NOT NULL DEFAULT 'INTEGRO',
    sgdea_referencia       VARCHAR(200),   -- R1 DELTA ítem 2: NULLABLE hasta sync SGDEA
    id_responsable         UUID NOT NULL,
    fecha_apertura         TIMESTAMPTZ NOT NULL DEFAULT now(),
    fecha_cierre           TIMESTAMPTZ,
    CONSTRAINT chk_exp_estado_cv  CHECK (estado_ciclo_vital IN ('apertura','gestion','cierre','preservacion')),
    CONSTRAINT chk_exp_folio      CHECK (folio_actual >= 0),
    CONSTRAINT chk_exp_integridad CHECK (estado_integridad IN ('INTEGRO','COMPROMETIDO'))
);

-- ---- radicado (C39, clase A) ------------------------------------------------
CREATE TABLE radicado (
    id_radicado            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    numero_radicado        VARCHAR(50) NOT NULL UNIQUE,
    prefijo_dependencia    VARCHAR(20) NOT NULL,  -- FK->dependencia(codigo)
    consecutivo_anual      BIGINT NOT NULL,
    anio                   SMALLINT NOT NULL,
    fecha_hora_radicacion  TIMESTAMPTZ NOT NULL,
    emisor_nombre          VARCHAR(300) NOT NULL,
    emisor_correo          correo_email,
    id_destinatario_interno UUID,
    destinatario_externo   VARCHAR(300),
    tipo_documento         VARCHAR(100) NOT NULL,
    canal_recepcion        VARCHAR(30) NOT NULL,
    acuse_enviado          BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_acuse            TIMESTAMPTZ,
    id_expediente          UUID,
    CONSTRAINT chk_rad_numero      CHECK (numero_radicado ~ '^SM-[A-Z0-9]+-[0-9]{4}-[0-9]{6}$'),
    CONSTRAINT chk_rad_consecutivo CHECK (consecutivo_anual > 0),
    CONSTRAINT chk_rad_canal       CHECK (canal_recepcion IN ('web','correo','presencial','app')),
    CONSTRAINT uq_radicado_consecutivo UNIQUE (prefijo_dependencia, anio, consecutivo_anual)
);

-- ---- solicitud (C20, clase A) -----------------------------------------------
-- R2 B: 7 columnas espejo se MUEVEN a borrador_solicitud/desistimiento/SAP.
-- Se conserva estado, etapa_actual, inicio_gestion. Trigger estado<->existencia 1:1.
CREATE TABLE solicitud (
    id_solicitud              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    numero_radicado           VARCHAR(50) NOT NULL UNIQUE,  -- FK->radicado(numero_radicado)
    id_tramite                BIGINT NOT NULL,
    id_ciudadano              BIGINT,
    id_sesion                 UUID,
    fecha_hora_radicacion     TIMESTAMPTZ NOT NULL,
    estado                    VARCHAR(40) NOT NULL DEFAULT 'BORRADOR',
    etapa_actual              SMALLINT NOT NULL DEFAULT 1,
    tiempo_estimado_resolucion INTEGER,
    fecha_vencimiento         DATE,
    datos_formulario          JSONB NOT NULL,
    autorizo_datos_personales BOOLEAN NOT NULL DEFAULT FALSE,
    acepto_terminos           BOOLEAN NOT NULL DEFAULT FALSE,
    clave_idempotencia        VARCHAR(128) NOT NULL UNIQUE,
    canal_ingreso             VARCHAR(30) NOT NULL DEFAULT 'EN_LINEA',
    inicio_gestion            BOOLEAN NOT NULL DEFAULT FALSE,
    -- solicitud NO se particiona (DELTA fisico D): se omite columna 'anio' derivada
    -- (EXTRACT sobre TIMESTAMPTZ no es IMMUTABLE -> no apta para GENERATED STORED).
    -- Reportes por vigencia: EXTRACT(YEAR FROM fecha_hora_radicacion) on-read.
    created_at                TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at                TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_sol_radicado CHECK (numero_radicado ~ '^SM-[A-Z0-9]+-[0-9]{4}-[0-9]{6}$'),
    CONSTRAINT chk_sol_estado   CHECK (estado IN ('BORRADOR','RADICADO','RECIBIDO','EN_TRAMITE','REQUIERE_SUBSANACION','SUBSANADO','EN_ESPERA_PAGO','RECHAZADO_PAGO','RESUELTO','DESISTIDO','ARCHIVADO','SILENCIO_POSITIVO')),
    CONSTRAINT chk_sol_etapa    CHECK (etapa_actual BETWEEN 1 AND 4),
    CONSTRAINT chk_sol_tiempo   CHECK (tiempo_estimado_resolucion IS NULL OR tiempo_estimado_resolucion > 0),
    CONSTRAINT chk_sol_autoriza CHECK (estado = 'BORRADOR' OR autorizo_datos_personales = TRUE),
    CONSTRAINT chk_sol_acepta   CHECK (estado = 'BORRADOR' OR acepto_terminos = TRUE),
    CONSTRAINT chk_sol_canal    CHECK (canal_ingreso IN ('EN_LINEA','PRESENCIAL_ASISTIDO'))
);

-- ---- pqrsd (C32, clase A) ---------------------------------------------------
CREATE TABLE pqrsd (
    id_pqrsd               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_tipo_pqrsd          BIGINT NOT NULL,
    es_anonima             BOOLEAN NOT NULL DEFAULT FALSE,
    es_identidad_reservada BOOLEAN NOT NULL DEFAULT FALSE,
    id_ciudadano           BIGINT,
    nombre_razon_social    VARCHAR(300),
    id_tipo_documento      BIGINT,
    numero_documento       VARCHAR(30),
    correo                 correo_email NOT NULL,
    telefono               VARCHAR(20),
    direccion_notificacion TEXT,
    canal_respuesta        VARCHAR(50) NOT NULL,
    id_dependencia         BIGINT NOT NULL,
    objeto                 VARCHAR(2000) NOT NULL,
    acepta_condiciones     BOOLEAN NOT NULL,
    acepta_privacidad      BOOLEAN NOT NULL,
    id_radicado            BIGINT NOT NULL UNIQUE,
    estado                 VARCHAR(30) NOT NULL DEFAULT 'radicada',
    fecha_hora_recepcion   TIMESTAMPTZ NOT NULL,
    fecha_estimada_respuesta DATE,
    id_expediente          UUID,
    ip_origen              INET,
    CONSTRAINT chk_pqrsd_nombre  CHECK (es_anonima OR nombre_razon_social IS NOT NULL),
    CONSTRAINT chk_pqrsd_tipodoc CHECK (es_anonima OR id_tipo_documento IS NOT NULL),
    CONSTRAINT chk_pqrsd_canal   CHECK (canal_respuesta IN ('correo','SMS','correo_certificado','fisico','edicto')),
    CONSTRAINT chk_pqrsd_objeto  CHECK (length(objeto) <= 2000),
    CONSTRAINT chk_pqrsd_cond    CHECK (acepta_condiciones = TRUE),
    CONSTRAINT chk_pqrsd_priv    CHECK (acepta_privacidad = TRUE),
    CONSTRAINT chk_pqrsd_estado  CHECK (estado IN ('radicada','en_tramite','prorrogada','trasladada','respondida','cerrada'))
);

-- =============================================================================
-- 3.4 DOCUMENTOS / FIRMA / SESION / NOTIFICACION / AUDITORIA / SEGURIDAD
-- =============================================================================

-- ---- documento_electronico (C113, clase B UUID; arco exclusivo H6) ----------
CREATE TABLE documento_electronico (
    id                          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_expediente               UUID,
    id_solicitud                BIGINT,
    id_pqrsd                    BIGINT,
    contexto                    VARCHAR(20)  NOT NULL,
    nombre_original             VARCHAR(500) NOT NULL,
    tipo_documental             VARCHAR(100),
    mime_type_declarado         VARCHAR(100) NOT NULL,
    mime_type_real              VARCHAR(100) NOT NULL,
    tamano_bytes                BIGINT       NOT NULL,
    hash_integridad             VARCHAR(128) NOT NULL,
    ruta_almacenamiento         VARCHAR(1000) NOT NULL,
    estado_antivirus            VARCHAR(20)  NOT NULL DEFAULT 'pendiente',
    es_valido                   BOOLEAN GENERATED ALWAYS AS (mime_type_declarado = mime_type_real AND estado_antivirus <> 'infectado') STORED,
    tipo_origen                 VARCHAR(20)  NOT NULL,
    es_carga_manual_excepcion   BOOLEAN      NOT NULL DEFAULT FALSE,
    id_falla_interop            BIGINT,
    id_requerimiento            BIGINT,
    numero_folio                INTEGER,
    autenticidad                BOOLEAN NOT NULL DEFAULT TRUE,
    integridad                  BOOLEAN NOT NULL DEFAULT TRUE,
    fiabilidad                  BOOLEAN NOT NULL DEFAULT TRUE,
    disponibilidad              BOOLEAN NOT NULL DEFAULT TRUE,
    firmado                     BOOLEAN NOT NULL DEFAULT FALSE,
    id_firma                    UUID,
    eliminacion_solicitada      BOOLEAN NOT NULL DEFAULT FALSE,
    id_eliminacion_aprobada_por UUID,
    id_creado_por               UUID,
    fecha_carga                 TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_doc_contexto  CHECK (contexto IN ('TRAMITE','PQRSD','EXPEDIENTE','RESULTADO')),
    CONSTRAINT chk_doc_tamano    CHECK (tamano_bytes > 0),
    CONSTRAINT chk_doc_antivirus CHECK (estado_antivirus IN ('pendiente','limpio','infectado')),
    CONSTRAINT chk_doc_origen    CHECK (tipo_origen IN ('CIUDADANO','ENTIDAD','SUBSANACION')),
    CONSTRAINT chk_doc_excepcion CHECK (NOT es_carga_manual_excepcion OR id_falla_interop IS NOT NULL),
    CONSTRAINT chk_doc_subsanac  CHECK (tipo_origen <> 'SUBSANACION' OR id_requerimiento IS NOT NULL),
    -- Arco exclusivo (H6, §4.2): exactamente una FK no nula segun contexto
    CONSTRAINT chk_doc_arco CHECK (
        (contexto = 'TRAMITE'   AND id_solicitud IS NOT NULL AND id_pqrsd IS NULL AND id_expediente IS NULL) OR
        (contexto = 'PQRSD'     AND id_pqrsd IS NOT NULL AND id_solicitud IS NULL AND id_expediente IS NULL) OR
        (contexto IN ('EXPEDIENTE','RESULTADO') AND id_expediente IS NOT NULL AND id_solicitud IS NULL AND id_pqrsd IS NULL)
    )
);

-- ---- firma_electronica (C111, clase B UUID; arco exclusivo H12) -------------
CREATE TABLE firma_electronica (
    id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_documento         UUID,
    id_expediente_indice UUID,
    id_usuario_registro  UUID,
    id_firmante          UUID NOT NULL,
    certificado_serial   VARCHAR(100) NOT NULL,
    certificado_emisor   VARCHAR(300) NOT NULL,
    algoritmo            VARCHAR(30) NOT NULL,
    timestamp_firma      TIMESTAMPTZ NOT NULL DEFAULT now(),
    resultado_ocsp       VARCHAR(20) NOT NULL,
    hash_documento       VARCHAR(128) NOT NULL,
    firma_valor          TEXT NOT NULL,
    efectos_juridicos    BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_fe_algoritmo CHECK (algoritmo IN ('RSA-SHA256','ECDSA-SHA256')),
    CONSTRAINT chk_fe_ocsp      CHECK (resultado_ocsp IN ('valido','revocado','desconocido')),
    CONSTRAINT chk_fe_arco CHECK (
        (CASE WHEN id_documento IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN id_expediente_indice IS NOT NULL THEN 1 ELSE 0 END) +
        (CASE WHEN id_usuario_registro IS NOT NULL THEN 1 ELSE 0 END) = 1
    ),
    CONSTRAINT uq_fe_defensiva UNIQUE (hash_documento, timestamp_firma, id_firmante)
);

-- ---- token_oidc (C66, UUID PK) ----------------------------------------------
CREATE TABLE token_oidc (
    id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_ciudadano       BIGINT NOT NULL,
    authorization_code VARCHAR(500),
    id_token           VARCHAR(2000) UNIQUE,
    access_token       VARCHAR(2000),
    refresh_token      VARCHAR(2000),
    client_id          VARCHAR(255),
    client_secret_hash VARCHAR(255),
    state              VARCHAR(255),
    nonce              VARCHAR(255),
    trust_level        VARCHAR(20),
    issued_at          TIMESTAMPTZ,
    expires_at         TIMESTAMPTZ,
    revoked_at         TIMESTAMPTZ,
    scope              VARCHAR(500),
    created_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_oidc_trust CHECK (trust_level IS NULL OR trust_level IN ('BAJO','MEDIO','ALTO','MUY_ALTO'))
);

-- ---- sesion (C64, clase B UUID; arco exclusivo actor H1) --------------------
CREATE TABLE sesion (
    id                     UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_type              VARCHAR(20) NOT NULL,
    id_usuario_interno     UUID,
    id_ciudadano           BIGINT,
    csrf_token             VARCHAR(255) NOT NULL,
    ip_address             INET NOT NULL,
    user_agent             VARCHAR(500),
    id_oidc_token          UUID,
    nivel_confianza_sesion VARCHAR(20),
    created_at             TIMESTAMPTZ NOT NULL DEFAULT now(),
    expires_at             TIMESTAMPTZ NOT NULL DEFAULT (now() + INTERVAL '900 seconds'),
    last_activity_at       TIMESTAMPTZ NOT NULL DEFAULT now(),
    invalidated_at         TIMESTAMPTZ,
    invalidation_reason    VARCHAR(30),
    estado_sesion          VARCHAR(20) NOT NULL DEFAULT 'activa',
    CONSTRAINT chk_ses_usertype CHECK (user_type IN ('internal','citizen')),
    CONSTRAINT chk_ses_confianza CHECK (nivel_confianza_sesion IS NULL OR nivel_confianza_sesion IN ('BAJO','MEDIO','ALTO','MUY_ALTO')),
    CONSTRAINT chk_ses_invreason CHECK (invalidation_reason IS NULL OR invalidation_reason IN ('logout','password_reset','token_revoked','timeout')),
    CONSTRAINT chk_ses_estado    CHECK (estado_sesion IN ('activa','expirada','revocada')),
    CONSTRAINT chk_ses_arco CHECK (
        (user_type = 'internal' AND id_usuario_interno IS NOT NULL AND id_ciudadano IS NULL) OR
        (user_type = 'citizen'  AND id_ciudadano IS NOT NULL AND id_usuario_interno IS NULL)
    )
);

-- ---- notificacion (C109, clase B UUID; PARTICIONADA RANGE por mes) ----------
-- Polimorfica hibrida H11: entidad_id TEXT (admite UUID clase B y BIGINT clase A).
CREATE TABLE notificacion (
    id                       UUID NOT NULL DEFAULT gen_random_uuid(),
    entidad_tipo             VARCHAR(50) NOT NULL,
    entidad_id               VARCHAR(255) NOT NULL,  -- DELTA R1 A: TEXT/VARCHAR (UUID y BIGINT)
    tipo                     VARCHAR(50) NOT NULL,
    id_acto_referencia       UUID,
    id_destinatario_usuario  UUID,
    id_destinatario_ciudadano BIGINT,
    destinatario             VARCHAR(300) NOT NULL,
    canal                    VARCHAR(30) NOT NULL,
    es_electronica_autorizada BOOLEAN NOT NULL DEFAULT FALSE,
    estado                   VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    constancia_envio         TEXT,
    plazo_notificacion_horas SMALLINT,
    autorizado_canal_alterno BOOLEAN NOT NULL DEFAULT FALSE,
    canal_alterno            VARCHAR(20),
    fecha_envio              TIMESTAMPTZ,
    fecha_entrega            TIMESTAMPTZ,
    fecha_creacion           TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT pk_notificacion PRIMARY KEY (id, fecha_creacion),
    CONSTRAINT chk_notif_entidad CHECK (entidad_tipo IN ('PQRSD','TRAMITE','CITA','CONTENIDO','ACTO')),
    CONSTRAINT chk_notif_tipo    CHECK (tipo IN ('acuse_recibo','respuesta','prorroga','traslado','alerta_vencimiento','acto_administrativo','cambio_estado','control_interno')),
    CONSTRAINT chk_notif_canal   CHECK (canal IN ('correo','SMS','correo_certificado','fisico','edicto','CCD','push_app','gestor_documental')),
    CONSTRAINT chk_notif_estado  CHECK (estado IN ('pendiente','enviada','entregada','fallida','rebotada','cancelada')),
    CONSTRAINT chk_notif_plazo   CHECK (plazo_notificacion_horas IS NULL OR plazo_notificacion_horas >= 0)
) PARTITION BY RANGE (fecha_creacion);
-- Particiones de ejemplo (mensual)
CREATE TABLE notificacion_2026_06 PARTITION OF notificacion
    FOR VALUES FROM ('2026-06-01') TO ('2026-07-01');
CREATE TABLE notificacion_2026_07 PARTITION OF notificacion
    FOR VALUES FROM ('2026-07-01') TO ('2026-08-01');

-- ---- log_auditoria (C68, clase D; append-only; PARTICIONADA RANGE por mes) --
CREATE TABLE log_auditoria (
    id                 BIGINT GENERATED ALWAYS AS IDENTITY,
    event_type         VARCHAR(100) NOT NULL,
    actor_type         VARCHAR(20)  NOT NULL,
    id_usuario_interno UUID,
    id_ciudadano       BIGINT,
    id_sesion          UUID,
    ip_address         INET NOT NULL,
    accion_detalle     VARCHAR(255) NOT NULL,
    componente         VARCHAR(100),
    entidad_tipo       VARCHAR(50),
    entidad_id         VARCHAR(255),
    resultado          VARCHAR(20)  NOT NULL,
    detalle            JSONB,
    previous_hash      VARCHAR(64)  NOT NULL,
    record_hash        VARCHAR(64)  NOT NULL,
    version_politica   VARCHAR(20),
    occurred_at        TIMESTAMPTZ  NOT NULL DEFAULT now(),
    retencion_hasta    DATE,
    CONSTRAINT pk_log_auditoria PRIMARY KEY (id, occurred_at),
    CONSTRAINT uq_log_record_hash UNIQUE (record_hash, occurred_at),
    CONSTRAINT chk_log_actortype CHECK (actor_type IN ('internal_user','citizen','system','anonymous')),
    CONSTRAINT chk_log_resultado CHECK (resultado IN ('success','failure','blocked','rejected')),
    CONSTRAINT chk_log_arco CHECK (
        (actor_type = 'internal_user' AND id_usuario_interno IS NOT NULL) OR
        (actor_type = 'citizen'       AND id_ciudadano IS NOT NULL) OR
        (actor_type IN ('system','anonymous') AND id_usuario_interno IS NULL AND id_ciudadano IS NULL)
    ),
    CONSTRAINT chk_log_retencion CHECK (retencion_hasta IS NULL OR retencion_hasta >= (occurred_at::date + INTERVAL '5 years'))
) PARTITION BY RANGE (occurred_at);
CREATE TABLE log_auditoria_2026_06 PARTITION OF log_auditoria
    FOR VALUES FROM ('2026-06-01') TO ('2026-07-01');
CREATE TABLE log_auditoria_2026_07 PARTITION OF log_auditoria
    FOR VALUES FROM ('2026-07-01') TO ('2026-08-01');

-- ---- intento_login (C71, clase D; PARTICIONADA RANGE por mes) ---------------
CREATE TABLE intento_login (
    id               BIGINT GENERATED ALWAYS AS IDENTITY,
    id_usuario       UUID,
    id_ciudadano     BIGINT,
    correo_intentado VARCHAR(254),
    ip_address       INET,
    resultado        VARCHAR(20) NOT NULL,
    user_agent       VARCHAR(500),
    contador_fallos  INTEGER,
    occurred_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT pk_intento_login PRIMARY KEY (id, occurred_at),
    CONSTRAINT chk_il_resultado CHECK (resultado IN ('exito','fallido','bloqueado'))
) PARTITION BY RANGE (occurred_at);
CREATE TABLE intento_login_2026_06 PARTITION OF intento_login
    FOR VALUES FROM ('2026-06-01') TO ('2026-07-01');
CREATE TABLE intento_login_2026_07 PARTITION OF intento_login
    FOR VALUES FROM ('2026-07-01') TO ('2026-08-01');

-- ---- incidente_seguridad (C69, clase B UUID) --------------------------------
CREATE TABLE incidente_seguridad (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    classification      VARCHAR(20),
    title               VARCHAR(300),
    detected_at         TIMESTAMPTZ,
    id_detected_by      UUID,
    impact_description  TEXT,
    affects_personal_data BOOLEAN NOT NULL DEFAULT FALSE,
    csirt_reported_at   TIMESTAMPTZ,
    sic_notified_at     TIMESTAMPTZ,
    status              VARCHAR(20) NOT NULL DEFAULT 'detected',
    resolved_at         TIMESTAMPTZ,
    post_incident_report TEXT,
    nivel_gravedad      VARCHAR(20),
    -- R3 b.2: doble destino de reporte SIC/RNBD
    sic_rnbd_reportado  BOOLEAN NOT NULL DEFAULT FALSE,
    sic_reporte_fecha   TIMESTAMPTZ,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_inc_class    CHECK (classification IS NULL OR classification IN ('leve','grave','muy_grave')),
    CONSTRAINT chk_inc_status   CHECK (status IN ('detected','classifying','mitigating','resolved','reported')),
    CONSTRAINT chk_inc_csirt    CHECK (classification IS NULL OR classification NOT IN ('grave','muy_grave') OR csirt_reported_at IS NOT NULL),
    CONSTRAINT chk_inc_sic_fecha CHECK (sic_rnbd_reportado = FALSE OR sic_reporte_fecha IS NOT NULL)
);

-- =============================================================================
-- 3.5 MODULO 09 - seguridad (resto) + 03 servicios (hijas de solicitud)
-- =============================================================================

-- ---- mfa_enrollment (C65) ---------------------------------------------------
CREATE TABLE mfa_enrollment (
    id                 BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario         UUID NOT NULL UNIQUE,
    metodo             VARCHAR(20) NOT NULL,
    secreto_cifrado    BYTEA,
    verificado         BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_enrolamiento TIMESTAMPTZ,
    ultimo_uso         TIMESTAMPTZ,
    CONSTRAINT chk_mfa_metodo CHECK (metodo IN ('totp','sms','email'))
);

-- ---- token_recuperacion (C67) -----------------------------------------------
CREATE TABLE token_recuperacion (
    id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario UUID NOT NULL,
    token      VARCHAR(255) NOT NULL UNIQUE,
    usado      BOOLEAN NOT NULL DEFAULT FALSE,
    expira_en  TIMESTAMPTZ NOT NULL DEFAULT (now() + INTERVAL '15 minutes'),
    created_at TIMESTAMPTZ NOT NULL DEFAULT now()
);

-- ---- accion_incidente (C70, historico) --------------------------------------
CREATE TABLE accion_incidente (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_incidente  UUID NOT NULL,
    secuencia     SMALLINT NOT NULL,
    tipo_accion   VARCHAR(30) NOT NULL,
    descripcion   TEXT,
    fecha_accion  TIMESTAMPTZ,
    CONSTRAINT uq_acc_inc UNIQUE (id_incidente, secuencia),
    CONSTRAINT chk_acc_tipo CHECK (tipo_accion IN ('bloqueo_ip','parche','restauracion','otro'))
);

-- ---- backup_log (C72) -------------------------------------------------------
CREATE TABLE backup_log (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo_backup         VARCHAR(20) NOT NULL,
    fecha_inicio        TIMESTAMPTZ,
    fecha_fin           TIMESTAMPTZ,
    estado              VARCHAR(20) NOT NULL,
    tamano_bytes        BIGINT,
    integridad_verificada BOOLEAN NOT NULL DEFAULT FALSE,
    cifrado             BOOLEAN NOT NULL DEFAULT FALSE,
    ubicacion           VARCHAR(500),
    retencion_hasta     DATE,
    id_ejecutor         UUID,
    CONSTRAINT chk_bk_tipo   CHECK (tipo_backup IN ('completo','incremental','diferencial')),
    CONSTRAINT chk_bk_estado CHECK (estado IN ('exitoso','fallido','parcial'))
);

-- ---- dato_personal_sensible (C63, 4FN, clave compuesta) ---------------------
CREATE TABLE dato_personal_sensible (
    id_ciudadano  BIGINT NOT NULL,
    id_categoria  BIGINT NOT NULL,
    categoria     VARCHAR(40),
    valor_cifrado BYTEA,
    fecha_registro TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT pk_dps PRIMARY KEY (id_ciudadano, id_categoria)
);

-- ---- solicitud_arco (C74) ---------------------------------------------------
CREATE TABLE solicitud_arco (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    radicado              VARCHAR(50) UNIQUE,
    id_ciudadano          BIGINT,
    arco_type             VARCHAR(20) NOT NULL,   -- FK->tipo_arco(arco_type)
    description           TEXT,
    supporting_docs       JSONB,
    status                VARCHAR(20) NOT NULL DEFAULT 'received',
    acknowledgement_sent_at TIMESTAMPTZ,
    -- deadline_at: derivado (received_at + plazo_base sobre calendario_habil) -> vista, NO base
    id_handler            UUID,
    response_text         TEXT,
    received_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    closed_at             TIMESTAMPTZ,
    CONSTRAINT chk_sarco_type   CHECK (arco_type IN ('acceso','rectificacion','cancelacion','oposicion')),
    CONSTRAINT chk_sarco_status CHECK (status IN ('received','in_progress','answered','closed'))
);

-- ---- borrador_solicitud (C21) -----------------------------------------------
CREATE TABLE borrador_solicitud (
    id                       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_ciudadano             BIGINT,
    id_tramite               BIGINT NOT NULL,
    datos_parciales          JSONB,
    paso_actual              SMALLINT,
    version_ficha_origen     INTEGER,
    fecha_creacion           TIMESTAMPTZ NOT NULL DEFAULT now(),
    fecha_expiracion         TIMESTAMPTZ DEFAULT (now() + INTERVAL '30 days'),
    fecha_ultima_modificacion TIMESTAMPTZ,
    clave_idempotencia       VARCHAR(128) UNIQUE
);

-- ---- pago (C22) -------------------------------------------------------------
-- monto = snapshot inmutable (= tramite.costo al radicar), validado por trigger.
CREATE TABLE pago (
    id                     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud           BIGINT NOT NULL,
    clave_idempotencia_pago VARCHAR(128) NOT NULL UNIQUE,
    monto                  NUMERIC(18,2) NOT NULL,
    medio_pago             VARCHAR(30),
    estado                 VARCHAR(20) NOT NULL DEFAULT 'PENDIENTE',
    referencia_pasarela    VARCHAR(255),
    comprobante_url        VARCHAR(500),
    fecha_inicio           TIMESTAMPTZ,
    fecha_confirmacion     TIMESTAMPTZ,
    intentos_reconsulta    SMALLINT NOT NULL DEFAULT 0,
    fecha_ultima_reconsulta TIMESTAMPTZ,
    id_token_idempotencia  BIGINT,
    CONSTRAINT chk_pago_monto  CHECK (monto >= 0),
    CONSTRAINT chk_pago_medio  CHECK (medio_pago IN ('PSE','TARJETA_DEBITO','TARJETA_CREDITO')),
    CONSTRAINT chk_pago_estado CHECK (estado IN ('PENDIENTE','APROBADO','RECHAZADO','FALLIDO','REEMBOLSADO')),
    CONSTRAINT chk_pago_confirm CHECK (estado <> 'APROBADO' OR fecha_confirmacion IS NOT NULL)
);

-- ---- requerimiento_subsanacion (C24) ----------------------------------------
CREATE TABLE requerimiento_subsanacion (
    id                       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud             BIGINT NOT NULL,
    descripcion_requerimiento TEXT,
    documentos_requeridos    JSONB,
    fecha_requerimiento      TIMESTAMPTZ,
    fecha_limite             DATE,
    estado                   VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    fecha_subsanacion        TIMESTAMPTZ,
    id_funcionario           UUID,
    plazo_dias               SMALLINT,
    notificado               BOOLEAN NOT NULL DEFAULT FALSE,
    nueva_fecha_vencimiento_tramite DATE,   -- R2 C (RN-03-D05)
    CONSTRAINT chk_rsub_estado CHECK (estado IN ('pendiente','subsanado','vencido'))
);

-- ---- desistimiento (C25) ----------------------------------------------------
CREATE TABLE desistimiento (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud        BIGINT NOT NULL UNIQUE,
    motivo              TEXT,
    fecha_desistimiento TIMESTAMPTZ,
    estado_tramite_antes VARCHAR(40),
    aplica_reembolso    BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---- silencio_administrativo_positivo (C26) ---------------------------------
CREATE TABLE silencio_administrativo_positivo (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud        BIGINT NOT NULL UNIQUE,
    fecha_configuracion TIMESTAMPTZ,
    termino_vencido_dias INTEGER,
    acto_ficto_emitido  BOOLEAN NOT NULL DEFAULT FALSE,
    id_documento_ficto  UUID
);

-- ---- resultado_tramite (C28) ------------------------------------------------
CREATE TABLE resultado_tramite (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud          BIGINT NOT NULL UNIQUE,
    tipo_acto             VARCHAR(30),
    resumen               TEXT,
    url_carpeta_ciudadana VARCHAR(500),
    estado_publicacion_ccd VARCHAR(20),
    fecha_publicacion_ccd  TIMESTAMPTZ,
    id_documento_resultado UUID,
    fecha_emision         TIMESTAMPTZ,
    CONSTRAINT chk_rt_tipo   CHECK (tipo_acto IN ('ACTO_ADMINISTRATIVO','CERTIFICADO','CONSTANCIA','PAZ_Y_SALVO','LICENCIA','OTRO')),
    CONSTRAINT chk_rt_estado CHECK (estado_publicacion_ccd IN ('PENDIENTE','PUBLICADO','EN_COLA','FALLIDO'))
);

-- ---- retroalimentacion (C29) ------------------------------------------------
CREATE TABLE retroalimentacion (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud BIGINT NOT NULL,
    momento     VARCHAR(20),
    valoracion  VARCHAR(20),
    comentario  TEXT,
    fecha       TIMESTAMPTZ,
    CONSTRAINT chk_retro_momento CHECK (momento IN ('inicio','final')),
    CONSTRAINT chk_retro_valor   CHECK (valoracion IN ('FACIL','DIFICIL'))
);

-- ---- encuesta_experiencia (C30) ---------------------------------------------
-- R2 B: respuesta_pregunta_1/2/3 -> respuesta_encuesta. Quedan estrellas/comentario.
CREATE TABLE encuesta_experiencia (
    id          BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud BIGINT NOT NULL,
    estrellas   SMALLINT,
    comentario  TEXT,
    fecha       TIMESTAMPTZ,
    CONSTRAINT chk_enc_estrellas CHECK (estrellas IS NULL OR estrellas BETWEEN 1 AND 5)
);

-- ---- pregunta_encuesta (C, R2 condicional P-13) -----------------------------
CREATE TABLE pregunta_encuesta (
    id_pregunta BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    texto       VARCHAR(500) NOT NULL,
    orden       SMALLINT,
    id_tramite  BIGINT,
    activa      BOOLEAN NOT NULL DEFAULT TRUE
);

-- ---- respuesta_encuesta (R2, clave compuesta) -------------------------------
CREATE TABLE respuesta_encuesta (
    id_encuesta     BIGINT   NOT NULL,
    numero_pregunta SMALLINT NOT NULL,
    valor_respuesta TEXT,
    id_pregunta     BIGINT,
    CONSTRAINT pk_respenc PRIMARY KEY (id_encuesta, numero_pregunta),
    CONSTRAINT chk_respenc_num CHECK (numero_pregunta BETWEEN 1 AND 3)
);

-- ---- evaluacion_sus (C59) ---------------------------------------------------
-- R2 B: item_1..item_10 -> respuesta_item_sus. puntaje_sus -> vista (no base).
CREATE TABLE evaluacion_sus (
    id                 BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_ronda           BIGINT NOT NULL,
    id_arquetipo       BIGINT,
    tarea_evaluada     VARCHAR(300),
    tasa_exito         NUMERIC(5,2),
    conforme           BOOLEAN,
    fecha_evaluacion   TIMESTAMPTZ,
    id_participante    VARCHAR(200),
    observaciones      TEXT,
    tiempo_tarea_segundos INTEGER,
    dispositivo        VARCHAR(100),
    navegador          VARCHAR(100),
    created_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT now(),
    version_instrumento VARCHAR(50),
    CONSTRAINT chk_sus_tasa CHECK (tasa_exito IS NULL OR tasa_exito BETWEEN 0 AND 100)
);

-- ---- respuesta_item_sus (=item_evaluacion_sus, clave compuesta) -------------
CREATE TABLE respuesta_item_sus (
    id_evaluacion   BIGINT   NOT NULL,
    numero_item     SMALLINT NOT NULL,
    respuesta_likert SMALLINT NOT NULL,
    CONSTRAINT pk_ris PRIMARY KEY (id_evaluacion, numero_item),
    CONSTRAINT chk_ris_item   CHECK (numero_item BETWEEN 1 AND 10),
    CONSTRAINT chk_ris_likert CHECK (respuesta_likert BETWEEN 1 AND 5)
);

-- ---- falla_interoperabilidad (C27) ------------------------------------------
CREATE TABLE falla_interoperabilidad (
    id                     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_solicitud           BIGINT NOT NULL,
    servicio_externo       VARCHAR(30),
    descripcion_falla      TEXT,
    intentos_reintento     SMALLINT,
    fallback_manual_habilitado BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_falla            TIMESTAMPTZ,
    fecha_resolucion_falla TIMESTAMPTZ,
    detalle_error          TEXT,
    CONSTRAINT chk_fi_servicio CHECK (servicio_externo IN ('ANI','RUNT','RUAF','REGISTRADURIA','OTRO')),
    CONSTRAINT chk_fi_fallback CHECK (fallback_manual_habilitado = FALSE OR detalle_error IS NOT NULL)
);

-- ---- log_acceso_radicado (C31) ----------------------------------------------
CREATE TABLE log_acceso_radicado (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    numero_radicado VARCHAR(50) NOT NULL,
    id_solicitante BIGINT,
    ip_address     INET,
    resultado      VARCHAR(20),
    fecha_consulta TIMESTAMPTZ NOT NULL DEFAULT now(),
    user_agent     VARCHAR(500),
    CONSTRAINT chk_lar_resultado CHECK (resultado IN ('permitido','denegado_403'))
);

-- =============================================================================
-- 3.6 MODULO 04 PQRSD (hijas) + 05 participacion + 06 canales/citas
-- =============================================================================

-- ---- asignacion_dependencia (C35, historico) --------------------------------
CREATE TABLE asignacion_dependencia (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pqrsd        BIGINT NOT NULL,
    id_dependencia  BIGINT NOT NULL,
    tipo            VARCHAR(30),
    motivo          TEXT,
    fecha_asignacion TIMESTAMPTZ NOT NULL DEFAULT now(),
    id_asignado_por UUID,
    activa          BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_asigdep UNIQUE (id_pqrsd, fecha_asignacion),
    CONSTRAINT chk_asigdep_tipo CHECK (tipo IN ('asignacion_inicial','reasignacion'))
);

-- ---- traslado_competencia (C36) ---------------------------------------------
CREATE TABLE traslado_competencia (
    id                       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pqrsd                 BIGINT NOT NULL,
    id_dependencia_origen    BIGINT NOT NULL,
    autoridad_externa_destino VARCHAR(300),
    motivo                   TEXT,
    fecha_traslado           TIMESTAMPTZ,
    fecha_limite_traslado    DATE,
    con_reserva_identidad    BOOLEAN NOT NULL DEFAULT FALSE,
    notificado_peticionario  BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---- prorroga (C37, historico) ----------------------------------------------
CREATE TABLE prorroga (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pqrsd            BIGINT NOT NULL,
    fecha_registro      TIMESTAMPTZ NOT NULL DEFAULT now(),
    fecha_limite_anterior DATE,
    nueva_fecha_limite  DATE,
    motivo              TEXT,
    dias_ampliados      INTEGER,
    id_funcionario      UUID,
    notificada          BOOLEAN NOT NULL DEFAULT FALSE,
    CONSTRAINT uq_prorroga UNIQUE (id_pqrsd, fecha_registro)
);

-- ---- respuesta_pqrsd (C38) --------------------------------------------------
CREATE TABLE respuesta_pqrsd (
    id                 BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_pqrsd           BIGINT NOT NULL,
    contenido_respuesta TEXT,
    id_documento       UUID,
    fecha_respuesta    TIMESTAMPTZ,
    canal_respuesta    VARCHAR(30),
    cumplimiento_plazo VARCHAR(20),
    id_funcionario     UUID,
    constancia_notificacion TEXT,
    CONSTRAINT chk_rpq_canal  CHECK (canal_respuesta IN ('correo','SMS','correo_certificado','fisico','edicto')),
    CONSTRAINT chk_rpq_cumpl  CHECK (cumplimiento_plazo IN ('dentro_termino','fuera_termino'))
);

-- ---- mecanismo_participacion (C41, supertipo H9) ----------------------------
CREATE TABLE mecanismo_participacion (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    titulo            VARCHAR(300),
    tipo_fase         VARCHAR(30),
    descripcion       TEXT,
    fecha_inicio      DATE,
    fecha_cierre      DATE,
    estado            VARCHAR(20) NOT NULL DEFAULT 'abierta',
    id_dependencia    BIGINT,
    cierre_automatico BOOLEAN NOT NULL DEFAULT FALSE,
    id_oficina_responsable BIGINT,  -- R3 b.3 FK->dependencia(id_dependencia)
    CONSTRAINT chk_mec_fase   CHECK (tipo_fase IN ('diagnostico','formulacion','ejecucion','evaluacion')),
    CONSTRAINT chk_mec_estado CHECK (estado IN ('abierta','cerrada','archivada'))
);

-- ---- proyecto_norma (C42, lado propietario FK circular §4.7) -----------------
CREATE TABLE proyecto_norma (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_normativa        BIGINT,   -- FK->normativa(id_normativa) NULLABLE
    titulo              VARCHAR(500),
    body_text           TEXT,
    fecha_inicio_consulta DATE,
    fecha_limite_comentarios DATE,
    url_sucop           VARCHAR(500),
    estado              VARCHAR(20),
    id_agenda_regulatoria BIGINT,
    CONSTRAINT chk_pn_estado CHECK (estado IN ('en_consulta','aprobado','archivado','abierta','cerrada'))
);

-- ---- aporte_participacion (C43) ---------------------------------------------
CREATE TABLE aporte_participacion (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_mecanismo BIGINT NOT NULL,
    id_ciudadano BIGINT,
    contenido    TEXT,
    fecha_aporte TIMESTAMPTZ,
    estado       VARCHAR(20),
    CONSTRAINT chk_apo_estado CHECK (estado IN ('recibido','publicado','moderado'))
);

-- ---- resultado_participacion (C44) ------------------------------------------
CREATE TABLE resultado_participacion (
    id                     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_mecanismo           BIGINT NOT NULL UNIQUE,
    consolidado_observaciones TEXT,
    respuesta_entidad      TEXT,
    num_aportes            INTEGER,
    fecha_publicacion      DATE,
    url_documento          VARCHAR(500),
    id_responsable         UUID
);

-- ---- micrositio_grupo_interes (C46) -----------------------------------------
CREATE TABLE micrositio_grupo_interes (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_grupo_interes BIGINT NOT NULL,
    titulo           VARCHAR(300),
    url              VARCHAR(500),
    descripcion      TEXT,
    lenguaje_claro   BOOLEAN NOT NULL DEFAULT FALSE,
    cumple_wcag      BOOLEAN NOT NULL DEFAULT FALSE,
    idioma_etnico    VARCHAR(50),
    activo           BOOLEAN NOT NULL DEFAULT TRUE
);

-- ---- grupo_interes_caracterizacion (C143, R3.1) -----------------------------
CREATE TABLE grupo_interes_caracterizacion (
    id_grupo_interes BIGINT NOT NULL,
    id_variable      BIGINT NOT NULL,
    valor            TEXT,
    CONSTRAINT pk_gic PRIMARY KEY (id_grupo_interes, id_variable)
);

-- ---- canal_atencion (C48) ---------------------------------------------------
CREATE TABLE canal_atencion (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo_canal        VARCHAR(40) NOT NULL,
    nombre_canal      VARCHAR(300),
    direccion_fisica  VARCHAR(500),
    codigo_postal     VARCHAR(10),
    telefono          telefono_co,
    correo_electronico correo_email,
    activo            BOOLEAN NOT NULL DEFAULT TRUE,
    id_sede           BIGINT,
    id_dependencia    BIGINT,
    CONSTRAINT chk_canal_tipo CHECK (tipo_canal IN ('presencial','telefonico','correo','chat','notificaciones_judiciales','anticorrupcion'))
);

-- ---- horario_canal (C49, 4FN, clave compuesta) ------------------------------
CREATE TABLE horario_canal (
    id_canal      BIGINT NOT NULL,
    dia           VARCHAR(10) NOT NULL,
    hora_apertura TIME,
    hora_cierre   TIME,
    activo        BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT pk_horcanal PRIMARY KEY (id_canal, dia),
    CONSTRAINT chk_horcanal_dia CHECK (dia IN ('lunes','martes','miercoles','jueves','viernes','sabado','domingo'))
);

-- ---- servicio_agendable (C50) -----------------------------------------------
CREATE TABLE servicio_agendable (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_dependencia       BIGINT NOT NULL,
    nombre_servicio      VARCHAR(300),
    duracion_cita_minutos SMALLINT,
    cupos_por_franja     SMALLINT,
    requiere_documentos  JSONB,
    activo               BOOLEAN NOT NULL DEFAULT TRUE
);

-- ---- franja_horaria (C51, desnormalizacion controlada cupos_disponibles) ----
CREATE TABLE franja_horaria (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_servicio_agendable BIGINT NOT NULL,
    fecha               DATE NOT NULL,
    hora_inicio         TIME NOT NULL,
    hora_fin            TIME NOT NULL,
    cupos_total         SMALLINT NOT NULL,
    cupos_disponibles   SMALLINT NOT NULL,
    estado              VARCHAR(20) NOT NULL DEFAULT 'disponible',
    id_dependencia      BIGINT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_franja_total  CHECK (cupos_total > 0),
    CONSTRAINT chk_franja_disp   CHECK (cupos_disponibles BETWEEN 0 AND cupos_total),
    CONSTRAINT chk_franja_estado CHECK (estado IN ('disponible','agotada','bloqueada'))
);

-- ---- bloqueo_agenda (C52) ---------------------------------------------------
CREATE TABLE bloqueo_agenda (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_servicio_agendable BIGINT NOT NULL,
    fecha_inicio         DATE,
    fecha_fin            DATE,
    motivo               TEXT,
    id_creado_por        UUID,
    CONSTRAINT chk_bloqag_fechas CHECK (fecha_fin IS NULL OR fecha_inicio IS NULL OR fecha_fin >= fecha_inicio)
);

-- ---- cita (C53, self-FK reprogramacion; contacto condicional) ---------------
CREATE TABLE cita (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    codigo_confirmacion UUID NOT NULL UNIQUE DEFAULT gen_random_uuid(),
    id_ciudadano        BIGINT,
    nombre_contacto     VARCHAR(300),
    correo_contacto     correo_email,
    telefono_contacto   VARCHAR(20),
    id_franja           BIGINT,
    id_dependencia      BIGINT,
    fecha_cita          DATE,
    hora_inicio_cita    TIME,
    estado              VARCHAR(20) NOT NULL DEFAULT 'reservada',
    fecha_hora_creacion TIMESTAMPTZ NOT NULL DEFAULT now(),
    fecha_hora_cancelacion TIMESTAMPTZ,
    id_cita_original    BIGINT,  -- self FK
    recordatorio_enviado BOOLEAN NOT NULL DEFAULT FALSE,
    motivo_cancelacion  TEXT,
    id_servicio_agendable BIGINT,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_cita_estado CHECK (estado IN ('reservada','confirmada','reprogramada','cancelada','atendida','no_show')),
    -- R2 B: contacto condicional bidireccional (elimina transitiva)
    CONSTRAINT chk_cita_contacto CHECK (
        (id_ciudadano IS NOT NULL) OR
        (id_ciudadano IS NULL AND correo_contacto IS NOT NULL)
    )
);

-- ---- recordatorio_cita (C54) ------------------------------------------------
CREATE TABLE recordatorio_cita (
    id                     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_cita                BIGINT NOT NULL UNIQUE,
    fecha_envio_programada TIMESTAMPTZ,
    enviado                BOOLEAN NOT NULL DEFAULT FALSE,
    canal                  VARCHAR(20),
    CONSTRAINT chk_reccita_canal CHECK (canal IN ('correo','SMS','push_app'))
);

-- ---- recurso_inclusivo (C55) ------------------------------------------------
CREATE TABLE recurso_inclusivo (
    id           BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_sede      BIGINT NOT NULL,
    tipo_recurso VARCHAR(30),
    cantidad     SMALLINT,
    descripcion  TEXT,
    disponible   BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_recinc_tipo CHECK (tipo_recurso IN ('computador','lector_pantalla','teclado_braille','otro')),
    CONSTRAINT chk_recinc_cant CHECK (cantidad IS NULL OR cantidad >= 2)
);

-- =============================================================================
-- 3.7 MODULO 07 accesibilidad + 12 CMS/documental
-- =============================================================================

-- ---- contenido (C105, clase B UUID; supertipo CMS H7) -----------------------
CREATE TABLE contenido (
    id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tipo                    VARCHAR(30) NOT NULL,
    titulo                  VARCHAR(500),
    cuerpo                  TEXT,
    estado                  VARCHAR(30) NOT NULL DEFAULT 'borrador',
    seccion                 VARCHAR(200),
    modulo_origen           VARCHAR(100),
    id_creado_por           UUID,
    id_aprobado_por         UUID,
    id_rechazado_por        UUID,
    comentario_rechazo      TEXT,
    fecha_publicacion       TIMESTAMPTZ,
    fecha_archivado         TIMESTAMPTZ,
    fecha_vigencia          TIMESTAMPTZ,
    version_actual          INTEGER NOT NULL DEFAULT 1,
    lock_version            INTEGER NOT NULL DEFAULT 0,
    lock_usuario_id         UUID,
    metadatos_json          JSONB,
    tiene_alt_imagen        BOOLEAN NOT NULL DEFAULT FALSE,
    tiene_subtitulos_video  BOOLEAN NOT NULL DEFAULT FALSE,
    url_fuente              VARCHAR(500),
    idioma_origen           VARCHAR(10) NOT NULL DEFAULT 'es',  -- R3 b.6
    fecha_creacion          TIMESTAMPTZ NOT NULL DEFAULT now(),
    fecha_ultima_modificacion TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_cont_tipo   CHECK (tipo IN ('pagina','resolucion','norma','informe','dataset','noticia','elemento_carrusel','otro')),
    CONSTRAINT chk_cont_estado CHECK (estado IN ('borrador','pendiente_aprobacion','publicado','archivado')),
    CONSTRAINT chk_cont_sod    CHECK (id_aprobado_por IS NULL OR id_creado_por IS NULL OR id_aprobado_por <> id_creado_por),
    CONSTRAINT chk_cont_idioma CHECK (idioma_origen ~ '^[a-z]{2,3}(-[A-Za-z0-9]+)?$')
);

-- ---- noticia (C06, subtipo de contenido H7) ---------------------------------
CREATE TABLE noticia (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_contenido     UUID,
    titulo           VARCHAR(150),
    descripcion      VARCHAR(200),
    cuerpo           TEXT,
    imagen_url       VARCHAR(500),
    fecha_publicacion TIMESTAMPTZ,
    autor            VARCHAR(200),
    destacada        BOOLEAN NOT NULL DEFAULT FALSE,
    activa           BOOLEAN NOT NULL DEFAULT TRUE,
    id_sede          BIGINT,
    ratio_imagen     VARCHAR(10),   -- R2 C
    CONSTRAINT chk_not_titulo CHECK (titulo IS NULL OR length(titulo) <= 150),
    CONSTRAINT chk_not_desc   CHECK (descripcion IS NULL OR length(descripcion) <= 200),
    CONSTRAINT chk_not_ratio  CHECK (ratio_imagen IS NULL OR ratio_imagen IN ('4:3','16:9'))
);

-- ---- elemento_carrusel (C07, subtipo de contenido H7) -----------------------
CREATE TABLE elemento_carrusel (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_contenido     UUID,
    titulo           VARCHAR(300),
    imagen_url       VARCHAR(500) NOT NULL,
    enlace_url       VARCHAR(500),
    orden            SMALLINT,
    texto_alternativo VARCHAR(500) NOT NULL,
    activo           BOOLEAN NOT NULL DEFAULT TRUE,
    fecha_inicio     DATE,
    fecha_fin        DATE
);

-- ---- version_contenido (C106, 4FN, clave compuesta + OCC) -------------------
CREATE TABLE version_contenido (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_contenido    UUID NOT NULL,
    version         INTEGER NOT NULL,
    cuerpo_snapshot TEXT,
    id_editor       UUID,
    fecha_edicion   TIMESTAMPTZ,
    comentario      TEXT,
    bloqueado_desde TIMESTAMPTZ,
    CONSTRAINT uq_vercont UNIQUE (id_contenido, version)
);

-- ---- validacion_ita (C108, 4FN, clave compuesta) ----------------------------
CREATE TABLE validacion_ita (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_contenido          UUID NOT NULL,
    id_criterio_ita       BIGINT NOT NULL,
    estado                VARCHAR(20),
    modulo                VARCHAR(100),
    ubicacion_contenido   VARCHAR(500),
    id_responsable        UUID,
    fecha_validacion      TIMESTAMPTZ,
    fecha_ultima_correccion TIMESTAMPTZ,
    notas                 TEXT,
    puntuacion            NUMERIC(5,2),
    avance_pct            NUMERIC(5,2),
    CONSTRAINT uq_valita UNIQUE (id_contenido, id_criterio_ita),
    CONSTRAINT chk_valita_estado CHECK (estado IN ('cumple','incumple'))
);

-- ---- contenido_traduccion (C135, clase F, R3) -------------------------------
CREATE TABLE contenido_traduccion (
    id_contenido     UUID        NOT NULL,
    idioma           VARCHAR(10) NOT NULL,
    titulo_traducido VARCHAR(500),
    cuerpo_traducido TEXT,
    es_original      BOOLEAN     NOT NULL DEFAULT FALSE,
    CONSTRAINT pk_ct PRIMARY KEY (id_contenido, idioma),
    CONSTRAINT chk_ct_idioma CHECK (idioma ~ '^[a-z]{2,3}(-[A-Za-z0-9]+)?$')
);

-- ---- intento_notificacion (C110, historico) ---------------------------------
CREATE TABLE intento_notificacion (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_notificacion UUID NOT NULL,
    numero_intento  SMALLINT NOT NULL,
    resultado       VARCHAR(20),
    canal_usado     VARCHAR(30),
    fecha_intento   TIMESTAMPTZ,
    detalle_error   TEXT,
    CONSTRAINT uq_intnotif UNIQUE (id_notificacion, numero_intento),
    CONSTRAINT chk_intnotif_res CHECK (resultado IN ('exito','rebote','reintento'))
);

-- ---- trd_serie (C114, clase C UUID; adjacency list H10) ---------------------
CREATE TABLE trd_serie (
    id                       UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo_serie             VARCHAR(50) NOT NULL UNIQUE,
    nombre_serie             VARCHAR(300),
    codigo_subserie          VARCHAR(50),
    nombre_subserie          VARCHAR(300),
    padre_id                 UUID,  -- self FK
    tipo_documental          VARCHAR(100),
    retencion_archivo_gestion INTEGER,
    retencion_archivo_central INTEGER,
    disposicion_final        VARCHAR(10),
    CONSTRAINT chk_trd_disp CHECK (disposicion_final IN ('CT','E','MT','S'))
);

-- ---- autorizacion_menor (C115) ----------------------------------------------
CREATE TABLE autorizacion_menor (
    id                 BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_ciudadano_menor BIGINT NOT NULL,
    id_representante   BIGINT NOT NULL,
    parentesco         VARCHAR(50),
    documento_soporte  UUID,
    fecha_autorizacion TIMESTAMPTZ,
    vigente            BOOLEAN NOT NULL DEFAULT TRUE,
    verificada         BOOLEAN NOT NULL DEFAULT FALSE
);

-- ---- preferencia_accesibilidad (C56) ----------------------------------------
CREATE TABLE preferencia_accesibilidad (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_usuario        BIGINT NOT NULL UNIQUE,
    tamano_fuente     VARCHAR(5),
    alto_contraste    BOOLEAN NOT NULL DEFAULT FALSE,
    reducir_movimiento BOOLEAN NOT NULL DEFAULT FALSE,
    updated_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_prefacc_fuente CHECK (tamano_fuente IN ('A','A+','A++'))
);

-- ---- recurso_multimedia_accesible (C57) -------------------------------------
CREATE TABLE recurso_multimedia_accesible (
    id                   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_contenido         UUID NOT NULL UNIQUE,
    content_type         VARCHAR(40),
    has_subtitles        BOOLEAN NOT NULL DEFAULT FALSE,
    subtitles_file_path  VARCHAR(1000),
    has_audio_description BOOLEAN NOT NULL DEFAULT FALSE,
    has_lsc              BOOLEAN NOT NULL DEFAULT FALSE,
    lsc_resource_path    VARCHAR(1000),
    has_transcript       BOOLEAN NOT NULL DEFAULT FALSE,
    transcript_text      TEXT,
    is_live              BOOLEAN NOT NULL DEFAULT FALSE,
    published_at         TIMESTAMPTZ,
    accessibility_status VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    created_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at           TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_rma_ctype  CHECK (content_type IN ('video_general','alocucion_alcalde','emergencia','seguridad_ciudadana','rendicion_cuentas','solo_audio')),
    CONSTRAINT chk_rma_status CHECK (accessibility_status IN ('pendiente','bloqueado','conforme'))
);

-- =============================================================================
-- 3.8 MODULO 10 interoperabilidad (resto) + interop_xroad_transaction (part.)
-- =============================================================================

-- ---- interop_xroad_subsystem (C81) ------------------------------------------
CREATE TABLE interop_xroad_subsystem (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_member      BIGINT NOT NULL,
    subsystem_code VARCHAR(100) NOT NULL,
    subsystem_name VARCHAR(300),
    tipo           VARCHAR(20),
    activo         BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT uq_ixs UNIQUE (id_member, subsystem_code),
    CONSTRAINT chk_ixs_tipo CHECK (tipo IN ('client','provider'))
);

-- ---- interop_xroad_service (C82) --------------------------------------------
CREATE TABLE interop_xroad_service (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_subsystem    BIGINT NOT NULL,
    service_code    VARCHAR(100) NOT NULL,
    service_version VARCHAR(50) NOT NULL,
    protocolo       VARCHAR(20),
    url_definicion  VARCHAR(500),
    descripcion     TEXT,
    estado          VARCHAR(20) NOT NULL DEFAULT 'activo',
    created_at      TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_ixsvc UNIQUE (id_subsystem, service_code, service_version),
    CONSTRAINT chk_ixsvc_proto  CHECK (protocolo IN ('SOAP_WSDL','REST_OPENAPI')),
    CONSTRAINT chk_ixsvc_estado CHECK (estado IN ('activo','inactivo'))
);

-- ---- interop_xroad_service_permission (C83, asociativa M:N) ------------------
CREATE TABLE interop_xroad_service_permission (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_service          BIGINT NOT NULL,
    id_client_subsystem BIGINT NOT NULL,
    concedido           BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_concesion     TIMESTAMPTZ,
    fecha_revocacion    TIMESTAMPTZ,
    id_concedido_por    UUID,
    CONSTRAINT uq_ixsp UNIQUE (id_service, id_client_subsystem)
);

-- ---- interop_xroad_transaction (C84, clase D; PARTICIONADA RANGE por mes) ----
CREATE TABLE interop_xroad_transaction (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY,
    id_service            BIGINT NOT NULL,
    id_client_subsystem   BIGINT NOT NULL,
    id_environment        BIGINT NOT NULL,
    transaction_timestamp TIMESTAMPTZ NOT NULL DEFAULT now(),
    xroad_client_header   TEXT,
    xroad_service_header  TEXT,
    request_hash          VARCHAR(128),
    digital_signature     TEXT,
    tsa_stamp_token       BYTEA,
    tsa_stamp_at          TIMESTAMPTZ,
    status                VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    error_code            VARCHAR(50),
    error_detail          TEXT,
    retry_count           SMALLINT,
    fallback_activated    BOOLEAN NOT NULL DEFAULT FALSE,
    audit_log             JSONB,
    member_class          VARCHAR(10),
    created_at            TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT pk_ixt PRIMARY KEY (id, transaction_timestamp),
    CONSTRAINT chk_ixt_status CHECK (status IN ('PENDING','COMPLETED','FAILED','QUEUED','RETRYING')),
    CONSTRAINT chk_ixt_tsa    CHECK (status <> 'COMPLETED' OR tsa_stamp_token IS NOT NULL)
) PARTITION BY RANGE (transaction_timestamp);
CREATE TABLE interop_xroad_transaction_2026_06 PARTITION OF interop_xroad_transaction
    FOR VALUES FROM ('2026-06-01') TO ('2026-07-01');
CREATE TABLE interop_xroad_transaction_2026_07 PARTITION OF interop_xroad_transaction
    FOR VALUES FROM ('2026-07-01') TO ('2026-08-01');

-- ---- interop_tsa_config (C85, R3 b.4: re-clave + host/puerto, sin url_tsa) ---
CREATE TABLE interop_tsa_config (
    id                  BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    ambiente            VARCHAR(20) NOT NULL,
    vigencia_desde      DATE NOT NULL DEFAULT CURRENT_DATE,
    proveedor           VARCHAR(100) DEFAULT 'GSE',
    algoritmo           VARCHAR(50),
    es_activo           BOOLEAN NOT NULL DEFAULT FALSE,
    id_environment      BIGINT,
    politica_oid        VARCHAR(100),
    firewall_habilitado BOOLEAN NOT NULL DEFAULT FALSE,
    host_salida         VARCHAR(255) NOT NULL DEFAULT 'tsa.gse.com.co',
    puerto              INTEGER NOT NULL DEFAULT 443,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT uq_tsa_ambiente_vig UNIQUE (ambiente, vigencia_desde),
    CONSTRAINT chk_tsa_ambiente CHECK (ambiente IN ('QA','PREPROD','PROD')),
    CONSTRAINT chk_tsa_puerto   CHECK (puerto BETWEEN 1 AND 65535)
);

-- ---- interop_tsa_queue_item (C86) -------------------------------------------
CREATE TABLE interop_tsa_queue_item (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_transaction  BIGINT NOT NULL,
    payload_hash    VARCHAR(128),
    estado          VARCHAR(20) NOT NULL DEFAULT 'pendiente',
    intentos        SMALLINT,
    fecha_encolado  TIMESTAMPTZ,
    fecha_procesado TIMESTAMPTZ,
    error           TEXT,
    prioridad       SMALLINT,
    CONSTRAINT chk_itq_estado CHECK (estado IN ('pendiente','procesado','fallido'))
);

-- ---- carpeta_ciudadana (C88) ------------------------------------------------
CREATE TABLE carpeta_ciudadana (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_mensaje            VARCHAR(100) UNIQUE,
    id_ciudadano          BIGINT,
    tipo_id               VARCHAR(20),
    id_usuario            VARCHAR(100),
    asunto                VARCHAR(300),
    texto_mensaje         TEXT,
    url_descargue_adjuntos VARCHAR(500),
    fecha_mensaje         TIMESTAMPTZ,
    citizen_authorized    BOOLEAN NOT NULL DEFAULT FALSE,
    historial_tramites    JSONB,
    sent_at               TIMESTAMPTZ,
    queried_at            TIMESTAMPTZ
);

-- ---- carpeta_autorizacion_acceso (C141, R3.1) -------------------------------
CREATE TABLE carpeta_autorizacion_acceso (
    id_autorizacion       BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_ciudadano          BIGINT       NOT NULL,
    entidad_autorizada    VARCHAR(255) NOT NULL,
    tipo_entidad          VARCHAR(20),
    conjunto_dato         VARCHAR(150) NOT NULL,
    accion                VARCHAR(20)  NOT NULL,
    fecha_inicio_vigencia DATE         NOT NULL DEFAULT CURRENT_DATE,
    fecha_fin_vigencia    DATE,
    estado                VARCHAR(15)  NOT NULL DEFAULT 'activa',
    fecha_revocacion      TIMESTAMPTZ,
    created_at            TIMESTAMPTZ  NOT NULL DEFAULT now(),
    CONSTRAINT chk_caa_entidad  CHECK (length(entidad_autorizada) > 0),
    CONSTRAINT chk_caa_tipoent  CHECK (tipo_entidad IS NULL OR tipo_entidad IN ('publica','privada','persona')),
    CONSTRAINT chk_caa_conjunto CHECK (length(conjunto_dato) > 0),
    CONSTRAINT chk_caa_accion   CHECK (accion IN ('consultar','compartir','descargar')),
    CONSTRAINT chk_caa_vigencia CHECK (fecha_fin_vigencia IS NULL OR fecha_fin_vigencia >= fecha_inicio_vigencia),
    CONSTRAINT chk_caa_estado   CHECK (estado IN ('activa','revocada','vencida')),
    CONSTRAINT chk_caa_revoc    CHECK (estado <> 'revocada' OR fecha_revocacion IS NOT NULL)
);

-- ---- interop_requirement_mapping (C91) --------------------------------------
CREATE TABLE interop_requirement_mapping (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_tramite        BIGINT NOT NULL,
    requisito         VARCHAR(500),
    id_external_system BIGINT NOT NULL,
    campo_verificable VARCHAR(200),
    verificable_xroad BOOLEAN NOT NULL DEFAULT FALSE,
    descripcion       TEXT,
    activo            BOOLEAN NOT NULL DEFAULT TRUE
);

-- ---- interop_error_log (C93) ------------------------------------------------
CREATE TABLE interop_error_log (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_transaction BIGINT,
    error_code     VARCHAR(50),
    error_message  TEXT,
    severidad      VARCHAR(20),
    componente     VARCHAR(100),
    fecha_error    TIMESTAMPTZ,
    resuelto       BOOLEAN NOT NULL DEFAULT FALSE,
    detalle        JSONB,
    CONSTRAINT chk_iel_sev CHECK (severidad IN ('info','warning','error','critical'))
);

-- ---- interop_lci_certification (C94) ----------------------------------------
CREATE TABLE interop_lci_certification (
    id                 BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_environment     BIGINT NOT NULL,
    nivel              SMALLINT,
    fecha_certificacion DATE,
    fecha_vencimiento  DATE,
    entidad_certificadora VARCHAR(300),
    estado             VARCHAR(20),
    url_certificado    VARCHAR(500),
    CONSTRAINT chk_ilci_nivel  CHECK (nivel = 3),
    CONSTRAINT chk_ilci_estado CHECK (estado IN ('vigente','vencido'))
);

-- ---- certificado_digital (C95, clase C: clave natural serial_number) --------
CREATE TABLE certificado_digital (
    id                BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_environment    BIGINT,
    cert_type         VARCHAR(30),
    ca_provider       VARCHAR(20),
    serial_number     VARCHAR(100) NOT NULL UNIQUE,
    subject_dn        VARCHAR(500),
    issued_at         TIMESTAMPTZ,
    expires_at        TIMESTAMPTZ,
    ocsp_status       VARCHAR(20),
    ocsp_checked_at   TIMESTAMPTZ,
    alert_days_before SMALLINT DEFAULT 30,
    alert_sent_at     TIMESTAMPTZ,
    proveedor         VARCHAR(200),
    tipo_certificado  VARCHAR(30),
    is_active         BOOLEAN NOT NULL DEFAULT TRUE,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT now(),
    CONSTRAINT chk_cert_type     CHECK (cert_type IN ('AUTH','SIGN','TLS','OCSP','firma_electronica')),
    CONSTRAINT chk_cert_ca       CHECK (ca_provider IN ('ONAC','GSE','OTHER')),
    CONSTRAINT chk_cert_ocsp     CHECK (ocsp_status IN ('GOOD','REVOKED','UNKNOWN')),
    CONSTRAINT chk_cert_tipo     CHECK (tipo_certificado IN ('institucional','personal','servidor'))
);

-- =============================================================================
-- 3.9 MODULO 11 datos abiertos
-- =============================================================================

-- ---- registro_activo_informacion (C100) -------------------------------------
CREATE TABLE registro_activo_informacion (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre_activo    VARCHAR(300),
    nivel_criticidad VARCHAR(20),
    id_licencia      BIGINT,
    plan_apertura    TEXT,
    cargado_datos_gov BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_carga      DATE,
    modulo_origen    VARCHAR(30),
    actualizacion_automatica BOOLEAN NOT NULL DEFAULT FALSE,  -- R3 b.8
    CONSTRAINT chk_rai_criticidad CHECK (nivel_criticidad IN ('critico','estrategico','muy_importante')),
    CONSTRAINT chk_rai_modulo     CHECK (modulo_origen IN ('transparencia','datos_abiertos','gestion_documental'))
);

-- ---- dataset (C96, clase A) -------------------------------------------------
-- R3 b.8: metadatos_completos DROPeado (NO base, va por vista v_dataset_publico).
CREATE TABLE dataset (
    id                     BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nombre                 VARCHAR(300) NOT NULL,
    descripcion            TEXT,
    categoria              VARCHAR(150),
    entidad_publicadora    VARCHAR(300),
    fecha_creacion         DATE,
    ultima_actualizacion   DATE,
    id_licencia            BIGINT,
    url_descarga           VARCHAR(500),
    nivel_criticidad       VARCHAR(20),
    frecuencia_actualizacion VARCHAR(20),
    estado                 VARCHAR(20) NOT NULL DEFAULT 'borrador',
    federado_datos_gov     BOOLEAN NOT NULL DEFAULT FALSE,
    id_activo_informacion  BIGINT,
    id_responsable         UUID,
    actualizacion_automatica BOOLEAN NOT NULL DEFAULT FALSE,  -- R3 b.8
    CONSTRAINT uq_dataset_nombre_ent UNIQUE (nombre, entidad_publicadora),
    CONSTRAINT chk_ds_criticidad CHECK (nivel_criticidad IN ('critico','estrategico','muy_importante')),
    CONSTRAINT chk_ds_frecuencia CHECK (frecuencia_actualizacion IN ('diaria','semanal','mensual','trimestral','anual','irregular')),
    CONSTRAINT chk_ds_estado     CHECK (estado IN ('borrador','publicado','desactualizado','despublicado'))
);

-- ---- dataset_version (C97, historico) ---------------------------------------
CREATE TABLE dataset_version (
    id             BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_dataset     BIGINT NOT NULL,
    numero_version INTEGER NOT NULL,
    url_archivo    VARCHAR(500),
    fecha_version  DATE,
    cambios        TEXT,
    tamano_bytes   BIGINT,
    CONSTRAINT uq_dsver UNIQUE (id_dataset, numero_version)
);

-- ---- dataset_metadata (C98, 4FN, clave-valor) -------------------------------
CREATE TABLE dataset_metadata (
    id_dataset BIGINT NOT NULL,
    clave      VARCHAR(150) NOT NULL,
    valor      TEXT,
    tipo_dato  VARCHAR(50),
    CONSTRAINT pk_dsmeta PRIMARY KEY (id_dataset, clave)
);

-- ---- dataset_formato (C99, asociativa M:N) ----------------------------------
CREATE TABLE dataset_formato (
    id_dataset BIGINT NOT NULL,
    id_formato BIGINT NOT NULL,
    CONSTRAINT pk_dsfmt PRIMARY KEY (id_dataset, id_formato)
);

-- ---- alerta_frescura (C103) -------------------------------------------------
CREATE TABLE alerta_frescura (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_dataset      BIGINT NOT NULL,
    fecha_generacion TIMESTAMPTZ,
    dias_vencido    INTEGER,
    estado          VARCHAR(20),
    id_responsable  UUID,
    CONSTRAINT chk_afr_estado CHECK (estado IN ('pendiente','atendida'))
);

-- ---- federacion_externa (C104) ----------------------------------------------
CREATE TABLE federacion_externa (
    id            BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_dataset    BIGINT NOT NULL,
    fecha_intento TIMESTAMPTZ,
    resultado     VARCHAR(20),
    error_detalle TEXT,
    validaciones  JSONB,
    url_destino   VARCHAR(500),
    id_responsable UUID,
    CONSTRAINT chk_fed_resultado CHECK (resultado IN ('exito','fallido'))
);

-- =============================================================================
-- 3.10 MODULO 02 transparencia (supertipo ISA H5 + subtipos) + tributario
-- =============================================================================

-- ---- transparencia_publicacion (C119, supertipo class-table H5) -------------
CREATE TABLE transparencia_publicacion (
    id_publicacion    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    subtipo           VARCHAR(40) NOT NULL,
    fecha_publicacion DATE,
    url_archivo       VARCHAR(500),
    formato_archivo   VARCHAR(20),
    id_publicado_por  BIGINT,   -- FK->servidor_publico
    activo            BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_tpub_subtipo CHECK (subtipo IN ('normativa','plan_adquisiciones','plan_accion','informe_gestion','informe_pqrsd','informe_control_interno','avance_proyecto_inversion','contrato')),
    CONSTRAINT chk_tpub_formato CHECK (formato_archivo IS NULL OR formato_archivo IN ('CSV','XML','RDF','JSON','ODF'))
);

-- ---- normativa (C120, subtipo clase E: PK=FK) -------------------------------
CREATE TABLE normativa (
    id_normativa            BIGINT PRIMARY KEY,  -- PK=FK->transparencia_publicacion
    tipo                    VARCHAR(20),
    numero                  VARCHAR(50),
    fecha_expedicion        DATE,
    fecha_publicacion       DATE,
    epigrafe                TEXT,
    vigencia                VARCHAR(50),
    url_descarga            VARCHAR(500),
    url_suin                VARCHAR(500),
    formato_archivo         VARCHAR(20),
    es_proyecto_norma       BOOLEAN NOT NULL DEFAULT FALSE,
    fecha_limite_comentarios DATE,
    id_agenda_regulatoria   BIGINT,
    id_publicado_por        BIGINT,
    CONSTRAINT chk_norm_tipo CHECK (tipo IN ('decreto','resolucion','acuerdo','circular','ley','directiva')),
    CONSTRAINT chk_norm_proy CHECK (es_proyecto_norma = FALSE OR fecha_limite_comentarios IS NOT NULL)
);

-- ---- plan_adquisiciones (C123, subtipo clase E) -----------------------------
CREATE TABLE plan_adquisiciones (
    id_plan          BIGINT PRIMARY KEY,
    vigencia_fiscal  SMALLINT UNIQUE,
    presupuesto_total NUMERIC(18,2),
    fecha_aprobacion DATE,
    url_documento    VARCHAR(500)
);

-- ---- contrato (C121, subtipo clase E; GENERATED porcentaje/pagos) -----------
CREATE TABLE contrato (
    id_contrato         BIGINT PRIMARY KEY,
    numero_contrato     VARCHAR(100),
    objeto              TEXT,
    monto               NUMERIC(18,2),
    honorarios          NUMERIC(18,2),
    fecha_inicio        DATE,
    fecha_fin           DATE,
    valor_ejecutado     NUMERIC(18,2),
    porcentaje_ejecutado NUMERIC(5,2) GENERATED ALWAYS AS (CASE WHEN monto > 0 THEN round(valor_ejecutado / monto * 100, 2) ELSE 0 END) STORED,
    pagos_realizados    NUMERIC(18,2),
    pagos_pendientes    NUMERIC(18,2) GENERATED ALWAYS AS (monto - pagos_realizados) STORED,
    url_secop           VARCHAR(500),
    tipo_secop          VARCHAR(20),
    id_plan_adquisiciones BIGINT,
    vigencia_fiscal     SMALLINT,
    estado_ejecucion    VARCHAR(30),
    CONSTRAINT chk_contr_monto    CHECK (monto IS NULL OR monto > 0),
    CONSTRAINT chk_contr_ejecut   CHECK (valor_ejecutado IS NULL OR valor_ejecutado >= 0),
    CONSTRAINT chk_contr_secop    CHECK (tipo_secop IN ('SECOP_I','SECOP_II')),
    CONSTRAINT chk_contr_estado   CHECK (estado_ejecucion IN ('en_ejecucion','suspendido','terminado','liquidado','cedido')),
    CONSTRAINT uq_contrato UNIQUE (numero_contrato, vigencia_fiscal)
);

-- ---- otrosi (C122, historico) -----------------------------------------------
CREATE TABLE otrosi (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_contrato     BIGINT NOT NULL,
    numero_otrosi   SMALLINT NOT NULL,
    objeto          TEXT,
    valor_modificacion NUMERIC(18,2),
    fecha           DATE,
    url_documento   VARCHAR(500),
    CONSTRAINT uq_otrosi UNIQUE (id_contrato, numero_otrosi)
);

-- ---- plan_accion (C124, subtipo clase E) ------------------------------------
CREATE TABLE plan_accion (
    id_plan          BIGINT PRIMARY KEY,
    vigencia_fiscal  SMALLINT,
    fecha_publicacion DATE,
    objetivos        TEXT,
    url_documento    VARCHAR(500)
);

-- ---- informe_gestion (C125, subtipo clase E) --------------------------------
CREATE TABLE informe_gestion (
    id_informe       BIGINT PRIMARY KEY,
    vigencia_fiscal  SMALLINT,
    fecha_publicacion DATE,
    resumen          TEXT,
    url_documento    VARCHAR(500)
);

-- ---- informe_pqrsd (C126, subtipo clase E) ----------------------------------
CREATE TABLE informe_pqrsd (
    id_informe          BIGINT PRIMARY KEY,
    trimestre           SMALLINT,
    vigencia_fiscal     SMALLINT,
    fecha_publicacion   DATE,
    total_recibidas     INTEGER,
    total_respondidas   INTEGER,
    total_vencidas      INTEGER,
    promedio_dias_respuesta NUMERIC(8,2),
    url_documento       VARCHAR(500),
    id_responsable      BIGINT,
    observaciones       TEXT,
    url_kogui           VARCHAR(500),       -- R2 C
    tiempo_promedio_respuesta NUMERIC(8,2), -- R2 C
    CONSTRAINT chk_infpq_trim CHECK (trimestre IS NULL OR trimestre BETWEEN 1 AND 4)
);

-- ---- informe_pqrsd_detalle (C127, 4FN, clave compuesta) ---------------------
CREATE TABLE informe_pqrsd_detalle (
    id_informe BIGINT NOT NULL,
    tipo_pqrsd VARCHAR(40) NOT NULL,
    estado     VARCHAR(40) NOT NULL,
    conteo     INTEGER,
    CONSTRAINT pk_ipd PRIMARY KEY (id_informe, tipo_pqrsd, estado)
);

-- ---- informe_control_interno (C128, subtipo clase E) ------------------------
CREATE TABLE informe_control_interno (
    id_informe       BIGINT PRIMARY KEY,
    semestre         SMALLINT,
    vigencia_fiscal  SMALLINT,
    fecha_publicacion DATE,
    url_documento    VARCHAR(500),
    CONSTRAINT chk_infci_sem CHECK (semestre IS NULL OR semestre BETWEEN 1 AND 2)
);

-- ---- avance_proyecto_inversion (C129, subtipo clase E) ----------------------
CREATE TABLE avance_proyecto_inversion (
    id_avance        BIGINT PRIMARY KEY,
    nombre_proyecto  VARCHAR(300),
    trimestre        SMALLINT,
    vigencia_fiscal  SMALLINT,
    porcentaje_avance NUMERIC(5,2),
    presupuesto_ejecutado NUMERIC(18,2),
    fecha_publicacion DATE,
    url_documento    VARCHAR(500),
    CONSTRAINT chk_api_avance CHECK (porcentaje_avance IS NULL OR porcentaje_avance BETWEEN 0 AND 100)
);

-- ---- version_documento_transparencia (C130, historico) ----------------------
CREATE TABLE version_documento_transparencia (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_publicacion  BIGINT NOT NULL,
    version         INTEGER NOT NULL,
    url_permanente  VARCHAR(500),
    url_snapshot    VARCHAR(500),
    fecha_version   DATE,
    motivo_reemplazo TEXT,
    id_responsable  BIGINT,
    CONSTRAINT uq_vdt UNIQUE (id_publicacion, version)
);

-- ---- calendario_tributario (C118) -------------------------------------------
CREATE TABLE calendario_tributario (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_impuesto     BIGINT NOT NULL,
    vigencia_fiscal SMALLINT NOT NULL,
    concepto        VARCHAR(300),
    fecha_vencimiento DATE,
    descuento_pronto_pago NUMERIC(5,2),
    CONSTRAINT uq_caltrib UNIQUE (id_impuesto, vigencia_fiscal)
);

-- ---- alerta_cumplimiento (C131, polimorfica) --------------------------------
CREATE TABLE alerta_cumplimiento (
    id                         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    tipo_publicacion_obligatoria VARCHAR(40),
    entidad_tipo               VARCHAR(50),
    entidad_id                 VARCHAR(255),  -- polimorfico (TEXT por UUID/BIGINT)
    vigencia_fiscal            SMALLINT,
    plazo_legal                DATE,
    dias_anticipacion          SMALLINT,
    norma_citada               VARCHAR(300),
    id_responsable             BIGINT,
    fecha_generacion           TIMESTAMPTZ,
    estado                     VARCHAR(20),
    fecha_cumplimiento         TIMESTAMPTZ,
    CONSTRAINT chk_alcum_tipo   CHECK (tipo_publicacion_obligatoria IN ('plan_accion','informe_gestion','informe_pqrsd','informe_control_interno','avance_inversion','dataset')),
    CONSTRAINT chk_alcum_estado CHECK (estado IN ('pendiente','cumplida','incumplida','escalada'))
);

-- ---- log_integracion (C132) -------------------------------------------------
CREATE TABLE log_integracion (
    id              BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    sistema_externo VARCHAR(20),
    tipo_operacion  VARCHAR(100),
    resultado       VARCHAR(20),
    mensaje         TEXT,
    fecha           TIMESTAMPTZ,
    reintentos      SMALLINT,
    detalle         JSONB,
    CONSTRAINT chk_logint_sis CHECK (sistema_externo IN ('SECOP','SIGEP','SUIN','SUCOP','KOGUI')),
    CONSTRAINT chk_logint_res CHECK (resultado IN ('exito','fallo'))
);

-- ---- sincronizacion_sigep (C133) --------------------------------------------
CREATE TABLE sincronizacion_sigep (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    fecha_sincronizacion  TIMESTAMPTZ,
    estado                VARCHAR(20),
    registros_sincronizados INTEGER,
    alerta_generada       BOOLEAN NOT NULL DEFAULT FALSE,
    mensaje_error         TEXT,
    proxima_sincronizacion TIMESTAMPTZ,
    CONSTRAINT chk_syncsig_estado CHECK (estado IN ('exito','fallo','desactualizado'))
);

-- ---- politica_retencion (C134) ----------------------------------------------
CREATE TABLE politica_retencion (
    id                    BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    entidad_nombre        VARCHAR(300),
    id_categoria_dato     BIGINT,
    periodo_retencion_dias INTEGER,
    accion_vencimiento    VARCHAR(20),
    CONSTRAINT chk_polret_accion CHECK (accion_vencimiento IN ('purgar','anonimizar'))
);

-- =============================================================================
-- 3.11 RBAC asociativas + tablas R3 (planes / rate-limit)
-- =============================================================================

-- ---- rol_permiso (C77, asociativa M:N) --------------------------------------
CREATE TABLE rol_permiso (
    id_rol     BIGINT NOT NULL,
    id_permiso BIGINT NOT NULL,
    CONSTRAINT pk_rolperm PRIMARY KEY (id_rol, id_permiso)
);

-- ---- usuario_rol (C78, asociativa M:N) --------------------------------------
CREATE TABLE usuario_rol (
    id_usuario_interno UUID NOT NULL,
    id_rol             BIGINT NOT NULL,
    fecha_asignacion   TIMESTAMPTZ NOT NULL DEFAULT now(),
    asignado_por       UUID,
    CONSTRAINT pk_usurol PRIMARY KEY (id_usuario_interno, id_rol)
);

-- ---- ciudadano_rol (C79, asociativa M:N) ------------------------------------
CREATE TABLE ciudadano_rol (
    id_ciudadano BIGINT NOT NULL,
    id_rol       BIGINT NOT NULL,
    CONSTRAINT pk_ciudrol PRIMARY KEY (id_ciudadano, id_rol)
);

-- ---- plan_integracion_tramite (PIT, C137, R3 clase F+surrogate) -------------
CREATE TABLE plan_integracion_tramite (
    id               BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    id_plan          BIGINT NOT NULL,
    id_tramite       BIGINT,                  -- nullable: tramite aun no en SUIT
    nombre_tramite   VARCHAR(500),            -- derivado si id_tramite NOT NULL (NO persistir); hecho propio si NULL
    solicitudes_anio INTEGER,
    accion           VARCHAR(20) NOT NULL,
    fecha_objetivo   DATE,
    id_responsable   BIGINT,
    CONSTRAINT chk_pit_nombre CHECK (id_tramite IS NOT NULL OR nombre_tramite IS NOT NULL),
    CONSTRAINT chk_pit_solic  CHECK (solicitudes_anio IS NULL OR solicitudes_anio >= 0),
    CONSTRAINT chk_pit_accion CHECK (accion IN ('converger','enlazar','retirar'))
);

-- ---- plan_integracion_dominio (PID, C138, R3 clase F) -----------------------
CREATE TABLE plan_integracion_dominio (
    id_plan BIGINT        NOT NULL,
    valor   VARCHAR(2048) NOT NULL,
    tipo    VARCHAR(30),
    CONSTRAINT pk_pid PRIMARY KEY (id_plan, valor),
    CONSTRAINT chk_pid_valor CHECK (length(valor) > 0)
);

-- ---- plan_integracion_otro_medio (POM, C139, R3 clase F) --------------------
CREATE TABLE plan_integracion_otro_medio (
    id_plan BIGINT       NOT NULL,
    valor   VARCHAR(500) NOT NULL,
    tipo    VARCHAR(30),
    CONSTRAINT pk_pom PRIMARY KEY (id_plan, valor),
    CONSTRAINT chk_pom_valor CHECK (length(valor) > 0),
    CONSTRAINT chk_pom_tipo  CHECK (tipo IS NULL OR tipo IN ('app','chatbot','pqr','otro'))
);

-- ---- rate_limit_contador (C140, R3 clase F; fillfactor=70 para HOT) ----------
CREATE TABLE rate_limit_contador (
    clave_sujeto    VARCHAR(100) NOT NULL,
    ventana_inicio  TIMESTAMPTZ  NOT NULL,
    recurso         VARCHAR(40)  NOT NULL,
    contador        INTEGER      NOT NULL DEFAULT 0,
    bloqueado_hasta TIMESTAMPTZ,
    CONSTRAINT pk_rlc       PRIMARY KEY (clave_sujeto, ventana_inicio, recurso),
    CONSTRAINT chk_rlc_suj  CHECK (length(clave_sujeto) > 0),
    CONSTRAINT chk_rlc_rec  CHECK (recurso IN ('login','pqrsd','busqueda','radicacion','otro')),
    CONSTRAINT chk_rlc_cont CHECK (contador >= 0)
) WITH (fillfactor = 70);

-- =============================================================================
-- 4. FOREIGN KEYS (declaradas via ALTER para evitar ciclos y forward-refs)
--    Acciones por semantica (modelo logico §5):
--    RESTRICT = catalogos/maestros/legal; CASCADE = composicion;
--    SET NULL = asociacion opcional/auditoria.
-- =============================================================================

-- ---- FK hacia dependencia (surrogate id_dependencia, ON UPDATE RESTRICT) -----
ALTER TABLE dependencia            ADD CONSTRAINT fk_dep_padre        FOREIGN KEY (id_dependencia_padre) REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE dependencia            ADD CONSTRAINT fk_dep_responsable  FOREIGN KEY (id_responsable)       REFERENCES servidor_publico (id)         ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE dependencia            ADD CONSTRAINT fk_dep_sede         FOREIGN KEY (sede_id)              REFERENCES sede_fisica (id)              ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tramite                ADD CONSTRAINT fk_tram_dep         FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE pqrsd                  ADD CONSTRAINT fk_pqrsd_dep        FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE canal_atencion         ADD CONSTRAINT fk_canal_dep        FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE servidor_publico       ADD CONSTRAINT fk_sp_dep           FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE servicio_agendable     ADD CONSTRAINT fk_servag_dep       FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE asignacion_dependencia ADD CONSTRAINT fk_asigdep_dep      FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE traslado_competencia   ADD CONSTRAINT fk_traslado_dep     FOREIGN KEY (id_dependencia_origen) REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE mecanismo_participacion ADD CONSTRAINT fk_mec_dep         FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE mecanismo_participacion ADD CONSTRAINT fk_mec_oficina     FOREIGN KEY (id_oficina_responsable) REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE franja_horaria         ADD CONSTRAINT fk_franja_dep       FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE cita                   ADD CONSTRAINT fk_cita_dep         FOREIGN KEY (id_dependencia)       REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
ALTER TABLE micrositio             ADD CONSTRAINT fk_micrositio_dep   FOREIGN KEY (id_dependencia_duena) REFERENCES dependencia (id_dependencia) ON DELETE RESTRICT ON UPDATE RESTRICT;
-- UNICA FK hacia dependencia(codigo): radicado.prefijo_dependencia (cierra P-08)
ALTER TABLE radicado               ADD CONSTRAINT fk_radicado_dep     FOREIGN KEY (prefijo_dependencia)  REFERENCES dependencia (codigo)          ON DELETE RESTRICT ON UPDATE CASCADE;

-- ---- FK hacia tipo_documento_identidad (RESTRICT) ---------------------------
ALTER TABLE usuario_interno ADD CONSTRAINT fk_ui_tdi   FOREIGN KEY (id_tipo_documento) REFERENCES tipo_documento_identidad (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE ciudadano       ADD CONSTRAINT fk_ciud_tdi FOREIGN KEY (id_tipo_documento) REFERENCES tipo_documento_identidad (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE pqrsd           ADD CONSTRAINT fk_pqrsd_tdi FOREIGN KEY (id_tipo_documento) REFERENCES tipo_documento_identidad (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK self-referenciales y de actores -------------------------------------
ALTER TABLE ciudadano        ADD CONSTRAINT fk_ciud_replegal  FOREIGN KEY (representante_legal_id) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE ciudadano        ADD CONSTRAINT fk_ciud_polver    FOREIGN KEY (id_version_politica)    REFERENCES politica_documento (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE ciudadano_juridica ADD CONSTRAINT fk_cj_ciud      FOREIGN KEY (id_ciudadano)          REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE ciudadano_juridica ADD CONSTRAINT fk_cj_replegal  FOREIGN KEY (representante_legal_id) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE usuario_interno  ADD CONSTRAINT fk_ui_firma       FOREIGN KEY (id_firma_registro)     REFERENCES firma_electronica (id)  ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE usuario_interno  ADD CONSTRAINT fk_ui_sigep       FOREIGN KEY (id_sigep)              REFERENCES servidor_publico (codigo_sigep) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE politica_documento ADD CONSTRAINT fk_polidoc_creador FOREIGN KEY (id_creado_por)       REFERENCES usuario_interno (id)    ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK modulo 01 -----------------------------------------------------------
ALTER TABLE contacto_entidad        ADD CONSTRAINT fk_contacto_sede FOREIGN KEY (id_sede) REFERENCES sede_electronica (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE red_social              ADD CONSTRAINT fk_red_sede      FOREIGN KEY (id_sede) REFERENCES sede_electronica (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE menu_navegacion         ADD CONSTRAINT fk_menu_sede     FOREIGN KEY (id_sede) REFERENCES sede_electronica (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE menu_navegacion         ADD CONSTRAINT fk_menu_padre    FOREIGN KEY (padre_id) REFERENCES menu_navegacion (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE cookie_catalogo         ADD CONSTRAINT fk_cookie_cat    FOREIGN KEY (id_categoria) REFERENCES categoria_cookie (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE consentimiento_datos    ADD CONSTRAINT fk_consent_ciud  FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE consentimiento_datos    ADD CONSTRAINT fk_consent_pol   FOREIGN KEY (id_politica)  REFERENCES politica_documento (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE consentimiento_datos    ADD CONSTRAINT fk_consent_ses   FOREIGN KEY (id_sesion)    REFERENCES sesion (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE consentimiento_categoria ADD CONSTRAINT fk_conscat_cons FOREIGN KEY (id_consentimiento) REFERENCES consentimiento_datos (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE consentimiento_categoria ADD CONSTRAINT fk_conscat_cat  FOREIGN KEY (id_categoria) REFERENCES categoria_cookie (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE dominio_confianza       ADD CONSTRAINT fk_dc_creador    FOREIGN KEY (id_creado_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE plan_integracion        ADD CONSTRAINT fk_pi_sede       FOREIGN KEY (id_sede) REFERENCES sede_electronica (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- ---- FK nucleo tramites / radicacion / pqrsd --------------------------------
ALTER TABLE tramite              ADD CONSTRAINT fk_tram_bloque   FOREIGN KEY (id_bloque_digitalizacion)      REFERENCES bloque_digitalizacion (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE tramite              ADD CONSTRAINT fk_tram_fase     FOREIGN KEY (id_fase_digitalizacion_actual) REFERENCES fase_digitalizacion (id)   ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE nivel_auth_tramite   ADD CONSTRAINT fk_nat_tram      FOREIGN KEY (id_tramite)  REFERENCES tramite (id_tramite) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE expediente_electronico ADD CONSTRAINT fk_exp_sol     FOREIGN KEY (id_solicitud)   REFERENCES solicitud (id_solicitud) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE expediente_electronico ADD CONSTRAINT fk_exp_trd     FOREIGN KEY (id_trd_serie)   REFERENCES trd_serie (id)           ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE expediente_electronico ADD CONSTRAINT fk_exp_indice  FOREIGN KEY (id_indice_firma) REFERENCES firma_electronica (id)  ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE expediente_electronico ADD CONSTRAINT fk_exp_resp    FOREIGN KEY (id_responsable) REFERENCES usuario_interno (id)     ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE radicado             ADD CONSTRAINT fk_rad_destint   FOREIGN KEY (id_destinatario_interno) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE radicado             ADD CONSTRAINT fk_rad_exp       FOREIGN KEY (id_expediente)  REFERENCES expediente_electronico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE solicitud            ADD CONSTRAINT fk_sol_rad       FOREIGN KEY (numero_radicado) REFERENCES radicado (numero_radicado) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE solicitud            ADD CONSTRAINT fk_sol_tram      FOREIGN KEY (id_tramite)     REFERENCES tramite (id_tramite)     ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE solicitud            ADD CONSTRAINT fk_sol_ciud      FOREIGN KEY (id_ciudadano)   REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE solicitud            ADD CONSTRAINT fk_sol_ses       FOREIGN KEY (id_sesion)      REFERENCES sesion (id)              ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE pqrsd                ADD CONSTRAINT fk_pqrsd_tipo    FOREIGN KEY (id_tipo_pqrsd)  REFERENCES tipo_pqrsd (id_tipo_pqrsd) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE pqrsd                ADD CONSTRAINT fk_pqrsd_ciud    FOREIGN KEY (id_ciudadano)   REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE pqrsd                ADD CONSTRAINT fk_pqrsd_rad     FOREIGN KEY (id_radicado)    REFERENCES radicado (id_radicado)   ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE pqrsd                ADD CONSTRAINT fk_pqrsd_exp     FOREIGN KEY (id_expediente)  REFERENCES expediente_electronico (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK documentos / firma / sesion / notificacion / auditoria --------------
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_exp    FOREIGN KEY (id_expediente) REFERENCES expediente_electronico (id) ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_sol    FOREIGN KEY (id_solicitud)  REFERENCES solicitud (id_solicitud)   ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_pqrsd  FOREIGN KEY (id_pqrsd)      REFERENCES pqrsd (id_pqrsd)           ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_falla  FOREIGN KEY (id_falla_interop) REFERENCES falla_interoperabilidad (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_req    FOREIGN KEY (id_requerimiento) REFERENCES requerimiento_subsanacion (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_firma  FOREIGN KEY (id_firma)      REFERENCES firma_electronica (id)    ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_elimap FOREIGN KEY (id_eliminacion_aprobada_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE documento_electronico ADD CONSTRAINT fk_doc_creado FOREIGN KEY (id_creado_por) REFERENCES usuario_interno (id)      ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE firma_electronica ADD CONSTRAINT fk_fe_doc    FOREIGN KEY (id_documento)         REFERENCES documento_electronico (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE firma_electronica ADD CONSTRAINT fk_fe_exp    FOREIGN KEY (id_expediente_indice) REFERENCES expediente_electronico (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE firma_electronica ADD CONSTRAINT fk_fe_usrreg FOREIGN KEY (id_usuario_registro)  REFERENCES usuario_interno (id)      ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE firma_electronica ADD CONSTRAINT fk_fe_firm   FOREIGN KEY (id_firmante)          REFERENCES usuario_interno (id)      ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE firma_electronica ADD CONSTRAINT fk_fe_cert   FOREIGN KEY (certificado_serial)   REFERENCES certificado_digital (serial_number) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE token_oidc ADD CONSTRAINT fk_oidc_ciud FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE sesion ADD CONSTRAINT fk_ses_ui    FOREIGN KEY (id_usuario_interno) REFERENCES usuario_interno (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE sesion ADD CONSTRAINT fk_ses_ciud  FOREIGN KEY (id_ciudadano)       REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE sesion ADD CONSTRAINT fk_ses_oidc  FOREIGN KEY (id_oidc_token)      REFERENCES token_oidc (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE notificacion ADD CONSTRAINT fk_notif_acto FOREIGN KEY (id_acto_referencia)        REFERENCES documento_electronico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE notificacion ADD CONSTRAINT fk_notif_ui   FOREIGN KEY (id_destinatario_usuario)   REFERENCES usuario_interno (id)       ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE notificacion ADD CONSTRAINT fk_notif_ciud FOREIGN KEY (id_destinatario_ciudadano) REFERENCES ciudadano (id_ciudadano)   ON DELETE SET NULL ON UPDATE CASCADE;
-- log_auditoria: arco actor SET NULL (append-only conserva el evento)
ALTER TABLE log_auditoria ADD CONSTRAINT fk_log_ui   FOREIGN KEY (id_usuario_interno) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE log_auditoria ADD CONSTRAINT fk_log_ciud FOREIGN KEY (id_ciudadano)       REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE log_auditoria ADD CONSTRAINT fk_log_ses  FOREIGN KEY (id_sesion)          REFERENCES sesion (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK seguridad (resto) ---------------------------------------------------
ALTER TABLE mfa_enrollment    ADD CONSTRAINT fk_mfa_ui   FOREIGN KEY (id_usuario)   REFERENCES usuario_interno (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE token_recuperacion ADD CONSTRAINT fk_tokrec_ui FOREIGN KEY (id_usuario) REFERENCES usuario_interno (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE accion_incidente  ADD CONSTRAINT fk_acc_inc  FOREIGN KEY (id_incidente) REFERENCES incidente_seguridad (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE incidente_seguridad ADD CONSTRAINT fk_inc_detby FOREIGN KEY (id_detected_by) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE backup_log        ADD CONSTRAINT fk_bk_ejec  FOREIGN KEY (id_ejecutor)  REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE intento_login     ADD CONSTRAINT fk_il_ui    FOREIGN KEY (id_usuario)   REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE intento_login     ADD CONSTRAINT fk_il_ciud  FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE dato_personal_sensible ADD CONSTRAINT fk_dps_ciud FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE dato_personal_sensible ADD CONSTRAINT fk_dps_cat  FOREIGN KEY (id_categoria) REFERENCES categoria_dato_sensible (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE solicitud_arco    ADD CONSTRAINT fk_sarco_ciud FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE solicitud_arco    ADD CONSTRAINT fk_sarco_type FOREIGN KEY (arco_type)    REFERENCES tipo_arco (arco_type) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE solicitud_arco    ADD CONSTRAINT fk_sarco_hand FOREIGN KEY (id_handler)   REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK hijas de solicitud / pqrsd ------------------------------------------
ALTER TABLE borrador_solicitud ADD CONSTRAINT fk_bsol_ciud FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE borrador_solicitud ADD CONSTRAINT fk_bsol_tram FOREIGN KEY (id_tramite)   REFERENCES tramite (id_tramite) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE pago ADD CONSTRAINT fk_pago_sol  FOREIGN KEY (id_solicitud) REFERENCES solicitud (id_solicitud) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE pago ADD CONSTRAINT fk_pago_tok  FOREIGN KEY (id_token_idempotencia) REFERENCES clave_idempotencia (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE requerimiento_subsanacion ADD CONSTRAINT fk_rsub_sol  FOREIGN KEY (id_solicitud)   REFERENCES solicitud (id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE requerimiento_subsanacion ADD CONSTRAINT fk_rsub_func FOREIGN KEY (id_funcionario) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE desistimiento ADD CONSTRAINT fk_desist_sol FOREIGN KEY (id_solicitud) REFERENCES solicitud (id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE silencio_administrativo_positivo ADD CONSTRAINT fk_sap_sol FOREIGN KEY (id_solicitud) REFERENCES solicitud (id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE silencio_administrativo_positivo ADD CONSTRAINT fk_sap_doc FOREIGN KEY (id_documento_ficto) REFERENCES documento_electronico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE resultado_tramite ADD CONSTRAINT fk_rt_sol  FOREIGN KEY (id_solicitud) REFERENCES solicitud (id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE resultado_tramite ADD CONSTRAINT fk_rt_doc  FOREIGN KEY (id_documento_resultado) REFERENCES documento_electronico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE retroalimentacion ADD CONSTRAINT fk_retro_sol FOREIGN KEY (id_solicitud) REFERENCES solicitud (id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE encuesta_experiencia ADD CONSTRAINT fk_enc_sol FOREIGN KEY (id_solicitud) REFERENCES solicitud (id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE pregunta_encuesta ADD CONSTRAINT fk_preg_tram FOREIGN KEY (id_tramite) REFERENCES tramite (id_tramite) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE respuesta_encuesta ADD CONSTRAINT fk_respenc_enc  FOREIGN KEY (id_encuesta) REFERENCES encuesta_experiencia (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE respuesta_encuesta ADD CONSTRAINT fk_respenc_preg FOREIGN KEY (id_pregunta) REFERENCES pregunta_encuesta (id_pregunta) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE evaluacion_sus ADD CONSTRAINT fk_sus_ronda FOREIGN KEY (id_ronda)     REFERENCES ronda_sus (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE evaluacion_sus ADD CONSTRAINT fk_sus_arq   FOREIGN KEY (id_arquetipo) REFERENCES arquetipo (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE respuesta_item_sus ADD CONSTRAINT fk_ris_eval FOREIGN KEY (id_evaluacion) REFERENCES evaluacion_sus (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE falla_interoperabilidad ADD CONSTRAINT fk_fi_sol FOREIGN KEY (id_solicitud) REFERENCES solicitud (id_solicitud) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE log_acceso_radicado ADD CONSTRAINT fk_lar_rad  FOREIGN KEY (numero_radicado) REFERENCES radicado (numero_radicado) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE log_acceso_radicado ADD CONSTRAINT fk_lar_ciud FOREIGN KEY (id_solicitante) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE asignacion_dependencia ADD CONSTRAINT fk_asigdep_pqrsd FOREIGN KEY (id_pqrsd) REFERENCES pqrsd (id_pqrsd) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE asignacion_dependencia ADD CONSTRAINT fk_asigdep_asigpor FOREIGN KEY (id_asignado_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE traslado_competencia ADD CONSTRAINT fk_traslado_pqrsd FOREIGN KEY (id_pqrsd) REFERENCES pqrsd (id_pqrsd) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE prorroga ADD CONSTRAINT fk_prorroga_pqrsd FOREIGN KEY (id_pqrsd) REFERENCES pqrsd (id_pqrsd) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE prorroga ADD CONSTRAINT fk_prorroga_func  FOREIGN KEY (id_funcionario) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE respuesta_pqrsd ADD CONSTRAINT fk_rpq_pqrsd FOREIGN KEY (id_pqrsd) REFERENCES pqrsd (id_pqrsd) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE respuesta_pqrsd ADD CONSTRAINT fk_rpq_doc   FOREIGN KEY (id_documento) REFERENCES documento_electronico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE respuesta_pqrsd ADD CONSTRAINT fk_rpq_func  FOREIGN KEY (id_funcionario) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK participacion / canales / citas -------------------------------------
ALTER TABLE proyecto_norma ADD CONSTRAINT fk_pn_norm   FOREIGN KEY (id_normativa) REFERENCES normativa (id_normativa) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE proyecto_norma ADD CONSTRAINT fk_pn_agenda FOREIGN KEY (id_agenda_regulatoria) REFERENCES agenda_regulatoria (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE aporte_participacion ADD CONSTRAINT fk_apo_mec  FOREIGN KEY (id_mecanismo) REFERENCES mecanismo_participacion (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE aporte_participacion ADD CONSTRAINT fk_apo_ciud FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE resultado_participacion ADD CONSTRAINT fk_rpart_mec  FOREIGN KEY (id_mecanismo) REFERENCES mecanismo_participacion (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE resultado_participacion ADD CONSTRAINT fk_rpart_resp FOREIGN KEY (id_responsable) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE micrositio_grupo_interes ADD CONSTRAINT fk_mgi_grupo FOREIGN KEY (id_grupo_interes) REFERENCES grupo_interes (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE grupo_interes_caracterizacion ADD CONSTRAINT fk_gic_grupo FOREIGN KEY (id_grupo_interes) REFERENCES grupo_interes (id) ON DELETE CASCADE  ON UPDATE RESTRICT;
ALTER TABLE grupo_interes_caracterizacion ADD CONSTRAINT fk_gic_var   FOREIGN KEY (id_variable) REFERENCES variable_caracterizacion (id_variable) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE canal_atencion ADD CONSTRAINT fk_canal_sede FOREIGN KEY (id_sede) REFERENCES sede_fisica (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE horario_canal  ADD CONSTRAINT fk_horcanal_canal FOREIGN KEY (id_canal) REFERENCES canal_atencion (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE franja_horaria ADD CONSTRAINT fk_franja_serv FOREIGN KEY (id_servicio_agendable) REFERENCES servicio_agendable (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE bloqueo_agenda ADD CONSTRAINT fk_bloqag_serv FOREIGN KEY (id_servicio_agendable) REFERENCES servicio_agendable (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE bloqueo_agenda ADD CONSTRAINT fk_bloqag_creado FOREIGN KEY (id_creado_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE cita ADD CONSTRAINT fk_cita_ciud   FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE cita ADD CONSTRAINT fk_cita_franja FOREIGN KEY (id_franja)    REFERENCES franja_horaria (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE cita ADD CONSTRAINT fk_cita_orig   FOREIGN KEY (id_cita_original) REFERENCES cita (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE cita ADD CONSTRAINT fk_cita_serv   FOREIGN KEY (id_servicio_agendable) REFERENCES servicio_agendable (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE recordatorio_cita ADD CONSTRAINT fk_reccita_cita FOREIGN KEY (id_cita) REFERENCES cita (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE recurso_inclusivo ADD CONSTRAINT fk_recinc_sede FOREIGN KEY (id_sede) REFERENCES sede_fisica (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- ---- FK accesibilidad / CMS -------------------------------------------------
ALTER TABLE noticia ADD CONSTRAINT fk_not_cont FOREIGN KEY (id_contenido) REFERENCES contenido (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE noticia ADD CONSTRAINT fk_not_sede FOREIGN KEY (id_sede) REFERENCES sede_electronica (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE elemento_carrusel ADD CONSTRAINT fk_ec_cont FOREIGN KEY (id_contenido) REFERENCES contenido (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE contenido ADD CONSTRAINT fk_cont_creado   FOREIGN KEY (id_creado_por)   REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE contenido ADD CONSTRAINT fk_cont_aprob    FOREIGN KEY (id_aprobado_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE contenido ADD CONSTRAINT fk_cont_rech     FOREIGN KEY (id_rechazado_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE contenido ADD CONSTRAINT fk_cont_lockusr  FOREIGN KEY (lock_usuario_id)  REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE version_contenido ADD CONSTRAINT fk_vc_cont FOREIGN KEY (id_contenido) REFERENCES contenido (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE version_contenido ADD CONSTRAINT fk_vc_editor FOREIGN KEY (id_editor) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE validacion_ita ADD CONSTRAINT fk_valita_cont FOREIGN KEY (id_contenido) REFERENCES contenido (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE validacion_ita ADD CONSTRAINT fk_valita_crit FOREIGN KEY (id_criterio_ita) REFERENCES criterio_ita (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE validacion_ita ADD CONSTRAINT fk_valita_resp FOREIGN KEY (id_responsable) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE contenido_traduccion ADD CONSTRAINT fk_ct_cont FOREIGN KEY (id_contenido) REFERENCES contenido (id) ON DELETE CASCADE ON UPDATE CASCADE;
-- NOTA PG15: 'notificacion' es PARTICIONADA con PK (id, fecha_creacion); una FK a id
-- solo no es declarable (la UNIQUE referenciada debe incluir la clave de particion).
-- Integridad de intento_notificacion->notificacion se valida por trigger de aplicacion
-- (mismo criterio que solicitud/radicado no particionadas en DELTA D).
-- ALTER TABLE intento_notificacion ADD CONSTRAINT fk_intnotif_notif FOREIGN KEY (id_notificacion) REFERENCES notificacion (id) ...;
ALTER TABLE trd_serie ADD CONSTRAINT fk_trd_padre FOREIGN KEY (padre_id) REFERENCES trd_serie (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE autorizacion_menor ADD CONSTRAINT fk_autmen_menor FOREIGN KEY (id_ciudadano_menor) REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE autorizacion_menor ADD CONSTRAINT fk_autmen_repr  FOREIGN KEY (id_representante)   REFERENCES ciudadano (id_ciudadano) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE autorizacion_menor ADD CONSTRAINT fk_autmen_doc   FOREIGN KEY (documento_soporte)  REFERENCES documento_electronico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE preferencia_accesibilidad ADD CONSTRAINT fk_prefacc_ciud FOREIGN KEY (id_usuario) REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE recurso_multimedia_accesible ADD CONSTRAINT fk_rma_cont FOREIGN KEY (id_contenido) REFERENCES contenido (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- ---- FK interoperabilidad ---------------------------------------------------
ALTER TABLE interop_xroad_subsystem ADD CONSTRAINT fk_ixs_member FOREIGN KEY (id_member) REFERENCES interop_xroad_member (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE interop_xroad_service ADD CONSTRAINT fk_ixsvc_sub FOREIGN KEY (id_subsystem) REFERENCES interop_xroad_subsystem (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE interop_xroad_service_permission ADD CONSTRAINT fk_ixsp_svc FOREIGN KEY (id_service) REFERENCES interop_xroad_service (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE interop_xroad_service_permission ADD CONSTRAINT fk_ixsp_cli FOREIGN KEY (id_client_subsystem) REFERENCES interop_xroad_subsystem (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE interop_xroad_service_permission ADD CONSTRAINT fk_ixsp_por FOREIGN KEY (id_concedido_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE interop_xroad_transaction ADD CONSTRAINT fk_ixt_svc FOREIGN KEY (id_service) REFERENCES interop_xroad_service (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE interop_xroad_transaction ADD CONSTRAINT fk_ixt_cli FOREIGN KEY (id_client_subsystem) REFERENCES interop_xroad_subsystem (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE interop_xroad_transaction ADD CONSTRAINT fk_ixt_env FOREIGN KEY (id_environment) REFERENCES interop_server_environment (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE interop_tsa_config ADD CONSTRAINT fk_tsa_env FOREIGN KEY (id_environment) REFERENCES interop_server_environment (id) ON DELETE SET NULL ON UPDATE CASCADE;
-- NOTA PG15: 'interop_xroad_transaction' es PARTICIONADA con PK (id, transaction_timestamp).
-- FK a id solo no declarable; integridad validada por trigger de aplicacion.
-- ALTER TABLE interop_tsa_queue_item ADD CONSTRAINT fk_itq_tx FOREIGN KEY (id_transaction) REFERENCES interop_xroad_transaction (id) ...;
ALTER TABLE carpeta_ciudadana ADD CONSTRAINT fk_cc_ciud FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE carpeta_autorizacion_acceso ADD CONSTRAINT fk_caa_ciudadano FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE ON UPDATE RESTRICT;
ALTER TABLE interop_and_agreement ADD CONSTRAINT fk_iaa_resp FOREIGN KEY (id_responsable) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE interop_requirement_mapping ADD CONSTRAINT fk_irm_tram FOREIGN KEY (id_tramite) REFERENCES tramite (id_tramite) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE interop_requirement_mapping ADD CONSTRAINT fk_irm_ext  FOREIGN KEY (id_external_system) REFERENCES interop_external_system (id) ON DELETE RESTRICT ON UPDATE CASCADE;
-- NOTA PG15: FK a interop_xroad_transaction (particionada) no declarable; trigger de aplicacion.
-- ALTER TABLE interop_error_log ADD CONSTRAINT fk_iel_tx FOREIGN KEY (id_transaction) REFERENCES interop_xroad_transaction (id) ...;
ALTER TABLE interop_lci_certification ADD CONSTRAINT fk_ilci_env FOREIGN KEY (id_environment) REFERENCES interop_server_environment (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE certificado_digital ADD CONSTRAINT fk_cert_env FOREIGN KEY (id_environment) REFERENCES interop_server_environment (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK datos abiertos ------------------------------------------------------
ALTER TABLE registro_activo_informacion ADD CONSTRAINT fk_rai_lic FOREIGN KEY (id_licencia) REFERENCES licencia_datos (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE dataset ADD CONSTRAINT fk_ds_lic  FOREIGN KEY (id_licencia) REFERENCES licencia_datos (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE dataset ADD CONSTRAINT fk_ds_rai  FOREIGN KEY (id_activo_informacion) REFERENCES registro_activo_informacion (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE dataset ADD CONSTRAINT fk_ds_resp FOREIGN KEY (id_responsable) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE dataset_version  ADD CONSTRAINT fk_dsver_ds  FOREIGN KEY (id_dataset) REFERENCES dataset (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE dataset_metadata ADD CONSTRAINT fk_dsmeta_ds FOREIGN KEY (id_dataset) REFERENCES dataset (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE dataset_formato  ADD CONSTRAINT fk_dsfmt_ds  FOREIGN KEY (id_dataset) REFERENCES dataset (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE dataset_formato  ADD CONSTRAINT fk_dsfmt_fmt FOREIGN KEY (id_formato) REFERENCES formato_abierto (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE alerta_frescura  ADD CONSTRAINT fk_afr_ds   FOREIGN KEY (id_dataset) REFERENCES dataset (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE alerta_frescura  ADD CONSTRAINT fk_afr_resp FOREIGN KEY (id_responsable) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE federacion_externa ADD CONSTRAINT fk_fed_ds   FOREIGN KEY (id_dataset) REFERENCES dataset (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE federacion_externa ADD CONSTRAINT fk_fed_resp FOREIGN KEY (id_responsable) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ---- FK transparencia ISA (subtipos PK=FK al supertipo, CASCADE) ------------
ALTER TABLE transparencia_publicacion ADD CONSTRAINT fk_tpub_pubpor FOREIGN KEY (id_publicado_por) REFERENCES servidor_publico (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE normativa ADD CONSTRAINT fk_norm_super  FOREIGN KEY (id_normativa) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE normativa ADD CONSTRAINT fk_norm_agenda FOREIGN KEY (id_agenda_regulatoria) REFERENCES agenda_regulatoria (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE normativa ADD CONSTRAINT fk_norm_pubpor FOREIGN KEY (id_publicado_por) REFERENCES servidor_publico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE plan_adquisiciones ADD CONSTRAINT fk_padq_super FOREIGN KEY (id_plan) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE contrato ADD CONSTRAINT fk_contr_super FOREIGN KEY (id_contrato) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE contrato ADD CONSTRAINT fk_contr_padq  FOREIGN KEY (id_plan_adquisiciones) REFERENCES plan_adquisiciones (id_plan) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE otrosi ADD CONSTRAINT fk_otrosi_contr FOREIGN KEY (id_contrato) REFERENCES contrato (id_contrato) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE plan_accion ADD CONSTRAINT fk_pacc_super FOREIGN KEY (id_plan) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE informe_gestion ADD CONSTRAINT fk_infg_super FOREIGN KEY (id_informe) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE informe_pqrsd ADD CONSTRAINT fk_infpq_super FOREIGN KEY (id_informe) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE informe_pqrsd ADD CONSTRAINT fk_infpq_resp  FOREIGN KEY (id_responsable) REFERENCES servidor_publico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE informe_pqrsd_detalle ADD CONSTRAINT fk_ipd_inf FOREIGN KEY (id_informe) REFERENCES informe_pqrsd (id_informe) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE informe_control_interno ADD CONSTRAINT fk_infci_super FOREIGN KEY (id_informe) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE avance_proyecto_inversion ADD CONSTRAINT fk_api_super FOREIGN KEY (id_avance) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE version_documento_transparencia ADD CONSTRAINT fk_vdt_pub  FOREIGN KEY (id_publicacion) REFERENCES transparencia_publicacion (id_publicacion) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE version_documento_transparencia ADD CONSTRAINT fk_vdt_resp FOREIGN KEY (id_responsable) REFERENCES servidor_publico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE calendario_tributario ADD CONSTRAINT fk_caltrib_imp FOREIGN KEY (id_impuesto) REFERENCES impuesto (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE alerta_cumplimiento ADD CONSTRAINT fk_alcum_resp FOREIGN KEY (id_responsable) REFERENCES servidor_publico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE politica_retencion ADD CONSTRAINT fk_polret_cat FOREIGN KEY (id_categoria_dato) REFERENCES categoria_dato_sensible (id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- ---- FK RBAC + R3 planes ----------------------------------------------------
ALTER TABLE rol_permiso ADD CONSTRAINT fk_rolperm_rol  FOREIGN KEY (id_rol)     REFERENCES rol (id)     ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE rol_permiso ADD CONSTRAINT fk_rolperm_perm FOREIGN KEY (id_permiso) REFERENCES permiso (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE usuario_rol ADD CONSTRAINT fk_usurol_ui  FOREIGN KEY (id_usuario_interno) REFERENCES usuario_interno (id) ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE usuario_rol ADD CONSTRAINT fk_usurol_rol FOREIGN KEY (id_rol) REFERENCES rol (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE usuario_rol ADD CONSTRAINT fk_usurol_por FOREIGN KEY (asignado_por) REFERENCES usuario_interno (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE ciudadano_rol ADD CONSTRAINT fk_ciudrol_ciud FOREIGN KEY (id_ciudadano) REFERENCES ciudadano (id_ciudadano) ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE ciudadano_rol ADD CONSTRAINT fk_ciudrol_rol  FOREIGN KEY (id_rol)       REFERENCES rol (id) ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE plan_integracion_tramite ADD CONSTRAINT fk_pit_plan   FOREIGN KEY (id_plan)     REFERENCES plan_integracion (id) ON DELETE CASCADE  ON UPDATE CASCADE;
ALTER TABLE plan_integracion_tramite ADD CONSTRAINT fk_pit_tram   FOREIGN KEY (id_tramite)  REFERENCES tramite (id_tramite)   ON DELETE RESTRICT ON UPDATE CASCADE;
ALTER TABLE plan_integracion_tramite ADD CONSTRAINT fk_pit_resp   FOREIGN KEY (id_responsable) REFERENCES servidor_publico (id) ON DELETE SET NULL ON UPDATE CASCADE;
ALTER TABLE plan_integracion_dominio ADD CONSTRAINT fk_pid_plan   FOREIGN KEY (id_plan) REFERENCES plan_integracion (id) ON DELETE CASCADE ON UPDATE CASCADE;
ALTER TABLE plan_integracion_otro_medio ADD CONSTRAINT fk_pom_plan FOREIGN KEY (id_plan) REFERENCES plan_integracion (id) ON DELETE CASCADE ON UPDATE CASCADE;

-- =============================================================================
-- 5. INDICES (justificados por algebra relacional - modelo fisico §2 + DELTAs)
--    sigma = filtro/WHERE; join = FK; pi/orden = proyeccion+ORDER BY; busqueda
-- =============================================================================

-- ---- 5.1 Nucleo: solicitud / radicado / pqrsd / tramite ---------------------
CREATE INDEX idx_solicitud_ciudadano_estado ON solicitud (id_ciudadano, estado)
    INCLUDE (numero_radicado, fecha_hora_radicacion, id_tramite);  -- join+sigma "mis tramites por estado" (index-only)
CREATE INDEX idx_solicitud_tramite ON solicitud (id_tramite);       -- join catalogo
CREATE INDEX idx_solicitud_vencimiento ON solicitud (fecha_vencimiento)
    WHERE estado IN ('RADICADO','EN_TRAMITE','REQUIERE_SUBSANACION'); -- sigma rango alertas vivas
CREATE INDEX idx_radicado_expediente ON radicado (id_expediente);   -- join reconstruccion expediente
CREATE INDEX idx_pqrsd_dependencia_estado ON pqrsd (id_dependencia, estado)
    INCLUDE (id_radicado, fecha_estimada_respuesta);               -- bandeja por dependencia (index-only)
CREATE INDEX idx_pqrsd_fecha_resp ON pqrsd (fecha_estimada_respuesta)
    WHERE estado NOT IN ('respondida','cerrada');                   -- sigma rango plazo legal
CREATE INDEX idx_pqrsd_tipo ON pqrsd (id_tipo_pqrsd);               -- join catalogo plazos
CREATE INDEX idx_pqrsd_ciudadano ON pqrsd (id_ciudadano) WHERE id_ciudadano IS NOT NULL;  -- "mis PQRSD"
CREATE INDEX idx_radicado_fecha ON radicado USING BRIN (fecha_hora_radicacion); -- sigma rango append
CREATE INDEX idx_tramite_dependencia ON tramite (id_dependencia);  -- join tramites por dependencia
CREATE INDEX idx_tramite_busqueda ON tramite USING GIN (to_tsvector('spanish', nombre || ' ' || descripcion)); -- busqueda SUIT
CREATE INDEX idx_tramite_estado ON tramite (estado_estandarizado) WHERE estado_estandarizado = 'ACTIVO'; -- listado publico

-- ---- 5.2 Actores / RBAC / identidad -----------------------------------------
CREATE UNIQUE INDEX idx_ciudadano_correo_lower ON ciudadano (lower(correo)) WHERE correo IS NOT NULL; -- login case-insensitive
CREATE UNIQUE INDEX idx_ciudadano_scd ON ciudadano (scd_sub) WHERE auth_source = 'scd_oidc';          -- identidad federada
CREATE UNIQUE INDEX idx_usuario_correo_lower ON usuario_interno (lower(correo));                       -- login back-office
CREATE UNIQUE INDEX idx_usuario_sigep ON usuario_interno (id_sigep) WHERE id_sigep IS NOT NULL;        -- cruce SIGEP C-09
CREATE INDEX idx_usuario_rol_rol ON usuario_rol (id_rol);     -- join "usuarios con rol X"
CREATE INDEX idx_rol_permiso_permiso ON rol_permiso (id_permiso); -- join permisos efectivos

-- ---- 5.3 Documentos / expedientes / notificaciones / firma ------------------
CREATE UNIQUE INDEX idx_doc_dedup ON documento_electronico (hash_integridad, contexto, COALESCE(id_solicitud, id_pqrsd, NULL)); -- dedup arco (rama bigint)
CREATE INDEX idx_doc_solicitud  ON documento_electronico (id_solicitud)  WHERE id_solicitud IS NOT NULL;  -- join rama TRAMITE
CREATE INDEX idx_doc_pqrsd      ON documento_electronico (id_pqrsd)      WHERE id_pqrsd IS NOT NULL;       -- join rama PQRSD
CREATE INDEX idx_doc_expediente ON documento_electronico (id_expediente) WHERE id_expediente IS NOT NULL;  -- join foliado
CREATE INDEX idx_doc_antivirus  ON documento_electronico (estado_antivirus) WHERE estado_antivirus = 'pendiente'; -- cola worker
CREATE UNIQUE INDEX idx_exp_sgdea ON expediente_electronico (sgdea_referencia) WHERE sgdea_referencia IS NOT NULL; -- sync SGDEA (DELTA item 2)
CREATE UNIQUE INDEX idx_exp_solicitud ON expediente_electronico (id_solicitud) WHERE id_solicitud IS NOT NULL;     -- 1:1 parcial
CREATE INDEX idx_notif_polimorf  ON notificacion (entidad_tipo, entidad_id);                                -- sigma notif de un objeto
CREATE INDEX idx_notif_pendientes ON notificacion (estado, canal) WHERE estado IN ('pendiente','fallida');  -- worker envio
CREATE INDEX idx_notif_ciudadano ON notificacion (id_destinatario_ciudadano) WHERE id_destinatario_ciudadano IS NOT NULL; -- "mis notificaciones"
CREATE INDEX idx_firma_firmante ON firma_electronica (id_firmante);            -- join verificacion (DELTA B)
CREATE INDEX idx_firma_cert     ON firma_electronica (certificado_serial);     -- join OCSP (DELTA B)

-- ---- 5.4 Transparencia ISA / tributario / contenido -------------------------
CREATE INDEX idx_transp_subtipo_fecha ON transparencia_publicacion (subtipo, fecha_publicacion DESC); -- "ultimas de seccion X"
CREATE INDEX idx_vdt_pub ON version_documento_transparencia (id_publicacion, version DESC);            -- version vigente
CREATE INDEX idx_cal_trib_impuesto ON calendario_tributario (id_impuesto, vigencia_fiscal);            -- vencimientos por impuesto
CREATE INDEX idx_contenido_tipo_estado ON contenido (tipo, estado) WHERE estado = 'publicado';         -- listado publico CMS
CREATE INDEX idx_contenido_busqueda ON contenido USING GIN (to_tsvector('spanish', coalesce(titulo,'') || ' ' || coalesce(cuerpo,''))); -- buscador contenidos

-- ---- 5.5 Canales / citas / datos abiertos / interop -------------------------
CREATE INDEX idx_franja_servicio_fecha ON franja_horaria (id_servicio_agendable, fecha, estado);  -- join+sigma disponibilidad cupos
CREATE INDEX idx_cita_franja   ON cita (id_franja);                                                -- join conteo cupos
CREATE INDEX idx_cita_ciudadano ON cita (id_ciudadano) WHERE id_ciudadano IS NOT NULL;            -- DELTA B
CREATE INDEX idx_cita_servicio ON cita (id_servicio_agendable);                                    -- DELTA B
CREATE INDEX idx_dataset_busqueda ON dataset USING GIN (to_tsvector('spanish', nombre || ' ' || coalesce(descripcion,''))); -- catalogo datos abiertos
CREATE INDEX idx_xroad_service_ts ON interop_xroad_transaction (id_service, transaction_timestamp); -- join+rango por servicio
CREATE INDEX idx_xroad_status ON interop_xroad_transaction (status) WHERE status IN ('PENDING','RETRYING','FAILED'); -- reintentos
CREATE UNIQUE INDEX idx_tsa_activa ON interop_tsa_config (ambiente) WHERE es_activo = TRUE;         -- 1 config TSA activa/ambiente (DELTA item 3)
CREATE INDEX idx_cert_expira ON certificado_digital (expires_at);                                   -- sigma rango alertas

-- ---- 5.6 FK->dependencia (RESTRICT exige indice en la hija) -----------------
CREATE INDEX idx_canal_dependencia       ON canal_atencion (id_dependencia);
CREATE INDEX idx_servag_dependencia      ON servicio_agendable (id_dependencia);
CREATE INDEX idx_mec_dependencia         ON mecanismo_participacion (id_dependencia);
CREATE INDEX idx_mec_oficina             ON mecanismo_participacion (id_oficina_responsable) WHERE id_oficina_responsable IS NOT NULL;
CREATE INDEX idx_franja_dependencia      ON franja_horaria (id_dependencia);
CREATE INDEX idx_cita_dependencia        ON cita (id_dependencia);
CREATE INDEX idx_asigdep_dependencia     ON asignacion_dependencia (id_dependencia);
CREATE INDEX idx_traslado_dependencia    ON traslado_competencia (id_dependencia_origen);
CREATE INDEX idx_micrositio_dependencia  ON micrositio (id_dependencia_duena);
CREATE INDEX idx_sp_dependencia          ON servidor_publico (id_dependencia);
CREATE INDEX idx_dependencia_padre       ON dependencia (id_dependencia_padre);
CREATE INDEX idx_dependencia_responsable ON dependencia (id_responsable) WHERE id_responsable IS NOT NULL;
CREATE INDEX idx_dependencia_sede        ON dependencia (sede_id) WHERE sede_id IS NOT NULL;
CREATE INDEX idx_cj_representante        ON ciudadano_juridica (representante_legal_id) WHERE representante_legal_id IS NOT NULL;

-- ---- 5.7 Append-only / seguridad (BRIN + parciales) -------------------------
CREATE INDEX idx_log_occurred ON log_auditoria USING BRIN (occurred_at);                    -- forense por periodo
CREATE INDEX idx_log_actor    ON log_auditoria (id_usuario_interno, occurred_at) WHERE id_usuario_interno IS NOT NULL; -- acciones del usuario X
CREATE INDEX idx_log_entidad  ON log_auditoria (entidad_tipo, entidad_id);                  -- historial polimorfico
CREATE INDEX idx_log_evento   ON log_auditoria (event_type) WHERE resultado IN ('failure','blocked'); -- eventos de seguridad
CREATE INDEX idx_intento_ip_ts   ON intento_login USING BRIN (occurred_at);                 -- alto volumen append
CREATE INDEX idx_intento_usuario ON intento_login (id_usuario, occurred_at) WHERE resultado = 'fallido'; -- bloqueo por N fallos
CREATE INDEX idx_xroad_ts ON interop_xroad_transaction USING BRIN (transaction_timestamp);  -- alto volumen append
CREATE INDEX idx_sesion_expira ON sesion (expires_at) WHERE estado_sesion = 'activa';        -- limpieza sesiones
CREATE UNIQUE INDEX idx_consent_unico ON consentimiento_datos (id_ciudadano, consent_type, occurred_at) WHERE id_ciudadano IS NOT NULL; -- consentimiento vigente (DELTA-deps C.5)
CREATE INDEX idx_consent_ciudadano ON consentimiento_datos (id_ciudadano, consent_type, occurred_at DESC); -- ultimo vigente

-- ---- 5.8 Pago / FK varias / R3 ----------------------------------------------
CREATE UNIQUE INDEX idx_pago_aprobado ON pago (id_solicitud) WHERE estado = 'APROBADO';  -- <=1 aprobado por solicitud (B.11)
CREATE INDEX idx_pago_solicitud ON pago (id_solicitud);                                    -- listar intentos
CREATE INDEX idx_pit_tramite ON plan_integracion_tramite (id_tramite) WHERE id_tramite IS NOT NULL; -- FK->tramite
CREATE INDEX idx_pit_responsable ON plan_integracion_tramite (id_responsable) WHERE id_responsable IS NOT NULL; -- FK->servidor
CREATE UNIQUE INDEX uq_pit_plan_tramite ON plan_integracion_tramite (id_plan, id_tramite) WHERE id_tramite IS NOT NULL; -- clave nat rama SUIT
CREATE UNIQUE INDEX uq_pit_plan_nombre  ON plan_integracion_tramite (id_plan, nombre_tramite) WHERE id_tramite IS NULL;  -- clave nat fuera-SUIT
CREATE INDEX idx_rlc_bloqueo ON rate_limit_contador (bloqueado_hasta) WHERE bloqueado_hasta IS NOT NULL; -- purga bloqueos
CREATE UNIQUE INDEX uq_ct_original ON contenido_traduccion (id_contenido) WHERE es_original = TRUE; -- 1 original/contenido (RN-TX-D05)
CREATE INDEX idx_gic_variable ON grupo_interes_caracterizacion (id_variable);  -- FK + "grupos con variable X"
CREATE UNIQUE INDEX uq_caa_activa ON carpeta_autorizacion_acceso (id_ciudadano, entidad_autorizada, conjunto_dato) WHERE estado = 'activa'; -- 1 activa por (titular,entidad,conjunto)
CREATE INDEX idx_caa_ciudadano  ON carpeta_autorizacion_acceso (id_ciudadano);
CREATE INDEX idx_caa_vencimiento ON carpeta_autorizacion_acceso (fecha_fin_vigencia) WHERE estado = 'activa' AND fecha_fin_vigencia IS NOT NULL;

-- =============================================================================
-- 6. VISTAS (independencia logica - modelo fisico §6.1 + DELTAs R2/R3)
-- =============================================================================

-- ---- 6.1 Vistas por perfil --------------------------------------------------
CREATE VIEW v_ciudadano_mis_solicitudes AS
SELECT s.numero_radicado, s.estado, s.fecha_hora_radicacion, t.nombre AS nombre_tramite, s.id_ciudadano
FROM solicitud s
JOIN tramite t ON t.id_tramite = s.id_tramite
WHERE s.estado <> 'BORRADOR';

CREATE VIEW v_backoffice_bandeja_pqrsd AS
SELECT p.numero_documento, p.objeto, p.estado, p.fecha_estimada_respuesta,
       d.nombre AS dependencia, p.id_dependencia, r.numero_radicado
FROM pqrsd p
JOIN dependencia d ON d.id_dependencia = p.id_dependencia
JOIN radicado r ON r.id_radicado = p.id_radicado;

CREATE VIEW v_auditoria_forense AS
SELECT occurred_at, event_type, actor_type, id_usuario_interno, id_ciudadano,
       entidad_tipo, entidad_id, resultado, componente
FROM log_auditoria;  -- aisla record_hash/previous_hash internos

CREATE VIEW v_directorio_institucional AS
SELECT d.codigo, d.nombre AS dependencia, d.sigla, d.correo_institucional, d.telefono,
       d.ubicacion_fisica, d.horario_atencion, sp.nombre_completo AS responsable, sp.cargo
FROM dependencia d
LEFT JOIN servidor_publico sp ON sp.id = d.id_responsable
WHERE d.activa = TRUE;  -- art.9 Ley 1712

-- ---- 6.2 Vistas de derivados NO materializados ------------------------------
-- ciudadano.is_minor (current_date no IMMUTABLE -> via vista)
CREATE VIEW v_ciudadano_completo AS
SELECT c.*,
       (c.fecha_nacimiento IS NOT NULL AND c.fecha_nacimiento > current_date - INTERVAL '18 years') AS is_minor_calc,
       cj.razon_social, cj.representante_legal_id AS rep_legal_juridica
FROM ciudadano c
LEFT JOIN ciudadano_juridica cj ON cj.id_ciudadano = c.id_ciudadano;

-- contrato.tiene_otrosi (depende de otra tabla -> vista)
CREATE VIEW v_contrato_ejecucion AS
SELECT k.*, EXISTS (SELECT 1 FROM otrosi o WHERE o.id_contrato = k.id_contrato) AS tiene_otrosi
FROM contrato k;

-- evaluacion_sus.puntaje_sus (formula SUS sobre respuesta_item_sus; NULL si COUNT<>10)
CREATE VIEW v_evaluacion_sus_puntaje AS
SELECT e.id AS id_evaluacion,
       CASE WHEN COUNT(r.numero_item) = 10
            THEN round(SUM(CASE WHEN r.numero_item % 2 = 1 THEN r.respuesta_likert - 1
                                ELSE 5 - r.respuesta_likert END) * 2.5, 2)
       END AS puntaje_sus
FROM evaluacion_sus e
LEFT JOIN respuesta_item_sus r ON r.id_evaluacion = e.id
GROUP BY e.id;

-- pago.monto (snapshot) -> vista de estado de pago
CREATE VIEW v_pago_estado AS
SELECT pg.id, pg.id_solicitud, pg.estado, pg.monto, pg.fecha_confirmacion
FROM pago pg;

-- ---- 6.3 Vistas R3 ----------------------------------------------------------
-- v_dataset_publico: metadatos_completos computado on-read (NO columna base)
CREATE VIEW v_dataset_publico AS
SELECT ds.*,
       EXISTS (SELECT 1 FROM dataset_metadata m WHERE m.id_dataset = ds.id) AS metadatos_completos
FROM dataset ds
WHERE ds.estado = 'publicado';

-- v_plan_integracion_tramite: nombre_tramite derivado del catalogo si hay id_tramite
CREATE VIEW v_plan_integracion_tramite AS
SELECT pit.id, pit.id_plan, pit.id_tramite,
       COALESCE(pit.nombre_tramite, t.nombre) AS nombre_tramite,
       pit.solicitudes_anio, pit.accion, pit.fecha_objetivo, pit.id_responsable
FROM plan_integracion_tramite pit
LEFT JOIN tramite t ON t.id_tramite = pit.id_tramite;

-- v_mecanismo_estado: cierre derivado temporal (now())
CREATE VIEW v_mecanismo_estado AS
SELECT m.id, m.titulo, m.tipo_fase, m.fecha_inicio, m.fecha_cierre, m.id_dependencia,
       CASE WHEN m.fecha_cierre IS NOT NULL AND m.fecha_cierre <= current_date THEN 'cerrada'
            ELSE m.estado END AS estado
FROM mecanismo_participacion m;

-- v_contenido_multilingue: fallback ES (RN-TX-D05)
CREATE VIEW v_contenido_multilingue AS
SELECT c.id AS id_contenido, c.idioma_origen,
       ct.idioma,
       COALESCE(ct.titulo_traducido, c.titulo) AS titulo,
       COALESCE(ct.cuerpo_traducido, c.cuerpo) AS cuerpo
FROM contenido c
LEFT JOIN contenido_traduccion ct ON ct.id_contenido = c.id;

-- v_interop_tsa_config: url_tsa reconstruido on-read (host_salida:puerto)
CREATE VIEW v_interop_tsa_config AS
SELECT tc.*, format('https://%s:%s', tc.host_salida, tc.puerto) AS url_tsa
FROM interop_tsa_config tc;

-- =============================================================================
-- 7. TRIGGERS Y FUNCIONES (modelo fisico §5 + DELTAs)
--    Cobertura ISA, anti-ciclo arbol, cierre temporal, cadena hash, cupos,
--    validacion polimorfica, snapshot pago, MFA por rol, SoD.
-- =============================================================================

-- ---- 7.1 Cadena hash append-only de log_auditoria (RN-09-D05) ----------------
CREATE OR REPLACE FUNCTION trg_log_hash() RETURNS trigger AS $$
DECLARE
    v_prev VARCHAR(64);
BEGIN
    -- previous_hash = ultimo record_hash de la cadena (escritor serializado por advisory lock global)
    PERFORM pg_advisory_xact_lock(hashtext('log_auditoria_chain'));
    SELECT record_hash INTO v_prev
    FROM log_auditoria
    ORDER BY occurred_at DESC, id DESC
    LIMIT 1;
    NEW.previous_hash := COALESCE(v_prev, repeat('0', 64));
    NEW.record_hash := encode(
        digest(
            coalesce(NEW.event_type,'') || '|' || coalesce(NEW.actor_type,'') || '|' ||
            coalesce(NEW.accion_detalle,'') || '|' || coalesce(NEW.resultado,'') || '|' ||
            NEW.occurred_at::text || '|' || NEW.previous_hash,
            'sha256'),
        'hex');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_log_hash_bi BEFORE INSERT ON log_auditoria
    FOR EACH ROW EXECUTE FUNCTION trg_log_hash();
-- Append-only: prohibir UPDATE/DELETE
CREATE OR REPLACE FUNCTION trg_log_appendonly() RETURNS trigger AS $$
BEGIN
    RAISE EXCEPTION 'log_auditoria es append-only: UPDATE/DELETE prohibido';
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_log_no_update BEFORE UPDATE OR DELETE ON log_auditoria
    FOR EACH ROW EXECUTE FUNCTION trg_log_appendonly();

-- ---- 7.2 Decremento de cupos de cita (RN-06-D01, lock pesimista) ------------
CREATE OR REPLACE FUNCTION trg_cupo_decrementa() RETURNS trigger AS $$
BEGIN
    IF NEW.id_franja IS NOT NULL AND NEW.estado IN ('reservada','confirmada') THEN
        UPDATE franja_horaria
        SET cupos_disponibles = cupos_disponibles - 1,
            estado = CASE WHEN cupos_disponibles - 1 = 0 THEN 'agotada' ELSE estado END
        WHERE id = NEW.id_franja AND cupos_disponibles > 0;
        IF NOT FOUND THEN
            RAISE EXCEPTION 'Sin cupos disponibles en la franja %', NEW.id_franja;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_cupo_decrementa_ai AFTER INSERT ON cita
    FOR EACH ROW EXECUTE FUNCTION trg_cupo_decrementa();

-- ---- 7.3 Cobertura ISA transparencia_publicacion (DELTA item 5, DEFERRED) ---
CREATE OR REPLACE FUNCTION trg_transp_cobertura() RETURNS trigger AS $$
DECLARE
    v_count INTEGER;
    v_tabla TEXT;
BEGIN
    v_tabla := CASE NEW.subtipo
        WHEN 'normativa' THEN 'normativa'
        WHEN 'contrato' THEN 'contrato'
        WHEN 'plan_adquisiciones' THEN 'plan_adquisiciones'
        WHEN 'plan_accion' THEN 'plan_accion'
        WHEN 'informe_gestion' THEN 'informe_gestion'
        WHEN 'informe_pqrsd' THEN 'informe_pqrsd'
        WHEN 'informe_control_interno' THEN 'informe_control_interno'
        WHEN 'avance_proyecto_inversion' THEN 'avance_proyecto_inversion'
    END;
    EXECUTE format(
        'SELECT count(*) FROM %I WHERE %I = $1',
        v_tabla,
        (SELECT a.attname FROM pg_attribute a
         JOIN pg_class c ON c.oid = a.attrelid
         WHERE c.relname = v_tabla AND a.attnum = 1)
    ) INTO v_count USING NEW.id_publicacion;
    IF v_count <> 1 THEN
        RAISE EXCEPTION 'Cobertura ISA transparencia: id_publicacion % debe tener exactamente 1 fila en %', NEW.id_publicacion, v_tabla;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE CONSTRAINT TRIGGER trg_transp_cobertura_ai
    AFTER INSERT ON transparencia_publicacion
    DEFERRABLE INITIALLY DEFERRED
    FOR EACH ROW EXECUTE FUNCTION trg_transp_cobertura();

-- ---- 7.4 Cobertura ISA ciudadano (disjoint+parcial, DEFERRED) ---------------
CREATE OR REPLACE FUNCTION trg_ciudadano_cobertura() RETURNS trigger AS $$
DECLARE
    v_exists BOOLEAN;
BEGIN
    SELECT EXISTS (SELECT 1 FROM ciudadano_juridica cj WHERE cj.id_ciudadano = NEW.id_ciudadano) INTO v_exists;
    IF NEW.es_persona_juridica AND NOT v_exists THEN
        RAISE EXCEPTION 'ciudadano % es_persona_juridica=TRUE pero no tiene fila en ciudadano_juridica', NEW.id_ciudadano;
    END IF;
    IF NOT NEW.es_persona_juridica AND v_exists THEN
        RAISE EXCEPTION 'ciudadano % no es persona juridica pero tiene fila en ciudadano_juridica', NEW.id_ciudadano;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE CONSTRAINT TRIGGER trg_ciudadano_cobertura_aiu
    AFTER INSERT OR UPDATE ON ciudadano
    DEFERRABLE INITIALLY DEFERRED
    FOR EACH ROW EXECUTE FUNCTION trg_ciudadano_cobertura();

-- ---- 7.5 Anti-ciclo arbol dependencia (organigrama, D.4) --------------------
CREATE OR REPLACE FUNCTION trg_dependencia_anticiclo() RETURNS trigger AS $$
DECLARE
    v_ancestro BIGINT;
BEGIN
    IF NEW.id_dependencia_padre IS NULL THEN RETURN NEW; END IF;
    v_ancestro := NEW.id_dependencia_padre;
    WHILE v_ancestro IS NOT NULL LOOP
        IF v_ancestro = NEW.id_dependencia THEN
            RAISE EXCEPTION 'Ciclo detectado en organigrama dependencia (id=%)', NEW.id_dependencia;
        END IF;
        SELECT id_dependencia_padre INTO v_ancestro FROM dependencia WHERE id_dependencia = v_ancestro;
    END LOOP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_dependencia_anticiclo_biu BEFORE INSERT OR UPDATE ON dependencia
    FOR EACH ROW EXECUTE FUNCTION trg_dependencia_anticiclo();

-- ---- 7.6 Validacion polimorfica de notificacion (H11) -----------------------
CREATE OR REPLACE FUNCTION trg_notif_valida_fk() RETURNS trigger AS $$
DECLARE
    v_ok BOOLEAN;
BEGIN
    v_ok := CASE NEW.entidad_tipo
        WHEN 'PQRSD'     THEN EXISTS (SELECT 1 FROM pqrsd WHERE id_pqrsd = NEW.entidad_id::bigint)
        WHEN 'TRAMITE'   THEN EXISTS (SELECT 1 FROM solicitud WHERE id_solicitud = NEW.entidad_id::bigint)
        WHEN 'CITA'      THEN EXISTS (SELECT 1 FROM cita WHERE id = NEW.entidad_id::bigint)
        WHEN 'CONTENIDO' THEN EXISTS (SELECT 1 FROM contenido WHERE id = NEW.entidad_id::uuid)
        WHEN 'ACTO'      THEN EXISTS (SELECT 1 FROM documento_electronico WHERE id = NEW.entidad_id::uuid)
        ELSE FALSE
    END;
    IF NOT v_ok THEN
        RAISE EXCEPTION 'notificacion polimorfica: entidad % de tipo % no existe', NEW.entidad_id, NEW.entidad_tipo;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_notif_valida_fk_biu BEFORE INSERT OR UPDATE ON notificacion
    FOR EACH ROW EXECUTE FUNCTION trg_notif_valida_fk();

-- ---- 7.7 Validacion polimorfica de alerta_cumplimiento (DELTA item 1) -------
CREATE OR REPLACE FUNCTION trg_alerta_valida_fk() RETURNS trigger AS $$
BEGIN
    -- entidad_tipo + entidad_id deben referenciar un objeto existente; se valida por dominio CHECK
    -- y, segun entidad_tipo, contra la tabla correspondiente (extension futura).
    IF NEW.entidad_tipo IS NOT NULL AND NEW.entidad_id IS NULL THEN
        RAISE EXCEPTION 'alerta_cumplimiento: entidad_tipo sin entidad_id';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_alerta_valida_fk_biu BEFORE INSERT OR UPDATE ON alerta_cumplimiento
    FOR EACH ROW EXECUTE FUNCTION trg_alerta_valida_fk();

-- ---- 7.8 Snapshot pago.monto = tramite.costo al radicar ---------------------
CREATE OR REPLACE FUNCTION trg_pago_snapshot() RETURNS trigger AS $$
DECLARE
    v_costo NUMERIC(18,2);
BEGIN
    SELECT t.costo INTO v_costo
    FROM solicitud s JOIN tramite t ON t.id_tramite = s.id_tramite
    WHERE s.id_solicitud = NEW.id_solicitud;
    IF v_costo IS NULL THEN
        RAISE EXCEPTION 'No se pudo determinar el costo del tramite para la solicitud %', NEW.id_solicitud;
    END IF;
    IF NEW.monto <> v_costo THEN
        RAISE EXCEPTION 'pago.monto (%) debe igualar tramite.costo (%)', NEW.monto, v_costo;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_pago_snapshot_bi BEFORE INSERT ON pago
    FOR EACH ROW EXECUTE FUNCTION trg_pago_snapshot();

-- ---- 7.9 MFA requerido por rol (RN-09-D04) ----------------------------------
CREATE OR REPLACE FUNCTION trg_mfa_requerido() RETURNS trigger AS $$
DECLARE
    v_requiere BOOLEAN;
    v_mfa BOOLEAN;
BEGIN
    SELECT requiere_mfa INTO v_requiere FROM rol WHERE id = NEW.id_rol;
    IF v_requiere THEN
        SELECT mfa_habilitado INTO v_mfa FROM usuario_interno WHERE id = NEW.id_usuario_interno;
        IF NOT COALESCE(v_mfa, FALSE) THEN
            RAISE EXCEPTION 'El rol % exige MFA habilitado en el usuario %', NEW.id_rol, NEW.id_usuario_interno;
        END IF;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_mfa_requerido_bi BEFORE INSERT OR UPDATE ON usuario_rol
    FOR EACH ROW EXECUTE FUNCTION trg_mfa_requerido();

-- ---- 7.10 Cierre temporal automatico de mecanismo (FD-MEC-1) ----------------
-- Preferida la vista v_mecanismo_estado; trigger opcional para materializar cierre por job.
CREATE OR REPLACE FUNCTION trg_mec_cierre_auto() RETURNS trigger AS $$
BEGIN
    IF NEW.fecha_cierre IS NOT NULL AND NEW.fecha_cierre <= current_date AND NEW.cierre_automatico THEN
        NEW.estado := 'cerrada';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
CREATE TRIGGER trg_mec_cierre_auto_biu BEFORE INSERT OR UPDATE ON mecanismo_participacion
    FOR EACH ROW EXECUTE FUNCTION trg_mec_cierre_auto();

-- =============================================================================
-- 8. FIN DEL SCRIPT
-- Conteo de tablas (CREATE TABLE): 148 tablas base + 8 particiones de ejemplo.
--   Base por clase C-13: A (negocio), B (UUID expuesta), C (catalogo natural),
--   D (append-only particionada), E (subtipo ISA), F (asociativa/historico).
--   Particionadas (4): log_auditoria, intento_login, interop_xroad_transaction
--   (RANGE mes), notificacion (RANGE mes). 2 particiones de ejemplo c/u.
-- Indices: ~80 (BTREE/parciales/funcionales lower(correo) + GIN x4 + BRIN x4).
-- Vistas: 13 (independencia logica + derivados no materializados + R3).
-- Triggers: 11 (hash chain, append-only guard, cupos, cobertura ISA x2,
--   anti-ciclo, polimorficos x2, snapshot pago, MFA, cierre mecanismo).
-- =============================================================================
