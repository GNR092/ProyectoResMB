# Tasks: implementación del módulo de inventarios

| Campo | Valor |
|---|---|
| **Estado** | Pendiente — ninguna tarea ejecutada |
| **Implemente** | `plan.md` |
| **Especifica** | `spec.md` |
| **Fecha** | 2026-09-30 |

**Este es el punto de entrada de la sesión de implementación.** Ninguna tarea de este documento se ejecutó en la sesión de planeación.

**Cómo usar este documento.** Las 6 fases son **secuenciales y bloqueantes**: la F2 no arranca hasta que F1 cierra, y F5 no arranca hasta que F2 cierra. Las tareas dentro de una fase sí son paralelizables entre sí. Cada criterio de salida es **verificable**, no una opinión.

**Antes de empezar, lee `spec.md` §3 (las 10 decisiones).** Casi cualquier duda que surja durante la implementación tiene su respuesta ahí.

---

## F0 · Prerrequisitos

Sin esto, nada más es posible.

- [ ] `composer install`
- [ ] `npm install`
- [ ] `php spark serve` corriendo en una terminal
- [ ] `npm run watch` corriendo en otra terminal
- [ ] `php spark migrate:status --all` ejecutado y su salida guardada como línea base
- [ ] `git status` limpio
- [ ] `php spark routes` ejecutado, para tener el mapa de rutas antes de que cambien

**Salida de fase:** la app levanta, el CSS compila, y existe una línea base de migraciones contra la cual comparar.

---

## F1 · Limpieza del código muerto

**Se hace primero, antes de tocar el esquema.** M6 no puede correr hasta que estas referencias desaparezcan, y desarrollar contra código muerto produce código muerto nuevo.

- [ ] Eliminar `public/js/almacen.js:711-865` (`initRecepcionMaterial`, 155 líneas)
- [ ] Eliminar el bloque `app/Libraries/Rest.php:1795-1865` (`getProductById`, `getProductsByCode`, `getProductsByName`, `registrarProductoArray`, `registrarProducto`)
- [ ] Eliminar el bloque `app/Libraries/Rest.php:2078-2113` (`eliminarProductoById`, `actualizarProducto`, `getAllProducts`)
- [ ] Borrar `app/Models/HistorialProductosModel.php`
- [ ] Borrar `app/Models/MapeoProductosModel.php`
- [ ] Borrar `app/Models/EntregasModel.php`
- [ ] Borrar `app/Models/DetalleEntregaModel.php`
- [ ] Borrar `app/Models/IngresosModel.php`
- [ ] Borrar `app/Models/DetalleIngresoModel.php`
- [ ] Borrar `app/Controllers/Inventario.php:30-35` (`index()` que apunta a una vista inexistente)
- [ ] `grep -rn "HistorialProductos\|MapeoProductos\|DetalleEntrega\|Ingresos" app/` → 0 resultados

**Salida de fase:** ningún archivo de `app/` referencia las 6 tablas que van a morir.

---

## F2 · Esquema — 6 migraciones

**En este orden exacto.** El nombre de cada archivo debe ser `YYYY-MM-DD-HHMMSS_Nombre.php`; CI4 ignora en silencio cualquier otro patrón. Crea cada uno con `php spark make:migration`.

- [ ] **M1** `ReshapeProducto` — `+ UnidadMedida VARCHAR(20) NULL`, `- Existencia`
- [ ] **M2** `CreateInventarioStockTable` — PK `(ID_Producto, ID_Place)`, `CHECK (Existencia >= 0)`, `NUMERIC(12,2)`
- [ ] **M3** `CreateKardexTable` — append-only, `Cantidad` con signo, `ID_Usuario` NOT NULL sin default, `ID_Linea_Origen` **sin FK**
- [ ] **M4** `CreateRecepcionTables` — `Recepcion.ID_OrdenCompra` NULL, `UUID` UNIQUE NULL, `Recepcion_Linea.ID_SolicitudProd` NULL
- [ ] **M5** `AddRecepcionFieldsToSolicitudProducto` — `Estado_Recepcion` default `'PENDIENTE'`, `Cantidad_Recibida` default 0, `CHECK (Cantidad_Recibida <= "Cantidad")`
- [ ] **M6** `DropLegacyInventoryTables` — en orden: `HistorialProductos`, `MapeoProductos`, `DetalleEntrega`, `Entregas`, `DetalleIngreso`, `Ingresos`
- [ ] Cada migración con `down()` explícito
- [ ] Cada migración con la rama `Postgre` y la rama `MySQLi` (patrón de `2026-07-31-143000_AddFechaComprobanteToOrdenCompra.php:11-53`)
- [ ] `php spark migrate:status --all` → las 6 aparecen
- [ ] `php spark migrate` → 6 migraciones aplicadas
- [ ] `php spark migrate:rollback --all` → las 6 revierten sin error
- [ ] `php spark migrate` → reaplicadas
- [ ] `\d "Producto"`, `\d InventarioStock`, `\d Kardex`, `\d Recepcion`, `\d Recepcion_Linea` en `psql` → los constraints existen de verdad

**Salida de fase:** el esquema está completo, los constraints verificados con `\d`, y el ciclo migrate → rollback → migrate funciona limpio.

**Atención:** el `CHECK` de `Existencia >= 0` no es un comentario. En `CreateTableProducto.php:31` el "CHECK" era solo texto en el archivo y nunca existió en la base. Verifícalo con `\d InventarioStock`, no leyendo la migración.

---

## F3 · Modelos

- [ ] `app/Models/ProductoModel.php` → `allowedFields = ['Codigo', 'Nombre', 'UnidadMedida']` (quitar `Existencia`)
- [ ] `app/Models/InventarioStockModel.php` → nuevo. `allowedFields` **no** incluye `Existencia`
- [ ] `app/Models/KardexModel.php` → nuevo
- [ ] `app/Models/RecepcionModel.php` → nuevo
- [ ] `app/Models/RecepcionLineaModel.php` → nuevo
- [ ] `app/Models/SolicitudProductModel.php` → `+ 'Estado_Recepcion', 'Cantidad_Recibida'` a `allowedFields`
- [ ] `app/Libraries/Status.php` → `+ RECEPCION_PENDIENTE`, `RECEPCION_PARCIAL`, `RECEPCION_TOTAL`, `RECEPCION_NO_ENTREGADO`, `RECEPCION_NO_APLICA`
- [ ] Verificar que ningún Model de stock declara `$useTimestamps` de forma inconsistente con su tabla

**Salida de fase:** los 5 models reflected con `$db->protectFields`. `Estado_Recepcion` y `Cantidad_Recibida` se pueden escribir; `Existencia` no.

---

## F4 · `InventarioService`

**El corazón del módulo.** Si esto no está bien, nada más importa.

- [ ] `app/Libraries/InventarioService.php` nuevo
- [ ] `registrarMovimiento()` con la secuencia exacta de `plan.md` §3.2: `transException(true)` → `SELECT ... FOR UPDATE` → crear fila si no existe → calcular → **validar `>= 0` antes de escribir** → `UPDATE` stock → `INSERT` kardex → `transComplete()`
- [ ] `getSaldos()` — RF-7, con nombre de producto y de sitio
- [ ] `getKardex()` — RF-7, filtros por artículo, sitio, tipo, rango de fechas, usuario; orden `Fecha DESC`
- [ ] `verificarInvariante()` — recalcula `SUM(Kardex.Cantidad)` y compara contra `InventarioStock`
- [ ] `recalcularEstadoRecepcion()` — traduce `Cantidad_Recibida` a estado
- [ ] `puedeOperarAlmacen()` — patrón de `Calendario::rolCalendario()` (`Calendario.php:554`)
- [ ] `app/Commands/VerificarInventario.php` nuevo → `php spark inventario:verificar`
- [ ] Instanciar como `$this->inventario` en `Api.php`, `Modales.php`, `Inventario.php`

**Salida de fase:** `php spark inventario:verificar` corre y reporta 0 desviaciones.

**Punto que no se negocia:** el paso 5 de `registrarMovimiento` valida el saldo **antes** del `UPDATE`. El bug actual (`Api.php:3981`) escribe primero y revienta después.

---

## F5 · Controladores

### 5a · Autorización primero

- [ ] `app/Controllers/Modales.php` → los 6 `case` de Almacén revalidan el rol → 403
- [ ] `app/Controllers/Api.php` → los POST de inventario revalidan el rol → 403
- [ ] `app/Controllers/Inventario.php` → revalida el rol → 403
- [ ] Probar: como `Contaduría`, llamar por URL a un POST de almacén → **403**, no 200

**Esto va primero a propósito.** Sin esto, todo lo demás queda accesible por URL a cualquier usuario autenticado (`hallazgos.md` §8).

### 5b · Los 7 flujos

- [ ] **RF-1** Alta de artículo: `Codigo` UNIQUE → 422 si se repite; `Codigo` inmutable tras crearse
- [ ] **RF-2** Carga inicial: crea el maestro si no existe; crea la fila de stock del sitio si no existe; `Tipo = 'CARGA_INICIAL'`; atómica
- [ ] **RF-3** Ingreso manual: `ID_OrdenCompra` en null; `ID_Place` **obligatorio**; `UUID` repetido → 422; sin `UUID` → se acepta
- [ ] **RF-4** Recepción de OC: Almacén **elige** los artículos; `ID_Place` con default sugerido desde `UnidadOperativa.ID_Place`; acumula `Cantidad_Recibida`; supera `Cantidad` → 422; reintento → 422; clasifica `NO_ENTREGADO` y `NO_APLICA` al cerrar; atómica
- [ ] **RF-5** Entrega: receptor **obligatorio**; exceder saldo → 422; **eliminar el clamp-a-cero de `Modales.php:1064`**; `Documento_Lote` compartido
- [ ] **RF-6** Baja: motivo **obligatorio**; exceder saldo → 422 (conservar `Api.php:4066`); motivo vacío → 422
- [ ] **RF-7** Consulta: saldos por `(artículo, sitio)`; kardex filtrable
- [ ] **Arreglar** `Api.php:4051` — `registrarBajaDestruccion` debe leer JSON, no `getPost()`
- [ ] **Reescribir** `Api::confirmarRecepcion` (`Api.php:3885-4044`) — la versión actual tiene un error fatal
- [ ] **Eliminar** `session('id') ?? 1` en `Inventario.php:76`
- [ ] `grep -rn "?? 1" app/Controllers/Inventario.php` → 0 resultados

**Salida de fase:** los 7 endpoints responden correctamente y ninguno es accesible sin el rol.

---

## F6 · Frontend y cableado

### 6a · Menú — los 4 registros

- [ ] `app/Config/MenuOptions.php:123-132` → **descomentar** `TituloAlmacen` y `almacen`
- [ ] `app/Config/MenuOptions.php` → restaurar `crud_productos`, `recepcion_material`, `entrega_productos`, `bajas_destruccion` (commit `dbb010d` las quitó)
- [ ] `app/Controllers/Home.php:81-89` → las claves coinciden exactamente con las de `MenuOptions`
- [ ] `app/Controllers/Modales.php` → los `case` correspondientes en el `switch` de `mostrar()`
- [ ] `public/js/mbscript.js` → los títulos en el objeto `titulos`, usado por `abrirModal(opcion)`
- [ ] `app/Config/Routes.php` → las rutas nuevas, **dentro** del grupo con `['filter' => ['auth','mantenimiento']]` (`Routes.php:42`)
- [ ] `php spark routes` → todas las rutas presentes

**Faltando cualquiera de los 4 registros, el item aparece vacío o devuelve `"Opción no válida"`.** No es un bug de una vista.

### 6b · Vistas

- [ ] `app/Views/modales/productos.php` — RF-1
- [ ] `app/Views/modales/carga_inicial.php` — RF-2
- [ ] `app/Views/modales/ingreso_manual.php` — RF-3
- [ ] `app/Views/modales/recepcion_oc.php` — RF-4
- [ ] `app/Views/modales/entregas.php` — RF-5
- [ ] `app/Views/modales/bajas.php` — RF-6
- [ ] `app/Views/modales/inventario.php` — RF-7, saldo y kardex

### 6c · JavaScript

- [ ] `public/js/almacen.js` → 7 inicializadores, uno por flujo
- [ ] `public/js/almacen.js` → `registrarBajaDestruccion` manda **JSON**
- [ ] `public/js/almacen.js` → sin referencias a `registro.ID_Historial` (la PK real era `ID_HistorialP`, y la vista se reemplaza por kardex)
- [ ] Clases Tailwind dinámicas en JS → agregadas a la `safelist` de `tailwind.config.js` si no compilan
- [ ] `npm run build:product`
- [ ] **`public/css/styless.css` recompilado y commiteado** — está versionado en git; sin esto el CSS no llega a producción

**Salida de fase:** el menú aparece, los 7 modales abren, y el CSS compilado está commiteado.

---

## F7 · Verificación

- [ ] **V1** `php spark migrate:status --all` → las 6 migraciones aplicadas
- [ ] **V2** `php spark inventario:verificar` → 0 desviaciones (INV-2)
- [ ] **V3** Entrega mayor al saldo → 422, no 500 (INV-1)
- [ ] **V4** `grep -rn "InventarioStock" app/` → solo el servicio y el model (INV-4)
- [ ] **V5** `grep -rn "?? 1" app/Controllers/Inventario.php` → 0 resultados (INV-5)
- [ ] **V6** `UPDATE` manual de `Cantidad_Recibida` por encima de `Cantidad` → error de constraint (INV-3)
- [ ] **V7** Entrar como `Almacen` y como `Contaduría` → ambos ven el item y abren los modales
- [ ] **V8** Como `Contaduría`, POST de almacén por URL → 403
- [ ] **V9** E2E de RF-1 a RF-7 con prefijo `[TEST-QA]`, luego verificar que el kardex cuadra
- [ ] `vendor\bin\phpunit --no-coverage` → 5/5 (los tests del framework; no cubren la app)

**Salida de fase:** las 9 verificaciones pasan.

**Sobre V7 y V8:** un menú que se ve pero devuelve 403 a medias es peor que un menú oculto, porque induce al usuario a un error sin explicación. Las dos van juntas.

---

## F8 · Cierre

- [ ] Revisar que ningún cálculo de presupuesto lee un campo de inventario (INV-7) — `PresupuestoMensual`, `PresupuestoAnual`, `Monto_Comprometido_Original`
- [ ] `grep -rn "Catalogo_Productos" app/Controllers/Inventario.php app/Controllers/Api.php` → solo el contexto de OC, nunca el catálogo de artículos
- [ ] Bump de versión en `package.json` (SemVer)
- [ ] `git add public/css/styless.css` — el CSS compilado va commiteado
- [ ] `vendor\bin\phpunit --no-coverage`
- [ ] `php spark routes` como sanity check final
- [ ] `git status` y `git diff` revisados antes de commitear

**Salida de fase:** la implementación está completa, verificada y commiteada.

---

## Notas para quien implemente

1. **El patrón falso del código actual es la trampa principal.** El código asume que lo que se solicita es lo que llega al almacén. No es cierto, y ninguna consulta a `Catalogo_Productos` dentro del controlador de inventario debe existir. Si aparece, es un error de diseño, no un bug.

2. **M6 no puede correr antes de F1.** La tabla vive, el código se lava primero. Intentar dropear `Ingresos` con `Modales.php` aún referenciándola deja el sistema roto en un estado intermedio.

3. **Los 0 resultados de `grep` son criterios de salida reales**, no sugerencias. Cada uno de los 5 verifica un invariante específico.

4. **No hay suite de pruebas.** `phpunit` corre 5 tests de ejemplo del framework y nada más. **Ningún test va a detectar una regresión.** La verificación de esta implementación es F7, manual y explícita.

5. **No corras `npm run format`.** `prettier --check .` falla en ~300 archivos del repositorio y el formateo reescribiría todo. Formatea solo lo que toques, si acaso.
