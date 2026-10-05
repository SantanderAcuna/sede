/**
 * Aviso de salida a sitio externo (RF-B1-071, RF-01-D03, RN-01-D03).
 *
 * Se prueba la **clasificación**, que es donde el aviso se equivoca de dos
 * maneras opuestas: si trata como externo lo que no lo es, aparece un modal cada
 * vez que alguien escribe un correo; y si trata como interno lo que sí lo es, el
 * ciudadano sale de la sede sin que nadie le avise. Los dos casos están aquí,
 * incluidos los que no se ven a simple vista (`mailto:`, `tel:` y las anclas,
 * cuyo `hostname` está vacío y que la primera versión clasificaba como externos).
 */
import { describe, expect, it, vi } from 'vitest'

import { useAvisoSalida } from '../app/composables/useAvisoSalida'

const { esEnlaceExterno, esDominioConfianza } = useAvisoSalida()

describe('qué cuenta como salir de la sede', () => {
  it('las rutas internas no son externas', () => {
    expect(esEnlaceExterno('/tramites')).toBe(false)
    expect(esEnlaceExterno('/politicas/uso-de-cookies')).toBe(false)
  })

  it('las anclas de la misma página no son externas', () => {
    expect(esEnlaceExterno('#contenido-principal')).toBe(false)
  })

  it('un correo o un teléfono no son una salida: no llevan a otro sitio', () => {
    expect(esEnlaceExterno('mailto:atencionalciudadano@santamarta.gov.co')).toBe(false)
    expect(esEnlaceExterno('tel:+576054209600')).toBe(false)
    expect(esEnlaceExterno('sms:+573001234567')).toBe(false)
  })

  it('una URL absoluta de otro dominio sí es una salida', () => {
    expect(esEnlaceExterno('https://www.facebook.com/alcaldiasantamarta')).toBe(true)
    expect(esEnlaceExterno('http://ejemplo.com')).toBe(true)
  })

  it('una cadena que no es una URL no se considera salida', () => {
    expect(esEnlaceExterno('no soy una url')).toBe(false)
  })
})

describe('lista blanca de dominios de confianza (RN-01-D03)', () => {
  it('el ecosistema del Estado no dispara el aviso', () => {
    expect(esDominioConfianza('https://www.gov.co/')).toBe(true)
    expect(esDominioConfianza('https://secop.gov.co')).toBe(true)
    expect(esDominioConfianza('https://suin.gov.co')).toBe(true)
    expect(esDominioConfianza('https://carpetaciudadana.gov.co')).toBe(true)
    expect(esDominioConfianza('https://www.dane.gov.co')).toBe(true)
    expect(esDominioConfianza('https://www.colombia.co')).toBe(true)
  })

  it('un subdominio de un dominio de confianza también lo es', () => {
    expect(esDominioConfianza('https://portal.secop.gov.co')).toBe(true)
  })

  it('un tercero no está en la lista', () => {
    expect(esDominioConfianza('https://www.facebook.com')).toBe(false)
    expect(esDominioConfianza('https://x.com')).toBe(false)
  })

  it('un dominio que sólo termina parecido no se cuela', () => {
    // `falsogov.co` no es `gov.co`: la comprobación exige el punto de separación.
    expect(esDominioConfianza('https://falsogov.co')).toBe(false)
  })

  it('el propio dominio de la sede siempre es de confianza', () => {
    expect(esDominioConfianza(`https://${window.location.hostname}/tramites`)).toBe(true)
  })
})

describe('la decisión completa', () => {
  it('un enlace al Portal GOV.CO es externo pero no se avisa', () => {
    const url = 'https://www.gov.co/'
    expect(esEnlaceExterno(url)).toBe(true)
    expect(esDominioConfianza(url)).toBe(true)
  })

  it('un enlace a una red social se avisa y se dice a quién se va', () => {
    const url = 'https://www.instagram.com/alcaldiasantamarta'
    expect(esEnlaceExterno(url)).toBe(true)
    expect(esDominioConfianza(url)).toBe(false)
  })
})

/**
 * El estado del aviso: pedirlo, confirmarlo y cancelarlo.
 *
 * La clasificación de enlaces se prueba arriba; esto prueba lo que pasa cuando el
 * aviso se pide y cuando se responde, que es donde vive el riesgo de navegar sin
 * que nadie haya confirmado —o de no navegar después de confirmar—.
 */
describe('pedir, confirmar y cancelar el aviso', () => {
  it('pedirlo deja el enlace pendiente con su nombre y su entidad', () => {
    const { visible, enlacePendiente, solicitarConfirmacion } = useAvisoSalida()

    solicitarConfirmacion('https://www.instagram.com/alcaldiasantamarta', true, null)

    expect(visible.value).toBe(true)
    expect(enlacePendiente.value?.url).toBe('https://www.instagram.com/alcaldiasantamarta')
    // El nombre y la entidad salen del dominio, no de una lista escrita a mano.
    expect(enlacePendiente.value?.nombre).toBeTruthy()
    expect(enlacePendiente.value?.entidad).toBeTruthy()
    expect(enlacePendiente.value?.nuevaPestana).toBe(true)
  })

  it('recuerda quién abrió el aviso, para devolverle el foco', () => {
    const { origenDelAviso, solicitarConfirmacion } = useAvisoSalida()
    const enlace = document.createElement('a')

    solicitarConfirmacion('https://ejemplo.com', true, enlace)

    expect(origenDelAviso.value).toBe(enlace)
  })

  it('confirmar abre el destino y cierra el aviso', () => {
    const { visible, enlacePendiente, solicitarConfirmacion, confirmarNavegacion } = useAvisoSalida()
    const abrir = vi.spyOn(window, 'open').mockReturnValue(null)

    solicitarConfirmacion('https://ejemplo.com/destino', true, null)
    confirmarNavegacion()

    expect(abrir).toHaveBeenCalledWith('https://ejemplo.com/destino', '_blank', 'noopener,noreferrer')
    expect(visible.value).toBe(false)
    expect(enlacePendiente.value).toBeNull()
    abrir.mockRestore()
  })

  it('cancelar cierra el aviso sin navegar a ninguna parte', () => {
    const { visible, enlacePendiente, solicitarConfirmacion, cancelarNavegacion } = useAvisoSalida()
    const abrir = vi.spyOn(window, 'open').mockReturnValue(null)

    solicitarConfirmacion('https://ejemplo.com/destino', true, null)
    cancelarNavegacion()

    expect(abrir).not.toHaveBeenCalled()
    expect(visible.value).toBe(false)
    expect(enlacePendiente.value).toBeNull()
    abrir.mockRestore()
  })

  it('confirmar sin nada pendiente no navega ni deja el aviso abierto', () => {
    const { visible, confirmarNavegacion, cancelarNavegacion } = useAvisoSalida()
    const abrir = vi.spyOn(window, 'open').mockReturnValue(null)

    cancelarNavegacion()
    confirmarNavegacion()

    expect(abrir).not.toHaveBeenCalled()
    expect(visible.value).toBe(false)
    abrir.mockRestore()
  })
})

describe('nombreDelDestino — inferencia legible de hostname', () => {
  const { nombreDelDestino } = useAvisoSalida()

  it('detecta Facebook', () => expect(nombreDelDestino('https://www.facebook.com/user')).toBe('Facebook'))
  it('detecta Instagram', () => expect(nombreDelDestino('https://instagram.com/user')).toBe('Instagram'))
  it('detecta X/Twitter', () => expect(nombreDelDestino('https://x.com/user')).toBe('X (Twitter)'))
  it('detecta YouTube', () => expect(nombreDelDestino('https://youtube.com/watch')).toBe('YouTube'))
  it('detecta LinkedIn', () => expect(nombreDelDestino('https://linkedin.com/in/user')).toBe('LinkedIn'))
  it('detecta SECOP', () => expect(nombreDelDestino('https://secop.gov.co/portal')).toBe('SECOP — Sistema Electrónico de Contratación Pública'))
  it('detecta SUIN', () => expect(nombreDelDestino('https://suin.gov.co/norma')).toBe('SUIN — Sistema Único de Información Normativa'))
  it('detecta Portal GOV.CO', () => expect(nombreDelDestino('https://www.gov.co')).toBe('Portal Único del Estado — GOV.CO'))
  it('detecta Colombia.co', () => expect(nombreDelDestino('https://www.colombia.co')).toBe('Marca País Colombia'))
  it('hostname genérico devuelve hostname sin www', () => expect(nombreDelDestino('https://ejemplo.gov.co/page')).toBe('ejemplo.gov.co'))
  it('URL inválida devuelve la cadena original', () => expect(nombreDelDestino('no-es-url')).toBe('no-es-url'))
})

describe('entidadDelDestino — identificación del responsable', () => {
  const { entidadDelDestino } = useAvisoSalida()

  it('gov.co devuelve entidad pública', () => expect(entidadDelDestino('https://www.gov.co')).toBe('Entidad pública del Estado colombiano'))
  it('Facebook/Instagram devuelve Meta', () => expect(entidadDelDestino('https://facebook.com/user')).toBe('Meta Platforms, Inc.'))
  it('X/Twitter devuelve X Corp.', () => expect(entidadDelDestino('https://x.com/user')).toBe('X Corp.'))
  it('YouTube devuelve Google', () => expect(entidadDelDestino('https://youtube.com')).toBe('Google LLC'))
  it('LinkedIn devuelve LinkedIn Corp.', () => expect(entidadDelDestino('https://linkedin.com')).toBe('LinkedIn Corporation'))
  it('dominio desconocido devuelve tercero externo', () => expect(entidadDelDestino('https://ejemplo.com')).toBe('Tercero externo'))
  it('URL inválida devuelve entidad externa', () => expect(entidadDelDestino('invalido')).toBe('Entidad externa'))
})
