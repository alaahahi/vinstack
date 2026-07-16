<?php

use App\Monitor\Providers\MonitorServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MonitorServiceProvider::class,
];
