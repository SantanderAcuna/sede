# 1. Marco normativo

> **Audiencia:** equipo de desarrollo + líder técnico de entidad pública colombiana.
> **Nivel:** riguroso. Toda afirmación legal lleva la cita literal del artículo del
> Decreto 2106/2019 (en adelante "DL 2106/2019"), indicando el título y, cuando
> aplica, parágrafo o inciso. Las referencias a páginas remiten al PDF fuente
> conservado en `/workspace/attachments/c5707bc7070d17a0/Decreto Ley 2106 de 2019.pdf`
> (65 páginas). El mismo decreto se respalda en `/workspace/attachments/378b80edaa17ebd3/`
> y `/workspace/attachments/59f0785f991964c1/`.

---

## 1.1 Qué es una Sede Electrónica (definición legal)

El término **"sede electrónica"** es la categoría central que el DL 2106/2019 adopta
como el **punto único y oficial de presencia digital de cada autoridad pública** en
Colombia. La definición legal —y por tanto el objeto del presente sistema— se
construye combinando cuatro enunciados del decreto:

### 1.1.1 Definición nuclear (Art. 14, inc. 1 y 2)

> *"Las autoridades deberán integrar a su sede electrónica todos los portales,
> sitios web, plataformas, ventanillas únicas, aplicaciones y soluciones
> existentes, que permitan la realización de trámites, procesos y procedimientos
> a los ciudadanos de manera eficaz.*
> *La titularidad, administración y gestión de la sede electrónica es
> responsabilidad de cada autoridad competente y estará dotada de las medidas
> jurídicas, organizativas y técnicas que garanticen **calidad, seguridad,
> disponibilidad, accesibilidad, neutralidad e interoperabilidad** de la
> información y de los servicios."*
> — **Decreto 2106/2019, art. 14** (p. 5 del PDF)

La frase anterior entrega la **definición operativa**: sede electrónica es la
*dirección electrónica oficial de cada autoridad*, sobre la cual esta última
ostenta **titularidad, administración y gestión exclusivas**, y que debe estar
*dotada* —no opcionalmente, sino como condición habilitante— de seis atributos
de calidad (los seis atributos que la Resolución MinTIC 2893/2020 desarrolla
después como "atributos de calidad de la sede electrónica").

### 1.1.2 Componentes que la sede debe absorber (Art. 14, inc. 1)

El primer inciso fija una **obligación de integración** (no de creación paralela):
todos los activos digitales previos de la autoridad deben **consolidarse** dentro
de la sede. Esto impacta directamente el diseño de nuestro sistema:

| Componente del art. 14 | Cómo se refleja en el sistema a construir |
|---|---|
| "portales" | Una sola URL canónica por autoridad. |
| "sitios web" | CMS unificado; sin micrositios huérfanos. |
| "plataformas" | APIs y back-office integrados al mismo dominio. |
| "ventanillas únicas" | Trámites y OPAs dentro de la sede, no en subdominios. |
| "aplicaciones" | Apps móviles federadas, no réplicas independientes. |
| "soluciones existentes" | Migración progresiva al stack de la sede. |

### 1.1.3 Canales oficiales de atención (Art. 14, inc. 3)

> *"Las autoridades deberán identificar en su sede electrónica los canales
> digitales oficiales de recepción de solicitudes, peticiones y de información.
> El Ministerio de Tecnologías de la Información y Comunicaciones regulará la
> materia."*
> — **Decreto 2106/2019, art. 14, inc. 3** (p. 6 del PDF)

Implicación: la sede debe **publicitar y centralizar** los canales digitales
oficiales (PQRS, chat, formulario de radicación, autenticación electrónica).
La regulación posterior MinTIC 2893/2020 desarrolla cómo.

### 1.1.4 Sede electrónica ≠ portal web, pero el portal debe SER la sede

El art. 14 **no exige crear un nuevo producto**: exige que el portal web
principal de cada autoridad **opere como** su sede electrónica. La Resolución
MinTIC 2893/2020 lo confirma: *"el portal web principal de cada autoridad
deberá constituirse como su sede electrónica"* (Anexo 2, "Guía técnica de
integración de sedes electrónicas al portal único del Estado colombiano – GOV.CO",
versión 1, diciembre 2020, definiciones). Para nuestro proyecto esto significa
que el sistema a construir **es** la sede, no un módulo opcional sobre un
portal preexistente.

### 1.1.5 Resumen de la definición

| Elemento | Fuente normativa | Contenido |
|---|---|---|
| Titularidad | DL 2106/2019, art. 14 inc. 2 | Cada autoridad es titular y administradora. |
| Atributos obligatorios | DL 2106/2019, art. 14 inc. 2 | calidad, seguridad, disponibilidad, accesibilidad, neutralidad, interoperabilidad. |
| Alcance | DL 2106/2019, art. 14 inc. 1 | integra todos los portales, sitios web, plataformas, ventanillas únicas, aplicaciones y soluciones existentes. |
| Canales oficiales | DL 2106/2019, art. 14 inc. 3 | publicar los canales digitales oficiales de PQRS y de información. |
| Regulación técnica delegada | DL 2106/2019, art. 14 inc. 3 (remisión) y art. 15 par. 1 | MinTIC (Res. 1519/2020 y Res. 2893/2020). |

---

## 1.2 Artículos relevantes del Decreto 2106/2019

> **Nota de numeración:** los artículos se citan por su número literal dentro
> del DL 2106/2019 (65 páginas, firmado el 22-nov-2019, Bogotá D.C.). Las
> páginas indicadas a continuación son las del PDF fuente.

### 1.2.1 Art. 1° — Objeto (p. 2)

- **Título:** "Objeto".
- **Cita literal:** *"El presente decreto tiene por objeto simplificar, suprimir
  y reformar trámites, procesos y procedimientos innecesarios existentes en la
  Administración Pública, bajo los principios constitucionales y legales que
  rigen la función pública, con el propósito de garantizar la efectividad de
  los principios, derechos y deberes de las personas consagrados en la
  Constitución mediante **trámites, procesos y procedimientos administrativos
  sencillos, ágiles, coordinados, modernos y digitales**."* (énfasis añadido).
- **Qué exige:** la transformación digital del Estado colombiano no es una
  opción, es un mandato de simplificación ("procedimientos modernos y
  digitales").
- **Implicación técnica:** la sede electrónica es el medio por excelencia
  para materializar el carácter "digital" del procedimiento administrativo.
  Toda implementación que **no** reduzca pasos o que mantenga un canal
  presencial obligatorio sin justificación legal vulnera el espíritu del
  decreto.

### 1.2.2 Art. 2° — Ámbito de aplicación (p. 3)

- **Título:** "Ámbito de aplicación".
- **Cita literal:** *"El presente decreto se aplicará a todos los organismos,
  entidades y personas integrantes de la Administración Pública en los
  términos del artículo 39 de la Ley 489 de 1998, y a los particulares cuando
  cumplan funciones administrativas o públicas. A todos ellos se les dará el
  nombre de **autoridades**."*
- **Qué exige:** cobertura total: cualquier entidad pública (nacional,
  departamental, distrital, municipal) y los particulares con función
  administrativa quedan **obligados** por el decreto.
- **Implicación técnica:** la sede electrónica del sistema debe poder
  desplegarse para múltiples "autoridades" sin reescritura. Esto justifica
  arquitecturas multi-tenant o instanciables por entidad.

### 1.2.3 Art. 8° — Obligación de uso de los canales digitales entre autoridades (p. 4)

- **Título:** "Obligación de uso de los canales digitales entre autoridades".
- **Cita literal:** *"Cuando las entidades públicas habiliten canales digitales
  para el cumplimiento de sus competencias deberán atender por dichos medios.
  **Únicamente** se utilizarán otros medios cuando la ley así lo establezca."*
- **Qué exige:** si existe canal digital habilitado, este es **preferente**;
  los canales no digitales son la excepción y deben tener base legal explícita.
- **Implicación técnica:** la sede debe garantizar que **todo** el recorrido
  del trámite (PQRS, notificaciones, pagos, radicación, seguimiento) tenga
  contraparte digital. Lo que solo se ofrezca presencialmente debe estar
  legalmente justificado y documentado en el SUIT.

### 1.2.4 Art. 9° — Servicios Ciudadanos Digitales (p. 4)

- **Título:** "Servicios Ciudadanos Digitales".
- **Cita literal:** *"Para lograr mayor nivel de eficiencia en la
  administración pública y una adecuada interacción con los ciudadanos y
  usuarios, garantizando el derecho a la utilización de medios electrónicos,
  las autoridades deberán **integrarse y hacer uso del modelo de Servicios
  Ciudadanos Digitales**. El Gobierno Nacional prestará gratuitamente los
  Servicios Ciudadanos Digitales base y se implementarán por parte de las
  autoridades de conformidad con los lineamientos que establezca el Ministerio
  de Tecnologías de la Información y las Comunicaciones."*
- **Qué exige:** integración con el modelo SCD (autenticación digital,
  carpeta ciudadana, interoperabilidad). El Gobierno presta los SCD base
  **gratis**; las autoridades deben integrarse.
- **Implicación técnica:** el sistema debe incluir módulos de **autenticación
  electrónica** (hoy: autenticación digital de MinTIC), **carpeta ciudadana**
  para mostrar al ciudadano sus datos con el Estado, e **interoperabilidad**
  mediante los servicios base (intercambio de datos, autenticación, firma).
  Ver Cap. 6 (interoperabilidad) y Cap. 5 (autenticación).

### 1.2.5 Art. 10 — Interoperabilidad de la información (p. 4–5)

- **Título:** "Interoperabilidad de la información de las autoridades
  integradas a los Servicios Ciudadanos Digitales".
- **Citas literales (incisos 1–4):**
  - Inc. 1: *"Las autoridades deberán vincular a los mecanismos que disponga
    la Agencia Nacional Digital, los instrumentos, mecanismos, plataformas,
    aplicaciones, entre otros, que contribuyan a masificar las capacidades
    del Estado en la prestación de Servicios Ciudadanos Digitales."*
  - Inc. 2: *"El servicio ciudadano digital de interoperabilidad será prestado
    por la Agencia Nacional Digital."*
  - Inc. 3: *"El uso y reutilización de la información que repose en datos o
    sistemas de información que se encuentren integrados en el servicio
    ciudadano digital de interoperabilidad, se efectuará bajo los principios y
    reglas de protección de datos personales señaladas, entre otras, en las
    **Leyes 1581 de 2012 y 1712 de 2014**, y conforme a los protocolos de
    clasificación, reserva y protección de datos, que las entidades para su
    uso. Para tal efecto no se requerirá la suscripción de acuerdos, convenios
    o contratos interadministrativos."*
  - Inc. 4: *"Las autoridades no exigirán a los ciudadanos los requisitos o
    documentos que reposen en otras entidades o sistemas de información que
    se encuentren integrados en el servicio ciudadano digital de
    interoperabilidad."*
- **Qué exige:** las autoridades **no pueden pedir al ciudadano documentos
  que ya repose en otra entidad integrada al servicio de interoperabilidad**.
  La integración es **oficiosa**: el cruce lo hace el Estado, no el ciudadano.
- **Parágrafos relevantes:**
  - Par. 1° (p. 5): *"Cuando se requieran documentos reconocidos ante cónsul
    o expedidos por un cónsul de Colombia, las autoridades deberán consultar
    los sistemas de información o bases de datos dispuestos por el Ministerio
    de Relaciones Exteriores para tal fin. En consecuencia, no se podrán exigir
    los referidos documentos originales para efectos de adelantar trámites o
    procedimientos."*
  - Par. 2° (p. 5): *"Cuando las autoridades se encuentren integradas y
    haciendo uso de los Servicios Ciudadanos Digitales, los requisitos que
    puedan ser verificados a través del servicio ciudadano digital de
    interoperabilidad deberán ser actualizados en el SUIT."*
  - Par. 3° (p. 5): *"Hasta tanto las autoridades encargadas de llevar
    registros públicos se integren al servicio ciudadano digital de
    interoperabilidad, deberán habilitar su consulta gratuita y en línea a
    todas las demás autoridades… En este caso, el ciudadano o usuario estará
    eximido de aportar el certificado o documento físico requerido y servirá
    de prueba bajo la anotación del servidor público que efectúe la consulta."*
- **Implicación técnica:**
  1. El sistema debe consumir el servicio de interoperabilidad de la Agencia
     Nacional Digital para verificar datos que ya obran en otras entidades
     (RUES, Registraduría, RUNT, Catastro, etc.).
  2. El sistema debe permitir al funcionario **anotar la verificación**
     cuando el dato aún no esté en interoperabilidad plena (par. 3°), lo que
     obliga a registrar trazabilidad de la consulta (fecha, hora, funcionario,
     fuente).
  3. No se debe pedir al usuario ningún documento físico que el sistema pueda
     resolver oficiosamente.

### 1.2.6 Art. 13 — Acceso a la identificación de los colombianos (p. 5)

- **Título:** "Acceso a la identificación de los colombianos por parte de
  entidades públicas".
- **Cita literal:** *"La Registraduría Nacional del Estado Civil deberá
  permitir a las entidades públicas el acceso a los mecanismos de identificación
  de los colombianos de manera gratuita."*
- **Qué exige:** la Registraduría provee identificación gratuita a entidades
  públicas (cédula, biometría, etc.).
- **Implicación técnica:** la sede debe poder autenticar al ciudadano con la
  cédula digital o biometría provista por la Registraduría, sin coste de
  licenciamiento para la entidad.

### 1.2.7 Art. 14 — Integración a la sede electrónica (p. 5–6) ← **DEFINICIÓN LEGAL**

- **Título:** "Integración a la sede electrónica".
- **Citas literales (tres incisos, ya transcritos parcialmente en §1.1):**
  - Inc. 1: integración obligatoria de todos los portales, sitios web,
    plataformas, ventanillas únicas, aplicaciones y soluciones existentes.
  - Inc. 2: titularidad, administración y gestión **a cargo de cada autoridad
    competente**; la sede debe garantizar los **seis atributos de calidad**:
    *"calidad, seguridad, disponibilidad, accesibilidad, neutralidad e
    interoperabilidad de la información y de los servicios"*.
  - Inc. 3: identificación de canales digitales oficiales; regulación
    delegada a MinTIC.
- **Qué exige (consolidado):**
  1. Una sola sede electrónica por autoridad (no múltiples).
  2. La sede absorbe **todos** los activos digitales previos (efecto
     consolidante).
  3. La autoridad es titular y administradora exclusiva (responsabilidad
     indelegable).
  4. Los seis atributos de calidad son condición habilitante.
  5. La sede debe hacer visibles los canales oficiales de atención.
- **Implicación técnica (los seis atributos, traducidos a requisitos
  verificables):**

  | Atributo (art. 14) | Requisito técnico verificable |
  |---|---|
  | Calidad | Versionado del contenido, metadatos, control de cambios, métricas de calidad de datos. |
  | Seguridad | HTTPS obligatorio (certificados válidos), cabeceras CSP/HSTS/XFO, autenticación robusta (MinTIC), auditoría, gestión de vulnerabilidades, modelo MSPI. |
  | Disponibilidad | SLA ≥ 99 % en horario hábil; plan de contingencia, DRP/BCP; monitoreo 7×24×365. |
  | Accesibilidad | WCAG 2.1 nivel AA (Res. 1519/2020, Anexo 1). |
  | Neutralidad | Sin sesgo hacia navegadores/dispositivos; diseño responsive; soporte multiplataforma. |
  | Interoperabilidad | API REST documentada (OpenAPI); consumo del SCD de interoperabilidad; publicación de datos en datos.gov.co. |

### 1.2.8 Art. 15 — Portal Único del Estado Colombiano (p. 6)

- **Título:** "Portal Único del Estado Colombiano".
- **Citas literales:**
  - Inc. 1: *"El Portal Único del Estado Colombiano es la sede electrónica
    compartida a través de la cual los ciudadanos accederán a la información,
    procedimientos, servicios y trámites que se deban adelantar ante las
    autoridades."*
  - Inc. 2: *"El Ministerio de Tecnologías de la Información y las
    Comunicaciones administrará, gestionará la titularidad del Portal Único
    del Estado Colombiano y garantizará las condiciones de calidad,
    seguridad, disponibilidad, accesibilidad, neutralidad e interoperabilidad."*
  - Inc. 3: *"Las autoridades deberán **integrar su sede electrónica** al
    Portal Único del Estado Colombiano, en los términos que establezca el
    Ministerio de Tecnologías de la Información y las Comunicaciones, y serán
    responsables de la calidad, seguridad, disponibilidad, accesibilidad,
    neutralidad e interoperabilidad de la información, procedimientos,
    servicios y trámites ofrecidos por este medio."*
  - Par. 1°: *"Los programas transversales del Estado que cuenten con portales
    específicos deberán integrarse al Portal Único del Estado Colombiano.
    Dentro de los seis (6) meses siguientes a la entrada en vigencia del
    presente decreto, el Ministerio de Tecnologías de la Información y las
    Comunicaciones establecerá las condiciones de creación e integración de
    dichos portales."*
  - Par. 2°: *"Las ventanillas únicas existentes deberán integrarse al
    Portal Único del Estado Colombiano en un plazo no mayor a seis (6) meses
    contados a partir de la entrada en vigencia del presente decreto."*
- **Qué exige:**
  1. **Toda** sede electrónica de autoridad debe quedar **integrada** al
     Portal Único del Estado Colombiano (GOV.CO).
  2. El MinTIC es titular y administrador del Portal Único; las autoridades
     siguen siendo responsables del contenido publicado.
  3. Las ventanillas únicas preexistentes tienen **6 meses** desde la entrada
     en vigencia del decreto (es decir, antes del 22-may-2020) para integrarse.
  4. Los portales transversales deben integrarse en **6 meses** desde que
     MinTIC publique las condiciones técnicas.
- **Implicación técnica:** la sede debe implementar el **mecanismo de
  integración con GOV.CO** definido por MinTIC (hoy: redirección desde GOV.CO
  hacia la sede de la autoridad, previa verificación de cumplimiento de los
  lineamientos de la Guía Técnica, Res. 2893/2020). No se trata de un dominio
  distinto, sino de un redireccionamiento controlado.

### 1.2.9 Art. 16 — Gestión documental electrónica y preservación (p. 6)

- **Título:** "Gestión documental electrónica y preservación de la
  información".
- **Cita literal:** *"Las autoridades que realicen trámites, procesos y
  procedimientos por medios digitales deberán implementar sistemas de gestión
  documental electrónica y de archivo digital, asegurando la conformación de
  expedientes electrónicos con características de **integridad, disponibilidad
  y autenticidad** de la información. La emisión, recepción y gestión de
  comunicaciones oficiales, a través de los diversos canales electrónicos,
  deberá asegurar un adecuado tratamiento archivístico y estar debidamente
  alineado con la gestión documental electrónica y archivo digital."*
- **Parágrafo:** *"Las autoridades deberán disponer de modelos de seguridad
  digital siguiendo los lineamientos que emita el Ministerio de Tecnologías de
  la Información y las Comunicaciones."*
- **Qué exige:**
  1. La sede debe conformar **expedientes electrónicos** por cada trámite o
     procedimiento (no PDFs sueltos).
  2. Los expedientes deben tener integridad, disponibilidad y autenticidad.
  3. La gestión documental debe alinearse con el **Archivo General de la
     Nación (AGN)** en coordinación con MinTIC.
  4. Las comunicaciones oficiales (entrada y salida) requieren tratamiento
     archivístico electrónico.
  5. Modelo de seguridad digital obligatorio (alineado con MSPI de MinTIC).
- **Implicación técnica:**
  - Adoptar un SGDEA (Sistema de Gestión Documental Electrónica de Archivo)
    o un subsistema equivalente dentro de la sede.
  - Firmar y sellar tiempo los documentos críticos (firma digital + timestamp).
  - Mantener cadena de custodia auditable (logs inmutables).
  - Aplicar Tablas de Retención Documental (TRD) y Tablas de Valoración
    Documental (TVD) del AGN.

### 1.2.10 Art. 17 — Transacciones a través de medios electrónicos (p. 6)

- **Título:** "Transacciones a través de medios electrónicos".
- **Cita literal:** *"Las autoridades deberán habilitar medios de pago
  electrónicos en las transacciones que se realicen a favor del Estado o de la
  entidad, en relación con el pago de las tarifas asociadas a trámites,
  procesos y procedimientos."*
- **Qué exige:** todos los pagos por trámites deben poder hacerse por medios
  electrónicos (transferencias, pasarela de pagos, botón PSE, etc.).
- **Implicación técnica:** integrar pasarela(s) de pago electrónico con
  conciliación contra el sistema contable de la entidad. Sin componente de
  recaudo electrónico la sede **no cumple** el art. 17.

### 1.2.11 Art. 18 — Registro público de profesionales, ocupaciones y oficios (p. 7)

- **Título:** "Registro público de profesionales, ocupaciones y oficios".
- **Citas literales:**
  - Inc. 1: *"Las autoridades que cumplan la función de acreditar títulos de
    idoneidad para las profesiones, ocupaciones u oficios exigidos por la
    ley, constituirán un registro de datos centralizado, público y de
    consulta gratuita…"*
  - Inc. 2: *"La consulta de los registros públicos por parte de las
    autoridades que requieren la información para la gestión de un trámite,
    vinculación a un cargo público o para suscribir contratos con el Estado,
    exime a los ciudadanos de aportar la tarjeta profesional física o
    cualquier medio de acreditación."*
- **Implicación técnica:** la sede debe poder consumir registros públicos
  profesionales (hoy: Rethus, registros de consejos profesionales) para no
  exigir tarjetas profesionales físicas.

### 1.2.12 Art. 19 — Desmaterialización de certificados, constancias, paz y salvos o carnés (p. 7)

- **Título:** "Desmaterialización de certificados, constancias, paz y salvos
  o carnés".
- **Cita literal:** *"Las autoridades que en ejercicio de sus funciones emitan
  certificados, constancias, paz y salvos o carnés, respecto de cualquier
  situación de hecho o de derecho de un particular, deberán organizar dicha
  información como un registro público y habilitar su consulta gratuita en
  medios digitales."*
- **Qué exige:** los actos de certificación **deben** ser consultables
  electrónicamente y de forma gratuita. La sede no debe requerir al ciudadano
  portar el certificado físico; cualquier autoridad debe poder verificar el
  documento en línea.
- **Implicación técnica:** la sede debe ofrecer un módulo de consulta pública
  de certificados emitidos, con respuesta verificable (QR, código seguro,
  firma digital del documento).

### 1.2.13 Art. 147 (sic, Cap. XIII) — Trámites electrónicos de las Cámaras de Comercio (p. 56)

- **Título:** "Trámites electrónicos de las Cámaras de Comercio a través de
  la Ventanilla Única Empresarial".
- **Cita literal:** *"Las Cámaras de Comercio deberán garantizar que los
  trámites, procesos, procedimientos y/o servicios asociados a la actividad
  empresarial se integren, incorporen y/o interoperen con la Ventanilla Única
  Empresarial - VUE, la cual **deberá integrarse a la sede electrónica del
  Ministerio de Comercio, Industria y Turismo**."*
- **Relevancia para nuestro sistema:** confirma la **regla arquitectónica**
  de que la ventanilla única opera **dentro** de la sede electrónica del
  Ministerio cabeza (no en paralelo, no en subdominios independientes). Es
  un caso testigo para nuestro patrón multi-entidad.

---

## 1.3 Obligaciones derivadas para el constructor

De la lectura combinada de los arts. 8, 9, 10, 13, 14, 15, 16, 17, 18, 19 y
147 del DL 2106/2019, se derivan para el equipo de desarrollo las siguientes
**obligaciones técnicas inaplazables**:

1. **Una sola sede por autoridad (art. 14 inc. 1).** No desplegar múltiples
   portales para la misma entidad; consolidar.
2. **Titularidad y administración intransferibles (art. 14 inc. 2).** El
   sistema debe permitir que la entidad sea dueña operativa de su sede
   (gestión de contenido, configuración, sin dependencias técnicas externas
   que la conviertan en rehén).
3. **Atributos de calidad verificables (art. 14 inc. 2):**
   - **Calidad:** contenido versionado, métricas de actualización.
   - **Seguridad:** HTTPS, cabeceras CSP/HSTS, MFA, auditoría, MSPI.
   - **Disponibilidad:** ≥ 99 % hábil, plan de contingencia.
   - **Accesibilidad:** WCAG 2.1 AA (Res. 1519/2020, Anexo 1).
   - **Neutralidad:** responsive, soporte multi-navegador.
   - **Interoperabilidad:** OpenAPI, SCD de interoperabilidad.
4. **Integración con GOV.CO (art. 15 inc. 3).** Mecanismo de redirección
   desde GOV.CO verificado por MinTIC (Res. 2893/2020).
5. **No pedir al ciudadano lo que repose en otra entidad (art. 10 inc. 4).**
   Cruce oficioso de datos vía SCD de interoperabilidad.
6. **Expedientes electrónicos con integridad, disponibilidad y autenticidad
   (art. 16 inc. 1).** SGDEA + firma digital + timestamp.
7. **Pagos electrónicos (art. 17).** Pasarela + conciliación.
8. **Canales oficiales identificables (art. 14 inc. 3).** Publicitar PQRS,
   agendamiento de citas, horarios y canales.
9. **Modelo de seguridad digital (art. 16 par.).** Alineado con MSPI.
10. **Desmaterialización de certificados (art. 19).** Consulta pública
    gratuita y verificación por QR o código seguro.

---

## 1.4 Tabla resumen: obligación → requisito → cómo se implementa

| # | Obligación (fuente legal) | Requisito de sistema | Cómo se implementa en este proyecto |
|---|---|---|---|
| O-01 | Integrar todos los activos digitales previos (art. 14 inc. 1) | Un único dominio canónico por autoridad | Reverse-proxy / router único (`sede.<entidad>.gov.co`); redirección de subdominios legacy hacia el mismo origen. |
| O-02 | Garantizar los seis atributos de calidad (art. 14 inc. 2) | Módulos de calidad, seguridad, disponibilidad, accesibilidad, neutralidad, interoperabilidad | Ver §1.2.7 tabla de atributos; checklist de aceptación por atributo en cada release. |
| O-03 | Identificar canales digitales oficiales (art. 14 inc. 3) | Sección "Atención al ciudadano" visible | Bloque fijo en homepage con PQRS, agendamiento de citas, horarios y canales verificados. |
| O-04 | Integrar SCD (art. 9 y art. 10) | Módulo de autenticación digital, módulo de carpeta ciudadana, módulo de interoperabilidad | OAuth2/OIDC contra MinTIC; API cliente del servicio de interoperabilidad (AND); carpeta ciudadana consultable. |
| O-05 | No exigir documentos que reposen en otra entidad (art. 10 inc. 4) | Cruce oficioso en formularios | Pre-relleno automático desde SCD; los formularios no piden lo que ya se puede verificar. |
| O-06 | Integrar a GOV.CO (art. 15 inc. 3) | Mecanismo de redirección verificado por MinTIC | Seguir "Guía técnica de integración" de la Res. 2893/2020 (Anexo 2). |
| O-07 | Expedientes electrónicos (art. 16 inc. 1) | SGDEA con integridad, disponibilidad y autenticidad | Subsistema documental: indexar cada documento con hash, firma digital y timestamp; almacenamiento inmutable. |
| O-08 | Pagos electrónicos (art. 17) | Pasarela de pagos integrada | Integración con PSE / botón de pagos y/o pasarela autorizada; conciliación diaria. |
| O-09 | Consultar Registraduría para identificación (art. 13) | Validación biométrica / cédula digital | Consumo del servicio de identificación de la Registraduría. |
| O-10 | Desmaterialización de certificados (art. 19) | Emisión con verificación pública | Generación de certificados con QR + URL de verificación en línea; firma digital del documento. |
| O-11 | Accesibilidad WCAG 2.1 AA (art. 14 inc. 2 → Res. 1519/2020 Anexo 1) | Auditorías de accesibilidad automatizadas y manuales | Pruebas automatizadas (axe-core / pa11y) en CI; auditoría manual semestral. |
| O-12 | Seguridad digital (art. 16 par. → MSPI) | Modelo MSPI aplicado | Diagnóstico, planificación, implementación, evaluación y mejora continua bajo MSPI. |
| O-13 | Cumplir protección de datos personales (art. 10 inc. 3 → Ley 1581/2012) | Módulo de privacidad y derechos ARCO | Registro de tratamientos, política de privacidad, canal de derechos ARCO. |
| O-14 | Interoperabilidad técnica (art. 14 inc. 2) | API REST documentada (OpenAPI 3.1) y catálogo de servicios | Contratos OpenAPI versionados; publicación en portal de desarrolladores de la entidad. |

---

## 1.5 Vacíos normativos y referencias complementarias

> **Criterio:** el DL 2106/2019 fija el **qué** (mandato legal). Para el
> **cómo** técnico, delega en MinTIC. Identificamos a continuación el marco
> complementario **estrictamente necesario** para construir la sede y los
> vacíos que este marco no cubre.

### 1.5.1 Marco complementario indispensable

| Norma | Emisor | Relación con DL 2106/2019 | Para qué la necesitamos |
|---|---|---|---|
| **Resolución 1519 de 2020** (24-ago-2020, DO 51.521 de 7-dic-2020) | MinTIC | Reglamenta arts. 14 y 15 (transversalmente); desarrolla Ley 1712/2014 | Anexo 1: WCAG 2.1 AA. Anexo 2: estándares de transparencia y divulgación. Anexo 3: seguridad digital (HTTPS, cabeceras, BCP/DRP). Anexo 4: datos abiertos. |
| **Resolución 2893 de 2020** (MinTIC) | MinTIC | *"Lineamientos para estandarizar las ventanillas únicas, los portales de programas transversales y unificación de sedes electrónicas del Estado colombiano"*; aplica arts. 14 y 15 del DL 2106/2019 | Anexo 1: Guía técnica de sede electrónica. Anexo 2: Guía de integración a GOV.CO. Define los seis atributos y los niveles de integración. |
| **Ley 2052 de 2020** (25-ago-2020, DO 51417) | Congreso | Racionalización de trámites, automatización, digitalización, trámites 100 % en línea | Marco de política. Arts. 5 (automatización), 6 (trámites en línea), 12 (carpeta ciudadana digital), 13 (estampilla electrónica). Plazo general: 12 meses desde promulgación. |
| **Decreto 088 de 2022** (24-ene-2022) | Adiciona Título 20 al Decreto 1078/2015 | Reglamenta arts. 3, 5 y 6 de la Ley 2052/2020 | Plazos específicos de digitalización por tipo de autoridad. Verificado: alcaldías avanzadas, gobernaciones, distritos → hasta mayo/2028 (77 meses). |
| **Decreto 1263 de 2022** | MinTIC | Adiciona Título 22 al Decreto 1078/2015, lineamientos y estándares de Transformación Digital Pública | Estándares para la arquitectura de los servicios ciudadanos digitales. |
| **Ley 1712 de 2014** | Congreso | Transparencia y acceso a la información pública | Determina qué información debe publicarse en la sección de Transparencia de la sede. |
| **Ley 1581 de 2012** | Congreso | Protección de datos personales | Habilita y limita el tratamiento de datos del ciudadano (cruce oficioso del art. 10 inc. 3 DL 2106/2019). |
| **Decreto 1078 de 2015** (compilatorio sector TIC) | Ministerio TIC | Concentra toda la regulación TIC vigente | Es la base reglamentaria a la cual se adicionan los Títulos 17, 20 y 22 (sedes electrónicas, trámites en línea, transformación digital). |

### 1.5.2 Vacíos normativos identificados (y cómo gestionarlos)

Los siguientes puntos **no están regulados al detalle por el DL 2106/2019 ni
por las resoluciones MinTIC vigentes** y deben resolverse con política interna
de la entidad y/o con el criterio del equipo de desarrollo. Documentamos cada
vacío para que la decisión quede explícita y justificada:

1. **Estándar de API para interoperabilidad.**
   El DL 2106/2019 no fija el formato (REST/SOAP/gRPC). La Resolución 2893/2020
   menciona la integración pero no prescribe OpenAPI. **Decisión de proyecto:**
   usar **OpenAPI 3.1 + JSON:API** (cuando aplique a recursos jerárquicos) y
   REST sobre HTTPS; documentar excepciones con justificación.
2. **Plataforma tecnológica concreta.**
   El decreto es agnóstico al stack. MinTIC tampoco fija framework ni lenguaje.
   **Decisión de proyecto:** elegir stack que cumpla los seis atributos (ej.
   Laravel 13 + Vue 3, FastAPI para microservicios de IA) y justificarlo en
   el SDD.
3. **Firma digital.**
   El decreto exige autenticidad e integridad (art. 16) pero no define el
   prestador de servicios de certificación. **Decisión de proyecto:** usar la
   entidad de certificación acreditada por ONAC que ofrezca integración con
   el SGDEA; evaluar coste.
4. **Cumplimiento de plazos según tipo de entidad.**
   El DL 2106/2019 fija plazos solo para "ventanillas únicas existentes" (6
   meses) y para "portales transversales" (6 meses desde regulación MinTIC).
   Los plazos detallados de **digitalización completa** de trámites están en
   el Decreto 088/2022 según tipo de autoridad. **Acción:** identificar el
   tipo de autoridad para nuestro caso y aplicar el cronograma correspondiente.
5. **Accesibilidad más allá de WCAG 2.1 AA.**
   La Res. 1519/2020 fija AA hasta ahora; si en el futuro MinTIC exige AAA o
   actualiza a WCAG 3.0, debe quedar en el radar. **Acción:** monitorcar
   publicaciones MinTIC y dejar el código preparado para subir nivel sin
   refactor mayor.
6. **Datos abiertos y catálogo de datos.**
   El art. 14 no obliga a publicar datos abiertos, pero la Res. 1519/2020
   (Anexo 4) sí. **Decisión de proyecto:** federar al portal datos.gov.co
   desde el inicio; planificar datasets.
7. **Atención diferencial y accesibilidad cognitiva.**
   El decreto menciona "personas con discapacidad" tangencialmente; la
   Resolución 1519/2020 se centra en WCAG. **Acción:** incluir lengua de
   señas, lectura fácil y ajustes razonables como política de la entidad.

### 1.5.3 Notas interpretativas (qué dice la doctrina técnica de MinTIC)

La **Resolución 2893/2020** (Anexo 2, "Guía técnica de integración de sedes
electrónicas al portal único del Estado colombiano – GOV.CO", versión 1,
diciembre 2020) recoge y amplía la definición del art. 14 DL 2106/2019 con
fines operativos:

> *"Sede electrónica: Es la dirección electrónica de titularidad,
> administración y gestión de cada autoridad competente, dotada de las
> medidas jurídicas, organizativas y técnicas que garanticen calidad,
> seguridad, disponibilidad, accesibilidad, neutralidad e interoperabilidad
> de la información y de los servicios. La sede electrónica permitirá las
> interacciones digitales existentes como trámites, servicios, ejercicios de
> participación, acceso a la información, colaboración y control social,
> entre otros."*
> — MinTIC, Res. 2893/2020, Anexo 2 (dic-2020), definiciones.

Esta definición operativa **no contradice** la del art. 14, sino que la
**explicita** y la **utiliza como referencia técnica** para evaluar la
integración al GOV.CO. La tomamos como la **especificación funcional
canónica** de la sede a construir.

---

## Referencias

### Fuentes primarias

1. **Decreto Ley 2106 de 2019** "Por el cual se dictan normas para simplificar,
   suprimir y reformar trámites, procesos y procedimientos innecesarios
   existentes en la administración pública", expedido el 22-nov-2019, Bogotá
   D.C. Archivo fuente:
   - `/workspace/attachments/c5707bc7070d17a0/Decreto Ley 2106 de 2019.pdf`
     (65 páginas, archivo principal).
   - Respaldo idéntico en `/workspace/attachments/378b80edaa17ebd3/` y
     `/workspace/attachments/59f0785f991964c1/` (mismo decreto, copias de
     respaldo).

   | Artículo citado | Páginas del PDF |
   |---|---|
   | Art. 1° (Objeto) | 2 |
   | Art. 2° (Ámbito de aplicación) | 3 |
   | Art. 8° (Canales digitales) | 4 |
   | Art. 9° (Servicios Ciudadanos Digitales) | 4 |
   | Art. 10 + parágrafos (Interoperabilidad) | 4–5 |
   | Art. 13 (Identificación) | 5 |
   | Art. 14 (Sede electrónica) | 5–6 |
   | Art. 15 + parágrafos (Portal Único / GOV.CO) | 6 |
   | Art. 16 + parágrafo (Gestión documental electrónica) | 6 |
   | Art. 17 (Transacciones electrónicas) | 6 |
   | Art. 18 (Registro público de profesionales) | 7 |
   | Art. 19 (Desmaterialización de certificados) | 7 |
   | Art. 147 (Cámaras de Comercio / VUE) | 56 |

### Fuentes complementarias (verificadas)

2. **Resolución MinTIC 1519 de 2020** (24-ago-2020; DO 51.521, 7-dic-2020):
   *"Por la cual se definen los estándares y directrices para publicar la
   información señalada en la Ley 1712 del 2014 y se definen los requisitos
   en materia de acceso a la información pública, accesibilidad web,
   seguridad digital, y datos abiertos."*
   - Anexo 1 → directrices WCAG 2.1 AA.
   - Anexo 2 → estándares de transparencia y divulgación.
   - Anexo 3 → condiciones mínimas técnicas y de seguridad digital (HTTPS,
     CSP, HSTS, XFO, BCP/DRP 7×24×365).
   - Anexo 4 → datos abiertos (federación a datos.gov.co).
   - URL verificación: `https://normograma.mintic.gov.co/mintic/compilacion/docs/resolucion_mintic_1519_2020.htm`

3. **Resolución MinTIC 2893 de 2020**:
   *"Por la cual se establecen los lineamientos para estandarizar las
   ventanillas únicas, los portales específicos de programas transversales y
   la unificación de la imagen de las sedes electrónicas del Estado
   colombiano."*
   - Anexo 1: Guía técnica de sede electrónica.
   - Anexo 2: Guía técnica de integración al Portal Único del Estado
     colombiano – GOV.CO (versión 1, dic-2020).
   - URL verificación: `https://normograma.mintic.gov.co/mintic/docs/resolucion_mintic_2893_2020.htm`

4. **Ley 2052 de 2020** (25-ago-2020; DO 51417): *"Por medio de la cual se
   establecen disposiciones transversales a la rama ejecutiva del nivel
   nacional y territorial y a los particulares que cumplan funciones públicas
   y/o administrativas, en relación con la racionalización de trámites y se
   dictan otras disposiciones."*
   - Arts. 5 (automatización), 6 (trámites en línea), 12 (carpeta
     ciudadana digital), 13 (estampilla electrónica).

5. **Decreto 088 de 2022** (24-ene-2022): adiciona Título 20 al Decreto
   1078/2015, reglamentando arts. 3, 5 y 6 de la Ley 2052/2020; fija plazos
   de digitalización por tipo de autoridad.

6. **Decreto 1263 de 2022**: adiciona Título 22 al Decreto 1078/2015 con los
   lineamientos y estándares de Transformación Digital Pública.

7. **Ley 1712 de 2014** — Transparencia y acceso a la información pública.

8. **Ley 1581 de 2012** — Protección de datos personales.

9. **Decreto 1078 de 2015** — Decreto Único Reglamentario del Sector TIC.

### Documentos internos relacionados

- **Plan del proyecto:** `/workspace/.mavis/plans/plan_d50b47a1/plan.yaml`
- **Board de progreso:** `/workspace/.mavis/plans/plan_d50b47a1/board.md`
