<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

date_default_timezone_set('Europe/Paris');

return function (array $context) {
    header_remove('X-Powered-By');
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
