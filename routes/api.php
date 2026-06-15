<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AsignacionCarreraController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\CalificacionController;
use App\Http\Controllers\CarreraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocentePanelController;
use App\Http\Controllers\DocenteHorarioController;
use App\Http\Controllers\DocenteMateriaController;
use App\Http\Controllers\DistribucionGrupoController;
use App\Http\Controllers\HabilitacionPostulanteController;
use App\Http\Controllers\HorarioGrupoController;
use App\Http\Controllers\MateriaController;
use App\Http\Controllers\PagoMatriculaController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\PeriodoAcademicoController;
use App\Http\Controllers\PostulanteGrupoController;
use App\Http\Controllers\PostulantePanelController;
use App\Http\Controllers\PonderacionNotaController;
use App\Http\Controllers\PreinscripcionController;
use App\Http\Controllers\RequisitoPostulanteController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReporteVozController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard y auditoria
|--------------------------------------------------------------------------
*/

// GET /api/dashboard: devuelve indicadores reales para el dashboard administrativo.
Route::middleware(['web', 'auth', 'permiso:rol:administrador'])
    ->get('/dashboard', [DashboardController::class, 'index']);

Route::middleware(['web', 'auth', 'permiso:rol:administrador'])->group(function (): void {
    // GET /api/bitacora: lista y filtra los movimientos registrados por los usuarios.
    Route::get('/bitacora', [BitacoraController::class, 'index']);
});

// POST /api/bitacora/movimiento: registra una accion realizada desde un modulo del frontend.
Route::middleware(['web', 'auth'])
    ->post('/bitacora/movimiento', [BitacoraController::class, 'movimiento']);

/*
|--------------------------------------------------------------------------
| CU-14 Gestionar aulas y validar cupos
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:controlar_aulas'])->group(function (): void {
    // GET /api/aulas/cupos: calcula capacidad, ocupacion y disponibilidad de cada aula.
    Route::get('/aulas/cupos', [AulaController::class, 'cupos']);

    /*
     * CRUD de aulas generado por apiResource:
     * GET    /api/aulas         -> listar aulas.
     * POST   /api/aulas         -> crear un aula.
     * GET    /api/aulas/{aula}  -> consultar un aula.
     * PUT    /api/aulas/{aula}  -> actualizar un aula.
     * DELETE /api/aulas/{aula}  -> eliminar un aula.
     */
    Route::apiResource('aulas', AulaController::class)->parameters([
        'aulas' => 'aula',
    ]);
});

/*
|--------------------------------------------------------------------------
| CU-13 Calcular distribucion de grupos
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:calcular_grupos'])->group(function (): void {
    // GET /api/distribucion-grupos: consulta los grupos ya guardados de un periodo.
    Route::get('/distribucion-grupos', [DistribucionGrupoController::class, 'index']);

    // GET /api/distribucion-grupos/calcular: calcula una vista previa sin guardar cambios.
    Route::get('/distribucion-grupos/calcular', [DistribucionGrupoController::class, 'calcular']);

    // POST /api/distribucion-grupos/generar: crea solamente los grupos que faltan por capacidad.
    Route::post('/distribucion-grupos/generar', [DistribucionGrupoController::class, 'generar']);

    // PUT /api/distribucion-grupos/{grupo}: modifica cupo, turno, descripcion o estado de un grupo.
    Route::put('/distribucion-grupos/{grupo}', [DistribucionGrupoController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| CU-12 Configurar periodo academico
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:rol:administrador'])->group(function (): void {
    /*
     * CRUD de periodos generado por apiResource:
     * GET    /api/periodos-academicos                    -> listar periodos.
     * POST   /api/periodos-academicos                    -> crear un periodo.
     * GET    /api/periodos-academicos/{periodoAcademico} -> consultar un periodo.
     * PUT    /api/periodos-academicos/{periodoAcademico} -> actualizar fechas y estado.
     * DELETE /api/periodos-academicos/{periodoAcademico} -> eliminar un periodo.
     */
    Route::apiResource('periodos-academicos', PeriodoAcademicoController::class)->parameters([
        'periodos-academicos' => 'periodoAcademico',
    ]);
});

/*
|--------------------------------------------------------------------------
| Catalogos academicos: carreras y materias
|--------------------------------------------------------------------------
*/

// GET /api/carreras-habilitadas: endpoint publico usado por la preinscripcion.
Route::get('/carreras-habilitadas', [CarreraController::class, 'habilitadas']);

Route::middleware(['web', 'auth', 'permiso:consultar_carrera,registrar_carrera,modificar_carrera'])->group(function (): void {
    /*
     * CRUD de carreras:
     * GET/POST /api/carreras y GET/PUT/DELETE /api/carreras/{carrera}.
     * Gestiona nombre, estado y cupo maximo de cada carrera.
     */
    Route::apiResource('carreras', CarreraController::class)->parameters([
        'carreras' => 'carrera',
    ]);

    /*
     * CRUD de materias:
     * GET/POST /api/materias y GET/PUT/DELETE /api/materias/{materia}.
     * Mantiene las materias que se usan en horarios, notas y docentes.
     */
    Route::apiResource('materias', MateriaController::class)->parameters([
        'materias' => 'materia',
    ]);
});

/*
|--------------------------------------------------------------------------
| Asignar materias y carga horaria docente
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:asignar_materias'])->group(function (): void {
    // GET /api/docentes-materias: lista docentes con sus materias autorizadas.
    Route::get('/docentes-materias', [DocenteMateriaController::class, 'index']);

    // GET /api/docentes/{username}/materias: consulta las materias de un docente.
    Route::get('/docentes/{username}/materias', [DocenteMateriaController::class, 'show']);

    // PUT /api/docentes/{username}/materias: sincroniza todas las materias autorizadas.
    Route::put('/docentes/{username}/materias', [DocenteMateriaController::class, 'sync']);

    // DELETE /api/docentes/{username}/materias/{materia}: retira una materia al docente.
    Route::delete('/docentes/{username}/materias/{materia}', [DocenteMateriaController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'permiso:asignar_horarios,controlar_carga_horaria_docente'])->group(function (): void {
    // GET /api/docentes-horarios: muestra horarios y carga actual por docente.
    Route::get('/docentes-horarios', [DocenteHorarioController::class, 'index']);

    // PUT /api/docentes-horarios/{horarioGrupo}: cambia el docente de un bloque y valida cruces/carga.
    Route::put('/docentes-horarios/{horarioGrupo}', [DocenteHorarioController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| CU-15 y CU-16 Materias, aulas, docentes y horarios por grupo
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:asignar_horarios'])->group(function (): void {
    // GET /api/horarios-grupos/opciones: carga periodos, grupos, materias, aulas, dias y docentes validos.
    Route::get('/horarios-grupos/opciones', [HorarioGrupoController::class, 'opciones']);

    // GET /api/horarios-grupos: lista horarios existentes con filtros.
    Route::get('/horarios-grupos', [HorarioGrupoController::class, 'index']);

    // POST /api/horarios-grupos: crea manualmente un bloque de horario.
    Route::post('/horarios-grupos', [HorarioGrupoController::class, 'store']);

    // POST /api/horarios-grupos/generar: propone horarios rotativos y valida cruces.
    Route::post('/horarios-grupos/generar', [HorarioGrupoController::class, 'generar']);

    // POST /api/horarios-grupos/confirmar: confirma y guarda la propuesta generada.
    Route::post('/horarios-grupos/confirmar', [HorarioGrupoController::class, 'confirmar']);

    // PUT /api/horarios-grupos/{horarioGrupo}: edita manualmente un bloque existente.
    Route::put('/horarios-grupos/{horarioGrupo}', [HorarioGrupoController::class, 'update']);

    // DELETE /api/horarios-grupos/{horarioGrupo}: elimina un bloque de horario.
    Route::delete('/horarios-grupos/{horarioGrupo}', [HorarioGrupoController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Asignar estudiantes a grupos
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:asignar_grupos'])->group(function (): void {
    // GET /api/postulantes-grupos: lista inscripciones y grupos disponibles para administracion.
    Route::get('/postulantes-grupos', [PostulanteGrupoController::class, 'index']);

    // POST /api/postulantes-grupos: inscribe un postulante en un grupo desde administracion.
    Route::post('/postulantes-grupos', [PostulanteGrupoController::class, 'store']);

    // DELETE /api/postulantes-grupos/{username}/{grupo}: retira al postulante del grupo.
    Route::delete('/postulantes-grupos/{username}/{grupo}', [PostulanteGrupoController::class, 'destroy']);
});

Route::middleware(['web', 'auth', 'permiso:rol:postulante'])->group(function (): void {
    // GET /api/postulante/mi-panel: consolida datos, requisitos, pago, grupo, horario, notas y carrera.
    Route::get('/postulante/mi-panel', [PostulantePanelController::class, 'index']);

    // GET /api/postulante/grupos-disponibles: muestra grupos activos con cupos para el postulante.
    Route::get('/postulante/grupos-disponibles', [PostulanteGrupoController::class, 'disponiblesPostulante']);

    // POST /api/postulante/grupo: permite que el postulante seleccione un grupo disponible.
    Route::post('/postulante/grupo', [PostulanteGrupoController::class, 'inscribirPostulante']);
});

Route::middleware(['web', 'auth', 'permiso:rol:docente'])->group(function (): void {
    // GET /api/docente/mi-panel: devuelve perfil, carga y horario del docente autenticado.
    Route::get('/docente/mi-panel', [DocentePanelController::class, 'index']);
});

/*
|--------------------------------------------------------------------------
| CU-21 Asignar carrera y generar lista de admitidos
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:asignar_carrera'])->group(function (): void {
    // GET /api/asignacion-carreras: consulta resultados, cupos, lista de espera y reprobados.
    Route::get('/asignacion-carreras', [AsignacionCarreraController::class, 'index']);

    // POST /api/asignacion-carreras/generar: ordena por promedio y asigna primera/segunda opcion segun cupo.
    Route::post('/asignacion-carreras/generar', [AsignacionCarreraController::class, 'generar']);
});

/*
|--------------------------------------------------------------------------
| CU-18 Registrar y editar calificaciones
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:registrar_nota,modificar_nota'])->group(function (): void {
    // GET /api/calificaciones/opciones: devuelve grupos, materias y alumnos permitidos para el usuario.
    Route::get('/calificaciones/opciones', [CalificacionController::class, 'opciones']);

    /*
     * CRUD de calificaciones:
     * GET    /api/calificaciones                -> listar notas permitidas.
     * POST   /api/calificaciones                -> registrar notas y calcular promedio.
     * GET    /api/calificaciones/{calificacion} -> consultar una calificacion.
     * PUT    /api/calificaciones/{calificacion} -> editar notas y recalcular promedio.
     * DELETE /api/calificaciones/{calificacion} -> eliminar una calificacion.
     */
    Route::apiResource('calificaciones', CalificacionController::class)->parameters([
        'calificaciones' => 'calificacion',
    ]);
});

/*
|--------------------------------------------------------------------------
| CU-31 Configurar ponderaciones de notas
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:calcular_promedio'])->group(function (): void {
    // GET /api/ponderaciones-notas: consulta la ponderacion activa y su historial.
    Route::get('/ponderaciones-notas', [PonderacionNotaController::class, 'index']);

    // POST /api/ponderaciones-notas: guarda porcentajes que deben sumar 100%.
    Route::post('/ponderaciones-notas', [PonderacionNotaController::class, 'store']);

    // POST /api/ponderaciones-notas/recalcular: recalcula todos los promedios con la ponderacion activa.
    Route::post('/ponderaciones-notas/recalcular', [PonderacionNotaController::class, 'recalcular']);
});

/*
|--------------------------------------------------------------------------
| CU-32, CU-33, CU-34 y CU-35 Reportes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:generar_reportes'])->group(function (): void {
    // GET /api/reportes/opciones: carga tipos de reporte y catalogos disponibles para filtros.
    Route::get('/reportes/opciones', [ReporteController::class, 'opciones']);

    // GET /api/reportes: genera una consulta dinamica con datos reales y filtros.
    Route::get('/reportes', [ReporteController::class, 'index']);

    // POST /api/reportes/voz: Gemini interpreta texto/voz y lo convierte en filtros seguros.
    Route::post('/reportes/voz', [ReporteVozController::class, 'procesar']);
});

// GET /api/reportes/pdf: exporta en PDF el mismo reporte y filtros mostrados en pantalla.
Route::middleware(['web', 'auth', 'permiso:exportar_pdf'])
    ->get('/reportes/pdf', [ReporteController::class, 'pdf']);

// GET /api/reportes/excel: exporta en Excel el mismo reporte y filtros mostrados en pantalla.
Route::middleware(['web', 'auth', 'permiso:exportar_excel'])
    ->get('/reportes/excel', [ReporteController::class, 'excel']);

/*
|--------------------------------------------------------------------------
| Registrar asistencia
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:registrar_asistencia'])->group(function (): void {
    // GET /api/asistencias/opciones: devuelve grupos, materias y alumnos permitidos para el docente.
    Route::get('/asistencias/opciones', [AsistenciaController::class, 'opciones']);

    // GET /api/asistencias: lista asistencias registradas con filtros.
    Route::get('/asistencias', [AsistenciaController::class, 'index']);

    // POST /api/asistencias/lote: registra presente, retraso o falta para todo un grupo.
    Route::post('/asistencias/lote', [AsistenciaController::class, 'storeLote']);

    // DELETE /api/asistencias/{asistencia}: elimina un registro de asistencia.
    Route::delete('/asistencias/{asistencia}', [AsistenciaController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| CU-06 Registrar preinscripcion
|--------------------------------------------------------------------------
*/

// GET /api/preinscripciones: lista preinscripciones para usuarios con permiso administrativo.
Route::middleware(['web', 'auth', 'permiso:consultar_postulante'])
    ->get('/preinscripciones', [PreinscripcionController::class, 'index']);

// GET /api/preinscripciones/consulta: busca publicamente una preinscripcion mediante CI.
Route::get('/preinscripciones/consulta', [PreinscripcionController::class, 'consultarPorCi']);

// GET /api/preinscripciones/{username}/formulario: descarga el formulario consolidado en PDF.
Route::get('/preinscripciones/{username}/formulario', [PreinscripcionController::class, 'formularioPdf']);

// POST /api/preinscripciones: valida datos, crea el tramite temporal e inicia el flujo de pago.
Route::post('/preinscripciones', [PreinscripcionController::class, 'store']);

// PUT/PATCH /api/preinscripciones/{username}: actualiza los datos permitidos de la preinscripcion.
Route::put('/preinscripciones/{username}', [PreinscripcionController::class, 'updatePublic']);
Route::patch('/preinscripciones/{username}', [PreinscripcionController::class, 'updatePublic']);

/*
|--------------------------------------------------------------------------
| CU-03 Gestionar usuarios y CU-05 Restablecer password
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])->group(function (): void {
    // GET /api/usuarios: lista cuentas y perfiles; requiere listar_usuario.
    Route::get('/usuarios', [UsuarioController::class, 'index'])
        ->middleware('permiso:listar_usuario');

    // POST /api/usuarios: crea una cuenta administrativa o docente con su perfil.
    Route::post('/usuarios', [UsuarioController::class, 'store'])
        ->middleware('permiso:crear_usuario');

    // GET /api/usuarios/{usuario}: devuelve todos los datos editables de una cuenta.
    Route::get('/usuarios/{usuario}', [UsuarioController::class, 'show'])
        ->middleware('permiso:listar_usuario');

    // PUT/PATCH /api/usuarios/{usuario}: actualiza cuenta y perfil segun el tipo de usuario.
    Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])
        ->middleware('permiso:modificar_usuario');
    Route::patch('/usuarios/{usuario}', [UsuarioController::class, 'update'])
        ->middleware('permiso:modificar_usuario');

    // DELETE /api/usuarios/{usuario}: elimina o desactiva una cuenta segun sus dependencias.
    Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])
        ->middleware('permiso:eliminar_usuario');

    // PATCH /api/usuarios/{usuario}/rol: cambia el rol asociado a una cuenta.
    Route::patch('/usuarios/{usuario}/rol', [UsuarioController::class, 'asignarRol'])
        ->middleware('permiso:asignar_roles');

    // POST /api/usuarios/{usuario}/restablecer-password: asigna una nueva contrasena segura.
    Route::post('/usuarios/{usuario}/restablecer-password', [UsuarioController::class, 'restablecerPassword'])
        ->middleware('permiso:modificar_usuario');
});

/*
|--------------------------------------------------------------------------
| CU-04 Gestionar roles y permisos
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:modificar_roles,asignar_roles'])->group(function (): void {
    /*
     * CRUD de roles:
     * GET/POST /api/roles y GET/PUT/DELETE /api/roles/{rol}.
     */
    Route::apiResource('roles', RolController::class)->parameters([
        'roles' => 'rol',
    ]);

    // PUT /api/roles/{rol}/permisos: reemplaza la matriz de permisos asignada al rol.
    Route::put('/roles/{rol}/permisos', [RolController::class, 'sincronizarPermisos']);

    /*
     * CRUD de permisos:
     * GET/POST /api/permisos y GET/PUT/DELETE /api/permisos/{permiso}.
     */
    Route::apiResource('permisos', PermisoController::class);
});

/*
|--------------------------------------------------------------------------
| CU-01 Iniciar sesion y CU-02 Cerrar sesion
|--------------------------------------------------------------------------
*/

Route::middleware('web')->prefix('auth')->group(function (): void {
    // POST /api/auth/login: valida username/password, regenera la sesion y devuelve usuario/rol.
    Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

    Route::middleware('auth')->group(function (): void {
        // GET /api/auth/me: devuelve la cuenta autenticada, su perfil, rol y permisos.
        Route::get('/me', [AuthController::class, 'me']);

        // POST /api/auth/logout: invalida la sesion y regenera el token CSRF.
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| CU-07 Validar requisitos fisicos
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:habilitar_postulante'])->group(function (): void {
    // POST /api/postulantes/{username}/requisitos: aprueba/rechaza documentos y registra auditoria.
    Route::post('/postulantes/{username}/requisitos', [RequisitoPostulanteController::class, 'store']);

    // GET /api/postulantes/{username}/requisitos: consulta documentos, estados y observaciones.
    Route::get('/postulantes/{username}/requisitos', [RequisitoPostulanteController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| CU-08 Registrar pago de matricula con Stripe
|--------------------------------------------------------------------------
*/

// GET /api/pago-matricula/configuracion: devuelve clave publica, moneda y monto para Stripe Elements.
Route::get('/pago-matricula/configuracion', [PagoMatriculaController::class, 'configuracion']);

// POST /api/postulantes/{username}/pago-matricula/intento: crea un PaymentIntent de 700 Bs.
Route::post('/postulantes/{username}/pago-matricula/intento', [PagoMatriculaController::class, 'crearIntento']);

// GET /api/postulantes/{username}/pago-matricula: consulta estado y comprobante del pago.
Route::get('/postulantes/{username}/pago-matricula', [PagoMatriculaController::class, 'show']);

// POST /api/postulantes/{username}/pago-matricula/confirmar: confirma localmente el resultado validado por Stripe.
Route::post('/postulantes/{username}/pago-matricula/confirmar', [PagoMatriculaController::class, 'confirmar']);

// POST /api/stripe/webhook: recibe eventos firmados de Stripe y actualiza el pago de forma confiable.
Route::post('/stripe/webhook', [PagoMatriculaController::class, 'webhook']);

/*
|--------------------------------------------------------------------------
| CU-09 Habilitar postulante
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'permiso:habilitar_postulante'])->group(function (): void {
    // GET /api/habilitaciones: lista candidatos y muestra si cumplen pago y requisitos.
    Route::get('/habilitaciones', [HabilitacionPostulanteController::class, 'index']);

    // GET /api/postulantes/{username}/habilitacion: consulta el diagnostico individual.
    Route::get('/postulantes/{username}/habilitacion', [HabilitacionPostulanteController::class, 'show']);

    // POST /api/postulantes/{username}/habilitacion: habilita, crea credenciales y envia correo.
    Route::post('/postulantes/{username}/habilitacion', [HabilitacionPostulanteController::class, 'store']);
});
