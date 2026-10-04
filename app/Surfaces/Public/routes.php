<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.welcome')->name('public.home');
