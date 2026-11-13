# Entorno local en Docker

La misma pila que se despliega, para revisar cambios y correr pruebas **antes** de pasarlos a `develop`.

```bash
make local          # genera .env y certificados de prueba (una vez), levanta, migra con el dueño y siembra
make local-abajo    # detiene la pila, conserva los datos
make local-limpio   # detiene y BORRA los datos locales
```

- Sitio y API: <https://localhost:8443> (certificado autofirmado: el navegador pide aceptarlo).
- Correo de prueba: <http://localhost:8025>.
- `.env` y los certificados se crean con `scripts/local.sh` **sin pisar** los que ya existan; están en
  `.gitignore` y no salen de la máquina. Los secretos son aleatorios y propios de esta máquina.
- La aplicación corre con el rol `sede_app` y la base usa TLS, como en producción. El servicio
  `migrar` es el único que lleva la clave del dueño `sede` (ver ADR-0004).
- Sólo el entorno y los certificados: `bash scripts/local.sh preparar`.
