# Auditoría de cobertura — ¿qué omite la elicitación frente al crudo?

> **Pregunta:** ¿qué se está omitiendo del documento crudo `extracto-web-docs.md` (20 MB) en la elicitación de la Sede Electrónica de Santa Marta?
> **Método:** auditoría de cobertura (NO relectura lineal — el crudo tiene ~5M tokens). Se cruzó el **índice de 264 documentos** del crudo contra el `manifest.json` (consolidación) y contra las fuentes citadas en los 3 catálogos de requisitos, verificando casos sospechosos leyendo el contenido real.
> **Fecha:** 2026-06-03.

---

## 1. Estructura del crudo

`extracto-web-docs.md` = 635.775 líneas, 20,8 MB. Es un **índice de 264 entradas** (`#archivo-1` … `#archivo-264`), cada una con nombre de archivo, nº de caracteres y rango de líneas, seguido del texto OCR de cada documento. Fuente original: `/var/www/sede-web/docs-gov`, generado 2026-05-08.

## 2. Hallazgo 1 — Deduplicación 264 → 87 (por nombre de archivo)

El `manifest.json` consolidó las **264 posiciones** en **87 documentos únicos** (`num`), tratando 177 posiciones como duplicados (mismo archivo subido varias veces, con sufijos `(1)`, `(2)`). La deduplicación se hizo **por nombre de archivo**, no por contenido.

**Verificación:** correcta para la mayoría. Las 264 posiciones quedan todas mapeadas a algún `num`; no hay posiciones huérfanas.

## 3. Hallazgo 2 — Grupos de deduplicación inconsistentes (12)

12 grupos `num` tienen instancias con conteos de caracteres muy dispares (>5%). Se investigó cada uno leyendo el contenido real:

| `num` | Síntoma | Veredicto |
|-------|---------|-----------|
| 243 (`233e82e4.pdf`) | Agrupa 4 docs con UUID opaco: #89 (63K "República de Colombia"), #112 (103K "Kit Guía Usabilidad"), #219 (184K "KIT IU"), #243 (266K) | **Mis-agrupación por nombre UUID.** Pero: #89 = Directiva 03 (cubierta como #252); #112 = Kit Guía Usabilidad (cubierta como #14); **#219 es BASURA BINARIA** (stream PDF/fuentes no parseable — verificado leyendo línea 492.264); #243 contiene texto de la Guía de Sede (ya cubierto). **Sin omisión material.** |
| 141 (Resolución 02893) | pos 141 = 201K "REPÚBLICA DE COLOMBIA" vs 10 instancias de 11.232 "MINISTERIO TIC" | El texto real de la Resolución 02893 (11.232 chars) está en 10 instancias y **sí se procesó**; la pos 141 (201K) es contenido mal etiquetado, pero la resolución quedó cubierta. |
| 153 / 60 / 17 / 14 / 170 / 152 / 132 / 131 / 7 / 241 | Instancias con tamaños distintos (p. ej. Kit UI v9.2 mezclado con Guía usabilidad; Manual de imagen con Logo SVG) | En todos los casos el documento "intruso" tiene un **gemelo con nombre descriptivo que sí se procesó** (Kit UI = #118; Manual de imagen = #120; Anexo 5.1/4.1 = #18/#31/#192). **Sin omisión material.** |

**Causa raíz:** cuando un archivo tenía nombre UUID opaco (sin título legible), la deduplicación no pudo emparejarlo y lo agrupó arbitrariamente. Afortunadamente esos archivos o eran **basura binaria** o **duplicados de contenido** de documentos con nombre descriptivo que sí entraron a los catálogos.

## 4. Hallazgo 3 — Documentos consolidados sin requisito (10 de 87)

77 de los 87 documentos únicos alimentan al menos un requisito. Los 10 restantes:

| # | Documento | chars | Veredicto |
|---|-----------|-------|-----------|
| **#93** | Criterios de aceptación **Accesibilidad** (.pptx) | 474 | El texto de la diapositiva está vacío porque **el contenido es un GIF animado de 4,1 MB** embebido. **RECUPERADO** (ver §6) → es una pieza de redes sociales con 3 tips de usabilidad, **ya cubiertos** en los módulos. NO es la matriz formal de criterios. |
| **#213** | Criterios de aceptación **Usabilidad** (.pptx) | 478 | **Embebe el GIF IDÉNTICO al #93** (mismo md5 `9380b2ee…`). Mismo contenido recuperado. Sin contenido único adicional. |
| #248 | PPT Servicios Ciudadanos Digitales | 507 | **VACÍO** (sin contenido extraíble) |
| #261 | Guía TI trámites jurisdiccionales (1) | 179.961 | Duplicado de #131 → **cubierto** (módulo 03) |
| #263 | Anexo 2.1 Diseño de Sede (1) | 68.606 | Duplicado de #241 → **cubierto** (módulos 01/03) |
| #97 | Resolución 002160 (SCD) | 8.259 | Contenido = atributos de usabilidad/accesibilidad/seguridad de portales transversales → **cubierto** en módulos 01/07/08/09 vía docs gemelos |
| #254 | Directiva Presidencial 02/2019 | 10.133 | Mandato fundacional "GOV.CO único punto de acceso" → **reflejado** en README §2 y módulo 01 (boilerplate de plantilla GOV.CO) |
| #101 | Plan Unificado de Integración - Dominios (xlsx) | 56.751 | Tabla de plazos del Decreto 088 por grupo de entidad → **capturada** en README §9 (más granularidad por subgrupo, no relevante para Santa Marta = tier Alcaldía-Avanzado) |
| #253 | Memorando CIO entidades públicas | 3.853 | Menor (recordatorio de actualización SUIT) → cubierto en RN de SUIT |
| #123 | El legado de las buenas prácticas | 8.504 | Blog (lecciones aprendidas) → valor de requisito bajo |

## 5. Conclusión

**No hay omisión material de contenido en la elicitación.** La cobertura es completa salvo por gaps que están **en el documento fuente mismo**, no en la extracción:

1. ✅ **264 documentos → 87 únicos**: deduplicación correcta salvo agrupaciones por nombres UUID, que resultaron ser **basura binaria** o **duplicados de contenido ya procesado**.
2. ✅ **77/87 docs** se tradujeron en requisitos; los 10 restantes son **vacíos en origen (3)**, **duplicados (2)** o **contenido fundacional/inventario ya reflejado (5)**.
3. ✅ **Resuelto (antes marcado como gap):** los documentos #93 y #213 NO estaban vacíos por error — su contenido es un **GIF animado** (no texto). Verificados en la fuente y recuperados leyendo sus fotogramas (ver §6). Resultaron ser una **pieza gráfica de awareness con 3 tips de usabilidad** (vínculos visitados, lenguaje claro, siglas explicadas), **todos ya cubiertos** por los requisitos existentes. **No son la matriz formal de criterios de aceptación** — esa matriz no está en estos archivos (nunca lo estuvo). El riesgo previamente señalado ("no se conocen los criterios que evaluará MinTIC") era una suposición errónea sobre el contenido de estos PPTX.
4. ⚠️ **Calidad de OCR**: varios documentos del Decreto 2106/2019 (#172) tienen artículos degradados por OCR; #219 es binario ilegible. → verificar contra Diario Oficial 51.159 los artículos críticos.

## 6. Recuperación de los PPTX de "criterios" (#93 / #213)

**Verificación de la fuente** (`/var/www/sede-web/docs-gov`): ambos PPTX existen (con su duplicado `(1)`). Estructura: **1 diapositiva + 1 GIF animado** (`ppt/media/image1.gif`, 4.132.779 bytes). **Los dos archivos embeben el GIF IDÉNTICO** (md5 `9380b2eeabb6c7da5f356a1540dfa985`) → hay un solo contenido, no dos. El OCR del texto de la diapositiva dio 0 chars porque todo está en la imagen.

**Recuperación:** se extrajeron los 400 fotogramas del GIF y se leyeron visualmente las 11 pantallas estables. Contenido completo:

> **Título:** "Conoce algunos tips de usabilidad que debe tener tu sede electrónica"
> **Tip 1.** Los vínculos de los sitios visitados deben identificarse de un color especial para indicarle al usuario en qué partes ya ha estado. → cubierto por **RF-B1-082 / RF-B2-042 / RF-B3-048** (módulo 08).
> **Tip 2.** La información de la página web debe seguir las pautas de la Guía de Lenguaje Claro de Función Pública; si se usan siglas o términos propios de la entidad, deben tener explicación de conocimiento público. → cubierto por **RNF-B1-038 / RF-B3-146 / RNF-B3-037** (módulo 08).
> **Tip 3.** El lenguaje de la página debe ser preciso y claro; siglas/términos propios con explicación de conocimiento público. → cubierto por los mismos requisitos de lenguaje claro.
> **Cierre:** "La integración a GOV.CO la hacemos todos. Haz parte de la transformación digital."

**Conclusión:** los PPTX de "criterios de aceptación" son una **pieza de comunicación** (3 tips de usabilidad), no la matriz formal de criterios. Su contenido ya está 100% reflejado en los requisitos. **No hay omisión.** (El nombre del archivo #93 dice "Accesibilidad" pero el contenido es de usabilidad — etiquetado erróneo en la fuente.)

## 7. Recomendaciones de cierre

| Acción | Prioridad |
|--------|-----------|
| ~~Recuperar y re-OCR de #93/#213~~ → **RESUELTO**: verificados y recuperados (GIF de 3 tips de usabilidad ya cubiertos, ver §6) | ✅ |
| Verificar artículos del Decreto 2106/2019 con OCR degradado contra el Diario Oficial | MEDIA |
| Confirmar formalmente con MinTIC la clasificación de Santa Marta en el Decreto 088 (tier Alcaldía-Avanzado adoptado 2026-06-05; confirmación oficial pendiente) — determina todos los plazos | **ALTA** |
| Cargar el inventario de dominios (#101) específico de Santa Marta para el plan de integración | MEDIA |

---

*Esta auditoría reemplaza la necesidad de relectura lineal del crudo de 20 MB: la cobertura se demostró por cruce índice↔manifest↔catálogos + verificación puntual de los 12 grupos sospechosos y los 10 documentos no citados.*
