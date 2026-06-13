<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CalificacionController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocenteMateriaController;
use App\Http\Controllers\DistribucionGrupoController;
use App\Http\Controllers\HabilitacionPostulanteController;
use App\Http\Controllers\HorarioGrupoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PagoMatriculaController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PeriodoAcademicoController;
use App\Http\Controllers\PreinscripcionController;
use App\Http\Controllers\RequisitoPostulanteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardController::class, 'index']);

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/bitacora', [BitacoraController::class, 'index']);
    Route::post('/bitacora/movimiento', [BitacoraController::class, 'movimiento']);
});

// Validar cupos por aula segun ocupacion registrada en horarios.
Route::get('/aulas/cupos', [AulaController::class, 'cupos']);
Route::apiResource('aulas', AulaController::class)->parameters([
    'aulas' => 'aula',
]);

// CU-13: Calcular distribucion de grupos.
Route::get('/distribucion-grupos/calcular', [DistribucionGrupoController::class, 'calcular']);
Route::post('/distribucion-grupos/generar', [DistribucionGrupoController::class, 'generar']);

Route::apiResource('periodos-academicos', PeriodoAcademicoController::class)->parameters([
    'periodos-academicos' => 'periodoAcademico',
]);

// Catalogos academicos para alimentar preinscripciones y calificaciones.
Route::get('/carreras-habilitadas', [CarreraController::class, 'habilitadas']);
Route::apiResource('carreras', CarreraController::class)->parameters([
    'carreras' => 'carrera',
]);
Route::apiResource('materias', MateriaController::class)->parameters([
    'materias' => 'materia',
]);
Route::get('/docentes-materias', [DocenteMateriaController::class, 'index']);
Route::get('/docentes/{username}/materias', [DocenteMateriaController::class, 'show']);
Route::put('/docentes/{username}/materias', [DocenteMateriaController::class, 'sync']);
Route::delete('/docentes/{username}/materias/{materia}', [DocenteMateriaController::class, 'destroy']);

// Generar horarios por grupo con rotacion circular de materias.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/horarios-grupos/opciones', [HorarioGrupoController::class, 'opciones']);
    Route::get('/horarios-grupos', [HorarioGrupoController::class, 'index']);
    Route::post('/horarios-grupos/generar', [HorarioGrupoController::class, 'generar']);
    Route::post('/horarios-grupos/confirmar', [HorarioGrupoController::class, 'confirmar']);
    Route::delete('/horarios-grupos/{horarioGrupo}', [HorarioGrupoController::class, 'destroy']);
});

// Registrar calificaciones y calcular promedio automaticamente.
// Requiere sesion para que el docente solo vea y califique sus grupos asignados.
Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/calificaciones/opciones', [CalificacionController::class, 'opciones']);
    Route::apiResource('calificaciones', CalificacionController::class)->parameters([
        'calificaciones' => 'calificacion',
    ]);
    Route::get('/asistencias/opciones', [AsistenciaController::class, 'opciones']);
    Route::get('/asistencias', [AsistenciaController::class, 'index']);
    Route::post('/asistencias/lote', [AsistenciaController::class, 'storeLote']);
    Route::delete('/asistencias/{asistencia}', [AsistenciaController::class, 'destroy']);
});

// CU-06: Registrar preinscripcion del bachiller para iniciar admision.
Route::get('/preinscripciones', [PreinscripcionController::class, 'index']);
Route::get('/preinscripciones/consulta', [PreinscripcionController::class, 'consultarPorCi']);
Route::get('/preinscripciones/{username}/formulario', [PreinscripcionController::class, 'formularioPdf']);
Route::post('/preinscripciones', [PreinscripcionController::class, 'store']);
Route::put('/preinscripciones/{username}', [PreinscripcionController::class, 'updatePublic']);
Route::patch('/preinscripciones/{username}', [PreinscripcionController::class, 'updatePublic']);

Route::get('/usuarios', [UsuarioController::class, 'index']);
Route::post('/usuarios', [UsuarioController::class, 'store']);
Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show']);
Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update']);
Route::patch('/usuarios/{usuario}', [UsuarioController::class, 'update']);
Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy']);
Route::patch('/usuarios/{usuario}/rol', [UsuarioController::class, 'asignarRol']);
Route::post('/usuarios/{usuario}/restablecer-password', [UsuarioController::class, 'restablecerPassword']);

Route::apiResource('roles', RolController::class)->parameters([
    'roles' => 'rol',
]);
Route::put('/roles/{rol}/permisos', [RolController::class, 'sincronizarPermisos']);
Route::apiResource('permisos', PermisoController::class);

Route::middleware('web')->prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware('auth')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// CU-07: Validar requisitos fisicos.
// CU-07: Validar requisitos fisicos del postulante.
Route::post('/postulantes/{username}/requisitos', [RequisitoPostulanteController::class, 'store']);
Route::get('/postulantes/{username}/requisitos', [RequisitoPostulanteController::class, 'show']);   

// CU-08: Registrar pago de matricula mediante Stripe.
Route::get('/pago-matricula/configuracion', [PagoMatriculaController::class, 'configuracion']);
Route::post('/postulantes/{username}/pago-matricula/intento', [PagoMatriculaController::class, 'crearIntento']);
Route::get('/postulantes/{username}/pago-matricula', [PagoMatriculaController::class, 'show']);
Route::post('/postulantes/{username}/pago-matricula/confirmar', [PagoMatriculaController::class, 'confirmar']);
Route::post('/stripe/webhook', [PagoMatriculaController::class, 'webhook']);

// CU-09: Habilitar postulante tras validar requisitos y pago.
Route::get('/habilitaciones', [HabilitacionPostulanteController::class, 'index']);
Route::get('/postulantes/{username}/habilitacion', [HabilitacionPostulanteController::class, 'show']);
Route::post('/postulantes/{username}/habilitacion', [HabilitacionPostulanteController::class, 'store']);
