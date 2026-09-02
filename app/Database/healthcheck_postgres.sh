#!/usr/bin/env bash
set -euo pipefail

CONTAINER_NAME="${1:-dbpostgre}"
DB_USER="${DB_USER:-mbuser}"
DB_NAME="${DB_NAME:-mb_compras}"
AUDIT_FILE="/home/compras/docker/compras/html/app/Database/auditoria_schema_postgres.sql"

echo "[healthcheck] Ejecutando auditoria SQL..."
docker exec -i "$CONTAINER_NAME" psql -U "$DB_USER" -d "$DB_NAME" < "$AUDIT_FILE"

missing_or_extra_tables=$(docker exec "$CONTAINER_NAME" psql -U "$DB_USER" -d "$DB_NAME" -At -c "
WITH expected_tables AS (
    SELECT table_name FROM (VALUES
        ('BancoDpto'),('bitacora'),('Cotizacion'),('Cuentas'),('Departamentos'),
        ('DetalleEntrega'),('DetalleIngreso'),('Detalle_Servicio'),('Entregas'),
        ('GrupoPresupuestal'),('HistorialProductos'),('Ingresos'),('MapeoProductos'),
        ('OrdenCompra'),('Pago'),('Places'),('PresupuestoAnual'),('PresupuestoMensual'),
        ('Producto'),('proveedor_archivos'),('Proveedor'),('Razon_Social'),
        ('SaldosBancarios'),('segmento_negocio'),('SolicitudesCambioPresupuesto'),
        ('Solicitud'),('Solicitud_Producto'),('Solicitud_Servicios'),('User_Tokens'),
        ('UnidadOperativa'),('Usuarios')
    ) AS t(table_name)
),
allowed_extra_tables AS (
    SELECT table_name FROM (VALUES ('migrations'),('Detalle_Producto')) AS t(table_name)
),
existing AS (
    SELECT tablename AS table_name FROM pg_tables WHERE schemaname='public'
),
missing AS (
    SELECT e.table_name FROM expected_tables e LEFT JOIN existing x USING (table_name) WHERE x.table_name IS NULL
),
extra AS (
    SELECT x.table_name
    FROM existing x
    LEFT JOIN expected_tables e USING (table_name)
    LEFT JOIN allowed_extra_tables a USING (table_name)
    WHERE e.table_name IS NULL AND a.table_name IS NULL
)
SELECT (SELECT COUNT(*) FROM missing) + (SELECT COUNT(*) FROM extra);
")

missing_fields=$(docker exec "$CONTAINER_NAME" psql -U "$DB_USER" -d "$DB_NAME" -At -c "
WITH expected_fields AS (
    SELECT * FROM (VALUES
        ('BancoDpto','ID_RazonSocial'),('BancoDpto','Clabe'),('BancoDpto','Banco'),('BancoDpto','Alias'),('BancoDpto','Cuenta'),('BancoDpto','Sucursal'),
        ('bitacora','usuario_id'),('bitacora','nombre_usuario'),('bitacora','departamento_id'),('bitacora','complejo_id'),('bitacora','razon_social_id'),('bitacora','tipo_accion'),('bitacora','clasificacion'),('bitacora','usuario_autoriza_id'),('bitacora','modulo'),('bitacora','solicitud_id'),('bitacora','orden_compra_id'),('bitacora','cotizacion_id'),('bitacora','ip_address'),('bitacora','valores_antiguos'),('bitacora','valores_nuevos'),('bitacora','estado'),
        ('Cotizacion','ID_Solicitud'),('Cotizacion','ID_Proveedor'),('Cotizacion','Cotizacion_Files'),('Cotizacion','Total'),('Cotizacion','ID_Usuario_Cotiza'),
        ('Cuentas','ID_Proveedor'),('Cuentas','Cuenta'),
        ('Departamentos','ID_UnidadOperativa'),('Departamentos','ID_Place'),('Departamentos','Nombre'),
        ('DetalleEntrega','ID_Entrega'),('DetalleEntrega','ID_Producto'),('DetalleEntrega','Cantidad'),
        ('DetalleIngreso','ID_Ingreso'),('DetalleIngreso','ID_Producto'),('DetalleIngreso','CantidadOriginal'),('DetalleIngreso','CantidadIngresada'),
        ('Detalle_Servicio','ID_SolicitudServ'),('Detalle_Servicio','Nombre_Servicio'),('Detalle_Servicio','Costo'),
        ('Entregas','ID_Usuario'),('Entregas','NombreEntrega'),('Entregas','DepartamentoRecibe'),('Entregas','NombreRecibe'),('Entregas','Fecha'),
        ('GrupoPresupuestal','ID_GrupoPresupuestal'),('GrupoPresupuestal','Nombre'),('GrupoPresupuestal','Descripcion'),('GrupoPresupuestal','ID_UnidadOperativa'),('GrupoPresupuestal','activo'),('GrupoPresupuestal','es_manual'),
        ('HistorialProductos','ID_Usuario'),('HistorialProductos','ID_Producto'),('HistorialProductos','CodigoAnt'),('HistorialProductos','NombreAnt'),('HistorialProductos','ExistenciaAnt'),('HistorialProductos','CodigoNew'),('HistorialProductos','NombreNew'),('HistorialProductos','ExistenciaNew'),('HistorialProductos','Razon'),
        ('Ingresos','ID_Proveedor'),('Ingresos','ID_Usuario'),('Ingresos','UUID'),('Ingresos','RFC_Receptor'),('Ingresos','FechaEmision'),('Ingresos','NombreArchivoXML'),
        ('MapeoProductos','ID_Proveedor'),('MapeoProductos','IdentificadorXML'),('MapeoProductos','ID_Producto'),('MapeoProductos','FactorConversion'),
        ('OrdenCompra','ID_Cotizacion'),('OrdenCompra','ID_Proveedor'),('OrdenCompra','Estado'),('OrdenCompra','Fecha'),('OrdenCompra','File_Factura'),('OrdenCompra','File_Comprobante'),('OrdenCompra','File_ReqPag'),('OrdenCompra','File_Remision'),('OrdenCompra','File_FacturaEntrada'),('OrdenCompra','File_FacturaServicioPDF'),('OrdenCompra','File_FacturaServicioXML'),('OrdenCompra','File_Complemento'),('OrdenCompra','FechaRefPago'),('OrdenCompra','FechaPagoRealizado'),
        ('Pago','ID_OrdenCompra'),('Pago','ID_Proveedor'),('Pago','Tipo'),('Pago','Fecha_Solicitud'),('Pago','Fecha_Pago'),('Pago','Folio'),('Pago','Concepto'),('Pago','Forma'),
        ('Places','Nombre_Corto'),('Places','Nombre_Completo'),('Places','ID_RazonSocial'),('Places','id_segmento'),('Places','activo'),
        ('PresupuestoAnual','ID_RazonSocial'),('PresupuestoAnual','Anio'),('PresupuestoAnual','Monto'),
        ('PresupuestoMensual','ID_UnidadOperativa'),('PresupuestoMensual','ID_GrupoPresupuestal'),('PresupuestoMensual','Anio'),('PresupuestoMensual','Mes'),('PresupuestoMensual','Monto_Asignado'),('PresupuestoMensual','Monto_Comprometido'),('PresupuestoMensual','Monto_Ejecutado'),
        ('Producto','Codigo'),('Producto','Nombre'),('Producto','Existencia'),
        ('proveedor_archivos','id_proveedor'),('proveedor_archivos','nombre_archivo'),('proveedor_archivos','fecha_subida'),
        ('Proveedor','RazonSocial'),('Proveedor','Correo'),('Proveedor','RFC'),('Proveedor','Banco'),('Proveedor','Cuenta'),('Proveedor','Clabe'),('Proveedor','Tel_Contacto'),('Proveedor','Nombre_Contacto'),('Proveedor','Servicio'),('Proveedor','Dias_Credito'),('Proveedor','Monto_Credito'),
        ('Razon_Social','Nombre'),('Razon_Social','RFC'),('Razon_Social','Ubicacion'),('Razon_Social','Nombre_Comercial'),('Razon_Social','Direccion'),
        ('SaldosBancarios','id_bancodpto'),('SaldosBancarios','mes'),('SaldosBancarios','anio'),('SaldosBancarios','saldo_inicial'),('SaldosBancarios','saldo_final'),
        ('segmento_negocio','id_razon_social'),('segmento_negocio','nombre'),('segmento_negocio','descripcion'),
        ('SolicitudesCambioPresupuesto','ID_Usuario'),('SolicitudesCambioPresupuesto','Modulo'),('SolicitudesCambioPresupuesto','Accion'),('SolicitudesCambioPresupuesto','ID_Afectado'),('SolicitudesCambioPresupuesto','Datos_Payload'),('SolicitudesCambioPresupuesto','Datos_Antiguos'),('SolicitudesCambioPresupuesto','Estado'),('SolicitudesCambioPresupuesto','Comentarios_Solicitante'),('SolicitudesCambioPresupuesto','Comentarios_Revisor'),('SolicitudesCambioPresupuesto','created_at'),('SolicitudesCambioPresupuesto','updated_at'),
        ('Solicitud','ID_Usuario'),('Solicitud','ID_Dpto'),('Solicitud','ID_UnidadOperativa'),('Solicitud','ID_Proveedor'),('Solicitud','ID_Cuenta'),('Solicitud','ID_RazonSocial'),('Solicitud','IVA'),('Solicitud','Fecha'),('Solicitud','Estado'),('Solicitud','No_Folio'),('Solicitud','Archivo'),('Solicitud','ComentariosAdmin'),('Solicitud','TipoComentarioAdmin'),('Solicitud','ComentariosUser'),('Solicitud','Tipo'),('Solicitud','MetodoPago'),('Solicitud','Fecha_Aprobacion'),('Solicitud','ID_Usuario_Autoriza'),('Solicitud','ComentarioCotizacion'),
        ('Solicitud_Producto','ID_Solicitud'),('Solicitud_Producto','ID_GrupoPresupuestal'),('Solicitud_Producto','Codigo'),('Solicitud_Producto','Nombre'),('Solicitud_Producto','Cantidad'),('Solicitud_Producto','Importe'),('Solicitud_Producto','Monto_Comprometido_Original'),
        ('Solicitud_Servicios','ID_Solicitud'),('Solicitud_Servicios','Nombre'),('Solicitud_Servicios','Importe'),
        ('User_Tokens','ID_Usuario'),('User_Tokens','token'),('User_Tokens','expires_at'),
        ('UnidadOperativa','Nombre'),('UnidadOperativa','ID_Place'),('UnidadOperativa','activo'),
        ('Usuarios','ID_Dpto'),('Usuarios','ID_RazonSocial'),('Usuarios','Nombre'),('Usuarios','Correo'),('Usuarios','ContrasenaP'),('Usuarios','ContrasenaG'),('Usuarios','Numero'),('Usuarios','Firma_digital')
    ) AS x(table_name, column_name)
),
existing_fields AS (
    SELECT table_name, column_name FROM information_schema.columns WHERE table_schema='public'
)
SELECT COUNT(*)
FROM expected_fields e
LEFT JOIN existing_fields x USING (table_name, column_name)
WHERE x.column_name IS NULL;
")

migration_mismatches=$(docker exec "$CONTAINER_NAME" psql -U "$DB_USER" -d "$DB_NAME" -At -c "
WITH expected_from_migration AS (
    SELECT * FROM (VALUES
        ('2026-03-03-100000','SaldosBancarios',NULL),
        ('2026-03-18-225650','SolicitudesCambioPresupuesto',NULL),
        ('2026-04-09-155308','bitacora',NULL),
        ('2026-03-12-181818','UnidadOperativa',NULL),
        ('2026-03-03-120000','segmento_negocio',NULL),
        ('2026-04-15-180000','proveedor_archivos',NULL),
        ('2026-03-10-113000','BancoDpto','ID_RazonSocial'),
        ('2026-04-06-130000','BancoDpto','Alias'),
        ('2026-04-06-130000','BancoDpto','Cuenta'),
        ('2026-04-06-130000','BancoDpto','Sucursal'),
        ('2026-04-06-120000','Razon_Social','Nombre_Comercial'),
        ('2026-04-06-120000','Razon_Social','Direccion'),
        ('2026-04-13-120000','OrdenCompra','File_Complemento'),
        ('2026-02-27-234219','Solicitud_Producto','Monto_Comprometido_Original')
    ) AS t(version, table_name, column_name)
),
migrated AS (SELECT DISTINCT version FROM migrations),
existing_tables AS (SELECT tablename AS table_name FROM pg_tables WHERE schemaname='public'),
existing_columns AS (SELECT table_name, column_name FROM information_schema.columns WHERE table_schema='public')
SELECT COUNT(*)
FROM expected_from_migration e
JOIN migrated m USING (version)
LEFT JOIN existing_tables t USING (table_name)
LEFT JOIN existing_columns c ON c.table_name = e.table_name AND c.column_name = e.column_name
WHERE (e.column_name IS NULL AND t.table_name IS NULL)
   OR (e.column_name IS NOT NULL AND c.column_name IS NULL);
")

missing_autoincrement=$(docker exec "$CONTAINER_NAME" psql -U "$DB_USER" -d "$DB_NAME" -At -c "
WITH pk_expected AS (
    SELECT * FROM (VALUES
        ('bitacora','id'),
        ('segmento_negocio','id'),
        ('UnidadOperativa','ID_UnidadOperativa'),
        ('proveedor_archivos','id_archivo')
    ) AS t(table_name, column_name)
)
SELECT COUNT(*)
FROM pk_expected p
JOIN information_schema.columns c
  ON c.table_schema = 'public'
 AND c.table_name = p.table_name
 AND c.column_name = p.column_name
WHERE c.column_default IS NULL OR c.column_default NOT LIKE 'nextval%';
")

total_issues=$((missing_or_extra_tables + missing_fields + migration_mismatches + missing_autoincrement))

echo "[healthcheck] missing_or_extra_tables=$missing_or_extra_tables"
echo "[healthcheck] missing_fields=$missing_fields"
echo "[healthcheck] migration_mismatches=$migration_mismatches"
echo "[healthcheck] missing_autoincrement=$missing_autoincrement"

if [ "$total_issues" -eq 0 ]; then
  echo "[healthcheck] OK: esquema consistente con models y migraciones"
  exit 0
fi

echo "[healthcheck] FAIL: se detectaron $total_issues inconsistencias"
exit 1
