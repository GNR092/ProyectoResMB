<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;
use App\Libraries\BitacoraService;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

/**
 * Evento para registrar en la bitácora de forma centralizada
 */
Events::on('auditoria', static function (array $data): void {
    BitacoraService::getInstance()->registrar($data);
});

/**
 * Persistir todos los logs acumulados al finalizar la ejecución del sistema
 */
Events::on('post_system', static function (): void {
    if (!is_cli()) {
        BitacoraService::getInstance()->persistir();
    }
});

/**
 * Captura errores críticos del sistema y los registra en la bitácora
 */
Events::on('systemException', static function (\Throwable $e): void {
    if (!is_cli()) {
        Events::trigger('auditoria', [
            'tipo_accion'    => 'SISTEMA_ERROR',
            'modulo'         => 'Sistema',
            'estado'         => 'fallido',
            'valores_nuevos' => json_encode([
                'excepcion' => get_class($e),
                'mensaje'   => $e->getMessage(),
                'archivo'   => $e->getFile(),
                'linea'     => $e->getLine()
            ])
        ]);
        // Aseguramos persistencia inmediata en caso de error fatal
        BitacoraService::getInstance()->persistir();
    }
});

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        if (ini_get('zlib.output_compression')) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});
