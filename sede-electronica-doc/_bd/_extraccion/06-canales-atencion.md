# Extraccion BD — Modulo 06 Canales de Atencion

## 0. Cobertura

- Archivo fuente: `canales-atencion.md:1-97`
- Integra: SÍ
- Lineas N: 97 leidas integras, 0 omitidas

---

## 1. Entidades candidatas

| # | Nombre entidad | Descripcion | CANDIDATA-COMPARTIDA | Fuente |
|---|----------------|-------------|----------------------|--------|
| 1 | canal_atencion | Cada canal/punto de contacto publicado (presencial, telefonico, correo institucional, correo judicial, correo anticorrupcion, chat). Contiene datos de contacto, horario y ubicacion. | NO (propia del modulo) | linea:17, linea:85 |
| 2 | sede | Punto fisico de la Alcaldia con ubicacion, capacidad de atencion inclusiva y recursos digitales. Maximas 5 en footer. | CANDIDATA-COMPARTIDA (la sede es la fuente de verdad de disponibilidad de citas; compartida con modulo agendamiento y footer mod-01) | linea:17, linea:32, linea:90 |
| 3 | dependencia | Unidad organica de la Alcaldia que expone servicios agendables (ej: "Catastro"). | CANDIDATA-COMPARTIDA (referenciada por cita, servicio_agendable; cruzada con estructura organizacional) | linea:18, linea:27, linea:62, linea:85 |
| 4 | servicio_agendable | Servicio especifico que una dependencia ofrece para agendar citas presenciales. Configurado por el administrador. | NO (propia del modulo, aunque el concepto de servicio puede compartirse con catalogo general) [AMBIGUO] | linea:27, linea:78, linea:85 |
| 5 | franja_horaria | Intervalo de tiempo dentro del horario de una dependencia/servicio en que se pueden agendar citas. Tiene capacidad maxima (cupos). | NO (propia del modulo) | linea:27, linea:78 |
| 6 | bloqueo_agenda | Dia o rango de fechas bloqueado para agendamiento (dia no laborable, festivo, evento especial). Administrado por CMS. | NO | linea:27, linea:91 |
| 7 | cita | Reserva de atencion presencial de un ciudadano en una dependencia/servicio para una franja especifica. Tiene codigo de confirmacion y estado. | CANDIDATA-COMPARTIDA (el ciudadano es entidad de otros modulos; la cita puede referenciarse desde notificaciones, metricas) | linea:18, linea:62, linea:86 |
| 8 | ciudadano | Persona que agenda, consulta, cancela o reprograma una cita. Provee datos de contacto (correo) para la confirmacion. | CANDIDATA-COMPARTIDA (entidad transversal a toda la sede electronica) | linea:18, linea:62, linea:68 |
| 9 | notificacion_cita | Comunicacion enviada al ciudadano: confirmacion, recordatorio o cancelacion de cita. | NO (propia del modulo de agendamiento) [INFERIDO: se deduce de la confirmacion por correo y del recordatorio a 24 h] | linea:18, linea:30, linea:62 |
| 10 | recurso_inclusivo | Computador/dispositivo con internet disponible en sede fisica para atencion inclusiva gratuita. Cantidad minima = 2 segun HU-B1-024. | NO | linea:19, linea:70 |
| 11 | funcionario_apoyo | Personal de la sede que acompaña a ciudadanos sin habilidades digitales. Asociado a una sede. | CANDIDATA-COMPARTIDA (funcionario puede ser entidad HR/recursos humanos transversal) [INFERIDO] | linea:19, linea:69 |

---

## 2. Atributos/campos por entidad

### 2.1 canal_atencion

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| canal_atencion_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| tipo_canal | VARCHAR(30) | SI | presencial / telefonico / correo / chat / notificaciones_judiciales / anticorrupcion | — | linea:17, linea:85 |
| nombre_canal | VARCHAR(100) | SI | texto libre | — | linea:85 |
| direccion_fisica | VARCHAR(255) | CONDICIONAL (obligatorio si tipo=presencial) | Direccion completa | — | linea:17 |
| codigo_postal | VARCHAR(10) | CONDICIONAL (obligatorio si tipo=presencial) | Formato postal colombiano | — | linea:17 |
| telefono | VARCHAR(20) | CONDICIONAL (obligatorio si tiene telefono) | Formato +57 + indicativo CRC; excepto 018000/019000 | — | linea:17, linea:45 |
| correo_electronico | VARCHAR(254) | CONDICIONAL (obligatorio si tipo=correo/notificaciones/anticorrupcion) | RFC 5321 | — | linea:17, linea:85 |
| activo | BOOLEAN | SI | true/false | — | [INFERIDO: necesario para activar/desactivar canales sin borrarlos] |
| sede_id | INTEGER | CONDICIONAL (obligatorio si tipo=presencial) | FK a sede | FK -> sede.sede_id | [INFERIDO] |

### 2.2 horario_canal

> Entidad separada de canal_atencion para normalizar multiples franjas de atencion por canal/dia [INFERIDO: el campo "horario" del documento es atomico pero en BD relacional requiere descomposicion].

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| horario_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| canal_atencion_id | INTEGER | SI | FK | FK -> canal_atencion.canal_atencion_id | linea:17, linea:85 |
| dia_semana | SMALLINT | SI | 1=lunes ... 7=domingo (ISO 8601) | — | [INFERIDO] |
| hora_apertura | TIME | SI | — | — | linea:17 |
| hora_cierre | TIME | SI | — | — | linea:17 |

### 2.3 sede

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| sede_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| nombre | VARCHAR(150) | SI | texto libre | — | linea:32, linea:85 |
| direccion | VARCHAR(255) | SI | Direccion completa | — | linea:17 |
| codigo_postal | VARCHAR(10) | NO | Formato postal colombiano | — | linea:17 |
| telefono_principal | VARCHAR(20) | NO | Formato +57 | — | linea:17, linea:45 |
| correo_sede | VARCHAR(254) | NO | RFC 5321 | — | [INFERIDO] |
| activa | BOOLEAN | SI | true/false | — | [INFERIDO] |
| orden_footer | SMALLINT | NO | 1-5 (NULL si no aparece en footer); max 5 sedes en footer | — | linea:17 |
| tiene_acceso_inclusivo | BOOLEAN | SI | true/false | — | linea:19 |
| cantidad_computadores_publicos | SMALLINT | NO | >= 2 si tiene_acceso_inclusivo=true (HU-B1-024) | — | linea:70 |

### 2.4 dependencia

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| dependencia_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| nombre | VARCHAR(150) | SI | texto libre; ej: "Catastro" | — | linea:27, linea:62 |
| descripcion | TEXT | NO | — | — | [INFERIDO] |
| sede_id | INTEGER | SI | FK a sede donde opera | FK -> sede.sede_id | linea:32 |
| activa | BOOLEAN | SI | true/false | — | [INFERIDO] |

### 2.5 servicio_agendable

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| servicio_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| dependencia_id | INTEGER | SI | FK | FK -> dependencia.dependencia_id | linea:27, linea:62 |
| nombre | VARCHAR(150) | SI | texto libre; ej: "Atencion Catastral" | — | linea:27, linea:78 |
| descripcion | TEXT | NO | — | — | [INFERIDO] |
| activo | BOOLEAN | SI | true/false | — | [INFERIDO] |

### 2.6 franja_horaria

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| franja_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| servicio_id | INTEGER | SI | FK | FK -> servicio_agendable.servicio_id | linea:27, linea:78 |
| dia_semana | SMALLINT | SI | 1=lunes ... 7=domingo (ISO 8601) | — | [INFERIDO] |
| hora_inicio | TIME | SI | — | — | linea:27, linea:78 |
| hora_fin | TIME | SI | — | — | linea:27 |
| cupos_totales | SMALLINT | SI | >= 1; ej: 10 segun ejemplo del doc | — | linea:27, linea:78 |
| cupos_disponibles | SMALLINT | SI | >= 0; <= cupos_totales; gestionado atomicamente (RN-06-D01) | — | linea:27, linea:54 |
| activa | BOOLEAN | SI | true/false | — | [INFERIDO] |

> NOTA RN: cupos_disponibles NO PUEDE reducirse por debajo del numero de citas ya reservadas en esa franja (HU-06-D01 negativo, linea:78). La reserva es atomica (RN-06-D01).

### 2.7 bloqueo_agenda

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| bloqueo_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| servicio_id | INTEGER | NO | FK; NULL = bloqueo aplica a toda la sede | FK -> servicio_agendable.servicio_id | linea:27 |
| sede_id | INTEGER | NO | FK; NULL = bloqueo global | FK -> sede.sede_id | linea:27, linea:91 |
| fecha_inicio | DATE | SI | — | — | linea:27 |
| fecha_fin | DATE | SI | >= fecha_inicio | — | linea:27 |
| motivo | VARCHAR(255) | NO | dia no laborable / festivo / evento especial | — | linea:27 |

### 2.8 cita

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| cita_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| codigo_confirmacion | VARCHAR(36) | SI | UUID o codigo alfanumerico unico; usado por el ciudadano para consultar/cancelar/reprogramar | UNIQUE | linea:18, linea:62, linea:86 |
| ciudadano_id | INTEGER | NO [AMBIGUO: el doc no exige autenticacion para agendar; puede ser anonimo con solo correo] | FK o NULL | FK -> ciudadano.ciudadano_id | linea:18, linea:62 |
| nombre_contacto | VARCHAR(150) | SI | nombre del ciudadano para la cita (puede ser anonimo) | — | linea:62, linea:86 |
| correo_contacto | VARCHAR(254) | SI | correo al que se envia la confirmacion | — | linea:18, linea:62 |
| telefono_contacto | VARCHAR(20) | NO | Formato +57 | — | linea:86 |
| franja_id | INTEGER | SI | FK | FK -> franja_horaria.franja_id | linea:18, linea:27 |
| fecha_cita | DATE | SI | debe ser dia habil segun calendario modulo 12 | — | linea:18, linea:86, linea:91 |
| hora_inicio_cita | TIME | SI | debe coincidir con franja_horaria.hora_inicio | — | linea:18, linea:86 |
| estado | VARCHAR(20) | SI | agendada / cancelada / reprogramada / atendida / no_show | — | linea:86, linea:30 |
| fecha_hora_creacion | TIMESTAMP | SI | timestamp de la reserva | — | [INFERIDO] |
| fecha_hora_cancelacion | TIMESTAMP | NO | NULL si no cancelada | — | linea:18 |
| cita_original_id | INTEGER | NO | FK autorreferencial; apunta a la cita original en caso de reprogramacion | FK -> cita.cita_id | linea:28, linea:79 |
| recordatorio_enviado | BOOLEAN | SI | DEFAULT false; true cuando se envio recordatorio a 24 h | — | linea:30, linea:81 |

### 2.9 ciudadano

> Entidad transversal. Los atributos aqui son los que el modulo 06 explicita; pueden existir mas campos en el esquema global.

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| ciudadano_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| nombre_completo | VARCHAR(200) | SI | — | — | linea:62 |
| correo_electronico | VARCHAR(254) | SI | RFC 5321; receptor de confirmaciones | — | linea:18, linea:62 |
| telefono | VARCHAR(20) | NO | Formato +57 | — | linea:86 |

### 2.10 notificacion_cita

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| notificacion_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| cita_id | INTEGER | SI | FK | FK -> cita.cita_id | linea:18, linea:30 |
| tipo_notificacion | VARCHAR(30) | SI | confirmacion / recordatorio / cancelacion / reprogramacion | — | linea:18, linea:30 |
| canal_envio | VARCHAR(20) | SI | correo (unico mencionado en el doc) | — | linea:18 |
| destinatario | VARCHAR(254) | SI | correo del ciudadano | — | linea:18 |
| fecha_hora_envio | TIMESTAMP | NO | NULL si aun no enviada | — | linea:30 |
| estado_envio | VARCHAR(20) | SI | pendiente / enviado / fallido | — | [INFERIDO] |

### 2.11 recurso_inclusivo

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| recurso_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| sede_id | INTEGER | SI | FK | FK -> sede.sede_id | linea:19, linea:70 |
| tipo_recurso | VARCHAR(50) | SI | computador_publico / otro | — | linea:19, linea:70 |
| descripcion | VARCHAR(255) | NO | — | — | [INFERIDO] |
| disponible | BOOLEAN | SI | true/false | — | [INFERIDO] |

### 2.12 funcionario_apoyo

| Campo | Tipo | Obligatorio | Dominio | PK/FK | Fuente |
|-------|------|-------------|---------|-------|--------|
| funcionario_id | INTEGER (serial) | SI | PK surrogate | PK | [INFERIDO] |
| sede_id | INTEGER | SI | FK a sede donde presta el apoyo | FK -> sede.sede_id | linea:19, linea:69 |
| nombre_completo | VARCHAR(200) | SI | — | — | linea:69 |
| disponible | BOOLEAN | SI | true/false; indica si esta en turno activo | — | [INFERIDO] |

---

## 3. Reglas de negocio con impacto en datos

| ID | Regla | Impacto en BD | Fuente |
|----|-------|---------------|--------|
| RN-B1-017 | Telefonos con prefijo +57 e indicativo CRC; excepto 018000/019000 | CHECK en columnas telefono: formato `+57XXXXXXXXXX` (7-10 digitos) o `01[89]000XXXXXXX`; excepto lineas gratuitas 018000/019000 que no llevan prefijo pais. | linea:45, Res.1519/2020 Anexo 2 |
| RN-B2-023 / RN-06-D03 | Agendamiento y autenticacion electronicos son opcionales; siempre debe existir via presencial/telefonica equivalente | No eliminar canales presencial/telefonico; al menos un canal de cada tipo debe estar activo (constraint de negocio, no FK). ciudadano_id en cita puede ser NULL (ciudadano no autenticado). | linea:46, linea:56, Ley 1753/2015 Art.45 par.1 |
| RN-06-D01 | Reserva atomica: un cupo no puede asignarse a dos ciudadanos; el ultimo cupo lo obtiene una sola reserva | franja_horaria.cupos_disponibles se decrementa en transaccion serializable o con SELECT FOR UPDATE. cupos_disponibles >= 0 con CHECK. | linea:54, linea:29 |
| RN-06-D02 | Cancelar o reprogramar una cita libera el cupo anterior para reasignacion inmediata | Al cambiar cita.estado a cancelada/reprogramada: incrementar franja_horaria.cupos_disponibles de la franja original. Operacion atomica en la misma transaccion. | linea:55, linea:28 |
| RN-06-D01 (variante reduccion cupos) | No se puede reducir franja_horaria.cupos_totales por debajo del numero de citas activas en esa franja | CHECK / trigger: COUNT(citas activas en franja) <= nuevo cupos_totales antes de UPDATE | linea:78, HU-06-D01 |
| (RNF-B1-037) | Horarios y datos de contacto deben estar actualizados y ser veraces | Columna updated_at en canal_atencion y horario_canal; proceso CMS con auditoria | linea:38 |
| (modulo 12) | fecha_cita debe ser dia habil segun calendario habil/inhabil (modulo 12) | FK o validacion contra tabla calendario_dia del modulo 12 en INSERT de cita | linea:91 |
| Maximo 5 sedes en footer | Solo hasta 5 sedes pueden aparecer en el footer GOV.CO | sede.orden_footer: SMALLINT NOT NULL con CHECK (orden_footer BETWEEN 1 AND 5); UNIQUE; al mas 5 filas con orden_footer NOT NULL | linea:17 |
| Recordatorio a 24 h | Job que envia recordatorio 24 h antes de la cita | cita.recordatorio_enviado = false -> job filtra citas con fecha_cita = NOW() + 1 dia y envia notificacion | linea:30 |

---

## 4. Cardinalidades y relaciones

| Relacion | Cardinalidad | Descripcion | Fuente |
|----------|-------------|-------------|--------|
| sede -> canal_atencion | 1:N (una sede puede tener varios canales/contactos) | Un canal presencial pertenece a una sede | linea:17, linea:85 |
| canal_atencion -> horario_canal | 1:N | Un canal tiene uno o varios horarios por dia | linea:17 |
| sede -> dependencia | 1:N | Una sede alberga una o varias dependencias | linea:27, linea:32 |
| dependencia -> servicio_agendable | 1:N | Una dependencia ofrece uno o varios servicios agendables | linea:27, linea:62 |
| servicio_agendable -> franja_horaria | 1:N | Un servicio tiene multiples franjas horarias | linea:27, linea:78 |
| servicio_agendable -> bloqueo_agenda | 0..N (opcional, puede ser por sede o global) | Un servicio o sede puede tener bloqueos | linea:27 |
| franja_horaria -> cita | 1:N (limitado por cupos_totales) | Una franja puede tener hasta cupos_totales citas activas | linea:18, linea:27, linea:54 |
| ciudadano -> cita | 0..1 : N (ciudadano_id puede ser NULL si no autenticado) | Un ciudadano puede tener muchas citas | linea:18, linea:62 |
| cita -> cita (autorreferencial) | 0..1 : 1 (reprogramacion) | Una cita puede ser reprogramacion de otra cita original | linea:28, linea:79 |
| cita -> notificacion_cita | 1:N | Una cita genera una o varias notificaciones (confirmacion, recordatorio, etc.) | linea:18, linea:30 |
| sede -> recurso_inclusivo | 1:N | Una sede tiene uno o mas recursos inclusivos | linea:19, linea:70 |
| sede -> funcionario_apoyo | 1:N | Una sede tiene uno o mas funcionarios de apoyo | linea:19, linea:69 |

---

## 5. Jerarquias ISA / polimorfismo

No se detectan jerarquias ISA explicitas en el documento fuente.

Observacion sobre polimorfismo potencial:
- `canal_atencion.tipo_canal` introduce un atributo discriminador con valores (presencial, telefonico, correo, chat, notificaciones_judiciales, anticorrupcion). Algunos atributos son exclusivos por tipo:
  - `direccion_fisica` y `codigo_postal`: solo tipo=presencial
  - `telefono`: solo tipo=telefonico (o presencial secundario)
  - `correo_electronico`: solo tipo=correo / notificaciones / anticorrupcion
- Esto sugiere un patron de especializacion parcial con solapamiento limitado. [INFERIDO: el documento no formaliza jerarquia ISA explicitamente; se describe como campo discriminador con columnas opcionales. Se recomienda evaluar tabla unica por jerarquia (single-table) con NULLs condicionales vs. tabla por subtipo en la fase de diseno.]

---

## 6. Inferencias [INFERIDO]

| # | Inferencia | Justificacion | Impacto |
|---|-----------|---------------|---------|
| I-01 | Entidad `horario_canal` separada de `canal_atencion` | El campo "horario" en el doc es atomico pero en un esquema relacional normalizado la disponibilidad semanal requiere N filas (dia, apertura, cierre). Sin esta tabla no se pueden modelar horarios variables por dia. | Nueva tabla horario_canal |
| I-02 | `ciudadano_id` puede ser NULL en `cita` | RN-B2-023 / RN-06-D03 exigen que el agendamiento no requiera autenticacion; el ciudadano puede ser anonimo identificado solo por correo_contacto y codigo_confirmacion. | FK nullable en cita.ciudadano_id |
| I-03 | Relacion autorreferencial `cita.cita_original_id` | RF-06-D02 y HU-06-D02 describen reprogramacion que crea una nueva cita y libera la anterior; se necesita trazar la cadena de reprogramaciones. | FK autorreferencial nullable |
| I-04 | `bloqueo_agenda` como entidad propia | RF-06-D01 menciona "dias no laborables y bloqueo de fechas" como configuracion del CMS. Sin tabla propia no hay forma de almacenar estos bloqueos. Se integra con modulo 12 (calendario habil). | Nueva tabla bloqueo_agenda |
| I-05 | `notificacion_cita` como entidad propia | El doc menciona confirmacion por correo (linea:18) y recordatorio a 24 h (linea:30) como eventos diferenciados; una tabla unifica el registro de envios y permite reintentos y auditoria. | Nueva tabla notificacion_cita |
| I-06 | `recurso_inclusivo` como entidad propia | HU-B1-024 menciona "al menos 2 computadores"; modelar como tabla permite inventariar y gestionar cada recurso. | Nueva tabla recurso_inclusivo |
| I-07 | `funcionario_apoyo` como entidad propia | HU-B1-013 y HU-B1-024 mencionan "funcionario capacitado"; si se quiere asignar y gestionar personal de apoyo por sede se requiere entidad. Puede ser subtipo de una entidad Empleado/Funcionario transversal. [AMBIGUO: el doc no especifica gestion de RRHH] | Nueva tabla o FK a entidad HR transversal |
| I-08 | `cita.recordatorio_enviado` como campo booleano | El job de recordatorio necesita saber cuales citas ya recibieron notificacion para no reenviar. | Campo en cita |
| I-09 | `sede.orden_footer` con CHECK 1-5 | RF-B1-041 dice "Max 5 sedes fisicas en el footer"; se implementa con columna nullable con rango 1-5 y UNIQUE parcial. | Columna + constraint en sede |
| I-10 | Integracion con modulo 12 (calendario habil) | linea:91 referencia modulo 12 para disponibilidad de citas. Significa que `fecha_cita` debe validarse contra un catalogo de dias habiles/inhabiles externo a este modulo. | FK o CHECK contra tabla del modulo 12 |

---

## Vacios detectados

| # | Informacion ausente | Pregunta para investigar |
|---|--------------------|-----------------------|
| V-01 | Cantidad y nombres reales de sedes y dependencias | ¿Cuantas sedes fisicas y con que dependencias opera la Alcaldia? (linea:96) |
| V-02 | Existencia real de canal chat / linea en vivo | ¿Existe canal chat o linea de atencion en vivo o es futuro? (linea:96) |
| V-03 | Autenticacion del ciudadano para agendar | ¿El ciudadano debe estar registrado/autenticado o puede agendar como anonimo con solo correo? [AMBIGUO en linea:46 vs. RN-06-D03] |
| V-04 | Estructura de codigo de confirmacion | ¿UUID, alfanumerico de N digitos, con checksum? No especificado en el doc. |
| V-05 | Integracion CRM / sistema de turnos | ¿La agenda es completamente propia del modulo o hay integracion con un sistema de turnos existente? (linea:78 DoR: [PREGUNTA ABIERTA]) |
| V-06 | Politica de no-show y reactivacion de ciudadanos | ¿Cuantos no-show suspenden al ciudadano? El doc menciona metrica pero no politica de bloqueo. |
| V-07 | Modelo de datos del ciudadano (entidad global) | Los atributos de ciudadano aqui son minimos; la entidad completa debe consolidarse con otros modulos. |
| V-08 | Gestion de funcionarios de apoyo (RRHH) | El doc no especifica si existe un sistema de personal; funcionario_apoyo puede ser simple o complejo. |
