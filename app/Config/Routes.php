<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$installerLockFile = WRITEPATH . 'installer.lock';

if (!file_exists($installerLockFile)) {

    $routes->get('/', 'Installer::index');
    $routes->post('installer/process', 'Installer::process');
    $routes->post('installer/testConnection', 'Installer::testConnection');
    $routes->get('test', 'Test::index');

} else {

    $routes->get('installer/success', 'Installer::success');
    $routes->get('/', 'Home::index');
    $routes->get('archivo', 'Archivo::index');
    $routes->post('archivo/subir', 'Archivo::subir');
    $routes->get('modales/(:segment)', 'Modales::mostrar/$1');
    $routes->get('/auth', 'Auth::login');
    $routes->match(['GET', 'POST'], 'auth/login', 'Auth::login');
    $routes->get('test', 'Test::index');
}
