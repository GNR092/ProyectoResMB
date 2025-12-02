function Account() {
  return {
    async CambiarNombre() {
      const confirmed = await Confirmar(
        'Confirmar Cambio',
        '¿Estás seguro de que deseas cambiar tu nombre de usuario?',
      )

      if (!confirmed) {
        return
      }

      const formnombre = this.$refs.xUserForm
      const formmessage = this.$refs['form-message-user']
      formmessage.innerHTML = ''

      const formData = new FormData(formnombre)
      const username = formData.get('username')
      const correo = formData.get('email')

      const data = {
        method: 'POST',
        body: {
          email: correo,
          data: {
            username: username,
          },
        },
      }

      try {
        const result = await SendDataEnd('api/user/update', data)

        if (result.success) {
          formmessage.innerHTML = `<p class="text-green-600">${result.message}</p>`
        } else {
          const errorMessage = result.messages
            ? result.messages.error
            : result.message || 'Ocurrió un error desconocido.'
          formmessage.innerHTML = `<p class="text-red-500">${errorMessage}</p>`
        }
      } catch (error) {
        console.error('Error al cambiar nombre:', error)
        formmessage.innerHTML = `<p class="text-red-500">Error de conexión: ${error.message}</p>`
      }
    },
    async CambiarContrasena() {
      const confirmed = await Confirmar(
        'Confirmar Cambio',
        '¿Estás seguro de que deseas cambiar tu contraseña?',
      )

      if (!confirmed) {
        return
      }

      const form = this.$refs.xPassForm
      const messageContainer = this.$refs['form-message-pass']
      messageContainer.innerHTML = ''

      const formData = new FormData(form)
      const oldPassword = formData.get('old_password')
      const newPassword = formData.get('new_password')
      const correo = document.getElementById('email').value

      if (!oldPassword || !newPassword) {
        messageContainer.innerHTML = `<p class="text-red-500">Todos los campos son requeridos.</p>`
        return
      }
      if (newPassword.length < 8) {
        messageContainer.innerHTML = `<p class="text-red-500">La nueva contraseña debe tener al menos 8 caracteres.</p>`
        return
      }

      const data = {
        method: 'POST',
        body: {
          email: correo,
          data: {
            old_password: oldPassword,
            password: newPassword,
          },
        },
      }

      try {
        const result = await SendDataEnd('api/user/update', data)

        if (result.success) {
          messageContainer.innerHTML = `<p class="text-green-600">${result.message}</p>`
          form.reset()
        } else {
          const errorMessage = result.messages
            ? result.messages.error
            : result.message || 'Ocurrió un error desconocido.'
          messageContainer.innerHTML = `<p class="text-red-500">${errorMessage}</p>`
        }
      } catch (error) {
        console.error('Error al cambiar contraseña:', error)
        messageContainer.innerHTML = `<p class="text-red-500">Error de conexión: ${error.message}</p>`
      }
    },
    async CambiarContrasenaG() {
      const confirmed = await Confirmar(
        'Confirmar Cambio',
        '¿Estás seguro de que deseas cambiar tu contraseña auxiliar?',
      )

      if (!confirmed) {
        return
      }

      const form = this.$refs.xPassGForm
      const messageContainer = this.$refs['form-message-gpass']
      messageContainer.innerHTML = ''

      const formData = new FormData(form)
      const userPassword = formData.get('user_password')
      const newGPassword = formData.get('new_Gpassword')
      const correo = document.getElementById('email').value

      if (!userPassword || !newGPassword) {
        messageContainer.innerHTML = `<p class="text-red-500">Todos los campos son requeridos.</p>`
        return
      }
      if (newGPassword.length < 8) {
        messageContainer.innerHTML = `<p class="text-red-500">La nueva contraseña debe tener al menos 8 caracteres.</p>`
        return
      }

      const data = {
        method: 'POST',
        body: {
          email: correo,
          data: {
            user_password: userPassword,
            password_g: newGPassword,
          },
        },
      }

      try {
        const result = await SendDataEnd('api/user/update', data)

        if (result.success) {
          messageContainer.innerHTML = `<p class="text-green-600">${result.message}</p>`
          form.reset()
        } else {
          const errorMessage = result.messages
            ? result.messages.error
            : result.message || 'Ocurrió un error desconocido.'
          messageContainer.innerHTML = `<p class="text-red-500">${errorMessage}</p>`
        }
      } catch (error) {
        console.error('Error al cambiar contraseña auxiliar:', error)
        messageContainer.innerHTML = `<p class="text-red-500">Error de conexión: ${error.message}</p>`
      }
    },
    async uploadSignature() {
      const confirmed = await Confirmar(
        'Confirmar Cambio',
        '¿Estás seguro de subir la imagen de tu firma?',
      )

      if (!confirmed) {
        return
      }

      const form = this.$refs.xSignForm
      const messageContainer = this.$refs['form-message-sign']
      messageContainer.innerHTML = ''

      const fileInput = form.querySelector('#signature-file')
      const file = fileInput.files[0]

      if (!file) {
        messageContainer.innerHTML = `<p class="text-red-500">Por favor, seleccione un archivo.</p>`
        return
      }

      const formData = new FormData()
      formData.append('signature', file)

      const submitButton = form.querySelector('button[type="submit"]')
      submitButton.disabled = true
      submitButton.textContent = 'Subiendo...'

      try {
        const result = await SendDataEnd('api/user/upload_signature', {
          method: 'POST',
          body: formData,
          headers: {
            Accept: 'application/json',
          },
        })

        if (result.success) {
          messageContainer.innerHTML = `<p class="text-green-600">${result.message}</p>`
          // Update preview
          const preview = document.getElementById('signature-preview')
          const reader = new FileReader()
          reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Firma" class="h-full w-full object-contain">`
          }
          reader.readAsDataURL(file)
        } else {
          const errorMessage = result.messages
            ? result.messages.error
            : result.message || 'Ocurrió un error desconocido.'
          messageContainer.innerHTML = `<p class="text-red-500">${errorMessage}</p>`
        }
      } catch (error) {
        console.error('Error al subir la firma:', error)
        messageContainer.innerHTML = `<p class="text-red-500">Error de conexión: ${error.message}</p>`
      } finally {
        submitButton.disabled = false
        submitButton.textContent = 'Subir y/o Guardar'
      }
    },
  }
}
function crudUsuarios() {
  return {
    init() {
      setupClientSideTable({
        rowsSelector: '#tablaCrudUsuarios tr.usuario-row',
        paginationSelector: 'paginacion-crud-usuarios',
        filterFormSelector: '#div-lista-usuarios',
        filterFunction: (row, form) => {
          const termino = (form.querySelector('#buscarUsuario')?.value || '').toLowerCase()

          const nombre = row.querySelector('.nombre')?.textContent.toLowerCase() || ''
          const correo = row.querySelector('.correo')?.textContent.toLowerCase() || ''

          return nombre.includes(termino) || correo.includes(termino)
        },
        rowsPerPage: 10,
      })
    },

    editarUsuario(id) {
      const fila = document.querySelector(`#tablaCrudUsuarios tr[data-id='${id}']`)
      if (!fila) return

      document.getElementById('editar-ID_Usuario').value = id
      document.getElementById('editar-Nombre').value = fila.querySelector('.nombre').textContent
      document.getElementById('editar-Correo').value = fila.querySelector('.correo').textContent
      document.getElementById('editar-ID_Dpto').value =
        fila.querySelector('.departamento').dataset.idDpto
      document.getElementById('editar-ID_RazonSocial').value = fila.dataset.idRazonsocial
      document.getElementById('editar-Numero').value = fila.dataset.numero || ''
      document.getElementById('editar-ContrasenaP').value = '' // Limpiar campo de contraseña
      document.getElementById('editar-ContrasenaG').value = '' // Limpiar campo de contraseña
      document.getElementById('editar-ContrasenaP_confirm').value = ''
      document.getElementById('editar-ContrasenaG_confirm').value = ''

      document.getElementById('div-lista-usuarios').classList.add('hidden')
      document.getElementById('div-editar-usuario').classList.remove('hidden')
    },

    mostrarFormularioCrear() {
      document.getElementById('form-crear-usuario').reset()
      document.getElementById('div-lista-usuarios').classList.add('hidden')
      document.getElementById('div-crear-usuario').classList.remove('hidden')
    },

    regresarALista() {
      document.getElementById('div-editar-usuario').classList.add('hidden')
      document.getElementById('div-crear-usuario').classList.add('hidden')
      document.getElementById('div-lista-usuarios').classList.remove('hidden')
    },

    async guardarCambiosUsuario() {
      const id = document.getElementById('editar-ID_Usuario').value
      const nombre = document.getElementById('editar-Nombre').value
      const correo = document.getElementById('editar-Correo').value
      const idDpto = document.getElementById('editar-ID_Dpto').value
      const idRazonSocial = document.getElementById('editar-ID_RazonSocial').value
      const numero = document.getElementById('editar-Numero').value
      const contrasena = document.getElementById('editar-ContrasenaP').value
      const contrasenaG = document.getElementById('editar-ContrasenaG').value
      const contrasenaConfirm = document.getElementById('editar-ContrasenaP_confirm').value
      const contrasenaGConfirm = document.getElementById('editar-ContrasenaG_confirm').value

      const data = {
        Nombre: nombre,
        Correo: correo,
        ID_Dpto: idDpto,
        Numero: numero,
        ID_RazonSocial: idRazonSocial,
      }

      if (contrasena) {
        if (contrasena !== contrasenaConfirm) {
          mostrarNotificacion('Las contraseñas de Jefe no coinciden.', 'error')
          return
        }
        data.ContrasenaP = contrasena
      }

      if (contrasenaG) {
        if (contrasenaG !== contrasenaGConfirm) {
          mostrarNotificacion('Las contraseñas de Empleado no coinciden.', 'error')
          return
        }
        data.ContrasenaG = contrasenaG
      }

      try {
        const result = await SendDataEnd(`modales/actualizarUsuario/${id}`, {
          method: 'POST',
          body: data,
        })

        if (result.success) {
          mostrarNotificacion(result.message, 'success')
          // Actualizar la fila en la tabla
          const fila = document.querySelector(`#tablaCrudUsuarios tr[data-id='${id}']`)
          if (fila) {
            fila.querySelector('.nombre').textContent = nombre
            fila.querySelector('.correo').textContent = correo
            const select = document.getElementById('editar-ID_Dpto') // Aquí se obtiene el texto completo (Depto + Lugar)
            const deptoText = select.options[select.selectedIndex].text
            fila.querySelector('.departamento').textContent = deptoText
            fila.dataset.numero = numero
            fila.querySelector('.departamento').dataset.idDpto = idDpto
          }
          this.regresarALista()
        } else {
          const errorMsg = result.errors ? Object.values(result.errors).join('\n') : result.message
          mostrarNotificacion(errorMsg, 'error')
        }
      } catch (error) {
        console.error('Error al actualizar usuario:', error)
        mostrarNotificacion('Error de conexión al actualizar.', 'error')
      }
    },

    async guardarNuevoUsuario() {
      const nombre = document.getElementById('crear-Nombre').value
      const correo = document.getElementById('crear-Correo').value
      const idDpto = document.getElementById('crear-ID_Dpto').value
      const idRazonSocial = document.getElementById('crear-ID_RazonSocial').value
      const numero = document.getElementById('crear-Numero').value
      const contrasena = document.getElementById('crear-ContrasenaP').value
      const contrasenaG = document.getElementById('crear-ContrasenaG').value
      const contrasenaConfirm = document.getElementById('crear-ContrasenaP_confirm').value
      const contrasenaGConfirm = document.getElementById('crear-ContrasenaG_confirm').value

      if (contrasena.length < 8) {
        mostrarNotificacion('La contraseña de Jefe debe tener al menos 8 caracteres.', 'error')
        return
      }

      if (contrasena !== contrasenaConfirm) {
        mostrarNotificacion('Las contraseñas de Jefe no coinciden.', 'error')
        return
      }

      // Validar ContraseñaG solo si se ha introducido
      if (contrasenaG) {
        if (contrasenaG.length < 8) {
          mostrarNotificacion(
            'La contraseña de Empleado debe tener al menos 8 caracteres.',
            'error',
          )
          return
        }
        if (contrasenaG !== contrasenaGConfirm) {
          mostrarNotificacion('Las contraseñas de Empleado no coinciden.', 'error')
          return
        }
      }

      const data = {
        Nombre: nombre,
        Correo: correo,
        ID_Dpto: idDpto,
        Numero: numero,
        ID_RazonSocial: idRazonSocial,
        ContrasenaP: contrasena,
        ContrasenaG: contrasenaG,
      }

      try {
        const result = await SendDataEnd('modales/registrarUsuario', {
          method: 'POST',
          body: data,
        })

        if (result.success) {
          mostrarNotificacion(result.message, 'success')

          // Añadir dinámicamente la nueva fila a la tabla
          const tablaBody = document.getElementById('tablaCrudUsuarios')
          const selectDepto = document.getElementById('crear-ID_Dpto') // Aquí se obtiene el texto completo (Depto + Lugar)
          const deptoText = selectDepto.options[selectDepto.selectedIndex].text
          const iconUrl = `/icons/icons.svg?v=${window.ICON_SVG_VERSION || new Date().getTime()}`

          const nuevaFila = `
            <tr data-id="${result.user.ID_Usuario}" class="usuario-row" data-numero="${numero}" data-id-razonsocial="${idRazonSocial}">
              <td class="py-2 px-4 border-b nombre">${result.user.Nombre}</td>
              <td class="py-2 px-4 border-b correo">${result.user.Correo}</td>
              <td class="py-2 px-4 border-b departamento" data-id-dpto="${result.user.ID_Dpto}">${deptoText}</td>
              <td class="py-2 px-4 border-b text-center">
                <button @click="editarUsuario(${result.user.ID_Usuario})" class="text-blue-600 hover:text-blue-800" title="Editar">
                  <svg class="h-5 w-5 inline" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="${iconUrl}#editar"></use></svg>
                </button>
                <button @click="eliminarUsuario(${result.user.ID_Usuario})" class="text-red-600 hover:text-red-800 ml-2" title="Eliminar">
                  <svg class="h-5 w-5 inline" fill="none" stroke-width="1.5" stroke="currentColor"><use xlink:href="${iconUrl}#eliminar-fila"></use></svg>
                </button>
              </td>
            </tr>`

          tablaBody.insertAdjacentHTML('beforeend', nuevaFila)
          this.regresarALista()
        } else {
          const errorMsg = result.errors ? Object.values(result.errors).join('\n') : result.message
          mostrarNotificacion(errorMsg, 'error')
        }
      } catch (error) {
        console.error('Error al registrar usuario:', error)
        mostrarNotificacion('Error de conexión al registrar.', 'error')
      }
    },

    async eliminarUsuario(id) {
      if (
        !(await Confirmar(
          'Eliminar Usuario',
          '¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.',
        ))
      ) {
        return
      }

      try {
        const result = await SendDataEnd(`modales/eliminarUsuario/${id}`, {
          method: 'POST',
        })
        if (result.success) {
          mostrarNotificacion(result.message, 'success')
          document.querySelector(`#tablaCrudUsuarios tr[data-id='${id}']`)?.remove()
        } else {
          mostrarNotificacion(result.message, 'error')
        }
      } catch (error) {
        console.error('Error al eliminar usuario:', error)
        mostrarNotificacion('Error de conexión al eliminar.', 'error')
      }
    },
  }
}