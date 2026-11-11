(() => {
  // El equipo activa una decoración desde código y puede cambiarla sin recargar la página.
  const TEMA_ACTIVO = 'christmas'
  const TEMAS = {
    christmas: `${window.BASE_URL}seasonal/themes/christmas/theme.js`,
  }
  const root = document.getElementById('seasonal-decoration-root')

  if (!root) return

  const version = window.SEASONAL_ASSET_VERSION || '1'
  let limpiezaTema = null
  let hojaEstilos = null
  let solicitudActual = 0

  const desactivar = async () => {
    solicitudActual++
    limpiezaTema?.()
    limpiezaTema = null
    hojaEstilos?.remove()
    hojaEstilos = null
    root.replaceChildren()
  }

  const activar = async (nombreTema) => {
    await desactivar()
    if (nombreTema === 'none') return
    if (!Object.prototype.hasOwnProperty.call(TEMAS, nombreTema)) {
      throw new Error(`Tema de temporada no registrado: ${nombreTema}`)
    }

    const solicitud = ++solicitudActual
    const estilos = document.createElement('link')
    estilos.rel = 'stylesheet'
    estilos.href = `${window.BASE_URL}seasonal/themes/${nombreTema}/theme.css?v=${encodeURIComponent(version)}`
    hojaEstilos = estilos

    try {
      const cargarEstilos = new Promise((resolve, reject) => {
        estilos.onload = resolve
        estilos.onerror = () => reject(new Error(`No se pudieron cargar los estilos de ${nombreTema}.`))
        document.head.appendChild(estilos)
      })
      const [ , modulo ] = await Promise.all([
        cargarEstilos,
        import(`${TEMAS[nombreTema]}?v=${encodeURIComponent(version)}`),
      ])
      if (solicitud !== solicitudActual) return

      const limpiar = await modulo.activar({ root, baseUrl: window.BASE_URL, version })
      if (solicitud !== solicitudActual) {
        limpiar?.()
        return
      }
      limpiezaTema = limpiar
    } catch (error) {
      if (solicitud === solicitudActual) {
        estilos.remove()
        hojaEstilos = null
        root.replaceChildren()
      }
      console.warn('La decoración de temporada no se pudo iniciar:', error)
    }
  }

  window.GestorDecoracionEstacional = { activar, desactivar }
  void activar(TEMA_ACTIVO)
})()
