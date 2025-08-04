<?php

namespace App\Controllers;

use Config\MenuOptions;

class Home extends BaseController
{
    public function index()
    {
        $configMenu = new MenuOptions();

        // Mostrar todas las opciones disponibles del sistema
        $opcionesDisponibles = $configMenu->opciones;

        $data = [
            'opcionesDinamicas' => $opcionesDisponibles,
            'nombre_usuario' => session('nombre_usuario') ?? 'Usuario',
            'departamento_usuario' => session('departamento_usuario') ?? 'Departamento',
        ];

        return view('inicio', $data);
    }
}
