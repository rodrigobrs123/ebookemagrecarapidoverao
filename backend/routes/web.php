<?php

/*
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
*/

use Illuminate\Support\Facades\Route;

Route::view('/', 'landing');

Route::view('/pagamento/{codigo}', 'landing');