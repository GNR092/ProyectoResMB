# Spec: Módulo de Inventarios

| Campo | Valor |
|---|---|
| **Estado** | Especificación cerrada — pendiente de implementación |
| **Alcance** | Módulo de Almacén completo: alta de artículos, carga inicial, ingresos, recepción de órdenes de compra, entregas, bajas y consulta de existencias |
| **Rama** | `Inventario` |
| **Fecha de cierre** | 2026-09-30 |
| **Implementación** | Fuera del alcance de la sesión de planeación. El punto de entrada es `tasks.md`. |
| **Investigación** | `hallazgos.md` (inmutable) |
| **Diseño técnico** | `plan.md` |

---

## 1. Contexto y problema

El módulo de Almacén existe en código pero no en operación. Tres problemas lo hacen inutilizable.

### 1.1 Está desconectado de la interfaz

`TituloAlmacen` y `almacen` están comentados en `app/Config/MenuOptions.php:123-132` desde el 2026-04-27. Como `Home.php:285-290` filtra el menú iterando sobre las claves definidas en `MenuOptions`, los permisos que `Home.php:81-89` y `:134-140` otorgan a los roles `Almacen` y `Contaduría` son inertes: **ningún rol ve el menú**, ni siquiera Administración.

### 1.2 El puente compras → almacén tiene un error fatal

`Api::confirmarRecepcion` referencia `Status::RECEPCION_PARCIAL` (`:3991`), `Status::RECEPCION_TOTAL` (`:3993`) y `Status::RECIBIDA_TOTALMENTE` (`:4015`). **Ninguna de las tres existe** en `app/Libraries/Status.php`.

En PHP 8, referenciar una constante de clase inexistente lanza `Error`, y `Error` **no** extiende `\Exception`. Por lo tanto el `catch (\Exception $e)` de `Api.php:4032` no la intercepta y la petición muere con un error no controlado. Peor: el `UPDATE` de stock de `Api.php:3981` se ejecuta **antes** del fatal, de modo que una petición que alcanza la primera fila de producto sí escribe y luego revienta.

### 1.3 El modelo implícito del código es falso

El código asume que **lo que el usuario solicita es lo que llega al almacén**. La realidad operativa es distinta:

- El catálogo de compras contiene **productos y servicios**. Un servicio no se almacena.
- El material que llega al almacén **puede tener otro nombre** que el solicitado.
- **No todos** los productos de una requisición llegan al almacén.
- Puede haber **existencias preexistentes** que no provienen de ninguna requisición.
- **No toda requisición pasa por almacén**, porque no tiene por qué pasar.

Esta spec rompe ese supuesto. El inventario se modela como un sistema **autónomo**, y la relación con las compras es un **acto opcional** —la recepción— y no un dato estructural.

---

## 2. Glosario

| Término | Significado |
|---|---|
| **Artículo** | El bien físico. Se registra **una sola vez**, globalmente, sin importar cuántas sedes lo soliciten ni cuántas requisiciones lo mencionen. Pertenece al almacén. |
| **Catálogo** | Fila que hace solicitable un producto **o servicio** en un contexto de compras y presupuesto, con un alcance de Razón Social, segmento, sitio, unidad operativa y grupo presupuestal. Pertenece a Compras. **El inventario no lo lee ni lo escribe.** |
| **Sitio** | `Places.ID_Place`. La sede física donde se almacena el stock. |
| **Recepción** | Documento del almacén que registra qué artículos entraron. Puede, opcionalmente, mencionar la orden de compra de origen. |
| **Movimiento** | Una fila de `Kardex`. Entrada o salida, nunca ambas. |
| **Kardex** | Libro de movimientos. **Fuente única de verdad** del inventario. |
| **Carga inicial** | Alta de existencias preexistentes, sin documento de origen. |

---

## 3. Decisiones

Estas decisiones están **cerradas**. Cambiarlas exige revisar esta spec completa.

| # | Decisión |
|---|---|
| **D1** | **El inventario es autónomo.** No existe ninguna llave foránea ni columna que enlace el inventario con `Catalogo_Productos`. |
| **D2** | El vínculo con compras es un **acto opcional** (`Recepcion.ID_OrdenCompra` nulable), no un dato estructural. |
| **D3** | El stock vive en `InventarioStock (ID_Producto, ID_Place)`. Ni en el catálogo, ni en el artículo. |
| **D4** | **Almacén elige el sitio destino** en cada ingreso o recepción, con default sugerido desde `UnidadOperativa.ID_Place` de la requisición cuando la hay. |
| **D5** | El kardex registra **solo cantidades**. Sin valuación, sin costo promedio, sin precios. |
| **D6** | `Producto` se resucita como maestro del artículo físico. Se le **quita** `Existencia` y se le **agrega** `UnidadMedida`. |
| **D7** | `Recepcion` + `Recepcion_Linea` **sustituyen** a `Ingresos` + `DetalleIngreso`. El kardex es el ledger único: absorbe entregas, bajas, ingresos y recepciones. No hay tablas de cabecera/detalle paralelas. |
| **D8** | Mueren 6 tablas: `HistorialProductos`, `MapeoProductos`, `Entregas`, `DetalleEntrega`, `Ingresos`, `DetalleIngreso`. |
| **D9** | El inventario escribe `Solicitud_Producto.Estado_Recepcion`. Es **informativo** y nunca alimenta cálculos presupuestales (ver INV-7). |
| **D10** | **Cero acoplamiento con el módulo de presupuestos.** |

### 3.1 Consecuencia de D1

`Catalogo_Productos` **no se modifica**. No gana columnas, no pierde columnas. El módulo de inventarios no la consulta. Esto elimina de paso un riesgo real: `MigracionesController.php:351` inserta filas en `Catalogo_Productos` al migrar datos entre sitios, y una columna de stock ahí habría duplicado existencias en cada traspaso.

---

## 4. Actores y permisos

| Actor | Puede | No puede |
|---|---|---|
| **Almacén** | Crear y editar artículos. Operar los 7 flujos de §7. Consultar saldos y kardex. Clasificar el estado de recepción de las partidas de una orden de compra. | Vincular catálogo con artículos. Crear o editar filas de `Catalogo_Producto`. Escribir `InventarioStock.Existencia` directamente. Tocar presupuestos. |
| **Compras** | Ver el estado de recepción de sus partidas de requisición. | Escribir cualquier campo del inventario. Escribir `Solicitud_Producto.Estado_Recepcion` —solo Almacén lo hace. |
| **Solicitante** | Ver si su requisición pasó o no por almacén. | Cualquier operación de inventario. |

**Requisito de seguridad.** Todo endpoint sensible revalida el rol en el servidor y devuelve **403** si no corresponde. Ocultar el menú no es seguridad. El patrón de referencia es `Calendario::puedeEditarAgenda()`.

**Requisito de cierre de sesión.** `Api.php:1080` lee `session()->get('id')` para filtrar por usuario. La autenticación es por sesión: un mismo usuario que cierra sesión pierde acceso a la vista. No se requiere re-autenticación por acción.

---

## 5. Fronteras entre módulos

```
   COMPRAS  ──── lectura ────►  ALMACÉN  ──── escritura informativa ────►  COMPRAS
 (OrdenCompra,                      │
  Solicitud_Producto)               └── NO toca presupuestos (INV-7)
```

Dos flechas, una sola dirección en cada una:

- **Compras → Almacén, solo lectura.** El almacén consulta la orden de compra para saber qué se pidió. Nunca la modifica.
- **Almacén → Compras, solo escritura informativa.** El almacén anota el estado de recepción. No toca cantidades pedidas, importes, compromisos ni presupuestos.

El resultado es que **el inventario depende de las requisiciones, pero las requisiciones no dependen del inventario.** Un almacén puede operar completo, con y sin órdenes de compra, sin que exista ninguna de ellas.

---

## 6. Modelo de dominio

### 6.1 Inventario — 5 tablas, autónomas

| Tabla | Estado | Rol |
|---|---|---|
| `Producto` | resucitada | Maestro del artículo físico. `ID_Producto` PK, `Codigo` UNIQUE (SKU), `Nombre`, `UnidadMedida`. **Sin `Existencia`.** Sin FK hacia compras. |
| `InventarioStock` | nueva | Saldo por `(ID_Producto, ID_Place)`. `Existencia` con `CHECK >= 0`. |
| `Kardex` | nueva | Libro de movimientos, append-only. Fuente única de verdad. |
| `Recepcion` | nueva | Cabecera de ingreso o recepción. `ID_OrdenCompra` **nulable**. |
| `Recepcion_Linea` | nueva | Detalle. `ID_Producto` obligatorio, `ID_SolicitudProd` **nulable**. |

```
┌──────────────────────────────────────────────────────────────┐
│ Producto                                                      │
│   ID_Producto   PK                                           │
│   Codigo        UNIQUE   (SKU)                               │
│   Nombre                                                     │
│   UnidadMedida                                               │
│   [Existencia]  ELIMINADA — el stock vive en InventarioStock │
└───────────────────────────┬──────────────────────────────────┘
                            │ 1:N
┌───────────────────────────┴──────────────────────────────────┐
│ InventarioStock                                               │
│   (ID_Producto, ID_Place)   PK compuesta                     │
│   Existencia   NUMERIC  CHECK (Existencia >= 0)              │
└───────────────────────────┬──────────────────────────────────┘
                            │
┌───────────────────────────┴──────────────────────────────────┐
│ Kardex                    append-only · fuente de verdad      │
│   ID_Producto, ID_Place                                       │
│   Fecha, Tipo, Cantidad (positiva=entrada, negativa=salida)  │
│   Existencia_Ant, Existencia_Nueva                            │
│   ID_Usuario                                                  │
│   Documento_Tipo, Documento_ID, Documento_Folio               │
│   Documento_Lote          agrupa las líneas de una entrega    │
│   ID_Linea_Origen         puntero blando a Recepcion_Linea   │
│   Motivo                  obligatorio si Tipo = BAJA          │
│   created_at                                                 │
└──────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────┐
│ Recepcion                    cabecera                         │
│   ID_OrdenCompra   ○ NULL   vínculo opcional con compras      │
│   ID_Proveedor, ID_Place, ID_Usuario, Fecha                   │
│   UUID             ○ UNIQUE  solo si hay factura              │
│   RFC_Receptor, FechaEmision, Observaciones                   │
├──────────────────────────────────────────────────────────────┤
│ Recepcion_Linea              detalle                          │
│   ID_Recepcion       FK                                       │
│   ID_Producto        FK   el artículo FÍSICO que llegó       │
│   Cantidad                                                  │
│   ID_SolicitudProd   ○ NULL   "creo que venía de esta partida"│
│   Observaciones                                               │
└──────────────────────────────────────────────────────────────┘
```

`○` = vínculo opcional, nulable. El inventario funciona completo sin una sola fila que los use.

### 6.2 Compras — sin cambios

| Tabla | Estado |
|---|---|
| `Catalogo_Productos` | **Intacta.** Cero columnas agregadas, cero eliminadas. El inventario no la lee. |
| `Solicitud_Producto` | `+ Estado_Recepcion`, `+ Cantidad_Recibida` (D9). Ambos informativos. |
| `OrdenCompra` | **Intacta.** |
| `Cotizacion`, `Solicitud` | **Intactas.** |

### 6.3 Estados de recepción

Cinco valores. **Los fija Almacén**, siempre en la pantalla de recepción, al cerrarla.

| Estado | Significado |
|---|---|
| `PENDIENTE` | La OC está en curso y la partida aún no ha pasado por almacén. Valor inicial. |
| `PARCIAL` | Llegó parte de la cantidad pedida. |
| `TOTAL` | Llegó toda la cantidad pedida. `Cantidad_Recibida = Cantidad`. |
| `NO_ENTREGADO` | La OC se cerró y esta partida nunca llegó al almacén. |
| `NO_APLICA` | Esta partida nunca pasará por almacén: es un servicio, o el proveedor entrega directo al solicitante. |

---

## 7. Requisitos funcionales

### RF-1 · Alta de artículo

> **Dado** un usuario con rol `Almacen`, **cuando** envía `Codigo` (SKU), `Nombre` y `UnidadMedida`, **entonces** se crea el artículo y queda disponible para los flujos de almacén.
>
> **Dado** un `Codigo` que ya existe, **cuando** se intenta crear otro artículo con ese código, **entonces** la API devuelve **422** y no se crea nada.
>
> **Dado** que se edita un artículo, **entonces** solo pueden modificarse `Nombre` y `UnidadMedida`. El `Codigo` es inmutable una vez creado.

**Nota de diseño.** El nombre del artículo es el del almacén. Puede o no coincidir con el nombre del catálogo, y no hay ninguna obligación de que coincidan.

### RF-2 · Carga inicial de existencias

Cubre los productos preexistentes: material que ya está en el almacén y no proviene de ninguna requisición.

> **Dado** un usuario con rol `Almacen` y un sitio destino, **cuando** registra artículos con nombre, código y cantidad, **entonces** por cada artículo se crea el maestro si no existe, se crea su fila en `InventarioStock` si no existe, y se escribe una fila de `Kardex` con `Tipo = 'CARGA_INICIAL'` y `Cantidad` positiva.
>
> **Dado** que un `Codigo` de artículo ya existe pero no tiene fila en el sitio destino, **entonces** se crea la fila de stock de ese sitio y se registra el movimiento.
>
> **Dado** cualquier fallo a mitad del proceso, **entonces** la carga se revierte completa.

### RF-3 · Ingreso manual

> **Dado** un sitio destino, un proveedor y las líneas `{artículo, cantidad}`, **cuando** se confirma, **entonces** se crean `Recepcion` con `ID_OrdenCompra` en **null** y sus `Recepcion_Linea`, y por cada línea una fila de `Kardex` con `Tipo = 'INGRESO_MANUAL'`.
>
> **Dado** que se proporciona un `UUID` que ya fue registrado, **entonces** la API devuelve **422** y no se duplica el stock.
>
> **Dado** que no se proporciona `UUID`, **entonces** la operación se acepta: puede no haber factura asociada.
>
> **Dado** cualquier fallo a mitad del proceso, **entonces** no queda ninguna escritura parcial.

**Requisito derivado.** El sitio destino es **obligatorio**. El ingreso manual actual no lo tiene, y bajo D3 es inutilizable tal como está.

### RF-4 · Recepción de orden de compra

> **Dado** una orden de compra en estado `Por_Pagar` o `En_Proceso_Pago`, **cuando** Almacén confirma una recepción, **entonces** Almacén **elige** qué artículos físicos entraron y en qué cantidad. El sistema no deduce los artículos de la orden de compra.
>
> **Dado** una línea de `Recepcion_Linea`, **cuando** se vincula a una partida de la requisición, **entonces** esa partida acumula `Cantidad_Recibida` y su `Estado_Recepcion` se recalcula. **La vinculación es opcional**: una línea de recepción puede no tener `ID_SolicitudProd`.
>
> **Dado** que el material tiene un nombre distinto al del catálogo, **entonces** la recepción se registra igual. El nombre del artículo de almacén es el que manda.
>
> **Dado** que la OC tiene partidas que no llegan al almacén, **cuando** Almacén cierra la recepción, **entonces** las clasifica como `NO_ENTREGADO` o `NO_APLICA`, y ese es el estado que queda en `Solicitud_Producto.Estado_Recepcion`.
>
> **Dado** que la cantidad acumulada de una partida iguala `Cantidad`, **entonces** su estado queda `TOTAL`.
>
> **Dado** que la cantidad acumulada supera `Cantidad`, **entonces** la API devuelve **422** y no se escribe nada.
>
> **Dado** que se reintenta la misma orden de compra con las mismas cantidades, **entonces** la API devuelve **422** por exceder el tope. La repetición no es idempotente-por-éxito sino **por-rechazo**, que es la propiedad buscada.
>
> **Dado** cualquier fallo a mitad del proceso, **entonces** no queda ninguna escritura parcial: la operación es atómica.

**Requisito derivado.** El sitio destino lo elige Almacén en un selector obligatorio, con default sugerido desde `UnidadOperativa.ID_Place` de la requisición, y puede cambiarlo.

**Requisito derivado.** **El catálogo no se consulta ni se modifica en ningún punto de este flujo.** La orden de compra se usa únicamente como contexto y para los archivos de remisión y factura de entrada.

### RF-5 · Entrega

> **Dado** un artículo con saldo disponible en un sitio, **cuando** se entrega una cantidad a un receptor identificado, **entonces** `InventarioStock` descuenta y se escribe una fila de `Kardex` con `Tipo = 'ENTREGA'` y `Cantidad` negativa.
>
> **Dado** que la cantidad supera el saldo disponible, **entonces** la API devuelve **422** y no se toca nada. El clamp-a-cero actual (`Modales.php:1064`) queda prohibido.
>
> **Dado** una entrega con varias líneas, **entonces** todas las filas de `Kardex` generadas comparten el mismo `Documento_Lote`, de modo que la entrega se recupera completa.

**Requisito derivado.** El receptor es un campo obligatorio. `Modales::descontarStockEntrega` no valida nada hoy.

### RF-6 · Baja por destrucción

> **Dado** un artículo con saldo disponible, **cuando** se registra una baja con cantidad y motivo, **entonces** se descuenta el stock y se escribe una fila de `Kardex` con `Tipo = 'BAJA'`.
>
> **Dado** que la cantidad supera el saldo disponible, **entonces** la API devuelve **422**. Este control ya existe en `Api.php:4066` y se conserva.
>
> **Dado** que el motivo está vacío, **entonces** la API devuelve **422**. Es obligatorio.

### RF-7 · Consulta de saldos y kardex

> **Dado** un usuario con rol `Almacen`, **cuando** consulta saldos, **entonces** ve `Existencia` por cada par `(artículo, sitio)`.
>
> **Dado** que filtra el kardex por artículo, sitio, tipo de movimiento, rango de fechas o usuario, **entonces** ve los movimientos ordenados por fecha descendente, con `Existencia_Ant` y `Existencia_Nueva` en cada fila.
>
> **Dado** que el kardex no tiene movimientos para un par `(artículo, sitio)`, **entonces** se muestra con existencia cero.

---

## 8. Invariantes

| # | Invariante | Cómo se garantiza |
|---|---|---|
| **INV-1** | `InventarioStock.Existencia >= 0` en todo momento | `CHECK` real en la base de datos **y** validación en el servicio. En el esquema anterior (`Producto`) esto era solo un comentario de migración (`CreateTableProducto.php:31`), nunca un constraint. |
| **INV-2** | `SUM(Kardex.Cantidad)` agrupado por `(ID_Producto, ID_Place)` **iguala** `InventarioStock.Existencia` | El comando `inventario:verificar` recalcula y compara. |
| **INV-3** | `Solicitud_Producto.Cantidad_Recibida <= Cantidad` en todo momento | `CHECK` en la base de datos. |
| **INV-4** | Nadie escribe `InventarioStock.Existencia` fuera del servicio de inventario | `Existencia` queda fuera de los `$allowedFields` del modelo. |
| **INV-5** | Toda fila de `Kardex` tiene `ID_Usuario` no nulo, y corresponde al usuario que hizo el movimiento | Sin valores por defecto. Se elimina el fallback `session('id') ?? 1` de `Inventario.php:76`. |
| **INV-6** | Toda actualización de stock toma un bloqueo de fila | `SELECT ... FOR UPDATE` dentro de la transacción, antes de leer el saldo. |
| **INV-7** | **Ningún campo del inventario es entrada de ningún cálculo de presupuesto.** `Estado_Recepcion`, `Cantidad_Recibida`, `Kardex` e `InventarioStock` son informativos. `PresupuestoMensual`, `PresupuestoAnual` y `Monto_Comprometido_Original` no los leen. | Frontera declarada en §5. Revisar en cada cambio que toque estos campos. |

---

## 9. No-objetivos

Queda explícitamente **fuera** de esta spec:

- Valuación, costo promedio y precios unitarios (D5).
- Ingesta de XML / CFDI. `XmlCfdiReader` permanece sin uso.
- Stock mínimo, punto de pedido y alertas de reorden.
- Múltiples almacenes dentro de una misma sede. Un sitio = un almacén en esta fase.
- Códigos de barras y códigos QR.
- Reportes de valuación e integración contable.
- **Cualquier interacción con el módulo de presupuestos** (D10, INV-7). En particular: liberar presupuesto de material no entregado, o recalcular compromisos a partir de cantidades recibidas. Estas partidas no pasan por almacén porque no tienen por qué pasar, y su resolución corresponde a otros módulos.

---

## 10. Preguntas abiertas

Ninguna.

Esta sección se mantiene vacía a propósito. Si una sesión futura necesita abrirla, es señal de que una decisión de §3 cambió o de que apareció un requisito no contemplado. En cualquiera de los dos casos hay que revisar esta spec completa antes de modificar `plan.md` o `tasks.md`.

---

## Ver también

- `hallazgos.md` — la investigación que sustenta cada decisión de §3, con evidencia `archivo:línea`.
- `plan.md` — el diseño técnico: migraciones, servicio, archivos a modificar.
- `tasks.md` — el checklist de implementación. **Punto de entrada de la sesión de implementación.**
