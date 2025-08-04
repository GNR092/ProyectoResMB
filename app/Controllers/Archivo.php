<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class Archivo extends BaseController
{
    public function index()
    {
        return view('formulario_subida');
    }

    public function subir()
    {
        $archivo = $this->request->getFile('archivo');

        if (!$archivo->isValid()) {
            return redirect()->back()->with('error', 'El archivo no es válido');
        }

        // Nombre aleatorio para evitar conflictos
        $nuevoNombre = $archivo->getRandomName();

        // Mover a writable/uploads/
        if ($archivo->move(WRITEPATH . 'uploads', $nuevoNombre)) {
            return redirect()->back()->with('mensaje', 'Archivo subido correctamente como: ' . $nuevoNombre);
        } else {
            return redirect()->back()->with('error', 'No se pudo mover el archivo');
        }
    }
}
