#!/usr/bin/env node
/**
 * patch-deps.mjs
 *
 * Aplica parches de seguridad a dependencias transitivas que no tienen
 * parche oficial en npm.
 *
 * Ejecutar como postinstall hook:
 *   "postinstall": "node scripts/patch-deps.mjs"
 *
 * Parches aplicados:
 * - braces (CVE-2026-93687): guard de profundidad de anidamiento
 */

import { readFile, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { Script } from 'node:vm';

const __dirname = dirname(fileURLToPath(import.meta.url));
// `PATCH_DEPS_RAIZ` solo la usan las pruebas (`sitio/tests/patch-deps.test.ts`), para trabajar sobre
// una copia y no sobre el `node_modules` real.
const projectRoot = process.env.PATCH_DEPS_RAIZ
  ? resolve(process.env.PATCH_DEPS_RAIZ)
  : resolve(__dirname, '..');

/**
 * ¿Compila el archivo como el módulo CommonJS que es? Se envuelve igual que lo hace Node al cargarlo.
 * Nunca se escribe un parche que no compile: un `expand.js` roto deja sin arrancar a `nuxt prepare`.
 */
function compila(codigo) {
  try {
    new Script(`(function (exports, require, module, __filename, __dirname) {${codigo}\n})`);
    return true;
  } catch {
    return false;
  }
}

const PATCHES = [
  {
    name: 'braces',
    description: 'CVE-2026-93687 — guard contra stack exhaustion por anidamiento',
    files: [
      'sitio/node_modules/braces/lib/expand.js',
      'panel/node_modules/braces/lib/expand.js',
    ],
  },
];

let applied = 0;
let skipped = 0;
let broken = 0;

for (const patch of PATCHES) {
  console.log(`[patch-deps] Verificando ${patch.name} (${patch.description})`);
  for (const file of patch.files) {
    const fullPath = resolve(projectRoot, file);
    if (!existsSync(fullPath)) {
      console.log(`  - ${file}: no existe, saltando`);
      skipped++;
      continue;
    }

    const content = await readFile(fullPath, 'utf8');

    // Ya parcheado: la marca sola no basta, también tiene que compilar. La versión anterior de este
    // script dejaba la marca sobre un archivo roto y la siguiente ejecución lo daba por bueno.
    if (content.includes('CVE-2026-93687')) {
      if (compila(content)) {
        console.log(`  - ${file}: ya parcheado, OK`);
        applied++;
      } else {
        console.error(`  ✗ ${file}: tiene la marca del parche pero NO compila.`);
        console.error(`    Reinstala las dependencias para recuperar el original: rm -rf node_modules && npm ci`);
        broken++;
      }
      continue;
    }

    // Aplicar el parche: completo y compilando, o nada.
    const patched = applyBracesPatch(content);
    if (patched === null || !compila(patched)) {
      console.warn(`  ! ${file}: no se aplicó el parche; el texto de esta versión de braces no coincide.`);
      console.warn(`    El archivo queda sin tocar. Revisa el parche si braces cambió de versión.`);
      skipped++;
      continue;
    }
    await writeFile(fullPath, patched, 'utf8');
    console.log(`  ✓ ${file}: parche aplicado`);
    applied++;
  }
}

console.log(`[patch-deps] Resumen: ${applied} aplicados/verificados, ${skipped} saltados${broken ? `, ${broken} ROTOS` : ''}`);
if (broken > 0) process.exit(1);

/**
 * Devuelve el contenido parcheado, o `null` si alguna de las tres sustituciones no encuentra su texto.
 * Antes eran tres `replace` sueltos: si fallaba el último (el que cierra el `try`), el archivo quedaba
 * con un `try {` abierto. Los textos son los de `braces` 3.0.3 (`sitio/tests/fixtures/braces-3.0.3`).
 */
function applyBracesPatch(content) {
  const sustituir = (texto, buscado, nuevo) => (texto.includes(buscado) ? texto.replace(buscado, nuevo) : null);

  // Inyectar el guard de profundidad
  const guardCode = `
// SECURITY PATCH (CVE-2026-93687): guard against stack exhaustion
// via deeply nested braces. Default limit: 50.
const MAX_NESTING_DEPTH = parseInt(process.env.BRACES_MAX_DEPTH || '50', 10);
let nestingDepth = 0;
`;

  // Añadir guard después de los requires iniciales
  let patched = sustituir(
    content,
    "const utils = require('./utils');\n\nconst append",
    `const utils = require('./utils');\n${guardCode}\nconst append`
  );
  if (patched === null) return null;

  // Envolver el cuerpo de walk() con try/finally
  patched = sustituir(
    patched,
    `  const walk = (node, parent = {}) => {
    node.queue = [];`,
    `  const walk = (node, parent = {}) => {
    // SECURITY PATCH: enforce nesting depth limit
    nestingDepth++;
    if (nestingDepth > MAX_NESTING_DEPTH) {
      nestingDepth--;
      throw new RangeError(
        \`braces: nesting depth exceeds \${MAX_NESTING_DEPTH} (CVE-2026-93687 mitigation). \` +
        \`Pattern rejected to prevent stack exhaustion.\`
      );
    }
    try {
    node.queue = [];`
  );
  if (patched === null) return null;

  // Cerrar el try/finally después del `return queue;` de walk() (4 espacios en braces 3.0.3; la
  // versión anterior buscaba 6 y nunca lo encontraba).
  return sustituir(
    patched,
    `    return queue;
  };

  return utils.flatten(walk(ast));`,
    `    return queue;
    } finally {
      nestingDepth--;
    }
  };

  return utils.flatten(walk(ast));`
  );
}
