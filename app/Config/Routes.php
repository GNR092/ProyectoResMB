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
    // Login
    $routes->get('auth', 'Auth::index');
    $routes->post('auth/login', 'Auth::login');
    // API Token Generation
    $routes->post('api/gentoken', 'Api::gentoken');

    /*
     **
     * Proteccion de rutas para evitar que se mande o filtre información sensible
     * Agregar nuevas rutas despues del $routes->group('/', ['filter' => 'auth'], function ($routes)
     */
    $routes->group('/', ['filter' => 'auth'], function ($routes) {
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

        // Proveedores
        $routes->post('proveedores/insertar', 'Modales::insertarProveedor');
        $routes->post('proveedores/eliminarProveedor/(:num)', 'Modales::eliminarProveedor/$1');
        $routes->post('proveedores/editar/(:num)', 'Modales::editarProveedor/$1');
        // Solicitudes y Cotizaciones
        $routes->post('api/cotizacion/crear', 'Api::crearCotizacion');
        $routes->post('api/solicitud/enviar-revision', 'Api::enviarSolicitudARevision');
        $routes->post('api/solicitud/dictaminar', 'Api::dictaminarSolicitud');
        $routes->get('api/solicitudes/pendientes-jefe', 'Api::getPendientesAprobacionJefe');
        $routes->post('api/solicitud/dictaminar-jefe', 'Api::dictaminarSolicitudJefe');
        $routes->post('solicitudes/registrar', 'Archivo::subir');
        $routes->get('solicitudes/archivo/(:num)', 'Archivo::descargar/$1');
        $routes->get(
            'cotizaciones/archivo/(:num)/(:segment)',
            'Archivo::descargarCotizacion/$1/$2',
        );
        // Modales
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
        // Historial
        $routes->get('api/historic', 'Api::getHistorial');
        $routes->get('api/historic/department/(:num)', 'Api::getHistorialByDepartment/$1');
        // Solicitudes
        $routes->get('api/solicitud/details/(:num)', 'Api::getSolicitudDetails/$1');
        $routes->get('api/cotizacion/details/(:num)', 'Api::getCotizacionDetails/$1');
        $routes->get('api/orden-compra/details/(:num)', 'Api::getOrdenCompra/$1');
        $routes->get('api/solicitudes/cotizadas', 'Api::getSolicitudesCotizadas');
        $routes->get('api/solicitudes/getsoluser/(:num)', 'Api::getSolicitudesUsers/$1');
        $routes->get('api/solicitudes/en-revision', 'Api::getSolicitudesEnRevision');
        $routes->post('api/solicitudes/cambiarEstado/(:num)', 'Api::cambiarEstadoOrden/$1');
        $routes->post('api/solicitud/enviarATesoreria', 'Api::enviarATesoreria');

        // Auth
        $routes->get('auth/logout', 'Auth::logout');
        //PDF
        $routes->get('api/solicitud/pdf/(:num)', 'GenerarPDF::GenerarRequisicion/$1');
        $routes->get('api/solicitud/pdf/(:num)/(:num)', 'GenerarPDF::GenerarRequisicion/$1/$2');
        $routes->get('api/pago/pdf/(:num)', 'GenerarPDF::GenerarOrdenPago/$1');
        $routes->get('api/orden/pdf/(:num)', 'GenerarPDF::GenerarOrden/$1');
    });
}