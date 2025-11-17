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
