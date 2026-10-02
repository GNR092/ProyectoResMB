# Hallazgos — Reporte "Solicitudes Mandadas a Cotizar"

> Estado **previo** al cambio. Este archivo es INMUTABLE: si el módulo evoluciona, se **agrega** una
> sección de re-verificación al final; nunca se edita lo ya descubierto.
> Fecha de captura: 2026-10-01. Driver: **PostgreSQL** (`database.default.DBDriver = Postgre`, `.env`).

---

## 1. El evento de bitácora que define "mandada a cotizar"

`app/Controllers/Api.php:1181-1253` — `Api::aprobarYCotizar()`

| Línea | Evidencia |
|---|---|
| `Api.php:1183-1185` | Guarda de rol: `session('login_type') !== 'boss'` → 403. |
| `Api.php:1200-1202` | Guarda de pertenencia: la solicitud debe ser del departamento del jefe. |
| **`Api.php:1203-1205`** | **Guarda de estado:** `if ($solicitud['Estado'] !== Status::Aprobacion_pendiente) return $this->fail(..., 400);` → una solicitud ya procesada **no** puede volver a cotizarse. |
| `Api.php:1211` | `$nuevoEstado = Status::En_espera;` (`'En espera'`, `app/Libraries/Status.php:14`). |
| `Api.php:1214-1216` | Si `Tipo == SolicitudTipo::Servicios` (`2`), `$nuevoEstado = 'Cotizando'` (`Status.php:16`). |
| `Api.php:1219` | `$solicitudModel->update($idSolicitud, ['Estado' => $nuevoEstado]);` |
| `Api.php:1221` | `$this->notificarWhatsApp($idSolicitud);` |
| **`Api.php:1236-1242`** | **Evento de auditoría:** `Events::trigger('auditoria', ['tipo_accion' => 'APROBAR_Y_COTIZAR', 'modulo' => 'Cotizacion', 'solicitud_id' => $idSolicitud, 'estado' => 'exito', ...])`. |

**Conclusión**: la tupla `(tipo_accion = 'APROBAR_Y_COTIZAR', estado = 'exito', solicitud_id)` es la
**fuente de verdad** de "esta solicitud fue mandada a cotizar por el jefe".

El evento se persiste por `app/Config/Events.php:30-32` →
`BitacoraService::getInstance()->registrar($data)`, y el volcado real ocurre en
`app/Config/Events.php:37-41` (`post_system` → `persistir()`, sólo fuera de CLI).

### 1.1 Estructura de la tabla `bitacora`

- Tabla en **minúsculas**: `bitacora`. `app/Models/BitacoraModel.php:9` → `protected $table = 'bitacora';`,
  clave primaria `id` (`BitacoraModel.php:10`).
- `fecha_hora`: `app/Database/Migrations/2026-04-09-155308_CreateBitacoraTable.php:19-22`
  (`DATETIME`, default `CURRENT_TIMESTAMP`).
- `solicitud_id` es **nullable**: `CreateBitacoraTable.php:68-72`. Por eso toda consulta sobre el evento
  debe excluir `solicitud_id IS NULL`.
- Índices existentes (misma migración, `L102-107`): `id` (PK), `fecha_hora`, `usuario_id`,
  `solicitud_id`, `orden_compia`, `clasificacion`. **No hay índice sobre `tipo_accion`.**
- El literal `APROBAR_Y_COTIZAR` ya está mapeado para la UI de bitácora en
  `public/js/bitacora.js:44` → `'Aprobar y Cotizar'`. Confirma que el `tipo_accion` es el esperado.

### 1.2 Formato real de `fecha_hora` en PostgreSQL (verificado en vivo)

```
2026-09-28 13:33:13.253512      <-- timestamp con microsegundos
```

`slice(0, 19)` es obligatorio en JS: recorta los microsegundos. Y el string **no** es conforme ES2020
(`'YYYY-MM-DD HH:MM:SS'` usa espacio como separador), por lo que `new Date(str)` produce
`Invalid Date` en Safari/WebKit. Ver §5.

---

## 2. LA LAGUNA DE COBERTURA — nacen en estado de cotización SIN evento

`app/Controllers/Archivo.php:213-225` — creación de solicitud:

```php
213  $estadoInicial = Status::Aprobacion_pendiente;
...
216  if ($enviarDireccion) {
217      $estadoInicial = Status::En_Revision;
...
219  } else if (session('login_type') === 'boss') {
220      if ($tipo == SolicitudTipo::Servicios) {
221          $estadoInicial = Status::Cotizando;      // 'Cotizando'
222      } else {
223          $estadoInicial = Status::En_espera;      // 'En espera'
224      }
225  }
```

Cuando un **jefe de departamento** captura su propia requisición (sin enviar a Dirección), la
solicitud **nace directamente** en `En espera` / `Cotizando` y **nunca pasa por
`Api::aprobarYCotizar()`**, por lo tanto **nunca genera un evento `APROBAR_Y_COTIZAR`**.

Consecuencia: un reporte construido **sólo** sobre el evento de bitácora perdería esas solicitudes.
Por eso el universo del reporte es la **UNIÓN de dos conjuntos** (ver `plan.md` §4).

---

## 3. Patrón a replicar — "Solicitudes Sin Cotizar" (sección hermana)

No se crea una pantalla nueva: la nueva vista vive **dentro del modal existente**
`app/Views/modales/control/ReportePresupuesto.php`, gobernada por la clave `pantalla` de Alpine.

### 3.1 Controlador — `app/Controllers/ReportesController.php`

| Rango | Contenido |
|---|---|
| `:2111-2115` | Docblock de `getSolicitudesSinCotizar()`. |
| `:2116-2140` | Consulta de `Solicitud` con LEFT JOINs. |
| `:2141-2144` | Early return `['datos' => [], 'totales' => ['cantidad' => 0, 'costo_total' => 0]]`. |
| `:2146-2157` | Fórmula de costo: productos + servicios. |
| `:2159-2176` | Lectura de bitácora (`ACTUALIZAR`) para `FechaAprobacionJefe`. |
| `:2178-2203` | Armado de `$datos` (incluye el `Tipo` en `:2199-2200` y el IVA en `:2182-2184`). |
| `:2205-2212` | `respond()` con `datos` + `totales`. |
| `:2214-2300` | `exportarSolicitudesSinCotizarJson()` — **genera XLSX** pese al nombre `Json` (`:2290` `new Xlsx(...)`). |
| `:2302-2415` | `exportarSolicitudesSinCotizarPdf()`. |

**JOINs exactos (`ReportesController.php:2123-2139`)**, a replicar *menos* el join a `Cotizacion`
y *menos* el `whereIn('Estado')`:

```
2124  select('Solicitud.*, Departamentos.Nombre as DepartamentoNombre,
               Places.Nombre_Corto as ComplejoNombre,
               Razon_Social.Nombre as RazonSocialNombre,
               Usuarios.Nombre as UsuarioNombre')
2125  join('Departamentos', 'Departamentos.ID_Dpto = Solicitud.ID_Dpto', 'left')
2126  join('Places', 'Places.ID_Place = Departamentos.ID_Place', 'left')     <-- ¡OJO!
2127  join('Razon_Social', 'Razon_Social.ID_RazonSocial = Solicitud.ID_RazonSocial', 'left')
2128  join('Usuarios', 'Usuarios.ID_Usuario = Solicitud.ID_Usuario', 'left')
2129  join('Cotizacion', ...)        <-- se OMITE en el reporte nuevo
2138  orderBy('Solicitud.ID_Solicitud', 'DESC')
```

> **Trampa de `Places` (`:2126`)**: el complejo sale de `Departamentos.ID_Place`, **NO** de
> `Solicitud.ID_UnidadOperativa`. Es fácil equivocarse porque otras pantallas sí usan la unidad
> operativa.

**Fórmula de costo (`:2147-2185`)** — se replica LITERALMENTE:

```php
2147  $costos = array_fill_keys($ids, 0.0);
2149  $rows = $productoModel->select('ID_Solicitud, Cantidad, Importe')->whereIn('ID_Solicitud', $ids)->findAll();
2151      $costos[$r['ID_Solicitud']] += (float) $r['Cantidad'] * (float) $r['Importe'];
2154  $rows = $servicioModel->select('ID_Solicitud, Importe')->whereIn('ID_Solicitud', $ids)->findAll();
2156      $costos[$r['ID_Solicitud']] += (float) $r['Importe'];
2182  $ivaVal    = $sol['IVA'] ?? false;
2183  $ivaOn     = ($ivaVal === 't' || $ivaVal === '1' || $ivaVal === 1 || $ivaVal === true);
2184  $costo     = round($montoBase * ($ivaOn ? 1.16 : 1.0), 2);
```

> PostgreSQL devuelve los booleanos como `'t'` / `'f'`; en la BD de desarrollo `Solicitud.IVA`
> devuelve `1`. La condición del hermano cubre los tres casos (`'t'`, `'1'`, `1`, `true`) y se
> replica sin tocar.

**Helpers privados reutilizables** (no reimplementar):
`_iso()` `:1580-1583`, `_fmtMoney()` `:1591-1594`, `getColumnLetter()` `:1394`,
`_dibujarCabeceraOscura()` `:1931-1940`, `_dibujarTotalGeneral()` `:1945-1959`.

### 3.2 Rutas — `app/Config/Routes.php:269-272`

```php
269  // Rutas API Solicitudes Sin Cotizar
270  $routes->get('api/solicitudes/sin-cotizar', 'ReportesController::getSolicitudesSinCotizar');
271  $routes->post('api/solicitudes/sin-cotizar/exportar-datos', 'ReportesController::exportarSolicitudesSinCotizarJson');
272  $routes->post('api/solicitudes/sin-cotizar/exportar-pdf',  'ReportesController::exportarSolicitudesSinCotizarPdf');
```

Están dentro del grupo con filtros `['filter' => ['auth', 'mantenimiento']]` (arranque del bloque).

### 3.3 Vista — `app/Views/modales/control/ReportePresupuesto.php`

| Rango | Contenido |
|---|---|
| `:96-103` | Botón del menú `irAPantalla('sincotizar')`, icono `#en_espera`, color `sky`. |
| `:1770-1771` | `<template x-if="pantalla === 'sincotizar'">`. |
| `:1773-1788` | Encabezado: volver, botones PDF/EXCEL, `<h2>`. |
| `:1790-1867` | Panel de filtros (`grid-cols-1 md:grid-cols-4`), botón Limpiar. |
| `:1869-1923` | Tabla + `tfoot` Total General. |
| `:1925-1943` | Tarjetas de resumen. |
| `:1945-1961` | Paginación. |

### 3.4 Frontend — `public/js/reporte_presupuesto.js`

| Rango | Contenido |
|---|---|
| `:4-5` | `registrarComponenteReportePresupuesto()` → `Alpine.data('reportePresupuestoComponent', ...)`. |
| `:65-82` | Bloque de estado `// Solicitudes sin cotizar`. |
| `:247-345` | `irAPantalla(nueva)`: resetea estado + carga automática por clave. |
| `:263-273` | Reset del bloque `SinCoti`. `:332-334` carga automática cuando `nueva === 'sincotizar'`. |
| `:713-735` | `cargarSolicitudesSinCotizar()` — `fetch` a `${BASE_URL}api/solicitudes/sin-cotizar`. |
| `:737-768` | `initChoicesSinCotizar()` — Choices.js sobre `x-ref`. |
| `:770-780` | Getters `opcionesRazones/Complejos/Deptos`. |
| `:782-816` | Getter `solicitudesSinCotizarFiltradas` (**contiene el bug de `new Date()`**, ver §5). |
| `:818-830` | `totalPagesSinCoti`, `paginatedSinCoti`, `totalCostoSinCoti`. |
| `:832-852` | `cambiarPaginaSinCoti()` y `limpiarFiltrosSinCoti()`. |
| `:854-950` | `exportarSolicitudesSinCotizarExcel()` / `...Pdf()`. |

---

## 4. La trampa de `registrarComponenteReportePresupuesto()` duplicada

`public/js/presupuestos.js:742` define una **copia obsoleta** de
`registrarComponenteReportePresupuesto()` (con `pantalla` limitado a `'menu','presupuesto','cuentas','completo'`).
La versión **real y completa** es `public/js/reporte_presupuesto.js:4`.

Si ambas se cargan, la segunda define sobrescribe el componente Alpine y desaparece toda la
funcionalidad de `sincotizar`. **Todo el JS nuevo va exclusivamente en `public/js/reporte_presupuesto.js`
y `public/js/presupuestos.js` no se toca.**

---

## 5. Defecto conocido del hermano: `new Date()` sobre el string de PostgreSQL

`public/js/reporte_presupuesto.js:793-794`:

```js
793  const fechaIni = this.filtroFechaDesdeSinCoti ? new Date(this.filtroFechaDesdeSinCoti + 'T00:00:00') : null;
794  const fechaFin = this.filtroFechaHastaSinCoti ? new Date(this.filtroFechaHastaSinCoti + 'T23:59:59') : null;
```

y `:806-810` reconstruye la fecha de la fila con `new Date(s.FechaSolicitud.replace(' ', 'T'))`.

El string de la BD es `'YYYY-MM-DD HH:MM:SS[.ffffff]'`. Sustituir el espacio por `T` funciona en
Chrome/Firefox pero **no es válido según ES2020 para `Date` cuando hay microsegundos**, y en
Safari/WebKit el resultado es `Invalid Date`. Un `Invalid Date` en una comparación **no** es `>=` ni
`<=` contra un número `NaN`... ambas son `false`, así que la fila **se cuela sin filtrar, en
silencio**: el filtro de periodo no acota nada y el usuario ve filas fuera del rango sin saberlo.

Mitigación adoptada en el módulo nuevo: **comparación lexicográfica de strings** (§5 de `plan.md`).

---

## 6. Modelos y columnas confirmadas

| Modelo | Archivo | Tabla | Columnas usadas |
|---|---|---|---|
| `BitacoraModel` | `app/Models/BitacoraModel.php:9-11` | `bitacora` | `solicitud_id`, `fecha_hora`, `tipo_accion`, `estado` |
| `SolicitudModel` | — | `Solicitud` | `ID_Solicitud`, `No_Folio`, `RazonSocial`, `Fecha`, `Estado`, `Tipo`, `IVA`, `ID_Dpto`, `ID_RazonSocial`, `ID_Usuario` |
| `SolicitudProductModel` | `app/Models/SolicitudProductModel.php:14-15` | `Solicitud_Producto` | `ID_Solicitud`, `Cantidad`, `Importe` |
| `SolicitudServiciosModel` | `app/Models/SolicitudServiciosModel.php:14` | `Solicitud_Servicios` | `ID_Solicitud`, `Importe` |

Enums / constantes:
- `app/Libraries/Status.php:7` `Aprobacion_pendiente = 'Aprobacion Pendiente'`;
  `:14` `En_espera = 'En espera'`; `:16` `Cotizando = 'Cotizando'`;
  `:11` `Dept_Rechazada`; `:20` `Rechazada`.
- `app/Libraries/SolicitudTipo.php:5-7` `Cotizacion = 0`, `NoCotizacion = 1`, `Servicios = 2`.

Icono: `public/icons/icons.svg:113` → `<symbol id="cotizacion" viewBox="0 0 24 24">`. Existe.

---

## 7. No existe librería de gráficas en el repo

`package.json` no declara `chart.js`, `apexcharts`, `highcharts` ni `d3`; `node_modules` no
contiene ningún paquete con esos nombres. **El reporte es 100 % tabular + tarjetas de resumen.**
No se puede añadir una gráfica sin incorporar una dependencia nueva (fuera del alcance del
encargo).

---

## 8. Referencia de lectura de bitácora con rango (patrón de filtro de fechas)

`app/Libraries/Rest.php:2970-3029` (`getBitacora`) — construye la consulta con Query Builder sobre
`bitacora b` + LEFT JOINs, y aplica el rango de fechas de forma **inclusiva en ambos extremos**:

```php
3016  if (!empty($filters['fecha_inicio'])) {
3017      $builder->where('b.fecha_hora >=', $filters['fecha_inicio'] . ' 00:00:00');
3018  }
3019  if (!empty($filters['fecha_fin'])) {
3020      $builder->where('b.fecha_hora <=', $filters['fecha_fin'] . ' 23:59:59');
3021  }
```

Es la convención del proyecto para "rango de fechas" sobre `fecha_hora`. En el módulo nuevo el
rango se aplica en el **cliente** (no hay query string en el contrato de datos), replicando el
mismo criterio de extremos abiertos.

---

## 9. FASE 0 — Verificación de volumen de datos (EJECUTADA 2026-10-01)

```sql
SELECT count(*) AS eventos, count(DISTINCT solicitud_id) AS solicitudes,
       min(fecha_hora) AS desde, max(fecha_hora) AS hasta
FROM bitacora WHERE tipo_accion='APROBAR_Y_COTIZAR' AND estado='exito';
```

```
eventos    = 0
solicitudes = 0
desde      = (null)
hasta      = (null)
Tiempo: 81 ms
```

Sin el filtro `estado` (control): **también 0 eventos**.

Contexto adicional de la BD de desarrollo:

| Consulta | Resultado |
|---|---|
| `SELECT count(*) FROM bitacora` | **91** filas |
| `tipo_accion` más frecuentes | `LOGIN_EXITOSO` 43, `ACTUALIZAR` 19, `INSERTAR` 15, `ACCESO_DENEGADO` 8, `GENERAR_PDF` 2, `+4` unitarios |
| `SELECT count(*) FROM bitacora WHERE modulo='Cotizacion'` | 4 |
| `SELECT count(*) FROM "Solicitud"` | **1** (`ID_Solicitud=1`, `No_Folio='MBSP-1'`, `Estado='En revision'`, `Tipo=1`, `Fecha=2026-09-21`, `IVA=1`) |
| `SELECT "Estado", count(*) FROM "Solicitud" WHERE "Estado" IN ('En espera','Cotizando')` | **0 filas** (conjunto vacío) |

### 9.1 CONCLUSIÓN DE LA FASE 0 — BLOQUEO

**Los DOS conjuntos del universo están vacíos en la base de datos de desarrollo.**

- Conjunto 1 (evento `APROBAR_Y_COTIZAR`): **0 eventos**.
- Conjunto 2 (`Estado IN ('En espera','Cotizando')`): **0 solicitudes** (la única solicitud
  existente está en `'En revision'`).

Por lo tanto `GET api/solicitudes/manda-cotizar` responderá siempre el *early return* y **el reporte
no se puede demostrar con datos reales**. Conforme al gate definido en `tasks.md` Fase 0, la
implementación se **detiene antes de la UI** (Fases 4 y 5) y se reporta al usuario.

La tubería del evento **sí está correctamente cableada** (`Api.php:1236` dispara `auditoria`;
`Events.php:30-32` registra; `Events.php:37-41` persiste), y el literal está mapeado en
`bitacora.js:44`: simplemente **nadie ha ejecutado `aprobarYCotizar` contra esta base de datos**.

**No se crea ningún índice.** La consulta de `bitacora` tarda 81 ms (muy por debajo del umbral de
2 s definido); añadir un índice sobre `bitacora.tipo_accion` está prohibido por la Regla de Oro y no
es necesario.

---

## 10. Re-verificación

### 10.1 — 2026-10-01, Fase 1/2 (verificación del SQL generado contra PostgreSQL real)

Ejecutado con un script temporal **fuera** del repositorio (sólo lectura), reutilizando el patrón
de bootstrap de `scripts/backfill_fecha_programacion.php`.

**SQL real generado por el conjunto 1** (confirma que `MIN()` y el `IS NOT NULL` **no** van
entrecomillados — `selectMin()` habría producido `"fecha_hora"` y un error de sintaxis):

```sql
SELECT "solicitud_id", MIN(fecha_hora) AS fecha_manda_cotizar
FROM "bitacora"
WHERE "tipo_accion" = 'APROBAR_Y_COTIZAR'
  AND "estado" = 'exito'
  AND solicitud_id IS NOT NULL
GROUP BY "solicitud_id"
```

**Conjunto 2**: `SELECT "ID_Solicitud" FROM "Solicitud" WHERE "Estado" IN ('En espera','Cotizando')`
→ ejecuta correctamente, **0 filas**.

**Consulta principal** (los 4 LEFT JOIN) → compila y ejecuta; devuelve filas con los 4 alias
resueltos. El JOIN de `Places` por `Departamentos.ID_Place` queda confirmado (R-6).

**Hallazgo nuevo 10.1.a — tipos reales de las columnas de fecha (vía `information_schema.columns`):**

| Columna | Tipo | `is_nullable` |
|---|---|---|
| `bitacora.fecha_hora` | `timestamp without time zone` | `NO` |
| `bitacora.solicitud_id` | `integer` | `YES` (justifica el `IS NOT NULL`) |
| `Solicitud.Fecha` | **`date`** | `NO` |
| `Solicitud.IVA` | `boolean` | `NO` |
| `Solicitud.No_Folio` | `character varying` | `YES` |

`Solicitud.Fecha` es un `date` (10 caracteres), **no** un datetime. Esto obliga a normalizar a
medianoche para que el filtro de periodo por strings sea inclusivo (ver `plan.md` §3.1.1).

**Hallazgo nuevo 10.1.b — `IVA` llega como `'t'` a través del driver CI4**, no como `1` (mi primer
sondeo con PDO directo devolvió `1`). La condición copiada del hermano
(`'t' || '1' || 1 || true`) cubre ambos casos; verificada en datos reales.

**Hallazgo nuevo 10.1.c — `respond()` no es invocable fuera del ciclo HTTP.**
Invocar `ReportesController::getSolicitudesMandaCotizar()` desde CLI lanza
`Call to a member function setContentType() on null`. Se comprobó que
`getSolicitudesSinCotizar()` —**sin modificar**— falla **de forma idéntica**: es una limitación
ambiental de la prueba en CLI, **no** un defecto del código nuevo.

**Hallazgo nuevo 10.1.d — `Modelo::getCompiledSelect()` no existe** (CI4 lanza `ModelException`);
para inspeccionar SQL hay que usar `$modelo->builder()->...->getCompiledSelect()`.

### 10.2 — Pendiente

Se agregará aquí si el módulo evoluciona o si la base de datos de desarrollo se puega con solicitudes
reales que permitan desbloquear las Fases 4, 5 y 6.

---

# Re-verificación posterior a la Fase 5 (cierre de DEF-1 … DEF-4)

> Sección **agregada**, sin modificar nada de lo anterior. Las secciones 1-10 registran el estado
> previo al cambio y se conservan intactas.

## R.1 — Auditoría clase-por-clase del CSS compilado

**Por qué se agrega:** el criterio de verificación de la tarea 5.7 era demasiado débil (sólo
comprobaba que no apareciera `public/css/styless.css` en `git diff`), y por eso **no detectó**
que 2 clases del bloque nuevo no existían en el CSS que realmente se sirve.

**Método:** extracción de las clases del botón de menú (`:104-109`) y del template
`x-if="pantalla === 'mandacotizar'"` (`:1974-2178`), incluyendo **los literales de cadena dentro
de los atributos `:class`**, porque son clases que se aplican en runtime y por tanto también tienen
que existir en el CSS compilado. Cada clase se escapó como lo hace Tailwind (`:`→`\:`, `/`→`\/`) y
se buscó como selector `.` + clase dentro de `public/css/styless.css`.

**Resultado inicial (121 clases únicas): 3 ausentes.**

| Clase | Causa | Resolución |
|---|---|---|
| `focus:ring-orange-400` | DEF-2. El CSS sólo compila `focus:ring-sky-400`, `-amber-400`, `-emerald-400`, `-blue-400`… | Sustituida por `focus:ring-amber-400` (`:2000`, `:2007`, `:2014`) |
| `hover:bg-orange-50/40` | DEF-2. El CSS tiene `.hover:bg-orange-50` y `.hover:bg-orange-50/70`, no `/40` | Sustituida por `hover:bg-orange-50/70` (`:2107`) |
| `animate-fadeIn` | **Preexistente y compartida** — ver R.1.1 | **No corregida** (queda abierta) |

**Resultado tras las correcciones (120 clases únicas): 119 presentes, 1 ausente** (`animate-fadeIn`).
`public/css/styless.css` **no** aparece en `git diff`; no se recompiló Tailwind ni se ejecutó npm.

### R.1.1 — `animate-fadeIn` es una carencia preexistente del repositorio, no de este módulo

No es una regresión de "Solicitudes Mandadas a Cotizar":

- La usan **24 pantallas** de `ReportePresupuesto.php`, incluida la hermana "Solicitudes Sin
  Cotizar" (`:1780`).
- No aparece en `public/css/styless.css` (**0 coincidencias de `fade`** en todo el archivo), ni en
  `input.css`, ni en `tailwind.config.js`.

Es decir: hoy la clase es un **no-op en todo el archivo**, incluido el hermano. Corregirla exigiría
`npm run build:product` + commitear el CSS compilado, y el keyframe tendría que existir antes en
`input.css`; ambas cosas quedan fuera del alcance de esta corrección. Se deja constancia para que
se resuelva como tarea independiente y global, no dentro de este reporte.

## R.2 — `fecha_hora` (26 chars) vs `Solicitud.Fecha` (10 chars): sigue vigente y es obligatoria

Los tipos reales ya estaban documentados en §10.1 y en `plan.md` §3.1.1. En esta re-verificación se
comprobó además el **comportamiento de la normalización**, sin tocar la base de datos:

| Origen | Valor crudo | Normalización | Resultado |
|---|---|---|---|
| `bitacora.fecha_hora` (`timestamp without time zone`, `NOT NULL`) | `'2026-09-28 13:33:13.253512'` — **26 chars** | `substr($x, 0, 19)` | `'2026-09-28 13:33:13'` — 19 chars |
| `Solicitud.Fecha` (`date`, `NOT NULL`) | `'2026-09-21'` — **10 chars** | `substr($x, 0, 10) . ' 00:00:00'` | `'2026-09-21 00:00:00'` — 19 chars |

Ambas ramas producen **exactamente 19 caracteres** (CA-1) y el filtro de periodo queda **inclusivo**
en los dos extremos. Se comprobó explícitamente que la normalización del conjunto 2 **no es
cosmética**: sin ella, `'2026-09-21' < '2026-09-21 00:00:00'` es lexicográficamente verdadero y la
fila se excluiría al filtrar con `desde = 2026-09-21`, rompiendo CA-3 de forma silenciosa.

**Salvedad honesta:** en este entorno **no** fue posible repetir la consulta a
`information_schema.columns`. El grupo `$default` de `app/Config/Database.php` está configurado como
`MySQLi` con `database` vacío, el grupo `Postgre` está comentado y el `.env` no contiene claves de
BD; tampoco hay Docker disponible. La verificación anterior contra el esquema real (§10.1) sigue
siendo la única evidencia directa del tipo de columna; lo re-verificado aquí es la normalización y
el comportamiento del filtro, que es donde un error tendría consecuencias.

## R.3 — `window.APP_NOMBRE_EMPRESA` nunca se define en el proyecto

Búsqueda exhaustiva en todo el repositorio (excluyendo `node_modules`, `vendor`, `.git`): la
constante aparece **4 veces y las 4 son lecturas**, en `public/js/reporte_presupuesto.js`
(`:1183`, `:1225`, `:1433`, `:1477`). **No hay ninguna asignación ni declaración.**

Consecuencia: `window.APP_NOMBRE_EMPRESA || ''` resuelve **siempre a `''`**, y el `?? 'Grupo MBM'`
del controlador **no** se dispara, porque `??` sólo reacciona a `null`/`undefined`, no a cadena
vacía. El encabezado de la exportación habría salido **en blanco**.

**Corrección aplicada a este módulo:** se encapsuló en `_nombreEmpresaMandaCoti()`, que devuelve
`window.APP_NOMBRE_EMPRESA || 'Grupo MBM'`, con el mismo valor por defecto que usa el backend.

**Deuda heredada, no corregida:** las dos lecturas del reporte *Requisiciones Pagadas* (`:1433`,
`:1477`) mantienen el `|| ''` y arrastran el mismo defecto. Se deja constancia; corregirlas exigiría
tocar código de otro reporte, fuera del alcance de esta corrección.
---

## Re-verificacion 2026-10-02 - Cobertura del periodo de envio a cotizacion

> Seccion anexada. **No modifica** lo registrado arriba: los hallazgos de las secciones 1-10 siguen
> vigentes tal como fueron observados antes del cambio. Aqui solo se registra lo que la
> verificacion con datos reales demostro que estaba mal.

### R.3 - El conjunto 2 del plan original perdia toda requisicion ya avanzada de estado

La Fase 6 dio por bueno el reporte validandolo contra una BD que **no tenia ni un evento
`APROBAR_Y_COTIZAR` ni una fila en `En espera`/`Cotizando`**. El *early return* de ceros hacia
que el codigo pareciera correcto. Con datos reales la brecha es total:

| Medicion (BD real) | Valor |
|---|---|
| Eventos `APROBAR_Y_COTIZAR` con `estado='exito'` | **0** |
| Solicitudes en `En espera`/`Cotizando` | **0** (las 2 existentes estan en `En revision`) |
| Solicitudes detectadas por el reporte original | **0 de 2** |

**Ciclo real completo de `MBSP-1` segun `bitacora` (`tipo_accion='ACTUALIZAR'`):**

| `fecha_hora` | `valores_antiguos` -> `valores_nuevos` | Significado |
|---|---|---|
| `2026-09-21 11:52:23` | `{"No_Folio":null}` -> `{"No_Folio":"MBSP-1"}` | creacion |
| `2026-09-22 11:22:01` | `{"Estado":"En espera"}` -> `{"Estado":"Cotizando"}` | **entrada a cotizacion** |
| `2026-09-22 11:22:21` | `{"Estado":"Cotizando"}` -> `{"Estado":"En revision"}` | salida de cotizacion |

Ni un solo evento `APROBAR_Y_COTIZAR`: el flujo real de esta requisicion **nunca paso por
`aprobarYCotizar()`**, asi que el conjunto 1 no podia detectarla y el conjunto 2 (que solo miraba
`Estado` actual) tampoco. Resultado: la requisicion no aparecia en **ningun** periodo.

**Correccion:** subconjunto 2b, `Origen='Historico'`, detectado por la transicion de **salida**
(`valores_antiguos.Estado` en estados de cotizacion). Verificado: 2 solicitudes, 4 ms.

### R.4 - CI4 4.7 no tiene `whereRaw()`, y `whereIn(..., $escape=false)` no escapa los valores

Dos trampas que hicieron fallar la primera implementacion. Ambas se detectaron **por ejecucion**
contra la BD; ninguna se habria visto leyendo el codigo.

**R.4.a - `Model::whereRaw()` no existe.** `BaseBuilder` (CI4 4.7.3) no define ningun metodo
`*Raw*`; los unicos Raw del namespace son `Database\RawSql`, `RawSqlInterface` y `Query`, que son
otra cosa. `Model::__call` reenvia solo una lista blanca de metodos y `whereRaw` no esta en ella:

```
BadMethodCallException: Call to undefined method App\Models\BitacoraModel::whereRaw
  at SYSTEMPATH\Model.php:763
```

La unica via para SQL crudo dentro de una consulta es pasar la expresion como *key* con
`$escape = false`, que es el patron ya usado en `Rest.php:2993-2998`.

**R.4.b - `$escape = false` desactiva el escapado de los VALORES, no solo del identificador.**
`BaseBuilder::_whereIn()` delega en `setBind()`, y con `escape === false` los valores se registran
con tipo `expression` y se insertan **verbatim**. El SQL salia asi:

```sql
AND "valores_antiguos"->>'Estado' IN (En espera,Cotizando)   -- sin comillas
```

Es decir, la mitigacion de "no deformar el identificador" introduce un error de sintaxis. **Correccion:**
escapar la lista a mano con `$dbConexion->escape([...])`. Ojo: `escape()` **devuelve array**
(`["'En espera'","'Cotizando'"]`), no string, y `whereIn()` exige array o closure
(`InvalidArgumentException: whereIn() expects $values to be of type array or closure`), asi que
un `implode()` intermediate rompe la llamada. SQL final verificado:

```sql
AND "valores_antiguos"->>'Estado' IN ('En espera','Cotizando')
```

### R.5 - `getCompiledSelect()` resetea el builder: inspeccionar y ejecutar son builders distintos

`getCompiledSelect($reset = true)` es el default y **borra las condiciones** ya montadas. Un script
que inspecciona el SQL y luego ejecuta *el mismo* objeto builder creyo estar validando el filtro
cuando en realidad ejecutaba `SELECT * FROM bitacora` a secas: devolvio **106 filas sin filtrar** en
lugar de 2. La validacion correcta usa dos instancias:

```php
$sql = $modelo->builder()->...->getCompiledSelect();          // solo inspección
$filas = (new Modelo())->...->findAll();                       // ejecucion, builder nuevo
```

### R.6 - `Solicitud.Fecha` NO es la fecha de envio a cotizacion (suposicion del plan, refutada)

El plan asumia que toda fila sin evento **nace ya** en estado de cotizacion
(`Archivo.php:219-224`) y por ello `Solicitud.Fecha` era la fecha exacta de envio. Los datos
refutan esa suposicion para el caso mayoritario:

| Solicitud | `Solicitud.Fecha` | Entrada real a cotizacion | Diferencia |
|---|---|---|---|
| `MBSP-1` | `2026-09-21` | `2026-09-22 11:22:01` | **1 dia** |
| `MBSP-2` | `2026-10-02` | `2026-10-02 09:33:00` | mismo dia |

`MBSP-1` se creo en `En espera` y paso a `Cotizando` al dia siguiente. Usar `Solicitud.Fecha` la
archivaba en el periodo equivocado: un filtro de "solo 22-sep" la **excluia** pese a haberse
mandado a cotizar ese dia. Violaba el requisito del usuario ("enviadas a cotizar dentro del
periodo"), no solo la precision.

**Correccion (2b-bis):** fecha de la transicion de **entrada**, `MIN(fecha_hora)` donde
`valores_nuevos.Estado` pasa a `En espera`/`Cotizando`, con `where('modulo','Solicitud')` para no
mezclar el ruido de `Solicitud_Producto`, `Cotizacion` y `eventos_calendario`. Fallback a
`Solicitud.Fecha` solo cuando no existe transicion de entrada (requisiciones creadas por jefe).
Verificado: 2 solicitudes, `modulo='Solicitud'` recorte 24 filas `ACTUALIZAR` a las 2 correctas.

Ademas la entrada aporta la **hora**, que `Solicitud.Fecha` no puede dar por ser un `date`.

### R.7 - Un periodo mal formado en el test puede simular un fallo del filtro

Primera medicion del filtro de periodo dio "excluye" para *todos* los periodos, incluso para uno que
abarcaba todo el anio. Causa: el script comparaba contra `$desde . ' 23:59:59'` con el mismo
`$desde` del rango, es decir contra **un solo dia**, no contra el periodo. El filtro del frontend es
correcto. Se deja constancia porque el sintoma (0 filas en todo) es indistinguible de un bug real.
