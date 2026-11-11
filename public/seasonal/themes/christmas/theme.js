const cargarLottie = () => {
  if (window.lottie) return Promise.resolve(window.lottie)
  if (window.__lottieSvgPromise) return window.__lottieSvgPromise

  window.__lottieSvgPromise = new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = `${window.BASE_URL}js/lottie-svg.js?v=${encodeURIComponent(window.SEASONAL_ASSET_VERSION || '1')}`
    script.async = true
    script.onload = () => resolve(window.lottie)
    script.onerror = () => reject(new Error('No se pudo cargar el reproductor Lottie.'))
    document.head.appendChild(script)
  })

  return window.__lottieSvgPromise
}

const crearElemento = (clase, padre, id = '') => {
  const elemento = document.createElement('div')
  elemento.className = clase
  elemento.setAttribute('aria-hidden', 'true')
  elemento.setAttribute('inert', '')
  if (id) elemento.id = id
  padre.appendChild(elemento)
  return elemento
}

export async function activar({ root, baseUrl, version }) {
  const main = root.closest('main')
  const modal = document.getElementById('modal-general')
  const modalCaja = modal?.firstElementChild
  const titulo = document.getElementById('modal-title')
  const botonCerrar = modalCaja?.querySelector('button[onclick="cerrarModal()"]')
  const footer = document.querySelector('footer')
  if (!main || !modal || !modalCaja || !titulo || !botonCerrar || !footer) {
    throw new Error('No se encontró el contenedor de la decoración navideña.')
  }

  const notaFestiva = document.createElement('span')
  notaFestiva.className = 'seasonal-christmas-footer-note'
  notaFestiva.textContent = ' · ¡Felices Fiestas!'
  footer.appendChild(notaFestiva)

  const parentTitulo = titulo.parentNode
  const nextTitulo = titulo.nextSibling
  const parentBoton = botonCerrar.parentNode
  const nextBoton = botonCerrar.nextSibling
  const claseTituloOriginal = titulo.getAttribute('class')
  const claseBotonOriginal = botonCerrar.getAttribute('class')
  const encabezado = document.createElement('div')
  encabezado.className = 'seasonal-christmas-heading-row'
  modalCaja.insertBefore(encabezado, botonCerrar)
  encabezado.appendChild(titulo)

  const home = crearElemento('seasonal-christmas-home', root)
  const mainAnimation = crearElemento('', home)
  const campanillas = [
    crearElemento('seasonal-christmas-chime seasonal-christmas-chime-left', root),
    crearElemento('seasonal-christmas-chime seasonal-christmas-chime-right', root),
  ]
  const snow = crearElemento('seasonal-christmas-modal', encabezado)
  const snowHalves = [
    crearElemento('seasonal-christmas-modal-half', snow),
    crearElemento('seasonal-christmas-modal-half', snow),
  ]
  titulo.setAttribute('class', 'text-lg font-bold text-carbon-900 border-l-4 border-yellow-500 pl-3')
  botonCerrar.setAttribute('class', 'modal-close-button text-carbon-400 hover:text-red-500 transition-colors text-2xl line-height-0')
  encabezado.appendChild(botonCerrar)

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)')
  const mobile = window.matchMedia('(max-width: 640px)')
  const animations = []
  let active = true
  let homeVisible = false
  let homeLoaded = false
  let homeStarted = false
  let homeFinished = false
  let homeAnimation = null

  const limpiar = () => {
    if (!active) return
    active = false
    observer.disconnect()
    modalObserver.disconnect()
    document.removeEventListener('visibilitychange', updatePlayback)
    reducedMotion.removeEventListener('change', updatePlayback)
    mobile.removeEventListener('change', updatePlayback)
    animations.forEach((animation) => animation.destroy())
    parentBoton.insertBefore(botonCerrar, nextBoton?.parentNode === parentBoton ? nextBoton : null)
    parentTitulo.insertBefore(titulo, nextTitulo?.parentNode === parentTitulo ? nextTitulo : null)
    if (claseTituloOriginal === null) titulo.removeAttribute('class')
    else titulo.setAttribute('class', claseTituloOriginal)
    if (claseBotonOriginal === null) botonCerrar.removeAttribute('class')
    else botonCerrar.setAttribute('class', claseBotonOriginal)
    encabezado.remove()
    notaFestiva.remove()
    root.replaceChildren()
  }

  const modalOpen = () => !modal.classList.contains('hidden')
  const canAnimateHome = () => active && homeVisible && !document.hidden && !modalOpen() && !reducedMotion.matches
  const canAnimateChimes = () => canAnimateHome() && !mobile.matches
  const canAnimateSnow = () => active && modalOpen() && !document.hidden && !reducedMotion.matches

  const updatePlayback = () => {
    if (!active) return
    const [, leftChime, rightChime, leftSnow, rightSnow] = animations
    if (homeAnimation) {
      if (!canAnimateHome() || homeFinished) {
        homeAnimation.pause()
      } else if (!homeStarted && homeLoaded) {
        homeStarted = true
        homeAnimation.playSegments([0, 80], true)
      } else if (homeStarted) {
        homeAnimation.play()
      }
    }
    if (leftChime && rightChime) {
      canAnimateChimes() ? (leftChime.play(), rightChime.play()) : (leftChime.pause(), rightChime.pause())
    }
    if (leftSnow && rightSnow) {
      canAnimateSnow() ? (leftSnow.play(), rightSnow.play()) : (leftSnow.pause(), rightSnow.pause())
    }
  }

  const observer = new IntersectionObserver((entries) => {
    homeVisible = entries.some((entry) => entry.isIntersecting)
    updatePlayback()
  }, { root: main, threshold: 0.01 })
  observer.observe(home)

  const modalObserver = new MutationObserver(updatePlayback)
  modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] })
  document.addEventListener('visibilitychange', updatePlayback)
  reducedMotion.addEventListener('change', updatePlayback)
  mobile.addEventListener('change', updatePlayback)

  try {
    const lottie = await cargarLottie()
    if (!active) return limpiar

    const options = (container, file, loop, autoplay, preserveAspectRatio) => ({
      container,
      renderer: 'svg',
      loop,
      autoplay,
      path: `${baseUrl}animations/${file}?v=${encodeURIComponent(version)}`,
      rendererSettings: { preserveAspectRatio, progressiveLoad: true },
    })

    const mobileLayout = window.matchMedia('(max-width: 640px), (max-width: 1000px) and (pointer: coarse)').matches
    homeAnimation = lottie.loadAnimation(options(mainAnimation, mobileLayout ? 'merry-christmas-mobile.json' : 'merry-christmas.json', false, false, mobileLayout ? 'xMidYMin meet' : 'xMidYMax meet'))
    const chimeAnimations = campanillas.map((container) => lottie.loadAnimation(options(container, 'christmas-wind-chimes.json', true, false, 'xMidYMin meet')))
    const snowAnimations = snowHalves.map((container) => lottie.loadAnimation(options(container, 'snow-flakes-christmas.json', true, false, 'none')))
    animations.push(homeAnimation, ...chimeAnimations, ...snowAnimations)

    homeAnimation.addEventListener('DOMLoaded', () => {
      if (!active) return
      homeLoaded = true
      updatePlayback()
    })
    homeAnimation.addEventListener('complete', () => {
      homeFinished = true
      homeAnimation.goToAndStop(79, true)
    })

    updatePlayback()

    return limpiar
  } catch (error) {
    limpiar()
    throw error
  }
}
