# Licencia de las tipografías del Kit UI servidas por la Sede (AC-28.6, H-20)

> **Estado: PENDIENTE de confirmación escrita.** Este documento reúne lo comprobado y lo que falta.
> No es una opinión jurídica, y la confirmación no la puede dar este repositorio: la dan quien
> publica el Kit (el Ministerio de Tecnologías de la Información y las Comunicaciones) o la oficina
> jurídica de la Alcaldía.

## Qué se sirve

La Sede distribuye, desde `sitio/public/govco/assets/fonts/`, dos familias del Kit UI de GOV.CO:

| Familia | Archivos | Licencia a la vista |
|---|---|---|
| **Nunito Sans** | `Nunito_Sans/static/*` | **HECHO:** el paquete trae `OFL.txt` (SIL Open Font License 1.1) y `README.txt`. La OFL permite usar, copiar y redistribuir la fuente —incluida su incorporación a un sitio web— con la condición de conservar el aviso de derechos y la licencia, y no venderla por sí sola. |
| **Verdana** | `Verdana/static/*` | **HECHO:** el paquete **no trae ningún archivo de licencia**. **HECHO (fuente externa conocida, sin verificar aquí):** Verdana es una fuente propietaria de Microsoft, diseñada por Matthew Carter; su redistribución como archivo de fuente web **no es libre** por defecto. |

`sitio/public/govco/LICENSE.md` (que no se edita: es del Kit) contiene sólo el título «Licencia del
proyecto Layout gov.co 5.0», sin el texto de la licencia. **No hay en el repositorio ninguna
constancia escrita de que se pueda redistribuir Verdana.**

## Lo que se concluye y lo que no

- **HECHO:** Nunito Sans se puede redistribuir en las condiciones de la OFL, y el aviso está incluido.
- **HECHO:** el repositorio no demuestra el derecho a redistribuir Verdana.
- **INFERENCIA:** el Kit llegó con Verdana porque el Ministerio lo publica así en su CDN, y la Sede
  copió lo que el Ministerio distribuye. Eso explica el origen, **no autoriza** la redistribución.
- **HIPÓTESIS:** que el Ministerio tenga una licencia que cubra a las entidades públicas que usan el
  Kit. Es plausible y no está comprobada.
- **No se sabe:** si el uso de Verdana por parte de la Alcaldía cae en una licencia ya existente (por
  ejemplo, la de un sistema operativo, que cubre su uso local pero no su distribución como archivo web).

## Qué hace falta (AC-28.6: «confirmación escrita de que se pueden redistribuir; si no, se retiran»)

1. **Pedir por escrito** al Ministerio (equipo del Kit UI de GOV.CO) o a su mesa de ayuda: ¿las
   tipografías del Kit, y en particular Verdana, se pueden redistribuir desde el sitio de cada
   entidad? ¿Bajo qué licencia o autorización? Guardar la respuesta en `docs/`.
2. **Si la respuesta es sí:** anotar aquí la fecha, el remitente y el texto, y cerrar H-20.
3. **Si la respuesta es no, o no llega:** retirar `Verdana/static/*` del paquete propio. Eso cambia
   el aspecto del sitio, porque el Kit declara `Verdana-Regular` y `Verdana-Bold` para el texto
   corriente. La alternativa técnica es la pila de sistema que el sitio ya declara como respaldo
   (`Verdana, Tahoma, …`), que usa la copia local de quien visita en vez de la nuestra. Esta decisión
   afecta a la identidad visual y debe tomarla la Entidad, no el equipo técnico.

## Borrador de la solicitud (sin nombres ni contacto: los pone quien la envía)

> Asunto: Licencia de redistribución de las tipografías del Kit UI de GOV.CO
>
> La Alcaldía Distrital de Santa Marta sirve desde su sede electrónica el Kit UI de GOV.CO (versión
> 9.2) con sus tipografías, para que la sede siga disponible aunque `cdn.www.gov.co` no responda.
> Entre ellas figura Verdana. Quisiéramos saber si el Ministerio autoriza a las entidades a
> redistribuir esas tipografías desde su propio dominio y, de ser así, bajo qué licencia o
> documento, para dejarlo registrado en nuestro expediente.
