<?php

namespace App\Controllers;

use Config\MenuOptions;
use App\Models\DepartamentosModel;
use App\Models\UsuarioModel;


class Home extends BaseController
{
    
    public function index()
    {
        if(session('isLoggedIn'))
        {    
        $configMenu = new MenuOptions();

        // Mostrar todas las opciones disponibles del sistema
        $opcionesDisponibles = $configMenu->opciones;

        $departamentos = new DepartamentosModel();
        $usuarios = new UsuarioModel();
        $usuario = $usuarios->find(session('id'));
        
        $departamento = $departamentos->find($usuario['ID_Dpto']);

        $data = [
            'opcionesDinamicas' => $opcionesDisponibles,
            'nombre_usuario' => session('name') ?? 'Usuario',
            'departamento_usuario' => $departamento['Nombre'] ?? 'Departamento',
            'departamentos' => $departamentos->findall(),
        ];
        $session = session();
        $session->set($data);

        return view('inicio', $data);
        }
        else
        {
            return redirect()->to('/auth');
        }
    }
}
