# INFORME TÉCNICO COMPLETO DEL SISTEMA DE COMPRAS Y REQUISICIONES
## MBSP - Sistema Integral de Administración de Compras

---

# ÍNDICE GENERAL

1. Arquitectura General del Sistema
2. Esquema de Base de Datos
3. Modelos y Relaciones
4. Controladores y su Propósito
5. Vistas y Frontend
6. Librerías y Servicios
7. Configuración y Rutas
8. Flujo de Negocio Completo
9. Estados del Sistema
10. Roles y Permisos
11. Sistema de Auditoría
12. Tecnologías y Stack

---

# 1. ARQUITECTURA GENERAL DEL SISTEMA

##1.1 Descripción General

El sistema MBSP es una aplicación web construida sobre **CodeIgniter 4** (framework PHP) que funciona como un ERP de compras y requisiciones para organizaciones con múltiples razones sociales, complejos y departamentos. Permite gestionar todo el ciclo de vida de una requisición: desde la solicitud de material o servicio por parte de un empleado, pasando por la cotización y aprobación, hasta la generación de órdenes de compra, programación de pagos y control presupuestal.

El sistema está diseñado con una arquitectura de **tres capas principales**:

- **Capa de Presentación**: Vistas PHP con JavaScript vanilla y Alpine.js para componentes reactivos. Usa Tailwind CSS compilado.
- **Capa de Lógica de Negocio**: Controladores que procesan requests, validan datos y orquestan operaciones. La librería central `Rest` actúa como fachada de acceso a datos.
- **Capa de Datos**: Modelos CodeIgniter que mapean tablas de MySQL/PostgreSQL con un trait de auditoría (`AuditTrait`) que registra automáticamente cambios en la bitácora.

##1.2 Estructura de Directorios

```
app/
├── Config/          # Archivos de configuración de CodeIgniter
├── Controllers/      # Controladores MVC
├── Database/
│   └── Migrations/  # Migraciones de esquema de BD
├── Filters/         # Filtros HTTP (Auth, Mantenimiento, HSTS)
├── Libraries/       # Clases de negocio y utilidad
├── Models/          # Modelos de datos
├── Traits/          # Traits reutilizables (AuditTrait)
└── Views/           # Vistas PHP
    ├── auth/ # Login
    ├── layout/      # Layout principal
    ├── modales/     # Modales dinámicos
    └── emails/      # Plantillas de correo
public/
├── css/             # Tailwind compilado
├── js/              # JavaScript principal
├── icons/           # Sprite SVG de iconos
└── images/          # Imágenes y logos
writable/
├── uploads/         # Archivos subidos por usuarios
├── session/         # Sesiones PHP (FileHandler)
└── cache/           # Cache de la aplicación
```

## 1.3 Patrones de Diseño Observados

El sistema utiliza varios patrones reconocidos:

- **Facade**: La librería `Rest` actúa como punto de acceso único a todas las operaciones de datos, encapsulando consultas complejas con joins.
- **Singleton**: `BitacoraService` usa el patrón Singleton para acumular eventos en memoria y persistirlos al final de cada request.
- **Trait-based Audit**: El `AuditTrait` se mezcla en modelos y proporciona callbacks que disparan eventos de auditoría sin duplicar código.
- **Resource Controller**: `Api.php` extiende `CodeIgniter\RESTful\ResourceController` para crear endpoints RESTful con formato JSON.
- **Service Layer**: Los controladores delegan lógica de negocio a la librería `Rest` en lugar de直接在 el controlador.

## 1.4 Instalación e Inicialización

El sistema tiene un **mecanismo de instalación** basado en el archivo `writable/installer.lock`:

- **Sin archivo lock**: El sistema muestra el instalador web y no permite acceso a la aplicación.
- **Con archivo lock**: El sistema está instalado y muestra la portada/login.

El instalador (`Installer.php`) soporta MySQL y PostgreSQL, crea la base de datos si no existe, ejecuta todas las migraciones, actualiza el archivo `.env` con las credenciales, y crea el archivo de lock al finalizar exitosamente.

---

# 2. ESQUEMA DE BASE DE DATOS

## 2.1 Vision General

La base de datos está organizada en **ocho módulos funcionales** que reflejan las áreas de negocio del sistema. El diseño permite multi-tenant (múltiples razones sociales con estructuras separadas) y control presupuestal por unidad operativa y grupo presupuestal.

Todas las tablas tienen un identificador con formato inconsistente: algunas usan `ID_Prefijo` (ej. `ID_Solicitud`), otras usan `id` a secas (ej. `id` en `bitacora`). Esto es una observación de diseño, no un error funcional.

## 2.2 Módulo: Catálogos Base

### Places (Complejos/Condominios)

La tabla `Places` representa los complejos o condominios que pertenecen a una razón social. Es el nivel superior de la jerarquía organizacional.

Cada Place tiene un nombre corto (para mostrarse en combos y selectores), un nombre completo, y puede pertenecer a un segmento de negocio específico. Un Place está activo o inactivo.

**Relaciones**: Un Place puede tener múltiples Departamentos, Unidades Operativas, Bancos, Grupos Presupuestales y Usuarios.

### Razon_Social

Representa las diferentes empresas o entidades legales que operan en el sistema. Cada razón social tiene RFC, ubicación, nombre comercial y dirección.

**Relaciones**: Una Razón Social puede tener múltiples Places, Usuarios, Presupuestos Anuales, Bancos, Proveedores y Segmentos de Negocio.

### Departamentos

Los departamentos están organizados jerárquicamente bajo una Unidad Operativa y/o un Place. Un departamento tiene un nombre que se usa para determinar permisos del usuario.

**Relaciones**: Un Departamento puede tener múltiples Usuarios y Solicitudes.

### UnidadOperativa

Este nivel jerárquico fue añadido en una migración posterior para separar la lógica de Places. Permite agrupar departamentos dentro de un Place para control presupuestal más granular.

**Relaciones**: Una Unidad Operativa puede tener múltiples Departamentos, Grupos Presupuestales y Presupuestos Mensuales.

### segmento_negocio

Clasifica los Places y productos por segmento de negocio (ej. Renta, Hotel, Servicios). Esto permite filtrar catálogos y reportes por segmento.

## 2.3 Módulo: Usuarios y Autenticación

### Usuarios

Cada usuario pertenece a un departamento y a una razón social. El sistema maneja **dos contraseñas por usuario**:

- **ContrasenaP (Contraseña Personal)**: Para el usuario principal (jefe de departamento). Usada cuando el checkbox "Acceso Auxiliar" no está marcado.
- **ContrasenaG (Contraseña General)**: Para auxiliares/empleados. Usada cuando el checkbox "Acceso Auxiliar" está marcado.

Esto permite que un mismo usuario pueda iniciar sesión como jefe (con plenos poderes de aprobación) o como empleado (con permisos limitados), usando contraseñas distintas.

La tabla también almacena la firma digital del usuario (para firmar PDF de órdenes de compra) y un número de contacto.

### User_Tokens

Tabla que almacena tokens API por usuario. Aunque existe este mecanismo de tokens, el sistema actual autentica principalmente por sesión PHP (cookie `ci_session`). Los tokens parecen ser un residuo de una estrategia de API que no se terminó de implementar completamente.

## 2.4 Módulo: Proveedores

### Proveedor

La tabla de proveedores almacena la información fiscal y bancaria de cada proveedor. Los campos incluyen: razón social, correo, RFC, banco, cuenta bancaria, CLABE interbancaria, días de crédito, monto de crédito, servicio que ofrece y datos de contacto.

Un detalle de diseño notable: cuando se crea un proveedor, el modelo `ProveedorModel` tiene un callback `afterInsert` que automáticamente inserta la cuenta bancaria principal en la tabla `Cuentas`. Esto asegura que al crear un proveedor ya exista al menos una cuenta registrada.

### Cuentas

Permite registrar múltiples cuentas bancarias por proveedor. Esto es útil para proveedores que tienen diferentes cuentas para diferentes tipos de operación.

### MapeoProductos

Esta tabla vincula los productos internos del sistema con los identificadores que el proveedor usa en sus facturas XML (CFDI). Incluye un factor de conversión para manejar diferencias de unidad de medida entre el sistema y el proveedor.

### proveedor_archivos

Almacena archivos adjuntos de proveedores (documentos legales, certificaciones, contratos). Los archivos se numeran secuencialmente por proveedor.

## 2.5 Módulo: Inventario

### Producto

Tabla simple con código único, nombre y existencia actual. La existencia se actualiza cuando se registran entradas (recepciones de material) o salidas (entregas a departamentos).

### HistorialProductos

Tabla de auditoría que registra cada cambio en un producto: código anterior/nuevo, nombre anterior/nuevo, existencia anterior/nueva, usuario que hizo el cambio y razón del cambio.

### Entregas y DetalleEntrega

Registra las salidas de material del almacén. Cada entrega tiene un usuario emisor, departamento receptor, nombre de quien recibe, fecha y una lista de productos entregados con cantidades.

### Ingresos y DetalleIngreso

Registra las entradas de mercancía al almacén, típicamente a partir de facturas XML (CFDI) de proveedores. Cada ingreso tiene un UUID único (para evitar duplicados), RFC del receptor, fecha de emisión y archivo XML del comprobante.

Los detalles del ingreso registran la cantidad original del XML y la cantidad que realmente se recibió (puede haber diferencias por mermas o daños).

## 2.6 Módulo: Solicitudes

### Solicitud

Esta es la tabla central del sistema. Representa cada requisición creada por un usuario. Los campos principales son:

- **ID_Usuario**: Quién creó la solicitud.
- **ID_Dpto / ID_UnidadOperativa**: Departamento y unidad operativa solicitante.
- **ID_Proveedor**: Proveedor seleccionado (puede ser nulo inicialmente).
- **ID_RazonSocial**: La empresa/razón social que procesa la solicitud.
- **Tipo**: 0 = producto con cotización, 1 = producto sin cotización, 2 = servicio.
- **MetodoPago**: 0 = efectivo/contado, 1 = crédito, 9 = en espera.
- **IVA**: Booleano para indicar si se aplica IVA.
- **Estado**: Estado actual de la solicitud (ver sección de estados).
- **No_Folio**: Identificador único con formato "MBSP-{ID}".
- **Archivo**: Archivo de la solicitud original (cotización escaneada u otro).
- **ComentariosAdmin / ComentariosUser**: Notas del administrador y del usuario.
- **Fecha_Aprobacion**: Cuándo fue aprobada.
- **ID_Usuario_Autoriza**: Quién autorizó la solicitud.

### Solicitud_Producto

Los productos asociados a una solicitud de tipo material. Cada registro incluye: código, nombre, cantidad, importe unitario, grupo presupuestal asociado, y el monto originalmente comprometido (para comparar contra cambios posteriores).

### Solicitud_Servicios

Similar a productos pero para servicios. Los servicios pueden tener nombre descriptivo e importe.

## 2.7 Módulo: Cotizaciones y Compras

### Cotizacion

Se genera cuando el departamento de Compras registra las cotizaciones de proveedores para una solicitud. Cada cotización pertenece a una solicitud y a un proveedor, tiene un total calculado y archivos adjuntos (cotizaciones escaneadas).

### OrdenCompra

Se genera automáticamente cuando una solicitud es aprobada. Representa la orden formal de compra al proveedor. Contiene referencias a la cotización seleccionada y múltiples campos de archivos para adjuntar: factura, comprobante de pago, requerimiento de pago, remisión, factura de entrada, archivos XML y PDF de facturas de servicio, y complemento de pago.

##2.8 Módulo: Pagos

### Pago

Registra cada pago asociado a una orden de compra. Los campos incluyen: tipo de pago, fecha de solicitud, fecha efectiva de pago, folio único, concepto y forma de pago.

##2.9 Módulo: Finanzas y Presupuestos

### GrupoPresupuestal

Las partidas presupuestales son el nivel más granular del control presupuestal. Cada grupo tiene nombre, descripción, pertenece a una unidad operativa, y tiene flags de `activo` (para desactivar sin eliminar) y `es_manual` (para marcar grupos que no se auto-asignan desde el catálogo).

### PresupuestoMensual

Controla el presupuesto mensual por unidad operativa y grupo presupuestal. Tiene tres montos: asignado (presupuesto base), comprometido (reservado para solicitudes aprobadas) y ejecutado (gastado en pagos realizados).

### PresupuestoAnual

Similar al mensual pero a nivel de razón social y año completo. Solo tiene un monto total anual.

### BancoDpto

Cuentas bancarias asociadas a una razón social. Almacena: CLABE, banco, alias, número de cuenta y sucursal.

### SaldosBancarios

Registra el saldo inicial y final de cada cuenta bancaria por mes y año. Esto permite llevar un control de efectivo disponible.

### SolicitudesCambioPresupuesto

Sistema de workflow para solicitar cambios en el presupuesto. Permite al usuario enviar una solicitud de ajuste que incluye: el módulo afectado, la acción a realizar, el ID del registro afectado, los datos antes y después del cambio, estado de la solicitud (Pendiente/Aprobada/Rechazada), y comentarios del solicitante y del revisor.

## 2.10 Módulo: Catálogo de Productos

### Catalogo_Productos

El catálogo maestro de productos y servicios disponibles en el sistema. A diferencia de la tabla `Producto` (inventario), el catálogo define qué productos pueden solicitarse, organizados por razón social, segmento, lugar, unidad operativa y grupo presupuestal.

### usuarios_productos_favoritos

Permite a cada usuario marcar productos del catálogo como favoritos, con un alias personal y contador de frecuencia de uso. Esto prioriza los productos más usados en el autocomplete.

## 2.11 Módulo: Auditoría

### bitacora

Tabla genérica de auditoría sin llaves foráneas activas. Registra: usuario, nombre, departamento, tipo de acción, clasificación, módulo, IDs de contexto (solicitud, orden, cotización), dirección IP, valores antiguos y nuevos (en JSON), y estado (éxito/fallido).

Es importante notar que `bitacora` no tiene relaciones activas con otras tablas a nivel de base de datos (no hay FK), lo que permite registrar cualquier tipo de evento sin restricciones de integridad referencial.

## 2.12 Integridad Referencial

Hay varias observaciones importantes sobre las llaves foráneas en este sistema:

**Llaves foráneas no creadas**: En la migración original de `Solicitud`, los campos `ID_Dpto` e `ID_Proveedor` están comentados y nunca se crearon como FK. Esto permite que existan solicitudes con referencias a departamentos o proveedores que ya no existen.

**Inconsistencia de tipos**: Los tipos de datos de las llaves primarias varían: `BIGINT`, `INT`, `INT UNSIGNED` — sin un estándar claro. Esto puede causar problemas de casting en comparaciones de joins.

**Ausencia de soft deletes**: Ningún modelo tiene `useSoftDeletes = true`. Las eliminaciones son físicas, lo cual también elimina los registros de auditoría de contexto si se borra un registro padre.

---

# 3. MODELOS Y RELACIONES

## 3.1 Inventario de Modelos

El sistema cuenta con **28 modelos** en `app/Models/`. La mayoría extiende `CodeIgniter\Model` y utiliza el trait `AuditTrait` para registrar automáticamente cambios en la bitácora.

### Modelos con AuditTrait (23 modelos)

Estos modelos registran automáticamente cuando se inserta, actualiza o elimina un registro:

- UsuariosModel, SolicitudModel, SolicitudProductModel, SolicitudServiciosModel, CotizacionModel, OrdenCompraModel, PagoModel, ProductoModel, ProveedorModel, DepartamentosModel, GrupoPresupuestalModel, PresupuestoMensualModel, PresupuestoAnualModel, RazonSocialModel, PlacesModel, UnidadOperativaModel, CatalogoProductosModel, CuentasModel, HistorialProductosModel, IngresosModel, SolicitudesCambioPresupuestoModel, SaldosBancariosModel, UsuarioProductoFavoritoModel.

### Modelos sin AuditTrait (5 modelos)

- TokenModel: Tokens API (no necesita auditoría).
- BitacoraModel: Es el receptor de la auditoría, no el generador.
- SegmentoNegocioModel: Datos de clasificación.
- BancoDptoModel: Datos bancarios.
- ProveedorArchivosModel: Archivos adjuntos.

### Modelos adicionales (sin archivo de modelo en app/Models/)

- DetalleEntregaModel, DetalleIngresoModel, DetalleServicioModel, MapeoProductosModel: También existen en la base de datos pero no tienen archivo de modelo dedicado.

## 3.2 Relaciones Definidas en la Librería Rest

Las relaciones entre entidades no están definidas en los modelos (no hay `belongsTo`, `hasMany`, etc. en los modelos), sino que se manejan a nivel de consultas SQL en la librería `Rest`. Esto significa que la integridad referencial en tiempo de ejecución depende del código PHP y no de la base de datos.

Por ejemplo, `getAllSolicitud()` hace un JOIN con Departamentos, Places, Proveedor y Razon_Social:

```sql
SELECT Solicitud.*, Departamentos.Nombre, Places.Nombre_Corto,
       Proveedor.RazonSocial, Razon_Social.Nombre
FROM Solicitud
LEFT JOIN Departamentos ON Departamentos.ID_Dpto = Solicitud.ID_Dpto
LEFT JOIN Places ON Places.ID_Place = Departamentos.ID_Place
LEFT JOIN Proveedor ON Proveedor.ID_Proveedor = Solicitud.ID_Proveedor
LEFT JOIN Razon_Social ON Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial
```

##3.3 Callbacks y Normalizaciones Especiales

Algunos modelos tienen lógica adicional en sus callbacks:

**SolicitudModel**: Normaliza `ID_UnidadOperativa` a null si viene vacío, cero o string "0". Esto evita problemas al guardar valores que no son numéricos.

**PlacesModel**: Normaliza `id_segmento` e `ID_UnidadOperativa` a null si vienen vacíos. También tiene validación `is_unique` en `Nombre_Corto`.

**ProveedorModel**: Después de insertar un proveedor, el callback `afterInsert` automáticamente inserta la cuenta bancaria principal en la tabla `Cuentas`. Esto asegura que el proveedor ya tenga una cuenta disponible al momento de ser usado.

**IngresosModel**: Tiene validación `is_unique` en `UUID` para evitar registrar dos veces la misma factura.

---

# 4. CONTROLADORES Y SU PROPÓSITO

## 4.1 Inventario de Controladores

El sistema tiene **12 controladores** principales:

### Auth.php (125 líneas)

Es el controlador de autenticación. Maneja tres acciones:

- **index()**: Muestra el formulario de login.
- **login()**: Procesa el POST del formulario. Busca usuario por correo, verifica la contraseña contra `ContrasenaP` (jefe) o `ContrasenaG` (empleado) según el checkbox `login_as_employee`. Si es exitoso, genera token API, carga datos de sesión (id, nombre, departamento, tipo de login) y redirige a Home. Si falla, registra en bitácora y muestra error.
- **logout()**: Registra en bitácora, nulifica el token del usuario, destruye la sesión PHP y redirige a /auth.

### Home.php (308 líneas)

Es el controlador de la página principal. Su responsabilidad principal es construir el menú dinámico según los permisos del usuario:

- Define un array `permisosPorDepto` que asigna qué opciones del menú ve cada departamento (Administración, Compras, Dirección, Tesorería, Almacén, Dirección Campus, Presupuestos, Contaduría).
- Si el usuario tiene `login_type === 'boss'` y no es de Dirección/Compras/Tesorería, se le agrega el permiso `aprobar_solicitudes`.
- Filtra las opciones disponibles según el departamento del usuario.
- También filtra las opciones de "Ajustes" (catálogos) según permisos.
- Renderiza la vista `inicio.php` con las opciones calculadas.

### Api.php (~4,000 líneas)

Es el controlador más grande y el más importante del sistema. Extiende `ResourceController` de CodeIgniter para APIs RESTful con formato JSON. Está organizado en regiones temáticas:

**Región de Productos**: Búsqueda, listado, detalle por ID.

**Región de Proveedores**: Listado completo, listado resumido (ID y nombre), exportación a Excel.

**Región de Departamentos**: Listado general, grupos presupuestales por unidad operativa, presupuestos mensuales.

**Región de Solicitudes (Consultas)**: Historial general, historial por departamento, detalles de solicitud, solicitudes cotizadas, solicitudes en revisión, solicitudes pendientes de aprobación por jefe.

**Región de Solicitudes (Acciones)**: Cancelar solicitud, actualizar montos, dictaminar por jefe, aprobar y cotizar, crear cotizaciones masivas, enviar a revisión, dictaminar por dirección.

**Región de Órdenes de Compra**: Generación de orden, listado completo, datos por ID, consulta por ID de solicitud, órdenes pendientes de programación, facturas por pagar, órdenes pendientes de recepción, cambiar estado, enviar a proveedor, enviar a tesorería, programar pagos.

**Región de Catálogo**: Filtrado dinámico, búsqueda asíncrona, favoritos, CRUD completo.

**Región de Archivos**: Listado de storage, servir archivos para preview, descarga como ZIP.

**Región de Reportes**: Exportación de requisiciones, historial y movimientos a Excel.

**Región de Recepción/Bajas**: Confirmación de recepción de productos, registro de bajas por destrucción.

**Región de Usuarios**: Actualización de datos de usuario, subida de firma digital.

**Región de Correo**: Prueba de conexión de correo.

### Modales.php (~1,800 líneas)

Es el controlador más extenso en número de líneas. Gestiona todas las vistas modales del sistema, que son alrededor de 40 modales diferentes. Tiene dos tipos de métodos:

**Renderizado de vistas**: Un solo método `mostrar($opcion)` que usa un switch para retornar la vista correcta según la opción solicitada. Cada caso carga una vista diferente.

**CRUDs**: Métodos individuales para cada entidad: `registrarUsuario`, `actualizarUsuario`, `eliminarUsuario`, `insertarProveedor`, `editarProveedor`, `eliminarProveedor`, `insertarPlace`, `editarPlace`, `eliminarPlace`, `insertarDepartamento`, `editarDepartamento`, `eliminarDepartamento`, `insertarCuenta`, `actualizarCuenta`, `eliminarCuenta`, `insertarGrupo`, `editarGrupo`, `eliminarGrupo`, `insertarBancoDpto`, `editarBancoDpto`, `eliminarBancoDpto`, `insertarUnidadOperativa`, `editarUnidadOperativa`, `eliminarUnidadOperativa`, `insertarSegmento`, `editarSegmento`, `eliminarSegmento`, `insertarRazonSocial`, `editarRazonSocial`, `eliminarRazonSocial`.

**Gestión de productos**: `registrarMaterial`, `editarProducto`, `actualizarProducto`, `descontarStockEntrega`, `insertarHistorialProducto`.

**Tablas dinámicas**: `getProductTableRow` y `getServiceTableRow` que retornan HTML parcial para agregar filas a formularios.

### Archivo.php (495 líneas)

Maneja la subida de archivos y la creación de solicitudes. El método principal `subir()` procesa el formulario de nueva solicitud:

- Determina el tipo de solicitud (cotización, sin cotizar, servicio).
- Obtiene datos del usuario de sesión.
- Resuelve la unidad operativa y departamento destino.
- Genera etiqueta de trazabilidad si la razón social destino es diferente.
- Determina el estado inicial según el tipo de login y si se envía directamente a dirección.
- Inserta la solicitud con folio "MBSP-{ID}".
- Inserta los productos o servicios en las tablas correspondientes.
- Procesa los archivos subidos (solicitud, evidencia, cotización).
- Si es envío directo a dirección, genera automáticamente la cotización.

### ControlMaestro.php (529 líneas)

Es el controlador más complejo en términos de lógica de negocio. Implementa el sistema de control de flujo de solicitudes con **9 niveles (0-8)**:

-0 = Pre-solicitud
- 1 = En espera
- 2 = Cotizando
- 3 = En revisión / Aprobación pendiente
- 4 = Aprobada (sin orden)
- 5 = Espera programación (con orden, sin PDF)
- 6 = Programada (orden + requisición PDF)
- 7 = Por pagar
- 8 = Pagada

El método `update_master()` hace lo siguiente:

1. **Snapshot presupuestal previo**: Captura los montos antes del cambio.
2. **Gestión de estados**: Actualiza solicitud y orden según el nivel.
3. **Detección de cambios**: Identifica si cambió el proveedor.
4. **Limpieza de archivos**: Elimina archivos cuando se baja de nivel.
5. **Sincronización de presupuesto**: Ejecuta 8 reglas para ajustar Montos_Comprometido y Ejecutado.
6. **Actualización de productos**: Modifica cantidades, precios y grupos presupuestales.
7. **Generación de PDFs**: Genera requisición (nivel 6+) y orden (si existe).
8. **Manejo de archivos**: Gestiona factura, ficha, complemento y cotizaciones.

### GenerarPDF.php (~2,000 líneas)

Maneja la generación de documentos PDF usando FPDI (una librería que permite importar páginas de PDFs existentes). Genera los siguientes documentos:

- **Requisición**: Documento que detalla los productos o servicios solicitados.
- **Orden de compra**: Documento formal de orden al proveedor.
- **Requisición de pago**: Documento que acompaña el proceso de pago.
- **Entrega de materiales**: Documento de entrega de mercancía.
- **Consolidado**: Expediente completo con todos los documentos.

Usa Ghostscript para convertir PDFs a versión 1.4 compatible con FPDI cuando es necesario. Incluye validaciones de PDF con `PdfValidator`.

### Inventario.php (233 líneas)

Gestiona la recepción manual de material. El método `guardarIngresoManual()` recibe un JSON con cabecera y detalles, inicia una transacción, inserta en `Ingresos` y `DetalleIngreso`, actualiza la existencia en `Producto` y registra en `HistorialProductos`.

### Mantenimiento.php (35 líneas)

Simplemente muestra una página de mantenimiento cuando el modo está activado. Lee la configuración de `writable/mantenimiento.json`.

### ReportesController.php (~1,000 líneas)

Genera reportes en Excel usando PhpSpreadsheet. Métodos principales:

- Reporte comparativo mensual y anual de presupuesto.
- Reporte completo (presupuesto + bancos).
- Reporte comparativo de bancos.
- Exportación a Excel de todos los anteriores.

### PresupuestoApiController.php (~775 líneas)

Maneja la gestión de presupuestos mensuales y saldos bancarios. Métodos principales:

- `getEstructura()`: Retorna la estructura de asignación de presupuesto para edición.
- `saveMasivo()`: Guarda asignaciones de presupuesto en lote.
- `saveGastosManuales()`: Registra gastos indirectos (incrementos).
- `getCambiosPendientes()` y `dictaminarCambio()`: Workflow de solicitudes de cambio presupuestal.
- `getEstructuraSaldos()` y `saveSaldosMasivo()`: Gestión de saldos bancarios.

### Installer.php (335 líneas)

Controlador del instalador web. Detecta si el sistema ya está instalado buscando `writable/installer.lock`. Si no existe, muestra el formulario de instalación. Si existe, el sistema funciona normalmente. El método `process()` crea la base de datos, ejecuta migraciones, actualiza `.env` y crea el archivo de lock.

### BaseController.php (58 líneas)

Clase base abstracta de la que extienden todos los controladores. Inicializa la sesión del usuario en `$this->session` para que esté disponible en todos los controladores.

---

# 5. VISTAS Y FRONTEND

## 5.1 Sistema de Vistas

Las vistas están organizadas en:

- `auth/login.php`: Formulario de login con diseño glassmorphism oscuro con acentos dorados.
- `portada.php`: Landing page animada con CSS-doodle para usuarios no autenticados.
- `layout/principal.php`: Shell principal con sidebar, header, footer y modal general.
- `inicio.php`: Placeholder que define la sección de contenido.
- `modales/`: ~40 archivos de modales dinámicos.
- `emails/`: Plantillas de correo (ficha de pago, solicitud de cotización, notificación de pago).
- `errors/`: Vistas de errores HTML y CLI.
- `installer/`: Vistas del instalador.
- `mantenimiento/`: Vista de modo mantenimiento.

## 5.2 Layout Principal

El archivo `layout/principal.php` es el shell que envuelve todo el contenido autenticado. Incluye:

- **Sidebar (64px de ancho)**: Logo, navegación dinámica con opciones filtradas por permisos, sección de Catálogos (solo para bosses), botón de Ajustes, botón de Cerrar sesión.
- **Header**: Nombre del usuario, departamento y tipo de login.
- **Main**: Área de contenido con `renderSection('contenido')`.
- **Modal General**: Un modal reutilizable con `#modal-general` que se carga dinámicamente.
- **Footer**: Versión de la aplicación.

El sidebar está controlado por variables PHP que vienen del controlador Home: `opcionesDinamicas`, `ajustes`, `login_type`, `nombre_usuario`, `departamento_usuario`, `id_departamento_usuario`, `modo_login`.

##5.3 Sistema de Modales Dinámicos

El sistema de modales funciona con el siguiente flujo:

1. El usuario hace click en una opción del menú.
2. La función `abrirModal(opcion)` en `mbscript.js` hace un `fetch` a `GET /modales/{opcion}`.
3. El controlador `Modales::mostrar($opcion)` retorna la vista del modal.
4. El HTML se inyecta en `#modal-contenido` del layout principal.
5. Se llama al inicializador específico del modal (ej. `initPaginacionHistorial()`, `initCrudProveedores()`).
6. El modal se muestra con una animación CSS.

Cada modal tiene su propio inicializador en JavaScript que configura los eventos, validaciones y comportamientos específicos.

## 5.4 Principales Modales

### solicitar_material.php

Formulario de creación de requisiciones con hasta4 pantallas:

1. **Selección de tipo**: Material con cotización, material sin cotizar, o servicio.
2. **Material cotizado**: Formulario completo con tabla de productos, filtros por razón social y lugar, selector de proveedor, cálculos de totales con IVA.
3. **Material sin cotizar**: Similar pero sin montos (el usuario ingresa precios estimados).
4. **Servicios**: Formulario para servicios con tabla de servicios.

Incluye sistema de búsqueda con autocompletado del catálogo de productos, favoritos del usuario, y opción de enviar directamente a dirección.

### ver_historial.php

Tabla paginada con filtros avanzados: fecha, folio, tipo, estado, proveedor, razón social, departamento. Incluye paginación estilo Google y opción de exportar a Excel. Toggle para ver solicitudes declinadas.

### revisar_solicitudes.php

Gestión de solicitudes pendientes de revisión por el departamento de Compras. Tiene tres secciones: VER (detalles), COTIZAR (selección de proveedores para generar cotizaciones), ENVIAR REVISIÓN (envío a dirección con archivos).

### pagos_pendientes.php y programar_pagos.php

Gestión de pagos con dos pestañas: Contado y Crédito. Muestra órdenes pendientes de pago con un semáforo de colores para créditos según días restantes (vencido, menos de 5 días, menos de 15 días, vigente).

### crud_proveedores.php, crud_usuarios.php, crud_productos.php

Formularios de CRUD completos con listas paginadas, formularios de creación y edición, validación de campos y manejo de archivos adjuntos.

### control/PresupuestoMensual.php

Asignación mensual de presupuestos por unidad operativa y grupo presupuestal. Permite copiar mes anterior, exportar a Excel, asignar resto del año, y guardado masivo.

### control/SaldosBancarios.php

Gestión de saldos iniciales y finales de cuentas bancarias por mes y año. Incluye alertas si hay una revisión pendiente.

## 5.5 JavaScript Principal

### mbscript.js (~4,200 líneas)

Es el archivo JavaScript principal del sistema. Contiene:

- **Sistema de modales**: Función `abrirModal()` con mapeo de categorías, determinación de ancho del modal, carga AJAX, e inicializadores específicos.
- **Sistema de solicitudes**: `initSolicitarMaterial()`, `initSolicitarMaterialSinCotizar()`, `initSolicitarServicio()`, `initEnviarDireccion()`.
- **Sistema de búsqueda**: `initAutocompleteCatalogo()`, `cargarListaCompletaProductos()`, `toggleFavoritoProducto()`.
- **Sistema de historial**: `initPaginacionHistorial()`, `toggleVistaDeclinadas()`.
- **CRUDs**: Inicializadores para cada modal de gestión.

### utils.js (~1,400 líneas)

Funciones utilitarias compartidas:

- **Comunicación HTTP**: `SendDataEnd()` — función central que envuelve `fetch` con manejo de CSRF, headers por defecto, nano-loader visual, y manejo de errores.
- **Formularios**: `SendData()` para envío asíncrono de formularios.
- **Notificaciones**: `mostrarNotificacion()` para toasts.
- **Confirmaciones**: `Confirmar()` para modales de confirmación con Promise.
- **Formateo**: `formatearMoneda()` para formato MXN, `getStatus()` para clases CSS de estado.
- **Tablas**: `createPaginatedTable()` y `setupClientSideTable()` para tablas con paginación.
- **Sistema de archivos**: `setupAccumulatedFileInput()` para inputs de archivo múltiples.
- **PDFs**: `mostrarVerPdf()`, `mostrarOrdenPdf()`, `mostrarExpedientePdf()`.

## 5.6 Pipeline de CSS

El sistema usa **Tailwind CSS v4** con PostCSS:

- `input.css`: Archivo fuente con `@import "tailwindcss"` y definiciones de fuentes personalizadas (Montserrat, Doulos SIL).
- `tailwind.config.js`: Configuración con content paths para vistas y controladores.
- `postcss.config.mjs`: Plugin `@tailwindcss/postcss`.
- Build: `npm run build:product` compila a `public/css/styless.css` (~4,300 líneas).

## 5.7 Librerías JavaScript Externas

- **Choices.js**: Selectores mejorados con búsqueda y multiselect. Usado en prácticamente todos los formularios de filtros.
- **Alpine.js v3.14.8**: Framework reactivo para componentes con estado. Usado en presupuestos.js, gasto_manual.js, bitacora.js, user.js.
- **CSS Doodle**: Librería para efectos visuales decorativos en la portada y login (partículas doradas animadas).

---

# 6. LIBRERÍAS Y SERVICIOS

## 6.1 Inventario de Librerías

### Rest.php (~2,600 líneas)

Es la **librería más importante del sistema**. Actúa como fachada de acceso a datos, encapsulando todas las consultas SQL complejas con joins y la lógica de negocio relacionada.

Está organizada en regiones temáticas:

**Tokens**: `generateUserToken()`, `generatetoken()`, `updateToken()`, `deleteToken()` — gestión de tokens API.

**Cotizaciones**: CRUD de cotizaciones con búsqueda por ID y por ID de solicitud.

**Solicitudes**: Este es el corazón de la librería. Métodos como `getAllSolicitud()` (trae todas con montos calculados), `getSolicitudWithProducts()` (solicitud completa con productos, servicios, cotizaciones, orden y grupos presupuestales), `getSolicitudesCotizadas()`, `getSolicitudesEnRevision()`, `getSolicitudesByStatusAndDept()`.

**Usuarios**: `getUserById()`, `getUserByEmail()`, `getAllUsers()`, `addUser()`, `updateUser()`, `deleteUser()`, `save_signature()`.

**Productos**: `getProductsByQuery()`, `getAllProducts()`, `registrarProducto()`, `buscarProductosCatalogo()` (búsqueda con lógica de departamentos especiales), `getFavoritosUsuario()`, `toggleFavorito()`.

**Proveedores**: `getAllProveedores()`, `getProveedorIdAndRazonSocial()`.

**Departamentos y Razones Sociales**: Listados con joins a Places.

**Storage**: `getStorageContent()` — lista archivos en el directorio de uploads con prevención de path traversal.

**Pagos**: `getPagosPendientes()`, `getFichasPago()`, `getMovimientosProveedor()`.

**Bitácora**: `getBitacora()` con filtros y compatibilidad multi-DB para extracción de JSON.

### BitacoraService.php (95 líneas)

Patrón Singleton. Acumula eventos en memoria durante la petición HTTP y los persiste todos juntos al final con `persistir()`. Esto optimiza el rendimiento evitando un INSERT por cada evento.

Carga automáticamente el contexto del usuario desde la sesión (id, nombre, departamento, complejo, razón social) y lo adjunta a cada entrada de bitácora.

### MBSMail.php (95 líneas)

Wrapper de PHPMailer que configura SMTP desde variables de entorno. Envía correos con adjuntos y dispara evento de auditoría por cada envío.

### HttpStatus.php (12 líneas)

Constantes simples de códigos HTTP: OK (200), CREATED (201), BAD_REQUEST (400), NOT_FOUND (404), INTERNAL_SERVER_ERROR (500), etc.

### Status.php (39 líneas)

Constantes de estados de solicitudes y órdenes de compra. Define estados en orden de flujo: En_espera, Cotizando, En_Revision, Rechazada, Aprobada, Espera_Programacion, Programada, Por_Pagar, Pagada. También define estados especiales como Aprobacion_pendiente y En_Proceso_Pago.

### SolicitudTipo.php (8 líneas)

Constantes de tipo de solicitud: Cotizacion (0), NoCotizacion (1), Servicios (2).

### MetodoPago.php (7 líneas)

Constantes de método de pago: Efectivo (0), Credito (1), EnEspera (9).

### FPath.php (28 líneas)

Constantes de rutas de almacenamiento de archivos. Todas relativas a `WRITEPATH/uploads/`:

- Cotizaciones, órdenes, PDFs de solicitudes, solicitudes, facturas, remisiones, entradas de facturas, facturas de servicios, comprobantes, complementos, proveedores, usuarios.

### ImageProcessor.php (94 líneas)

Procesa imágenes subidas: comprime a80% de calidad, redimensiona a máximo 1920px de ancho, maneja memoria PHP para imágenes grandes. Para archivos no-imagen (PDF, SVG, DOCX) simplemente los mueve.

### PdfValidator.php (130 líneas)

Analiza PDFs para verificar compatibilidad con FPDI (≤ versión 1.4 y sin encriptar). Retorna warnings si el PDF no es compatible.

### PDF.php (165 líneas)

Extiende FPDI y añade métodos de helper: `setHeaderTitle()`, `Header()` (override con logo), `Title()`, `Footer()` (número de página), `SetWidths()` para tablas.

### GhostscriptProcessor.php (125 líneas)

Wrapper de Ghostscript para convertir PDFs a versión 1.4 compatible con FPDI. Detecta automáticamente el binario disponible en el sistema (Windows o Linux).

### XmlCfdiReader.php (86 líneas)

Parser de archivos XML CFDI (Comprobante Fiscal Digital por Internet). Extrae: fecha, serie-folio, RFC emisor/receptor, nombre emisor, UUID del timbre fiscal, y conceptos (cantidad, unidad, descripción, identificador).

## 6.2 Eventos del Sistema

El sistema usa el sistema de eventos de CodeIgniter en `Events.php`:

**Evento `auditoria`**: Disparado manualmente desde cualquier parte del código con `Events::trigger('auditoria', [...])`. `BitacoraService` lo captura y acumula en memoria.

**Evento `post_system`**: Al finalizar cada request HTTP, persiste todos los logs acumulados en la base de datos.

**Evento `systemException`**: Captura excepciones no controladas, las registra en bitácora y persiste inmediatamente.

**Evento `pre_system`**: Al inicio de cada request, limpia buffers de salida y configura el Debug Toolbar en desarrollo.

---

# 7. CONFIGURACIÓN Y RUTAS

## 7.1 Sistema de Rutas

El archivo `Routes.php` define todas las rutas explícitamente (`autoRoute = false`). Esto es bueno para seguridad ya que no hay exposición de controladores no intentionados.

### Rutas Públicas

Cuando el sistema está instalado (existe `writable/installer.lock`):

- `GET /` → Home::index
- `GET /mantenimiento` → Mantenimiento::index
- `GET /auth` → Auth::index
- `POST /auth/login` → Auth::login
- `GET /auth/logout` → Auth::logout
- `POST /api/gentoken` → Api::gentoken (ruta huérfana: el método no existe)

### Rutas Protegidas

Todas las demás (~150 rutas) están dentro de un grupo con filtros `['auth', 'mantenimiento']`:

```php
$routes->group('/', ['filter' => ['auth', 'mantenimiento']], function ($routes) {
    // Todas las rutas de la aplicación
});
```

Esto significa que requieren sesión activa (`isLoggedIn`) y que el modo mantenimiento no esté activo (o que el usuario tenga rol permitido en mantenimiento).

### Sin Auto-Routing

`autoRoute = false` en `Routing.php`. Solo las rutas definidas explícitamente son accesibles. Esto previene exposición accidental de métodos de controladores.

## 7.2 Filtros HTTP

### AuthFilter

Verifica `session('isLoggedIn')`. Si no existe, redirige a `/auth` y registra en bitácora un evento de `ACCESO_DENEGADO`.

### MantenimientoFilter

Lee `writable/mantenimiento.json`. Si está activado:

- Si el usuario no está logueado, redirige a `/auth`.
- Si el usuario está logueado, verifica si su `login_type` o `departamento_usuario` está en la lista de roles permitidos.
- Si no tiene acceso, destruye la sesión y redirige a `/mantenimiento`.

Las URIs `/mantenimiento`, `/auth` e `/installer` están exentas de este filtro.

### HSTS Filter

Agrega el header `Strict-Transport-Security: max-age=31536000; includeSubDomains; preload` en todas las respuestas HTTPS.

## 7.3 Configuración de Sesión

- **Driver**: FileHandler (sesiones en archivos, no en BD).
- **Expiración**: 7200 segundos (2 horas).
- **Save path**: `writable/session/`.
- **Match IP**: Desactivado.
- **TimeToUpdate**: 300 segundos (regenera ID de sesión cada 5 minutos).
- **RegenerateDestroy**: Desactivado (los datos de la sesión vieja persisten al regenerar).

## 7.4 Configuración de CSRF

- **Método**: Cookie.
- **Token name**: `csrf_test_name` (valor por defecto — considerar cambiar en producción).
- **Cookie name**: `csrf_cookie_name` (valor por defecto — considerar cambiar en producción).
- **Expiración**: 7200 segundos.
- **Regenerate**: Activado (regenera token en cada submission).

**Observación importante**: El filtro global CSRF está comentado en `Filters.php` (`globals.before`), por lo que la protección CSRF solo está activa en los formularios que manualmente incluyen `csrf_field()`.

## 7.5 Configuración de Base de Datos

- **Driver por defecto**: MySQLi.
- **Charset**: utf8mb4.
- **Puerto**: 3306.
- **DBDebug**: true (expone información en errores — desactivar en producción).
- **Encryption key**: Vacía (debe configurarse).

## 7.6 Configuración de Aplicación

- **BaseURL**: `http://localhost:8080/` (desarrollo).
- **Locale**: `es` (español).
- **Timezone**: UTC.
- **CSP**: Deshabilitado.
- **ForceHTTPS**: Deshabilitado.

---

# 8. FLUJO DE NEGOCIO COMPLETO

## 8.1 Ciclo de Vida de una Solicitud

### Paso 1: Creación

Un usuario logueado accede a "Solicitar Material" desde el menú. Selecciona el tipo de requisición (material con cotización, material sin cotizar, o servicio). Completa los datos: razón social, complejo, proveedor (opcional), productos o servicios con cantidades e importes, grupo presupuestal, método de pago, y comentarios.

El sistema determina el estado inicial según el tipo de login:

- Si es empleado (`login_type === 'employee'`): El estado inicial es `En_espera` y la solicitud queda pendiente de aprobación del jefe.
- Si es jefe (`login_type === 'boss'`): Puede enviar directamente a Dirección (para servicios) o generar cotizaciones automáticamente.

### Paso 2: Aprobación Departamental (solo para empleados)

El jefe de departamento ve las solicitudes pendientes de su departamento. Puede aprobar o rechazar. Si aprueba, la solicitud avanza a `Cotizando` (si es un servicio) o `En_espera` (si es material con cotización).

### Paso 3: Cotización

El departamento de Compras genera cotizaciones. Selecciona proveedores de una lista, genera un PDF de requisición y lo envía por correo a cada proveedor. Se crean registros en la tabla `Cotizacion`.

### Paso 4: Selección de Proveedor

Una vez recibidas las cotizaciones, Compras selecciona al proveedor ganador, adjunta los archivos de cotización y envía la solicitud a revisión. Las cotizaciones no seleccionadas se eliminan.

### Paso 5: Dictamen de Dirección

La Dirección revisa la solicitud con las cotizaciones. Puede aprobar o rechazar. Si aprueba, el sistema genera automáticamente una orden de compra en estado `Por_Pagar`.

### Paso 6: Programación de Pagos

Tesorería programa los pagos de las órdenes. Selecciona órdenes pendientes, asigna fechas de pago y cambia el estado a `Programada`.

### Paso 7: Pago

Tesoreria sube la factura del proveedor y el comprobante de pago. El sistema cambia el estado a `Pagada`, ejecuta el presupuesto (pasa de comprometido a ejecutado), y envía notificación al proveedor.

## 8.2 Sistema de Control de Niveles (ControlMaestro)

El sistema de control maestro tiene 9 niveles (0-8) que управляют el flujo de la solicitud:

- **Nivel 0**: Pre-solicitud (creada pero no enviada).
- **Nivel 1**: En espera de aprobación departamental.
- **Nivel 2**: Cotizando (Compras está generando cotizaciones).
- **Nivel 3**: En revisión (cotizaciones listas, esperando dictamen de Dirección).
- **Nivel 4**: Aprobada (sin orden de compra aún).
- **Nivel 5**: Espera programación (orden existe, sin PDF).
- **Nivel 6**: Programada (orden + requisición PDF generada).
- **Nivel 7**: Por pagar.
- **Nivel 8**: Pagada.

El método `update_master()` en `ControlMaestro` gestiona las transiciones entre niveles, incluyendo: snapshot presupuestal previo, sincronización de montos, limpieza de archivos obsolescentes, y generación de PDFs en los niveles apropiados.

## 8.3 Control Presupuestal

El presupuesto se controla a tres niveles:

1. **Grupo Presupuestal**: Partida específica (ej. "Servicios de auditoría externa").
2. **Unidad Operativa**: Área dentro de un Place (ej. "Operación Centro").
3. **Razón Social**: Empresa completa.

El flujo presupuestal funciona así:

- **Asignación**: Se asigna un monto mensual a cada combinación de unidad operativa + grupo presupuestal (`PresupuestoMensual.Monto_Asignado`).
- **Compromiso**: Cuando una solicitud es aprobada, el monto se resta de `Monto_Asignado` y se suma a `Monto_Comprometido`.
- **Ejecución**: Cuando una orden es pagada, el monto se resta de `Monto_Comprometido` y se suma a `Monto_Ejecutado`.
- **Liberación**: Si una solicitud aprobada se cancela, el monto se libera de vuelta a `Monto_Asignado` (si estaba en comprometido) o de `Monto_Ejecutado` (si ya estaba pagada, requiere intervención manual).

## 8.4 Sistema de Favoritos

Cada usuario puede marcar productos del catálogo como favoritos. El sistema prioriza estos productos en el autocomplete de búsqueda, basándose en la frecuencia de uso. Esto acelera la creación de solicitudes para usuarios recurrentes.

## 8.5 Sistema de Workflow de Cambios Presupuestales

Cuando se necesita hacer un ajuste que no es cubierto por el flujo normal (ej. redistribuir presupuesto entre grupos), el usuario crea una `SolicitudCambioPresupuesto`. Esta solicitud queda pendiente y un administrador la revisa para aprobarla o rechazarla. Si se aprueba, el sistema aplica los cambios automáticamente.

---

# 9. ESTADOS DEL SISTEMA

## 9.1 Estados de Solicitud

Los estados están definidos en `Status.php` en orden de flujo:

| Estado | Descripción |
|--------|-------------|
| `En_espera` | Esperando aprobación del jefe de departamento |
| `Cotizando` | Compras está generando cotizaciones |
| `En_Revision` | Cotizaciones listas, esperando dictamen de Dirección |
| `Rechazada` | Rechazada por Dirección |
| `Aprobada` | Aprobada por Dirección, lista para generar orden |
| `Espera_Programacion` | Orden generada, esperando programación de pago |
| `Programada` | Pago programado |
| `Por_Pagar` | Factura recibida, esperando pago |
| `Pagada` | Pago realizado |

Estados especiales:

| Estado | Descripción |
|--------|-------------|
| `Aprobacion_pendiente` | Pendiente de aprobación departamental |
| `Dept_Rechazada` | Rechazada por el departamento |
| `En_Proceso_Pago` | Pago en proceso (entre programación y pago efectivo) |

## 9.2 Estados de Orden de Compra

Las órdenes de compra heredan el estado de la solicitud. Una vez generada, su estado evoluciona: `Por_Pagar` → `En_Proceso_Pago` → `Pagada`.

## 9.3 Estados de Workflow Presupuestal

Las solicitudes de cambio presupuestal tienen estados: `Pendiente`, `Aprobada`, `Rechazada`.

## 9.4 Estados de Solicitud de Cambio

El sistema de cambios de presupuesto (`SolicitudesCambioPresupuesto`) maneja estados: `Pendiente`, `Aprobada`, `Rechazada`.

---

# 10. ROLES Y PERMISOS

## 10.1 Sistema de Permisos

El sistema determina los permisos de cada usuario basándose en **dos factores**:

1. **Departamento**: Cada departamento tiene una lista predefinida de opciones de menú disponibles.
2. **Tipo de login**: `boss` (jefe) o `employee` (empleado). Los jefes tienen permisos adicionales si no son de Dirección, Compras o Tesorería.

## 10.2 Permisos por Departamento

### Administración

Ve absolutamente todas las opciones del sistema. Es el único rol que puede gestionar usuarios, proveedores, razones sociales, y todas las configuraciones.

### Compras

Tiene acceso a: solicitar material, revisar solicitudes (cotizar), enviar a revisión, órdenes de compra, pagos pendientes, ver historial, correcciones, lista de pagos, gestión de proveedores, gestión de usuarios, gestión de cuentas, reportes y catálogo de productos.

### Dirección

Tiene acceso a: dictaminar solicitudes, programar pagos, ajustes de presupuesto, ver historial, gestión de presupuestos (unidades operativas, grupos presupuestales, presupuesto mensual, segmentos de negocio, bancos, saldos bancarios), y reportes.

### Tesorería

Tiene acceso a: ficha de pago, pagos pendientes, gestión de cuentas, lista de pagos, ver historial, solicitar material, y reportes.

### Almacén

Tiene acceso a: registrar productos, gestión de productos (existencias), entrega de productos, recepción de material, bajas por destrucción, y menú de almacén.

### Dirección Campus

Permisos limitados: solicitar material, enviar a revisión, ver historial, reportes.

### Presupuestos

Gestión completa de presupuestos: unidades operativas, grupos presupuestales, presupuesto mensual, segmentos de negocio, reportes, bancos y saldos bancarios.

### Contaduría

Acceso combinado de Compras, Tesorería, Almacén y additional permisos de reportes contables.

### Default (Jefes de otros departamentos)

Solo operaciones básicas: solicitar material, ver historial, reportes.

## 10.3 Diferencia entre Login Type Boss y Employee

Cuando un usuario inicia sesión con el checkbox "Acceso Auxiliar" marcado, usa `ContrasenaG` y el `login_type` se setea como `employee`. Sin este checkbox, usa `ContrasenaP` y `login_type` es `boss`.

Esta distinción afecta:

- **Aprobación departamental**: Solo los `boss` pueden aprobar solicitudes de su departamento.
- **Opciones de menú**: Los `boss` de departamentos que no son Dirección/Compras/Tesorería ven la opción "Aprobar solicitudes".
- **Acceso a catálogos**: Los `boss` ven la sección de catálogos en el sidebar.

##10.4 Permisos de Ajustes

Cada departamento tiene una lista específica de opciones de ajustes disponibles. Por ejemplo, Administración y Dirección ven todas las opciones de ajustes, mientras que otros departamentos ven un subconjunto.

---

# 11. SISTEMA DE AUDITORÍA

## 11.1 Arquitectura de Auditoría

El sistema de auditoría funciona con un patrón de **acumulación en memoria + persistencia batch**:

1. Durante el procesamiento de un request, cualquier parte del código puede disparar el evento `auditoria` con `Events::trigger('auditoria', [...])`.
2. `BitacoraService` recibe el evento y lo acumula en un array en memoria.
3. Al final del request (`post_system`), `BitacoraService::persistir()` hace un único `insertBatch()` con todos los eventos acumulados.

Para errores críticos (`systemException`), la persistencia es inmediata para asegurar que el error queda registrado incluso si el sistema crash.

## 11.2 Trait de Auditoría

El `AuditTrait` se mezcla en los modelos y proporciona callbacks automáticos:

- **beforeInsert / afterInsert**: Registra la creación de nuevos registros.
- **beforeUpdate / afterUpdate**: Registra modificaciones comparando valores antiguos con nuevos.
- **beforeDelete / afterDelete**: Registra eliminaciones.

El trait captura datos del contexto: si el modelo tiene campos como `solicitud_id`, `orden_compra_id` o `cotizacion_id`, los incluye automáticamente en la bitácora.

## 11.3 Información Registrada

Cada entrada de bitácora incluye:

- Usuario (id y nombre)
- Departamento, complejo y razón social del usuario
- Tipo de acción (ej. `LOGIN_EXITOSO`, `GENERAR_ORDEN`, `ACTUALIZAR_MONTOS`)
- Clasificación (ej. `Operaciones`, `Catálogos`, `Finanzas`)
- Módulo afectado
- IDs de contexto (solicitud, orden, cotización)
- Dirección IP del cliente
- Valores antiguos y nuevos en JSON
- Estado (éxito o fallido)
- Timestamp

## 11.4 Eventos de Auditoría Principales

El sistema registra eventos para: login exitoso, login fallido, logout, acceso denegado, mantenimiento denegado, solicitudes (creación, aprobación, rechazo, cancelación), órdenes de compra (generación, cambio de estado), pagos, errores del sistema, y envíos de correo.

---

# 12. TECNOLOGÍAS Y STACK

## 12.1 Backend

- **Framework**: CodeIgniter 4 (PHP 8.1+)
- **Lenguaje**: PHP 8.1
- **Base de datos**: MySQLi (驱动) — compatible también con PostgreSQL
- **Gestión de dependencias**: Composer
- **Email**: PHPMailer

## 12.2 Frontend

- **CSS**: Tailwind CSS v4 (compilado con PostCSS)
- **JavaScript**: Vanilla JavaScript (ES6+) + Alpine.js v3.14.8
- **Selectores**: Choices.js
- **Efectos visuales**: CSS Doodle
- **Fuentes**: Montserrat (Google Fonts), Doulos SIL

## 12.3 Documentos

- **Generación de PDFs**: FPDI (importar PDFs existentes) + FPDF (crear nuevos)
- **Lectura de XML CFDI**: Parser personalizado (XmlCfdiReader)
- **Conversión de PDFs**: Ghostscript
- **Excel**: PhpSpreadsheet
- **Procesamiento de imágenes**: Intervention/Image (ImageProcessor custom)

## 12.4 Seguridad

- **Autenticación**: Sesiones PHP con double password (jefe/empleado)
- **Protección CSRF**: Token en cookie y header (implementación CodeIgniter)
- **Headers de seguridad**: HSTS (Strict-Transport-Security)
- **Validez de sesión**: 2 horas con regeneración cada 5 minutos
- **Auditoría**: Sistema de bitácora centralizado con eventos

## 12.5 Infraestructura

- **Upload de archivos**: Directorio `writable/uploads/` con organización por tipo
- **Cache**: FileHandler (writable/cache/)
- **Sesiones**: FileHandler (writable/session/)
- **Logs**: CodeIgniter Logger con niveles configurables

---

# 13. OBSERVACIONES FINALES Y RECOMENDACIONES

## 13.1 Hallazgos de Diseño Positivo

- **Arquitectura clara**: La separación entre controladores, librería Rest y modelos es limpia.
- **Auditoría centralizada**: El sistema de eventos con `BitacoraService` es elegante y eficiente.
- **Control presupuestal robusto**: El sistema de compromisos y ejecuciones permite tracking granular.
- **Multi-tenant funcional**: La jerarquía Razón Social → Place → Unidad Operativa → Departamento funciona bien.
- **Sin auto-routing**: Previene exposición accidental de métodos.

## 13.2 Áreas de Mejora Identificadas

- **Tokens API huérfanos**: La ruta `POST /api/gentoken` existe pero el método `Api::gentoken()` no existe. Los tokens se generan en login pero no se usan activamente para auth de API.
- **CSRF parcial**: El filtro global CSRF está comentado. Los formularios incluyen el token pero no hay protección global.
- **Logout por GET**: `GET /auth/logout` es vulnerable a CSRF de logout. Debería ser POST.
- **Sin rate limiting**: No hay protección contra fuerza bruta en el login.
- **Llaves foráneas omitidas**: `ID_Dpto` e `ID_Proveedor` en `Solicitud` no tienen FK activas, permitiendo datos huérfanos.
- **Nomenclatura inconsistente**: Algunas tablas usan `ID_Prefijo`, otras usan `id` minúscula.
- **Sesión sin regeneración en login**: No se regenera el ID de sesión al hacer login exitoso, lo que podría facilitar ataques de fixation.
- **Deprecación de Feature**: `translateURIDashes = false` pero `translateUriToCamelCase = true` — configuración contradictoria.
- **DBDebug activo en producción**: Expone información de la base de datos en mensajes de error.

## 13.3 Documentación Técnica Adicional

- **Endpoint count**: ~150+ rutas definidas explícitamente.
- **Modal count**: ~40 modales diferentes.
- **Modelo count**: 28+ modelos.
- **Controlador count**: 12 controladores principales.
- **Librería count**: 14 librerías y servicios.
- **Estado count**: 9 estados principales de solicitud + estados especiales.
- **Rol count**: 9+ roles/departamentos con permisos diferenciados.

---

*Documento generado a partir de análisis estático y dinámico del código fuente del sistema MBSP.*
*Versión del sistema: 3.18.4*
*Fecha de análisis: Junio 2026*
