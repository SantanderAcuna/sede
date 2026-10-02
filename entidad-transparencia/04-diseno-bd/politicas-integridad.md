# Políticas de Integridad — Triggers, Constraints y RLS

> **Objetivo:** documentar todas las reglas de negocio (RN, BR) implementadas a nivel de BD (no en aplicación) y las políticas de seguridad RLS para datos sensibles.

---

## 1. Constraints CHECK

### 1.1 Documento

```sql
-- Estado válido
ALTER TABLE documento
  ADD CONSTRAINT documento_estado_check
  CHECK (estado IN ('borrador','revision','publicado','despublicado','archivado'));

-- Periodicidad válida
ALTER TABLE documento
  ADD CONSTRAINT documento_periodicidad_check
  CHECK (periodicidad IN ('anual','semestral','trimestral','mensual','eventual'));

-- Índice de lecturabilidad Fernández-Huerta
ALTER TABLE documento
  ADD CONSTRAINT documento_lecturabilidad_check
  CHECK (indice_lecturabilidad IS NULL OR (indice_lecturabilidad BETWEEN 0 AND 100));

-- Versión actual debe estar vigente
ALTER TABLE documento
  ADD CONSTRAINT documento_version_actual_publicada_check
  CHECK (
    version_actual_id IS NULL OR EXISTS (
      SELECT 1 FROM documento_version
      WHERE id = version_actual_id AND version_publicada = true
    )
  );
```

### 1.2 Servidor Público

```sql
ALTER TABLE servidor_publico
  ADD CONSTRAINT servidor_publico_estado_check
  CHECK (estado IN ('activo','inactivo','comision','desvinculado'));

-- Fecha de salida posterior a ingreso
ALTER TABLE servidor_publico
  ADD CONSTRAINT servidor_publico_fechas_check
  CHECK (fecha_salida IS NULL OR fecha_salida >= fecha_ingreso);

-- Identificación con longitud mínima
ALTER TABLE servidor_publico
  ADD CONSTRAINT servidor_publico_identificacion_check
  CHECK (length(numero_identificacion) >= 5);
```

### 1.3 Dependencia

```sql
ALTER TABLE dependencia
  ADD CONSTRAINT dependencia_no_self_ref_check
  CHECK (id <> dependencia_padre_id);

ALTER TABLE dependencia
  ADD CONSTRAINT dependencia_nivel_check
  CHECK (nivel IN ('secretaria','oficina','gerencia','direccion','subdireccion'));
```

### 1.4 Menú

```sql
ALTER TABLE menu_item
  ADD CONSTRAINT menu_item_tipo_check
  CHECK (tipo IN ('interno','externo','ancla','modal'));

-- Profundidad máxima 2 niveles (BR-DEP-02 equivalente para menú)
-- Implementado en trigger (ver §2)
```

### 1.5 Archivo Storage

```sql
ALTER TABLE archivo_storage
  ADD CONSTRAINT archivo_tamano_check
  CHECK (tamano_bytes >= 0 AND tamano_bytes <= 1073741824); -- máximo 1 GB

-- Hash SHA-256 válido (64 caracteres hex)
ALTER TABLE archivo_storage
  ADD CONSTRAINT archivo_hash_format_check
  CHECK (hash_sha256 ~ '^[a-f0-9]{64}$');
```

---

## 2. Triggers PL/pgSQL

### 2.1 Validación de organigrama (sin ciclos)

```sql
CREATE OR REPLACE FUNCTION validar_organigrama() RETURNS trigger AS $$
DECLARE
  current_id BIGINT;
  depth INT := 0;
BEGIN
  IF NEW.dependencia_padre_id IS NULL THEN
    RETURN NEW;
  END IF;

  current_id := NEW.dependencia_padre_id;
  WHILE current_id IS NOT NULL LOOP
    depth := depth + 1;
    IF depth > 5 THEN
      RAISE EXCEPTION 'Profundidad máxima del organigrama excedida (5 niveles)';
    END IF;
    IF current_id = NEW.id THEN
      RAISE EXCEPTION 'Ciclo detectado en el organigrama';
    END IF;
    SELECT dependencia_padre_id INTO current_id
      FROM dependencia WHERE id = current_id;
  END LOOP;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validar_organigrama
  BEFORE INSERT OR UPDATE OF dependencia_padre_id ON dependencia
  FOR EACH ROW EXECUTE FUNCTION validar_organigrama();
```

### 2.2 Validación de menú (sin ciclos + profundidad ≤ 2)

```sql
CREATE OR REPLACE FUNCTION validar_menu() RETURNS trigger AS $$
DECLARE
  current_id BIGINT;
  depth INT := 0;
BEGIN
  IF NEW.padre_id IS NULL THEN
    RETURN NEW;
  END IF;

  current_id := NEW.padre_id;
  WHILE current_id IS NOT NULL LOOP
    depth := depth + 1;
    IF depth > 2 THEN
      RAISE EXCEPTION 'Menú: máximo 2 niveles de submenú (RF-01-007)';
    END IF;
    IF current_id = NEW.id THEN
      RAISE EXCEPTION 'Ciclo detectado en el menú';
    END IF;
    SELECT padre_id INTO current_id FROM menu_item WHERE id = current_id;
  END LOOP;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validar_menu
  BEFORE INSERT OR UPDATE OF padre_id ON menu_item
  FOR EACH ROW EXECUTE FUNCTION validar_menu();
```

### 2.3 Hash automático al subir archivo

```sql
-- El hash se calcula en PHP antes de subir a S3.
-- Este trigger es defensivo: si por algún motivo se inserta sin hash, falla.
CREATE OR REPLACE FUNCTION validar_hash_archivo() RETURNS trigger AS $$
BEGIN
  IF NEW.hash_sha256 !~ '^[a-f0-9]{64}$' THEN
    RAISE EXCEPTION 'Hash SHA-256 inválido (debe ser hex de 64 caracteres)';
  END IF;
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_validar_hash_archivo
  BEFORE INSERT OR UPDATE OF hash_sha256 ON archivo_storage
  FOR EACH ROW EXECUTE FUNCTION validar_hash_archivo();
```

### 2.4 Actualización automática de versión_actual_id

```sql
CREATE OR REPLACE FUNCTION actualizar_version_actual() RETURNS trigger AS $$
BEGIN
  -- Cuando se inserta una nueva versión, se actualiza version_actual_id en documento
  UPDATE documento
    SET version_actual_id = NEW.id,
        updated_at = now()
    WHERE id = NEW.documento_id
      AND version_actual_id IS NULL;

  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_actualizar_version_actual
  AFTER INSERT ON documento_version
  FOR EACH ROW EXECUTE FUNCTION actualizar_version_actual();
```

### 2.5 Audit log automático

```sql
-- El trigger captura cualquier INSERT/UPDATE/DELETE en tablas marcadas
CREATE OR REPLACE FUNCTION audit_trigger() RETURNS trigger AS $$
BEGIN
  IF TG_OP = 'INSERT' THEN
    INSERT INTO log_auditoria (id, usuario_id, accion, recurso, recurso_id, cambios, ip_oragen, created_at)
    VALUES (
      nextval('log_auditoria_id_seq'),
      current_setting('app.usuario_id', true)::BIGINT,
      'crear',
      TG_TABLE_NAME,
      NEW.id,
      to_jsonb(NEW),
      current_setting('app.ip_origen', true)::INET,
      now()
    );
  ELSIF TG_OP = 'UPDATE' THEN
    INSERT INTO log_auditoria (id, usuario_id, accion, recurso, recurso_id, cambios, ip_origen, created_at)
    VALUES (
      nextval('log_auditoria_id_seq'),
      current_setting('app.usuario_id', true)::BIGINT,
      'actualizar',
      TG_TABLE_NAME,
      NEW.id,
      jsonb_build_object('antes', to_jsonb(OLD), 'despues', to_jsonb(NEW)),
      current_setting('app.ip_origen', true)::INET,
      now()
    );
  ELSIF TG_OP = 'DELETE' THEN
    INSERT INTO log_auditoria (id, usuario_id, accion, recurso, recurso_id, cambios, ip_origen, created_at)
    VALUES (
      nextval('log_auditoria_id_seq'),
      current_setting('app.usuario_id', true)::BIGINT,
      'eliminar',
      TG_TABLE_NAME,
      OLD.id,
      to_jsonb(OLD),
      current_setting('app.ip_origen', true)::INET,
      now()
    );
  END IF;
  RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- Aplicar a tablas críticas
CREATE TRIGGER audit_documento
  AFTER INSERT OR UPDATE OR DELETE ON documento
  FOR EACH ROW EXECUTE FUNCTION audit_trigger();

CREATE TRIGGER audit_servidor_publico
  AFTER INSERT OR UPDATE OR DELETE ON servidor_publico
  FOR EACH ROW EXECUTE FUNCTION audit_trigger();

CREATE TRIGGER audit_usuario
  AFTER INSERT OR UPDATE OR DELETE ON usuario
  FOR EACH ROW EXECUTE FUNCTION audit_trigger();

CREATE TRIGGER audit_dependencia
  AFTER INSERT OR UPDATE OR DELETE ON dependencia
  FOR EACH ROW EXECUTE FUNCTION audit_trigger();
```

**Configuración de variables de sesión en Laravel:**
```php
// App\Http\Middleware\AuditarOperacion.php
DB::statement("SET LOCAL app.usuario_id = " . (int) $request->user()?->id);
DB::statement("SET LOCAL app.ip_origen = '" . $request->ip() . "'");
```

---

## 3. Row Level Security (RLS) — datos personales

### 3.1 Filosofía

Las tablas que contienen datos personales o sensibles implementan RLS para que ningún usuario de la BD pueda leer datos fuera de su rol autorizado, incluso con credenciales de superusuario (excepto `postgres` con `BYPASSRLS`).

### 3.2 Habilitar RLS en `servidor_publico`

```sql
ALTER TABLE servidor_publico ENABLE ROW LEVEL SECURITY;
ALTER TABLE servidor_publico FORCE ROW LEVEL SECURITY;

-- Política 1: el público solo ve servidores activos (sin datos sensibles)
CREATE POLICY servidor_publico_publico_select ON servidor_publico
  FOR SELECT
  TO PUBLIC
  USING (
    estado = 'activo'
    AND deleted_at IS NULL
    -- Solo expone campos no sensibles via view
  );

-- Política 2: editores con rol 'editor' ven todo
CREATE POLICY servidor_publico_editor_all ON servidor_publico
  FOR ALL
  TO PUBLIC
  USING (
    current_setting('app.usuario_rol', true) IN ('editor','aprobador','administrador','seguridad')
  );

-- Política 3: servidores pueden ver sus propios datos
CREATE POLICY servidor_publico_self ON servidor_publico
  FOR SELECT
  TO PUBLIC
  USING (
    current_setting('app.servidor_publico_id', true)::BIGINT = id
  );
```

### 3.3 Habilitar RLS en `usuario` (credenciales)

```sql
ALTER TABLE usuario ENABLE ROW LEVEL SECURITY;
ALTER TABLE usuario FORCE ROW LEVEL SECURITY;

-- Solo admins pueden ver todos los usuarios
CREATE POLICY usuario_admin_all ON usuario
  FOR ALL
  TO PUBLIC
  USING (
    current_setting('app.usuario_rol', true) = 'administrador'
  );

-- Usuarios pueden ver su propio registro
CREATE POLICY usuario_self ON usuario
  FOR SELECT
  TO PUBLIC
  USING (
    current_setting('app.usuario_id', true)::BIGINT = id
  );
```

### 3.4 Habilitar RLS en `sesion`

```sql
ALTER TABLE sesion ENABLE ROW LEVEL SECURITY;
ALTER TABLE sesion FORCE ROW LEVEL SECURITY;

-- Usuarios ven solo sus sesiones
CREATE POLICY sesion_self ON sesion
  FOR SELECT
  TO PUBLIC
  USING (
    usuario_id = current_setting('app.usuario_id', true)::BIGINT
  );

-- Administradores ven todas
CREATE POLICY sesion_admin_all ON sesion
  FOR ALL
  TO PUBLIC
  USING (
    current_setting('app.usuario_rol', true) = 'administrador'
  );
```

### 3.5 Vista pública de directorio (sin RLS para exponer solo lo público)

```sql
CREATE VIEW v_directorio_publico AS
SELECT
  sp.id,
  sp.codigo_sigep,
  sp.nombres,
  sp.apellidos,
  sp.cargo,
  sp.correo_institucional,
  sp.extension,
  d.codigo AS dependencia_codigo,
  d.nombre AS dependencia_nombre
FROM servidor_publico sp
JOIN dependencia d ON sp.dependencia_id = d.id
WHERE sp.estado = 'activo'
  AND sp.deleted_at IS NULL;

-- Esta vista se usa en el API público (RF-02-006)
-- El campo numero_identificacion NO se expone
```

---

## 4. Constraints únicos compuestos

```sql
-- No puede haber dos servidores con el mismo codigo_sigep activo
CREATE UNIQUE INDEX uk_servidor_publico_sigep_activo
  ON servidor_publico (codigo_sigep)
  WHERE deleted_at IS NULL;

-- No puede haber dos documentos con el mismo slug activo
CREATE UNIQUE INDEX uk_documento_slug_activo
  ON documento (slug)
  WHERE deleted_at IS NULL;

-- No puede haber dos archivos con el mismo hash (deduplicación)
CREATE UNIQUE INDEX uk_archivo_hash ON archivo_storage (hash_sha256);

-- Una dependencia solo puede tener un responsable activo
CREATE UNIQUE INDEX uk_dependencia_responsable_activo
  ON dependencia (id)
  WHERE responsable_id IS NOT NULL AND activo = true;
```

---

## 5. Vistas de conveniencia

### 5.1 Documentos pendientes de publicar

```sql
CREATE VIEW v_documentos_pendientes_publicar AS
SELECT d.id, d.slug, d.titulo, d.fecha_publicacion,
       s.nombre AS subseccion,
       EXTRACT(DAY FROM (d.fecha_publicacion - CURRENT_DATE)) AS dias_hasta
FROM documento d
JOIN subseccion_transparencia s ON d.subseccion_id = s.id
WHERE d.estado = 'borrador'
  AND d.fecha_publicacion IS NOT NULL
  AND d.fecha_publicacion <= CURRENT_DATE + INTERVAL '7 days';
```

### 5.2 Servidores con datos incompletos

```sql
CREATE VIEW v_servidores_incompletos AS
SELECT sp.id, sp.nombres, sp.apellidos, sp.cargo, d.nombre AS dependencia
FROM servidor_publico sp
JOIN dependencia d ON sp.dependencia_id = d.id
WHERE sp.correo_institucional IS NULL
   OR sp.extension IS NULL
   OR sp.codigo_sigep IS NULL;
```

### 5.3 Documentos sin metadatos completos

```sql
CREATE VIEW v_documentos_sin_metadatos AS
SELECT d.id, d.slug, d.titulo, d.fecha_publicacion
FROM documento d
WHERE d.estado = 'publicado'
  AND d.deleted_at IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM metadato_documento m WHERE m.documento_id = d.id
  );
```

---

## 6. Comentarios en objetos (documentación in-line)

```sql
COMMENT ON TABLE documento IS
  'Documentos de transparencia pública. Ver Ley 1712/2014 y Res. 1519/2020.';
COMMENT ON COLUMN documento.slug IS
  'Identificador único semántico para URL canónica (no cambia en versionado).';
COMMENT ON COLUMN documento.estado IS
  'Estados posibles: borrador, revision, publicado, despublicado, archivado.';
COMMENT ON COLUMN archivo_storage.hash_sha256 IS
  'Hash SHA-256 del archivo para verificación de integridad (RN-06, Ley 1712 Art. 4).';
COMMENT ON COLUMN servidor_publico.numero_identificacion IS
  'Dato sensible protegido por RLS. NO se expone en API público.';
```

---

## 7. Resumen de cobertura de reglas de negocio

| Regla | Implementación |
|---|---|
| RN-06 (autenticidad hash) | `archivo_storage.hash_sha256` UNIQUE + CHECK formato |
| RN-07 (protección datos personales) | RLS en `servidor_publico`, vista `v_directorio_publico` |
| RN-08 (formatos abiertos) | `tipo_documento.formato_abierto` BOOLEAN + reporte |
| BR-DEP-01 (sin ciclos organigrama) | Trigger `validar_organigrama` |
| BR-DEP-02 (max 5 niveles) | Trigger `validar_organigrama` |
| BR-DEP-03 (responsable de la misma dependencia) | Validación en código + FK opcional |
| BR-SP-01 (directorio público sin datos sensibles) | Vista `v_directorio_publico` |
| BR-SP-02 (sync SIGEP ≤24h) | Job `sigep:sincronizar` |
| BR-01 (menú max 2 niveles) | Trigger `validar_menu` |
| RF-02-004 (fuente única) | UNIQUE en `documento.slug` |
| RF-02-021 (versionado) | Tabla `documento_version` + FK `version_actual_id` |
| RF-02-024 (hash publicado) | UNIQUE en `archivo_storage.hash_sha256` + columna visible |
| Auditoría obligatoria | Triggers `audit_trigger` en tablas críticas |
