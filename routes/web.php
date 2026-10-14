<?php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Torre Manager API v1.0 - Acceso denegado.']);
});
