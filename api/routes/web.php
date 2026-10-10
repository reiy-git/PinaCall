<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'system' => 'PINACALL Academic Management System API',
        'status' => 'operational',
        'version' => '2.1.0',
    ]);
});

