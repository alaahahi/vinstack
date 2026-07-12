<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('vinstack:sync')
    ->hourly()
    ->name('vinstack-auto-sync')
    ->withoutOverlapping();

Schedule::command('image-transfers:process')
    ->everyMinute()
    ->name('image-transfers-process')
    ->withoutOverlapping();
