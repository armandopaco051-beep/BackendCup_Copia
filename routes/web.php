<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard.index');
});

Route::get('/dashboard/usuarios', function () {
    return view('dashboard.usuarios');
});

Route::get('/dashboard/roles-permisos', function () {
    return view('dashboard.roles-permisos');
});

Route::get('/dashboard/password', function () {
    return view('dashboard.password');
});

Route::get('/dashboard/preinscripciones', function () {
    return view('dashboard.preinscripciones');
});

Route::get('/dashboard/requisitos', function () {
    return view('dashboard.requisitos');
});

Route::get('/dashboard/pagos', function () {
    return view('dashboard.pagos');
});

Route::get('/dashboard/habilitacion', function () {
    return view('dashboard.habilitacion');
});

Route::get('/dashboard/perfil', function () {
    return view('dashboard.perfil');
});
