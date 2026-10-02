# Spec — Reporte "Solicitudes Mandadas a Cotizar"

> Contrato y *Definition of Done*. Los criterios son **binarios**: cada uno se comprueba o no.
> Módulos relacionados: `hallazgos.md` (estado previo), `plan.md` (diseño), `tasks.md` (orden).

---

## 1. Criterios de Aceptación (CA)

Cada criterio indica su **método de comprobación**.

### CA-1 — Origen único y no nulo de `FechaMandaCotizar`, con formato canónico de 19 caracteres
Ninguna fila puede traer `FechaMandaCotizar` en `null`, y su formato es **siempre**
`'YYYY-MM-DD HH:MM:SS'` (exactamente 19 caracteres).
*Comprobación:* `GET api/solicitudes/manda-cotizar` con datos → para cada elemento de `datos`,
`/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(s.FechaMandaCotizar) === true`.
*Normalización obligatoria (descubierta en la implementación, ver `hallazgos.md` §1.2 y §9):*
- Conjunto 1: `bitacora.fecha_hora` es `timestamp without time zone` y trae **microsegundos**
  (`2026-09-28 13:33:13.253512`, 26 chars) → `substr($x, 0, 19)`.
- Conjunto 2: la fuente primaria es la transición de **entrada** a cotización
  (`bitacora.fecha_hora` con microsegundos → `substr($x, 0, 19)`), igual que el conjunto 1.
  Solo si no existe esa transición (requisición creada por un jefe de departamento, que nace ya
  en estado de cotización — `Archivo.php:219-224`) se recurre a `Solicitud.Fecha`, que es un
  **`date`** (`2026-09-21`, 10 chars) → `substr(...) . ' 00:00:00'`.
  Sin esta normalización, una fila del conjunto 2 con fecha igual al `desde` del filtro sería
  **excluida** (porque `'2026-09-21' < '2026-09-21 00:00:00'` lexicográficamente) y CA-3 se rompería.
Ambas columnas son `NOT NULL` en el esquema, por lo que el valor nunca puede quedar vacío.

### CA-2 — Una fila por solicitud, sin duplicados
*Comprobación:* `new Set(datos.map(d => d.ID_Solicitud)).size === datos.length`. Mecánicamente
garantizado por `MIN`+`GROUP BY` en el conjunto 1, `whereNotIn` + `array_unique` en la fusión, y
`SELECT` (no `JOIN`) sobre `bitacora` agrupada.

### CA-3 — El rango de fechas es inclusivo en ambos extremos
Con `desde = D` y `hasta = D` deben aparecer todas las filas cuyo `FechaMandaCotizar` caiga en el
día `D` (desde `00:00:00` inclusive hasta `23:59:59` inclusive).
*Comprobación:* en la UI, filtrar por un mismo día en `Desde` y `Hasta`; el total de filas no
debe quedar en 0 si hay eventos ese día. En código:
`f >= desde + ' 00:00:00'` y `f <= hasta + ' 23:59:59'`.

### CA-4 — `Origen` discrimina correctamente el conjunto de origen
*Comprobación:* `totales.con_evento + totales.sin_evento === datos.length`, y para toda fila
`datos[i].Origen ∈ {'Evento','Estado actual','Historico'}`. Las filas del conjunto 1 llevan
`'Evento'`; las del conjunto 2a (siguen en `En espera`/`Cotizando`) llevan `'Estado actual'`; las
del conjunto 2b (ya avanzaron de estado) llevan `'Historico'`. Para estas últimas,
`FechaMandaCotizar` es la transición de entrada y por tanto **no** es aproximada
(limitación residual documentada en `plan.md` §4: el fallback a `Solicitud.Fecha` sí tiene
precisión de día).

### CA-5 — `totales.costo_total` es la suma de ambos conjuntos
*Comprobación:* `round(suma(datos[].CostoTotal), 2) === totales.costo_total`, con `datos` ya
mezclando conjuntos 1 y 2.

### CA-6 — `filtrosEstadoMandaCoti` está vacío por defecto
Al entrar a la pantalla, **todas** las solicitudes se muestran (incluidas las que están en
`Aprobacion Pendiente`, `Cancelada`, etc.).
*Comprobación:* cargar la pantalla y comparar `solicitudesMandaCotiFiltradas.length` con
`solicitudesMandaCoti.length` → iguales. Código: `filtrosEstadoMandaCoti: []` en `data()` y en el
reset de `irAPantalla()` y en `limpiarFiltrosMandaCoti()`.
*Contraste explícito:* el hermano inicializa en `['Aprobacion Pendiente','En espera']`
(`reporte_presupuesto.js:71`); **no** se copia ese comportamiento.

### CA-7 — Paridad de fórmula de costo con el hermano
Para una misma solicitud, `CostoTotal` debe coincidir con el que devuelve
`getSolicitudesSinCotizar()` para esa misma fila.
*Comprobación:* comparar `CostoTotal` de una solicitud compartida entre ambos reportes. La fórmula
se replica literalmente (`plan.md` §6), incluyendo el IVA `'t' / '1' / 1 / true`.

### CA-8 — Las exportaciones contienen exactamente las filas filtradas
*Comprobación:* aplicar un filtro (p. ej. sólo `Origen = Evento`), pulsar EXCEL y PDF, y confirmar
que el número de filas de datos del archivo es igual a
`solicitudesMandaCotiFiltradas.length` y que el `TOTAL GENERAL` coincide con
`totalCostoMandaCoti`. El exportador **no** recalcula ni re-filtra.

### CA-9 — No-regresión de "Solicitudes Sin Cotizar"
*Comprobación:*
1. `php spark routes` sigue listando las 3 rutas `sin-cotizar` intactas y aparecen 3 `manda-cotizar`.
2. `php -l app/Controllers/ReportesController.php` sin errores.
3. La pantalla hermana carga, filtra, pagina, limpia y exporta exactamente igual que antes.
4. `git diff` no muestra cambios en el bloque `SinCoti` de `public/js/reporte_presupuesto.js`
   (`:288-298`, `:375-377`, `:757-995`) ni en `getSolicitudesSinCotizar()` /
   `exportarSolicitudesSinCotizar*()`.
   *(Cifras actualizadas tras la inserción del bloque `MandaCoti`, que desplazó las líneas del
   hermano; las de `hallazgos.md` §"líneas del hermano" se conservan a propósito, porque
   describen el estado previo al cambio.)*

### CA-10 — Cero cambios de esquema de base de datos
*Comprobación:* `git status` y `git diff --stat` no listan nada bajo `app/Database/Migrations/` ni
ningún `.sql`. La implementación es **SELECT puro**: ninguna llamada a `insert()`, `update()`,
`delete()`, `save()`, `forge->` ni SQL crudo de escritura.

### CA-11 — Cero `new Date()` sobre strings de base de datos
*Comprobación:* buscar `new Date(` dentro del bloque `MandaCoti` de
`public/js/reporte_presupuesto.js` → debe salir **0 resultados**. El único `new Date()` permitido
es el del nombre de archivo descargado (`new Date().toISOString()`), que **no** consume datos de
la BD.
*Motivo:* `hallazgos.md` §5 — con `Invalid Date` las comparaciones dan `false` y las filas se cuelan
**sin filtrar, en silencio**.

### CA-12 — El botón de menú abre la pantalla correcta
*Comprobación:* pulsar "Solicitudes Mandadas a Cotizar" en la rejilla del menú y verificar que
`pantalla === 'mandacotizar'` renderiza el bloque y dispara
`GET api/solicitudes/manda-cotizar` (visible en la pestaña Network). Icono: `#cotizacion` existe
en `public/icons/icons.svg:113`.

### CA-13 — Aislamiento total del estado Alpine respecto a `SinCoti`
Ningún identificador del módulo contiene la cadena `SinCoti`.
*Comprobación:* `grep -n "SinCoti" public/js/reporte_presupuesto.js` devuelve **sólo** las líneas
del hermano (las preexistentes); los identificadores nuevos usan el sufijo `MandaCoti`. Igual en la
vista para `SinCoti` vs `MandaCoti`.

### CA-14 — Las rutas respetan los filtros de sesión
*Comprobación:* las 3 rutas están dentro del grupo con `['filter' => ['auth','mantenimiento']]`; sin
sesión, `GET api/solicitudes/manda-cotizar` responde redirección/401 y no emite datos.

### CA-15 — La pantalla se mantiene en el modal existente
*Comprobación:* `git diff --stat` no muestra cambios en `app/Config/MenuOptions.php`,
`app/Controllers/Modales.php`, `app/Controllers/Home.php` ni `public/js/mbscript.js`. El acceso es
únicamente mediante `irAPantalla('mandacotizar')` desde la rejilla interna de
`ReportePresupuesto.php`.

---

## 2. Invariantes (INV)

Restricciones estructurales que deben sostenerse en **todo** momento del ciclo de vida del módulo.

### INV-1 — Lectura pura
El módulo **nunca** escribe en la base de datos. Sólo `findAll()` / `select()` / `where*()` /
`join()` / `groupBy()`. Ninguna ruta crea, altera ni borra tablas, columnas o índices (Regla de Oro).

### INV-2 — Origen único de la fecha
`FechaMandaCotizar` tiene **exactamente un** origen por fila: `bitacora.fecha_hora` (`MIN`) para el
conjunto 1 y para la transición de entrada del conjunto 2, con `Solicitud.Fecha` como único
fallback cuando no existe transición de entrada. Nunca se "adivina" ni se combina con
`created_at` u otros campos.

### INV-3 — Una fila por solicitud
El reporte es una **proyección 1:1** de `Solicitud`. La cardinalidad se protege en tres capas:
`GROUP BY solicitud_id` en la bitácora, `whereNotIn` para excluir del conjunto 2 lo ya cubierto, y
`array_unique` en la fusión. Ninguna de las tres se elimina sin sustituto.

### INV-4 — Orden lexicográfico canónico
Todas las comparaciones y ordenamientos de fecha se hacen **como strings** sobre el formato fijo
`YYYY-MM-DD HH:MM:SS` (previa normalización a 19 caracteres). El orden lexicográfico de ese formato
es el orden cronológico, por lo que la comparación de strings es exacta y **no** depende del
régimen de parsing del navegador.

### INV-5 — Paridad de cálculo
`CostoTotal` se calcula con la **misma** fórmula que `getSolicitudesSinCotizar()`: productos
(`Cantidad × Importe`) + servicios (`Importe`), por `Solicitud.IVA`, redondeo a 2 decimales.
Si el hermano cambia su fórmula, esta cambia en el mismo commit.

### INV-6 — El universo no depende del estado actual, salvo el conjunto 2 explícitamente declarado
Una solicitud que salió de cotización (estado `Cotizada`, `Aprobada`, `En revision`, etc.) **sigue
apareciendo** si tiene un evento `APROBAR_Y_COTIZAR`. El reporte **no** aplica ningún `whereIn`
de estado sobre el conjunto 1. La única dependencia del estado es el conjunto 2, declarado
explícitamente en `plan.md` §4 y etiquetado en la columna `Origen`.

### INV-7 — Aislamiento de estado Alpine
El estado, los getters, los métodos, los `x-ref` y las claves `:key` del módulo usan el sufijo
`MandaCoti`. Resetear o destruir Choices del módulo **nunca** toca objetos `*SinCoti`.

### INV-8 — Cero gráficas
El reporte es tabular: tabla + `tfoot` + tarjetas de resumen + paginación. No se introduce ninguna
librería de gráficas (`chart.js`, `apexcharts`, `highcharts`, `d3`): **ninguna existe en el
repositorio** (`hallazgos.md` §7) y añadir dependencias está fuera de alcance.

### INV-9 — Cero tablas nuevas
El módulo no requiere ninguna tabla, columna ni índice nuevo. Si en el futuro se quisiera
persisting el "estado de enviar a cotizar" explícito, eso sería un módulo nuevo con su propio
ciclo SDD, no una extensión de éste.

---

## 3. Tabla riesgo → mitigación

| # | Riesgo | Impacto | Mitigación |
|---|---|---|---|
| R-1 | `bitacora.tipo_accion` **no tiene índice** (migración L102-107) | Consulta lenta si la bitácora crece mucho | **No se crea índice** (Regla de Oro). Medido: **81 ms**. Gate en `tasks.md` Fase 0: si supera ~2 s → detener y avisar. |
| R-2 | La BD de desarrollo tiene **0 eventos** `APROBAR_Y_COTIZAR` y **0** solicitudes en `En espera`/`Cotizando` | El reporte devuelve siempre vacío; no se puede demostrar | **Gate de Fase 0** (`hallazgos.md` §9.1): se detiene antes de la UI y se reporta. El backend sí se entrega y es verificable estáticamente. |
| R-3 | `new Date()` sobre `'YYYY-MM-DD HH:MM:SS'` → `Invalid Date` en Safari | **Filtrado silenciosamente roto** (las filas se cuelan) | INV-4 / CA-11: comparación de strings con `slice(0,19)`. Prohibido terminantemente. |
| R-4 | Doble definición de `registrarComponenteReportePresupuesto()` (`presupuestos.js:742` obsoleta vs `reporte_presupuesto.js:4` real) | La copia obsoleta sobreescribe el componente y desaparece la pantalla | Todo el JS nuevo va **sólo** en `reporte_presupuesto.js`. `presupuestos.js` no se toca. |
| R-5 | Colisión de nombres con el hermano (`SinCoti`) | Un identificador mal copiado rompe o contamina la pantalla hermana | Sufijo único `MandaCoti` en los ~28 identificadores nuevos (CA-13 / INV-7). |
| R-6 | JOIN de `Places` por `Departamentos.ID_Place` y **no** por `Solicitud.ID_UnidadOperativa` | Complejo incorrecto / `NULL` | Comentario explícito en el código y línea exacta citada (`plan.md` §5, `ReportesController.php:2126`). |
| R-7 | `selectMin()` escaparía `"fecha_hora"` → alias inválido en PostgreSQL | Error 500 en la consulta de bitácora | Se usa `->select('... MIN(fecha_hora) AS ...')` literal, **no** `selectMin()` (notado en el código). |
| R-8 | Booleanos de PostgreSQL como `'t'`/`'f'` vs `1` de la BD de desarrollo | IVA mal aplicado (±16 %) | Se replica la condición del hermano, que cubre `'t'`, `'1'`, `1` y `true` (INV-5 / CA-7). |
| R-9 | `fecha_hora` es `timestamp` **con microsegundos** (`...13:33:13.253512`) | El string tiene 26 caracteres; comparar contra 19 rompe la alineación | `slice(0, 19)` en JS; `(string)` en PHP. Documentado en `hallazgos.md` §1.2. |
| R-10 | Fechas aproximadas (no reales) en el conjunto 2 | El usuario podría leer "mandada a cotizar el día de creación" | Columna `Origen = 'Estado actual'` explícita + limitation documentada en `plan.md` §4 y CA-4. Corregirlo exigiría una columna nueva → prohibida (INV-9). |
| R-11 | La App no tiene cobertura de tests (`vendor\bin\phpunit` sólo corre 5 tests del framework) | Una regresión no la detecta ningún test | `tasks.md` Fase 6 exige verificación manual reproducible + `php -l` + `php spark routes` + revisión de `git diff` línea a línea. |
| R-12 | Necesidad de una clase Tailwind inexistente en `public/css/styless.css` | El estilo no llega a producción | Sólo se reutilizan clases ya presentes en el archivo; si hiciera falta una nueva, `npm run build:product` + commit del CSS compilado y constancia en el reporte. |
| R-13 | El nombre del exportador hermano (`...Json()`) genera XLSX | Confusión al replicar | El nuestro se llama deliberadamente `...Xlsx()` (`plan.md` §2). |
| R-14 | El botón de menú nuevo queda "vacío" si falta alguno de los registros de cableado | pantalla en blanco | Al vivir **dentro** del modal existente sólo hacen falta 2 registros: `MenuOptions.php`/`Home.php`/`Modales.php`/`mbscript.js` **no** se tocan (CA-15). Verificado en CA-12. |