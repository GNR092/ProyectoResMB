# Plan de implementación — Reporte "Solicitudes Mandadas a Cotizar"

> Documento de diseño. Contrato cerrado; ver `spect.md` para los criterios de aceptación.
> Todo el código, comentarios y nombres están en **español**.

---

## 1. Objetivo

Dentro del modal existente **"Reporte Presupuesto"** (`app/Views/modales/control/ReportePresupuesto.php`)
agregar una octava pantalla: **"Solicitudes Mandadas a Cotizar"**, que lista las requisiciones que
ya fueron enviadas a la etapa de cotización (compras / proveedor), con filtros, paginación y
exportación a XLSX y PDF.

**No es una opción de menú nueva.** El encargo del usuario es explícito: la pantalla vive dentro del
modal existente. Por eso **no** se tocan `app/Config/MenuOptions.php`, `app/Controllers/Modales.php`
ni los registros de menú de `app/Controllers/Home.php`, ni el objeto `titulos` de
`public/js/mbscript.js`.

---

## 2. Nombres, claves y rutas (definitivos)

| Elemento | Valor |
|---|---|
| Clave de pantalla Alpine | `'mandacotizar'` |
| Sufijo de estado Alpine | `MandaCoti` (**nunca** `SinCoti`) |
| Prefijo de rutas | `api/solicitudes/manda-cotizar` |
| Método de listado | `ReportesController::getSolicitudesMandaCotizar()` |
| Método exportador XLSX | `ReportesController::exportarSolicitudesMandaCotizarXlsx()` |
| Método exportador PDF | `ReportesController::exportarSolicitudesMandaCotizarPdf()` |
| Icono del botón | `<use xlink:href="<?= $iconUrl ?>#cotizacion">` (`public/icons/icons.svg:113`) |

**Colisión a evitar:** el hermano usa el sufijo `SinCoti`; ours usa `MandaCoti` en **cada**
propiedad, getter, método, `x-ref` y `:key`. Ningún identificador se comparte.

> Nota de nomenclatura heredada: el hermano se llama `exportarSolicitudesSinCotizarJson()` pero
> genera **XLSX** (`ReportesController.php:2290` → `new Xlsx(...)`). El nuestro se llama
> deliberadamente `...Xlsx()` para no perpetuar el error.

---

## 3. Contrato de datos

### 3.1 `GET api/solicitudes/manda-cotizar` — **sin query string**

Respuesta 200. `respond()` **no** envuelve nada (`$format = 'json'`, `ReportesController.php:32`):

```json
{
  "datos": [{
    "ID_Solicitud": 123,
    "No_Folio": "REQ-00123",
    "RazonSocial": "N/A",
    "Complejo": "N/A",
    "Departamento": "N/A",
    "Usuario": "N/A",
    "FechaSolicitud": "YYYY-MM-DD HH:MM:SS",
    "FechaMandaCotizar": "YYYY-MM-DD HH:MM:SS",
    "Origen": "Evento",
    "Estado": "En espera",
    "Tipo": "Producto",
    "CostoTotal": 10000.00
  }],
  "totales": {
    "cantidad": 10,
    "costo_total": 150000.00,
    "con_evento": 7,
    "sin_evento": 3
  }
}
```

**Invariantes del shape:**
- `FechaMandaCotizar` **nunca es `null`** y su formato es **siempre de 19 caracteres**
  (`'YYYY-MM-DD HH:MM:SS'`) (CA-1). Origen de verdad: `bitacora.fecha_hora` (`MIN`) tanto para el
  conjunto 1 como para la transición de entrada del conjunto 2; `Solicitud.Fecha` es solo el
  fallback para requisiciones sin transición de entrada.
- `Origen` ∈ { `"Evento"`, `"Estado actual"`, `"Historico"` } — discrimina el conjunto de origen (CA-4).
- Early return con ceros si el universo queda vacío:
  `['datos' => [], 'totales' => ['cantidad'=>0,'costo_total'=>0,'con_evento'=>0,'sin_evento'=>0]]`.
- Claves del objeto `datos[]`: **exactamente** las 12 listadas arriba, en ese orden.

#### 3.1.1 Normalización obligatoria de `FechaMandaCotizar` a 19 caracteres

Verificado contra el esquema real (`information_schema.columns`):

| Columna | Tipo real | Valor devuelto | Normalización |
|---|---|---|---|
| `bitacora.fecha_hora` | `timestamp without time zone`, `NOT NULL` | `'2026-09-28 13:33:13.253512'` (**26 chars**, microsegundos) | `substr($x, 0, 19)` |
| `Solicitud.Fecha` | **`date`**, `NOT NULL` | `'2026-09-21'` (**10 chars**) | `substr($x, 0, 10) . ' 00:00:00'` |

La normalización del conjunto 2 **no es cosmética**: sin ella, el filtro de periodo por strings
(§8) excluiría las filas cuya fecha coincida exactamente con `desde`, porque `'2026-09-21' <
'2026-09-21 00:00:00'` es lexicográficamente verdadero. CA-3 se rompería de forma silenciosa.

`FechaSolicitud` **no** se normaliza: se devuelve tal cual, replicando literalmente al hermano
(`$sol['Fecha'] ?? null`, `ReportesController.php:2195`).

### 3.2 Exportadores

`POST api/solicitudes/manda-cotizar/exportar-datos` → **XLSX** (PhpSpreadsheet).
`POST api/solicitudes/manda-cotizar/exportar-pdf` → **PDF** (`App\Libraries\PDF`).

Ambos reciben:

```json
{
  "datos": [ /* arreglo datos[] EXACTAMENTE como lo devuelve el getter filtrado */ ],
  "filtros": {
    "desde": "", "hasta": "",
    "estados": "", "razonesSociales": "", "complejos": "", "departamentos": "", "tipos": "",
    "origenes": "", "folios": ""
  },
  "nombreEmpresa": "..."
}
```

El exportador **no recalcula nada**: itera `datos` tal cual llega y totaliza `CostoTotal`. Esto
garantiza CA-8 (paridad exacto entre lo que se ve en pantalla y lo que se descarga).

> **Asimetría conocida entre exportadores (paridad con el hermano).** El frontend envía las mismas
> **9 claves** de `filtros` a los dos endpoints, pero **el XLSX las ignora por completo**: no imprime
> ninguna línea de filtros, sólo las cabeceras y los datos. El **PDF sí las usa** y las imprime como
> una línea `Filtros: …` (`ReportesController.php:2526`). Se conserva así a propósito, porque es
> exactamente lo que hace el hermano; pero implica que **el Excel descargado no deja constancia de
> qué filtros lo produjeron**. Si algún día se requiere trazabilidad en el Excel, hay que propagar el
> cambio también al exportador del hermano para no romper la simetría.

---

## 4. El universo: UNIÓN de dos conjuntos

**Decisión del usuario (opción B).** No es negociable y está justificada por `hallazgos.md` §2.

### Conjunto 1 — con evento de bitácora (`Origen = 'Evento'`)

Consulta **literal** (CI4 Query Builder, **sin** `selectMin()` porque escaparía `"fecha_hora"` y
produciría un alias inválido en PostgreSQL):

```php
$eventos = $bitacoraModel
    ->select('solicitud_id, MIN(fecha_hora) AS fecha_manda_cotizar')
    ->where('tipo_accion', 'APROBAR_Y_COTIZAR')
    ->where('estado', 'exito')
    ->where('solicitud_id IS NOT NULL', null, false)   // sintaxis CI4: sin comillas en el valor
    ->groupBy('solicitud_id')
    ->findAll();
```

Se convierte en el mapa `$mapaEvento = [ solicitud_id => 'YYYY-MM-DD HH:MM:SS' ]`.

> **Por qué se conserva `MIN(fecha_hora)` + `GROUP BY`** aunque el duplicado sea *estructuralmente*
> imposible (`Api::aprobarYCotizar` rechaza con 400 si `Estado !== Aprobacion_pendiente`,
> `Api.php:1203-1205`, y nada devuelve una solicitud a `Aprobacion Pendiente`): es **defensa
> idempotente**. Si mañana alguien agrega un reintento o un endpoint nuevo que sí re-emita el evento,
> el reporte no duplicará filas niMultiplierá `con_evento`.

### Conjunto 2 — sin evento, pero que pasaron (o siguen) por estado de cotización

Se armacon **dos subconjuntos**, porque el conjunto 2 original solo miraba el estado *actual* y
perdía toda requisición que ya hubiera avanzado:

```php
// 2a. Estado actual: sigue en 'En espera' / 'Cotizando'  -> Origen = 'Estado actual'
$idsSinEvento = $solicitudModel->select('ID_Solicitud')
    ->whereIn('Estado', [Status::En_espera, 'Cotizando'])
    ->findAll();

// 2b. Histórico: tiene una transición de SALIDA registrada  -> Origen = 'Historico'
$estadoPrevioExpr = $dbDriver === 'Postgre'
    ? '"valores_antiguos"->>\'Estado\''
    : 'JSON_UNQUOTE(JSON_EXTRACT(`valores_antiguos`, \'$.Estado\'))';
$estadosCotizacion = $dbConexion->escape([Status::En_espera, 'Cotizando']);

$transiciones = $bitacoraModel->select('solicitud_id')
    ->where('tipo_accion', 'ACTUALIZAR')
    ->where('solicitud_id IS NOT NULL', null, false)
    ->whereIn($estadoPrevioExpr, $estadosCotizacion, false)   // escape=false = expresión cruda
    ->groupBy('solicitud_id')
    ->findAll();
```

> ### ⚠ `whereIn(..., $escape = false)`: dos trampas de CI4 4.7 (verificado en ejecución)
> 1. **`Model::whereRaw()` no existe.** `Model::__call` sólo reenvía una lista blanca de métodos y
>    `BaseBuilder` tampoco define `whereRaw`. No hay forma de pasar SQL crudo con binds.
> 2. **`$escape = false` desactiva el escapado de los *valores*, no sólo del identificador.**
>    `BaseBuilder::_whereIn()` delega en `setBind()`, que con `escape === false` registra los
>    valores como tipo `expression` y los inserta **verbatim**. Sin escapar antes, el SQL sale
>    como `IN (En espera,Cotizando)` — sin comillas — y la consulta falla o devuelve basura.
>    Por eso los estados se escapan a mano con `$dbConexion->escape([...])`, que devuelve el
>    **array** `["'En espera'","'Cotizando'"]` (`implode` no: `whereIn` exige array o closure).

### 2b-bis — Fecha real de envío a cotización (transición de ENTRADA)

```php
$entradas = $bitacoraModel->select('solicitud_id, MIN(fecha_hora) AS fecha_entrada')
    ->where('tipo_accion', 'ACTUALIZAR')
    ->where('modulo', 'Solicitud')                       // sin esto entra ruido de otros módulos
    ->where('solicitud_id IS NOT NULL', null, false)
    ->whereIn($estadoNuevoExpr, $estadosCotizacion, false)
    ->groupBy('solicitud_id')
    ->findAll();
```

`$estadoNuevoExpr` usa `valores_nuevos` (la entrada) donde `$estadoPrevioExpr` usa
`valores_antiguos` (la salida). Se consulta para **todo** el conjunto 2, no sólo 2b: una solicitud
que sigue en `Cotizando` (2a) también tiene entrada y así queda con hora exacta.

**Por qué `Solicitud.Fecha` es insuficiente como fuente principal.** Evidencia real (solicitud
MBSP-1, `Estado` actual `En revision`):

| `bitacora.fecha_hora` | `valores_antiguos` → `valores_nuevos` |
|---|---|
| `2026-09-21 11:52:23` | `{"No_Folio":null}` → `{"No_Folio":"MBSP-1"}` (creación) |
| `2026-09-22 11:22:01` | `{"Estado":"En espera"}` → `{"Estado":"Cotizando"}` (**entrada**) |
| `2026-09-22 11:22:21` | `{"Estado":"Cotizando"}` → `{"Estado":"En revision"}` (salida) |

`solicitud.Fecha = 2026-09-21`, pero se mandó a cotizar el `2026-09-22`. Usarla archivaba la fila
en el periodo equivocado (un filtro de "sólo 22-sep" la excluía). La transición de entrada corrige
el periodo y además aporta la hora.

> ### ⚠ LIMITACIÓN RESIDUAL DOCUMENTADA (CA-4 / INV-6)
> El fallback a `Solicitud.Fecha` sigue siendo necesario para las requisiciones creadas por un
> jefe de departamento, que nacen **ya** en `En espera`/`Cotizando` (`Archivo.php:219-224`) y por
> tanto nunca generan transición de entrada. En ese caso la fecha es exacta en el día pero con
> precisión de día, no de hora. Sigue sin requerir columna nueva → compatible con la Regla de Oro.

### Fusión

```php
$ids = array_values(array_unique(array_merge(array_keys($mapaEvento), $idsSinEvento)));
```

`array_unique` + `whereNotIn` = **doble garantía** de una-fila-por-solicitud (INV-3).

---

## 5. Consulta de `Solicitud` (JOINs exactos)

Idéntica a `ReportesController.php:2123-2139` **menos** el join a `Cotizacion` (`:2129`), **menos**
el `where('Cotizacion.ID_Cotizacion IS NULL')` (`:2130`) y **menos** el `whereIn('Estado')`
(`:2131-2137`). Se **añade** `->whereIn('Solicitud.ID_Solicitud', $ids)`:

```php
->select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre,
                Places.Nombre_Corto as ComplejoNombre,
                Razon_Social.Nombre as RazonSocialNombre,
                Usuarios.Nombre as UsuarioNombre')
->join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
->join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')   // ¡NO ID_UnidadOperativa!
->join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
->join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
->whereIn('Solicitud.ID_Solicitud', $ids)
->orderBy('Solicitud.ID_Solicitud', 'DESC')
->findAll();
```

---

## 6. Fórmula de costo — réplica LITERAL del hermano (INV-5)

Se copia `ReportesController.php:2146-2185` sin una sola alteración:

1. `$costos = array_fill_keys($ids, 0.0);`
2. Productos (`Solicitud_Producto`): `+= (float) Cantidad * (float) Importe`.
3. Servicios (`Solicitud_Servicios`): `+= (float) Importe`.
4. IVA: `$ivaOn = ($ivaVal === 't' || $ivaVal === '1' || $ivaVal === 1 || $ivaVal === true);`
   `$costo = round($montoBase * ($ivaOn ? 1.16 : 1.0), 2);`
   (PostgreSQL devuelve `'t'`/`'f'`; la BD de desarrollo devuelve `1`. El hermano cubre ambos.)
5. `Tipo`: `'Producto'` si `Tipo ∈ [SolicitudTipo::NoCotizacion, SolicitudTipo::Cotizacion]`,
   si no `'Servicio'` (`ReportesController.php:2199-2200`).
6. `totales.costo_total = round($totalGeneral, 2)`.

---

## 7. Ordenamiento determinista (INV-4)

Dos niveles:

1. **Servidor**: `ORDER BY "Solicitud"."ID_Solicitud" DESC` — desempate estable y determinista,
   idéntico al hermano.
2. **Cliente**: el getter `solicitudesMandaCotiFiltradas` reordena por
   `FechaMandaCotizar` **descendente** con `localeCompare`, que para el formato fijo
   `'YYYY-MM-DD HH:MM:SS'` es exactamente el orden cronológico. En empates, `Array.prototype.sort`
   es estable (ES2019) y conserva el orden del servidor → resultado determinista.

---

## 8. Filtro de periodo por comparación de strings (INV-4 / CA-3)

**Prohibido** `new Date()` sobre el string de PostgreSQL. Ver `hallazgos.md` §5: `'YYYY-MM-DD HH:MM:SS'`
no es fecha conforme ES2020, Safari devuelve `Invalid Date`, las comparaciones contra `NaN` dan
`false` y **las filas se cuelan sin filtrar en silencio**.

Como el formato es de longitud fija y totalmente ordenable campo a campo, el **orden lexicográfico
coincide con el cronológico**. Implementación:

```js
const f = (s.FechaMandaCotizar || '').slice(0, 19);
if (desde && f < desde + ' 00:00:00') return false;
if (hasta && f > hasta + ' 23:59:59') return false;
```

- `slice(0, 19)` recorta los microsegundos (`2026-09-28 13:33:13.253512` → `2026-09-28 13:33:13`).
- Extremos **abiertos/inclusivos**: incluye todo el día de `desde` y todo el día de `hasta`
  (mismo criterio que `Rest.php:3016-3021`).
- Sin `new Date()` en ningún punto del módulo (CA-13 / INV-8).

### Los demás filtros

`Estado` **NO** es filtro de origen (decisión del usuario: *"incluye todo"*).
`filtrosEstadoMandaCoti` es un **refinamiento opcional**, `[]` por defecto (CA-6), con opciones
**dinámicas** derivadas de los datos cargados (igual que `opcionesRazones*`). El refinamiento
`Origen` (Evento / Estado actual) también es opcional.

---

## 9. Estado Alpine (`public/js/reporte_presupuesto.js`)

Todo el JS va **exclusivamente** en `public/js/reporte_presupuesto.js` (§"no tocar
`public/js/presupuestos.js`" y la trampa de la función duplicada, `hallazgos.md` §4).

### `data()`

```
solicitudesMandaCoti: []
totalesMandaCoti: { cantidad: 0, costo_total: 0, con_evento: 0, sin_evento: 0 }
cargandoMandaCoti: false
filtroTextoFolioMandaCoti: ''
filtroFechaDesdeMandaCoti: ''
filtroFechaHastaMandaCoti: ''
filtrosEstadoMandaCoti: []          <-- VACÍO por default (a diferencia del hermano)
filtrosRazonMandaCoti: []
filtrosComplejoMandaCoti: []
filtrosDeptoMandaCoti: []
filtrosTipoMandaCoti: []
filtrosOrigenMandaCoti: []          <-- refinamiento opcional
currentPageMandaCoti: 1
rowsPerPageMandaCoti: 15
choicesEstadoMandaCoti / choicesRazonMandaCoti / choicesComplejoMandaCoti /
choicesDeptoMandaCoti / choicesTipoMandaCoti / choicesOrigenMandaCoti: null
```

### Getters

`opcionesRazonesMandaCoti`, `opcionesComplejosMandaCoti`, `opcionesDeptosMandaCoti`,
`opcionesEstadosMandaCoti`, `opcionesTiposMandaCoti`, `opcionesOrigenesMandaCoti`,
`solicitudesMandaCotiFiltradas`, `totalPagesMandaCoti`, `paginatedMandaCoti`,
`totalCostoMandaCoti`.

### Métodos

`cargarSolicitudesMandaCoti()`, `initChoicesMandaCoti()`, `cambiarPaginaMandaCoti(page)`,
`limpiarFiltrosMandaCoti()`, `exportarSolicitudesMandaCotiExcel()`,
`exportarSolicitudesMandaCotiPdf()`.

Dos auxiliares privados, usados sólo por los exportadores:

- `_payloadFiltrosMandaCoti()` — arma el objeto `filtros` con las **9 claves** que ambos endpoints
  reciben. Centralizarlo garantiza que Excel y PDF Envíen exactamente el mismo conjunto.
- `_nombreEmpresaMandaCoti()` — resuelve `window.APP_NOMBRE_EMPRESA` con fallback a `'Grupo MBM'`
  (el mismo valor por defecto que usa el backend). Existe porque `window.APP_NOMBRE_EMPRESA`
  **no está definido en ninguna vista** del proyecto: sin el fallback, se enviaría `''` y el
  `?? 'Grupo MBM'` del controlador **no** se dispararía (sólo reacciona a `null`), dejando el
  encabezado de la exportación en blanco. Ver `hallazgos.md` §Re-verificación.

Además, `irAPantalla()` (L272) se toca **sólo** para: (a) resetear el bloque `MandaCoti` y
(b) añadir `if (nueva === 'mandacotizar') { this.cargarSolicitudesMandaCoti(); }` en el bloque de
carga automática. El bloque `SinCoti` (`:288-298`, `:375-377`) **no se toca** (INV-7).

---

## 10. Vista (`app/Views/modales/control/ReportePresupuesto.php`)

1. **Botón de menú** junto al de "Solicitudes Sin Cotizar" (`:96-103`), color distinto para
   diferenciarlo, `@click="irAPantalla('mandacotizar')"`, icono `#cotizacion`.
2. **`<template x-if="pantalla === 'mandacotizar'">`** colocado **después** del bloque del hermano
   (tras ~L1963), replicando su estructura: encabezado + PDF/EXCEL, panel de filtros, botón
   Limpiar, tabla, `tfoot`, tarjetas de resumen, paginación.
3. **11 columnas** de tabla: Folio, Razón Social, Complejo, Departamento, Usuario, Fecha Solic.,
   F. Manda Cotizar, Origen, Estado, Tipo, Costo Total.
   `:key="'mandacoti-' + s.ID_Solicitud"`.
4. **Tarjetas de resumen propias** (NO se copian las del hermano, que cuentan "Aprobación
   Pendiente" / "En espera" y aquí no aplican):
   Total Solicitudes / Con Evento / Por Estado Actual / Costo Total.
   Se calculan **sobre el conjunto filtrado** (`solicitudesMandaCotiFiltradas`), igual que el
   `tfoot` "Total General", para que las tarjetas describan lo que el usuario está viendo y no
   el total global del servidor. `totalesMandaCoti` sigue siendo la respuesta del API para
   consumidores externos y es lo que garantiza el invariante de `spect.md` §CA.
5. **Clases Tailwind: sólo las que ya existen en el archivo.** Si hiciera falta una nueva,
   `npm run build:product` + commit de `public/css/styless.css` (versionado en git) y constancia
   en el reporte final.

---

## 11. Archivos a crear / modificar

### Crear
- `spects/reportes_manda_cotizar/hallazgos.md`
- `spects/reportes_manda_cotizar/plan.md`
- `spects/reportes_manda_cotizar/spect.md`
- `spects/reportes_manda_cotizar/tasks.md`

### Modificar (y NADA más)
- `app/Controllers/ReportesController.php` → 3 métodos nuevos pegados **después** de
  `getSolicitudesSinCotizar()` (antes de `exportarSolicitudesSinCotizarJson()`, L2213).
- `app/Config/Routes.php` → 3 rutas nuevas pegadas en `:273`, después del bloque `sin-cotizar`.
- `public/js/reporte_presupuesto.js` → estado + reset/carga en `irAPantalla` + bloque de métodos
  `MandaCoti` después de `:950`.
- `app/Views/modales/control/ReportePresupuesto.php` → botón + `<template x-if>`.

### Explícitamente NO tocar
`app/Database/Migrations/**` (Regla de Oro), `public/js/presupuestos.js`,
`app/Config/MenuOptions.php`, `app/Controllers/Modales.php`,
`app/Controllers/Home.php`, `public/js/mbscript.js`, `package.json`, `writable/installer.lock`,
`public/css/styless.css` (sólo si una clase nueva lo exige).

---

## 12. Orden de ejecución

`tasks.md` define 7 fases secuenciales y bloqueantes. La **Fase 0 es un gate**: si el volumen de
eventos es 0, **no se continúa a la UI** y se reporta al usuario.