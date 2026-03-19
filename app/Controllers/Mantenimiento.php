<?php

namespace App\Controllers;

class Mantenimiento extends BaseController
{
    private const CONFIG_FILE = WRITEPATH . 'mantenimiento.json';

    public function index()
    {
        $config = $this->getConfig();

        if (!$config || !($config['activado'] ?? false)) {
            return redirect()->to('/');
        }

        $data = [
            'mensaje' => $config['mensaje'] ?? 'Estamos trabajando para mejorar tu experiencia',
        ];

        return view('mantenimiento/index', $data);
    }

    private function getConfig(): ?array
    {
        if (!file_exists(self::CONFIG_FILE)) {
            return null;
        }

        $content = file_get_contents(self::CONFIG_FILE);
        $config = json_decode($content, true);

        return $config;
    }
}
