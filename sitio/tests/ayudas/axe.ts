/**
 * Ayuda para las pruebas de componentes: ejecuta axe-core sobre un nodo montado
 * en jsdom y devuelve las violaciones en una forma legible.
 *
 * **Qué no puede medir jsdom, y se dice aquí para que nadie lo dé por cubierto:**
 * el contraste de color y la geometría (tamaños en píxeles, solapes) dependen de
 * un motor de maquetación que jsdom no tiene. Se desactiva la regla
 * `color-contrast` —en jsdom daría «incompleto» siempre— y lo que dependa de la
 * maquetación se comprueba en navegador real (`tests/diseno.mjs`, T-29).
 */
import axe from 'axe-core'

export async function violacionesAxe(nodo: Element): Promise<string[]> {
  const resultado = await axe.run(nodo, {
    rules: { 'color-contrast': { enabled: false }, region: { enabled: false } },
  })
  return resultado.violations.map(
    (v) => `${v.id}: ${v.help} (${v.nodes.map((n) => n.target.join(' ')).join(' | ')})`,
  )
}
