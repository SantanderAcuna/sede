# Estado de ejecución del panel de gestión de la Sede (Fases 1–7)

Fecha: 2026-11-12. Complementa `plan-panel-gestion-sede-2026-11-12.md` (la propuesta original). Las decisiones de José que habilitaron las fases: banner = carrusel de la portada; trámites desde cero en el panel; editor con licencia MIT (Tiptap); alcance = solo las vistas de su mensaje; admin y súper administrador administran usuarios/roles; CSP en solo-reporte con bloqueo detrás de variable de entorno (apagada); margen derecho para el botón de accesibilidad.

## Fases (todas en `develop`; promoción a `staging` en el PR #575)

| Fase | Contenido |
|---|---|
| 1 | Editor de texto (Tiptap), saneado HTML en servidor, subida de imágenes |
| 2 | Noticias con cuerpo HTML saneado (migración reversible) |
| 3 | Políticas y Entidad: tabla + vistas propias |
| 4 | Trámites y servicios (API + panel) |
| 5 | Usuarios, roles y permisos con reglas anti-escalada y auditoría |
| 6 | Carrusel de la portada (banners): API, panel, sitio con respaldo |
| 7 | Matriz responsive + axe (14 pantallas × 6 anchos): un contraste corregido en Perfil |

PR de las fases 1–4: #567 a #572. Fase 5: #573. Fase 6: #574. Fase 7 y promoción: #575.

## Verificación (hechos)
- CI verde en cada PR de fase (Backend, Panel, Contrato, Sitio, Pila, Dependencias, Credenciales, Puerta visual).
- Pruebas locales al cierre de la Fase 6: backend 622, panel 600, sitio 742.
- Navegador real (Playwright) en cada fase; axe WCAG 2.1 AA sin violaciones y sin desborde horizontal entre 320 y 1920 px.

## Pendiente (conscientemente fuera de alcance)
- MFA obligatorio para quien administra usuarios (documentado en decisiones).
- Búsqueda pública sin normalizar tildes (preexistente).
- Los 14 módulos del menú sin construir siguen «En construcción» hasta que José los priorice.
- Recorrido en staging con sesión iniciada por José (yo no introduzco credenciales).

El registro detallado de decisiones tomadas sin autorización se entrega en el archivo «decisiones-tomadas-sin-autorizacion.md».

## Actualización: trámites reconstruidos desde cero (módulo 03, Fase A)

Estado al 2026-11-12 (verificado en GitHub, no de memoria):
- **#576** (feat/tramites-propios → develop, squash): catálogo relacional propio (tablas `dependencias`, `tramite_categorias`, `tramites`, pasos, requisitos, documentos, puntos de atención, normas, canales, audiencias, cuentas, valoraciones), sin SUIT. Conserva los 123 trámites reales con carga única editable. Capas: contratos de repositorio y servicio, servicios, repositorios, form requests, policy, resources. Contrato OpenAPI y tipos regenerados.
- Sitio: directorio de trámites (grupos trámites/OPA/consultas, búsqueda, facetas, paginación) y ficha con etapas, requisitos, puntos de atención con mapa, normas y valoración (FÁCIL/DIFÍCIL + estrellas, con límite por IP).
- Panel: formulario conservado y adaptado (tipo, dependencia, solicitantes, pasos, requisitos por paso).
- Pruebas locales: backend 556, panel 600, sitio 747. axe sin violaciones y sin desborde entre 320 y 1920 px en lista y ficha.
- **#577** y **#579** (develop → staging, merge commit). Despliegue de staging a pruebas: éxito.
- **#578**: se corrigió una prueba inestable de usuarios (nombre aleatorio de Faker que coincidía con `q=ana`).

### Abierto
- Asignar `dependencia` a los trámites (venía vacía) y revisar los temas automáticos.
- Fase B (pago PSE, radicado, expediente, Carpeta Ciudadana) depende de los módulos 09/10. Lectura/análisis de valoraciones en el panel no construida.

## Actualización 2: tarjetas a GOV.CO y directorio estilo Montería

Estado al 2026-11-12 (verificado en GitHub y en la página real de staging):
- **#580**: cada trámite (portada y directorio) abre su ficha de GOV.CO en pestaña nueva. `enlaceTramite()` solo acepta `https` de `gov.co` o subdominios; sin URL válida cae a la ficha propia. 123/123 URLs pasan.
- **#582**: el directorio pasa a **filas** con panel lateral (buscador + filtros en acordeones del Kit), diez por página, datos con ícono a la derecha y rótulo «Trámite en gov.co». Hover del título a 6,51:1 (el del Kit daba 3,93:1).
- **#583**: corrige la puerta móvil, que buscaba una clase que #576 eliminó (error mío).
- **#581** y **#584**: promociones a staging; despliegue a pruebas exitoso. En staging, la puerta móvil ya reporta «filas del listado de trámites: 10 de ≥ 44 px, cubiertas por su enlace».
- Local: sitio 758 pruebas, tipos, `stylelint`, puerta del Kit y puerta visual contra `develop` en verde; axe=0 sin desborde a 320–1920 px.

### Decisión pendiente de José
- El buscador y `/tramites/{slug}` siguen entrando a la ficha propia (con la valoración con estrellas). Redirigirlos a GOV.CO la dejaría inalcanzable.
- Panel lateral en el neutro del sitio; Montería usa beige y no hay un token equivalente.

### Fallos de Navegadores en staging que siguen abiertos (no son de estos cambios)
- Carrusel de la portada con **1 sola diapositiva** («1 de 1»); las pruebas esperan varias. Falta cargar banners.
- `GET /api/v1/banners` no está en la API simulada (API-SIM).
- Comprobación de cookies no esenciales (XSRF-TOKEN, sede-session): ya fallaba en #575.
