# Solicitud de vinculación a los Servicios Ciudadanos Digitales (AND)

**ALCALDÍA DISTRITAL DE SANTA MARTA** — Distrito Turístico, Cultural e Histórico
Dirección de las Tecnologías de la Información y las Comunicaciones (TIC)
Calle 14 No. 2-49, Palacio Municipal, Centro Histórico · Tel. 420 9600

Santa Marta, D.T.C.H., ____ de ____________ de 2026 · Oficio N.º ________

**Señores**
**AGENCIA NACIONAL DIGITAL (AND)** — Ministerio de Tecnologías de la Información y las Comunicaciones
Atn.: [Nombre del funcionario de enlace] · Correo: [verificar correo oficial en `mintic.gov.co` / `and.gov.co`]
Bogotá D.C.

**Asunto:** Solicitud de vinculación a los Servicios Ciudadanos Digitales — Interoperabilidad (X-Road/PDI), Autenticación Digital y Carpeta Ciudadana Digital.

---

Respetados señores:

La **Alcaldía Distrital de Santa Marta**, identificada con NIT **891.780.009-4**, en el marco de la implementación de su **Sede Electrónica** conforme a la política de Gobierno Digital, se permite **solicitar formalmente la vinculación** a los **Servicios Ciudadanos Digitales base** prestados por la Agencia Nacional Digital, a saber:

1. **Servicio de Interoperabilidad (X-Road / PDI):** para el intercambio seguro de información con otras entidades del Estado en los trámites del Distrito.
2. **Servicio de Autenticación Digital:** para la autenticación de los ciudadanos en la sede mediante el esquema oficial (OpenID Connect).
3. **Servicio de Carpeta Ciudadana Digital:** para poner a disposición de los ciudadanos sus comunicaciones y documentos oficiales.

Esta solicitud se fundamenta en el **Decreto 2106 de 2019** (arts. 9, 10 y 15), el **Decreto 620 de 2020** (lineamientos de los Servicios Ciudadanos Digitales), la **Ley 2052 de 2020** y el **Decreto 088 de 2022** (digitalización y automatización de trámites), normas que asignan a la AND la prestación del servicio de interoperabilidad y a las entidades el deber de integrarse al Portal Único del Estado y a los SCD. La iniciativa está alineada con el **Plan Estratégico de Tecnologías de la Información (PETI) 2024-2027** del Distrito (actualizado 2026).

Para los efectos técnicos y de acompañamiento, informamos:

- **Contraparte técnica:** Dirección de las Tecnologías de la Información y las Comunicaciones (TIC) — Alcaldía Distrital de Santa Marta. Responsable: **[Nombre del Director de TIC]**. Correo: [______] · Tel.: 420 9600.
- **Infraestructura prevista** *(parámetros propuestos por la Alcaldía, a confirmar contra el manual técnico vigente de la AND):* servidor de seguridad (*security server*) X-Road sobre **Ubuntu LTS** en nube (**DigitalOcean**), con recursos del orden de **4 CPU / 16 GB RAM / almacenamiento dedicado** y conectividad gestionada con **Cloudflare** (DNS, WAF, anti-DDoS, TLS). Se ajustará a la versión de SO y a los requisitos de cómputo que indique la AND.
- **Esquema de firma y estampado:** firma electrónica **server-side con certificado institucional** y **sello de tiempo (TSA)** del proveedor **GSE** (Gestión de Seguridad Electrónica S.A.) configurado en el *security server* X-Road, conforme al manual de configuración TSA–AND (regla de salida a `tsa.gse.com.co`, puerto 443).
- **Gestión documental:** la sede operará sobre **Orfeo** (SGDEA open-source) para expedientes, radicación y firma.
- **Equipo de implementación:** personal del Área de Desarrollo de la Dirección TIC de la Alcaldía.
- **Estado:** sede en construcción (*greenfield*); no existe integración previa con X-Road/PDI, Carpeta Ciudadana ni Autenticación Digital. Se requiere el **cronograma de acompañamiento** de la AND y los **requisitos técnicos y documentales** para la vinculación.

Agradecemos indicar los pasos, formatos y requisitos para formalizar la vinculación, así como asignar el acompañamiento técnico correspondiente.

Cordialmente,

\
__________________________________
**Carlos Pinedo Cuello**
Alcalde Distrital de Santa Marta (2024-2027)

\
__________________________________
**[Nombre del Director de TIC]**
Director de las Tecnologías de la Información y las Comunicaciones (TIC)

*Anexos:* [acto de designación del responsable del proyecto, si aplica] · [PETI 2024-2027, si se adjunta].

---

## Notas para quien radica (no forma parte del oficio)

**Qué se completó en esta revisión (2026-06-05):**

- ✅ **Contraparte técnica identificada:** Dirección TIC (existe en el organigrama oficial) — Calle 14 No. 2-49, tel. 420 9600.
- ✅ **NIT** del Distrito: **891.780.009-4** (dígito de verificación confirmado).
- ✅ **Alcalde:** Carlos Pinedo Cuello (periodo 2024-2027).
- ✅ **PETI** agregado como fundamento/anexo (existe y está vigente: PETI 2024-2027 actualizado 2026).
- ✅ **Orfeo** referenciado como SGDEA.

**Qué falta antes de radicar (campos que solo la Alcaldía puede cerrar):**

- [ ] Nombre y correo institucional del **Director de TIC**.
- [ ] Correo **oficial y verificado** del funcionario de enlace en la AND/MinTIC (no enviar a un correo sin confirmar).
- [ ] Número de oficio y fecha.
- [ ] Confirmar con la AND las **specs técnicas del *security server*** (versión exacta de SO y cómputo) antes de comprometerlas formalmente.
- [ ] Radicar **también por la ventanilla/PQRSD oficial** de MinTIC/AND para obtener número de radicado formal (no solo correo).
- [ ] Tras radicar: registrar el **número de radicado** y pedir el **cronograma de acompañamiento** (la AND aplica una metodología de ~17 semanas) para insertarlo en el plan del proyecto.

> **Prioridad:** este oficio resuelve el prerrequisito P0 de toda la capa de interoperabilidad (X-Road, Autenticación Digital, CCD y "no exigir documentos"). Radicar cuanto antes; su demora bloquea el roadmap de trámites autenticados.
