# Supuestos del Proyecto

> **Definición:** un supuesto es una condición que se asume verdadera para la planificación y ejecución del proyecto. Si resulta ser falsa, requiere re-planificación.
> **Convención:** cada supuesto tiene ID, descripción, fundamento, impacto si es falso, plan de validación.

---

## 1. Supuestos sobre el entorno

### SUP-01 — PostgreSQL 15+ disponible en producción
- **Descripción:** El equipo de infraestructura tiene acceso a una instancia PostgreSQL 15+ gestionada.
- **Fundamento:** Stack ADR-003; ya validado en `sede-electronica-doc/_bd/`.
- **Impacto si es falso:** Migración a MySQL 8.0 requeriría adaptar las migraciones (RN-08 afectado).
- **Validación:** Confirmar con G-CIO en Sprint 0.

### SUP-02 — Cloudflare plan Business disponible
- **Descripción:** El Distrito tiene o adquirirá Cloudflare Business (USD 200/mes).
- **Fundamento:** ADR-008.
- **Impacto si es falso:** Mitigación con Cloudflare Free + rate limit estricto en Laravel; latencia mayor.
- **Validación:** Confirmar presupuesto en Sprint 0.

### SUP-03 — GCP us-central1 disponible con cuota suficiente
- **Descripción:** GCP acepta el proyecto y otorga cuota para GKE, Cloud SQL, Memorystore.
- **Fundamento:** ADR-015.
- **Impacto si es falso:** Migración a AWS (más caro) o DigitalOcean (menos features).
- **Validación:** Solicitar incremento de cuota en Sprint 0.

### SUP-04 — Acceso a internet estable en Santa Marta
- **Descripción:** El equipo de desarrollo y operaciones tiene acceso a internet de al menos 50 Mbps simétricos.
- **Fundamento:** Trabajo remoto + CI/CD requieren conectividad.
- **Impacto si es falso:** Reducir dependencia de CI remoto; más trabajo local.
- **Validación:** Verificar en Sprint 0.

### SUP-05 — Cobertura móvil 4G/LTE para usuarios ciudadanos
- **Descripción:** La población de Santa Marta tiene acceso razonable a internet móvil.
- **Fundamento:** Las mediciones de MinTIC indican ~85% cobertura 4G en zona urbana.
- **Impacto si es falso:** Optimizar aún más para 3G/2G (mayor compresión, menos JS).
- **Validación:** Estadísticas DANE y MinTIC.

---

## 2. Supuestos sobre el equipo

### SUP-06 — Equipo completo desde el inicio
- **Descripción:** 4 devs backend, 3 devs frontend, 1 DevOps, 1 QA, 1 G-CIO, 1 diseñador UX/UI.
- **Fundamento:** Estimación de capacidad en `07-plan-tareas/backlog.md`.
- **Impacto si es falso:** Reducir alcance del sprint 1-2 o extender cronograma.
- **Validación:** Confirmación de G-CIO y RR.HH.

### SUP-07 — Conocimiento de Laravel 13 y Nuxt 4 en el equipo
- **Descripción:** Al menos 2 devs tienen experiencia previa con Laravel 11/12 y Nuxt 3/4.
- **Fundamento:** Stack común en el ecosistema PHP/Vue.
- **Impacto si es falso:** Capacitación previa de 1 semana (incluida en Sprint 0).
- **Validación:** Encuesta interna al equipo.

### SUP-08 — Product Owner con disponibilidad semanal
- **Descripción:** El G-CIO dedica al menos 4 h/semana a ceremonias Scrum.
- **Fundamento:** Definido en `07-plan-tareas/ceremonias.md`.
- **Impacto si es falso:** Retrasos en aceptación de historias.
- **Validación:** Acuerdo formal con el G-CIO.

### SUP-09 — Capacidad de QA para pruebas manuales
- **Descripción:** QA tiene 8 h/semana para pruebas manuales y exploratorias.
- **Fundamento:** Velocidad objetivo en `07-plan-tareas/sprints.md`.
- **Impacto si es falso:** Reducir historias del sprint o aceptar menor cobertura.
- **Validación:** Confirmación con QA Lead.

### SUP-10 — Diseñador UX/UI disponible para sprints 2, 6 y 8
- **Descripción:** El diseñador dedica tiempo a wireframes, mockups y auditoría de accesibilidad.
- **Fundamento:** Identidad visual crítica (RF-01-005, RNF-ACES-01).
- **Impacto si es falso:** Diseño básico sin identidad visual completa.
- **Validación:** Confirmación con G-CIO.

---

## 3. Supuestos sobre el negocio y usuarios

### SUP-11 — Usuarios objetivo saben usar navegadores web
- **Descripción:** Los usuarios objetivo (ciudadanos, servidores públicos) tienen alfabetización digital básica.
- **Fundamento:** Ley GEL asume conectividad digital progresiva.
- **Impacto si es falso:** Mayor énfasis en tutoriales y atención presencial.
- **Validación:** Estudio de caracterización de usuarios en Sprint 1.

### SUP-12 — El volumen de documentos no excede 100k en el primer año
- **Descripción:** La Alcaldía Distrital maneja menos de 100k documentos de transparencia en el primer año.
- **Fundamento:** Estimación conservadora basada en Montería y Santa Marta.
- **Impacto si es falso:** Migración a Elasticsearch (ADR-007 upgrade).
- **Validación:** Inventario inicial de documentos.

### SUP-13 — Los editores son empleados de la Alcaldía con contrato vigente
- **Descripción:** Cada editor tiene contrato laboral o de prestación con el Distrito.
- **Fundamento:** Requisito de acceso a panel autenticado.
- **Impacto si es falso:** Definir tipos de usuario adicionales (terceros, proveedores).
- **Validación:** Listado de cargos del Distrito.

### SUP-14 — Los datos de la Alcaldía ya están digitalizados
- **Descripción:** No hay migración masiva de documentos físicos a digitales en este proyecto.
- **Fundamento:** Alcance del proyecto (sin módulo de digitalización).
- **Impacto si es falso:** Necesidad de OCR + carga manual; sprint adicional.
- **Validación:** Reunión con Gestión Documental del Distrito.

### SUP-15 — El contenido en inglés NO es requerido en el primer año
- **Descripción:** Solo castellano; otros idiomas son diferidos.
- **Fundamento:** Decisión #16 (2026-06-05).
- **Impacto si es falso:** RF-01-D01 se activa; coste +20%.
- **Validación:** Decisión del G-CIO.

---

## 4. Supuestos técnicos

### SUP-16 — Las APIs externas (SIGEP, SECOP, SUIN) son estables
- **Descripción:** Las APIs externas mantienen su contrato durante el proyecto.
- **Fundamento:** Servicios públicos consolidados.
- **Impacto si es falso:** Stubs locales como fallback (R-TEC-01).
- **Validación:** Pruebas de integración tempranas en Sprint 4.

### SUP-17 — GitHub como repositorio está disponible
- **Descripción:** GitHub es la plataforma de repositorio (sin on-premise GitLab).
- **Fundamento:** Ya en uso en el proyecto.
- **Impacto si es falso:** Migración a GitLab on-premise.
- **Validación:** Confirmación con DevOps.

### SUP-18 — Docker disponible en máquinas de desarrollo
- **Descripción:** Cada dev tiene Docker Desktop o equivalente.
- **Fundamento:** Stack contenerizado (compose.yaml).
- **Impacto si es falso:** Setup manual más complejo.
- **Validación:** Verificación en onboarding.

### SUP-19 — El navegador objetivo es Chrome/Edge/Firefox/Safari recientes
- **Descripción:** Los usuarios usan versiones recientes (≤ 2 versiones atrás).
- **Fundamento:** RNF-PORT-01.
- **Impacto si es falso:** Polyfills adicionales; coste +15%.
- **Validación:** Analytics de los sitios previos.

### SUP-20 — Node.js 20+ y PHP 8.3+ disponibles
- **Descripción:** El equipo puede instalar Node.js 20+ y PHP 8.3+ localmente.
- **Fundamento:** Versiones mínimas del stack.
- **Impacto si es falso:** Trabajo en VMs remotas.
- **Validación:** Verificación de entorno en Sprint 0.

### SUP-21 — Composer y npm funcionan sin restricciones de red
- **Descripción:** El equipo puede acceder a Packagist y npm registry sin bloqueos.
- **Fundamento:** Sin firewall corporativo restrictivo.
- **Impacto si es falso:** Mirror privado de paquetes.
- **Validación:** Verificación en Sprint 0.

### SUP-22 — El navegador soporta Service Workers y Web Crypto API
- **Descripción:** Funcionalidades modernas disponibles (≥95% navegadores).
- **Fundamento:** CanIUse.
- **Impacto si es falso:** Polyfills o fallbacks.
- **Validación:** Automático por base de usuarios.

---

## 5. Supuestos sobre la normativa

### SUP-23 — Ley 1712/2014 no se modifica durante el proyecto
- **Descripción:** La estructura de 10 subsecciones se mantiene estable.
- **Fundamento:** Ley vigente desde 2014, estable.
- **Impacto si es falso:** Migración de datos + UI; sprint adicional (R-PRY-03).
- **Validación:** Monitoreo de cambios legislativos.

### SUP-24 — Res. 1519/2020 sigue vigente
- **Descripción:** La resolución y sus anexos se mantienen vigentes.
- **Fundamento:** Vigente desde 2020.
- **Impacto si es falso:** Tablero ITA recalculado contra nueva norma.
- **Validación:** Alertas MinTIC.

### SUP-25 — Ley 1581/2012 no se modifica durante el proyecto
- **Descripción:** El marco de protección de datos se mantiene.
- **Fundamento:** Vigente desde 2012.
- **Impacto si es falso:** RNF-PD-01 requiere ajustes.
- **Validación:** Alertas SIC.

### SUP-26 — Decreto 2106/2019 mantiene los plazos
- **Descripción:** Los plazos de digitalización de trámites no se modifican.
- **Fundamento:** Santa Marta como Distrito Avanzado (Bloque 1: may/2028).
- **Impacto si es falso:** Re-planificación del roadmap.
- **Validación:** Monitoreo de MinTIC.

### SUP-27 — Los plazos de digitalización son alcanzables
- **Descripción:** El cronograma del proyecto (4 meses para módulos 01-02) es alcanzable.
- **Fundamento:** Estimación en `07-plan-tareas/sprints.md`.
- **Impacto si es falso:** Extensión del cronograma (no crítico).
- **Validación:** Velocity del Sprint 1.

---

## 6. Supuestos sobre stakeholders

### SUP-28 — El Alcalde apoya públicamente el proyecto
- **Descripción:** El Alcalde respalda el PETI y la integración con GOV.CO.
- **Fundamento:** Plan de Desarrollo Distrital vigente.
- **Impacto si es falso:** Falta de apoyo en minTIC; retrasos administrativos.
- **Validación:** Acuerdo formal.

---

## 7. Plan de validación de supuestos

| Sprint | Supuestos a validar | Acción |
|---|---|---|
| Sprint 0 (pre-proyecto) | SUP-01..06, SUP-08, SUP-17..21, SUP-28 | Reuniones de kick-off con stakeholders; verificación técnica |
| Sprint 1 | SUP-11, SUP-12, SUP-14 | Investigación de usuarios + inventario de documentos |
| Sprint 2 | SUP-07, SUP-10 | Onboarding técnico; design workshop |
| Sprint 4 | SUP-13, SUP-16 | Pruebas de integración temprana |
| Sprint 5 | SUP-09 | QA testing real |
| Sprint 7 | SUP-22, SUP-19 | Cross-browser testing |
| Sprint 8 | SUP-23..27 | Auditoría final de compliance |

---

## 8. Supuestos que requieren re-planificación si son falsos

| Prioridad | Supuesto | Acción si falso |
|---|---|---|
| Crítica | SUP-01 (PostgreSQL 15+) | Migración a MySQL 8.0 |
| Crítica | SUP-08 (PO disponible) | Delegar PO a subalterno |
| Crítica | SUP-23, SUP-24, SUP-25 (normativa) | Sprint adicional de adaptación |
| Alta | SUP-02, SUP-03 (infra) | Re-evaluación ADR |
| Alta | SUP-06 (equipo) | Reducción de alcance |
| Media | Resto | Monitoreo |
