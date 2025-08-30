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
        $routes->post('modales/registrarUsuario', 'Modales::registrarUsuario');
        // Ruta POST para registrar productos
        $routes->post('modales/registrarMaterial', 'Modales::registrarMaterial');
        // Ruta POST para eliminar productos
        $routes->post('modales/eliminarProducto/(:num)', 'Modales::eliminarProducto/$1');
        //Ruta POST para editar productos
        $routes->post('modales/editarProducto/(:num)', 'Modales::editarProducto/$1');

        // Otros
        $routes->get('archivo', 'Archivo::index');
        $routes->post('solicitudes/registrar', 'Archivo::subir');
        $routes->get('modales/(:segment)', 'Modales::mostrar/$1');
        $routes->get('auth/logout', 'Auth::logout');
        $routes->get('modales/vistas/product_row', 'Modales::getProductTableRow');
        // API Restful
        //region productos
        $routes->get('api/product/search', 'Api::search');
        $routes->get('api/product/all', 'Api::allProducts');
        $routes->get('api/product/(:num)', 'Api::getProductById/$1');
        $routes->get('api/product', 'Api::allProducts');
        //endregion
        //region departamentos
        $routes->get('api/departments/all', 'Api::getDepartments');
        //endregion
        //region proveedores
        $routes->get('api/providers/all', 'Api::getAllProviders');
        //endregion
        $routes->get('api/historic', 'Api::getHistorial');
    });
}
// --- Rutas para Modo de Desarrollo ---
// Se aplican en ambos casos (instalación o app) si el entorno es 'development'.
if (ENVIRONMENT === 'development') {
    $routes->match(['GET', 'POST'], 'test', 'Test::index');
    $routes->get('installer', 'Installer::index');
    $routes->post('installer/process', 'Installer::process');
    $routes->post('installer/testConnection', 'Installer::testConnection');
    $routes->get('api/product/search', 'Api::search');
    $routes->get('api/product/all', 'Api::allProducts');
    $routes->get('api/product/(:num)', 'Api::getProductById/$1');
    $routes->get('api/product', 'Api::allProducts');
    $routes->get('api/departments/all', 'Api::getDepartments');

    $routes->get('api/gentoken', 'Api::gentoken');
}