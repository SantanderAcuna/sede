# Criterios de Aceptación Gherkin — Flujos Críticos

> **Propósito:** consolidar en Given/When/Then los criterios de aceptación de los flujos críticos de los módulos 01 y 02, listos para automatizar con Pest (PHP) y Playwright (TypeScript).
> **Trazabilidad:** cada criterio referencia el RF origen.

---

## Módulo 01 — Estructura-Identidad

### CA-01-001 — Render del top bar GOV.CO
- **Origen:** RF-01-001
- **Feature:** Top bar persistente en todas las páginas
```gherkin
Feature: Top bar GOV.CO visible en todas las páginas

  Scenario: Ciudadano carga la página de inicio
    Given el usuario accede a "https://www.santamarta.gov.co/"
    When la página termina de cargar
    Then debe existir un <div data-testid="top-bar-govco"> con altura computada de 56 px
    And el color de fondo debe ser exactamente rgb(9, 67, 181)
    And debe haber un <a href="https://www.gov.co/home/"> como primer enlace focalizable

  Scenario: Botón de búsqueda del top bar mantiene accesibilidad con teclado
    Given el usuario presiona Tab repetidamente desde la barra de URL
    When llega al primer enlace focalizable
    Then ese enlace es el logo GOV.CO (focus-visible azul 3 px)
```

### CA-01-007 — Menú principal obligatorio
- **Origen:** RF-01-007
```gherkin
Feature: Menú principal con 4 ítems obligatorios

  Scenario: Render del menú en home
    Given la página "/" carga
    When el <nav aria-label="Menú principal"> se renderiza
    Then los ítems aparecen en este orden: Inicio, Transparencia y acceso a la información pública, Atención y Servicios a la Ciudadanía, Participa
    And el número total de ítems de primer nivel no excede 7

  Scenario: Apertura de submenú con teclado
    Given el usuario está en un submenú del menú principal
    When presiona ArrowDown
    Then el foco se mueve al siguiente ítem del submenú
    When presiona Escape
    Then el submenú se cierra y el foco vuelve al ítem padre
```

### CA-01-009 — Buscador predictivo
- **Origen:** RF-01-009
```gherkin
Feature: Buscador con autocompletado y tolerancia a errores

  Scenario: Autocompletado con tolerancia ortográfica
    Given el usuario escribe "transparenci" en el buscador
    When pasan 250 ms desde la última tecla (debounce)
    Then debe aparecer un panel de sugerencias con hasta 10 ítems
    And al menos uno debe incluir "transparencia" en el título
    And no debe haber sugerencias con dominios externos (gob.co, gov.uk, etc.)

  Scenario: Búsqueda sin resultados
    Given el usuario escribe "xyzabc123sinresultados"
    When ejecuta la búsqueda
    Then el sistema muestra "No se encontraron resultados" y sugiere 5 búsquedas relacionadas
```

### CA-01-015 — Página 404 personalizada
- **Origen:** RF-01-015
```gherkin
Feature: 404 personalizado para rutas inexistentes

  Scenario: Acceso a ruta inexistente
    Given el usuario accede a "/ruta-inexistente-xyz"
    When el servidor responde
    Then el código HTTP es 404
    And la página contiene un buscador, un menú resumido y un CTA "Volver al inicio"
    And el código de respuesta no es 200 (no se enmascara como éxito)

  Scenario: 404 devuelve JSON:API en API
    Given un cliente API accede a "/api/v1/identidad/seccion-inexistente"
    When el servidor responde
    Then el código HTTP es 404
    And el body sigue la especificación JSON:API 1.0 con un objeto "errors"
```

### CA-01-017 — Banner de cookies
- **Origen:** RF-01-017
```gherkin
Feature: Consentimiento de cookies explícito

  Scenario: Primera visita
    Given el usuario nunca ha interactuado con el banner de cookies
    When carga cualquier página
    Then aparece el banner con opciones "Aceptar", "Rechazar", "Configurar"
    And las cookies de analítica NO se cargan (verificable en DevTools > Application > Cookies)

  Scenario: Revocación desde el footer
    Given el usuario previamente aceptó cookies
    When hace clic en "Revocar consentimiento de cookies" en el footer
    Then la preferencia se actualiza a "rechazado" y las cookies de analítica se eliminan
```

### CA-01-021 — Componente paginación WCAG
- **Origen:** RF-01-021
```gherkin
Feature: Paginación accesible

  Scenario: Navegación con teclado
    Given el usuario está en la página 1 del listado de noticias
    When presiona Enter sobre el botón "Siguiente"
    Then la URL cambia a "?page=2"
    And el foco se mantiene en el control
    And el botón "Página 2" tiene aria-current="page"

  Scenario: Tamaño táctil en móvil
    Given el viewport es 375 px
    When el usuario ve el paginador
    Then todos los botones tienen al menos 44x44 px (touch target WCAG 2.5.5)
```

---

## Módulo 02 — Transparencia

### CA-02-001 — Render de las 10 subsecciones
- **Origen:** RF-02-001
```gherkin
Feature: Menú Transparencia con 10 subsecciones obligatorias

  Scenario: Render del menú en /transparencia
    Given el usuario accede a "/transparencia"
    When la página carga
    Then el <nav> del submenú contiene exactamente 10 ítems
    And los ítems son: Información de la entidad, Normativa, Contratación, Planeación/presupuesto/informes, Trámites, Participa, Datos abiertos, Grupos de interés, Obligación de reporte específico, Tributaria
    And cada ítem tiene al menos un documento publicado
```

### CA-02-006 — Sincronización SIGEP
- **Origen:** RF-02-006
```gherkin
Feature: Directorio de servidores sincronizado con SIGEP

  Scenario: Nuevo servidor registrado en SIGEP aparece en el sitio
    Given un servidor público es registrado en SIGEP con cargo "Jefe de Oficina TIC"
    When pasan 24 horas hábiles
    Then el directorio en "/transparencia/informacion-de-la-entidad/directorio" contiene al servidor
    And la sincronización se registró en log_auditoria

  Scenario: Servidor desvinculado desaparece
    Given un servidor se desvincula de SIGEP
    When pasan 24 horas hábiles
    Then el servidor ya no aparece en el directorio público
```

### CA-02-011 — Plan de Acción antes del 31-ene
- **Origen:** RF-02-011
```gherkin
Feature: Plan de Acción anual vigente

  Scenario: Validación el 31 de enero
    Given llega la fecha "2026-01-31"
    When el job "PlanAccion:VerificarVigencia" ejecuta
    And NO hay un documento publicado con categoría="plan_accion" y vigencia=2026
    Then el sistema notifica al administrador de cumplimiento (email + panel)
    And el ítem ITA correspondiente se marca como "No cumple"

  Scenario: Plan vigente antes de la fecha límite
    Given el editor carga el "PlanAccion2026.pdf" el 2026-01-15
    When llega el 2026-01-31
    Then el job verifica y el ítem ITA aparece como "Cumple"
```

### CA-02-021 — Versionado de documentos
- **Origen:** RF-02-021
```gherkin
Feature: Versionado sin romper URL canónica

  Scenario: Reemplazo de un documento
    Given el documento "PlanAccion2026.pdf" está publicado en "/transparencia/planeacion/"
    And tiene versión "v1" con hash sha256:abc123...
    When el editor lo reemplaza con "PlanAccion2026-v2.pdf"
    Then la URL pública sigue siendo "/transparencia/planeacion/plan-accion-2026.pdf"
    And la URL histórica "/transparencia/planeacion/plan-accion-2026.pdf?v=2025-12-15" sigue accesible
    And ambos archivos tienen su hash SHA-256 registrado

  Scenario: Soft-delete preserva auditoría
    Given el editor elimina un documento (soft-delete)
    When se consulta el endpoint GET /api/v1/transparencia/documentos/{id}?include=deleted
    Then el documento aparece con atributo "deleted_at" poblado
    And se registra en log_auditoria quién y cuándo eliminó
```

### CA-02-024 — Hash SHA-256 publicado
- **Origen:** RF-02-024
```gherkin
Feature: Integridad documental verificable

  Scenario: Hash calculado al cargar
    Given el editor carga "PlanAccion2026.pdf" con bytes B
    When el sistema procesa el archivo
    Then el hash SHA-256 calculado se persiste en la BD
    And la vista pública muestra el hash junto al enlace de descarga

  Scenario: Verificación por terceros
    Given el usuario descarga el PDF
    When calcula SHA-256 localmente
    Then el hash coincide con el publicado
```

### CA-02-027 — Búsqueda full-text
- **Origen:** RF-02-027
```gherkin
Feature: Búsqueda full-text con tolerancia ortográfica

  Scenario: Búsqueda con error ortográfico
    Given el usuario busca "prespuesto" (error en "presupuesto")
    When ejecuta la búsqueda
    Then los resultados incluyen documentos con "presupuesto" en el título o descripción
    And la coincidencia está resaltada con <mark>

  Scenario: Filtro por subsección
    Given el usuario busca "ICA"
    And filtra por subsección "Tributaria"
    When ejecuta la búsqueda
    Then los resultados se limitan a documentos de la subsección Tributaria que mencionan "ICA"
```

### CA-02-029 — Tablero ITA automático
- **Origen:** RF-02-029
```gherkin
Feature: Tablero ITA interno

  Scenario: Detección automática al publicar imagen sin alt
    Given el editor carga un documento de transparencia con imagen sin atributo alt
    When el sistema valida el cumplimiento
    Then el ítem ITA "Imágenes con texto alternativo" se marca como "No cumple"
    And se notifica al responsable

  Scenario: Recalculo del tablero
    Given el editor corrige el atributo alt
    When guarda el documento
    Then el ítem ITA vuelve a "Cumple" en el siguiente cálculo
```

---

## Cross-cutting (transversal a ambos módulos)

### CA-CROSS-001 — Health check de la API
```gherkin
Feature: Health check de la API

  Scenario: Servicio operativo
    Given la API está en ejecución
    When se hace GET a "/api/v1/salud"
    Then responde HTTP 200 con body {"status":"ok","timestamp":"...","version":"..."}

  Scenario: Servicio caído
    Given la BD no responde
    When se hace GET a "/api/v1/salud"
    Then responde HTTP 503 con body {"status":"degraded","componentes":{"bd":"down"}}
```

### CA-CROSS-002 — Rate limiting
- **Origen:** RNF-SEG-03
```gherkin
Feature: Rate limiting en API pública

  Scenario: Exceder límite
    Given un cliente hace 60 requests en 1 minuto a "/api/v1/transparencia/documentos"
    When intenta la request 61
    Then responde HTTP 429 con cabecera "Retry-After: 60"
```

### CA-CROSS-003 — Versionado URL
```gherkin
Feature: Versionado de API por URL

  Scenario: Prefijo /api/v1
    Given la API está publicada
    When un cliente hace GET a "/api/v1/transparencia/documentos"
    Then el backend responde correctamente

  Scenario: Prefijo inexistente
    Given la API v2 NO está publicada
    When un cliente hace GET a "/api/v2/transparencia/documentos"
    Then responde HTTP 404 con error JSON:API "Recurso no encontrado"
```

---

## Tabla de cobertura

| CA | RF origen | Módulo | Cliente |
|---|---|---|---|
| CA-01-001..017 | RF-01-001..017 | 01 | sitio |
| CA-01-021 | RF-01-021 | 01 | sitio |
| CA-02-001..029 | RF-02-001..029 | 02 | sitio/panel |
| CA-CROSS-001..003 | RNF-SEG-03 + transversales | ambos | sitio/panel |

> **Total criterios automatizables:** 19 Gherkin scenarios; automatización objetivo 100% con Pest + Playwright.
