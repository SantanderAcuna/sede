# Matriz de trazabilidad — Sede Electrónica Alcaldía Distrital de Santa Marta

> Fase 8 (Trazabilidad). Conecta cada requisito con su fuente, necesidad, stakeholder, criterio de aceptación, contradicción/riesgo y estado. Estructurada por módulo (691 ítems). Stakeholder por defecto: Ciudadano (titular) salvo indicación. Objetivo (Obj-n) refiere a README §2; contradicciones C-0x a README §8.
> Estado: **Documentado** (con fuente literal) · **Inferido** · **En conflicto** (afectado por C-0x) · **Pendiente** (pregunta abierta asociada).
> Para no diluir la trazabilidad, los **RF** se trazan fila por fila; los **RNF/RN** se agrupan por temática con sus IDs explícitos al final de cada módulo. UC y HU se referencian en la columna correspondiente. Diagrama Mermaid en la columna D-xx (ver `diagramas.md`).

## Objetivos de referencia (README §2)
- **Obj-1** Acceso digital unificado · **Obj-2** Integración a GOV.CO · **Obj-3** Cumplir Gobierno Digital / cerrar déficit ITA · **Obj-4** No pedir documentos que el Estado ya tiene · **Obj-5** Digitalizar/automatizar (Decreto 088) · **Obj-6** Reducir tiempo de interacción · **Obj-7** Proteger datos personales.

---

## Módulo 01 — Estructura e Identidad GOV.CO

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-001 Top bar GOV.CO | #3,#7,#21,#225,#241,#70 | Obj-2 | Ciudadano / MinTIC | — | Barra visible antes del contenido | A-01 (¿obligación o facultad?) | D-06 | En conflicto (A-01) |
| RF-B1-002 Footer GOV.CO | #7,#21,#225,#241,#70 | Obj-2 | Ciudadano | HU-B3-028 | Footer completo con +57 | — | D-06 | Documentado |
| RF-B2-006 Teléfonos +57 | #70,#125,#130 | Obj-2 | Ciudadano | — | Líneas con +57 salvo 018000 | — | — | Documentado |
| RF-B1-043 Logo Alcaldía | #7,#170,#118 | Obj-2 | Ciudadano | — | Clic logo → inicio | — | — | Documentado |
| RF-B1-098 Identidad Kit UI | #7,#252,#118,#120 | Obj-2 | Ciudadano | — | 0 violaciones de marca | Kit UI Cobalt inamovible? | D-06 | Inferido (color) |
| RF-B3-055 Botón idioma | #118,#102 | Obj-1 | Ciudadano | — | Idioma persistente | Decisión #16: DIFERIDO (castellano por ahora) | — | Diferido (Could) 2026-06-05 |
| RF-B3-061 Volver arriba | #118 | Usabilidad | Ciudadano | — | Aparece en scroll | — | — | Documentado |
| RF-B1-003 Menú principal | #7,#21,#225,#241 | Obj-2 | Ciudadano | — | 4 ítems en orden, ≤7 | — | D-01 | Documentado |
| RF-B1-004 Menú responsive | #7 | Usabilidad | Ciudadano | HU-B1-010 | Hamburguesa <768px | — | — | Documentado |
| RF-B1-005 Buscador interno | #7,#21,#241,#60 | Obj-1 | Ciudadano | HU-B1-017 | Resultados solo de la sede | — | — | Documentado |
| RF-B1-006 Autocompletado | #170,#60 | Usabilidad | Ciudadano | — | ≤10 sugerencias, tolera errores | — | — | Documentado |
| RF-B1-010 Mapa del sitio | #17,#241,#70 | Accesibilidad | Ciudadano | — | sitemap.xml accesible | — | — | Documentado |
| RF-B2-038 Breadcrumb | #60,#70,#118 | Usabilidad | Ciudadano | — | Ruta completa en internas | — | — | Documentado |
| RF-B2-041 Sin vínculos rotos | #60,#70,#129 | Usabilidad | Ciudadano | — | 0 HTTP 404 | — | — | Documentado |
| RF-B1-011 Noticias home | #7 | Obj-1 | Ciudadano | — | Orden cronológico inverso | — | — | Documentado |
| RF-B1-042 Carrusel | #7,#23,#118 | Accesibilidad | Ciudadano | — | Stop detiene; pausa por defecto | — | — | Documentado |
| RF-B1-007 Página 404 | #7,#170,#60 | Usabilidad | Ciudadano | — | 404 personalizada con ≥3 opciones | — | — | Documentado |
| RF-B1-071 Aviso salida externa | #241,#70,#132 | Obj-2 | Ciudadano | — | Modal antes de redirigir | — | — | Documentado |
| RF-B1-008 Banner cookies | #7,#21,#241,#70 | Obj-7 | Ciudadano | HU-B3-023 | No esencial inactiva por defecto | — | — | Documentado |
| RF-B1-009 Políticas footer | #7,#21,#53,#225 | Obj-7 | Ciudadano | — | 5 políticas descargables | — | — | Documentado |
| RF-B2-008 T&C | #70,#125,#128 | Obj-7 | Ciudadano | — | 6 componentes mínimos | — | — | Documentado |
| RF-B2-009 Política privacidad | #70,#125,#128,#152 | Obj-7 | Ciudadano | — | ARCO + responsable + reclamo | — | — | Documentado |
| RF-B2-010 Derechos autor | #70,#125,#128 | Obj-7 | Ciudadano | — | Términos de reutilización | — | — | Documentado |
| RF-B2-084/085 Componentes Kit UI | #130,#198 | Obj-2 | Ciudadano | — | Lector lee Área Servicio→Trámite | — | — | Documentado |
| RF-B3-060..076 Componentes UI (grid, acordeón, modal, toast, botones, galería, spinner, paginación, tablas) | #118 | Accesibilidad/Usabilidad | Ciudadano | — | Cada componente con ARIA/estados | — | D-01 | Documentado |
| RF-B1-070 Integración 7 pasos | #241,#209 | Obj-2 | Equipo TI / MinTIC | UC-B2-009 | MinTIC verifica y activa enmascaramiento | Metodología 17 sem (riesgo 9) | D-12 | Documentado |
| RF-B1-100 Plan de Integración | #165 | Obj-2 | G-CIO | — | PETI incluye el plan | — | — | Documentado |
| RF-B2-001 Redireccionamiento/enmascaramiento | #128,#141,#143,#172,#209 | Obj-2 | Ciudadano / MinTIC | UC-B2-009 | Redirige sin salir de gov.co | Dependencia proxy MinTIC | D-06,D-10 | Documentado |
| RF-B2-002 Integrar todos los portales | #141,#172 | Obj-2 | Alcaldía | — | Todo servicio por la sede | Des-integración no regulada | — | Pendiente |
| RF-B2-003 Portales transversales ≤6 meses | #141,#172 | Obj-2 | Alcaldía | — | Portal bajo GOV.CO | Plazo vencido (RN-B3-007) | D-12 | En conflicto |
| RF-B3-126 Registrar en SUIT | #131,#132 | Obj-2 | Tramitólogo | — | Ficha con 4 momentos | — | D-10 | Documentado |
| RF-B3-127 Estados estandarizados | #131 | Obj-5 | Ciudadano | UC-B1-014 | Usa estados que GOV.CO reconoce | — | D-09 | Documentado |
| RF-B1-099 Doble pila IPv4/IPv6 | #241,#132 | Obj-3 | Equipo TI | — | Acceso IPv6 conecta | Plazo IPv6 vencido 2020 | D-12 | En conflicto |
| **RF-01-D01** Cookies: persistir/versionar consentimiento | [DOMINIO]+[NORMATIVA] Ley 1581 | Obj-7 | Ciudadano | UC-016 A2 / HU-01-D01 | Consentimiento >12m o cambio política → re-solicita banner con versión+timestamp | Refuerza RN-B3-031 | D-04 | Documentado |
| **RF-01-D02** CRUD menú/noticias/carrusel (CMS) | [DOMINIO] | Obj-3 | Editor | UC-048 / HU-01-D02 | Reordenar refleja en todas las páginas; bloquea >7 ítems / >2 niveles | — | D-14 | Documentado |
| **RF-01-D03** Aviso salida externa por lista blanca | [DOMINIO] | Usabilidad | Administrador | UC-003 E4 / HU-01-D03 | Enlace GOV.CO/SCD no dispara modal; dominio no listado sí | — | — | Documentado |
| **RNF-01-D01** Resiliencia CDN GOV.CO | [DOMINIO] | RNF disponibilidad | Ciudadano | HU-01-D04 | CDN caído → tipografías locales de respaldo, layout intacto, reintento ≤3s | — | — | Documentado |

**RNF (01):** RNF-B1-027/B2-025/B3-040 (IPv4+IPv6) · RNF-B1-033/B2-013/B3-033 (≥3 navegadores, 0 errores) · RNF-B3-045/046 (identidad visual, 0 violaciones marca) · RNF-B3-005 (tap-target ≥44px). Fuente #99,#241,#70,#118,#120,#132. Estado Documentado.
**RN (01):** RN-B1-001/B3-006 (integrar a GOV.CO 7 pasos) · RN-B3-004 (≥1 sede electrónica) · RN-B3-005 (integra todos los portales) · RN-B2-028 (dominio GOV.CO) · RN-B2-029 (Kit UI obligatorio) · RN-B1-012/B3-025 (castellano) · RN-B1-014 (no publicidad) · RN-B1-017/B2-025 (+57) · RN-B3-007 (VU integradas, plazo vencido). Fuente #241,#209,#70,#130,#7,#141. Estado: Documentado salvo RN-B3-007 (En conflicto, plazo vencido).
**RN (01) — Delta:** RN-01-D01 (consentimiento cookies versionado ≤12m) · RN-01-D02 (menú ≤7 ítems/2 niveles invariante) · RN-01-D03 (lista blanca aviso salida). Fuente [NORMATIVA] Ley 1581 / Kit UI / [DOMINIO]. Documentado.

---

## Módulo 02 — Transparencia

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-012 Menú Transparencia 10 subsecciones | #225,#241,#221,#217 | Obj-3 | Ciudadano | — | 10 subsecciones con contenido | A-03 resuelto (ITA desde cero) | D-01 | Documentado |
| RF-B3-087 Orden cronológico + buscador | #221 | Obj-3 | Ciudadano | — | Más reciente primero | — | — | Documentado |
| RF-B3-088 Fuente única | #221 | Obj-1 | Ciudadano | — | Otros menús redirigen | — | — | Documentado |
| RF-B1-097 Info institucional | #225,#239 | Obj-3 | Ciudadano | — | Organigrama vigente | — | — | Documentado |
| RF-B1-017 Directorio SIGEP | #225,#241,#221 | Obj-3 | Ciudadano | — | Nuevo servidor en ≤1 día | — | D-10 | Documentado |
| RF-B1-096 Grupos de interés | #225 | Obj-3 | Proveedores/gremios | — | Contenido por grupo | A-07 caracterización indefinida | — | Pendiente |
| RF-B1-013 Normativa | #7,#225,#241,#221 | Obj-3 | Ciudadano | UC-B1-005 | Norma en ≤24h con todos los campos | — | D-04 | Documentado |
| RF-B1-014 Enlace SUIN/Agenda Regulatoria | #7,#225 | Obj-3 | Ciudadano | — | Enlace SUIN funcional | — | D-10 | Documentado |
| RF-B1-015 Contratación SECOP | #225,#241,#221,#239 | Obj-3 | Ciudadano | HU-B1-006 | Enlace SECOP con contratos | — | D-10 | Documentado |
| RF-B1-016 Plan de Acción 31-ene | #225,#221 | Obj-3 | Ciudadano | HU-B3-015 | Publicado el 31-ene | — | D-12 | Documentado |
| RF-B3-084 Informe gestión 31-ene | #221 | Obj-3 | Ciudadano | HU-B3-015 | Informe con fecha correcta | — | D-12 | Documentado |
| RF-B1-037 Informes trimestrales PQRSD | #225,#221 | Obj-3 | Ciudadano | — | Informe abierto antes día 15 | — | — | Documentado |
| RF-B3-152 Control interno 6 meses | Decreto 2106 Art.156,#99 | Obj-3 | Ciudadano | — | Informe en 5 días hábiles | — | D-12 | Documentado |
| RF-B1-018 Tributaria predial/ICA | #225,#239,#221 | Obj-3 | Contribuyente | HU-B1-007 | Ficha ICA completa | — | D-04 | Documentado |
| RF-B3-151 Calendario tributario | Decreto 2106 Art.39,#79 | Obj-3 | Contribuyente | HU-B3-025 | Fechas de vencimiento publicadas | — | — | Documentado |
| **RF-02-D01** CRUD + versionado documentos transparencia | [DOMINIO] | Obj-3 | Editor transparencia | UC-050 / HU-02-D01 | Reemplazo conserva versión anterior con fecha; URL fuente única intacta | — | D-04,D-14 | Documentado |
| **RF-02-D02** Alertas vencimiento publicaciones obligatorias | [NORMATIVA] Res.1519 Anexo 2 4.3/4.7/4.10 | Obj-3 | Admin cumplimiento | UC-050 E2 / HU-02-D02 | A 10 días del 31-ene sin Plan → alerta | — | D-12 | Documentado |
| **RF-02-D03** Manejo caída integraciones externas | [DOMINIO] | Obj-3 | Ciudadano | UC-050 E1 / HU-02-D03 | SECOP caído → mensaje + log; no viola RF-B2-041 | — | D-10 | Documentado |
| **RNF-02-D01** Trazabilidad sync SIGEP | [DOMINIO]+[INFERENCIA] | RNF | Admin directorio | HU-02-D04 | Registra última sync; alerta si >24h sin éxito | — | D-10 | Documentado |

**RNF (02):** RNF-B1-037/B2-017 (calidad información) · RNF-B1-038/B3-037 (lenguaje claro DNP) · RNF-B3-039 (≥90% formatos abiertos). Fuente #241,#70,#225,#251,#132,#217. Documentado.
**RN (02):** RN-B1-002 (publicación proactiva) · RN-B1-003/B3-020 (31-ene) · RN-B1-004/B3-021 (trimestral) · RN-B1-005/B3-023 (normativa ≤24h) · RN-B1-018/B3-024 (SECOP) · RN-B3-022 (SIGEP) · RN-B3-025 (ICA) · RN-B3-028 (control interno). Fuente #225,#221. Documentado.
**RN (02) — Delta:** RN-02-D01 (versión histórica inmutable + URL fuente única) · RN-02-D02 (alerta plazos legales N días antes) · RN-02-D03 (caída integración ≠ vínculo roto) · RN-02-D04 (SIGEP desactualizado >24h). Fuente [NORMATIVA] Ley 1712/Res.1519. Documentado.
**Pendiente:** LSC territorial (¿aplica a la Alcaldía?).

---

## Módulo 03 — Servicios y Trámites

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-021 Catálogo SUIT | #7,#21,#36,#241,#132,#209 | Obj-1,2 | Ciudadano | UC-B1-003, HU-B1-005 | Ficha con modalidad/costo/tiempo | — | D-10 | Documentado |
| RF-B1-022 Búsqueda/filtros | #7,#21,#241,#132 | Obj-1 | Ciudadano | UC-B1-003 | Filtra "gratuitos en línea" | — | — | Documentado |
| RF-B2-091 Servicios a la Ciudadanía | #125 | Obj-1 | Ciudadano | — | Catálogo con filtros | C-04 ubicación menú | — | En conflicto (C-04) |
| RF-B2-028 4 etapas (stepper) | #128,#130,#152,#198,#251 | Obj-5 | Ciudadano | UC-B1-004, HU-B2-001 | Línea de avance 4 etapas | — | D-02 | Documentado |
| RF-B2-029 Etapa 1 Inicio | #128,#152 | Obj-5 | Ciudadano | UC-B1-004 | Ficha completa con enlace | — | D-02 | Documentado |
| RF-B2-030 Etapa 2 Solicitud | #128,#152 | Obj-5 | Ciudadano | UC-B1-004 | Radicado único + confirmación | — | D-02,D-04 | Documentado |
| RF-B2-031 Etapa 3 Procesamiento | #128,#152,#198 | Obj-5 | Ciudadano | UC-B3-007, HU-B2-011 | Estado en tiempo real | — | D-02,D-09 | Documentado |
| RF-B2-032 Etapa 4 Respuesta | #128,#130,#152,#198 | Obj-5 | Ciudadano | UC-B2-004 | Notifica + CCD | — | D-02 | Documentado |
| RF-B3-065 Área de servicio | #118 | Usabilidad | Ciudadano | — | Califica FÁCIL/DIFÍCIL | — | D-02 | Documentado |
| RF-B2-033/034 Retroalimentación | #130,#198,#251 | Obj-6 | Ciudadano | UC-B2-004 | Componente al inicio y final | — | D-02 | Documentado |
| RF-B1-024 100% en línea | #22,#251,#183 | Obj-5 | Ciudadano | — | Nuevo trámite en línea día 1 | — | D-12 | Documentado |
| RF-B3-149 Digitalización por fases | #79 | Obj-5 | Equipo TI | — | Entregables por fase | Riesgo 9 (17 sem) | D-12 | Documentado |
| RF-B3-150 Priorización 3 bloques | #79 | Obj-5 | Equipo TI | — | Mayor demanda → bloque 1 | Tier Avanzado adoptado (§8 #1); priorización por volumen pendiente (ejercicio SUIT) | D-12 | Pendiente |
| RF-B1-092 Expediente SGDEA | #251,#132,#209 | Obj-3 | Funcionario | — | Expediente con integridad | SGDEA = Orfeo (a implementar) | D-04 | Documentado (Orfeo) |
| RF-B3-124 Consulta registros públicos | Decreto 2106 Art.19 | Obj-4 | Ciudadano | — | Estado predial sin carné | — | — | Documentado |
| RF-B1-093 Tablero BI trámites | #251 | Obj-3 | Administrador | — | Solicitudes/tasa/abandono | — | D-06 | Documentado |
| RF-B1-095 Acceso inclusivo | #251 | Obj-1 | Adulto mayor/discapacidad | HU-B1-013 | Computadores + apoyo en sede | — | — | Documentado |
| RF-B1-029 Pagos PSE/tarjeta | #22,#251,#128,#141 | Obj-5 | Ciudadano | UC-B3-006, HU-B1-022 | Paga sin recargo | — | D-02 | Documentado |
| RF-B3-123 No cobrar por automatización | Decreto 2106 Art.7 | Obj-5 | Ciudadano | — | Tarifa digital ≤ presencial | — | — | Documentado |
| RF-B1-027 Carpeta Ciudadana | #22,#156,#251,#119 | Obj-1,4 | Ciudadano | UC-B2-004, HU-B1-025 | Documento en CCD en ≤24h | — | D-02,D-10 | Documentado |
| RF-B1-025/026 Autenticación SCD | #156,#165,#251,#124 | Obj-7 | Ciudadano | UC-B2-005 | Redirección con nivel correcto | A-09 equivalencia niveles | D-07 | Documentado |
| RF-B2-098 Actualizar SUIT con SCD | #143,#152 | Obj-2 | Tramitólogo | — | GOV.CO actualiza ≤24h | — | D-10 | Documentado |
| **RF-03-D01** Idempotencia pago y radicado | [DOMINIO] | Obj-5 | Ciudadano | UC-007 E3' / HU-03-D01 | Reintento con misma clave → comprobante existente, sin doble cargo/radicado | — | D-02,D-15 | Documentado |
| **RF-03-D02** Conciliación y estados del pago | [DOMINIO]+[WEB] | Obj-5 | Ciudadano | UC-007 E5 / HU-03-D02 | "Pendiente"→en espera; reconsulta; solo "aprobado" avanza | — | D-02,D-15 | Documentado |
| **RF-03-D03** Guardar borrador / reanudar trámite | [DOMINIO] | Obj-5 | Ciudadano | UC-045 / HU-03-D04 | Retoma desde paso guardado con datos y adjuntos | RNF-03-D02 (30 días, §8 #10) | D-02 | Documentado |
| **RF-03-D04** Validación adjuntos en trámites | [DOMINIO]+[NORMATIVA] | Obj-7 | Ciudadano | UC-042 E2 / HU-03-D05 | MIME real + tamaño + antivirus; PHP renombrado rechazado | **C-06** (frontera vs PQRSD) | D-02 | En conflicto (C-06) |
| **RF-03-D05** Subsanación/requerimiento documentos | [DOMINIO]+[WEB] | Obj-5 | Funcionario/Ciudadano | UC-042 / HU-03-D06, HU-03-D07 | "Requiere subsanación"→carga→continúa con plazo recalculado | — | D-02,D-09 | Documentado |
| **RF-03-D06** Fallback verificación SCD/X-Road | [NORMATIVA] Dec.2106 Art.10 | Obj-4 | Ciudadano | UC-004 E2' / HU-03-D10 | ANI no responde → carga manual con "falla técnica documentada" | — | D-02,D-08 | Documentado |
| **RF-03-D07** Desistimiento de trámite | [NORMATIVA] Ley 1755 Art.18; [WEB] | Obj-5 | Ciudadano | UC-043 / HU-03-D08 | "Desistir"→estado "Desistido" en expediente | Reembolso si pagó (PA) | D-02,D-09 | Documentado |
| **RNF-03-D01** Paginación/capacidad catálogo | [DOMINIO] | RNF rendimiento | Ciudadano | HU-03-D12 | Búsqueda ≤2s; ≥10 por página | — | — | Documentado |
| **RNF-03-D02** Retención de borradores | [DOMINIO] | RNF | Ciudadano | HU-03-D04 | 30 días; vencido se purga/anonimiza | Resuelto §8 #10 (2026-06-05) | — | Documentado |

**RNF (03):** RNF-B1-001/B2-004/B3-006 (disponibilidad ≥98%/≥95%) **C-02** · RNF-B3-016/017 (≥5.000 concurrentes / 100.000 trans.) · RNF-B1-008/B3-010 (carga ≤3s, internas ≤1.5s). Fuente #21,#241,#251,#217,#131,#170. Estado: RNF-B1-001 **En conflicto (C-02)**; resto Documentado.
**RN (03):** RN-B1-006 (SUIT ≤3 días) · RN-B1-007/B2-020/B3-038 (100% en línea, sin cobro) · RN-B1-008/B3-033 (digitalización por bloques) · RN-B1-023 (pasos susceptibles) · RN-B2-001/B3-010 (no incrementar tarifas) · RN-B2-002/B3-027 (consultas no son trámites) · RN-B2-003 (concepto DAFP) · RN-B3-011/037 (no exigir certificados físicos). Fuente #22,#251,#79,#141,#172,#183. Documentado.
**RN (03) — Delta:** RN-03-D01 (idempotencia pago/radicado) · RN-03-D02 (avance solo con pago aprobado) · RN-03-D03 (sin recargo pasarela) · RN-03-D04 (validación adjuntos trámite, **C-06**) · RN-03-D05 (subsanación recalcula plazo / desistimiento por no subsanar) · RN-03-D06 (excepción falla técnica documentada) · RN-03-D07 (desistimiento expreso) · RN-03-D08 (silencio administrativo positivo). Fuente [NORMATIVA] Ley 1755/Dec.2106 + [DOMINIO]. Estado: RN-03-D04 En conflicto (C-06); RN-03-D08 Pendiente (catálogo SAP — Secretaría Jurídica); resto Documentado.

---

## Módulo 04 — PQRSD

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-031 Formulario PQRSD | #7,#21,#225,#239,#241 | Obj-1 | Ciudadano | UC-B1-001, HU-B1-002 | Radicado + acuse + registro | C-04 ubicación | D-03,D-04 | En conflicto (C-04) |
| RF-B1-032 Solicitud anónima | #239,#241,#125,#209 | Obj-1 | Ciudadano anónimo | UC-B3-003, HU-B1-012 | Deshabilita identidad + aviso | — | D-03 | Documentado |
| RF-B1-033 Sin restricción adjuntos | #241,#209 | Obj-1 | Ciudadano | — | Acepta archivo grande no estándar | **C-01 ALTA** (impl. 10MB) | D-03 | En conflicto (C-01) |
| RF-B1-034 Seguimiento por radicado | #7,#21,#225,#241 | Obj-1 | Ciudadano | UC-B1-002, HU-B1-004 | Estado actualizado | — | D-03,D-09 | Documentado |
| RF-B3-099 Acuse automático ≤24h | #221 | Obj-1 | Ciudadano | HU-B3-008 | Acuse inmediato, radicado ≤24h | — | D-03 | Documentado |
| RF-B1-035 Validación accesible | #19,#53,#241,#243,#132 | Accesibilidad | Ciudadano | HU-B1-023 | Mensaje + foco al campo | — | D-03 | Documentado |
| RF-B3-101 Anti-spam captcha | #221 | Obj-7 | Ciudadano | — | Bloquea bots | Captcha vs A11y | D-03 | En conflicto |
| RF-B3-102 Mensaje de falla | #221 | Usabilidad | Ciudadano | — | "Error… intente nuevamente" | — | — | Documentado |
| RF-B1-036 Integración SGDEA | #225,#241,#251,#221 | Obj-1 | Funcionario | — | Radicado+expediente <5s, móvil | SGDEA = Orfeo (a implementar) | D-03,D-04 | Documentado (Orfeo) |
| RF-B2-070 ARCO plazos | #122 | Obj-7 | Ciudadano | — | Contador + alerta 3 días | — | — | Documentado |
| **RF-04-D01** Enrutamiento/reasignación/traslado por competencia | [WEB]+[NORMATIVA] Ley 1755 Art.21 | Obj-1 | Funcionario dependencia | UC-035 A3 / UC-037 / HU-04-D01 | No competente → traslada, notifica entidad receptora y fecha | Resuelve A-11 | D-03,D-09 | Documentado |
| **RF-04-D02** Gestión respuesta y cierre PQRSD | [DOMINIO] | Obj-1 | Funcionario dependencia | UC-036 A2 / HU-04-D02 | Respuesta→"Respondido"+marca dentro/fuera de término | — | D-03,D-09 | Documentado |
| **RF-04-D03** Cómputo plazos diferenciados por tipo | [NORMATIVA] Ley 1755 Art.14 | Obj-1 | Responsable PQRSD | UC-035 E3 / UC-046 / HU-04-D03, HU-04-D04 | Reclamo 15 vs consulta 30 días hábiles, excluye festivos | — | D-03 | Documentado |
| **RF-04-D04** Validación campos formulario PQRSD | [DOMINIO] | Obj-1 | Ciudadano | HU-04-D06 (enriquece HU-B1-023) | NIT con DV inválido→error inline; objeto >2000→bloquea | — | D-03 | Documentado |
| **RF-04-D05** Notificación electrónica CPACA | [WEB]+[NORMATIVA] Ley 1437 Art.56 | Obj-1 | Funcionario | UC-036 E3 / HU-04-D05 | Notifica canal autorizado con constancia y fecha | — | D-03 | Documentado |
| **RNF-04-D01** Formato del radicado | [DOMINIO] | RNF | Gestión Documental | HU-03-D13 | prefijo dependencia+año+consecutivo atómico (ej. `SM-CAT-2026-000123`) | Resuelto §8 #11 (2026-06-05) | D-04 | Documentado |
| **RNF-04-D02** Concurrencia de radicación | [DOMINIO] | RNF | Ciudadano | UC-001 E9 / HU-03-D13 | Consecutivo atómico, sin duplicados bajo concurrencia | — | D-04 | Documentado |

**RNF (04):** RNF-B2-006 (ARCO ≥99%) · RNF-B1-008 (radicado <5s) · RNF-B3-003 (responsive). Fuente #122,#225,#241,#60,#70. Documentado.
**RN (04):** RN-B1-009 (anónimas / protección denunciante) · RN-B1-010 (sin restricciones técnicas) **C-01** · RN-B1-011 (consultas gratuitas) · RN-B3-026 (acuse ≤24h) · RN-B2-019 (formularios normalizados). Fuente #241,#225,#22,#221,#143. Estado: RN-B1-010 **En conflicto (C-01)**; resto Documentado.
**RN (04) — Delta:** RN-04-D01 (plazos diferenciados por tipo) · RN-04-D02 (prórroga antes del vencimiento) · RN-04-D03 (traslado por competencia ≤5 días) · RN-04-D04 (cierre con medición de cumplimiento) · RN-04-D05 (notificación electrónica CPACA) · RN-04-D06 (identidad reservada denunciante) · RN-04-D07 (unicidad/atomicidad del radicado) · RN-04-D08 (gratuidad con cobro tasado de copias). Fuente [NORMATIVA] Ley 1755/1437/1712 + [DOMINIO]. Documentado. **A-11 resuelto** por RF-04-D01/RN-04-D03.
**Pendiente:** A-11 enrutamiento PQRSD ¿automático o manual?; proceso interno de gestión.

---

## Módulo 05 — Participa

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-038 Sección Participa 4 fases | #21,#239,#241,#132,#209 | Obj-1 | Ciudadano | HU-B3-027 | 4 fases con mecanismos activos | Mecanismos del Distrito sin definir | D-01 | Pendiente |
| RF-B1-039 Consulta normas SUCOP | #7,#225 | Obj-1 | Ciudadano | UC-B1-009, HU-B1-016 | Radica comentarios vía SUCOP | — | D-10 | Documentado |
| RF-B1-040 Micrositios grupos interés | #239 | Obj-1 | Grupos de interés | HU-B1-040 | Accesible, lenguaje claro | A-07 caracterización | — | Pendiente |
| **RF-05-D01** CRUD/ciclo de vida mecanismos de participación | [DOMINIO]+[NORMATIVA] | Obj-1 | Admin participación | UC-018 / HU-05-D01 | Consulta vencida → aportes deshabilitados, estado "Cerrada" | — | D-09,D-14 | Documentado |
| **RF-05-D02** Publicación resultado/cierre participación | [NORMATIVA] Dec.1081 Art.2.1.2.1.14 | Obj-1 | Ciudadano | HU-05-D02 | Publica consolidado de observaciones y respuesta | Aportes sede vs SUCOP (PA) | — | Documentado |

**RNF (05):** transversales — WCAG 2.1 AA (ver 07); RNF-B1-038 lenguaje claro. Fuente #17,#239,#225. Documentado.
**RN (05):** RN-B1-025(f2) (lineamientos DAFP) · RN-B1-039 (consulta pública SUCOP). Fuente #241,#7,#225. Documentado.
**RN (05) — Delta:** RN-05-D01 (cierre automático por vencimiento) · RN-05-D02 (publicación del resultado del proceso). Fuente [NORMATIVA] Ley 1712/1757/Dec.1081. Documentado.
**Resuelto (2026-06-05):** existe la Oficina de **Atención al Ciudadano** / **Atención al Ciudadano y Participación Social** (organigrama `/dependencias`); asume la función del Art.17 Ley 2052.

---

## Módulo 06 — Canales de Atención

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-041 Sección Canales | #7,#225,#241 | Obj-1 | Ciudadano | — | Dirección/horario/contactos +57 | — | — | Documentado |
| RF-B1-030 Agendamiento citas | #225,#241,#132 | Obj-1 | Ciudadano | UC-B1-007, HU-B1-019 | Confirmación con código | — | D-04 | Documentado |
| RF-B1-095 Acceso inclusivo | #251 | Obj-1 | Adulto mayor | HU-B1-013/024 | Computadores + apoyo | — | — | Documentado |
| **RF-06-D01** Administración de agenda de citas (CMS) | [DOMINIO] | Obj-1 | Admin atención/agenda | UC-047 / HU-06-D01 | Define servicios, franjas, cupos, bloqueos; alimenta UC-011 | Agenda propia vs turnos (PA) | D-13,D-14 | Documentado |
| **RF-06-D02** Reprogramación de cita | [DOMINIO] | Obj-1 | Ciudadano | UC-032 A1 / HU-06-D02 | Reprogramar libera cupo anterior y reconfirma | — | D-13 | Documentado |
| **RF-06-D03** Control concurrencia/doble reserva | [DOMINIO] | Obj-1 | Ciudadano | UC-032 E3' / UC-011 E1' / HU-06-D03 | Último cupo a una sola reserva; concurrente "no disponible" | — | D-13 | Documentado |
| **RF-06-D04** Recordatorio y registro no-show | [DOMINIO] | Obj-1 | Admin atención | UC-032 E4 / HU-06-D04 | Recordatorio 24h; cita no atendida → "no-show", libera cupo | — | D-13 | Documentado |

**RNF (06):** RNF-B1-037 (datos actualizados) · WCAG (transversal). Fuente #241,#17. Documentado.
**RN (06):** RN-B1-017 (+57) · RN-B2-023 (canal digital opcional, mantener presencial). Fuente #7,#225,#258. Documentado.
**RN (06) — Delta:** RN-06-D01 (reserva atómica de cita) · RN-06-D02 (liberación de cupo en cancelación/reprogramación) · RN-06-D03 (alternativa no digital obligatoria, invariante). Fuente [NORMATIVA] Ley 1753 + [DOMINIO]. Documentado.
**Pendiente:** ¿cuántas sedes/horarios? ¿chat en vivo?

---

## Módulo 07 — Accesibilidad (transversal)

| RF (agrupado) | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-044 Barra accesibilidad / RF-B3-022 Saltar contenido | #239,#241,#70,#118,#76 | Obj-1 | Discapacidad visual/motriz | UC-B3-009, HU-B1-015 | Modo persistente; primer Tab "Saltar" | Barra A11y en tablet sin definir | — | Documentado (tablet Pendiente) |
| RF-B1-045..054 Perceptible (alt, subtítulos, LSC, audio, color, UTF-8) | #17,#70,#76,#200,#84 | Obj-1 | Discapacidad visual/auditiva | UC-B3-015, HU-B1-018 | CC1-CC3/CC5/CC18/CC31 | LSC territorial; WCAG 2.1 vs 2.2 | — | Documentado (LSC Pendiente) |
| RF-B1-047/052 + RF-B3-004..047 Estructura/idioma/adaptable (semántica, lang, 200%/400%) | #17,#76,#84,#200 | Obj-1 | Discapacidad cognitiva/baja visión | UC-B3-010, HU-B3-002 | CC4/CC8/CC9/CC27/CC29 | A-08 contraste "4:5:1" (error) | — | Documentado |
| RF-B1-048/051 + RF-B3-016..034 Operable teclado/tiempo (foco, sin trampas, destellos) | #17,#70,#76,#243 | Obj-1 | Discapacidad motriz/fotosensible | UC-B3-009, HU-B1-009 | CC6/CC16/CC17/CC19/CC20 | — | — | Documentado |
| RF-B1-049/053 + RF-B3-006..040 Comprensible (labels, errores, enlaces, multi-vía) | #17,#170,#76,#118,#200 | Obj-1 | Discapacidad cognitiva | — | CC12/CC24/CC25/CC26/CC28 | — | — | Documentado |
| RF-B3-041..052 Robusto (HTML válido, ARIA, aria-live, sitemap) | #76,#200 | Obj-1 | Tecnologías de asistencia | UC-B2-011 | CC11/CC30/CC32 | — | — | Documentado |
| **RF-07-D01** Bloqueo publicación multimedia inaccesible | [NORMATIVA] RN-B3-002/003; Res.1519 Anexo 1 1.5 | Obj-1 | Editor | UC-019 / UC-048 / HU-07-D01 | Video sin .SRT (y LSC si aplica) → CMS impide publicar | Convierte UC-B3-015 en bloqueante | D-14 | Documentado |
| **RNF-07-D01** Barra accesibilidad en tablet (768-992px) | [INFERENCIA]+[DOMINIO] | RNF A11y | Discapacidad | HU-07-D02 | Barra disponible y operable en tablet | Resuelve ambigüedad §9 | — | Documentado |

**RNF (07):** RNF-B1-014/B2-001/B3-001 (WCAG 2.1 AA, 0 críticas) · RNF-B1-015 (NTC 5854) · RNF-B1-016/B3-002 (NVDA/JAWS/VoiceOver, ≥90% tareas) · RNF-B1-017/B2-002/B3-003 (contraste ≥4.5:1) · RNF-B1-018 (≤3 destellos/seg) · RNF-B3-004 (zoom 400%) · RNF-B3-005 (≥44px). Fuente #17,#30,#76,#200,#118. Estado Documentado; **A-WCAG En conflicto** (sin fiscalización/umbral).
**RN (07):** RN-B1-021/B3-001 (WCAG AA obligatorio 2022, vencido) · RN-B3-002 (subtítulos) · RN-B3-003 (LSC 4 tipos) · RN-B2-026 (subrayado solo enlaces). Fuente #17,#76,#200,#60. Estado: RN-B1-021 **En conflicto** (plazo vencido + sin sanción).
**RN (07) — Delta:** RN-07-D01 (bloqueo de multimedia inaccesible, regla bloqueante) · RN-07-D02 (captcha accesible obligatorio — **resuelve Captcha vs A11y**). Fuente [NORMATIVA] Res.1519/Ley 1618/WCAG 2.1. Documentado.

---

## Módulo 08 — Usabilidad (transversal)

| RF (agrupado) | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-080/081/082 + RF-B2-071..074 Navegación/URLs/inicio orientado a tareas | #170,#60,#243 | Obj-6 | Ciudadano | HU-B1-017 | Breadcrumb; URLs semánticas; 3 trámites ≤2 clics | A-02 "debe/recomienda" sin jerarquía | — | Documentado (A-02 ambiguo) |
| RF-B1-083 Páginas de confirmación | #170,#60,#243 | Obj-6 | Ciudadano | — | Nº referencia + próximos pasos | — | D-02 | Documentado |
| RF-B1-084 + RF-B3-077..080 Formularios usables (Kit UI) | #170,#241,#60,#118 | Obj-6 | Ciudadano | — | Validación dinámica; estados de campo | — | — | Documentado |
| RF-B1-085 Responsive | #7,#170,#241,#118 | Obj-6 | Ciudadano | HU-B1-010 | Sin scroll horizontal a 375px | — | — | Documentado |
| RF-B1-089 Código W3C válido | #170,#241,#60,#201 | Obj-3 | Equipo dev | UC-B2-017 | Validador W3C sin errores | — | — | Documentado |
| RF-B1-088 SEO | #170,#60,#153 | Obj-1 | Ciudadano | HU-B1-001 | Top 5 en Google | — | — | Documentado |
| RF-B1-087 Encuesta SUS | #21,#32,#190 | Obj-6 | Equipo UX | UC-B1-013, HU-B2-019 | Calcula y almacena puntaje | A-14 sin SUS mínimo "cumple" | — | Documentado (A-14 Pendiente) |
| RF-B3-146/147 Lenguaje claro + pruebas usuario | #76,#243 | Obj-6 | Ciudadano | — | Informe de hallazgos por versión | Design Thinking omitible (riesgo) | — | Documentado |
| **RF-08-D01** Criterio cuantitativo de "cumple" usabilidad | [PREGUNTA ABIERTA]+[DOMINIO] | Obj-6 | Equipo UX | HU-08-D01 | SUS ≥68 + tasa de éxito ≥ umbral | Resuelve A-14; umbral PA (UX) | — | Pendiente |
| **RNF-08-D01** Persistencia/exportación resultados SUS | [DOMINIO] | RNF | Equipo UX | HU-08-D02 | Resultados almacenados y exportables CSV con histórico | — | — | Documentado |

**RNF (08):** RNF-B1-030 (SUS ≥68) · RNF-B1-031/B2-018 (60-80 car/línea) · RNF-B1-032 (1024×768 sin scroll, 320px) · RNF-B2-019 (buscador ~27 car) · RNF-B2-007/B3-010 (FCP ≤2.5s 4G) · RNF-B2-021/B3-038 (SEO top10, ≥95% URLs semánticas) · RNF-B1-043/B3-042 (Clean Code, cobertura ≥70%, CI/CD) · RNF-B3-036 (≤7 menú) · RNF-B3-037 (Fernández-Huerta ≥60). Fuente #32,#170,#60,#243,#131,#201,#132,#118. Documentado.
**RN (08):** RN-B2-026 (subrayado enlaces) · RN-B1-038/B3-037 (lenguaje claro DNP) · RN-B2-029(f2) (W3C). Fuente #60,#225,#243,#170. Documentado.
**RN (08) — Delta:** RN-08-D01 (criterio cuantitativo de "cumple" — resuelve A-14, umbral **[PREGUNTA ABIERTA]** Equipo UX). Fuente [DOMINIO]+Guía Usabilidad MinTIC. Pendiente.

---

## Módulo 09 — Seguridad Digital

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-055 HTTPS obligatorio | #19,#53,#241,#70,#201 | Obj-7 | Ciudadano | HU-B2-010 | http→https con cert válido | — | D-06 | Documentado |
| RF-B1-056 Cabeceras seguridad | #19,#53,#70,#201 | Obj-7 | Equipo seguridad | — | securityheaders.com OK | **HPKP obsoleto** | D-06 | En conflicto (HPKP) |
| RF-B1-057 Cookies seguras | #19,#53,#156,#124 | Obj-7 | Ciudadano | — | Secure+HttpOnly | — | — | Documentado |
| RF-B1-059 Métodos HTTP / hardening | #19,#53,#241,#70 | Obj-7 | Equipo seguridad | — | TRACE→405 | — | — | Documentado |
| RF-B1-060 Sanitización | #19,#53,#241,#70 | Obj-7 | Equipo seguridad | — | `<script>` sanitizado | — | — | Documentado |
| RF-B1-061 Errores genéricos | #19,#53,#70 | Obj-7 | Equipo seguridad | — | Sin stack/traza | — | — | Documentado |
| RF-B1-058 Captcha accesible | #19,#53,#241,#128,#132 | Obj-7 | Discapacidad visual | — | Alternativa de audio | **Captcha vs A11y** | D-03 | En conflicto |
| RF-B1-064 Token CSRF | #19 | Obj-7 | Equipo seguridad | — | CSRF forjado rechazado | — | — | Documentado |
| RF-B1-062 Control de acceso | #19,#53,#241 | Obj-7 | Administrador | UC-B1-010, HU-B1-020 | 5 fallos→bloqueo 15min | — | — | Documentado |
| RF-B1-063 Permisos / MIME | #19,#53 | Obj-7 | Equipo seguridad | — | PHP disfrazado rechazado | — | — | Documentado |
| RF-B3-074 Módulo login Kit UI | #118 | Obj-7 | Ciudadano | UC-B3-004 | CC/CE/TI/PEP/NIT | — | — | Documentado |
| RF-B1-025/026 Autenticación SCD OIDC | #156,#165,#251,#124 | Obj-7 | Ciudadano | UC-B2-005, HU-B3-021 | Niveles B/M/A/MA, SSO/SLO | A-09 equivalencia niveles | D-07 | Documentado |
| RF-B3-106 Registro validado ANI | #131 | Obj-4 | Ciudadano | UC-B3-004 | Valida cédula vía ANI | — | D-07,D-10 | Documentado |
| RF-B1-065 Logs auditoría | #19,#156,#128,#131 | Obj-3 | Equipo seguridad | HU-B2-014 | Log con HLC, retención ≥5 años | SIEM no nombrado | D-06 | Documentado |
| RF-B1-066 Gestión incidentes CSIRT | #19,#251,#201 | Obj-7 | Equipo seguridad/CSIRT | UC-B1-012 | Reporte CSIRT ≤24h | A-06 umbral "grave" | D-10 | En conflicto (A-06) |
| RF-B1-067 Backups DRP/BCP | #19,#241,#70,#224 | Obj-7 | Equipo TI | — | Verificación integridad en log | ¿DRP/BCP probado? | — | Pendiente |
| RF-B1-069 MSPI monitoreo/pentest | #19,#165,#70,#201 | Obj-7 | Equipo seguridad | — | Mitigación DDoS + alerta | — | — | Documentado |
| RF-B1-068 Git CI/CD parches ≤72h | #19,#70,#201 | Obj-3 | Equipo dev | — | Pipeline ejecuta pruebas | — | — | Documentado |
| RF-B2-047 Módulo ARCO | #122 | Obj-7 | Ciudadano | UC-B2-007 | Acuse + plazo | — | — | Documentado |
| RF-B2-048/049 Consentimiento | #122,#128 | Obj-7 | Ciudadano | — | Casilla no premarcada; sensibles separados | — | — | Documentado |
| RF-B2-050/051/052 Rectificar/log consentimiento | #122 | Obj-7 | Ciudadano | HU-B2-014 | Log con timestamp/IP/versión | — | D-04 | Documentado |
| RF-B2-090 Notificación cambios política | #122 | Obj-7 | Ciudadano | — | Reautorización si amplía | — | — | Documentado |
| **RF-09-D01** Autorización por recurso (anti-IDOR) | [DOMINIO] OWASP A01 | Obj-7 | Ciudadano/Oficial seguridad | UC-002/004/009 E(IDOR) / HU-09-D01 | A abre radicado de B por URL → 403 + log | — | D-15 | Documentado |
| **RF-09-D02** Política contraseñas + recuperación segura | [DOMINIO] | Obj-7 | Usuario interno | UC-031 E1' / HU-09-D02 | Token un solo uso 15 min; restablecer invalida sesiones | — | — | Documentado |
| **RF-09-D03** Segregación de funciones (SoD) | [DOMINIO]+[INFERENCIA] | Obj-7 | Editor/Aprobador | UC-048 E1 / HU-09-D03 | Quien crea no aprueba; auto-aprobado bloqueado | — | D-14 | Documentado |
| **RF-09-D04** Caída del SCD de Autenticación (OIDC) | [DOMINIO] | Obj-7 | Ciudadano | UC-005 E7 / HU-09-D04 | Articulador caído → contenido público sigue; state inválido → rechazo+log | — | D-07 | Documentado |
| **RNF-09-D01** Integridad/inmutabilidad de logs | [DOMINIO]+[INFERENCIA] | RNF seguridad | Auditor | HU-09-D06 | Log append-only/encadenado por hash, inalterable 5 años | — | D-04,D-06 | Documentado |
| **RNF-09-D02** Rate limiting endpoints públicos | [DOMINIO] OWASP | RNF seguridad | Oficial seguridad | HU-09-D07 | Throttling/bloqueo por IP en login/PQRSD/búsqueda | — | — | Documentado |
| **RNF-09-D03** Criterio de incidente "grave" | [PREGUNTA ABIERTA] | RNF | Oficial seguridad | — | Umbral local para reporte CSIRT ≤24h | Resuelve A-06; PA | D-10 | Pendiente |

**RNF (09):** RNF-B1-020/B3-021 (RSA≥2048, AES≥256) · RNF-B2-008 (TLS 1.2) · RNF-B2-009 (AES-256 reposo) · RNF-B1-021/022/023 (contraseñas/cert/timeout) · RNF-B1-024/B3-022 (logs ≥5 años HLC) · RNF-B1-025/B3-025 (incidentes ≤24h) · RNF-B1-026 (parches ≤72h) · RNF-B2-010/B3-026 (RBAC) · RNF-B2-011 (revocar ≤1 día) · RNF-B2-012/B3-024 (MSPI ≥95%) · RNF-B3-019 (SSL A/A+) · RNF-B3-020 (OWASP Top10) · RNF-B1-040/B2-022 (PbD/PIA) · RNF-B2-027 (transferencias SIC). Fuente #156,#224,#122,#19,#201,#132. Estado: Documentado; **RNF-B2-027 Pendiente** (nube extranjera por confirmar).
**RN (09):** RN-B1-013/B3-030 (autorización previa) · RN-B1-016 (CSIRT ≤24h) · RN-B2-006 (Responsable/Encargado) · RN-B2-007 (menores) · RN-B2-008/009 (minimización) · RN-B2-010 (RNBD) · RN-B2-022/030 (notificar SIC) · RN-B3-031 (cookies). Fuente #156,#225,#122,#19,#201,#132. Estado Documentado; **RN-B2-010 (RNBD)** riesgo 13 (no inscribir → investigación).
**RN (09) — Delta:** RN-09-D01 (autorización por recurso anti-IDOR) · RN-09-D02 (SoD) · RN-09-D03 (recuperación de credenciales segura) · RN-09-D04 (**MFA admins CMS — resuelve C-07**) · RN-09-D05 (inmutabilidad del log) · RN-09-D06 (continuidad ante caída SCD) · RN-09-D07 (umbral incidente grave — **[PREGUNTA ABIERTA]** A-06). Fuente [DOMINIO]/MSPI/[NORMATIVA]. Estado: RN-09-D04 En conflicto (C-07); RN-09-D07 Pendiente; resto Documentado.

---

## Módulo 10 — Interoperabilidad

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-028 Servidor seguridad X-Road 3 ambientes | #156,#165,#251,#124,#126,#119,#203 | Obj-4 | Equipo TI / AND | UC-B2-010, HU-B3-019 | Mensaje cifrado + estampa | **SO obsoleto** (Ubuntu 18.04) | D-08,D-10 | En conflicto (SO) |
| RF-B2-017 Subsistemas/servicios | #124,#126,#119,#203 | Obj-4 | Equipo TI | — | Servicios + permisos por cliente | — | D-08 | Documentado |
| RF-B2-018 HTTPS+RSA-SHA512+TSA | #124,#126,#140 | Obj-4 | Equipo TI | — | RSA-SHA512 + estampa GSE | — | D-08 | Documentado |
| RF-B2-088 TSA GSE (reemplaza Certicámara) | #140 | Obj-4 | Equipo TI | HU-B3-020 | Diagnostics TSA GSE activo | **Riesgo cambio TSA** (riesgo 4) | D-08 | En conflicto |
| RF-B2-019 Certificación nivel 3 LCI | #124,#126 | Obj-4 | Equipo TI / AND | — | Demuestra LCI nivel 3 | — | D-08 | Documentado |
| RF-B2-020 4 servicios REST CCD | #124,#143,#147,#119 | Obj-1,4 | Ciudadano | UC-B2-015, HU-B2-004 | JSON por tipoId+idUsuario | — | D-10 | Documentado |
| RF-B2-021 Acceso CCD vía GOV.CO | #124 | Obj-1 | Ciudadano | — | Datos en tiempo real, no almacena | — | D-04,D-10 | Documentado |
| RF-B2-022 Clasificar info Ley 1712 | #124 | Obj-3 | Funcionario | — | Solo expone no reservado | — | — | Documentado |
| RF-B3-117 Comunicaciones CCD | #119,#224 | Obj-1 | Ciudadano | — | Alerta si autorizó canal | — | — | Documentado |
| RF-B2-094 No exigir documentos | #141,#172 | Obj-4 | Ciudadano | UC-B2-002, HU-B2-015 | Consulta automática sin copia | — | D-08 | Documentado |
| RF-B3-113 Consulta gratuita ANI | Decreto 2106 Art.13 | Obj-4 | Ciudadano | — | Valida identidad sin costo | — | D-10 | Documentado |
| RF-B3-114 Actualizar SUIT interop | Decreto 2106 Art.10 | Obj-4 | Tramitólogo | — | Ficha refleja "no aplica" | — | D-10 | Documentado |
| RF-B3-115 Consulta registros no integrados | Decreto 2106 Art.10 | Obj-4 | Otra entidad | — | Consulta en línea | — | — | Documentado |
| RF-B2-095 Interconexión dependencias | #143 | Obj-4 | Funcionario | — | 2ª dependencia sin re-radicación | — | D-06 | Documentado |
| RF-B1-072 SUIT | #22,#165,#131 | Obj-2 | Tramitólogo | — | URL T{código} ≤3 días | — | D-10 | Documentado |
| RF-B1-073 SIGEP | #165,#225,#203 | Obj-3 | Funcionario | — | memberCode = sigla-SIGEP | — | D-10 | Documentado |
| RF-B1-075 SGDEA | #251,#241 | Obj-3 | Funcionario | — | Expedientes auténticos | SGDEA = Orfeo (a implementar) | D-04 | Documentado (Orfeo) |
| **RF-10-D01** Manejo de error y reintento X-Road | [DOMINIO] | Obj-4 | Equipo técnico | UC-029 E5 / HU-10-D01 | Timeout/OCSP vencido → reintento + log + fallback (RF-03-D06) | — | D-08 | Documentado |
| **RF-10-D02** Continuidad del estampado TSA en migración | [DOMINIO] | Obj-4 | Equipo técnico | UC-029 E4 / HU-10-D02 | Migración Certicámara→GSE → cola de sello, ninguno sin RFC 3161 | Riesgo TSA | D-08 | Documentado |
| **RNF-10-D01** Monitoreo de certificados | [DOMINIO] | RNF | Equipo técnico | HU-10-D03 | Alerta N días antes de vencer ONAC/TLS/OCSP | — | — | Documentado |

**RNF (10):** RNF-B1-002/B3-007 (SCD ≥99.98%) **C-03** · RNF-B1-004 (RTO ≤8min) · RNF-B1-005 (RPO ≤30min) · RNF-B1-009/010/011 (auth<1s, SCD<5s, latencia<3s) · RNF-B1-034/B2-015 (LCI nivel 3, 4 dominios) · RNF-B1-035 (SOA/REST/JWT) · RNF-B2-014 (X-Road HA K2/K3) · RNF-B3-030/031 (100% TSA+firma) · RNF-B3-032 (≥80% LCI). Fuente #156,#224,#126,#119,#140,#165,#124,#132,#217. Estado: **RNF-B1-002 En conflicto (C-03)**; resto Documentado.
**RN (10):** RN-B1-020/B3-013 (SCD obligatorios/gratuitos) · RN-B2-004/B3-008 (no exigir docs) · RN-B2-005 (actualizar SUIT) · RN-B2-016/B3-014 (AND exclusivo) · RN-B2-018 (LCI nivel 3) · RN-B2-021 (Docker solo dev) · RN-B3-032 (memberCode) · RN-B2-025 (vinculación CCD) · RN-B3-011 (Registraduría gratis). Fuente #156,#165,#141,#143,#124,#126,#203,#147. Estado Documentado; **memberClass C-x En conflicto** ("CO" vs "GOB/PRIV").
**RN (10) — Delta:** RN-10-D01 (no dejar trámite "colgado" ante error X-Road) · RN-10-D02 (continuidad estampado TSA) · RN-10-D03 (monitoreo y renovación anticipada de certificados). Fuente Marco de Interoperabilidad/RFC 3161/Dec.2106. Documentado.

---

## Módulo 11 — Datos Abiertos

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-019 Sección datos abiertos | #3,#8,#225,#241,#242 | Obj-3 | Ciudadano/Investigador | UC-B1-008, HU-B1-008 | Descarga CSV/JSON/XML | A-04 datos.gov vs archivo | D-10 | Documentado (A-04 aclarado) |
| RF-B1-020 Registro activos + licencia | #8,#242 | Obj-3 | Administrador | UC-B2-013 | Inventario con criticidad | ¿Activos cargados? | — | Pendiente |
| RF-B1-090 Formatos abiertos | #3,#8,#242 | Obj-3 | Ciudadano | HU-B2-018 | Procesable sin propietario | — | — | Documentado |
| RF-B1-091 Metadatos completos | #8,#242 | Obj-3 | Ciudadano | — | Todos los metadatos visibles | — | D-04 | Documentado |
| **RF-11-D01** CRUD + actualización programada de datasets | [DOMINIO] | Obj-3 | Admin portal | UC-049 / HU-11-D01 | Versiona; frecuencia "mensual" sin actualizar 35d → alerta | — | D-04,D-14 | Documentado |
| **RNF-11-D01** Validación de calidad del dato | [DOMINIO] | RNF | Admin portal | UC-049 E2 / HU-11-D02 | Valida CSV/JSON/XML bien formado + metadatos antes de federar | — | D-10 | Documentado |

**RNF (11):** RNF-B1-036/B2-003 (CSV/XML/RDF/RSS/JSON/ODF/WMS/WFS) · RNF-B3-039 (≥90% procesable). Fuente #8,#242,#132,#217. Documentado.
**RN (11):** RN-B1-019 (datos.gov.co NO es archivo) · RN-B1-026(f2) (licencia abierta) · RN(Ley 1753) (formatos MinTIC). Fuente #242,#258. Documentado.
**RN (11) — Delta:** RN-11-D01 (control de frescura del dataset) · RN-11-D02 (validación de calidad previa a federar). Fuente [NORMATIVA] Res.1519 Anexo 4. Documentado.
**Pendiente:** ¿SM CMS actualiza automáticamente a datos.gov.co o manual?

---

## Módulo 12 — Gestión de Contenidos y Administración

| RF | Fuente #NN | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|-----------|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| RF-B1-076 CMS roles + log | #239,#241 | Obj-3 | Editor/Administrador | UC-B2-017, HU-B1-011 | "Pendiente aprobación" + log | Dependencia SM CMS (riesgo 10) | D-06 | Documentado |
| RF-B1-079 Gestión usuarios/roles | #239,#241,#143 | Obj-7 | Administrador | UC-B1-015 | Permisos por módulo + auditoría | Roles SM CMS sin confirmar | — | Pendiente |
| RF-B2-067 Registro electrónico 24/7 | #143,#132,#217 | Obj-1 | Ciudadano | — | Radicado inmediato en festivo | — | D-04 | Documentado |
| RF-B2-068 Calendario hábil/inhábil | #143,#132,#217 | Obj-1 | Funcionario | — | Excluye fines de semana/festivos | — | — | Documentado |
| RF-B1-077 Archivo TRD/AGN | #21,#241,#131,#251 | Obj-3 | Funcionario | HU-B1-021 | Eliminar requiere aprobación + log | — | D-04 | Documentado |
| RF-B1-078 Tablero ITA interno (validación automática, desde cero) | #239 | Obj-3 | Administrador cumplimiento | UC-B1-011, HU-B1-014 / RF-12-D05 | Valida cada publicación contra criterios ITA; marca incumplimientos por ubicación/norma; arranca en cero, sin mock | A-03 resuelto (no mock) | D-14 | Documentado |
| **RF-12-D05** Validación automática ITA al publicar | [DECISIÓN 2026-06-05] | Obj-3 | Editor/Sistema | RF-07-D01 / HU-B1-014 | Imagen sin alt/video sin subtítulos/metadato faltante → marca o bloquea; alimenta el tablero | Generaliza RF-07-D01 | D-14 | Documentado |
| RF-B2-089 Analítica de uso | #70,#125,#144,#153 | Obj-6 | Administrador | — | Indicadores con drill-down | — | — | Documentado |
| RF-B2-093 Informe control interno 6m | #141,#172,#99 | Obj-3 | Administrador | — | Publica al semestre | — | D-12 | Documentado |
| RF-B3-153/154 Notificaciones multicanal | Decreto 2106 Art.46,#131,#132 | Obj-1 | Ciudadano | UC-B3-014, HU-B3-022 | Notificación <1h por canal preferido | — | D-04 | Documentado |
| RF-B3-155 Firma electrónica | Decreto 2106 Art.60 | Obj-5 | Funcionario | HU-B3-017 | Válida jurídicamente, archivada SGDEA | SGDEA = Orfeo (a implementar) | — | Documentado (Orfeo) |
| **RF-12-D01** Flujo aprobación/publicación con estados | [DOMINIO] | Obj-3 | Editor/Administrador | UC-048 / HU-12-D01 | Borrador→Pendiente→Publicado→Archivado; rechazo a edición | — | D-14 | Documentado |
| **RF-12-D02** CRUD usuarios internos con baja segura | [DOMINIO]+[INFERENCIA] | Obj-7 | Administrador | UC-020 A2 / HU-12-D02 | Baja revoca accesos ≤1 día; auditoría se conserva (no borrado físico) | — | D-04 | Documentado |
| **RF-12-D03** Reintento/cola notificaciones multicanal | [DOMINIO] | Obj-1 | Ciudadano | UC-008 E4 / HU-12-D03 | Correo rebota → reintento/canal alterno + estado de entrega | — | D-04 | Documentado |
| **RF-12-D04** Verificación de edad titulares menores | [NORMATIVA] Ley 1581 Art.7 | Obj-7 | Representante legal | UC-006 E6 / HU-12-D04 | Fecha nacimiento <18 → exige autorización del representante | — | — | Documentado |
| **RNF-12-D01** Concurrencia editorial | [DOMINIO] | RNF | Editor | UC-018 E4' / HU-12-D05 | Bloqueo optimista/pesimista al editar mismo contenido | — | D-14 | Documentado |

**RNF (12):** RNF-B1-044 (Hora Legal) · RNF-B2-026 (TRD 100%) · RNF-B3-022 (logs ≥5 años) · RNF-B3-044 (MTBF >4.320h, integridad) · RNF-B1-039 (RAEE). Fuente #156,#141,#131,#224,#165. Documentado.
**RN (12):** RN-B1-015/B3-012 (TRD/AGN) · RN-B3-016 (firma=autógrafa) · RN-B3-019 (notificación electrónica preferente) · RN-B1-022 (G-CIO reporta a representante legal) · RN-B1-024/B2-024 (PETI 5 años) · RN-B1-025 (Acuerdos Marco CCE) · RN-B2-013/014 (Oficial datos + PIGDP). Fuente #21,#241,#165,#258,#143. Documentado.
**RN (12) — Delta:** RN-12-D01 (ciclo editorial con estados) · RN-12-D02 (baja segura con retención de auditoría) · RN-12-D03 (verificación de edad de menores) · RN-12-D04 (garantía de entrega multicanal) · RN-12-D05 (bloqueo de edición concurrente) · RN-12-D06 (foliado/índice firmado del expediente). Fuente [NORMATIVA] Ley 1581/594/MSPI/Dec.1080 + [DOMINIO]. Documentado.

---

## Módulo TX — Requisitos y reglas transversales (delta profundo)

| ID | Necesidad/Obj | Stakeholder | UC/HU | Criterio aceptación | Riesgo/Contradicción | Diagrama | Estado |
|----|---------------|-------------|-------|---------------------|----------------------|----------|--------|
| **RNF-TX-D01** Observabilidad/uptime por componente | Obj-3 | Equipo TI | HU-01-D04 | Alerta automática al incumplir SLA (≥98% trámites / ≥95% sede) | Ligado a C-02 | D-06 | Documentado |
| **RNF-TX-D02** Internacionalización + lenguas étnicas | Obj-1 | Comunicaciones | RN-TX-D05 | Gestión de traducciones; fallback al castellano | Decisión #16: castellano por ahora | — | Diferido (Could) 2026-06-05 |
| **RNF-TX-D03** Retención/minimización de datos | Obj-7 | Oficial Protección Datos | UC-045 / HU-03-D04 | Política de retención y purga por categoría (Ley 1581/TRD) | Períodos por entidad (PA) | — | Pendiente |
| **RNF-TX-D04** Pruebas RTO/RPO (DRP/BCP) | Obj-7 | Equipo TI | — | Restauración real de backup periódica; RTO≤8min/RPO≤30min probados | Ligado a RF-B1-067 | — | Documentado |
| **RN-TX-D01** Calendario hábil único del Distrito | Obj-1 | Gestión Documental | UC-035/036, UC-042, UC-046 / HU-04-D03 | Una sola fuente de días hábiles para todos los cómputos | Habilita RN-04-D01/02/03, RN-03-D05 | D-03,D-09 | Documentado |
| **RN-TX-D02** Sellado temporal con Hora Legal | Obj-3 | Equipo TI | UC-001/005/007/008/039 / HU-B2-014 | Hora Legal INM + estampa TSA RFC 3161 en evidencia legal | — | D-08 | Documentado |
| **RN-TX-D03** Retención/purga de datos personales | Obj-7 | Oficial Protección Datos | UC-045 / HU-03-D04 | Período por categoría conforme TRD/minimización | **[PREGUNTA ABIERTA]** períodos | — | Pendiente |
| **RN-TX-D04** Nivel auth mínimo por tipo de trámite | Obj-7 | G-CIO / AND | UC-004 E8 / HU-03-D11 | Bloquea inicio si nivel del ciudadano < requerido | **C-08** matriz trámite↔nivel | D-07 | En conflicto (C-08) |
| **RN-TX-D05** Fallback al castellano en traducciones | Obj-1 | Comunicaciones | RNF-TX-D02 | Sin traducción → muestra castellano (original) | Decisión #16 | — | Diferido (Could) 2026-06-05 |

---

## Sub-matriz de contradicciones, ambigüedades y riesgos transversales

| Código | Tema | RF/RN afectados | Stakeholder resolutor | Resolución sugerida | Estado |
|--------|------|-----------------|----------------------|---------------------|--------|
| **C-01** | Restricción de adjuntos PQRSD | RF-B1-033, RN-B1-010 | Alcaldía / DAFP | Eliminar límite 10MB/formato; aplicar Anexo 5 (libre) | En conflicto — ALTA |
| **C-02** | Disponibilidad (≥95% vs ≥98%) | RNF-B1-001/B2-004/B3-006 | G-CIO / MinTIC | Aplicar ≥98% (Anexo 1 prevalente) para trámites | En conflicto — ALTA |
| **C-03** | Disponibilidad SCD (99.98 vs 99.982) | RNF-B1-002/B3-007 | AND | Adoptar el más exigente (99.982%) | En conflicto — MEDIA |
| **C-04** | Ubicación formulario PQRSD | RF-B1-031, RF-B2-091 | Alcaldía / MinTIC | Menú "Atención y Servicios" (norma prevalente) | En conflicto — ALTA |
| **C-05** | Fecha Decreto 088 (2021 vs 2022) | RN-B1-008 (cómputo plazos) | MinTIC | Usar firma 24-ene-2022 como base | En conflicto — MEDIA |
| **C-06** | Adjuntos PQRSD (derecho) vs trámites (requisito) | RF-B1-033/RN-B1-010 vs RF-03-D04/RN-03-D04/RF-B1-063 | Alcaldía / Seguridad / Protección Datos | PQRSD: no rechaza por tipo/tamaño pero aplica antivirus + límite técnico alto documentado; trámites: SÍ validan MIME/tamaño/cantidad | ✅ Resuelto 2026-06-05 (separar dominios) |
| **C-07** | MFA administradores del CMS | RNF-B1-021 / RF-B3-074 vs RN-09-D04 | Oficial de Seguridad | MFA obligatorio para roles internos del CMS (contraseña ≥8 no basta) | ✅ Resuelto 2026-06-05 (MFA todos los roles internos) |
| **C-08** | Matriz trámite ↔ nivel de autenticación | RF-B2-021 (nivel "medio") vs RN-TX-D04 | G-CIO / AND | Definir matriz trámite↔nivel; bloquear inicio si nivel insuficiente | ✅ Resuelto 2026-06-05 (matriz por riesgo; detalle con G-CIO/AND) |
| **memberClass** | "CO" vs "GOB/PRIV" | RN-B3-032, RF-B2-017 | AND | Verificar guía vigente en producción | En conflicto — MEDIA |
| **A-WCAG** | AA sin fiscalización/umbral | RN-B1-021, RNF-B1-014 | MinTIC | Definir umbral de errores tolerables | En conflicto — ALTA |
| **HPKP** | Cabecera obsoleta | RF-B1-056 | Equipo seguridad | Omitir HPKP; usar HSTS+CSP | En conflicto — MEDIA |
| **Captcha vs A11y** | Barrera visual | RF-B1-058, RF-B3-101 | UX/Accesibilidad | Captcha accesible con audio | En conflicto — ALTA |
| **WCAG 2.1 vs 2.2** | Estándar desactualizado | RN-B1-021 | MinTIC | Migrar a 2.2 cuando MinTIC lo adopte | En conflicto — MEDIA |
| **SO X-Road** | Ubuntu 18.04/RHEL7 fuera de soporte | RF-B1-028 | Equipo TI / AND | Verificar SO soportado con la AND | En conflicto — MEDIA |
| **Riesgo TSA** | Migración Certicámara→GSE | RF-B2-088 | Equipo TI | Ventana planificada sin mensajes sin sello | En conflicto — MEDIA |
| **A-03** | Datos ITA mock | RF-B1-078, RF-12-D05 | Administrador | ✅ Resuelto 2026-06-05: el tablero ITA se rediseña como validador interno automático que **arranca en cero** y no usa datos mock ni el 47/100; el cumplimiento se construye validando cada publicación | Resuelto (sin mock por diseño) |
| **A-11** | Enrutamiento PQRSD auto/manual | RF-B1-036, RF-B1-031, RF-04-D01 | Alcaldía | ✅ Decidido 2026-06-05: enrutamiento HÍBRIDO (auto-sugerencia + validación de ventanilla; reasignable) | Resuelto (híbrido) |
| **A-07** | Caracterización grupos interés | RF-B1-096, RF-B1-040 | Alcaldía | Caracterizar grupos del Distrito | Pendiente |
| **Grupo 088** | Clasificación Santa Marta — tier Avanzado/Intermedio/Básico (no "Grupo 1/2") | RF-B3-150, RN-B1-008 | MinTIC / Alcaldía | ✅ **Adoptado 2026-06-05: Alcaldía-Avanzado** (Santa Marta es Distrito → tier de Distrito Capital/Gobernaciones; plazos may/2028·mar/2034·abr/2037). Dataset no publicado en datos.gov.co; oficio a MinTIC como formalidad de confirmación. | Adoptado (Avanzado) — confirmar formalmente |
| **SGDEA** | Se implementa **Orfeo** (open-source) | RF-B1-092, RF-B1-036, RF-B1-075, RF-B3-155 | Alcaldía | ✅ Decidido 2026-06-05: SGDEA = **Orfeo** (gratuito, muy usado en el Estado; a implementar/configurar) | Resuelto (Orfeo) |
| **SIEM (zona auditoría)** | No nombrado explícitamente | RF-B1-065 | Equipo seguridad | Definir SIEM para zona de auditoría | Inferido |

---

## Resumen de trazabilidad por módulo

| Módulo | RF (filas) | RF-D | RNF (agrupados) | RNF-D | RN (agrupados) | RN-D | UC | HU | HU-D | RF/HU en conflicto/pendiente |
|--------|-----------|------|-----------------|-------|----------------|------|----|----|------|------------------------------|
| 01 Estructura/Identidad | 33 | 3 | 4 grupos | 1 | 10 | 3 | 1 +UC-048 | 2 | 4 | 6 |
| 02 Transparencia | 15 | 3 | 3 | 1 | 8 | 4 | 2 +UC-050 | 6 | 4 | 1 |
| 03 Servicios/Trámites | 22 | 7 | 3 | 2 | 8 | 8 | 6 +UC-042/043/044/045 | 5 | 13 | 5 (incl. C-06) |
| 04 PQRSD | 10 | 5 | 3 | 2 | 5 | 8 | 3 +UC-046 | 5 | 8 | 6 |
| 05 Participa | 3 | 2 | 2 | 0 | 2 | 2 | 1 | 3 | 2 | 2 |
| 06 Canales | 3 | 4 | 2 | 0 | 2 | 3 | 1 +UC-047 | 3 | 4 | 1 |
| 07 Accesibilidad | 6 grupos (52 CC) | 1 | 8 | 1 | 4 | 2 | 4 | 5 | 3 | 2 |
| 08 Usabilidad | 8 grupos | 1 | 9 | 1 | 3 | 1 | 2 | 6 | 2 | 2 |
| 09 Seguridad | 22 | 4 | 14 | 3 | 8 | 7 | 5 | 5 | 7 | 6 (incl. C-07) |
| 10 Interoperabilidad | 17 | 2 | 9 | 1 | 9 | 3 | 4 | 5 | 3 | 4 |
| 11 Datos Abiertos | 4 | 1 | 2 | 1 | 3 | 2 | 2 +UC-049 | 2 | 2 | 1 |
| 12 Gestión Contenidos | 10 | 4 | 5 | 1 | 7 | 6 | 4 +UC-048 | 7 | 5 | 2 |
| TX Transversal | — | — | — | 4 | — | 5 | — | — | — | 3 (incl. C-08) |
| **TOTAL** | **~155 RF** | **+37 RF-D** | **~64 RNF** | **+18 RNF-D** | **~69 RN** | **+54 RN-D** | **50 UC (41 base + 9 delta UC-042..050)** | **54 HU** | **+57 HU-D** | **41** |

> Nota de cobertura: el corpus declara 691 ítems (353 RF, 120 RNF, 93 RN, 52 UC, 73 HU). Esta matriz traza **fila por fila los RF y UC nucleares de cada módulo** (los IDs `RF-Bn-xxx` con dobletes/tripletes consolidan los 353 RF originales) y **agrupa RNF/RN por temática** conservando sus IDs explícitos para trazar a fuente. Cada RF mantiene su `(#NN)` hacia `/tmp/elicit/out/_req_bundle_{1,2,3}.md` y al documento original.

> **Delta de profundización (2026-06-04):** se integraron 37 RF-D + 18 RNF-D, 54 RN-D, 9 UC nuevos (UC-042..UC-050) con ~38 flujos alt/excepción sobre 18 UC existentes, y 57 HU-D (más enriquecimientos/divisiones INVEST). Nuevos conflictos C-06 (adjuntos PQRSD vs trámites), C-07 (MFA admins CMS), C-08 (matriz trámite↔nivel auth). Procedencia de los ítems `-D` por etiqueta [DOMINIO]/[NORMATIVA]/[WEB]/[INFERENCIA]/[PREGUNTA ABIERTA] (el corpus `#NN` fue borrado).

---

## Huecos de trazabilidad persistentes (para el auditor)

**RF/RNF sin UC ni HU directo:**
- RNF-09-D03 (umbral incidente "grave") — solo motiva la pregunta abierta A-06; sin UC/HU propio.
- RNF-TX-D04 (pruebas RTO/RPO) y RNF-TX-D02 (i18n) — sin UC; HU parcial (HU-01-D04 toca observabilidad, no DRP).
- RF-B1-067 (Backups DRP/BCP, base) sigue sin UC; ahora reforzado por RNF-TX-D04 pero sin caso de uso de "probar restauración".

**Decisiones del 2026-06-05 (cierran la trazabilidad; las marcadas con ▸ requieren solo detalle operativo en ejecución, ya no bloquean):**
1. ✅ Retención/expiración de borradores (RNF-03-D02 / RN-TX-D03 / HU-03-D04) — **30 días**, luego purga/anonimiza. (§8 #10)
2. ✅ Reembolso al desistir trámite pagado (RF-03-D07 / UC-043 E2 / HU-03-D08) — **solo si no inició la gestión**. ▸ Tesorería lo formaliza en el reglamento de cartera. (§8 #18)
3. ✅ Silencio administrativo (RN-03-D08 / UC-044 / HU-03-D09) — **negativo por defecto** (CPACA Art.83); SAP solo por norma especial. ▸ Secretaría Jurídica arma el catálogo de excepciones. (§8 #19)
4. ✅ Nivel de autenticación por trámite (C-08 / RN-TX-D04 / UC-004 E8 / HU-03-D11) — **matriz por riesgo** (bloquea inicio si nivel < requerido). ▸ Matriz concreta por trámite con G-CIO + AND. (§8 #15)
5. ✅ Agenda de citas (RF-06-D01 / UC-047 / HU-06-D01) — **agenda propia en la sede**. (§8 #12)
6. ✅ Umbral de usabilidad (A-14 / RF-08-D01 / RN-08-D01 / HU-08-D01) — **≥90% tasa de éxito** + SUS ≥68. (§8 #14)
7. ✅ Aportes de Participa (RF-05-D01/D02) — **CRUD propio dentro de la sede**; consultas normativas DNP vía redirección SUCOP. (§8 #13)
8. ✅ Traducción / lenguas étnicas (RNF-TX-D02 / RN-TX-D05) — **castellano por ahora**; lenguas étnicas como compromiso futuro (prioridad Could). (§8 #16)
9. ✅ Formato y unicidad del radicado (RNF-04-D01) — **prefijo dependencia + año + consecutivo atómico** (ej. `SM-CAT-2026-000123`). (§8 #11)

> Ninguna de estas bloquea ya el cierre de trazabilidad. Lo único pendiente es detalle operativo (ítems ▸ 2/3/4) que la Alcaldía formaliza durante la ejecución.

**Conflictos abiertos del delta:** C-06 (ALTA), C-07 (ALTA). *(C-08 decidido — §8 #15: matriz por riesgo; matriz concreta por trámite pendiente como detalle operativo.)* — ver sub-matriz.

**Roles back-office recién trazados** (antes ausentes en la matriz): funcionario de dependencia, administrador de atención/agenda, editor vs aprobador con SoD, oficial de seguridad/auditor, equipo técnico de interoperabilidad, representante legal de menor, admin de cumplimiento ITA. Reflejados en D-05b y en las HU-D.
