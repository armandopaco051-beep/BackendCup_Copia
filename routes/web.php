<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('landing');
});

Route::get('/login', function () {
    return view('auth.login');
});

Route::get('/registro-postulante', function () {
    return view('auth.registro-postulante');
});

Route::get('/dashboard', function () {
    $usuario = Auth::user();

    if (! $usuario) {
        return redirect('/login');
    }

    return match ($usuario->tipo) {
        'docente' => redirect('/dashboard/docente'),
        'postulante' => redirect('/dashboard/postulante'),
        default => view('dashboard.index'),
    };
});

Route::get('/dashboard/docente', function () {
    if (! Auth::user()) {
        return redirect('/login');
    }

    if (Auth::user()->tipo !== 'docente') {
        return redirect('/dashboard');
    }

    return view('dashboard.docente');
});

Route::get('/dashboard/postulante', function () {
    if (! Auth::user()) {
        return redirect('/login');
    }

    if (Auth::user()->tipo !== 'postulante') {
        return redirect('/dashboard');
    }

    return view('dashboard.postulante');
});

Route::get('/dashboard/usuarios', function () {
    return view('dashboard.usuarios');
});

Route::get('/dashboard/roles-permisos', function () {
    return view('dashboard.roles-permisos');
});

Route::get('/dashboard/bitacora', function () {
    $usuario = Auth::user();
    $usuario?->loadMissing('rol');

    if (! $usuario) {
        return redirect('/login');
    }

    abort_unless($usuario->rol?->nombre === 'administrador', 403);

    return view('dashboard.bitacora');
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

Route::get('/dashboard/periodo-academico', function () {
    return view('dashboard.periodo-academico');
});

Route::get('/dashboard/distribucion-grupos', function () {
    return view('dashboard.distribucion-grupos');
});

Route::get('/dashboard/horarios-grupos', function () {
    return view('dashboard.horarios-grupos');
});

Route::get('/dashboard/aulas', function () {
    return view('dashboard.aulas');
});

Route::get('/dashboard/calificaciones', function () {
    return view('dashboard.calificaciones');
});

Route::get('/dashboard/asistencias', function () {
    return view('dashboard.asistencias');
});

Route::get('/dashboard/catalogos-academicos', function () {
    return view('dashboard.catalogos-academicos');
});

Route::get('/dashboard/docentes-materias', function () {
    return view('dashboard.docentes-materias');
});

Route::get('/dashboard/perfil', function () {
    return view('dashboard.perfil');
});
