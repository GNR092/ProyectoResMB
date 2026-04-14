<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$installerLockFile = WRITEPATH . 'installer.lock';

// --- Rutas del Instalador ---
if (!file_exists($installerLockFile)) {
    // La ruta raíz redirige al instalador
    $routes->get('/', 'Installer::index');
    $routes->get('installer', 'Installer::index');

    // Rutas del proceso de instalación
    $routes->post('installer/process', 'Installer::process');
    $routes->post('installer/testConnection', 'Installer::testConnection');

    // Rutas de las migraciones
    $routes->get('installer/migrate', 'Installer::migrate');
} else {
    // --- Rutas de la Aplicación ---
    // Estas rutas solo están disponibles si el archivo de bloqueo YA existe.
    $routes->get('installer/success', 'Installer::success');
    $routes->get('/', 'Home::index');
    $routes->get('mantenimiento', 'Mantenimiento::index');
    // Login
    $routes->get('auth', 'Auth::index');
    $routes->post('auth/login', 'Auth::login');
    // Auth
    $routes->get('auth/logout', 'Auth::logout');
    // API Token Generation
    $routes->post('api/gentoken', 'Api::gentoken');

    /*
     **
     * Proteccion de rutas para evitar que se mande o filtre información sensible
     * Agregar nuevas rutas despues del $routes->group('/', ['filter' => 'auth'], function ($routes)
     */
    $routes->group('/', ['filter' => ['auth', 'mantenimiento']], function ($routes) {
        //Registrar usuarios
        $routes->post('modales/actualizarUsuario/(:num)', 'Modales::actualizarUsuario/$1');
        $routes->post('modales/eliminarUsuario/(:num)', 'Modales::eliminarUsuario/$1');
        $routes->post('modales/registrarUsuario', 'Modales::registrarUsuario');
        // Productos
        $routes->post('modales/registrarMaterial', 'Modales::registrarMaterial');
        $routes->post('modales/eliminarProducto/(:num)', 'Modales::eliminarProducto/$1');
        $routes->post('modales/editarProducto/(:num)', 'Modales::editarProducto/$1');
        $routes->post('modales/actualizarProducto/(:num)', 'Modales::actualizarProducto/$1');
        $routes->post('modales/insertarHistorialProducto', 'Modales::insertarHistorialProducto');
        $routes->post('modales/descontarStock', 'Modales::descontarStockEntrega');

        // Proveedores
        $routes->post('proveedores/insertar', 'Modales::insertarProveedor');
        $routes->post('proveedores/eliminarProveedor/(:num)', 'Modales::eliminarProveedor/$1');
        $routes->post('proveedores/editar/(:num)', 'Modales::editarProveedor/$1');

        // Solicitudes y Cotizaciones
        $routes->post('api/cotizacion/crear', 'Api::crearCotizacion');
        $routes->post('api/solicitud/update', 'Api::actualizarMontos');
        $routes->post('api/solicitud/enviar-revision', 'Api::enviarSolicitudARevision');
        $routes->post('api/solicitud/dictaminar', 'Api::dictaminarSolicitud');
        $routes->get('api/solicitudes/pendientes-jefe', 'Api::getPendientesAprobacionJefe');
        $routes->post('api/solicitud/dictaminar-jefe', 'Api::dictaminarSolicitudJefe');
        $routes->post('api/solicitud/cancelar', 'Api::cancelarSolicitud');
        $routes->post('api/solicitud/aprobar-y-cotizar', 'Api::aprobarYCotizar');
        $routes->post('api/orden/generar/(:num)', 'Api::GenerarOrden/$1');
        $routes->post('solicitudes/registrar', 'Archivo::subir');
        $routes->get('solicitudes/archivo/(:num)/(:any)', 'Archivo::descargar/$1/$2');
        $routes->get('solicitudes/archivo/(:num)', 'Archivo::descargar/$1');        $routes->get(
            'cotizaciones/archivo/(:num)/(:segment)',
            'Archivo::descargarCotizacion/$1/$2',
        );

        //crud cuentas
        $routes->get('modales/cuentas/proveedor/(:num)', 'Modales::getCuentasByProveedor/$1');
        $routes->post('modales/cuentas/insertar', 'Modales::insertarCuenta');
        $routes->post('modales/cuentas/editar/(:num)', 'Modales::actualizarCuenta/$1');
        $routes->post('modales/cuentas/eliminar/(:num)', 'Modales::eliminarCuenta/$1');

        // Modales
        $routes->get('modales/ReportePresupuesto', 'ReportesController::index');
        $routes->get('modales/(:segment)', 'Modales::mostrar/$1');
        $routes->get('modales/vistas/product_row', 'Modales::getProductTableRow');
        $routes->get('modales/vistas/service_row', 'Modales::getServiceTableRow');

        // API Restful - Productos
        $routes->get('api/product/search', 'Api::search');
        $routes->get('api/product/all', 'Api::allProducts');
        $routes->get('api/product/(:num)', 'Api::getProductById/$1');
        $routes->get('api/product', 'Api::allProducts');
        //endregion

        //region departamentos
        $routes->get('api/departments/all', 'Api::getDepartments');

        //region proveedores
        $routes->get('api/providers/all', 'Api::getAllProviders');
        $routes->get('api/providers/full-list', 'Api::getFullProvidersList');
        $routes->get('api/providers/exportar-excel', 'Api::exportarProveedoresExcel');
        $routes->get('api/provider/(:num)', 'Api::getProviderById/$1');

        // Historial
        $routes->get('api/historic', 'Api::getHistorial');
        $routes->get('api/historic/department/(:num)', 'Api::getHistorialByDepartment/$1');
        $routes->get('api/historic/movimientos-proveedor', 'Api::getMovimientosProveedor', ['filter' => 'mantenimiento']);
        $routes->get('api/historic/reporte-vencimientos', 'Api::getReporteVencimientos', ['filter' => 'mantenimiento']);
        $routes->post('api/vencimientos/exportar-datos', 'ReportesController::exportarVencimientosJson');
        $routes->post('api/historic/exportar-movimientos', 'Api::exportarMovimientosExcel');
        $routes->get('api/historial/exportar', 'Api::exportarHistorial');

        // Solicitudes
        $routes->get('api/solicitud/details/(:num)', 'Api::getSolicitudDetails/$1');
        $routes->get('api/cotizacion/details/(:num)', 'Api::getCotizacionDetails/$1');
        $routes->get('api/solicitudes/cotizadas', 'Api::getSolicitudesCotizadas');
        $routes->get('api/solicitudes/getsoluser/(:num)', 'Api::getSolicitudesUsers/$1');
        $routes->get('api/solicitudes/en-revision', 'Api::getSolicitudesEnRevision');
        $routes->get('api/pagos/all', 'Api::getAllPagos');
        $routes->get('api/pagos/programados', 'Api::getPagosProgramados');
        $routes->get('api/pagos/exportar', 'Api::exportarPagosProgramados');
        $routes->get('api/exportar-requisiciones', 'Api::exportarRequisiciones');

        //Inventarios
        $routes->get('inventario/getProveedores', 'Inventario::getProveedores');
        $routes->get('inventario/getProductos', 'Inventario::getProductos');
        $routes->post('inventario/guardarIngresoManual', 'Inventario::guardarIngresoManual');
        $routes->get('inventario/getReceptores', 'Inventario::getReceptores');
        $routes->post('inventario/crearProductoRapido', 'Inventario::crearProductoRapido');

        //Orden de compra
        $routes->get('api/orden-compra/details/(:num)', 'Api::getOrdenCompra/$1');
        $routes->get('api/orden-compra/alldata', 'Api::getAllOrdenCompraData');
        $routes->get('api/pagos-pendientes', 'Api::getPagosPendientes');
        $routes->get('api/fichas-pago', 'Api::getFichasPago');
        $routes->get('api/orden-compra/data/(:num)', 'Api::getOrdenCompraData/$1');

        // Rutas de apis que no saturan el servidor
        $routes->get('api/ordenes-programar', 'Api::getOrdenesParaProgramacion');
        $routes->get('api/facturas-por-pagar', 'Api::getFacturasPorPagar');

        $routes->get(
            'api/ordenes-compra/pendientes-recepcion',
            'Api::getOrdenesCompraPendientesRecepcion',
        );
        $routes->post('api/recepcion/confirmar', 'Api::confirmarRecepcion');
        $routes->post('api/bajas/destruccion/registrar', 'Api::registrarBajaDestruccion');
        $routes->get('api/orden/solicitud/(:num)', 'Api::getOrdenBySolicitudID/$1');
        $routes->post('api/solicitudes/cambiarEstado/(:num)', 'Api::cambiarEstadoOrden/$1');
        $routes->post('api/solicitud/enviarATesoreria', 'Api::enviarATesoreria');
        $routes->post(
            'api/orden/enviar-proveedor/(:num)/(:segment)',
            'Api::enviarOrdenAProveedor/$1/$2',
        );
        $routes->post('api/orden/programar-pagos', 'Api::programarPagos');

        //razon social
        $routes->get('api/razonsocial/all', 'Api::getAllRazonSocial');
        $routes->post('modales/razonsocial/insertar', 'Modales::insertarRazonSocial');
        $routes->post('modales/razonsocial/editar/(:num)', 'Modales::editarRazonSocial/$1');
        $routes->post('modales/razonsocial/eliminar/(:num)', 'Modales::eliminarRazonSocial/$1');

        //Limpiar almacenamiento
        $routes->get('api/storage/list', 'Api::getStorageList');
        $routes->get('api/storage/serve', 'Api::serveFile');
        $routes->get('api/download-attachments/(:num)', 'Api::downloadAttachmentsAsZip/$1');

        //crud places
        $routes->post('modales/crud_places/insertar', 'Modales::insertarPlace');
        $routes->post('modales/crud_places/editar/(:num)', 'Modales::editarPlace/$1');
        $routes->post('modales/crud_places/eliminar/(:num)', 'Modales::eliminarPlace/$1');

        //crud segmentos
        $routes->post('modales/crud_segmentos/insertar', 'Modales::insertarSegmento');
        $routes->post('modales/crud_segmentos/editar/(:num)', 'Modales::editarSegmento/$1');
        $routes->post('modales/crud_segmentos/eliminar/(:num)', 'Modales::eliminarSegmento/$1');

        //crud unidades operativas
        $routes->post('modales/crud_unidades_operativas/insertar', 'Modales::insertarUnidadOperativa');
        $routes->post('modales/crud_unidades_operativas/editar/(:num)', 'Modales::editarUnidadOperativa/$1');
        $routes->post('modales/crud_unidades_operativas/eliminar/(:num)', 'Modales::eliminarUnidadOperativa/$1');

        //crud departamentos
        $routes->post('modales/crud_departamentos/insertar', 'Modales::insertarDepartamento');
        $routes->post('modales/crud_departamentos/editar/(:num)', 'Modales::editarDepartamento/$1');
        $routes->post(
            'modales/crud_departamentos/eliminar/(:num)',
            'Modales::eliminarDepartamento/$1',
        );
        //Bancos de Dpto
        $routes->post('modales/crud_banco_dpto/insertar', 'Modales::insertarBancoDpto');
        $routes->post('modales/crud_banco_dpto/editar/(:num)', 'Modales::editarBancoDpto/$1');
        $routes->post('modales/crud_banco_dpto/eliminar/(:num)', 'Modales::eliminarBancoDpto/$1');

        // Rutas para CRUD Grupo Presupuestal
        $routes->post('modales/crud_grupos_presupuestales/insertar', 'Modales::insertarGrupo');
        $routes->post(
            'modales/crud_grupos_presupuestales/editar/(:num)',
            'Modales::editarGrupo/$1',
        );
        $routes->post(
            'modales/crud_grupos_presupuestales/eliminar/(:num)',
            'Modales::eliminarGrupo/$1',
        );

        // Rutas API Presupuesto Dictamen
        $routes->get('api/presupuesto/cambios', 'PresupuestoApiController::getCambiosPendientes');
        $routes->post('api/presupuesto/dictaminar', 'PresupuestoApiController::dictaminarCambio');

        // Rutas API Presupuesto Mensual
        $routes->get('api/presupuesto-mensual/estructura/(:any)/(:num)/(:num)', 'PresupuestoApiController::getEstructura/$1/$2/$3');
        $routes->post('api/presupuesto-mensual/guardar-masivo', 'PresupuestoApiController::saveMasivo');
        $routes->post('api/presupuesto-mensual/exportar-asignacion', 'PresupuestoApiController::exportarAsignacion');
        $routes->post('api/presupuesto-mensual/exportar-anual', 'PresupuestoApiController::exportarAnual');
        $routes->get('api/presupuesto/comparativo/(:any)/(:num)/(:any)', 'ReportesController::getComparativo/$1/$2/$3');
        $routes->get('api/presupuesto/exportar/(:any)/(:num)/(:any)', 'ReportesController::exportarComparativo/$1/$2/$3');
        $routes->post('api/presupuesto/exportar-datos', 'ReportesController::exportarDatosJson');

        // Rutas API Saldos Bancarios
        $routes->get('api/saldos-bancarios/estructura/(:num)/(:num)/(:num)', 'PresupuestoApiController::getEstructuraSaldos/$1/$2/$3');
        $routes->post('api/saldos-bancarios/guardar-masivo', 'PresupuestoApiController::saveSaldosMasivo');
        $routes->get('api/bancos/comparativo/(:any)/(:num)/(:any)', 'ReportesController::getComparativoBancos/$1/$2/$3');
        $routes->post('api/bancos/exportar-datos', 'ReportesController::exportarBancosJson');

        // Rutas API Reporte Completo
        $routes->get('api/reporte/completo/exportar/(:any)/(:num)/(:any)', 'ReportesController::exportarReporteCompleto/$1/$2/$3');
        $routes->post('api/reporte/completo/exportar-datos', 'ReportesController::exportarReporteCompletoJson');
        $routes->get('api/reporte/completo/(:any)/(:num)/(:any)', 'ReportesController::getReporteCompleto/$1/$2/$3');

        //Control maestro
        $routes->post('api/solicitudes/update_master/(:num)', 'ControlMaestro::update_master/$1');

        // User
        $routes->post('api/user/update', 'Api::updateUser');
        $routes->post('api/user/upload_signature', 'Api::upload_signature');

        //PDF
        $routes->get('api/solicitud/pdf/(:num)', 'GenerarPDF::GenerarRequisicion/$1');
        $routes->get('api/solicitud/pdf/(:num)/(:num)', 'GenerarPDF::GenerarRequisicion/$1/$2');
        $routes->get('api/solicitud/pdf-consolidado/(:num)', 'GenerarPDF::GenerarPdfConsolidado/$1');
        $routes->get('api/orden/pdf/(:num)', 'GenerarPDF::GenerarOrden/$1');
        $routes->get('api/requisicionpago/pdf/(:num)', 'GenerarPDF::GenerarRequisicionPago/$1');
        $routes->post('api/entrega/pdf', 'GenerarPDF::GenerarEntregaMateriales');
        $routes->get('admin/migrate', 'Installer::runMigrations');
        $routes->get('admin/rollback', 'Installer::rollback');

        $routes->get('dev', 'Dev::index');
        $routes->get('api/test-email', 'Api::testEmailConnection');
    });
}