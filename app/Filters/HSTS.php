<?php namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class HSTS implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it could *change* the response during
     * execution and return the CodeIgniter\HTTP\Response
     * object as current response.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // No se necesita hacer nada antes de la solicitud
    }

    /**
     * We modify the response here.
     *
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Solo agregar la cabecera si la conexión es segura (HTTPS)
        // CodeIgniter debe estar configurado para forzar HTTPS
        if ($request->isSecure())
        {
            // 'max-age=31536000' es un año. 
            // 'includeSubDomains' asegura que todos los subdominios también usen HSTS.
            // 'preload' es opcional, pero ayuda a la mitigación TOFU.
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }
    }
}