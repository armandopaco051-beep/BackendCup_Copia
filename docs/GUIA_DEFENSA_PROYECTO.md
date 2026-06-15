# Guia de defensa tecnica - Sistema CUP UAGRM

## 1. Explicacion corta de la arquitectura

El proyecto utiliza una arquitectura web modular basada en Laravel:

```text
Usuario
  -> Vista Blade (HTML)
  -> Modulo JavaScript
  -> Endpoint REST /api
  -> Middleware de autenticacion y permisos
  -> Controller
  -> Model / Service
  -> PostgreSQL por esquemas
  -> Respuesta JSON
  -> JavaScript actualiza la pantalla
```

Respuesta recomendada:

> El frontend esta construido con Blade, CSS y JavaScript modular. El frontend
> no accede directamente a PostgreSQL: consume endpoints REST. Laravel valida
> sesion, rol, permisos y reglas del negocio antes de utilizar los modelos.
> PostgreSQL esta organizado mediante los esquemas seguridad, academico y pago.

## 2. Estructura del proyecto

| Carpeta | Responsabilidad |
|---|---|
| `routes/web.php` | Rutas que muestran pantallas Blade y protegen el acceso visual. |
| `routes/api.php` | Contrato REST entre frontend y backend. Cada endpoint esta comentado. |
| `app/Http/Controllers` | Recibe solicitudes, valida datos y coordina cada caso de uso. |
| `app/Http/Middleware` | Verifica autenticacion, rol y permisos. |
| `app/Models` | Representa tablas de PostgreSQL y sus esquemas. |
| `app/Services` | Logica compartida o compleja, como reportes, Excel y Gemini. |
| `resources/views` | HTML generado por Blade. |
| `resources/js/modules` | Logica frontend separada por modulo/caso de uso. |
| `resources/css/app.css` | Estilos generales y responsivos. |
| `database/migrations` | Cambios versionados de la estructura de base de datos. |
| `tests` | Pruebas automatizadas. |

## 3. Como demostrar la trazabilidad

Ejemplo: registrar una calificacion.

```text
Pantalla:
resources/views/dashboard/calificaciones.blade.php

Frontend:
resources/js/modules/calificaciones.js

Endpoint:
POST /api/calificaciones

Backend:
app/Http/Controllers/CalificacionController.php

Modelo:
app/Models/ActaNota.php

Tabla:
academico.acta_nota
```

La misma estructura se repite para usuarios, requisitos, pagos, grupos,
horarios, asistencia y reportes.

## 4. Seguridad por roles y permisos

Existen tres vistas principales:

- Administrador: dashboard, usuarios, roles, admision, academico y reportes.
- Docente: panel docente, calificaciones, asistencia y perfil.
- Postulante: preinscripcion, requisitos, pago, grupo, horario, notas y carrera.

La seguridad se aplica en dos niveles:

1. `routes/web.php` evita que el usuario abra una pantalla no autorizada.
2. `routes/api.php` evita que invoque el endpoint aunque escriba la URL manualmente.

Los middleware importantes son:

- `auth`: exige una sesion activa.
- `guest`: permite login solamente sin sesion activa.
- `permiso:...`: exige uno de los permisos indicados.
- `permiso:rol:administrador`: exige rol administrativo.
- `permiso:rol:postulante`: exige rol postulante.

Respuesta recomendada:

> Ocultar botones no es la seguridad principal. Aunque un estudiante intente
> llamar un endpoint administrativo desde Postman, el middleware del backend
> responde sin autorizacion.

## 5. Casos de uso implementados

| Caso de uso | Frontend | Endpoint principal | Backend / tablas |
|---|---|---|---|
| CU-01 Iniciar sesion | `auth.js`, `auth/login.blade.php` | `POST /api/auth/login` | `AuthController`, `seguridad.usuario` |
| CU-02 Cerrar sesion | `session.js` | `POST /api/auth/logout` | `AuthController`, sesion Laravel |
| CU-03 Gestionar usuarios | `usuarios.js` | CRUD `/api/usuarios` | `UsuarioController`, usuario y perfiles |
| CU-04 Roles y permisos | `roles-permisos.js` | CRUD roles/permisos | `RolController`, `PermisoController` |
| CU-05 Restablecer password | `usuarios.js` | `POST /api/usuarios/{usuario}/restablecer-password` | Hash seguro en usuario |
| CU-06 Registrar preinscripcion | `preinscripciones.js` | `POST /api/preinscripciones` | `PreinscripcionController`, postulante y carreras |
| CU-07 Validar requisitos | `requisitos.js` | `POST /api/postulantes/{username}/requisitos` | `RequisitoPostulanteController` |
| CU-08 Pago de matricula | `pagos.js` y registro publico | PaymentIntent, confirmar, webhook | `PagoMatriculaController`, Stripe, `pago.pago` |
| CU-09 Habilitar postulante | `habilitacion.js` | `POST /api/postulantes/{username}/habilitacion` | valida pago + requisitos, crea cuenta y correo |
| CU-10 Formulario consolidado | preinscripcion publica | `GET /api/preinscripciones/{username}/formulario` | PDF con datos reales |
| CU-12 Periodo academico | `periodo-academico.js` | CRUD periodos | controla ventana del proceso |
| CU-13 Distribucion de grupos | `distribucion-grupos.js` | calcular/generar/editar | maximo configurable, conserva grupos existentes |
| CU-14 Cupos por aula | `aulas.js` | `GET /api/aulas/cupos` | capacidad, ocupacion y disponibilidad |
| CU-15 Infraestructura y horarios | `horarios-grupos.js` | CRUD horarios y generar | aula, materia, grupo, dia y bloque |
| CU-16 Carga docente | `docentes-horarios.js` | `PUT /api/docentes-horarios/{id}` | valida materia, cruces y limite de carga |
| Asignar materias a docente | `docentes-materias.js` | `/api/docentes/{username}/materias` | perfil profesional y materias autorizadas |
| Asignar estudiantes a grupos | `postulantes-grupos.js` | `/api/postulantes-grupos` | valida estado, periodo y cupo |
| CU-18 Registrar calificaciones | `calificaciones.js` | CRUD `/api/calificaciones` | docente limitado a sus grupos/materias |
| Registrar asistencia | `asistencias.js` | `POST /api/asistencias/lote` | presente, retraso o falta |
| CU-21 Lista de admitidos | `asignacion-carreras.js` | `POST /api/asignacion-carreras/generar` | merito, cupos, primera/segunda opcion |
| CU-24 Bitacora | `bitacora.js` | `/api/bitacora` | registra usuario, accion, fecha y datos |
| CU-31 Ponderaciones | `ponderaciones-notas.js` | `/api/ponderaciones-notas` | Nota 1 30%, Nota 2 30%, Nota 3 40% |
| CU-32 Reporte PDF | `reportes.js` | `GET /api/reportes/pdf` | DomPDF |
| CU-33 Reporte Excel | `reportes.js` | `GET /api/reportes/excel` | PhpSpreadsheet |
| CU-34 Reportes dinamicos | `reportes.js` | `GET /api/reportes` | `ReporteService` y filtros |
| CU-35 Reporte por voz/IA | `reportes.js` | `POST /api/reportes/voz` | Gemini interpreta; Laravel consulta |

## 6. Diferencia entre aprobado, admitido y habilitado

- Habilitado: cumplio pago y requisitos, por lo que puede continuar el CUP.
- Aprobado: su promedio final es mayor o igual a 60.
- Admitido: aprobo y obtuvo un cupo en una carrera.
- Lista de espera: aprobo, pero sus opciones no tienen cupo.
- Reprobado: promedio final menor a 60.

No se debe usar "aprobado" y "admitido" como sinonimos.

## 7. Calculo automatico de notas

La ponderacion activa es:

```text
Promedio = Nota 1 * 0.30 + Nota 2 * 0.30 + Nota 3 * 0.40
```

Archivos para mostrar:

- `app/Http/Controllers/PonderacionNotaController.php`
- `app/Http/Controllers/CalificacionController.php`
- `app/Models/PonderacionNota.php`
- tabla `academico.acta_nota`

La nota minima de aprobacion esta unificada en 60.

## 8. Seleccion de admitidos por cupo

Proceso:

1. Se calculan los promedios finales.
2. Se descartan como reprobados los promedios menores a 60.
3. Se ordena de mayor a menor por promedio con decimales.
4. El desempate usa Nota 3, Nota 2, Nota 1 y finalmente el folio.
5. Se intenta asignar la primera opcion.
6. Si no existe cupo, se intenta la segunda opcion.
7. Si ambas estan llenas, queda en lista de espera.
8. El resultado se guarda en `academico.asignacion_carrera`.
9. El reporte "Lista oficial de admitidos" lee esa tabla.

Archivo para mostrar:

- `app/Http/Controllers/AsignacionCarreraController.php`

## 9. Distribucion automatica de grupos

Regla:

```text
grupos necesarios = techo(postulantes sin cupo / cupo por grupo)
```

El sistema:

- consulta grupos guardados del periodo;
- suma su capacidad activa;
- calcula cuantos postulantes todavia no tienen cupo;
- propone solamente grupos faltantes;
- genera codigos `Grupo-G01`, `Grupo-G02`, etc.;
- permite editar cupo, turno, descripcion y estado;
- impide bajar el cupo por debajo de los inscritos.

Archivo:

- `app/Http/Controllers/DistribucionGrupoController.php`

## 10. Reglas del docente

Antes de asignar un docente se valida:

- perfil profesional y documentacion requerida;
- materia autorizada para impartir;
- que no tenga otro bloque en el mismo horario;
- limite maximo de grupos;
- limite maximo de horas semanales.

En calificaciones y asistencia:

- el administrador puede consultar todos los grupos;
- el docente solamente recibe sus grupos y materias asignadas;
- el backend vuelve a validar la autorizacion antes de guardar.

Archivos:

- `DocenteMateriaController.php`
- `DocenteHorarioController.php`
- `HorarioGrupoController.php`
- `CalificacionController.php`
- `AsistenciaController.php`

## 11. Flujo de preinscripcion y pago

```text
Completar formulario
  -> validar CI/correo y evitar duplicados confirmados
  -> crear tramite temporal
  -> crear PaymentIntent de Stripe
  -> pagar con tarjeta
  -> webhook confirma el pago
  -> conservar preinscripcion valida
  -> descargar formulario
  -> validar documentos fisicos
  -> habilitar postulante
  -> generar usuario y password temporal
  -> enviar credenciales por correo
```

Si el pago no se confirma, el tramite no debe considerarse una preinscripcion
finalizada.

## 12. Reportes

Reportes disponibles:

- lista general de postulantes;
- postulantes aprobados y sus promedios;
- postulantes reprobados y sus promedios;
- lista oficial de admitidos;
- estadisticas por materia;
- grupos habilitados;
- docentes por grupo;
- rendimiento por grupo;
- pagos;
- detalle de calificaciones.

Los filtros disponibles incluyen periodo, carrera, grupo, estado, fechas y
busqueda. La consulta de pantalla, PDF y Excel utiliza el mismo `ReporteService`,
por lo que no existen calculos distintos entre formatos.

Gemini no consulta directamente la base de datos. Solamente transforma una
orden de voz/texto en filtros permitidos. Laravel valida esos filtros y ejecuta
la consulta.

## 13. Demostracion recomendada

### Demostracion 1: permisos

1. Iniciar sesion como postulante.
2. Mostrar que no aparecen Usuarios, Roles ni Reportes.
3. Intentar abrir `/dashboard/usuarios`.
4. Explicar que la ruta web y el endpoint API estan protegidos.
5. Iniciar sesion como administrador y mostrar las opciones.

### Demostracion 2: camino del dato

1. Abrir preinscripcion.
2. Registrar un postulante.
3. Mostrar la llamada `POST /api/preinscripciones`.
4. Abrir `PreinscripcionController`.
5. Mostrar modelos `Postulante` y `PostulanteCarrera`.
6. Consultar las filas en PostgreSQL.
7. Mostrar la accion registrada en bitacora.

### Demostracion 3: reglas docentes

1. Intentar asignar una materia no autorizada.
2. Intentar crear un cruce de horario.
3. Mostrar el mensaje de validacion.
4. Mostrar el metodo del controlador que impide guardar.

### Demostracion 4: calculos

1. Mostrar ponderacion 30/30/40.
2. Registrar tres notas y comprobar el promedio.
3. Mostrar distribucion de grupos y la formula de capacidad.
4. Mostrar la lista de admitidos por promedio y cupo.

### Demostracion 5: reportes

1. Seleccionar "Postulantes aprobados y promedios".
2. Mostrar Nota 1, Nota 2, Nota 3 y promedio final.
3. Descargar PDF.
4. Descargar Excel.
5. Solicitar por voz: "Genera un PDF de postulantes aprobados".

## 14. Preguntas probables

**Por que PostgreSQL usa esquemas?**

Para separar responsabilidades: `seguridad` contiene usuarios, roles y permisos;
`academico` contiene el proceso academico; `pago` contiene transacciones.

**Por que hay controllers y services?**

El controller maneja la solicitud HTTP. Los services encapsulan procesos
reutilizables o extensos, como reportes, Excel y Gemini.

**Como evita SQL Injection?**

Laravel usa Eloquent, Query Builder y parametros enlazados. Los filtros se
validan antes de formar la consulta.

**Como se guardan passwords?**

Se almacenan con hash de Laravel. Nunca se guarda ni devuelve el password plano.

**Como se confirma un pago?**

Stripe procesa la tarjeta. El webhook firmado confirma el evento y Laravel
actualiza el estado del pago.

**Que ocurre si se manipula el JavaScript?**

El backend vuelve a validar todos los permisos y reglas. El frontend mejora la
experiencia, pero no es la autoridad de seguridad.

**Como se evita un horario duplicado?**

Antes de guardar se consulta si aula, docente o grupo ya estan ocupados en el
mismo dia y rango de horas.

## 15. Comandos para la defensa

```powershell
# Mostrar todos los endpoints y middleware.
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan route:list

# Limpiar configuracion despues de editar .env.
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan config:clear

# Verificar migraciones.
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan migrate:status

# Ejecutar pruebas.
C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe artisan test

# Compilar frontend.
npm run build
```

## 16. Frase final para la exposicion

> El sistema no se limita a guardar formularios. Controla el flujo completo del
> CUP mediante reglas verificadas en el backend: identidad y permisos, pago,
> requisitos, habilitacion, grupos, horarios, carga docente, evaluacion,
> asignacion de carrera, auditoria y reportes exportables con datos reales.
