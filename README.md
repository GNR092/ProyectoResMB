# Sistema de Compras

Una de las características clave es su **asistente de instalación web**, que simplifica enormemente la configuración inicial de la base de datos y el entorno, haciendo que el despliegue sea rápido y sin complicaciones.

## ✨ Características Principales

- **Asistente de Instalación Web**: Configura la base de datos (PostgreSQL), crea el usuario, actualiza el archivo `.env` y ejecuta las migraciones automáticamente desde el navegador.
- **Backend Robusto**: Construido sobre CodeIgniter 4, un framework PHP potente y ligero.
- **Frontend Moderno**: Interfaz de usuario estilizada con Tailwind CSS v4 y componentes dinámicos gracias a Alpine.js.
- **Seguridad**: El instalador se bloquea automáticamente después del primer uso.
- **Base de Datos**: Diseñado para funcionar con PostgreSQL.
- **Entorno de Desarrollo**: Scripts preconfigurados para compilar CSS y servir la aplicación localmente.

## 🚀 Tecnologías Utilizadas

- **Backend**:
  - PHP 8.1+
  - CodeIgniter 4
  - Composer
- **Frontend**:
  - Tailwind CSS v4
  - Alpine.js v3
  - PostCSS
  - npm
- **Base de Datos**:
  - PostgreSQL

## 📋 Requisitos Previos

Tener instalado lo siguiente en tu entorno de desarrollo:

- PHP 8.1 o superior
- Composer
- Node.js y npm
- Un servidor de base de datos PostgreSQL en ejecución.

## 📄 Licencia

Este proyecto está bajo la Licencia ISC. Consulta el archivo `package.json` para más detalles.

## 🔧 Guía de Uso

Sigue estos pasos para poner en marcha el entorno de desarrollo.

### 1. Instalar Dependencias

Asegúrate de tener Composer y Node.js instalados. Luego, ejecuta los siguientes comandos en la raíz del proyecto:

```bash
# Instalar dependencias de PHP
composer install

# Instalar dependencias de Node.js
npm install
```

### 2. Iniciar los Servidores

El proyecto requiere dos procesos para funcionar en modo de desarrollo:

1. **Servidor de PHP (CodeIgniter)**: Se encarga de procesar el backend.

2. **Compilador de CSS (Tailwind)**: Vigila los cambios en los archivos de estilo y los compila automáticamente.

Abre dos terminales separadas en la raíz del proyecto y ejecuta los siguientes comandos:

#### **Terminal 1: Iniciar el servidor de CodeIgniter**

```bash
php spark serve
```

La aplicación estará disponible en `http://localhost:8080`.

#### **Terminal 2: Compilar CSS en modo "watch"**

```bash
npm run watch
```

Este comando mantendrá un proceso activo que compilará `input.css` a `public/css/styless.css` cada vez que guardes un cambio.

### 3. Build de Producción

Para generar los archivos de CSS minificados para producción, utiliza el siguiente comando:

```bash
npm run build:product
```
