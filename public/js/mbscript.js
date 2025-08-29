/**
 * getData: Función para obtener datos de una API RESTful
 * Utiliza fetch para hacer solicitudes HTTP y maneja errores.
 * @param { * } endpoint - La ruta del endpoint (sin la parte base de la URL)
 * @param {*} option  - Opciones para la solicitud fetch (método, headers, body, etc.)
 * @param {*} api   - Indica si se debe usar la ruta de la API o no
 * @returns  - Los datos obtenidos de la API o un array vacío en caso de error
 */
async function getData(endpoint, option = {}, api = true) {
  let apiUrl = api ? `${BASE_URL}api/${endpoint}` : `${BASE_URL}${endpoint}`
  let response
  try {
    console.log(`Intentando cargar desde: ${apiUrl}`)
    response = option ? await fetch(apiUrl, option) : await fetch(apiUrl)

    if (!response.ok) {
      throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`)
    }

    const data = await response.json()

    return data
  } catch (error) {
    console.error('Hubo un error al obtener los datos:', error)
    return []
  }
}
async function loadHistoric()
{
  try {
    const data = await getData('historic');
    if(data.length > 0)
    {

    }
    else{
      console.error('Los datos recibidos no son un array:', data);
    }
  } catch (error) {
    console.error('Hubo un error al obtener los departamentos:', error)
  }
}
/**
 * loadRazonSocialProv: Función para cargar las opciones de razón social desde la API
 * y agregarlas a un elemento <select> en el DOM.
 */
async function loadRazonSocialProv() {
  const razonSocialSelect = document.getElementById('razonSocialSelect')
  try {
    const data = await getData('providers/all')
    console.log('Datos recibidos:', data)
    if (data.length > 0) {
      razonSocialSelect.innerHTML = '<option value="">Seleccione una opción</option>'
      data.forEach((provider) => {
        let option = document.createElement('option')
        option.value = provider.ID_Proveedor
        option.textContent = provider.Nombre
        razonSocialSelect.appendChild(option)
      })
    } else {
      console.error('Los datos recibidos no son un array:', data)
    }
  } catch (error) {
    console.error('Hubo un error al obtener los departamentos:', error)
  }
}

async function loadDepartamentos() {
  const departamentosSelect = document.getElementById('departamento')
  try {
    const data = await getData('departments/all')
    console.log('Departamentos cargados: ', data)
    if (data.length > 0) {
      departamentosSelect.innerHTML = '<option value="">Seleccione un departamento</option>'
      data.forEach((departaments) => {
        let option = document.createElement('option')
        option.value = departaments.ID_Dpto
        option.textContent = departaments.Nombre + ' ' + departaments.Place
        departamentosSelect.appendChild(option)
      })
    } else {
      console.error('Los datos recibidos no son array: ', data)
    }
  } catch (error) {
    console.error(error)
  }
}

/**
 * SendData: Función para manejar el envío del formulario de manera asíncrona
 * @param {*} event - El evento de envío del formulario
 */
async function SendData(event) {
  event.preventDefault()

  const formulario = event.target
  const formData = new FormData(formulario)

  const messageContainer = document.getElementById('mensajes-form')
  const submitButton = document.getElementById('btn-enviar')

  if (submitButton) {
    submitButton.disabled = true
    const buttonTextSpan = submitButton.querySelector('span')
    if (buttonTextSpan) {
      buttonTextSpan.textContent = 'Enviando...'
    } else {
      submitButton.textContent = 'Enviando...'
    }
  }

  if (messageContainer) {
    messageContainer.innerHTML = ''
  }

  try {
    const data = await getData(
      'solicitudes/registrar',
      {
        method: 'POST',
        body: formData,
        headers: { Accept: 'application/json' },
      },
      false,
    )

    if (data.success) {
      if (messageContainer) {
        messageContainer.innerHTML = `<p class="text-green-600">${data.message}</p>`
      }
      formulario.reset()
    } else {
      let erroresHtml = ''
      if (data.errors) {
        for (const key in data.errors) {
          erroresHtml += `<li>${data.errors[key]}</li>`
        }
      } else {
        erroresHtml = `<li>${data.message || 'Ocurrió un error desconocido.'}</li>`
      }
      if (messageContainer) {
        messageContainer.innerHTML = `<ul class="list-disc list-inside text-red-600">${erroresHtml}</ul>`
      }
    }
  } catch (error) {
    console.error('Error en el envío del formulario:', error)
    if (messageContainer) {
      messageContainer.innerHTML = `<p class="text-red-600">Ocurrió un error de red. Por favor, intente de nuevo.</p>`
    }
  } finally {
    if (submitButton) {
      submitButton.disabled = false
      const buttonTextSpan = submitButton.querySelector('span')
      if (buttonTextSpan) {
        buttonTextSpan.textContent = 'Enviar'
      } else {
        submitButton.textContent = 'Enviar'
      }
    }
  }
}
