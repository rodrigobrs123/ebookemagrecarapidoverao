<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'landing');
Route::view('/pagamento/{codigo}', 'landing');
