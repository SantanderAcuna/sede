# Plantilla — Datos completos de la ficha de un trámite

> **Sede Electrónica** — Alcaldía Distrital de Santa Marta
> **Carpeta:** `entidad-transparencia/ciudadano/`
> **Auditoría:** [`auditoria-tramites-ficha.md`](./auditoria-tramites-ficha.md)
>
> Esta plantilla es el **checklist** que un editor de la Entidad debe completar por cada trámite. La Sede publicará sólo los campos que estén rellenos: un campo sin fuente es un campo ausente, no un campo vacío.

---

## Cómo se usa

1. Crear una copia de esta plantilla por cada trámite del catálogo.
2. Rellenar **solo** los campos de los que la Entidad tenga fuente oficial (SUIT, GOV.CO, decreto, resolución, acto administrativo, ficha interna aprobada).
3. Marcar como `AUSENTE` los campos que no tengan fuente: **no** inventar, **no** copiar de Internet, **no** rellenar con texto genérico.
4. Una vez rellena, la ficha se carga al backend por el panel de administración (`PATCH /api/v1/panel/tramites/{slug}`).

---

## Datos de identificación

| Campo | Valor | Fuente |
|---|---|---|
| Código SUIT (T-código) | `Txxxx` | SUIT — DAFP |
| Slug | (autogenerado del nombre) | sistema |
| Tipo | `tramites` · `opa` · `consultas` | Anexo 2.1 |
| Categoría | (slug + nombre, opcional) | Entidad |
| Nombre del trámite | (de SUIT o de la ficha oficial) | SUIT / ficha GOV.CO |

---

## 1. Información general

| Campo | Valor | Fuente |
|---|---|---|
| **Nombre** | (título oficial) | SUIT / ficha GOV.CO |
| **Resumen** (1-3 frases: «qué es, para qué sirve») | (texto aprobado) | Entidad / SUIT |
| **URL de la ficha oficial en GOV.CO** | `https://www.gov.co/ficha-tramites-y-servicios/Txxxx` | GOV.CO |

---

## 2. Hacer el trámite

| Campo | Valor | Fuente |
|---|---|---|
| **Modalidad** | `En línea` · `Parcialmente en línea` · `Presencial` | SUIT (`enLinea`) |
| **URL para iniciar el trámite en línea** | (URL de la Entidad) | Entidad |
| **Pasos** (orden, título, descripción) | ver tabla de pasos abajo | Entidad / SUIT |

### Tabla de pasos

| # | Título del paso | Descripción (opcional) |
|---|---|---|
| 1 | | |
| 2 | | |
| 3 | | |
| 4 | | |
| 5 | | |

---

## 3. Requisitos — ¿Qué necesito para hacer mi trámite?

La ficha oficial de GOV.CO distingue **5 naturalezas** de requisito. Se publica cada bloque por separado, no una sola lista.

### 3.1 Documentos que debe aportar

| Documento | Obligatorio | Formato | URL de origen |
|---|---|---|---|
| | | | |
| | | | |

### 3.2 Condiciones que debe cumplir (verificaciones institucionales)

| Condición |
|---|
| (Ej.: «Ser ciudadano colombiano», «Tener NIT activo», etc.) |

### 3.3 Solicitudes que debe presentar (por canal)

| Solicitud | Canal |
|---|---|
| | (web / presencial / correo / telefónico) |

### 3.4 Formularios que debe diligenciar

| Formulario | URL |
|---|---|
| | |

### 3.5 Pagos que debe realizar

| Pago | Monto | Cuenta |
|---|---|---|
| | | |

> Si la fuente oficial no publica requisitos, este bloque se publica con la frase «**La fuente oficial no publica los requisitos. Consúltelos en la ficha de GOV.CO**» y el enlace a `url_ficha_gov_co`. **No** se rellena con texto genérico.

---

## 4. Resultado — ¿Qué obtengo luego de hacer mi trámite?

| Campo | Valor | Fuente |
|---|---|---|
| **Descripción del resultado** | (texto aprobado) | Entidad |

> Si la fuente oficial no lo declara: «La fuente oficial no publica el resultado. Consúltelo en la ficha de GOV.CO».

---

## 5. A quién va dirigido — ¿Quién puede hacerlo?

| Perfil |
|---|
| (Ej.: «Ciudadanos colombianos mayores de 18 años», «Empresas con NIT activo», etc.) |

---

## 6. Costo — ¿Cuánto cuesta?

| Campo | Valor | Fuente |
|---|---|---|
| **¿Tiene costo?** | `gratuito` · `con_costo` | SUIT |
| **Tipo de valor** | `fijo` · `smlv` · `avaluo_liquidacion` · `rango` | Entidad / SUIT |
| **Valor** | (número) | Entidad |
| **Moneda** | (ej.: `Pesos ($)`, `SMLMV`) | Entidad |
| **URL para pagar en línea** | (si hay pasarela) | Entidad |
| **Descripción del costo** | (texto aclaratorio) | Entidad |

### Cuentas de recaudo

| Entidad bancaria | Tipo de cuenta | Titular | Número | Código de recaudo |
|---|---|---|---|---|
| | | | | |

> Si el trámite es gratuito: «Gratuito». Si el costo depende de un cálculo (avalúo, rango, SMLV), la Sede lo explica con la frase predefinida del contrato (`EXPLICACION_TIPO_VALOR`).

---

## 7. Tiempo de respuesta

| Campo | Valor | Fuente |
|---|---|---|
| **Tiempo en días** | (entero) | SUIT (`tiempoObtencion`) |
| **Nota de conversión** | (si vino en horas/meses/años, decir cómo se convirtió) | sistema |

> La Sede publica «N días hábiles». Si la conversión vino de «3 MES(ES)», la nota dice «3 MES(ES) → 90 días».

---

## 8. Puntos de atención — ¿Dónde?

| Nombre | Dirección | Horario | Teléfono | Municipio | Lat, Lon |
|---|---|---|---|---|---|
| | | | | | |

> Si la fuente no publica puntos de atención, este bloque se omite. **No** se inventan direcciones.

---

## 9. Normativa — ¿Cuál es la normativa relacionada?

| Tipo | Número | Año | Artículos | URL |
|---|---|---|---|---|
| (Ley / Decreto / Resolución / Acuerdo / Circular) | | | | |

> Si la fuente no publica normativa, este bloque se publica con la frase «**La fuente oficial no publica la normativa. Consúltela en la ficha de GOV.CO**» y el enlace.

---

## 10. Consulta del estado del trámite

| Campo | Valor |
|---|---|
| **URL interna de la Sede** (constante, no se edita) | `/seguimiento?radicado=…` |

### Canales declarados por la Entidad

| Canal | ¿Habilitado? | Nombre | URL / Correo / Teléfono | Horario |
|---|---|---|---|---|
| (Sede Electrónica / Presencial / Telefónico / Web / Correo / Otro) | `true`/`false` | | | |

> Hoy **ningún** canal de la Sede está habilitado para consulta de estado (el expediente electrónico no existe). Por eso la Sede **no** publica un formulario de consulta de estado en la ficha del trámite: lo declara como «no habilitado» y enlaza a la página `/seguimiento` que dice por qué.

---

## 11. Procedencia del dato (auditoría)

| Campo | Valor | Sistema |
|---|---|---|
| **Fuente** | (ej.: `SUIT — Función Pública (entidad 0043)`) | sembrador / panel |
| **URL de la fuente** | (ej.: `https://www.funcionpublica.gov.co/...`) | sembrador / panel |
| **Fecha de obtención** | (AAAA-MM-DD) | sembrador / panel |
| **Nota** | (opcional) | sembrador / panel |
| **Origen por campo** | (mapa campo → `fuente` \| `derivado` \| `entidad` \| `ausente`) | sembrador / panel |
| **Derivados** (reglas) | (lista de reglas) | sembrador |
| **Faltantes** (campos que la fuente no declara) | (lista) | sembrador / panel |

> La Sede publica este bloque **al final de la ficha**, no al principio: es la prueba de que el dato es trazable.

---

## 12. Sellos de auditoría

- **Creado en SUIT el:** (AAAA-MM-DD)
- **Publicado en la Sede el:** (AAAA-MM-DD)
- **Última modificación por la Entidad:** (AAAA-MM-DD, si aplica)
- **Próxima revisión sugerida:** (AAAA-MM-DD, según RN-B1-006 ≤3 días hábiles tras acto administrativo)

---

## Glosario mínimo

- **SUIT** — Sistema Único de Información de Trámites, administrado por la Función Pública (DAFP). Es la **fuente oficial** del catálogo.
- **GOV.CO** — Portal del Estado colombiano. Aloja la **ficha oficial** de cada trámite (la que ve el ciudadano al hacer clic en «Ver en GOV.CO»).
- **Sede** — La sede electrónica distrital que se está construyendo. **No** es fuente de contenido institucional: lo que publica debe venir de SUIT, de GOV.CO, de un acto administrativo o de la Entidad.
- **Ausente** — Decisión consciente de **no publicar** un campo. La Sede publica la ausencia con su razón y el enlace a la fuente que sí tiene el dato.
- **Ingerir** — Traer datos de una fuente externa a la base propia. Hoy la Sede ingiere de SUIT; en el futuro puede ingerir de GOV.CO o de un CSV aprobado por la Entidad.
