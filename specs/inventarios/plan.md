# Plan: diseño técnico del módulo de inventarios

| Campo | Valor |
|---|---|
| **Estado** | Diseño cerrado — no implementado |
| **Implementa** | `spec.md` |
| **Evidencia** | `hallazgos.md` |
| **Punto de entrada** | `tasks.md` |
| **Fecha** | 2026-09-30 |

Este documento describe **cómo** construir lo que `spec.md` define **qué**. No autoriza la implementación por sí mismo.

---

## 1. Arquitectura

### 1.1 El patrón: un solo servicio

Todo el stock entra y sale por un único servicio. Ningún controlador escribe `InventarioStock` ni `Kardex` directamente.

```
   HTTP  ──►  Controladores  ──►  InventarioService  ──►  InventarioStock
              (Api, Modales,        (transacción +           (saldo actual)
               Inventario)           FOR UPDATE)                     │
                                                                        ▼
                                                                  Kardex
                                                            (append-only)
```

**Por qué un servicio y no lógica en los controladores.** El módulo tiene 7 flujos, y 4 de ellos mueven stock (RF-2, RF-3, RF-4, RF-5, RF-6 son cinco). Si cada controlador abriera su propia transacción, un ingreso manual y una recepción de OC divergirían en cómo manejan el saldo negativo, el `FOR UPDATE` y el rollback. El servicio es el único lugar donde se puede garantizar INV-1, INV-2, INV-4, INV-5 e INV-6.

**Por qué `InventarioService` en `app/Libraries/`.** Es la convención existente: `Rest.php` ya vive ahí y se instancia como `$this->api` en los controladores. `InventarioService` sigue el mismo patrón. No es un Model, porque opera sobre 3 tablas en una transacción y tiene lógica de negocio; no es un Controller, porque no habla HTTP.

### 1.2 La regla de oro

> **INV-4: nadie escribe `InventarioStock.Existencia` fuera de `InventarioService`.**

Se defiende en tres capas:

1. `InventarioStockModel::$allowedFields` **no incluye** `Existencia`.
2. Ninguna ruta llama a un método de escritura del model de stock directamente.
3. `inventario:verificar` (INV-2) detecta cualquier desviación.

### 1.3 Lo que NO existe en este diseño

- No hay tabla de existencias por requisición. El stock es `(artículo, sitio)`, punto.
- No hay columna que enlace `Producto` con `Catalogo_Productos` (D1).
- No hay valuación ni costo promedio (D5).
- No hay tabla de cabecera/detalle para entregas ni para bajas: van directo a `Kardex` (D7).
- No hay campo de precio ni importe en ninguna tabla de inventario.

---

## 2. Migraciones

**6 archivos, en este orden.** Cada uno es reversible (`down()` explícito), idempotente en su rama de Postgres, y usa el patrón de rama por driver de `2026-07-31-143000_AddFechaComprobanteToOrdenCompra.php:11-53`.

**Regla de nomenclatura, sin excepciones:** `YYYY-MM-DD-HHMMSS_Nombre.php`. CI4 ignora en silencio cualquier otro nombre (`app/Config/Migrations.php:49`). No repetir el error de `2026-09-03-AddFechaProgramacionToOrdenCompra.php`, que nunca corrió.

### M1 · `ReshapeProducto`

`Producto` se resucita como maestro del artículo físico (D6). Se le **quita `Existencia`** y se le **agrega `UnidadMedida`**.

| Driver | SQL |
|---|---|
| Postgres | `ALTER TABLE "Producto" ADD COLUMN IF NOT EXISTS "UnidadMedida" VARCHAR(20) NULL` y `ALTER TABLE "Producto" DROP COLUMN IF EXISTS "Existencia"` |
| MySQL | `ALTER TABLE Producto ADD COLUMN UnidadMedida VARCHAR(20) NULL` y `ALTER TABLE Producto DROP COLUMN Existencia` |

- `UnidadMedida` **nulable**: la carga inicial no siempre la conoce, y no queremos bloquear el alta.
- Se elimina `Existencia` en vez de dejarla huérfana: su presencia sería la invitación más tentadora a reintroducir el bug de escritura directa. **No se lleva ningún dato**: la tabla tiene 0 filas.
- `down()`: vuelve a agregar `Existencia INT unsigned NOT NULL DEFAULT 0` y elimina `UnidadMedida`.
- **Prerrequisito:** no requiere limpieza previa. Las 6 tablas que la referencian mueren en M6.

### M2 · `CreateInventarioStockTable`

| Columna | Tipo | Nota |
|---|---|---|
| `ID_Producto` | INT, NOT NULL | FK → `Producto(ID_Producto)`, `ON DELETE RESTRICT` |
| `ID_Place` | BIGINT, NOT NULL | FK → `Places(ID_Place)`, `ON DELETE RESTRICT` |
| `Existencia` | `NUMERIC(12,2)`, NOT NULL, default 0 | **`CHECK (Existencia >= 0)` — constraint real, no un comentario** |
| `created_at`, `updated_at` | TIMESTAMP | |

- **PK compuesta** `(ID_Producto, ID_Place)`.
- `ON DELETE RESTRICT`, no `CASCADE`: si el kardex es la fuente de verdad, un artículo con movimientos no se puede borrar. `RESTRICT` hace que el error sea explícito en vez de perder historia.
- El `CHECK` corrige el defecto de `CreateTableProducto.php:31`, donde "Equivale a CHECK (Existencia >= 0)" era solo un comentario en el archivo de migración y nunca existió en la base.
- `NUMERIC(12,2)`, no `INT`: existen cantidades fraccionarias (litros de reactivo, kg, metros). `INT` truncaría silenciosamente.

### M3 · `CreateKardexTable`

La fuente única de verdad (D5, D7). **Append-only**: no se edita ni se borra.

| Columna | Tipo | Nota |
|---|---|---|
| `ID_Kardex` | BIGSERIAL, PK | |
| `ID_Producto` | INT, NOT NULL | FK → `Producto` |
| `ID_Place` | BIGINT, NOT NULL | FK → `Places` |
| `Fecha` | TIMESTAMP, NOT NULL | default `CURRENT_TIMESTAMP` |
| `Tipo` | VARCHAR(24), NOT NULL | `INGRESO_MANUAL`, `RECEPCION_OC`, `CARGA_INICIAL`, `ENTREGA`, `BAJA` |
| `Cantidad` | `NUMERIC(12,2)`, NOT NULL | **con signo**: positiva entra, negativa sale |
| `Existencia_Ant` | `NUMERIC(12,2)`, NOT NULL | saldo antes del movimiento |
| `Existencia_Nueva` | `NUMERIC(12,2)`, NOT NULL | saldo después |
| `ID_Usuario` | INT, **NOT NULL** | FK → `Usuarios(ID_Usuario)`. Sin default (INV-5) |
| `Documento_Tipo` | VARCHAR(24), NULL | `RECEPCION`, `ENTREGA`, `CARGA_INICIAL` |
| `Documento_ID` | INT, NULL | id de la cabecera, sin FK: polimórfico |
| `Documento_Folio` | VARCHAR(60), NULL | para mostrar en pantalla |
| `Documento_Lote` | UUID, NULL | agrupa las N líneas de un mismo documento (RF-5) |
| `ID_Linea_Origen` | INT, NULL | puntero blando a `Recepcion_Linea`. **Sin FK**, a propósito |
| `Motivo` | TEXT, NULL | **obligatorio en aplicación** si `Tipo = 'BAJA'` (RF-6) |
| `created_at` | TIMESTAMP, NOT NULL | |

Índices: `(ID_Producto, Fecha DESC)`, `(Documento_Lote)`, `(ID_Usuario)`, `(Tipo)`.

**Por qué `ID_Linea_Origen` no lleva llave foránea.** Aprobado en la revisión de `spec.md`. La razón: `Kardex` es append-only y no debe poder ser bloqueado ni invalidado por el borrado de una recepción. El puntero blando sirve para rastrear el origen sin crear acoplamiento de integridad entre el ledger y las cabeceras, que van y vienen.

**Por qué `Documento_ID` no lleva FK.** El ledger registra documentos de 3 tipos distintos con 3 esquemas distintos. Una FK polimórfica no existe en PostgreSQL; forzarla con 3 columnas nullable nullable sería peor. `Documento_Tipo` desambigua.

### M4 · `CreateRecepcionTables`

**Reemplazan** a `Ingresos` + `DetalleIngreso` (D7).

**`Recepcion`** (cabecera):

| Columna | Tipo | Nota |
|---|---|---|
| `ID_Recepcion` | SERIAL, PK | |
| `ID_OrdenCompra` | INT, **NULL** | FK → `OrdenCompra`, `ON DELETE SET NULL`. **Nulable por D2** |
| `ID_Proveedor` | INT, NOT NULL | FK → `Proveedor` |
| `ID_Place` | BIGINT, NOT NULL | FK → `Places`. **Obligatorio: el sitio lo elige Almacén (D4)** |
| `ID_Usuario` | INT, NOT NULL | quién recibió |
| `Fecha` | TIMESTAMP, NOT NULL | |
| `UUID` | VARCHAR(36), **UNIQUE, NULL** | dedupe del CFDI. Solo si hay factura |
| `RFC_Receptor` | VARCHAR(13), NULL | |
| `FechaEmision` | DATETIME, NULL | |
| `Observaciones` | TEXT, NULL | |
| `created_at` | TIMESTAMP, NOT NULL | |

`UUID` es `UNIQUE` pero **nulable**: en PostgreSQL, `NULL` no colisiona con `NULL` en un índice único, así que N ingresos manuales sin factura conviven sin problema, y un `UUID` repetido sí se rechaza (RF-3).

**`Recepcion_Linea`** (detalle):

| Columna | Tipo | Nota |
|---|---|---|
| `ID_Recepcion_Linea` | SERIAL, PK | |
| `ID_Recepcion` | INT, NOT NULL | FK → `Recepcion`, `ON DELETE CASCADE` |
| `ID_Producto` | INT, **NOT NULL** | FK → `Producto`, `ON DELETE RESTRICT`. **El artículo físico que llegó** |
| `Cantidad` | `NUMERIC(12,2)`, NOT NULL | `CHECK (Cantidad > 0)` |
| `ID_SolicitudProd` | INT, **NULL** | FK → `Solicitud_Producto`, `ON DELETE SET NULL`. **Nulable: la vinculación es opcional (RF-4)** |
| `Observaciones` | TEXT, NULL | |
| `created_at` | TIMESTAMP, NOT NULL | |

`ON DELETE CASCADE` en el detalle **sí** es correcto: es el único borrado en cascada del diseño, y solo aplica si alguien borra explícitamente una recepción, lo cual la UI no ofrece.

### M5 · `AddRecepcionFieldsToSolicitudProducto`

| Columna | Tipo | Nota |
|---|---|---|
| `Estado_Recepcion` | VARCHAR(20), NOT NULL, default `'PENDIENTE'` | `PENDIENTE`, `PARCIAL`, `TOTAL`, `NO_ENTREGADO`, `NO_APLICA` (D9) |
| `Cantidad_Recibida` | `NUMERIC(12,2)`, NOT NULL, default 0 | **`CHECK (Cantidad_Recibida >= 0)`** |

- `DEFAULT 'PENDIENTE'` en BD, no en PHP: las 2 filas existentes de `Solicitud_Producto` quedan en `PENDIENTE` sin que nadie las toque.
- `CHECK (Cantidad_Recibida <= "Cantidad")` — **INV-3 a nivel de base de datos.** Referencia entrecomillada, PostgreSQL.
- Actualizar `$allowedFields` de `SolicitudProductModel` para que `protectFields` deje pasar ambas. Sin esto, el `UPDATE` es un no-op silencioso (`hallazgos.md` §3).

### M6 · `DropLegacyInventoryTables`

6 tablas, en este orden (hijas antes que madres, por las FK):

| # | Tabla | Razón |
|---|---|---|
| 1 | `HistorialProductos` | Reemplazado por `Kardex` |
| 2 | `MapeoProductos` | 0 filas, 0 referencias, 0 columnas hacia el catálogo (D8) |
| 3 | `DetalleEntrega` | Singular. FK → `Producto` |
| 4 | `Entregas` | FK → `DetalleEntrega` |
| 5 | `DetalleIngreso` | FK → `Producto` |
| 6 | `Ingresos` | FK → `DetalleIngreso` |

- Todas con 0 filas. **No hay migración de datos.**
- `dropTable('X', true)` para la versión con datos, y el orden importa: intentar dropear `Ingresos` antes de `DetalleIngreso` falla por la FK.
- Se borran también los 6 Models correspondientes.
- **Precondición dura:** M6 no puede correr hasta que `Rest.php` y `Modales.php` dejen de referenciar `Producto` por las rutas legacy. Ver §5.

---

## 3. `InventarioService`

`app/Libraries/InventarioService.php`, instanciado como `$this->inventario` en los controladores, siguiendo el patrón de `Rest.php`.

### 3.1 Interfaz

| Método | Responsabilidad |
|---|---|
| `registrarMovimiento(int $idProducto, int $idPlace, string $tipo, float $cantidad, array $contexto = []): int` | **El corazón.** Transacción + `FOR UPDATE` + validación + escritura en las 2 tablas. Devuelve `ID_Kardex`. |
| `getSaldos(array $filtros = []): array` | RF-7. Lee `InventarioStock` con `Nombre` del producto y del sitio |
| `getKardex(int $idProducto, ?int $idPlace = null, array $filtros = []): array` | RF-7. Filtra por artículo, sitio, tipo, fechas, usuario |
| `verificarInvariante(): array` | INV-2. Recalcula `SUM(Cantidad)` y compara contra `InventarioStock`. Devuelve el desbalance |
| `recalcularEstadoRecepcion(int $idSolicitudProd): string` | D9. Traduce `Cantidad_Recibida` en estado |
| `puedeOperarAlmacen(): bool` | Autorización. 403 centralizado |

### 3.2 `registrarMovimiento`, paso a paso

Es la operación que garantiza INV-1, INV-2, INV-4, INV-5 e INV-6. La secuencia importa:

```
1.  $db->transException(true)->transStart();
2.  SELECT Existencia FROM InventarioStock
        WHERE ID_Producto = ? AND ID_Place = ?
        FOR UPDATE;                        ← INV-6
3.  si no existe fila:
        INSERT InventarioStock (…, Existencia = 0);
        $existenciaAnt = 0;
    si existe:
        $existenciaAnt = fila.Existencia;
4.  $existenciaNueva = $existenciaAnt + $cantidad;
5.  if ($existenciaNueva < 0) { throw DomainException; }   ← INV-1
6.  UPDATE InventarioStock SET Existencia = ? WHERE (ID_Producto, ID_Place);
7.  INSERT Kardex (ID_Producto, ID_Place, Fecha, Tipo, Cantidad,
                   Existencia_Ant, Existencia_Nueva, ID_Usuario,
                   Documento_Tipo, Documento_ID, Documento_Folio,
                   Documento_Lote, ID_Linea_Origen, Motivo);  ← INV-2, INV-4, INV-5
8.  $db->transComplete();
```

**Puntos donde este diseño arregla defectos existentes:**

| Defecto actual | Arreglo |
|---|---|
| `Api.php:3981` escribe el stock **antes** de un fatal posterior | Paso 5 valida **antes** del paso 6 |
| `Api.php:3912` usa `transStart()` sin `transException(true)` | Paso 1 lo agrega |
| `Inventario.php:76` audita al usuario 1 si falla la sesión | Paso 7 exige `ID_Usuario` real; sin sesión, `puedeOperarAlmacen()` ya devolvió 403 |
| `CreateTableProducto.php:31` — el CHECK era un comentario | M2 crea el constraint real |
| `Modales.php:1064` hace clamp-a-cero en entregas | Paso 5 lanza excepción; el clamp se elimina |
| Las entregas no dejan rastro | Paso 7 escribe `Kardex` con `Tipo = 'ENTREGA'` |

**Por qué `FOR UPDATE` y no solo una transacción.** Sin bloqueo de fila, dos ingresos concurrentes sobre el mismo `(artículo, sitio)` leen el mismo saldo y ambos escriben `Existencia_Ant` igual. El ledger quedaría descuadrado y INV-2 se rompería de forma intermitente, difícil de reproducir. `SELECT ... FOR UPDATE` serializa la lectura y la escritura.

**Sobre `transException(true)`.** Hace que una excepción no capturada revierta y propague. Sin él, CI4 revierte solo si el error es una `DatabaseException`. Los `DomainException` de negocio del paso 5 se capturan explícitamente y se convierten en 422.

### 3.3 Autorización

`puedeOperarAlmacen()` resuelve el rol por el patrón de `Calendario::rolCalendario()` (`Calendario.php:554`): `session('id_departamento_usuario')` + `DepartamentosModel`, quitando el sufijo `" (Place)"` y normalizando acentos.

**Motivo, en `hallazgos.md` §8:** los 6 `case` de Almacén en `Modales::mostrar()` no revalidan el rol. La ruta `modales/(:segment)` (`Routes.php:88`) está dentro del grupo con filtros `['auth','mantenimiento']` (`Routes.php:42`), así que cualquier usuario autenticado puede invocarlos por URL aunque no los vea en el menú. **Ocultar el menú no es seguridad.** El revalidado se aplica en los 3 controladores, no solo en la vista.

---

## 4. Modelo final

| Model | Estado | `$allowedFields` |
|---|---|---|
| `ProductoModel` | **Modificar** | `['Codigo', 'Nombre', 'UnidadMedida']` — quitar `Existencia` |
| `InventarioStockModel` | **Nuevo** | `['ID_Producto', 'ID_Place', 'Existencia']` — `protected $primaryKey` no aplica: PK compuesta. Se accede por el servicio, no por `$model->update()` |
| `KardexModel` | **Nuevo** | Solo lectura desde la UI. Las escrituras las hace el servicio |
| `RecepcionModel` | **Nuevo** | `['ID_OrdenCompra', 'ID_Proveedor', 'ID_Place', 'ID_Usuario', 'Fecha', 'UUID', 'RFC_Receptor', 'FechaEmision', 'Observaciones']` |
| `RecepcionLineaModel` | **Nuevo** | `['ID_Recepcion', 'ID_Producto', 'Cantidad', 'ID_SolicitudProd', 'Observaciones']` |
| `SolicitudProductModel` | **Modificar** | `+ 'Estado_Recepcion', 'Cantidad_Recibida'` |
| `IngresosModel`, `DetalleIngresoModel` | **Borrar** | |
| `EntregasModel`, `DetalleEntregaModel` | **Borrar** | |
| `HistorialProductosModel`, `MapeoProductosModel` | **Borrar** | |

`ProductoModel.php` es de 28 líneas hoy y su `allowedFields` es de 3 elementos. El cambio es de 1 palabra.

---

## 5. Cambios backend

### 5.1 Controladores

| Archivo | Acción |
|---|---|
| `app/Controllers/Api.php` | **Reescribir** `confirmarRecepcion` (`:3885-4044`): transaccional, con `ID_Place` obligatorio, con selección de artículos por Almacén, con validación de reintento. **Arreglar** `registrarBajaDestruccion` (`:4051`): leer JSON. Agregar 403 |
| `app/Controllers/Inventario.php` | **Reescribir.** Hoy apunta a `app/Views/inventario/recepcion_manual`, que **no existe** (`hallazgos.md` §7). Pasa a ser el controlador del ingreso manual y la carga inicial, delegando todo movimiento al servicio. Quitar el `?? 1` de `:76` |
| `app/Controllers/Modales.php` | **Reescribir** los 6 `case` de Almacén. Agregar el revalidado de rol. Matar el clamp-a-cero de `:1064` |
| `app/Controllers/Auth.php` | **Sin cambios.** Ya setea `session('id')` correctamente |

### 5.2 Librerías

| Archivo | Acción |
|---|---|
| `app/Libraries/InventarioService.php` | **Nuevo.** §3 |
| `app/Libraries/Rest.php` | **Eliminar** 2 bloques: `:1795-1865` (`getProductById`, `getProductsByCode`, `getProductsByName`, `registrarProductoArray`, `registrarProducto`) y `:2078-2113` (`eliminarProductoById`, `actualizarProducto`, `getAllProducts`). Son envoltorios CRUD sin lógica, con equivalentes de catálogo desde `:1933` |
| `app/Libraries/Status.php` | **Agregar 5 constantes**: `RECEPCION_PENDIENTE`, `RECEPCION_PARCIAL`, `RECEPCION_TOTAL`, `RECEPCION_NO_ENTREGADO`, `RECEPCION_NO_APLICA` |
| `app/Libraries/XmlCfdiReader.php` | **Sin cambios.** Fuera de alcance. Permanece sin uso (`hallazgos.md` §4) |

### 5.3 Comando de verificación

`app/Commands/VerificarInventario.php` → `php spark inventario:verificar`.

Recorre todos los pares `(ID_Producto, ID_Place)` de `Kardex`, recalcula `SUM(Cantidad)` y compara contra `InventarioStock.Existencia`. Reporta cada desviación.

**Por qué un comando y no un test.** El repositorio no tiene suite de pruebas de aplicación: `vendor\bin\phpunit --no-coverage` corre 5 tests de ejemplo del framework y nada más (`hallazgos.md` §12). Un comando `spark` es ejecutable en el servidor por cualquiera, sin instalar nada, y sirve tanto como verificación de migración como chequeo recurrente.

**Advertencia esperada:** en los 5 primeros minutos después de M2, `InventarioStock` estará vacía y `Kardex` también, así que el comando reporta 0 desviaciones. Después de M3, cualquier fila de `Kardex` sin su fila de `InventarioStock` es una desviación real.

---

## 6. Cambios frontend

### 6.1 Menú — 4 registros obligatorios

`almacen` no aparece con un solo cambio. Según la convención del proyecto, un item de menú nuevo requiere 4 registros coordinados. **Faltando cualquiera, el item aparece vacío o devuelve "Opción no válida".**

| # | Archivo | Cambio |
|---|---|---|
| 1 | `app/Config/MenuOptions.php:123-132` | **Descomentar** `TituloAlmacen` y `almacen`. Restaurar también las claves hijas `crud_productos`, `recepcion_material`, `entrega_productos`, `bajas_destruccion` que quitó el commit `dbb010d` |
| 2 | `app/Controllers/Home.php:81-89` | Las claves deben **coincidir exactamente** con las de `MenuOptions`. `Almacen` las ya tiene; `Administración` recibe todo con `array_keys()`, así que no requiere nada. Verificar que `Contaduría:134-140` sigue coherente |
| 3 | `app/Controllers/Modales.php` | Los `case` correspondientes en el `switch` de `mostrar()` |
| 4 | `public/js/mbscript.js` | El título de cada opción en el objeto `titulos`, usado por `abrirModal(opcion)` |

Las rutas API van en `app/Config/Routes.php`, siempre **dentro** del grupo con `['filter' => ['auth','mantenimiento']]` (`Routes.php:42`).

### 6.2 JavaScript

`public/js/almacen.js` se reescribe.

| Acción | Detalle |
|---|---|
| **Borrar** | `initRecepcionMaterial()` (`:711-865`), 155 líneas muertas. Busca `#ordenCompraSelect` y `#form-recepcion-material`, que no existen en ninguna vista |
| **Agregar** | Inicializadores para los 7 flujos de RF-1 a RF-7 |
| **Arreglar** | El `:key="registro.ID_Historial"` del historial. La PK real es `ID_HistorialP`, y de todos modos la vista de historial se reemplaza por la consulta de `Kardex` |
| **Arreglar** | `registrarBajaDestruccion` debe mandar JSON, no `getPost()` |

**Sobre Alpine vs. el patrón existente.** El módulo usa Alpine con `x-data` e inicializadores en `almacen.js`. Se mantiene ese patrón en vez de introducir un framework nuevo.

### 6.3 Vistas

7 vistas, una por flujo funcional, en `app/Views/`:

| Vista | Flujo |
|---|---|
| `modales/productos.php` | RF-1 |
| `modales/carga_inicial.php` | RF-2 |
| `modales/ingreso_manual.php` | RF-3 |
| `modales/recepcion_oc.php` | RF-4 |
| `modales/entregas.php` | RF-5 |
| `modelas/bajas.php` | RF-6 |
| `modales/inventario.php` | RF-7, con el saldo actual y el kardex juntos |

### 6.4 CSS

`public/css/styless.css` **está versionado en git**. Si se agregan clases Tailwind, hay que recompilar y commitear el CSS compilado, o el cambio no llega a producción.

- `input.css` es la fuente, con config CSS-first (`@import "tailwindcss"` + `@theme`).
- `tailwind.config.js` es estilo legacy v3 y **solo su `safelist` se respeta**. Si se generan nombres de clase dinámicamente en JS, hay que agregarlos a la `safelist` o no compilan.
- `public/css/fullcalendar-{core,daygrid,list,timegrid}.css` son stubs rotos: contienen el mensaje de error de FullCalendar. Los que funcionan son `fullcalendar-carbon.css` y `agenda.css`. No affects este módulo.

---

## 7. Orden de ejecución y sus dependencias

```
M1 ReshapeProducto
  └─► M2 CreateInventarioStock
        └─► M3 CreateKardex
              └─► M4 CreateRecepcion + Recepcion_Linea
                    └─► M5 AddRecepcionFieldsToSolicitudProducto
                          └─► M6 DropLegacyTables   ← requiere backend limpio primero
```

**M6 tiene una precondición que las otras no.** No se puede dropear `Ingresos` mientras `Modales.php` y `Rest.php` la referencien. El orden real de trabajo es:

1. Backend limpio: reescribir controladores, eliminar bloques legacy de `Rest.php`, borrar los 6 Models.
2. M1 a M5.
3. M6.
4. Cablear el menú (4 registros).
5. Frontend.
6. Verificar.

Hacer M6 antes del paso 1 deja el código roto en un estado intermedio. **La tabla vive; el código se lava primero.**

---

## 8. Verificación

No hay suite de pruebas de aplicación, así que la verificación es explícita y manual.

| # | Verificación | Cómo |
|---|---|---|
| V1 | Migraciones aplicadas | `php spark migrate:status --all`. Las 6 deben aparecer |
| V2 | INV-2 | `php spark inventario:verificar` → 0 desviaciones |
| V3 | INV-1 | Intentar una entrega mayor al saldo → 422, no error 500 |
| V4 | INV-4 | `grep -rn "InventarioStock" app/` → solo el servicio y el model |
| V5 | INV-5 | `grep -rn "?? 1" app/Controllers/Inventario.php` → 0 resultados |
| V6 | INV-3 | `UPDATE` manual de `Cantidad_Recibida` por encima de `Cantidad` → error de constraint |
| V7 | Menú | Entrar como `Almacen` y como `Contaduría`. Ambos deben ver el item y abrir los modales |
| V8 | 403 | Entrar como `Contaduría` y llamar por URL a un POST de almacén → 403, no 200 |
| V9 | E2E | Recorrer RF-1 a RF-7 con prefijo `[TEST-QA]`, luego verificar que el kardex cuadra |

**Sobre V7 y V8.** La diferencia entre ambos es el punto entero de la sección de autorización. Un menú que se ve pero devuelve 403 a medias es peor que un menú oculto, porque induce al usuario a un error sin explicación.

---

## 9. Riesgos

| # | Riesgo | Mitigación |
|---|---|---|
| R1 | **M6 rompe el sistema** si corre antes de limpiar el código | La precondición de §7. `migrate:rollback` restaura. Y como todo tiene 0 filas, no hay pérdida de datos |
| R2 | El `CHECK` de `Existencia >= 0` choca con una corrección manual de saldos | Es intencional. Un saldo negativo indica un bug, y el constraint lo hace visible en vez de propagarlo |
| R3 | Correlación de concurrencia mal resuelta | `FOR UPDATE` en el servicio. V2 lo detecta |
| R4 | La UI permite elegir un sitio que no corresponde al segmento | El selector se filtra por los lugares del usuario, no se lista completo |
| R5 | El sistema asume que toda OC tiene material almacenable | RF-4 y `NO_APLICA`. Almacén clasifica explícitamente |
| R6 | Confundir el catálogo con el inventario al escribir código nuevo | §1.3. Si una consulta a `Catalogo_Productos` aparece dentro de `app/Controllers/Inventario.php` o `api/inventario`, es un error de diseño, no un bug |
| R7 | El cambio de `Producto` rompe 14 puntos de referencia | El plan los cubre todos en §5.1 y §5.2. `grep -rn "Producto" app/` da la lista exacta |
| R8 | `ID_Linea_Origen` sin FK se queda huérfano | Aceptado a propósito. `Kardex` es append-only y las recepciones no se borran desde la UI |

---

## 10. Lo que este plan no cubre

- **El módulo de Migraciones.** `MigracionesController.php:351` sigue insertando en `Catalogo_Productos`. Con D1 eso ya no causa conflicto. No se toca.
- **`Archivo.php:285`**, que guarda el ID del catálogo como `Codigo` cuando el frontend no manda código. Es un defecto preexistente del módulo de requisiciones. Con D1 el inventario ya no depende de ese campo, así que **no se corrige aquí**.
- **`XmlCfdiReader.php`.** Sin uso, fuera de alcance.
- **Migración de datos.** Las 6 tablas tienen 0 filas. Las 2 filas de `Cotizacion`/`Solicitud` son andamiaje de prueba.
- **Valuación, stock mínimo, códigos de barras, múltiples almacenes por sede.** Los no-objetivos de `spec.md` §9.
