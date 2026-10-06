#!/usr/bin/env node
/**
 * Test de validación del parche de seguridad de braces (CVE-2026-93687)
 *
 * Verifica que el guard de profundidad funciona correctamente:
 * 1. Patrones normales (≤ 10 niveles) se procesan OK
 * 2. Patrones maliciosos (> 50 niveles) lanzan RangeError
 *
 * Uso: node tests/test-braces-patch.mjs
 */

import { strict as assert } from 'node:assert';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

let pass = 0;
let fail = 0;

function test(name, fn) {
  try {
    fn();
    console.log(`  \u2713 ${name}`);
    pass++;
  } catch (err) {
    console.error(`  \u2717 ${name}: ${err.message}`);
    fail++;
  }
}

console.log('=== Test: patch de seguridad de braces (CVE-2026-93687) ===\n');

// Cargar el módulo braces desde el proyecto
const bracesPath = path.join(__dirname, '..', '..', 'node_modules', 'braces');
const braces = await import(bracesPath);

test('patron normal (1 nivel) se expande correctamente', () => {
  const result = braces.expand('a{b,c,d}e');
  assert.deepEqual(result.sort(), ['abe', 'ace', 'ade']);
});

test('patron normal (5 niveles) se expande correctamente', () => {
  const result = braces.expand('a{b,c{d,e{f,g}}h,i}j');
  assert.ok(result.length > 0);
});

test('patron malicioso (60 niveles) lanza RangeError', () => {
  // Construir patron con 60 llaves abiertas
  const malicious = '{'.repeat(60) + 'a' + '}'.repeat(60);
  assert.throws(
    () => braces.expand(malicious),
    /nesting depth exceeds/,
    'Debe lanzar RangeError por nesting depth'
  );
});

test('patron malicioso (100 niveles) lanza RangeError', () => {
  const malicious = '{'.repeat(100) + 'a' + '}'.repeat(100);
  assert.throws(
    () => braces.expand(malicious),
    /nesting depth exceeds/,
    'Debe lanzar RangeError por nesting depth'
  );
});

test('limite configurable via variable de entorno', () => {
  const oldEnv = process.env.BRACES_MAX_DEPTH;
  process.env.BRACES_MAX_DEPTH = '5';
  // Recargar el modulo con la nueva env var
  delete require.cache[require.resolve(bracesPath)];
  const reloadedBraces = require(bracesPath);
  const malicious = '{'.repeat(10) + 'a' + '}'.repeat(10);
  try {
    assert.throws(
      () => reloadedBraces.expand(malicious),
      /nesting depth exceeds 5/,
      'Debe respetar el limite de 5'
    );
  } finally {
    if (oldEnv) {
      process.env.BRACES_MAX_DEPTH = oldEnv;
    } else {
      delete process.env.BRACES_MAX_DEPTH;
    }
  }
});

console.log(`\n=== Resultado: ${pass} pass, ${fail} fail ===`);
process.exit(fail > 0 ? 1 : 0);
