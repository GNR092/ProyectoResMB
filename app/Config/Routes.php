<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');


$routes->get('archivo', 'Archivo::index');
$routes->post('archivo/subir', 'Archivo::subir');

$routes->get('modales/(:segment)', 'Modales::mostrar/$1');

