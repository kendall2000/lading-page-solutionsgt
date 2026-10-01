<?php

use Illuminate\Support\Facades\Schedule;

// Tareas programadas: las corre el contenedor «solutionsgt-tareas» (php artisan schedule:work).
Schedule::command('respaldo:crear')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('sitio:limpiar')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('pagos:sincronizar')->everySixHours()->withoutOverlapping();
