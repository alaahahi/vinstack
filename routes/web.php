<?php

use Illuminate\Support\Facades\Route;

// Never shadow /api/* — otherwise missing API routes look like "POST not supported (GET, HEAD)".
Route::view('/{any?}', 'app')->where('any', '^(?!api(?:/|$)).*');
