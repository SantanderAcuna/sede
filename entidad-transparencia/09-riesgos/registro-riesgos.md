# Registro de Riesgos

> **Formato:** cada riesgo tiene ID, descripción, categoría, probabilidad, impacto, score, estrategia (evitar/transferir/mitigar/aceptar), plan de mitigación, dueño, estado.

---

## 1. Riesgos técnicos (RT)

### R-TEC-01 — Sincronización con SIGEP no disponible o inestable
- **Categoría:** Técnico / Integración externa
- **Descripción:** El servicio SIGEP de la DAFP puede no estar accesible, cambiar su API sin aviso, o tener latencia alta que afecte la sincronización diaria de servidores públicos.
- **Probabilidad:** 4 (muy probable)
- **Impacto:** 3 (alto) — afecta RF-02-006 directamente.
- **Score:** 12 (Crítico)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Implementar cliente con retry exponencial (3 intentos) y circuit breaker.
  2. Cachear última sincronización exitosa por 7 días (fallback si SIGEP caído).
  3. Implementar SIGEP stub local para desarrollo y testing.
  4. Documentar contrato API y mantener pinning de versión.
  5. Job `Sigep:Sincronizar` registra fallos y notifica al admin.
- **Plan de contingencia:** Mostrar último directorio conocido con aviso "Última sincronización: hace X días".
- **Dueño:** Tech Lead Backend.
- **Estado:** Activo, plan en implementación (S-04).

### R-TEC-02 — Performance del buscador FTS con >100k documentos
- **Categoría:** Técnico / Performance
- **Descripción:** PostgreSQL FTS puede degradarse cuando se superan los 100k documentos indexados; el índice GIN crece en tamaño.
- **Probabilidad:** 3 (probable)
- **Impacto:** 3 (alto) — afecta RF-02-027 y RNF-REND-01.
- **Score:** 9 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Índices parciales `WHERE estado = 'publicado'`.
  2. Vista materializada `mv_documentos_subseccion` con índice GIN separado.
  3. Análisis de `EXPLAIN ANALYZE` mensual; tuneo de `work_mem`.
  4. Plan de migración a Elasticsearch si FTS > 500k docs (ADR-007 queda como upgrade).
- **Plan de contingencia:** Filtros más restrictivos en UI para reducir cardinalidad.
- **Dueño:** Tech Lead Backend.
- **Estado:** Activo.

### R-TEC-03 — Vulnerabilidad crítica en paquete Laravel o Vue
- **Categoría:** Técnico / Seguridad
- **Descripción:** Dependencias desactualizadas pueden contener CVEs conocidos (Log4Shell-like events).
- **Probabilidad:** 3 (probable)
- **Impacto:** 4 (crítico) — compromiso de seguridad total.
- **Score:** 12 (Crítico)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Dependabot activo en GitHub con auto-merge para parches de seguridad.
  2. `composer audit` y `npm audit` en CI (falla el merge si hay high/critical).
  3. RenovateBot para PRs automáticos.
  4. Calendario de upgrades: Laravel LTS cada 6 meses, Vue/Pinia al latest stable.
- **Plan de contingencia:** Patch de emergencia con rollback verificado en < 4 h.
- **Dueño:** DevOps + Security Champion.
- **Estado:** Activo, monitoreado en CI.

### R-TEC-04 — Drift entre OpenAPI spec y rutas Laravel
- **Categoría:** Técnico / Mantenibilidad
- **Descripción:** Modificaciones en rutas sin actualizar la spec generan desalineación entre frontend y backend.
- **Probabilidad:** 3 (probable)
- **Impacto:** 3 (alto) — tipos TS desactualizados; bugs en runtime.
- **Score:** 9 (Alto)
- **Estrategia:** Mitigar (automatizar).
- **Plan de mitigación:**
  1. CI ejecuta `php artisan contract:verificar-drift` en cada PR.
  2. Code review obligatorio verifica que el PR actualiza `contract/openapi.yaml`.
  3. Webhook que rechaza PR si ruta cambia sin actualizar spec.
- **Dueño:** Tech Lead Backend.
- **Estado:** Activo.

### R-TEC-05 — Saturación del storage S3 con archivos no optimizados
- **Categoría:** Técnico / Costos
- **Descripción:** Usuarios suben PDFs de 500 MB; con 50 GB/año de crecimiento se llega al límite de presupuesto.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio) — sobrecosto, RNF-CAP-02 afectado.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Validar tamaño máximo 100 MB en Form Request.
  2. Lifecycle policy: docs > 365 días → Coldline.
  3. Comprimir PDFs al recibir (opcional, reversible).
  4. Alerta cuando uso > 80% del presupuesto.
- **Dueño:** DevOps.
- **Estado:** Activo.

### R-TEC-06 — Migraciones de BD rompen datos en producción
- **Categoría:** Técnico / Datos
- **Descripción:** Una migración mal diseñada podría perder o corromper datos al deployar.
- **Probabilidad:** 2 (posible)
- **Impacto:** 4 (crítico) — pérdida de información pública.
- **Score:** 8 (Alto)
- **Estrategia:** Evitar + Mitigar.
- **Plan de mitigación:**
  1. Toda migración tiene `up()` y `down()` reversible (RT-06).
  2. Test de migración en BD copia de prod antes de aplicar.
  3. Backup automático pre-deploy (PITR + backup manual).
  4. Deploy canary 10% → 100% en 2 etapas.
- **Dueño:** Tech Lead Backend.
- **Estado:** Activo.

### R-TEC-07 — Bundle inicial del sitio > 150 KB (RNF-REND-02)
- **Categoría:** Técnico / Performance
- **Descripción:** El sitio Nuxt con SSR puede tener un bundle inicial mayor al objetivo.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio) — LCP degrada.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Code splitting por ruta; lazy load de imágenes y componentes no críticos.
  2. Tree shaking y PurgeCSS en Tailwind.
  3. Lighthouse budget en CI (rechaza si JS > 150 KB gzip).
- **Dueño:** Tech Lead Frontend.
- **Estado:** Activo, validado en S-08.

### R-TEC-08 — Brecha en contrato JSON:API por no usar paquete estándar
- **Categoría:** Técnico / Cumplimiento técnico
- **Descripción:** Implementación manual de JSON:API puede no cumplir el 100% de la especificación.
- **Probabilidad:** 2 (posible)
- **Impacto:** 2 (medio) — incompatibilidad con clientes externos.
- **Score:** 4 (Medio)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Tests E2E que validan cumplimiento JSON:API 1.0 contra fixtures.
  2. Revisión manual por Tech Lead del `JsonApiResource` base.
  3. Documentar extensiones explícitas del estándar.
- **Dueño:** Tech Lead Backend.
- **Estado:** Activo.

### R-TEC-09 — Pérdida de sesión Sanctum al desplegar
- **Categoría:** Técnico / Operación
- **Descripción:** Sesiones de usuarios del panel pueden invalidarse con cada deploy.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio) —用户体验 degradada.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Sesiones Sanctum persistidas en Redis (alta disponibilidad).
  2. TTL extendido (24 h) con sliding renewal.
  3. Deploy blue-green con keepalive de conexiones.
- **Dueño:** DevOps.
- **Estado:** Activo.

### R-TEC-10 — Tipos TS desactualizados por cambios en OpenAPI
- **Categoría:** Técnico / Mantenibilidad
- **Descripción:** El frontend consume tipos TS que no reflejan la API real.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio) — bugs en build.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. `pnpm openapi:generar-ts` se ejecuta automáticamente en CI.
  2. PRs que cambien OpenAPI fallan el merge si los tipos TS no se regeneran.
  3. Test E2E contra backend real para validar tipos.
- **Dueño:** Tech Lead Frontend.
- **Estado:** Activo.

### R-TEC-11 — Búsqueda devuelve resultados irrelevantes por stemming español limitado
- **Categoría:** Técnico / Calidad
- **Descripción:** PostgreSQL FTS con `to_tsvector('spanish')` puede no lematizar correctamente términos coloquiales o regionales colombianos.
- **Probabilidad:** 2 (posible)
- **Impacto:** 2 (medio) — RF-02-027 con baja calidad.
- **Score:** 4 (Medio)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Análisis mensual de las 100 queries más frecuentes.
  2. Diccionario personalizado de sinónimos para términos locales ("alcalde" = "burgomaestre").
  3. Pruebas de usuario con 10 casos reales.
- **Dueño:** Tech Lead Backend.
- **Estado:** Activo.

### R-TEC-12 — Inconsistencias entre datos SIGEP y datos locales
- **Categoría:** Técnico / Datos
- **Descripción:** SIGEP puede tener datos que difieren de los registrados localmente en la Alcaldía.
- **Probabilidad:** 3 (probable)
- **Impacto:** 1 (bajo) — solo afecta unicidad.
- **Score:** 3 (Medio)
- **Estrategia:** Aceptar.
- **Plan:** SIGEP es fuente única; el local se sobrescribe en cada sync.
- **Dueño:** Tech Lead Backend.
- **Estado:** Aceptado.

---

## 2. Riesgos de proyecto (RP)

### R-PRY-01 — Retraso de 2+ sprints por dependencias externas
- **Categoría:** Proyecto / Cronograma
- **Descripción:** Las integraciones con SIGEP, SECOP, SUIN pueden retrasar el sprint 4-5.
- **Probabilidad:** 3 (probable)
- **Impacto:** 4 (crítico) — go-live retrasado.
- **Score:** 12 (Crítico)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Sprint 1: iniciar conversaciones formales con SIGEP/SECOP/SUIN.
  2. Plan B: stubs locales para mantener desarrollo paralelo.
  3. Buffer de 1 sprint (sprint 8) para integración tardía.
- **Dueño:** Product Owner / G-CIO.
- **Estado:** Activo, acción temprana en S-01.

### R-PRY-02 — Pérdida de un miembro clave del equipo
- **Categoría:** Proyecto / Recursos
- **Descripción:** Tech Lead, G-CIO o un backend senior podría dejar el proyecto.
- **Probabilidad:** 2 (posible)
- **Impacto:** 4 (crítico) — pérdida de conocimiento.
- **Score:** 8 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Documentación continua en `entidad-transparencia/` (este artefacto).
  2. Pair programming en tareas críticas.
  3. Knowledge transfer plan: cada componente tiene al menos 2 devs familiarizados.
  4. Code review cruzado como práctica permanente.
- **Dueño:** Scrum Master.
- **Estado:** Activo.

### R-PRY-03 — Cambios en normativa durante el proyecto
- **Categoría:** Proyecto / Regulatorio
- **Descripción:** Res. 1519/2020 podría ser actualizada durante el desarrollo.
- **Probabilidad:** 2 (posible)
- **Impacto:** 3 (alto) — afecta cumplimiento ITA.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Suscripción a alertas de MinTIC y Diario Oficial.
  2. Diseño modular que permite añadir ítems ITA sin refactor mayor.
  3. Reunión mensual de compliance.
- **Dueño:** G-CIO.
- **Estado:** Activo.

### R-PRY-04 — Subestimación del alcance de pruebas E2E
- **Categoría:** Proyecto / Calidad
- **Descripción:** Los flujos E2E pueden requerir más tiempo del estimado por configuración de Playwright + axe-core + mocks.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio).
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Spike en Sprint 1 para validar setup Playwright.
  2. Reutilizar fixtures y page objects.
  3. Smoke tests diarios con subset crítico.
- **Dueño:** QA Lead.
- **Estado:** Activo.

### R-PRY-05 — Cambios de alcance del Product Owner durante sprints
- **Categoría:** Proyecto / Alcance
- **Descripción:** El G-CIO podría solicitar nuevas funcionalidades a mitad del sprint.
- **Probabilidad:** 4 (muy probable)
- **Impacto:** 2 (medio).
- **Score:** 8 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Backlog Refinement semanal para alinear.
  2. Cambios de alcance se reflejan en sprint siguiente (no durante).
  3. Sprint Goal claro y firmado por PO al inicio.
- **Dueño:** Scrum Master.
- **Estado:** Activo.

### R-PRY-06 — Burnout del equipo por sprint 6 (Navidad)
- **Categoría:** Proyecto / Recursos humanos
- **Descripción:** El sprint 6 cruza Navidad y puede haber fatiga.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio).
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Reducir capacidad comprometida del sprint 6 (-10%).
  2. Tiempo de vacaciones cubierto con trabajo asíncrono.
  3. Retrospectiva explícita sobre bienestar.
- **Dueño:** Scrum Master.
- **Estado:** Planificado para S-06.

### R-PRY-07 — Documentación insuficiente para auditoría
- **Categoría:** Proyecto / Compliance
- **Descripción:** Ente de control pide documentación y no se tiene completa.
- **Probabilidad:** 2 (posible)
- **Impacto:** 3 (alto).
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. `entidad-transparencia/` completo y actualizado antes de go-live.
  2. Acta de aceptación firmada por PO.
  3. Carpeta `docs/` del proyecto con todos los manuales operativos.
- **Dueño:** G-CIO + Tech Lead.
- **Estado:** Activo.

### R-PRY-08 — Dificultad de contratar QA especializado en accesibilidad
- **Categoría:** Proyecto / Recursos
- **Descripción:** El mercado colombiano tiene pocos QA con experiencia WCAG 2.1 AA.
- **Probabilidad:** 2 (posible)
- **Impacto:** 2 (medio).
- **Score:** 4 (Medio)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Capacitación interna del equipo en WCAG (cursos en línea).
  2. Auditoría externa anual (consultoría).
  3. axe-core automatiza 80% de la verificación.
- **Dueño:** G-CIO.
- **Estado:** Activo.

---

## 3. Riesgos normativos (RN)

### R-NOR-01 — Cambio de administración distrital durante el proyecto
- **Categoría:** Normativo / Político
- **Descripción:** Cambio de Alcalde en 2027 podría cambiar prioridades o cancelar el proyecto.
- **Probabilidad:** 2 (posible — fuera del alcance del sprint actual)
- **Impacto:** 4 (crítico) — cancelación total.
- **Score:** 8 (Alto)
- **Estrategia:** Mitigar (políticamente).
- **Plan de mitigación:**
  1. Inclusión de transición en el PETI.
  2. Empalme documentado con el equipo entrante.
  3. Cumplimiento de hitos que generen valor inmediato (ITA, datos abiertos).
  4. Visibilidad pública del proyecto (sala de prensa).
- **Dueño:** Alcalde / G-CIO.
- **Estado:** Activo, fuera del control directo del equipo técnico.

### R-NOR-02 — Auditoría ITA con resultado < 85
- **Categoría:** Normativo / Compliance
- **Descripción:** La autoevaluación ITA puede no alcanzar el umbral de 85/100 exigido.
- **Probabilidad:** 2 (posible)
- **Impacto:** 3 (alto) — RN-02 no cumplido.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Tablero ITA automático (RF-02-029) marca incumplimientos.
  2. Revisión quincenal del tablero ITA en Comité de Gestión.
  3. Plan correctivo documentado para cada ítem < cumple.
- **Dueño:** G-CIO.
- **Estado:** Activo.

### R-NOR-03 — Interpretación divergente de Ley 1581/2012 por la SIC
- **Categoría:** Normativo / Privacidad
- **Descripción:** La SIC podría requerir ajustes en el manejo de datos personales no previstos.
- **Probabilidad:** 2 (posible)
- **Impacto:** 3 (alto) — RNF-PD-01 afectado.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Oficial de Protección de Datos con experiencia en sector público.
  2. Registro Nacional de Bases de Datos (RNBD) actualizado.
  3. Consulta previa con la SIC si hay dudas.
- **Dueño:** Oficial de Protección de Datos.
- **Estado:** Activo.

### R-NOR-04 — Bloqueo por habeas data de un ciudadano
- **Categoría:** Normativo / Legal
- **Descripción:** Un ciudadano podría solicitar exclusión de datos y generar disputa legal.
- **Probabilidad:** 2 (posible)
- **Impacto:** 2 (medio) — caso aislado.
- **Score:** 4 (Medio)
- **Estrategia:** Aceptar + Mitigar.
- **Plan de mitigación:**
  1. Procedimiento ARCO documentado y probado.
  2. Respuesta en ≤ 15 días hábiles.
  3. Asesoría jurídica del Distrito.
- **Dueño:** Oficial de Protección de Datos.
- **Estado:** Activo.

### R-NOR-05 — Demora en aprobación del Plan de Integración GOV.CO por MinTIC
- **Categoría:** Normativo / Administrativo
- **Descripción:** MinTIC puede tardar más de lo esperado en aprobar el plan de integración.
- **Probabilidad:** 2 (posible)
- **Impacto:** 2 (medio).
- **Score:** 4 (Medio)
- **Estrategia:** Aceptar.
- **Plan:** La integración funciona técnicamente sin la aprobación; el enmascaramiento URL es opcional.
- **Dueño:** G-CIO.
- **Estado:** Activo.

---

## 4. Riesgos de seguridad (RS)

### R-SEG-01 — Suplantación de identidad en el panel
- **Categoría:** Seguridad / Autenticación
- **Descripción:** Un atacante con credenciales válidas podría acceder al panel.
- **Probabilidad:** 3 (probable)
- **Impacto:** 4 (crítico) — publicación de información falsa o sensible.
- **Score:** 12 (Crítico)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. MFA TOTP obligatorio (RNF-SEG-02).
  2. Contraseñas ≥ 12 chars con validación contra HIBP.
  3. Bloqueo tras 5 intentos fallidos.
  4. Logs de auditoría de toda operación (R-TEC-13).
- **Dueño:** Tech Lead Backend + Oficial de Seguridad.
- **Estado:** Activo.

### R-SEG-02 — Vulnerabilidad XSS/CSRF en contenido editorial
- **Categoría:** Seguridad / Aplicación
- **Descripción:** El editor HTML de documentos podría permitir inyección de scripts maliciosos.
- **Probabilidad:** 3 (probable)
- **Impacto:** 4 (crítico) — compromiso del navegador del visitante.
- **Score:** 12 (Crítico)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. CSP estricta (sin `unsafe-inline`, sin `unsafe-eval`).
  2. Sanitización HTML con DOMPurify en backend y frontend.
  3. CSRF tokens en todos los POST/PATCH/DELETE.
  4. Pentest anual.
- **Dueño:** Tech Lead Backend.
- **Estado:** Activo.

### R-SEG-03 — Acceso indebido a datos personales del directorio
- **Categoría:** Seguridad / Privacidad
- **Descripción:** Un atacante podría extraer el directorio completo de servidores.
- **Probabilidad:** 2 (posible)
- **Impacto:** 3 (alto) — privacidad.
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. RLS en PostgreSQL (servidor_publico).
  2. Vista pública `v_directorio_publico` sin datos sensibles.
  3. Rate limit estricto en `/transparencia/directorio`.
- **Dueño:** Oficial de Protección de Datos + Tech Lead.
- **Estado:** Activo.

### R-SEG-04 — DDoS durante evento público (ej. elecciones, calamidad)
- **Categoría:** Seguridad / Disponibilidad
- **Descripción:** Picos de tráfico durante eventos relevantes podrían tirar el sitio.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio).
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Cloudflare WAF + DDoS protection (ADR-008).
  2. Auto-scaling GKE (3-15 réplicas).
  3. CDN con caché agresivo para contenido público.
- **Dueño:** DevOps.
- **Estado:** Activo.

### R-SEG-05 — Filtración de código fuente por error de configuración Git
- **Categoría:** Seguridad / Operación
- **Descripción:** Un commit podría incluir credenciales en el código.
- **Probabilidad:** 3 (probable)
- **Impacto:** 3 (alto).
- **Score:** 9 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Pre-commit hook con `gitleaks`.
  2. GitHub secret scanning activo.
  3. Variables de entorno en `.env.example`, secrets en Secret Manager.
- **Dueño:** DevOps + todos los devs.
- **Estado:** Activo.

### R-SEG-06 — Compromiso de credenciales de la BD o S3
- **Categoría:** Seguridad / Operación
- **Descripción:** Credenciales filtradas podrían permitir acceso a la BD o a documentos en S3.
- **Probabilidad:** 2 (posible)
- **Impacto:** 4 (crítico).
- **Score:** 8 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Rotación trimestral de credenciales.
  2. IAM con privilegios mínimos (S3 solo lectura para app, escritura separada para admin).
  3. Alertas de uso anómalo.
  4. Cifrado at-rest habilitado.
- **Dueño:** DevOps.
- **Estado:** Activo.

---

## 5. Riesgos operativos (RO)

### R-OPS-01 — Servicio de correo (SMTP) cae o es marcado como spam
- **Categoría:** Operativo / Comunicación
- **Descripción:** Emails transaccionales (alertas, recuperación de contraseña) podrían no llegar.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio).
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Usar proveedor dedicado (SendGrid, Mailgun) en lugar de SMTP propio.
  2. SPF, DKIM, DMARC configurados.
  3. Notificaciones también en panel (no solo email).
- **Dueño:** DevOps.
- **Estado:** Activo.

### R-OPS-02 — Costos cloud超出 presupuesto
- **Categoría:** Operativo / Financiero
- **Descripción:** El gasto mensual de GCP puede exceder el presupuesto de USD 1200.
- **Probabilidad:** 2 (posible)
- **Impacto:** 3 (alto).
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Alertas de presupuesto en GCP al 50%, 80%, 100%.
  2. Auto-scaling con límites (min 3, max 15).
  3. Revisión mensual de costos con FinOps.
- **Dueño:** DevOps + G-CIO.
- **Estado:** Activo.

### R-OPS-03 — Capacitación insuficiente a editores del panel
- **Categoría:** Operativo / Adopción
- **Descripción:** Los editores podrían no usar correctamente el panel, generando errores de publicación.
- **Probabilidad:** 3 (probable)
- **Impacto:** 2 (medio).
- **Score:** 6 (Alto)
- **Estrategia:** Mitigar.
- **Plan de mitigación:**
  1. Manual de usuario del panel.
  2. Video tutoriales (3-5 min cada uno).
  3. Sesión de capacitación presencial (4 horas).
  4. Tooltips contextuales en la UI.
- **Dueño:** G-CIO + UX Designer.
- **Estado:** Activo, planificar para S-07.

### R-OPS-04 — Pérdida de conocimiento por rotación de editores
- **Categoría:** Operativo / Personas
- **Descripción:** Editores con experiencia en el panel podrían cambiar de cargo.
- **Probabilidad:** 2 (posible)
- **Impacto:** 2 (medio).
- **Score:** 4 (Medio)
- **Estrategia:** Aceptar + Mitigar.
- **Plan:** Documentación del panel + al menos 2 editores por dependencia.
- **Dueño:** G-CIO.
- **Estado:** Activo.

---

## 6. Resumen por categoría y plan de acción

| Riesgo | Score | Estrategia | Plan listo | Responsable |
|---|---|---|---|---|
| R-TEC-01 | 12 | Mitigar | Sí | Tech Lead Backend |
| R-TEC-03 | 12 | Mitigar | Sí | DevOps |
| R-PRY-01 | 12 | Mitigar | Sí | G-CIO |
| R-SEG-01 | 12 | Mitigar | Sí | Tech Lead |
| R-SEG-02 | 12 | Mitigar | Sí | Tech Lead |
| R-TEC-02 | 9 | Mitigar | Sí | Tech Lead |
| R-TEC-04 | 9 | Mitigar | Sí | Tech Lead |
| R-SEG-05 | 9 | Mitigar | Sí | DevOps |
| R-TEC-06 | 8 | Evitar + Mitigar | Sí | Tech Lead |
| R-SEG-06 | 8 | Mitigar | Sí | DevOps |
| R-NOR-01 | 8 | Mitigar (político) | Sí | Alcalde |
| R-TEC-05 | 6 | Mitigar | Sí | DevOps |
| R-TEC-07 | 6 | Mitigar | Sí | Tech Lead FE |
| R-TEC-09 | 6 | Mitigar | Sí | DevOps |
| R-TEC-10 | 6 | Mitigar | Sí | Tech Lead FE |
| R-PRY-03 | 6 | Mitigar | Sí | G-CIO |
| R-PRY-04 | 6 | Mitigar | Sí | QA |
| R-PRY-05 | 8 | Mitigar | Sí | Scrum Master |
| R-PRY-06 | 6 | Mitigar | Sí | Scrum Master |
| R-PRY-07 | 6 | Mitigar | Sí | G-CIO |
| R-NOR-02 | 6 | Mitigar | Sí | G-CIO |
| R-NOR-03 | 6 | Mitigar | Sí | Of. Datos |
| R-SEG-03 | 6 | Mitigar | Sí | Of. Datos |
| R-SEG-04 | 6 | Mitigar | Sí | DevOps |
| R-OPS-01 | 6 | Mitigar | Sí | DevOps |
| R-OPS-02 | 6 | Mitigar | Sí | DevOps |
| R-OPS-03 | 6 | Mitigar | Sí | G-CIO |

---

## 7. Revisión periódica

| Frecuencia | Actividad | Responsable |
|---|---|---|
| Diaria | Daily standup revisa riesgos nuevos/bloqueantes | Scrum Master |
| Semanal | Revisión de riesgos Críticos y Altos | Tech Lead |
| Quincenal | Reunión de riesgos con G-CIO | G-CIO |
| Mensual | Revisión completa del registro | G-CIO + equipo |
| Trimestral | Auditoría externa de cumplimiento | Ente de control |

---

## 8. Proceso de escalamiento

```
Riesgo identificado
        ↓
Scrum Master evalúa
        ↓
¿Score ≥ 12? ─→ Sí → Escalamiento inmediato a G-CIO (24 h)
        ↓ No
¿Score ≥ 6? ─→ Sí → Mitigación activa, revisión semanal
        ↓ No
Monitoreo mensual
```
