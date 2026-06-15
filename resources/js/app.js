import { initAuth } from './modules/auth';
import { initAsignacionCarreras } from './modules/asignacion-carreras';
import { initAsistencias } from './modules/asistencias';
import { initAulas } from './modules/aulas';
import { initBitacora } from './modules/bitacora';
import { initCalificaciones } from './modules/calificaciones';
import { initCatalogosAcademicos } from './modules/catalogos-academicos';
import { initDashboard } from './modules/dashboard';
import { initDistribucionGrupos } from './modules/distribucion-grupos';
import { initDocentesHorarios } from './modules/docentes-horarios';
import { initDocentesMaterias } from './modules/docentes-materias';
import { initDocentePanel } from './modules/docente-panel';
import { initHabilitacion } from './modules/habilitacion';
import { initHorariosGrupos } from './modules/horarios-grupos';
import { initPagos } from './modules/pagos';
import { initPeriodoAcademico } from './modules/periodo-academico';
import { initPortalSidebar } from './modules/portal-sidebar';
import { initPostulantesGrupos } from './modules/postulantes-grupos';
import { initPostulantePanel } from './modules/postulante-panel';
import { initPonderacionesNotas } from './modules/ponderaciones-notas';
import { initPreinscripciones } from './modules/preinscripciones';
import { initRequisitos } from './modules/requisitos';
import { initReportes } from './modules/reportes';
import { initRolesPermisos } from './modules/roles-permisos';
import { initSession } from './modules/session';
import { initUsuarios } from './modules/usuarios';

/*
 * Punto de entrada del frontend.
 *
 * Cada pantalla Blade incluye el mismo bundle, pero cada funcion init valida
 * si su pantalla existe antes de ejecutarse. De esta forma la logica queda
 * dividida por modulo/caso de uso y no concentrada en un unico archivo.
 *
 * Flujo frontend:
 * Blade renderiza HTML -> modulo JS captura eventos -> apiRequest llama a
 * /api/... -> Laravel responde JSON -> el modulo actualiza la vista.
 */
document.addEventListener('DOMContentLoaded', () => {
    // CU-01 y CU-02: login, sesion actual y cierre de sesion.
    initAuth();
    initSession();

    // Comportamiento comun del menu lateral responsivo.
    initPortalSidebar();

    // Modulos administrativos y de seguridad.
    initDashboard();
    initBitacora();
    initUsuarios();
    initRolesPermisos();

    // Flujo de admision: preinscripcion, documentos, pago y habilitacion.
    initPreinscripciones();
    initRequisitos();
    initPagos();
    initHabilitacion();
    initPeriodoAcademico();

    // Flujo academico: grupos, horarios, docentes y estudiantes.
    initDistribucionGrupos();
    initHorariosGrupos();
    initDocentesMaterias();
    initDocentesHorarios();
    initDocentePanel();
    initPostulantesGrupos();
    initAsignacionCarreras();

    // Panel individual del postulante.
    initPostulantePanel();

    // Evaluacion academica y reportes.
    initPonderacionesNotas();
    initReportes();
    initAulas();
    initCalificaciones();
    initAsistencias();
    initCatalogosAcademicos();
});
