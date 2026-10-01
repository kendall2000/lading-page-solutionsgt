<?php

namespace App\Exceptions;

use RuntimeException;

/** PayPal rechazó una llamada o no respondió. El mensaje se puede mostrar en el panel; al cliente se le da uno genérico. */
class ErrorPayPal extends RuntimeException
{
    public function __construct(string $mensaje, public readonly ?string $codigo = null)
    {
        parent::__construct($mensaje);
    }
}
