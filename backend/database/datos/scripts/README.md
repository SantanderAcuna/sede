# Ingesta del catálogo desde SUIT

Estos tres scripts son la receta con la que se construyen las dos copias
congeladas que el sembrador lee:

| Archivo | Qué es |
|---|---|
| `../tramites-0043-suit-visor.json` | La ficha completa de cada trámite, en la forma del visor de SUIT (requisitos por tipo, normativa con su archivo, momentos, puntos de atención, audiencias, cuentas, seguimiento) |
| `../tramites-0043-suit.json` | El listado por T-código de GOV.CO, cruzado con el visor: es lo que el `TramiteSeeder` lee |

## Por qué existen

El `TramiteSeeder` documenta que sin la copia congelada «ni la integración
continua ni un despliegue nuevo podrían sembrar nada». Eso vale también para la
receta: mientras vivió en un directorio de trabajo no versionado, **la copia no
se podía reproducir** —solo se podía confiar en que alguien la hubiera dejado
ahí—. Estos scripts son la receta versionada.

## Orden

```bash
cd backend/database/datos/scripts

# 1. Red: trae el listado de la entidad 0043 y la ficha de cada trámite.
#    ~124 peticiones con 200 ms de pausa (unos 2 minutos).
php 01-scrape-visor.php /tmp/visores.json

# 2. Normaliza la respuesta cruda al JSON del visor. No toca la red.
php 02-normalizar-visor.php /tmp/visores.json ../tramites-0043-suit-visor.json

# 3. Cruza el visor con el listado T-código y deriva los campos de la Sede
#    (entre ellos `url_descarga`). No toca la red.
php 03-cruzar-con-listado.php ../tramites-0043-suit-visor.json ../tramites-0043-suit.json
```

Después, en `backend/`: `php artisan db:seed --class=TramiteSeeder`.

## Lo que hay que saber antes de ejecutarlos

- **El certificado del DAFP no valida con el almacén de CA local**: los scripts
  desactivan la verificación TLS (`verify_peer => false`) y está declarado en el
  código. Es una concesión del raspado, no una decisión de diseño.
- **El paso 3 conserva el bloque de GOV.CO** (`nombreEstandarizado_govco`,
  `proposito_govco`) que ya esté en el listado. Ese bloque se obtuvo una sola vez
  de `api-interno.www.gov.co` y es el que manda para el nombre y el propósito:
  los 124 nombres del listado SUIT y los 124 de GOV.CO **difieren**, y la Sede
  publica los de GOV.CO por ser los vigentes.
- **El paso 1 reescribe la foto completa.** Si el portal cambió un trámite desde
  la última captura, el diff de datos lo va a mostrar. Para un cambio quirúrgico
  conviene conservar la respuesta cruda anterior y volver a correr solo el 2 y el 3.
- **El sembrador solo reescribe las filas que llevan su marca**
  (`GOV.CO + SUIT — Visor (entidad 0043)`). Si un trámite fue corregido a mano en
  el panel, la siembra lo cuenta como protegido y no lo toca.
