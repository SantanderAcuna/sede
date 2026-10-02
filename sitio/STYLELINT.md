# Gobernanza del CSS

Las reglas viven en `.stylelintrc.json` y se ejecutan en el CI. Este documento
explica **por qué** cada una tiene el valor que tiene, porque una regla sin
motivo se desactiva en cuanto estorba.

## Las reglas, y qué protegen

| Regla | Valor | Qué evita |
|---|---|---|
| `max-nesting-depth` | 3 | Un selector que depende de su contexto deja de ser reutilizable |
| `selector-max-id` | 0 | Un `#id` gana siempre: obliga a `!important` para sobreescribirlo |
| `selector-max-specificity` | `0,3,0` | Que la siguiente persona necesite `!important` para ganarte |
| `declaration-no-important` | aviso | Un `!important` casi siempre esconde un problema de especificidad |
| `no-duplicate-selectors` | error | Dos bloques para el mismo selector: el orden decide y nadie lo ve |

## Las dos excepciones, y por qué son excepciones y no silencios

### 1. `tokens.css` puede repetir `:root`

El archivo declara los tokens en **tres niveles** —primitivo, semántico, de
componente— y cada uno es un bloque autocontenido. Fusionarlos daría un bloque
de 800 líneas donde el nivel se pierde de vista. La excepción está **declarada en
el config**, no silenciada: quien la lea sabe que es deliberada.

### 2. El `!important` de los modos de accesibilidad es **obligatorio**, no deuda

Aquí conviene ser exacto, porque la instrucción original pedía «cero
`!important` fuera de `utilities/`» y este proyecto tiene 43.

**33 de los 43 están en los modos de accesibilidad** —`.contraste-govco`,
`.dislexia-govco`, `.detener-animaciones-govco`, `.resaltar-enlaces-govco`,
`.contraste-inverso-govco`, `.contraste-grises-govco`—.

Un modo de accesibilidad **tiene que ganarle a todo**, incluido el CSS del Kit UI
gov.co, que es un tercero que no controlamos y que llega vendorizado byte a byte
desde su CDN. Sin `!important` no hay forma de que el modo de alto contraste
pueda repintar lo que el Kit ya pintó. **Meterlos en `utilities/` sería
clasificarlos mal: no son helpers, son una capa de modo**, y así se tratan.

Por eso la regla queda como **aviso y no como error**: el CI señala cada
`!important` nuevo para que alguien decida si es un modo legítimo o un atajo, en
vez de bloquear la construcción por algo que a veces es la respuesta correcta.

## Lo que el CI puede verificar

```bash
npx stylelint "app/assets/css/*.css"
```

Y las métricas que quedan medidas hoy, para poder compararlas mañana:

| Métrica | Valor |
|---|---|
| `!important` | 43 (33 en modos de accesibilidad) |
| Selectores con `#id` | 5 |
| Especificidad por encima de `0,3,0` | 60 |
| Peso de `sitio.css` | 60 KB |

**No se ponen como puerta bloqueante todavía.** Poner el umbral en cero cuando hay
60 infracciones conocidas obliga a desactivar el CI el primer día, que es la peor
gobernanza posible. El orden correcto es: medir, reducir, y **entonces** bloquear.
