<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return view('welcome'); 
});

Route::get('/easy-nest', function () {
    return Inertia::render('Home'); // "Home" est le nom du composant Vue.js
});
