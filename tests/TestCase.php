<?php

namespace Tests;

use App\Support\Antispam;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Marca de llegada de un formulario abierto hace un minuto (como una persona). */
    protected function antispam(): array
    {
        return ['llegada' => Antispam::marca(time() - 60)];
    }
}
