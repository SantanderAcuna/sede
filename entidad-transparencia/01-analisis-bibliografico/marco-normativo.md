# 01.1 — Marco Normativo Colombiano

> Insumos normativos vinculantes para la Sede Electrónica del Distrito de Santa Marta.
> Cada norma se cita por su nombre corto en el cuerpo y por referencia completa al final.

---

## 1. Ley 1712 de 2014 — Transparencia y Acceso a la Información Pública

### 1.1 Objeto y principios

La Ley 1712 de 2014 "[1]" regula el derecho de acceso a la información pública, los procedimientos para garantizarlo y las excepciones a la publicidad. Establece el principio de **publicación proactiva** (Art. 4, nral. d): la información pública debe divulgarse de forma oficiosa, sin que medie solicitud previa, en formatos abiertos y reutilizables.

### 1.2 Instrumentos de gestión de la información (Art. 4)

La ley impone cinco instrumentos obligatorios:

1. **Registro de Activos de Información.**
2. **Índice de Información Clasificada y Reservada.**
3. **Esquema de Publicación de Información.**
4. **Programa de Gestión Documental (PGD).**
5. **Tablas de Retención Documental (TRD).**

La sede electrónica es el **canal primario** de publicación proactiva, lo que la convierte en infraestructura crítica de cumplimiento.

### 1.3 Estructura obligatoria de transparencia (Art. 9)

El menú de Transparencia debe organizarse en al menos las siguientes **10 subsecciones**:

1. Información de la entidad.
2. Normativa.
3. Contratación.
4. Planeación, presupuesto e informes.
5. Trámites y servicios.
6. Participa.
7. Datos abiertos.
8. Grupos de interés.
9. Obligación de reporte específico.
10. Información tributaria territorial (predial, ICA).

> **Implicación para la BD:** la entidad `transparencia_publicacion` (supertipo) debe poder representar cada uno de los 10 subtipos con subtipos especializados, manteniendo integridad referencial del versionado.

### 1.4 Publicación inmediata y en tiempo real (Art. 11)

> "La información pública publicada deberá ser oportuna, objetiva, veraz, completa, reutilizable, en formatos accesibles y sin condicionamientos" — Ley 1712/2014, Art. 11.b.

- **Oportunidad:** actualización en ≤24 h hábiles para normativa (Art. 11.c).
- **Lenguaje claro:** "índice Fernández-Huerta ≥ 60" en descripciones de trámites (medición estándar DNP, citado en Res. 1519/2020 Anexo 2 [2]).

---

## 2. Resolución MinTIC 1519 de 2020

### 2.1 Objeto

La Resolución 1519 de 2020 [2] define los **estándares y directrices** que deben observar los sujetos obligados para publicar información en sus sitios web. Deroga la Resolución 3564 de 2015.

### 2.2 Estructura del Anexo Técnico 2

El Anexo 2 [2] se compone de seis numerales:

| Numeral | Materia | Implicación para el módulo |
|---|---|---|
| **§2.1** | Nomenclatura y domicilio | URL, palabra clave, logo GOV.CO |
| **§2.2** | Datos de contacto | +57 obligatorio, 018000 exento, redes sociales, horarios |
| **§2.3** | Menú del sitio | 4 ítems obligatorios (Inicio, Transparencia, Atención y Servicios, Participa); ≤7 ítems totales; ≤2 niveles |
| **§2.4** | Sección de transparencia | 10 subsecciones obligatorias |
| **§2.5** | Accesibilidad | WCAG 2.1 AA obligatorio |
| **§2.6** | Privacidad y seguridad | Ley 1581/2012, cifrado TLS 1.2+, doble pila IPv4/IPv6 |

### 2.3 Publicación de documentos (Res. 1519/2020, Anexo 2 §4)

Para cada documento se publica:

- **Tipo y número** de la norma / acto.
- **Fecha de expedición y publicación.**
- **Epígrafe / descripción.**
- **Vigencia.**
- **URL de descarga en formato abierto.**
- **Hash de integridad** (para verificar autenticidad).

---

## 3. Anexo Técnico 2 — Identidad visual GOV.CO

### 3.1 Articulación visual obligatoria

El **Anexo Técnico 2** [3] del Ministerio de TIC define la identidad visual de los sitios web de entidades públicas. Componentes obligatorios:

- **Top bar GOV.CO:** barra superior fija, color Cobalt `#0943B5`, altura 56 px, logo GOV.CO enlazado a `https://www.gov.co/home/`.
- **Footer GOV.CO:** datos de la autoridad (dirección, conmutador +57, línea gratuita, correo de notificaciones judiciales, redes sociales).
- **Marca País Colombia.**
- **Tipografía:** Nunito Sans (títulos) + Verdana (párrafos).
- **Botones y componentes:** Kit UI v9.2 (Bootstrap 5.0).
- **Botón de traducir** (idioma) en el top bar (cuando aplique).

### 3.2 Restricciones

- **Sin publicidad** ajena a las funciones de la entidad.
- **Footer institucional** puede usar color propio, pero el top bar es inamovible.
- **Bloque "Nuestras marcas":** el logo institucional aparece junto al logo GOV.CO en header.

---

## 4. Ley 1581 de 2012 — Protección de Datos Personales

### 4.1 Objeto

La Ley 1581 de 2012 [4] dicta disposiciones generales para la **protección de datos personales** en Colombia. Complementada por el Decreto 1377 de 2013 [5].

### 4.2 Principios (Art. 4)

- **Legalidad, finalidad, libertad, veracidad, calidad, transparencia, acceso restringido, circulación restringida, seguridad, confidencialidad.**
- **Consentimiento informado:** toda recolección de datos requiere consentimiento previo, expreso e informado del titular.

### 4.3 Derechos del titular (Art. 17)

- **Conocer, actualizar, rectificar** sus datos.
- **Solicitar prueba del consentimiento.**
- **Presentar quejas** ante la SIC.
- **Revocar el consentimiento** y/o solicitar la supresión.
- **Acceder gratuitamente** a sus datos.

### 4.4 Implicaciones para el directorio de servidores

El directorio institucional (Art. 9, Ley 1712/2014) publica **nombre, cargo, dependencia y correo institucional**. **NO** publica:

- Número de cédula.
- Teléfono celular personal.
- Datos de contacto personal (dirección residencial, correo personal).

> **RN-01:** "El directorio institucional publica únicamente el dato institucional del servidor, no sus datos personales." — Ley 1581/2012, Art. 6 [4].

---

## 5. Decreto 1081 de 2015 — Decreto Único Reglamentario del Sector Presidencia

El Decreto 1081 de 2015 [6] compila la normativa del sector Presidencia. En particular, reglamenta la **Ley 1712/2014** en sus Artículos 2.1.1.1 a 2.1.1.9.

- **Art. 2.1.1.2.1:** define "información pública".
- **Art. 2.1.1.2.2:** contenido del Registro de Activos de Información.
- **Art. 2.1.1.5:** publicidad de la información contractual.

---

## 6. Decreto 103 de 2015

El Decreto 103 de 2015 [7] reglamenta parcialmente la Ley 1712/2014, en lo relacionado con:

- **Art. 4:** criterios de la información clasificada y reservada.
- **Art. 5:** excepciones al derecho de acceso a la información.

---

## 7. Decreto 2106 de 2019 — Modernización digital

El Decreto 2106 de 2019 [8] es clave para el módulo 01 (Estructura-Identidad):

- **Art. 14:** las autoridades territoriales deben integrar a su sede electrónica **todos los portales, sitios web, plataformas, ventanillas únicas y aplicativos** existentes.
- **Art. 15:** la integración se realiza mediante **redireccionamiento con enmascaramiento de URL** a través del proxy del MinTIC. Estructura: `https://www.gov.co/[palabra_clave]` o `/tramites-y-servicios/T{id_SUIT}`.
- **Art. 39:** publicación del **calendario tributario** en la web.
- **Art. 156:** publicación del **informe de control interno** cada 6 meses.

### 7.1 Plazos de integración (Decreto 2106/2019, Art. 15)

- **Ventanillas únicas y portales de programas transversales:** ≤6 meses desde la vigencia del decreto (plazo vencido mayo 2020; estado actual: pendiente de auditoría).
- **Resto de portales:** gradualidad según Decreto 088/2022 (Alcaldía-Avanzado = plazos largos; ver Riesgos).

---

## 8. Decreto 088 de 2022 — Lineamientos de la Política de Gobierno Digital

El Decreto 088 de 2022 [9] define los **lineamientos de la Política de Gobierno Digital**. Establece tres niveles de madurez:

- **Básico, Intermedio, Avanzado.**

Santa Marta se cataloga preliminarmente como **Alcaldía-Avanzado** (Distrito capital), con los plazos más largos de la gradualidad:

- Bloque 1 (30% de trámites digitalizados): **may/2028**.
- Bloque 2 (60%): **mar/2034**.
- Bloque 3 (100%): **abr/2037**.

---

## 9. Decreto 1078 de 2015 — Decreto Único Reglamentario del Sector TIC

El Decreto 1078 de 2015 [10] reglamenta el sector TIC. **Art. 2.2.17.1.2:** define a los sujetos obligados de la Política de Gobierno Digital.

---

## 10. Estrategia de Gobierno en Línea (GEL) — Lineamientos

La Estrategia de Gobierno en Línea (GEL) es el marco histórico de la Política de Gobierno Digital. Los **lineamientos de la GEL** [11] establecen los tres (ahora cinco) propósitos del gobierno digital:

1. **Servicios digitales centrados en el usuario.**
2. **Procesos internos seguros y eficientes.**
3. **Decisiones basadas en datos.**
4. **Empoderamiento ciudadano** (cuarto propósito, posterior).
5. **Estado abierto** (quinto propósito, posterior).

---

## 11. Estándares técnicos vinculantes

### 11.1 IPv4 + IPv6 (doble pila)

Toda sede electrónica debe operar en **doble pila IPv4/IPv6** (Res. 1519/2020, Anexo 2 §2.6 [2]). Implicaciones para infraestructura y BD:

- Campo `INET` en PostgreSQL para direcciones IP.
- DNS con registros A y AAAA.

### 11.2 TLS 1.2+ y HTTPS obligatorio

Toda comunicación debe cifrarse con TLS 1.2 o superior (Res. 1519/2020, Anexo 2 §2.6 [2]).

### 11.3 Accesibilidad WCAG 2.1 AA

El estándar WCAG 2.1 nivel AA es **obligatorio** (Res. 1519/2020, Anexo 2 §2.5 [2]).

| Criterio | Umbral |
|---|---|
| Contraste de texto | ≥4.5:1 (texto normal); ≥3:1 (texto grande) |
| Texto alternativo en imágenes | Sí, obligatorio |
| Navegación por teclado | Completa |
| Foco visible | Sí, obligatorio |
| Tamaño de objetivo táctil | ≥44×44 px (móvil) |

---

## 12. Otras normas aplicables (resumen)

| Norma | Materia | Implicación |
|---|---|---|
| Ley 1437/2011 (CPACA) | Procedimiento administrativo | Plazos de respuesta PQRSD |
| Ley 1755/2015 | Derecho de petición | Plazos PQRSD: 15 días hábiles (general), 10 días (consulta) |
| Ley 1474/2011 | Estatuto Anticorrupción | Plan Anticorrupción y Atención al Ciudadano; mapa de riesgos |
| Ley 594/2000 | Ley General de Archivos | TRD y preservación documental |
| Ley 850/2003 | Veedurías ciudadanas | Módulo de participa |
| Acuerdo AGN 060/2001 | Tablas de retención | Numeración de radicados |
| Decreto 415 de 2016 | Modelo de Gestión | Articulación PETI |

---

## Referencias IEEE (marco normativo)

[1] Congreso de la República de Colombia, *Ley 1712 de 2014: Por medio de la cual se crea la Ley de Transparencia y del Derecho de Acceso a la Información Pública Nacional y se dictan otras disposiciones*, Bogotá, D.C., 6 mar. 2014. [En línea]. Disponible: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=56882

[2] Ministerio de Tecnologías de la Información y las Comunicaciones (MinTIC), *Resolución 1519 de 2020: Por la cual se definen los estándares y directrices para publicar la información pública en los sitios web de las entidades públicas*, Bogotá, D.C., 17 ago. 2020. [En línea]. Disponible: https://www.mintic.gov.co/gestion-ti/normatividad/2009/resolucion-1519-de-2020/

[3] Ministerio de Tecnologías de la Información y las Comunicaciones (MinTIC), *Anexo Técnico 2: Directrices de identidad visual y articulación con el Portal Único del Estado Colombiano (GOV.CO)*, Versión vigente al 2026. [En línea]. Disponible: https://www.gov.co/

[4] Congreso de la República de Colombia, *Ley 1581 de 2012: Por la cual se dictan disposiciones generales para la protección de datos personales*, Bogotá, D.C., 17 oct. 2012. [En línea]. Disponible: https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=49981

[5] Presidencia de la República de Colombia, *Decreto 1377 de 2013: Por el cual se reglamenta parcialmente la Ley 1581 de 2012*, Bogotá, D.C., 27 jun. 2013.

[6] Presidencia de la República de Colombia, *Decreto 1081 de 2015: Decreto Único Reglamentario del Sector Presidencia de la República*, Bogotá, D.C., 26 may. 2015.

[7] Presidencia de la República de Colombia, *Decreto 103 de 2015: Por el cual se reglamenta parcialmente la Ley 1712 de 2014*, Bogotá, D.C., 20 ene. 2015.

[8] Presidencia de la República de Colombia, *Decreto 2106 de 2019: Por el cual se dictan normas para simplificar, suprimir y reformar trámites, procesos y procedimientos innecesarios existentes en la administración pública*, Bogotá, D.C., 22 nov. 2019.

[9] Presidencia de la República de Colombia, *Decreto 088 de 2022: Por el cual se adiciona y modifica el Decreto 1078 de 2015, Decreto Único Reglamentario del Sector de Tecnologías de la Información y las Comunicaciones*, en lo relacionado con los lineamientos de la Política de Gobierno Digital, Bogotá, D.C., 24 ene. 2022.

[10] Presidencia de la República de Colombia, *Decreto 1078 de 2015: Decreto Único Reglamentario del Sector de Tecnologías de la Información y las Comunicaciones*, Bogotá, D.C., 26 may. 2015.

[11] Ministerio de Tecnologías de la Información y las Comunicaciones, *Manual de la Estrategia de Gobierno en Línea*, Bogotá, D.C., 2018. [En línea]. Disponible: https://gobiernodigital.mintic.gov.co/
