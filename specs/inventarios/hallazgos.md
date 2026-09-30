# Hallazgos: estado actual del módulo de inventarios

| Campo | Valor |
|---|---|
| **Fecha de la investigación** | 2026-09-30 |
| **Estado** | **Inmutable.** Registra el estado del código *antes* del cambio. Si una spec evoluciona, este archivo no se edita: se agrega una sección de "re-verificación". |
| **Relación** | Sustenta las decisiones de `spec.md` §3. Cada hallazgo lleva la evidencia `archivo:línea` que lo prueba. |
| **Base de datos** | `MBSPCompras` @ `localhost:5432`, driver `Postgre`, esquema `public`. Conteos obtenidos por consulta de solo lectura. |

---

## 1. Estado de las tablas

Ninguna tabla de inventario tiene datos. Esto es el hallazgo con mayor impacto de toda la investigación: **no hay migración de datos que hacer**.

| Tabla | Filas | Nota |
|---|---|---|
| `Producto` | **0** | El maestro del artículo físico nunca se ha usado |
| `HistorialProductos` | **0** | El "Historial de Almacén" nunca ha mostrado nada |
| `Ingresos` | **0** | El ingreso manual nunca completó un ciclo exitoso |
| `DetalleIngreso` | **0** | |
| `Entregas` | **0** | Además sin ninguna referencia en código |
| `DetalleEntrega` | **0** | Idem |
| `MapeoProductos` | **0** | Además sin ninguna referencia en código |
| `OrdenCompra` | **0** | El lado de compras tampoco tiene órdenes |
| `Solicitud` | 1 | `No_Folio = MBSP-1` |
| `Cotizacion` | 1 | `ID_Proveedor = 72`, `Total = 23.20` |
| `Solicitud_Producto` | 1 | |
| `Solicitud_Servicios` | 0 | |
| `Catalogo_Productos` | 1 | **Único dato vivo del sistema** |
| `Proveedor` | 72 | Referencia |
| `Razon_Social` | 4 | Referencia |

**Contenido literal de las dos filas que existen:**

```json
// Catalogo_Productos
{"ID_CatalogoProd":1,"ID_RazonSocial":2,"id_segmento":8,"ID_Place":5,"ID_Dpto":14,
 "ID_GrupoPresupuestal":153,"Nombre":"asaa",
 "created_at":"2026-09-21 11:52:08","updated_at":"2026-09-21 11:52:08"}

// Solicitud_Producto
{"ID_SolicitudProd":1,"ID_Solicitud":1,"Codigo":"1","Nombre":"asaa","Cantidad":1,
 "Importe":"20.00","ID_GrupoPresupuestal":153,"Monto_Comprometido_Original":"23.20",
 "ID_CatalogoProd":1}
```

Ambas son **andamiaje de prueba** (`"asaa"`, fecha 2026-09-21), no uso real. Conclusión: no hay regresión posible sobre datos de producción, y cualquier E2E necesita sembrar su propia cadena.

---

## 2. El menú está desconectado

| Evidencia | Detalle |
|---|---|
| `app/Config/MenuOptions.php:123-127` | `TituloAlmacen` comentado |
| `app/Config/MenuOptions.php:129-132` | `almacen` comentado |
| Commit `84ab9a8` (2026-04-27) | *"Comentar temporalmente la opcion de almacen"* |
| Commit `dbb010d` | Eliminó del menú `crud_productos`, `recepcion_material`, `entrega_productos`, `bajas_destruccion` |
| `app/Controllers/Home.php:285-290` | El filtro final itera sobre `$opcionesDisponibles`, que proviene de `MenuOptions`. Una clave que no exista ahí **nunca se renderiza** |
| `app/Controllers/Home.php:81-89` | El rol `Almacen` sí lista las claves — pero son inertes |
| `app/Controllers/Home.php:134-140` | El rol `Contaduría` también — igual de inerte |

`App\Config\MenuOptions` da todo a Administración con `array_keys()`, así que **ningún rol ve Almacén**, ni siquiera Administración.

Las claves de menú que sí están activas y son de catálogo: `catalogo_productos` (`MenuOptions.php:224-227`). Las de almacén están ausentes del array `$opciones` por completo, no solo comentadas.

---

## 3. El error fatal del puente compras → almacén

`Api::confirmarRecepcion` (`app/Controllers/Api.php:3885-4044`) usa tres constantes que **no existen**:

| Línea | Constante usada | ¿Existe en `Status.php`? |
|---|---|---|
| `Api.php:3991` | `Status::RECEPCION_PARCIAL` | **No** |
| `Api.php:3993` | `Status::RECEPCION_TOTAL` | **No** |
| `Api.php:4015` | `Status::RECIBIDA_TOTALMENTE` | **No** |

`app/Libraries/Status.php` tiene 39 líneas y define exactamente 14 constantes: `Aprobacion_pendiente:7`, `Aprobar:9`, `Dept_Rechazada:11`, `En_espera:14`, `Cotizando:16`, `En_Revision:18`, `Rechazada:20`, `Aprobada:22`, `Espera_Programacion:24`, `Programada:26`, `Por_Pagar:28`, `Pagada:30`, `Rechazar:35`, `En_Proceso_Pago:37`.

**Por qué esto es un error no controlado:** en PHP 8, referenciar una constante de clase inexistente lanza `Error`, y `Error` **no** extiende `\Exception`. Por lo tanto el `catch (\Exception $e)` de `Api.php:4032` no la intercepta.

**Consecuencia de orden:** el `UPDATE` de stock está en `Api.php:3978-3985` y el fatal en `:3991`. Una petición que alcanza la primera fila de producto **sí escribe y después revienta**.

**Segundo defecto en la misma función:** `Api.php:3912` usa `$db->transStart()` sin `transException(true)`, así que un error intermedio no fuerza rollback.

**Tercer defecto:** `Api.php:3998-4001` escribe `Cantidad_Recibida` y `Estado_Recepcion` en `Solicitud_Producto`. Ninguna de las dos columnas existe, y ninguna está en `$allowedFields` de `SolicitudProductModel.php:20`, con `protectFields = true` (`:19`). Es un no-op silencioso: CI4 descarta los campos en vez de lanzar error.

---

## 4. El modelo implícito del código es falso

El código asume que **lo que se solicita es lo que llega al almacén**. La estructura lo refleja:

| Tabla de inventario | Apunta a | Qué implica |
|---|---|---|
| `DetalleIngreso.ID_Producto` | `Producto` (FK) | El ingreso manual trabaja sobre el artículo físico |
| `DetalleEntrega.ID_Producto` | `Producto` (FK) | Ídem para entregas |
| `HistorialProductos.ID_Producto` | `Producto` (FK) | Ídem para el historial |
| `Solicitud_Producto.ID_CatalogoProd` | `Catalogo_Productos` (FK) | La requisición trabaja sobre el catálogo |

**Nadie conectó los dos mundos.** Y no es un descuido: `MapeoProductos` era el puente proyectado y nunca se construyó.

| Evidencia | Detalle |
|---|---|
| `app/Database/Migrations/2026-01-27-153401_AddMapeoProductos.php:50-51` | `ID_Proveedor → Proveedor` y `ID_Producto → Producto`. **Sin ninguna columna que refiera `Catalogo_Productos`.** Diseñado para mapear el `IdentificadorXML` de un proveedor contra `Producto` |
| `MapeoProductos` | 0 filas, 0 referencias en `app/` |
| `app/Libraries/XmlCfdiReader.php:14-85` | **0 callers.** Es el único parser CFDI del repositorio y no está conectado a nada |
| `app/Controllers/Inventario.php:94` | El ingreso manual graba `'NombreArchivoXML' => 'Carga Manual'` hardcodeado. La ingesta de XML nunca se implementó |

**Por qué el supuesto es falso (contexto de negocio).** El catálogo de compras contiene productos **y servicios**; un servicio no se almacena. Además el material que llega puede tener otro nombre, no llegan todas las partidas, hay existencias preexistentes sin requisición de origen, y no toda requisición pasa por almacén. Ninguna de esas situaciones tiene hoy dónde registrarse, y el modelo de dos mundos las hace imposibles de representar.

---

## 5. Riesgo neutralizado por la decisión D1

`app/Controllers/MigracionesController.php:351`:

```php
$db->table('Catalogo_Productos')->insert($data);
```

El módulo "Traspasos y Migraciones" **inserta filas** en el catálogo al migrar datos entre sitios y unidades operativas.

Si `Catalogo_Productos` hubiera recibido una columna `Existencia` —el diseño que se descartó— cada traspaso habría **duplicado existencias**: la fila original conservaría su stock y la copia la replicaría. La decisión D1 de no tocar el catálogo elimina este riesgo por completo.

---

## 6. Radio de impacto de `Producto`

`Producto` está referenciada en **14 puntos de `app/`**:

| Archivo | Líneas | Qué hace |
|---|---|---|
| `app/Libraries/Rest.php` | `1802`, `1815`, `1828`, `1845`, `1858`, `2085`, `2097`, `2107` | CRUD genérico legacy (ver abajo) |
| `app/Controllers/Modales.php` | `307`, `313`, `329`, `443`, `969`, `1043`, `1085`, `1102` | Altas, edición, entrega, historial, bajas |
| `app/Controllers/Api.php` | `3888`, `4048`, `4049` | Recepción y baja por destrucción |
| `app/Controllers/Inventario.php` | `55`, `80`, `81`, `182` | Listado, ingreso manual, alta rápida |

**El bloque legacy de `Rest.php` que se elimina completo:**

| Rango | Métodos |
|---|---|
| `Rest.php:1795-1865` | `getProductById`, `getProductsByCode`, `getProductsByName`, `registrarProductoArray`, `registrarProducto` |
| `Rest.php:2078-2113` | `eliminarProductoById`, `actualizarProducto`, `getAllProducts` |

Son envoltorios CRUD sin lógica de negocio. Sus equivalentes de catálogo ya existen desde `Rest.php:1933` en adelante.

**Definición actual de la tabla** — `app/Database/Migrations/2025-08-14-001514_CreateTableProducto.php`:

| Línea | Columna | Nota |
|---|---|---|
| `:12-17` | `ID_Producto` | INT(5) unsigned, autoincrement, PK (`:35`) |
| `:18-23` | `Codigo` | VARCHAR(50), **`unique => true`** |
| `:24-28` | `Nombre` | VARCHAR(100) |
| `:29-33` | `Existencia` | INT unsigned, default 0. El comentario de `:31` dice *"Equivale a CHECK (Existencia >= 0)"* — **es solo un comentario. No existe ningún constraint** |

**Sin ninguna llave foránea.** `Producto` no referencia nada.

`ProductoModel.php` es de 28 líneas: `AuditTrait` (`:10`), `auditClasificacion = 'Catálogos'` (`:12`), `protectFields = true` (`:19`), `allowedFields = ['Codigo', 'Nombre', 'Existencia']` (`:20`). **No declara `$useTimestamps`**, así que CI4 usa el default `false`.

---

## 7. Código muerto

| Ubicación | Detalle |
|---|---|
| `public/js/almacen.js:711-865` | `async function initRecepcionMaterial()`, **155 líneas muertas**. Busca `#ordenCompraSelect` (`:712`) y `#form-recepcion-material` (`:716`) y hace early-return en `:718` porque **ninguno de esos IDs existe en `app/Views/`**. Aun si corriera, mandaría `p.ID_Producto` (`:775`) que el backend nunca devuelve |
| `app/Models/EntregasModel.php` | 1 sola referencia en todo `app/`: su propia declaración (`:7`) |
| `app/Models/DetalleEntregaModel.php` | Igual |
| `app/Models/MapeoProductosModel.php` | Igual |
| `app/Controllers/Inventario.php:30-35` | `index()` hace `return view('inventario/recepcion_manual')`. **El directorio `app/Views/inventario/` no existe** |

**Detalle de nomenclatura que importa al borrado:** la tabla se llama `DetalleEntrega` en **singular**, aunque la clase y el archivo sean en plural (`CreateDetalleEntregasTable.php:51` la crea como `'DetalleEntrega'`, `:56` la dropea). No existe una tabla `DetalleEntregas`. Y la PK de `HistorialProductos` es **`ID_HistorialP`**, con la P final (`HistorialProductosModel.php:13`), no `ID_Historial`.

---

## 8. Tres bugs activos

| # | Ubicación | Defecto |
|---|---|---|
| 1 | `Api.php:4051` | `registrarBajaDestruccion` lee el cuerpo con `getPost()`, pero `almacen.js` lo manda como JSON. **La baja por destrucción siempre responde 400** |
| 2 | `Inventario.php:129-139` | Precisión sobre el `created_at`: `Solicitud_Producto` **no tiene** columna `created_at`; `SolicitudProductModel` declara `$createdField = 'created_at'` (`:25-26`) con `$useTimestamps = false` (`:23`), lo que hace esos campos inertes. En cambio `Ingresos` y `HistorialProductos` **sí** tienen la columna pero sus modelos tampoco la llenan por `$useTimestamps = false`. Netamente: el historial nunca registra cuándo ocurrió el movimiento |
| 3 | `public/js/almacen.js` | La vista de historial usa `:key="registro.ID_Historial"`, que no existe. La PK real es `ID_HistorialP` |

**Riesgo de seguridad, no de datos.** Los 6 `case` de Almacén en `Modales::mostrar()` y los POST de productos **no revalidan el rol**. La ruta `modales/(:segment)` (`Routes.php:88`) está dentro del grupo con filtros `['auth','mantenimiento']` (`Routes.php:42`), así que cualquier usuario autenticado puede invocarlos por URL aunque no los vea en el menú. El patrón correcto ya existe en el repositorio: `Calendario::puedeEditarAgenda()`.

---

## 9. La clave de sesión del usuario

**Pregunta que se corollaryó durante la planeación: ¿cuál es la clave real del usuario en sesión?**

**Respuesta: `id`.** Y está en el código, no inferido.

| Evidencia | Detalle |
|---|---|
| `app/Controllers/Auth.php:76` | `'id' => $userData['ID_Usuario']` dentro de `$ses_data` |
| `app/Controllers/Auth.php:84` | `$this->session->set($ses_data)` |
| 94 lecturas en `app/` | Incluidas `Home.php:207` (`$usuarios->find(session('id'))`) y `Calendario.php` con 11 usos |
| `app/Views/layout/principal.php:27` | `window.CURRENT_USER_ID = <?= session('id') ?? 'null' ?>` — se expone al frontend, lo que confirma que la clave es estable y conocida |

**Defecto asociado.** El fallback `?? 1` aparece en:

| Ubicación | Código |
|---|---|
| `app/Controllers/Inventario.php:76` | `$usuarioID = session('id') ?? 1;` |
| `app/Controllers/Api.php:1592`, `:1640` | `'ID_Usuario_Cotiza' => session('id') ?? 1` |
| `app/Controllers/ControlMaestro.php:385`, `:499` | `session('id') ?? 1` |

Es un fallback **silencioso al usuario 1**: si la sesión no estuviera poblada, los movimientos se auditarían a nombre del usuario equivocado sin ningún error visible. En el módulo de inventario se elimina (INV-5).

---

## 10. Semántica de `Place`

| Evidencia | Detalle |
|---|---|
| `app/Models/PlacesModel.php:14` | `Places` es la tabla de sitios |
| Columnas | `ID_Place`, `Nombre_Corto`, `ID_RazonSocial` (agregada en `2026-02-11-182637`), `id_segmento` (agregada en `2026-03-03-130000`), `activo` (agregada en `2026-03-12-222634`) |
| `2026-03-12-181818_CreateUnidadOperativaAndRestructure.php:38` | `UnidadOperativa.ID_Place → Places.ID_Place` |

**Conclusión: `Place` es la sede física**, y una unidad operativa pertenece a un sitio. Eso sostiene la decisión D3 de que el stock se separe por sitio, y la D4 de que el sitio destino se pueda sugerir desde la UO de la requisición.

**Nota de nomenclatura heredada:** en `Catalogo_Productos`, la columna `ID_Dpto` **no** apunta a `Departamentos`. La llave foránea autoritativa es a `UnidadOperativa`:

| Evidencia | Detalle |
|---|---|
| `2026-04-24-164207_CreateCatalogoProductosTable.php:68` | FK original a `Departamentos(ID_Dpto)` |
| `2026-04-27-100500_UpdateCatalogoProductosFK.php:29` | Corregida a `UnidadOperativa(ID_UnidadOperativa)` |
| `CatalogoProductosModel.php:48`, `:60`, `:75` | Los tres joins del modelo confirman la corrección |

---

## 11. La cadena de compras

**No existe tabla de detalle de `OrdenCompra`.** Se verificó la lista completa de tablas del esquema `public` y no hay `OrdenCompra_Producto`, ni `Cotizacion_Producto`, ni `DetalleOrdenCompra`.

| Tabla | PK | ¿Tiene partidas? |
|---|---|---|
| `OrdenCompra` | `ID_OrdenCompra` | **No** — solo cabecera |
| `Cotizacion` | `ID_Cotizacion` | **No** — solo cabecera + `Total` |
| `Solicitud_Producto` | `ID_SolicitudProd` | **Sí** — es la única tabla de partidas de toda la cadena |

**Ruta de resolución** (identificadores entrecomillados, PostgreSQL):

```
OrdenCompra.ID_Cotizacion  →  Cotizacion.ID_Cotizacion
Cotizacion.ID_Solicitud    →  Solicitud.ID_Solicitud
Solicitud_Producto.ID_Solicitud → Solicitud.ID_Solicitud
```

**Historial del detalle:** la tabla `Detalle_Producto` fue creada por `2025-08-08-201604_CreateTableDetalleProd.php` (con `ID_DetalleProd`, `ID_SolicitudProd`, `Nombre_Prod`, `Cantidad`, `Costo`) y destruida por `2026-03-05-235954_DropTableDetalleProducto.php:11`. **`OrdenCompra` nunca tuvo tabla propia de partidas**: el concepto siempre colgó de `Solicitud_Producto`.

**Por qué esto ya no importa para el diseño.** Con D1, el inventario no resuelve productos desde la orden de compra —Almacén elige qué artículos entraron (RF-4). La ruta de 3 saltos solo se usa para el **default sugerido del sitio** (D4) y para el estado de recepción (D9).

**Efecto secundario a corregir en el módulo de compras (fuera de alcance).** `Archivo.php:285`:

```php
'Codigo' => $codigos[$i] ?? ($idCatalogo ? (string)$idCatalogo : null),
```

Cuando el frontend no manda código, se guarda el **ID del catálogo convertido a texto**. Por eso el dato vivo de la tabla 1 tiene `Codigo = "1"`. No se corrige aquí: con D1 el inventario ya no depende de ese campo.

---

## 12. Notas de esquema relevantes para la implementación

| Tema | Detalle |
|---|---|
| **Formato de migración** | `app/Config/Migrations.php:49` fija `$timestampFormat = 'Y-m-d-His_'`. Un archivo que no matchee `YYYY-MM-DD-HHMMSS_Nombre.php` se ignora **en silencio** |
| **Migración que nunca corrió** | `app/Database/Migrations/2026-09-03-AddFechaProgramacionToOrdenCompra.php` no matchea el patrón. Confirmado ausente en la tabla `migrations` |
| **Rama por driver** | Patrón canónico en `2026-07-31-143000_AddFechaComprobanteToOrdenCompra.php:11-53`: SQL crudo con `ADD COLUMN IF NOT EXISTS` / `CREATE INDEX IF NOT EXISTS` para `Postgre`, y `AFTER` para `MySQLi` |
| **Idempotencia en Postgres** | `information_schema.table_constraints` con `LOWER(table_name) = LOWER(?)` antes de agregar FKs, envuelto en `try/catch` (`2026-02-25-131148_AddGrupoPresupuestalToSolicitudProducto.php:57-67`) |
| **PostgreSQL** | Los identificadores son sensibles a mayúsculas y deben ir entrecomillados en SQL crudo |
| **CSRF** | Desactivado globalmente (`app/Config/Filters.php`, `csrf` comentado en `$globals`). Ningún POST de este módulo debe asumir protección por token |
| **Sin pruebas** | `vendor\bin\phpunit --no-coverage` corre 5 tests de ejemplo del framework. **La aplicación no tiene cobertura.** No hay E2E en el repositorio |
