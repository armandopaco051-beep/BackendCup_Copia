<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Pagina publica institucional del sistema CUP.
Route::get('/', function () {
    return view('landing');
});

// Formulario publico para CU-01 Iniciar sesion.
Route::get('/login', function () {
    return view('auth.login');
});

// Formulario publico para CU-06 Registrar preinscripcion.
Route::get('/registro-postulante', function () {
    return view('auth.registro-postulante');
});

// Entrada principal: redirige al panel correspondiente segun el tipo de usuario.
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
})->middleware('auth');

// Panel exclusivo del docente.
Route::get('/dashboard/docente', function () {
    if (! Auth::user()) {
        return redirect('/login');
    }

    if (Auth::user()->tipo !== 'docente') {
        return redirect('/dashboard');
    }

    return view('dashboard.docente');
})->middleware('auth');

// Panel exclusivo del postulante.
Route::get('/dashboard/postulante', function () {
    if (! Auth::user()) {
        return redirect('/login');
    }

    if (Auth::user()->tipo !== 'postulante') {
        return redirect('/dashboard');
    }

    return view('dashboard.postulante');
})->middleware('auth');

// CU-03: pantalla administrativa para gestionar cuentas y perfiles.
Route::get('/dashboard/usuarios', function () {
    return view('dashboard.usuarios');
})->middleware(['auth', 'permiso:listar_usuario']);

// CU-04: pantalla para configurar roles y su matriz de permisos.
Route::get('/dashboard/roles-permisos', function () {
    return view('dashboard.roles-permisos');
})->middleware(['auth', 'permiso:modificar_roles,asignar_roles']);

// CU-24 y CU-30: historial de acciones y auditoria visible solo al administrador.
Route::get('/dashboard/bitacora', function () {
    return view('dashboard.bitacora');
})->middleware(['auth', 'permiso:rol:administrador']);

// CU-05: pantalla para restablecer o cambiar contrasenas.
Route::get('/dashboard/password', function () {
    return view('dashboard.password');
})->middleware('auth');

// CU-06: listado y gestion administrativa de preinscripciones.
Route::get('/dashboard/preinscripciones', function () {
    return view('dashboard.preinscripciones');
})->middleware(['auth', 'permiso:consultar_postulante']);

// CU-07: revision y validacion de documentos fisicos.
Route::get('/dashboard/requisitos', function () {
    return view('dashboard.requisitos');
})->middleware(['auth', 'permiso:habilitar_postulante']);

// CU-08: consulta administrativa de pagos de matricula.
Route::get('/dashboard/pagos', function () {
    return view('dashboard.pagos');
})->middleware(['auth', 'permiso:consultar_pago,registrar_pago,validar_pago']);

// CU-09: habilitacion final despues de validar pago y documentos.
Route::get('/dashboard/habilitacion', function () {
    return view('dashboard.habilitacion');
})->middleware(['auth', 'permiso:habilitar_postulante']);

// CU-12: configuracion de fechas y estado del periodo CUP.
Route::get('/dashboard/periodo-academico', function () {
    return view('dashboard.periodo-academico');
})->middleware(['auth', 'permiso:rol:administrador']);

// CU-13: calculo, generacion y edicion de grupos por periodo.
Route::get('/dashboard/distribucion-grupos', function () {
    return view('dashboard.distribucion-grupos');
})->middleware(['auth', 'permiso:calcular_grupos']);

// CU-15 y CU-16: generacion y edicion de materias, aulas y horarios por grupo.
Route::get('/dashboard/horarios-grupos', function () {
    return view('dashboard.horarios-grupos');
})->middleware(['auth', 'permiso:asignar_horarios']);

// CU-21: asignacion de carreras y generacion de la lista oficial de admitidos.
Route::get('/dashboard/asignacion-carreras', function () {
    return view('dashboard.asignacion-carreras');
})->middleware(['auth', 'permiso:asignar_carrera']);

// Pantalla para inscribir o retirar postulantes de los grupos disponibles.
Route::get('/dashboard/postulantes-grupos', function () {
    return view('dashboard.postulantes-grupos');
})->middleware(['auth', 'permiso:asignar_grupos']);

// CU-14: CRUD de aulas y visualizacion de cupos/ocupacion.
Route::get('/dashboard/aulas', function () {
    return view('dashboard.aulas');
})->middleware(['auth', 'permiso:controlar_aulas']);

// CU-18: registro y edicion de calificaciones.
Route::get('/dashboard/calificaciones', function () {
    return view('dashboard.calificaciones');
})->middleware(['auth', 'permiso:registrar_nota,modificar_nota']);

// CU-31: configuracion 30/30/40 y recalculo de promedios.
Route::get('/dashboard/ponderaciones-notas', function () {
    return view('dashboard.ponderaciones-notas');
})->middleware(['auth', 'permiso:calcular_promedio']);

// CU-32 a CU-35: consultas dinamicas, PDF, Excel y comandos de voz con IA.
Route::get('/dashboard/reportes', function () {
    return view('dashboard.reportes');
})->middleware(['auth', 'permiso:generar_reportes']);

// Pantalla docente para registrar presente, retraso o falta.
Route::get('/dashboard/asistencias', function () {
    return view('dashboard.asistencias');
})->middleware(['auth', 'permiso:registrar_asistencia']);

// CRUD academico de carreras, cupos y materias.
Route::get('/dashboard/catalogos-academicos', function () {
    return view('dashboard.catalogos-academicos');
})->middleware(['auth', 'permiso:consultar_carrera,registrar_carrera,modificar_carrera']);

// Asigna las materias que cada docente esta profesionalmente autorizado a impartir.
Route::get('/dashboard/docentes-materias', function () {
    return view('dashboard.docentes-materias');
})->middleware(['auth', 'permiso:asignar_materias']);

// Cambia docentes de horarios y valida cruces y limites de carga.
Route::get('/dashboard/docentes-horarios', function () {
    return view('dashboard.docentes-horarios');
})->middleware(['auth', 'permiso:asignar_horarios,controlar_carga_horaria_docente']);

// Perfil de la cuenta actualmente autenticada.
Route::get('/dashboard/perfil', function () {
    return view('dashboard.perfil');
})->middleware('auth');
