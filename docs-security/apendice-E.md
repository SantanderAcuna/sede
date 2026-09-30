# Apéndice E — Glosario

> Definiciones operativas, no enciclopédicas. Cuando un término tiene varios usos, aquí está el sentido en que se usa en esta guía.

**A+ (SSL Labs):** Calificación máxima en [Qualys SSL Labs](https://www.ssllabs.com/ssltest/). Indica TLS 1.2/1.3 sin vulnerabilidades, cifrados fuertes, OCSP stapling, forward secrecy.

**AEAD (Authenticated Encryption with Associated Data):** Modo de cifrado que autentica el mensaje además de cifrarlo. AES-GCM y ChaCha20-Poly1305 son AEAD.

**AIDE (Advanced Intrusion Detection Environment):** Herramienta que crea una base de datos hash de archivos críticos y detecta cambios. Es la línea base de integridad del sistema.

**ALPN (Application-Layer Protocol Negotiation):** Extensión TLS que permite al cliente y servidor negociar el protocolo de aplicación (h2, http/1.1) durante el handshake.

**API (Application Programming Interface):** Interfaz de programación. En esta guía, el Laravel 13 expone una API JSON:API.

**APO / Authenticated Origin Pulls:** Mecanismo mTLS de Cloudflare donde solo sus edges pueden conectar al origin (vuestro droplet) porque presentan un certificado cliente firmado por la CA de CF.

**APT (Advanced Package Tool):** Sistema de paquetes de Debian/Ubuntu.

**Argon2id:** Algoritmo de hash de contraseñas recomendado por OWASP. Laravel 13 lo soporta vía `Hash::driver('argon')`.

**ASCII (American Standard Code for Information Interchange):** Codificación de caracteres; obsoleto para sistemas modernos (usar UTF-8).

**Audit (auditd):** Subsistema del kernel Linux que registra llamadas al sistema (`syscall`). Permite rastrear accesos a archivos, cambios de configuración, ejecución de binarios.

**Auth:** Abreviación de "authentication" (autenticación).

**Authorization:** Autorización. Distinto de autenticación: es lo que el usuario puede hacer, no quién es.

**Aux:** Servicio de copia automática de DigitalOcean (snapshots semanales).

**Backend:** Lado servidor de una aplicación. Aquí, Laravel 13.

**Bcrypt:** Algoritmo de hash de contraseñas (cost 12 por defecto). Laravel lo usa por defecto.

**Bogon:** Dirección IP privada (RFC 1918) o reservada que aparece en Internet. Indicador de spoofing.

**Bot Fight Mode:** Funcionalidad Cloudflare que distingue bots buenos (Google, Bing) de bots maliciosos y los bloquea.

**BPM:** Beats Per Minute. Sin relevancia aquí. No confundir con Bits Per Minute.

**Brotli:** Compresión de HTTP alternativa a Gzip. Soportada por la mayoría de navegadores modernos. Mejor ratio de compresión para texto.

**CA (Certificate Authority):** Entidad que firma certificados digitales. Let's Encrypt es una CA pública gratuita.

**Cache:** Almacenamiento temporal para reducir latencia. En Laravel 13, Redis es el driver recomendado.

**CAPTCHA:** Sistema para distinguir humanos de bots.

**CASB (Cloud Access Security Broker):** Producto intermedio entre usuarios y SaaS para monitorizar y aplicar políticas. No usado directamente aquí, pero Cloudflare Zero Trust cumple un rol similar.

**CCPA (California Consumer Privacy Act):** Ley de privacidad de California. Aplica si tu servicio recoge datos de residentes californianos.

**CDN (Content Delivery Network):** Red de distribución de contenido. Cloudflare es CDN + WAF + DNS + DDoS protection.

**Certbot:** Cliente de Let's Encrypt mantenido por la EFF. Automatiza emisión y renovación.

**CFR (Code of Federal Regulations):** Normativa federal de EE.UU. No aplica directamente a GDPR, pero relevante para sectores regulados en USA.

**CI/CD (Continuous Integration / Continuous Deployment):** Automatización de build, test y deploy. Aquí, GitHub Actions.

**CIS Benchmarks:** Guías de hardening mantenidas por Center for Internet Security. Para Ubuntu 24.04: ~250 reglas.

**Cipher Suite:** Combinación de algoritmos para TLS: key exchange + authentication + encryption + MAC. Cada TLS handshake negocia uno.

**Clickjacking:** Ataque que engaña al usuario para hacer click en algo distinto de lo que cree. Mitigado por `X-Frame-Options: DENY` o CSP `frame-ancestors 'none'`.

**Cloud-init:** Sistema de inicialización de instancias cloud (DO, AWS, etc.). Permite inyectar configuración al primer boot.

**CN (Common Name):** Campo legacy de certificados. Reemplazado por SAN (Subject Alternative Name) pero todavía presente.

**Cookie:** Pequeño archivo enviado al navegador. En Laravel, configurable vía `SESSION_*`.

**CORS (Cross-Origin Resource Sharing):** Mecanismo del navegador para permitir (o denegar) requests entre orígenes distintos. Laravel 13 maneja vía `config/cors.php`.

**CORS Allowed Origins:** Lista explícita de orígenes que pueden hacer fetch a tu API. `*` es inseguro en producción.

**COS (Class of Service):** Campo del header Ethernet para QoS. No usado directamente aquí.

**CORS Preflight:** Request OPTIONS que el navegador envía antes de ciertos métodos (POST no-simple) para verificar permisos CORS.

**CP (Certificate Pinning):** Pinning de certificado. Deprecated en navegadores modernos (HPKP).

**CPU (Central Processing Unit):** Procesador. Recurso a monitorear.

**CRL (Certificate Revocation List):** Lista de certificados revocados. Reemplazada por OCSP en la práctica.

**Crontab:** Tabla de tareas programadas. Cada usuario puede tener la suya; `/etc/cron.d/` es global.

**Crontab format:** `min hour day month weekday command`. Ej: `0 3 * * *` = diario a las 3:00.

**CRS (Core Rule Set):** Conjunto de reglas de WAF mantenido por OWASP. Para ModSecurity.

**CSP (Content-Security-Policy):** Header HTTP que indica al navegador de dónde puede cargar recursos. Defensa profunda contra XSS.

**CVE (Common Vulnerabilities and Exposures):** Identificador único de vulnerabilidad. Ej: CVE-2024-1234.

**CWE (Common Weakness Enumeration):** Taxonomía de debilidades. Diferente de CVE (que es instancia).

**DDoS (Distributed Denial of Service):** Ataque que inunda el servicio desde múltiples fuentes. Mitigado por Cloudflare + UFW + nftables + Fail2Ban.

**Dependabot:** Servicio de GitHub que abre PRs automáticas para actualizar dependencias vulnerables.

**DevOps:** Cultura y prácticas que unen desarrollo y operaciones. En esta guía: DevOps = Git + CI/CD + monitoring + runbook.

**DH (Diffie-Hellman):** Algoritmo de intercambio de claves. En TLS, `DHE` (ephímero) y `ECDHE` (curva elíptica, ephímero) ofrecen forward secrecy.

**DMARC (Domain-based Message Authentication, Reporting & Conformance):** Política de autenticación de email. No directamente relevante aquí, pero si gestionas email transaccional, conviene.

**DNI (Documento Nacional de Identidad):** No relevante aquí. No confundir con DNT (Do Not Track).

**DNS (Domain Name System):** Resolución de nombres a IPs. En esta guía, gestionado por Cloudflare.

**Docker:** Plataforma de contenedores. En esta guía, opcional. Si se usa, base DHI (Docker Hardened Images).

**Dockerfile:** Archivo con instrucciones para construir una imagen Docker.

**DoS (Denial of Service):** Ataque que inunda el servicio desde una sola fuente. Más simple que DDoS.

**DTLS (Datagram Transport Layer Security):** TLS sobre UDP. Usado por WebRTC, no directamente aquí.

**ECDH (Elliptic Curve Diffie-Hellman):** Variante de DH sobre curva elíptica. Más rápido y con claves más cortas que RSA.

**ECDSA (Elliptic Curve Digital Signature Algorithm):** Firma digital sobre curva elíptica.

**Ed25519:** Esquema de firma digital sobre curva elíptica Curve25519. Recomendado para SSH.

**EFS (Elastic File System):** Servicio AWS. No usado aquí.

**Eloquent:** ORM de Laravel. Convención: nombres de modelos en singular, tablas en plural.

**Endurecer (hardening):** Aplicar configuración para reducir superficie de ataque.

**Ephemeral port:** Puerto TCP aleatorio usado por clientes. Rango típico: 32768-60999.

**ETag:** Header HTTP para cache. Identificador de versión de un recurso.

**Etc/passwd, /etc/shadow:** Archivos de cuentas Unix. `/etc/passwd` es legible por todos (info pública), `/etc/shadow` solo por root (hashes de contraseñas).

**Exploit:** Código que aprovecha una vulnerabilidad.

**Fail2Ban:** Herramienta que escanea logs y aplica bans (iptables/nftables) tras N intentos fallidos.

**FastCGI:** Protocolo entre nginx y PHP-FPM.

**FIM (File Integrity Monitoring):** Monitoreo de integridad de archivos. Wazuh FIM, AIDE, Tripwire.

**FIPS (Federal Information Processing Standards):** Estándares del NIST para sistemas federales USA. FIPS 140-2/3 es el módulo criptográfico.

**Firewall:** Sistema que filtra tráfico según reglas. UFW (capa local), nftables (kernel), DO Cloud Firewall (borde).

**Forward secrecy:** Propiedad por la que el compromiso de la clave privada del servidor no descifra sesiones pasadas. Requiere DHE/ECDHE.

**FQDN (Fully Qualified Domain Name):** Nombre completo, ej: `api.dominio.tld`.

**Frontend:** Lado cliente de una aplicación. Aquí, Vue 3.

**GDPR (General Data Protection Regulation):** Reglamento UE 2016/679 de protección de datos. Aplica si procesas datos de residentes UE.

**Git:** Sistema de control de versiones distribuido. Usado aquí para deploy vía SSH.

**GitHub Actions:** Servicio CI/CD de GitHub. Ejecuta workflows definidos en `.github/workflows/`.

**GPG (GNU Privacy Guard):** Herramienta de cifrado y firma. Usada aquí para cifrar backups.

**GZip:** Compresión HTTP. Activada por nginx en `gzip on;`.

**HA (High Availability):** Arquitectura que mantiene servicio activo pese a fallos de componentes individuales. Esta guía no cubre HA completo (escapa del scope).

**Hardcoded:** Valor escrito directamente en código, no configurable. "API_KEY=xxx" en `.env.example` es OK; hardcoded en `auth.php` es no.

**Header:** Cabecera HTTP. Metadatos enviados con cada request/response.

**HSTS (HTTP Strict Transport Security):** Header que indica al navegador "este sitio debe ser HTTPS siempre, no aceptar HTTP".

**HTML (HyperText Markup Language):** Lenguaje de marcado. Renderizado por el navegador.

**HTTP/2:** Versión mayor de HTTP (RFC 7540). Multiplexado, server push (deprecated), header compression.

**HTTP/3:** HTTP sobre QUIC (RFC 9114). Multiplexado sobre UDP.

**HTTPS:** HTTP sobre TLS. Obligatorio en esta guía.

**Hypervisor:** Software que ejecuta máquinas virtuales. KVM en DigitalOcean.

**IANA (Internet Assigned Numbers Authority):** Registra números de protocolo, puertos, MIME types.

**IDP (Identity Provider):** Servicio que gestiona identidades (login). SAML, OIDC. Cloudflare Zero Trust puede actuar como IDP.

**IDS (Intrusion Detection System):** Sistema que detecta intrusiones sin actuar. Wazuh agent.

**IPS (Intrusion Prevention System):** Sistema que detecta y bloquea. Suricata, OSSEC en modo active response.

**Ingress controller:** Componente Kubernetes que enruta tráfico externo. No usado directamente aquí.

**Instance:** En cloud, una VM. Sinónimo de Droplet en DO.

**IP (Internet Protocol):** Capa de red.

**IPv4:** Direcciones de 32 bits. ~4.3 mil millones.

**IPv6:** Direcciones de 128 bits. ~340 undecillones. Soportado pero no usado directamente aquí.

**IPSec:** Conjunto de protocolos para VPN. No usado directamente aquí.

**ISO 27001:** Estándar de gestión de seguridad de la información.

**JSON (JavaScript Object Notation):** Formato de datos. Laravel API usa `application/json` por defecto; JSON:API usa `application/vnd.api+json`.

**JSON:API:** Especificación para APIs (jsonapi.org). Laravel 13 tiene soporte nativo.

**JWT (JSON Web Token):** Token compacto y autocontenido. Usado para auth, aunque Laravel Sanctum usa tokens opacos.

**KDF (Key Derivation Function):** Función para derivar claves. Argon2id, bcrypt, scrypt, PBKDF2.

**Kerberos:** Protocolo de autenticación por tickets. No usado directamente aquí.

**KexAlgorithm (Key Exchange Algorithm):** Algoritmo para negociar claves en SSH/TLS.

**Laravel:** Framework PHP para aplicaciones web. Versión objetivo: 13.

**LDAP (Lightweight Directory Access Protocol):** Protocolo para directorio. No usado directamente aquí.

**LFI (Local File Inclusion):** Vulnerabilidad que permite leer archivos locales. Mitigada en Laravel por el `try_files` en nginx.

**LTS (Long Term Support):** Versión soportada por más tiempo. Ubuntu 24.04 LTS hasta 2029.

**mTLS (Mutual TLS):** TLS donde ambos lados presentan certificado. Usado en AOP (Cloudflare).

**MAC (Message Authentication Code):** HMAC-SHA256, etc. En TLS, parte del cipher suite.

**Mailgun, SendGrid, Postmark:** Servicios de email transaccional. Mencionados en runbook si necesitas rotación de alertas.

**Man-in-the-middle:** Atacante que se interpone entre dos partes. Mitigado por TLS + verificación de certificado.

**MariaDB Audit Plugin:** Plugin de auditoría open source compatible con MySQL.

**MD5:** Hash criptográfico deprecado. NUNCA usar para seguridad.

**MFA (Multi-Factor Authentication):** Autenticación multifactor. TOTP (RFC 6238) es la opción común.

**ModSecurity:** WAF open source. CRS (Core Rule Set) de OWASP.

**MTTR (Mean Time To Recover):** Tiempo medio de recuperación. Métrica de operación.

**MTTD (Mean Time To Detect):** Tiempo medio de detección. Métrica de monitoring.

**Mux:** Multiplexor. Sin relevancia aquí.

**MySQL:** RDBMS. Versión objetivo: 8.0 LTS.

**Nginx:** Servidor web + reverse proxy. Versión objetivo: 1.29 mainline.

**NIST (National Institute of Standards and Technology):** Agencia de estándares USA. Mantiene SP 800 series (seguridad).

**Nmap:** Escáner de puertos. Usar `nmap -p 1-65535` desde fuera para confirmar cero exposición.

**NoSQL:** Bases no relacionales. Redis cae aquí.

**nftables:** Sucesor moderno de iptables en Linux. Sintaxis `inet filter`.

**OCSP (Online Certificate Status Protocol):** Protocolo para verificar revocación de cert en tiempo real. Stapling = el servidor envía la respuesta cacheada.

**OCSP Stapling:** El servidor obtiene una respuesta OCSP firmada por la CA y la envía en cada TLS handshake. Evita que el cliente tenga que contactar la CA.

**OIDC (OpenID Connect):** Capa de identidad sobre OAuth 2.0. GitHub Actions → DO via OIDC es la práctica recomendada.

**OpenSSH:** Implementación open source del protocolo SSH.

**OSSEC:** Host-based IDS. Wazuh es su fork mantenido.

**OWASP (Open Web Application Security Project):** Fundación que mantiene guías de seguridad.

**PAM (Pluggable Authentication Modules):** Sistema modular de autenticación en Linux. Google Authenticator se enchufa como módulo PAM.

**Passphrase:** Contraseña larga, opcional en SSH keys. Si la pierdes, pierdes la clave (a menos que esté en ssh-agent).

**PCI DSS:** Estándar de seguridad para pagos con tarjeta. No aplica si no manejas tarjetas directamente.

**PHP-FPM (PHP FastCGI Process Manager):** Gestor de procesos para PHP. Usado por nginx vía FastCGI.

**PostgreSQL:** RDBMS. Versión objetivo: 16.

**Postmortem:** Análisis posterior a un incidente. Blameless = sin señalar culpables, aprender del sistema.

**PPA (Personal Package Archive):** Repositorio de paquetes de Ubuntu mantenido por terceros.

**Proxy / Reverse Proxy:** Intermediario. nginx es reverse proxy (cliente → nginx → backend).

**PHP:** Lenguaje de programación. Versión objetivo: 8.3+.

**PHPUnit:** Framework de testing PHP. Laravel 13 usa por defecto.

**PHP-FPM pool:** Configuración de un proceso PHP-FPM. Cada pool es un proceso separado con su propio usuario.

**PID:** Process ID. Identificador de proceso.

**PII (Personally Identifiable Information):** Datos personales. Requieren protección GDPR.

**Pipeline:** Conjunto de stages automatizados (CI/CD).

**Plain text:** Texto sin cifrar. NUNCA para credenciales.

**PQC (Post-Quantum Cryptography):** Algoritmos resistentes a computación cuántica. Aún en estándar (NIST PQC 2024). Cloudflare ofrece TLS PQC experimental.

**Proxy_protocol:** Protocolo para preservar IP del cliente al pasar por proxies. Usado por HAProxy, nginx stream.

**Public key cryptography:** Criptografía asimétrica. Par clave pública/privada.

**QA (Quality Assurance):** Aseguramiento de calidad.

**RBAC (Role-Based Access Control):** Control de acceso por roles. Spatie Permission en Laravel.

**RCE (Remote Code Execution):** Vulnerabilidad que permite ejecutar código en el servidor. La peor categoría.

**Redis:** Almacén de estructuras en memoria. Versión objetivo: 7.x.

**REST (Representational State Transfer):** Estilo de arquitectura para APIs. JSON:API no es REST puro pero es compatible.

**Reverse proxy:** Proxy que recibe requests externos y los reenvía a backends internos. nginx, HAProxy.

**RFC (Request for Comments):** Documentos técnicos del IETF. Algunos son estándares de facto.

**RPO (Recovery Point Objective):** Cuánto dato se acepta perder. Define frecuencia de backup.

**RTO (Recovery Time Objective):** Cuánto tiempo se acepta estar caído. Define la prioridad de los runbooks.

**SAN (Subject Alternative Name):** Extensión de certificados para incluir múltiples nombres (DNS, IP, etc.).

**Sast:** Static Application Security Testing. Análisis de código sin ejecutarlo.

**SBOM (Software Bill of Materials):** Lista de componentes de un binario. Syft, CycloneDX, SPDX.

**SCA (Software Composition Analysis):** Análisis de dependencias. Dependabot, Snyk.

**SDLC (Secure Development Lifecycle):** Ciclo de desarrollo seguro.

**SE Linux (Security-Enhanced Linux):** Mandatory Access Control alternativo a AppArmor. No usado en Ubuntu por defecto.

**Secret:** Valor sensible (API key, password, cert). Nunca en repo. GitHub Secrets, Vault.

**SELinux:** Mandatory Access Control de Red Hat. Ubuntu usa AppArmor.

**Service Worker:** Script del navegador para cache offline, push notifications. No relevante aquí directamente.

**SHA-1:** Hash deprecado. NUNCA para seguridad.

**SHA-256:** Hash de 256 bits. Estándar actual.

**SHA-3:** Hash de la familia Keccak. Alternativa a SHA-256.

**SIEM (Security Information and Event Management):** Sistema que centraliza logs y alertas. Wazuh Manager + Elasticsearch.

**Signal:** En Unix, mecanismo de IPC. `SIGTERM`, `SIGHUP` (reload).

**Single Sign-On (SSO):** Login único que da acceso a múltiples servicios. Cloudflare Zero Trust.

**SLA (Service Level Agreement):** Acuerdo de nivel de servicio. 99.9% uptime = 8.76h/año de caída.

**SLO (Service Level Objective):** Objetivo medible de servicio. Más granular que SLA.

**SLI (Service Level Indicator):** Indicador medido. Latencia p99, error rate, etc.

**Snyk:** Plataforma de seguridad para código y dependencias. Alternativa a Dependabot.

**SOAR (Security Orchestration, Automation and Response):** Automatización de respuesta a incidentes.

**SOC (Security Operations Center):** Centro de operaciones de seguridad.

**SOC 2:** Certificación de seguridad para proveedores SaaS.

**Socket:** Punto final de comunicación. UNIX socket, TCP socket.

**SQL injection:** Vulnerabilidad que permite inyectar SQL. Eloquent + parameterized queries la mitigan.

**SSH (Secure Shell):** Protocolo de acceso remoto seguro. Versión objetivo: OpenSSH 9.x.

**SSL (Secure Sockets Layer):** Versión deprecada. Usar TLS.

**SSRF (Server-Side Request Forgery):** Vulnerabilidad que hace al servidor hacer requests a recursos internos.

**SSL Labs (Qualys):** Servicio que evalúa la configuración TLS.

**SSO:** Single Sign-On.

**Subnet:** Subred. 192.168.1.0/24 = 256 direcciones.

**SVC:** No relevante aquí.

**Symfony:** Framework PHP subyacente a Laravel.

**Syslog:** Protocolo estándar de logging.

**Sysctl:** Configuración del kernel en runtime. /etc/sysctl.d/*.conf.

**Systemd:** Init y gestor de servicios en Linux moderno.

**TCP (Transmission Control Protocol):** Protocolo orientado a conexión.

**TFA / 2FA (Two-Factor Authentication):** Ver cap-01 §4.

**TLS (Transport Layer Security):** Sucesor de SSL. Versión actual: 1.3 (RFC 8446).

**Token (Sanctum):** Cadena aleatoria hasheada en DB. Tiene abilities, expiración, last_used_at.

**TOTP (Time-based One-Time Password):** RFC 6238. Códigos de 6 dígitos que rotan cada 30s.

**TPM (Trusted Platform Module):** Chip criptográfico. No usado directamente aquí.

**Traffic shaping:** Control de uso de ancho de banda. nftables con limit rate.

**Transport security:** Cifrado en tránsito (TLS).

**Trivy:** Scanner de vulnerabilidades de filesystem, imágenes Docker, IaC.

**UDF (User Defined Function):** Función personalizada en SQL. MySQL/PostgreSQL las soportan.

**UDP (User Datagram Protocol):** Protocolo sin conexión. QUIC, DNS, VoIP.

**UEFI (Unified Extensible Firmware Interface):** Reemplazo del BIOS. Soporte Secure Boot.

**Uptime:** Porcentaje de tiempo activo. 99.99% = 52.6 min/año de caída.

**VHost (Virtual Host):** Configuración de un sitio en un servidor web.

**VLAN (Virtual LAN):** Segmentación de red L2.

**VPC (Virtual Private Cloud):** Red privada en cloud.

**VPN (Virtual Private Network):** Túnel cifrado entre redes. WireGuard, OpenVPN, IPsec.

**Vulnerabilidad:** Debilidad explotable.

**Vulnerability scan:** Escaneo de vulnerabilidades. Trivy, Grype, OpenVAS.

**WAF (Web Application Firewall):** Firewall a nivel de aplicación HTTP. Cloudflare WAF, ModSecurity.

**WebAuthn:** Estándar de autenticación sin contraseña. No usado directamente aquí.

**WEP:** Cifrado WiFi antiguo y roto. NUNCA.

**WPA3:** Cifrado WiFi moderno. Estándar actual.

**XSS (Cross-Site Scripting):** Vulnerabilidad que permite inyectar JS. CSP la mitiga.

**YAML (YAML Ain't Markup Language):** Formato de configuración. Usado en GitHub Actions, docker-compose, etc.

**Zero Trust:** Modelo de seguridad que asume cero confianza perimetral. Cloudflare Access implementa esto.

**Zero-day:** Vulnerabilidad desconocida por el fabricante. Sin parche disponible.
