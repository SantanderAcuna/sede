# PRODUCT.md — Sede Electrónica Alcaldía Distrital de Santa Marta

> **Versión:** 1.0
> **Fecha:** 2025-01-15
> **Tipo:** Portal público de sede electrónica gubernamental (Nuxt 4)
> **Repo:** `/home/sacunapolo/Documentos/sede/`

---

## 1. Qué es este producto

**Sede Electrónica** de la Alcaldía Distrital de Santa Marta, construida conforme al
artículo 14 del Decreto Ley 2106 de 2019. Es el punto único de acceso electrónico a
los trámites, servicios y canales de atención de la Entidad, integrado al ecosistema
GOV.CO del Estado colombiano.

El portal se compone de dos superficies:

| Superficie | Tecnología | Propósito |
|---|---|---|
| `sitio/` | Nuxt 4 (Vue 3) | Portal público para ciudadanos |
| `panel/` | Vue 3 + Tailwind | Superficie administrativa interna |

**Audiencia primaria:** ciudadanos que necesitan realizar trámites, consultar información
pública y acceder a servicios de la Alcaldía Distrital de Santa Marta.

**Audiencia secundaria:** ciudadanos que buscan transparencia, participación ciudadana y
seguimiento de peticiones (PQRSD).

---

## 2. Contexto normativo y de marca

### 2.1 Normas que aplican

| Norma | Aplicación |
|---|---|
| Decreto 2106/2019 Art. 14 | Obligatoriedad de la sede electrónica |
| Decreto 1078/2015 Art. 2.2.17.1.2 | Integración a GOV.CO |
| Resolución 1519/2020 (Anexo 2) | Estándares de diseño GOV.CO |
| Ley 1712/2014 | Ley de Transparencia y Acceso a la Información |
| Ley 1437/2011 Art. 60 | Ventanilla única electrónica |
| Ley 1581/2012 | Protección de datos personales |
| Directiva Presidencial 03/2019 | Uso obligatorio del Kit UI GOV.CO |

### 2.2 Identidad visual

- **Tipografía:** Nunito Sans (títulos) + Verdana (párrafos)
- **Paleta principal:** Cobalt `#0943B5`
- **Paleta Entidad:** Azul celeste `#00ADE7`, Azul noche `#00568D`
- **Logo GOV.CO:** Sin modificaciones, conforme a RN-B2-029
- **Plataforma:** Kit UI GOV.CO v9.2 (Bootstrap 5.0)

---

## 3. Estructura de navegación

### Menú principal (7 ítems, máx. 2 niveles — RF-B1-003)

```
1. Inicio                    → /
2. Transparencia             → /transparencia
3. Atención y Servicios      → /atencion, /tramites, /realizar-una-peticion, /seguimiento
4. Participa                 → /participa/* (6 subsecciones)
5. PQRSD                    → /pqrsd
6. Normativa                 → /normativa
7. Noticias                  → /noticias
```

### Páginas principales

| Ruta | Descripción | Estado |
|---|---|---|
| `/` | Portada con carrusel y secciones | ✅ Construida |
| `/transparencia` | Información de transparencia | ⚠️ En preparación |
| `/tramites` | Catálogo de trámites | ✅ Construida |
| `/tramites/[slug]` | Ficha de trámite | ✅ Construida |
| `/realizar-una-peticion` | Formulario PQRSD | ✅ Construida |
| `/seguimiento` | Seguimiento de radicado | ⚠️ En preparación |
| `/pqrsd` | Estadísticas PQRSD | ✅ Construida |
| `/normativa` | Catálogo normativo | ✅ Construida |
| `/politicas/[slug]` | Políticas públicas | ⚠️ En preparación |
| `/accesibilidad` | Declaración de accesibilidad | ✅ Construida |
| `/mapa-del-sitio` | Mapa del sitio | ✅ Construida |
| `/buscar` | Resultados de búsqueda | ⚠️ Parcial |
| `/portales` | Portales integrados | ⚠️ En preparación |
| `/noticias` | Módulo de noticias | ⚠️ En preparación |
| `/participa/*` | Secciones de participación | ⚠️ En preparación |

---

## 4. Hallazgos críticos de la auditoría

> Ver `auditoria-sede.md` para el detalle completo.
>
> **Nota:** esta tabla reproduce el registro **tal y como se encontró** —son los hallazgos antes de
> corregirlos— y por eso varios ya no describen el estado del código. El estado actual, hallazgo por
> hallazgo y con su verificación, está en `auditoria-sede.md` (§13 a §19): de los 59 elementos del
> registro (52 hallazgos y 7 regresiones) quedan **44 cerrados y 15 abiertos**, con el motivo de
> cada uno en `auditoria-sede.md` §21.

### 🔴 Bloqueantes (6)

| ID | Hallazgo | RF asociado |
|---|---|---|
| D-01 | **Banner de cookies no existe** | RF-B1-008 |
| D-02 | **Aviso de salida a sitio externo no existe** | RF-B1-071 |
| D-03 | **Buscador existe pero no busca** (sin índice ni sugerencias) | RF-B1-005, RF-B1-006 |
| D-06 | **Trazabilidad acredita artefactos que no existen** | — |
| D-15 | **No hay CMS** | RF-01-D02 |
| D-23 | **Declaración de Conformidad de Accesibilidad** | RF-B1-044 | ✅ Implementada con 3/12 elementos completados (estructura + 3 datos fijos); 9 elementos pendentes de auditoría |

### 🟠 Graves (17)

| ID | Hallazgo | RF asociado |
|---|---|---|
| D-04 | Panel sin guardias de navegación | RF-B1-079 |
| D-05 | Panel muestra datos inventados | RF-B1-078 |
| D-11 | Barra de accesibilidad no persiste en localStorage | RF-B1-044 |
| D-12 | **Carrusel arranca reproduciendo, debe empezar pausado** | RF-B1-042, RF-B1-051 |
| D-16 | Panel: modales sin gestión de foco | RF-B1-048 |
| D-17 | Secciones en "en preparación" | RF-B1-012 |
| D-25 | **Sin estilos :visited en enlaces** | RF-B1-082 |

---

## 5. Funcionalidades críticas pendientes

### 5.1 Cookie Banner (D-01) ✅

**Implementado:** `sitio/app/components/BannerCookies.vue`

El banner de cookies:
1. Se muestra a todo usuario nuevo
2. Ofrece Aceptar / Rechazar / Configurar por categoría
3. Persiste consentimiento con versión y fecha en `localStorage`
4. Caduca tras 12 meses o cambio de política

### 5.2 Aviso de Salida a Sitio Externo (D-02) ✅

**Implementado:** `sitio/app/components/ModalAvisoSalida.vue` + plugin cliente

Modal de confirmación antes de redirigir a dominio externo:
- Muestra nombre del destino y entidad responsable
- Lista blanca para dominios de confianza (GOV.CO, SECOP, SUIN, etc.)
- Plugin global que intercepta clics en enlaces externos automáticamente

### 5.3 Ajustes de accesibilidad (RF-B1-044, RNF-07-D01) ✅

**Implementado:** `sitio/app/composables/useAccesibilidad.ts` ·
`components/govco/BotonAccesibilidad.vue` · `components/govco/PanelAccesibilidad.vue`

La barra lateral del Kit se sustituyó por **un botón circular flotante** —fijo,
centrado verticalmente en el lado derecho e igual en todas las pantallas— que abre
**un panel** con los once controles que pide el brief de accesibilidad, agrupados en
cuatro categorías:

| Categoría | Controles |
|---|---|
| Contraste | Normal · Alto contraste · Colores invertidos · Escala de grises |
| Tamaño de texto | A− / A+ sobre una escala del 100 % al 200 % |
| Lectura | Más espaciado · Fuente para dislexia · Resaltar enlaces · Guía de lectura |
| Movimiento | Detener animaciones |
| Acciones | Restablecer todo · Centro de Relevo |

Características:

1. Las siete preferencias se guardan en `localStorage` **con versión de formato**;
   el formato anterior —contraste booleano, letra de −5 a +5— se migra al leer.
2. El panel es un `<dialog>` nativo abierto con `showModal()`: trampa de foco, fondo
   inerte y `Escape` correctos sin programarlos a mano.
3. Segunda vía de acceso desde el pie, para no duplicar controles (CC7) pero sí
   ofrecer varias vías (CC12).
4. Respeta `prefers-reduced-motion` siempre y **sugiere** —sin imponer— el alto
   contraste cuando el sistema lo pide.
5. Ni el botón ni el panel quedan dentro del envoltorio que invierten los modos de
   contraste, para que sus propios colores no se alteren.

### 5.4 Declaración de Accesibilidad (D-23) ✅

**Implementado:** `sitio/app/pages/accesibilidad.vue`

Página con los 12 elementos del Anexo 1 num. 9.3 de la Resolución 1519:
- Elementos completados: Entidad, Sede Electrónica (URL), Norma de referencia
- Elementos pendientes de auditoría: los 9 elementos restantes
- Canales de reporte de barreras de accesibilidad
- Tabla de estado de cumplimiento con badges visuales

### 5.5 Página 404 personalizada (RF-B1-007) ✅

**Implementado:** `sitio/app/error.vue`

Página de error 404/500 con ≥3 opciones de navegación:
- Volver a la portada
- Buscar en la sede
- Mapa del sitio
- Secciones principales (Transparencia, Servicios, Participa)

### 5.6 Galería de aplicaciones en cabecera (RF-B3-068) ✅

**Implementado:** `sitio/app/layouts/default.vue` + `config/sitemap.ts`

Galería de aplicaciones integrada en la cabecera de la sede:
- Portal GOV.CO
- Carpeta Ciudadana (SECOP)
- CIIU (DIAN)

### 5.7 Mapa del sitio autoactualizado (RF-B1-010) ✅

**Implementado:** `sitio/app/config/sitemap.ts` + `sitio/app/pages/mapa-del-sitio.vue` + `sitio/server/routes/sitemap.xml.ts`

- Configuración centralizada de rutas en `config/sitemap.ts`
- El mapa del sitio (`mapa-del-sitio.vue`) se genera desde la config
- El `sitemap.xml` se genera desde la misma config
- Ambos se actualizan automáticamente al cambiar la navegación

---

## 6. Componentes GOV.CO implementados

| Componente | Archivo | Estado |
|---|---|---|
| BarraSuperior | `components/govco/BarraSuperior.vue` | ✅ |
| BotonAccesibilidad | `components/govco/BotonAccesibilidad.vue` | ✅ (círculo flotante, 11 controles) |
| PanelAccesibilidad | `components/govco/PanelAccesibilidad.vue` | ✅ (`<dialog>` nativo) |
| CabeceraGovco | `components/govco/CabeceraGovco.vue` | ✅ |
| MenuNavegacionGovco | `components/govco/MenuNavegacionGovco.vue` | ✅ |
| MigaDePanGovco | `components/govco/MigaDePanGovco.vue` | ✅ |
| CarruselGovco | `components/govco/CarruselGovco.vue` | ✅ (autoplay: false) |
| PiePaginaGovco | `components/govco/PiePaginaGovco.vue` | ✅ |
| BuscadorGovco | `components/govco/BuscadorGovco.vue` | ⚠️ Sin funcionalidad |
| VolverArriba | `components/govco/VolverArriba.vue` | ✅ |
| TarjetaInformacionGovco | `components/govco/TarjetaInformacionGovco.vue` | ✅ |
| GaleriaAplicacionesGovco | `components/govco/GaleriaAplicacionesGovco.vue` | ✅ (integrada en cabecera) |
| SeccionEnPreparacion | `components/SeccionEnPreparacion.vue` | ✅ |
| BannerCookies | `components/BannerCookies.vue` | ✅ |
| ModalAvisoSalida | `components/ModalAvisoSalida.vue` | ✅ |

---

## 7. Archivos de configuración centralizada

| Archivo | Propósito |
|---|---|
| `app/config/sitemap.ts` | Definición centralizada de todas las rutas públicas con metadatos para menú y sitemap |

---

## 7. Stack tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Framework | Nuxt | 4.x |
| UI | Vue | 3.x |
| Estilos | Bootstrap | 5.0.2 |
| Kit UI | GOV.CO | v9.2 |
| CSS personalizado | `sitio.css` | — |
| Tipoografía | Nunito Sans + Verdana | — |
| Deploy | — | Pendiente |

---

## 8. Restricciones de diseño

1. **Sin invenciones de contenido:** el sitio prefiere no publicar antes que publicar relleno
2. **Sin datos mock en producción:** el panel no puede mostrar cifras inventadas
3. **Cumplimiento WCAG 2.1 AA:** como mínimo,meta de accesibilidad
4. **0 vínculos rotos:** ningún enlace debe devolver 404
5. **Responsive hasta 320 px:** reflujo sin scroll horizontal
6. **60–80 caracteres por línea:** medida de lectura óptima

---

## 9. Producto no construido (roadmap)

| Módulo | Descripción | Prioridad |
|---|---|---|
| CMS | Gestión de contenidos con flujo editorial | Alta |
| Módulo de noticias | Carrusel de noticias con imagen 4:3 o 16:9, título ≤150 car., descripción ≤200 car. (RF-B1-011) | Alta |
| Buscador funcional | Índice, autocompletado, tolerancia a errores (RF-B1-005/006) | Alta |
| Integración SUIT | Catálogo vinculado con 6 atributos | Media |
| Auth del panel | Autenticación, roles, guardias (RF-B1-079) | Alta |
| Notificaciones | Sistema de alertas multicanal | Media |
| Seguimiento PQRSD | Estados estandarizados GOV.CO (RF-B3-127) | Media |
| Declaración de Conformidad (auditoría) | Ejecutar la auditoría de accesibilidad para completar los 9 elementos pendientes (D-23) | **Alta (crítico)** |

---

## 10. Implementaciones completadas (sesión actual)

### D-01: Banner de cookies ✅
- Componente: `sitio/app/components/BannerCookies.vue`
- Consentimiento versionado, caducidad 12 meses, 3 categorías

### D-02: Aviso de salida a sitio externo ✅
- Componente: `sitio/app/components/ModalAvisoSalida.vue`
- Plugin: `sitio/app/plugins/avisoSalida.client.ts`
- Lista blanca: gov.co, secop.gov.co, secopii.gov.co, suin.gov.co, dian.gov.co

### D-11: Persistencia de accesibilidad ✅
- Componente: `sitio/app/composables/useAccesibilidad.ts`
- Preferencia guardada en `localStorage` al cambiar y restaurada al cargar

### D-12: Carrusel pausado al inicio ✅
- Componente: `sitio/app/components/govco/CarruselGovco.vue`
- `autoplay: false` por defecto

### D-23: Declaración de Conformidad de Accesibilidad ✅
- Página: `sitio/app/pages/accesibilidad.vue`
- 12 elementos del Anexo 1 num. 9.3 de la Res. 1519

### RF-B1-007: Página 404 personalizada ✅
- Página: `sitio/app/error.vue`
- ≥3 opciones de navegación (portada, buscador, mapa, secciones populares)

### RF-B3-068: Galería de aplicaciones ✅
- Componente integrado en la cabecera
- Portal GOV.CO, Carpeta Ciudadana (SECOP), CIIU (DIAN)

### RF-B1-010: Mapa del sitio autoactualizado ✅
- Configuración centralizada: `sitio/app/config/sitemap.ts`
- Mapa HTML: `sitio/app/pages/mapa-del-sitio.vue` (derivado de la config)
- Sitemap XML: `sitio/server/routes/sitemap.xml.ts` (derivado de la config)

---

*Este documento representa la verdad de producto y debe mantenerse sincronizado con los cambios del proyecto.*
