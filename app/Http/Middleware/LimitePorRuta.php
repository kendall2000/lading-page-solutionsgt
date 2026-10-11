<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests;

/**
 * «throttle» con un contador por ruta. El de Laravel cuenta por visitante sin mirar la ruta: las consultas
 * del chat (que el widget hace solo en cada página) gastaban los intentos de registro, contacto, etc.
 */
class LimitePorRuta extends ThrottleRequests
{
    protected function resolveRequestSignature($request)
    {
        $ruta = $request->route();

        return sha1(($ruta?->getName() ?: $request->method().'|'.$ruta?->uri()).'|'.parent::resolveRequestSignature($request));
    }
}
