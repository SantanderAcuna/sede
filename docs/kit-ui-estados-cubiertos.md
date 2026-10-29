# Kit UI: componente → estados cubiertos (T-28, AC-28.1)

Qué exige cada requisito, qué prueba lo cubre y **qué no cubre**. Lo que jsdom no puede medir
(maquetación y aspecto) está marcado y lo comprueba el navegador real
(`npm run test:kit-ui`, `tests/kit-ui.mjs`).

Las pruebas viven en `sitio/tests/kit-*.test.ts`. Todas incluyen axe-core sobre el DOM montado
(sin la regla de contraste de color, que jsdom no puede calcular).

| Componente (requisito) | Estado exigido | Cubierto por | Dónde |
|---|---|---|---|
| **Acordeón** (RF-B3-062) | Normal (contraído): `aria-expanded="false"`, región `hidden` | prueba de DOM | `kit-acordeon.test.ts` |
| | Activo (expandido): `aria-expanded="true"`, región visible; se vuelve a cerrar | prueba de DOM | `kit-acordeon.test.ts` |
| | Foco: **no se abre al recibir el foco** | prueba de DOM + navegador | `kit-acordeon.test.ts` · `kit-ui.mjs` §3 (necesita una ficha con pasos) |
| | Deshabilitado: `disabled`, cerrado, el clic no lo abre | prueba de DOM | `kit-acordeon.test.ts` |
| | Hover | **sólo existencia de la regla CSS** | `kit-estados-css.test.ts` |
| **Alerta modal** (RF-B3-063) | Abierto: `role="dialog"`, `aria-modal`, título y descripción, foco en la acción segura | prueba de DOM | `kit-modal.test.ts` |
| | Cierre con Esc, con clic en el fondo, con los botones; clic dentro no cierra | prueba de DOM | `kit-modal.test.ts` |
| | No ocupa toda la pantalla | regla CSS (ancho máximo, sin alto de ventana) | `kit-modal.test.ts` |
| | Tabulación atrapada | **no cubierto** (jsdom no calcula `offsetParent`) | pendiente en T-29 |
| **Toast** (RF-B3-064) | Informativa / positiva / negativa: clase del Kit, rol (`status` / `alert`), título y mensaje | prueba de DOM | `kit-toast.test.ts` |
| | Duración suficiente; el cursor o el foco detienen el reloj y sigue donde iba | temporizadores simulados | `kit-toast.test.ts` |
| | Se cierra con el botón y con Esc; la negativa no caduca; máximo 3 visibles | prueba de DOM | `kit-toast.test.ts` |
| | Aparece y se cierra en un navegador real | navegador | `kit-ui.mjs` §4 |
| **Botones** (RF-B3-066) | 4 variantes (`fill`, `outline`, `fill-secundary`, `outline-secundary`) × normal, hover, foco, deshabilitado | **existencia de la regla CSS** en el Kit | `kit-estados-css.test.ts` |
| | Su aspecto real en cada estado | **no cubierto** | pendiente en T-29 |
| **Spinner** (RF-B3-070) | Indicador + mensaje en región de estado | prueba de DOM | `kit-cargando.test.ts` |
| | Aviso a los 10 s (ni uno antes) y reloj liberado al desmontar | temporizadores simulados | `kit-cargando.test.ts` |
| **Paginación** (RF-B3-075) | `aria-current="page"` sólo en la actual; elipsis decorativa; primera, intermedias y última | prueba de DOM | `kit-paginacion.test.ts` |
| | Una sola página; extremos apagados (también por carga o fallo) | prueba de DOM | `kit-paginacion.test.ts` |
| | Área activa ≥ 44 × 44 px | regla CSS + **medida en navegador** a 390 px | `kit-paginacion.test.ts` · `kit-ui.mjs` §2 |
| **Tabla** (RF-B3-076) | `aria-sort` `none` / `ascending` / `descending` en columnas ordenables, ninguno en las demás | prueba de DOM | `kit-tabla.test.ts` |
| | Orden numérico (10 < 15 < 30), de texto en español, estable, sin alterar los datos | prueba de DOM | `kit-tabla.test.ts` |
| | Aviso hablado del orden aplicado | prueba de DOM | `kit-tabla.test.ts` |
| | En la tabla real de PQRSD, con medida del botón (≥ 44 px) | navegador | `kit-ui.mjs` §1 |

## Lo que NO está demostrado (dicho aquí para que nadie lo dé por cubierto)

- **Lector de pantalla real.** Los roles y los avisos están bien formados (axe lo confirma), pero no
  se escuchó el resultado con NVDA/JAWS/VoiceOver. **HIPÓTESIS:** el aviso emergente se anuncia de
  forma fiable; un contenedor presente de antemano es la forma que menos riesgo tiene, no una garantía.
- **Contraste de color** de los componentes nuevos: axe en jsdom no puede calcularlo. Se revisó a
  ojo en capturas; falta la medición en el navegador (T-29).
- **Acordeón en una ficha con datos reales**: la puerta de navegador lo declara «NO COMPROBADO» si el
  catálogo no tiene trámites con pasos.
- **Modo de alto contraste** con los componentes nuevos: la captura visual sólo cubre la portada.
