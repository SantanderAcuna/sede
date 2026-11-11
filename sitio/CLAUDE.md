# Instrucciones para agentes

Las reglas de este proyecto viven en [`AGENTS.md`](AGENTS.md), en un solo lugar.

Este archivo se mantiene como enlace porque algunas herramientas leen `CLAUDE.md`
y otras `AGENTS.md`, y dos copias de las mismas reglas terminan divergiendo. La
fuente es `AGENTS.md`.

## Lo mínimo, si vas con prisa

0. **El diseño está aprobado por José y no se toca sin su autorización explícita en el chat.**
   Un hallazgo de una prueba (axe, la puerta visual, la captura) se informa; no se «arregla».
   Cambiar pruebas, scripts de prueba o el CI no cuenta como tocar el diseño.
1. **`public/govco/**` no se toca.** Es el Kit y Bootstrap vendorizados byte a byte
   contra su CDN, y el Kit **depende** de Bootstrap sin distribuirlo.
2. **Un color no se escribe en crudo fuera de `tokens.css`.**
3. **Antes de dar algo por hecho**: `typecheck`, `build`, `stylelint` y
   **`scripts/captura-visual.mjs`**. La captura es la única prueba que detecta que
   el diseño cambió — `axe` no lo ve.
4. **Nada de contenido institucional inventado.** Lo aprueba la Entidad.
5. **La puerta visual corre sola en cada PR hacia `develop`** (`npm run test:puerta-visual`);
   su línea base no se sube sin autorización de José.

El resto, en [`AGENTS.md`](AGENTS.md).
