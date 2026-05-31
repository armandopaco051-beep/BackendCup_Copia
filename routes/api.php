<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HabilitacionPostulanteController;
use App\Http\Controllers\PagoMatriculaController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PreinscripcionController;
use App\Http\Controllers\RequisitoPostulanteController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// CU-06: Registrar preinscripcion del bachiller para iniciar admision.
Route::post('/preinscripciones', [PreinscripcionController::class, 'store']);

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
Route::post('/postulantes/{username}/pago-matricula/intento', [PagoMatriculaController::class, 'crearIntento']);
Route::get('/postulantes/{username}/pago-matricula', [PagoMatriculaController::class, 'show']);
Route::post('/postulantes/{username}/pago-matricula/confirmar', [PagoMatriculaController::class, 'confirmar']);
Route::post('/stripe/webhook', [PagoMatriculaController::class, 'webhook']);

// CU-09: Habilitar postulante tras validar requisitos y pago.
Route::get('/postulantes/{username}/habilitacion', [HabilitacionPostulanteController::class, 'show']);
Route::post('/postulantes/{username}/habilitacion', [HabilitacionPostulanteController::class, 'store']);
