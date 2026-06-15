--
-- PostgreSQL database dump
--

\restrict hlfbEPzkGotmCXTqKCOB8HGhgfhb88cWp1eWHYIKF489d51tOjotCHalSepY3HO

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: academico; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA academico;


--
-- Name: pago; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA pago;


--
-- Name: seguridad; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA seguridad;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: acta_nota; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.acta_nota (
    id integer NOT NULL,
    nota1 integer,
    nota2 integer,
    nota3 integer,
    promedio double precision,
    descripcion text,
    id_grupo character varying(100) NOT NULL,
    id_materia character varying(100) NOT NULL,
    username_postulante character varying(100) NOT NULL
);


--
-- Name: acta_nota_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.acta_nota_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: acta_nota_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.acta_nota_id_seq OWNED BY academico.acta_nota.id;


--
-- Name: administrativo; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.administrativo (
    username_administrativo character varying(100) NOT NULL,
    nombre character varying(500) NOT NULL,
    telefono character varying(10) NOT NULL,
    ciudad text NOT NULL,
    correo character varying(100)
);


--
-- Name: asignacion_carrera; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.asignacion_carrera (
    id integer NOT NULL,
    username_postulante character varying(500) NOT NULL,
    id_carrera character varying(50),
    primera_opcion character varying(50),
    segunda_opcion character varying(50),
    promedio_final numeric(5,2) NOT NULL,
    nota3_promedio numeric(5,2),
    nota2_promedio numeric(5,2),
    nota1_promedio numeric(5,2),
    opcion_asignada integer,
    estado character varying(50) DEFAULT 'asignado'::character varying NOT NULL,
    motivo text,
    created_at timestamp without time zone DEFAULT now(),
    CONSTRAINT asignacion_carrera_estado_check CHECK (((estado)::text = ANY ((ARRAY['asignado'::character varying, 'lista_espera'::character varying, 'reprobado'::character varying, 'sin_opcion'::character varying])::text[])))
);


--
-- Name: asignacion_carrera_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.asignacion_carrera_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: asignacion_carrera_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.asignacion_carrera_id_seq OWNED BY academico.asignacion_carrera.id;


--
-- Name: asistencia; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.asistencia (
    id integer NOT NULL,
    username_postulante character varying(500) NOT NULL,
    username_docente character varying(500) NOT NULL,
    id_grupo character varying(100) NOT NULL,
    id_materia character varying(100) NOT NULL,
    fecha date NOT NULL,
    estado character varying(50) NOT NULL,
    observacion text,
    created_at timestamp without time zone DEFAULT now(),
    CONSTRAINT asistencia_estado_check CHECK (((estado)::text = ANY ((ARRAY['presente'::character varying, 'retraso'::character varying, 'falta'::character varying])::text[])))
);


--
-- Name: asistencia_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.asistencia_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: asistencia_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.asistencia_id_seq OWNED BY academico.asistencia.id;


--
-- Name: aula; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.aula (
    nro_aula integer NOT NULL,
    tipo text NOT NULL,
    piso character varying(100) NOT NULL,
    capacidad integer DEFAULT 70 NOT NULL,
    estado character varying(50) DEFAULT 'disponible'::character varying NOT NULL
);


--
-- Name: carrera; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.carrera (
    codigo character varying(50) NOT NULL,
    nombre character varying(500) NOT NULL,
    estado character varying(50) DEFAULT 'habilitada'::character varying NOT NULL,
    cupo_maximo integer DEFAULT 0 NOT NULL
);


--
-- Name: dia; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.dia (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL
);


--
-- Name: dia_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.dia_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: dia_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.dia_id_seq OWNED BY academico.dia.id;


--
-- Name: docente; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.docente (
    username_docente character varying(500) NOT NULL,
    nombre character varying(500) NOT NULL,
    especializacion text,
    maestria text,
    correo character varying(100),
    telefono character varying(10),
    ciudad text,
    titulo_profesional character varying(500),
    nro_registro_profesional character varying(100),
    estado_profesional character varying(50) DEFAULT 'pendiente_revision'::character varying NOT NULL,
    observacion_profesional text,
    max_grupos_periodo integer DEFAULT 3 NOT NULL,
    max_horas_semana numeric(5,2) DEFAULT 30 NOT NULL
);


--
-- Name: docente_grupo; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.docente_grupo (
    username_docente character varying(500) NOT NULL,
    codigo_grupo character varying(100) NOT NULL,
    created_at timestamp without time zone DEFAULT now()
);


--
-- Name: docente_materia; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.docente_materia (
    username_docente character varying(500) NOT NULL,
    id_materia character varying(100) NOT NULL,
    created_at timestamp without time zone DEFAULT now()
);


--
-- Name: grupo; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.grupo (
    codigo character varying(100) NOT NULL,
    descripcion text,
    cupo_maximo integer DEFAULT 70 NOT NULL,
    turno character varying(50),
    id_periodo_academico integer,
    estado character varying(50) DEFAULT 'activo'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT now()
);


--
-- Name: horario; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.horario (
    codigo integer NOT NULL,
    horario_inicial time without time zone NOT NULL,
    horario_final time without time zone NOT NULL,
    turno character varying(100) NOT NULL,
    username_postulante character varying(100) NOT NULL,
    id_grupo character varying(100) NOT NULL,
    id_materia character varying(100) NOT NULL,
    id_aula integer NOT NULL,
    id_periodoacademico integer NOT NULL,
    id_dia integer NOT NULL
);


--
-- Name: horario_codigo_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.horario_codigo_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: horario_codigo_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.horario_codigo_seq OWNED BY academico.horario.codigo;


--
-- Name: horario_grupo; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.horario_grupo (
    id integer NOT NULL,
    id_grupo character varying(100) NOT NULL,
    id_materia character varying(100) NOT NULL,
    id_aula integer NOT NULL,
    username_docente character varying(500) NOT NULL,
    id_dia integer NOT NULL,
    hora_inicio time without time zone NOT NULL,
    hora_fin time without time zone NOT NULL,
    turno character varying(100) NOT NULL,
    id_periodo_academico integer,
    estado character varying(50) DEFAULT 'propuesto'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT now(),
    CONSTRAINT horario_grupo_estado_check CHECK (((estado)::text = ANY ((ARRAY['propuesto'::character varying, 'confirmado'::character varying])::text[])))
);


--
-- Name: horario_grupo_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.horario_grupo_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: horario_grupo_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.horario_grupo_id_seq OWNED BY academico.horario_grupo.id;


--
-- Name: materia; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.materia (
    id character varying(100) NOT NULL,
    nombre character varying(500) NOT NULL,
    estado character varying(50) DEFAULT 'habilitada'::character varying NOT NULL
);


--
-- Name: materia_grupo; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.materia_grupo (
    codigo_grupo character varying(100) NOT NULL,
    id_materia character varying(100) NOT NULL
);


--
-- Name: periodo_academico; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.periodo_academico (
    id integer NOT NULL,
    semestre integer NOT NULL,
    "año" integer NOT NULL,
    nombre character varying(100),
    fecha_inicio_preinscripcion date,
    fecha_fin_preinscripcion date,
    fecha_inicio_requisitos date,
    fecha_fin_requisitos date,
    fecha_inicio_pago date,
    fecha_fin_pago date,
    estado character varying(50) DEFAULT 'pendiente'::character varying NOT NULL
);


--
-- Name: periodo_academico_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.periodo_academico_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: periodo_academico_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.periodo_academico_id_seq OWNED BY academico.periodo_academico.id;


--
-- Name: ponderacion_nota; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.ponderacion_nota (
    id integer NOT NULL,
    nombre character varying(100) DEFAULT 'Ponderacion CUP'::character varying NOT NULL,
    nota1_porcentaje numeric(5,2) DEFAULT 30 NOT NULL,
    nota2_porcentaje numeric(5,2) DEFAULT 30 NOT NULL,
    nota3_porcentaje numeric(5,2) DEFAULT 40 NOT NULL,
    estado character varying(50) DEFAULT 'activa'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT now()
);


--
-- Name: ponderacion_nota_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.ponderacion_nota_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ponderacion_nota_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.ponderacion_nota_id_seq OWNED BY academico.ponderacion_nota.id;


--
-- Name: postulante; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.postulante (
    username_postulante character varying(500) NOT NULL,
    ci character varying(100) NOT NULL,
    nombre character varying(100) NOT NULL,
    telefono character varying(10) NOT NULL,
    ciudad character varying(100) NOT NULL,
    colegio_procedencia text NOT NULL,
    direccion text NOT NULL,
    fecha_nacimiento date NOT NULL,
    genero character varying(100) NOT NULL,
    cod_titulo_bachiller text NOT NULL,
    correo character varying(100) NOT NULL,
    estado character varying(50) NOT NULL
);


--
-- Name: postulante_carrera; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.postulante_carrera (
    id_carrera character varying(50) NOT NULL,
    username_postulante character varying(100) NOT NULL,
    descripcion text NOT NULL
);


--
-- Name: postulante_grupo; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.postulante_grupo (
    username_postulante character varying(500) NOT NULL,
    id_grupo character varying(100) NOT NULL,
    id_periodo_academico integer,
    estado character varying(50) DEFAULT 'inscrito'::character varying NOT NULL,
    created_at timestamp without time zone DEFAULT now(),
    CONSTRAINT postulante_grupo_estado_check CHECK (((estado)::text = ANY ((ARRAY['inscrito'::character varying, 'retirado'::character varying])::text[])))
);


--
-- Name: requisito_postulante; Type: TABLE; Schema: academico; Owner: -
--

CREATE TABLE academico.requisito_postulante (
    id integer NOT NULL,
    username_postulante character varying(500) NOT NULL,
    ci_entregado boolean DEFAULT false NOT NULL,
    titulo_entregado boolean DEFAULT false NOT NULL,
    libretas_entregadas boolean DEFAULT false NOT NULL,
    observacion text,
    validado_por character varying(500),
    fecha_validacion timestamp without time zone
);


--
-- Name: requisito_postulante_id_seq; Type: SEQUENCE; Schema: academico; Owner: -
--

CREATE SEQUENCE academico.requisito_postulante_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: requisito_postulante_id_seq; Type: SEQUENCE OWNED BY; Schema: academico; Owner: -
--

ALTER SEQUENCE academico.requisito_postulante_id_seq OWNED BY academico.requisito_postulante.id;


--
-- Name: pago; Type: TABLE; Schema: pago; Owner: -
--

CREATE TABLE pago.pago (
    id integer NOT NULL,
    username_postulante character varying(500) NOT NULL,
    monto numeric(10,2) NOT NULL,
    nro_comprobante character varying(100) NOT NULL,
    fecha_pago date NOT NULL,
    registrado_por character varying(500),
    estado character varying(50) DEFAULT 'registrado'::character varying NOT NULL,
    observacion text,
    created_at timestamp without time zone DEFAULT now()
);


--
-- Name: pago_id_seq; Type: SEQUENCE; Schema: pago; Owner: -
--

CREATE SEQUENCE pago.pago_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: pago_id_seq; Type: SEQUENCE OWNED BY; Schema: pago; Owner: -
--

ALTER SEQUENCE pago.pago_id_seq OWNED BY pago.pago.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id character varying(500),
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: bitacora; Type: TABLE; Schema: seguridad; Owner: -
--

CREATE TABLE seguridad.bitacora (
    id bigint NOT NULL,
    username character varying(500),
    rol character varying(500),
    tipo_usuario character varying(100),
    accion character varying(120) NOT NULL,
    modulo character varying(120),
    metodo character varying(20),
    ruta text,
    descripcion text,
    ip character varying(100),
    user_agent text,
    datos jsonb,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


--
-- Name: bitacora_id_seq; Type: SEQUENCE; Schema: seguridad; Owner: -
--

CREATE SEQUENCE seguridad.bitacora_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: bitacora_id_seq; Type: SEQUENCE OWNED BY; Schema: seguridad; Owner: -
--

ALTER SEQUENCE seguridad.bitacora_id_seq OWNED BY seguridad.bitacora.id;


--
-- Name: carrera; Type: TABLE; Schema: seguridad; Owner: -
--

CREATE TABLE seguridad.carrera (
    codigo character varying(50) NOT NULL,
    nombre character varying(500) NOT NULL
);


--
-- Name: permiso; Type: TABLE; Schema: seguridad; Owner: -
--

CREATE TABLE seguridad.permiso (
    codigo integer NOT NULL,
    nombre character varying(500) NOT NULL
);


--
-- Name: permiso_codigo_seq; Type: SEQUENCE; Schema: seguridad; Owner: -
--

CREATE SEQUENCE seguridad.permiso_codigo_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permiso_codigo_seq; Type: SEQUENCE OWNED BY; Schema: seguridad; Owner: -
--

ALTER SEQUENCE seguridad.permiso_codigo_seq OWNED BY seguridad.permiso.codigo;


--
-- Name: permiso_rol; Type: TABLE; Schema: seguridad; Owner: -
--

CREATE TABLE seguridad.permiso_rol (
    codigo_permiso integer NOT NULL,
    id_rol integer NOT NULL
);


--
-- Name: rol; Type: TABLE; Schema: seguridad; Owner: -
--

CREATE TABLE seguridad.rol (
    id integer NOT NULL,
    nombre character varying(500) NOT NULL
);


--
-- Name: rol_id_seq; Type: SEQUENCE; Schema: seguridad; Owner: -
--

CREATE SEQUENCE seguridad.rol_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: rol_id_seq; Type: SEQUENCE OWNED BY; Schema: seguridad; Owner: -
--

ALTER SEQUENCE seguridad.rol_id_seq OWNED BY seguridad.rol.id;


--
-- Name: usuario; Type: TABLE; Schema: seguridad; Owner: -
--

CREATE TABLE seguridad.usuario (
    username character varying(500) NOT NULL,
    password text NOT NULL,
    codigo_rol integer,
    tipo character varying(100) NOT NULL
);


--
-- Name: acta_nota id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.acta_nota ALTER COLUMN id SET DEFAULT nextval('academico.acta_nota_id_seq'::regclass);


--
-- Name: asignacion_carrera id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asignacion_carrera ALTER COLUMN id SET DEFAULT nextval('academico.asignacion_carrera_id_seq'::regclass);


--
-- Name: asistencia id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asistencia ALTER COLUMN id SET DEFAULT nextval('academico.asistencia_id_seq'::regclass);


--
-- Name: dia id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.dia ALTER COLUMN id SET DEFAULT nextval('academico.dia_id_seq'::regclass);


--
-- Name: horario codigo; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario ALTER COLUMN codigo SET DEFAULT nextval('academico.horario_codigo_seq'::regclass);


--
-- Name: horario_grupo id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo ALTER COLUMN id SET DEFAULT nextval('academico.horario_grupo_id_seq'::regclass);


--
-- Name: periodo_academico id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.periodo_academico ALTER COLUMN id SET DEFAULT nextval('academico.periodo_academico_id_seq'::regclass);


--
-- Name: ponderacion_nota id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.ponderacion_nota ALTER COLUMN id SET DEFAULT nextval('academico.ponderacion_nota_id_seq'::regclass);


--
-- Name: requisito_postulante id; Type: DEFAULT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.requisito_postulante ALTER COLUMN id SET DEFAULT nextval('academico.requisito_postulante_id_seq'::regclass);


--
-- Name: pago id; Type: DEFAULT; Schema: pago; Owner: -
--

ALTER TABLE ONLY pago.pago ALTER COLUMN id SET DEFAULT nextval('pago.pago_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: bitacora id; Type: DEFAULT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.bitacora ALTER COLUMN id SET DEFAULT nextval('seguridad.bitacora_id_seq'::regclass);


--
-- Name: permiso codigo; Type: DEFAULT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.permiso ALTER COLUMN codigo SET DEFAULT nextval('seguridad.permiso_codigo_seq'::regclass);


--
-- Name: rol id; Type: DEFAULT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.rol ALTER COLUMN id SET DEFAULT nextval('seguridad.rol_id_seq'::regclass);


--
-- Name: acta_nota acta_nota_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.acta_nota
    ADD CONSTRAINT acta_nota_pkey PRIMARY KEY (id);


--
-- Name: administrativo administrativo_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.administrativo
    ADD CONSTRAINT administrativo_pkey PRIMARY KEY (username_administrativo);


--
-- Name: asignacion_carrera asignacion_carrera_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asignacion_carrera
    ADD CONSTRAINT asignacion_carrera_pkey PRIMARY KEY (id);


--
-- Name: asignacion_carrera asignacion_carrera_username_unique; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asignacion_carrera
    ADD CONSTRAINT asignacion_carrera_username_unique UNIQUE (username_postulante);


--
-- Name: asistencia asistencia_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asistencia
    ADD CONSTRAINT asistencia_pkey PRIMARY KEY (id);


--
-- Name: asistencia asistencia_unique; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asistencia
    ADD CONSTRAINT asistencia_unique UNIQUE (username_postulante, id_grupo, id_materia, fecha);


--
-- Name: aula aula_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.aula
    ADD CONSTRAINT aula_pkey PRIMARY KEY (nro_aula);


--
-- Name: carrera carrera_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.carrera
    ADD CONSTRAINT carrera_pkey PRIMARY KEY (codigo);


--
-- Name: dia dia_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.dia
    ADD CONSTRAINT dia_pkey PRIMARY KEY (id);


--
-- Name: docente_grupo docente_grupo_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente_grupo
    ADD CONSTRAINT docente_grupo_pkey PRIMARY KEY (username_docente, codigo_grupo);


--
-- Name: docente_materia docente_materia_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente_materia
    ADD CONSTRAINT docente_materia_pkey PRIMARY KEY (username_docente, id_materia);


--
-- Name: docente docente_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente
    ADD CONSTRAINT docente_pkey PRIMARY KEY (username_docente);


--
-- Name: grupo grupo_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.grupo
    ADD CONSTRAINT grupo_pkey PRIMARY KEY (codigo);


--
-- Name: horario_grupo horario_grupo_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_pkey PRIMARY KEY (id);


--
-- Name: horario_grupo horario_grupo_unico_bloque; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_unico_bloque UNIQUE (id_grupo, id_dia, hora_inicio, hora_fin);


--
-- Name: horario horario_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario
    ADD CONSTRAINT horario_pkey PRIMARY KEY (codigo);


--
-- Name: materia_grupo materia_grupo_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.materia_grupo
    ADD CONSTRAINT materia_grupo_pkey PRIMARY KEY (codigo_grupo, id_materia);


--
-- Name: materia materia_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.materia
    ADD CONSTRAINT materia_pkey PRIMARY KEY (id);


--
-- Name: periodo_academico periodo_academico_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.periodo_academico
    ADD CONSTRAINT periodo_academico_pkey PRIMARY KEY (id);


--
-- Name: ponderacion_nota ponderacion_nota_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.ponderacion_nota
    ADD CONSTRAINT ponderacion_nota_pkey PRIMARY KEY (id);


--
-- Name: postulante_carrera postulante_carrera_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_carrera
    ADD CONSTRAINT postulante_carrera_pkey PRIMARY KEY (id_carrera, username_postulante);


--
-- Name: postulante_grupo postulante_grupo_periodo_unique; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_grupo
    ADD CONSTRAINT postulante_grupo_periodo_unique UNIQUE (username_postulante, id_periodo_academico);


--
-- Name: postulante_grupo postulante_grupo_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_grupo
    ADD CONSTRAINT postulante_grupo_pkey PRIMARY KEY (username_postulante, id_grupo);


--
-- Name: postulante postulante_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante
    ADD CONSTRAINT postulante_pkey PRIMARY KEY (username_postulante);


--
-- Name: requisito_postulante requisito_postulante_pkey; Type: CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.requisito_postulante
    ADD CONSTRAINT requisito_postulante_pkey PRIMARY KEY (id);


--
-- Name: pago pago_pkey; Type: CONSTRAINT; Schema: pago; Owner: -
--

ALTER TABLE ONLY pago.pago
    ADD CONSTRAINT pago_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: bitacora bitacora_pkey; Type: CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.bitacora
    ADD CONSTRAINT bitacora_pkey PRIMARY KEY (id);


--
-- Name: carrera carrera_pkey; Type: CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.carrera
    ADD CONSTRAINT carrera_pkey PRIMARY KEY (codigo);


--
-- Name: permiso permiso_pkey; Type: CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.permiso
    ADD CONSTRAINT permiso_pkey PRIMARY KEY (codigo);


--
-- Name: permiso_rol permiso_rol_pkey; Type: CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.permiso_rol
    ADD CONSTRAINT permiso_rol_pkey PRIMARY KEY (codigo_permiso, id_rol);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (username);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: acta_nota acta_nota_id_grupo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.acta_nota
    ADD CONSTRAINT acta_nota_id_grupo_fkey FOREIGN KEY (id_grupo) REFERENCES academico.grupo(codigo);


--
-- Name: acta_nota acta_nota_id_materia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.acta_nota
    ADD CONSTRAINT acta_nota_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES academico.materia(id);


--
-- Name: acta_nota acta_nota_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.acta_nota
    ADD CONSTRAINT acta_nota_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES seguridad.usuario(username);


--
-- Name: administrativo administrativo_username_administrativo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.administrativo
    ADD CONSTRAINT administrativo_username_administrativo_fkey FOREIGN KEY (username_administrativo) REFERENCES seguridad.usuario(username);


--
-- Name: asignacion_carrera asignacion_carrera_id_carrera_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asignacion_carrera
    ADD CONSTRAINT asignacion_carrera_id_carrera_fkey FOREIGN KEY (id_carrera) REFERENCES academico.carrera(codigo);


--
-- Name: asignacion_carrera asignacion_carrera_primera_opcion_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asignacion_carrera
    ADD CONSTRAINT asignacion_carrera_primera_opcion_fkey FOREIGN KEY (primera_opcion) REFERENCES academico.carrera(codigo);


--
-- Name: asignacion_carrera asignacion_carrera_segunda_opcion_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asignacion_carrera
    ADD CONSTRAINT asignacion_carrera_segunda_opcion_fkey FOREIGN KEY (segunda_opcion) REFERENCES academico.carrera(codigo);


--
-- Name: asignacion_carrera asignacion_carrera_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asignacion_carrera
    ADD CONSTRAINT asignacion_carrera_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES academico.postulante(username_postulante);


--
-- Name: asistencia asistencia_id_grupo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asistencia
    ADD CONSTRAINT asistencia_id_grupo_fkey FOREIGN KEY (id_grupo) REFERENCES academico.grupo(codigo);


--
-- Name: asistencia asistencia_id_materia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asistencia
    ADD CONSTRAINT asistencia_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES academico.materia(id);


--
-- Name: asistencia asistencia_username_docente_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asistencia
    ADD CONSTRAINT asistencia_username_docente_fkey FOREIGN KEY (username_docente) REFERENCES academico.docente(username_docente);


--
-- Name: asistencia asistencia_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.asistencia
    ADD CONSTRAINT asistencia_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES academico.postulante(username_postulante);


--
-- Name: docente_grupo docente_grupo_codigo_grupo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente_grupo
    ADD CONSTRAINT docente_grupo_codigo_grupo_fkey FOREIGN KEY (codigo_grupo) REFERENCES academico.grupo(codigo);


--
-- Name: docente_grupo docente_grupo_username_docente_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente_grupo
    ADD CONSTRAINT docente_grupo_username_docente_fkey FOREIGN KEY (username_docente) REFERENCES academico.docente(username_docente);


--
-- Name: docente_materia docente_materia_id_materia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente_materia
    ADD CONSTRAINT docente_materia_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES academico.materia(id);


--
-- Name: docente_materia docente_materia_username_docente_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente_materia
    ADD CONSTRAINT docente_materia_username_docente_fkey FOREIGN KEY (username_docente) REFERENCES academico.docente(username_docente);


--
-- Name: docente docente_username_docente_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.docente
    ADD CONSTRAINT docente_username_docente_fkey FOREIGN KEY (username_docente) REFERENCES seguridad.usuario(username);


--
-- Name: grupo grupo_id_periodo_academico_foreign; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.grupo
    ADD CONSTRAINT grupo_id_periodo_academico_foreign FOREIGN KEY (id_periodo_academico) REFERENCES academico.periodo_academico(id);


--
-- Name: horario_grupo horario_grupo_id_aula_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_id_aula_fkey FOREIGN KEY (id_aula) REFERENCES academico.aula(nro_aula);


--
-- Name: horario_grupo horario_grupo_id_dia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_id_dia_fkey FOREIGN KEY (id_dia) REFERENCES academico.dia(id);


--
-- Name: horario_grupo horario_grupo_id_grupo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_id_grupo_fkey FOREIGN KEY (id_grupo) REFERENCES academico.grupo(codigo);


--
-- Name: horario_grupo horario_grupo_id_materia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES academico.materia(id);


--
-- Name: horario_grupo horario_grupo_id_periodo_academico_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_id_periodo_academico_fkey FOREIGN KEY (id_periodo_academico) REFERENCES academico.periodo_academico(id);


--
-- Name: horario_grupo horario_grupo_username_docente_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario_grupo
    ADD CONSTRAINT horario_grupo_username_docente_fkey FOREIGN KEY (username_docente) REFERENCES academico.docente(username_docente);


--
-- Name: horario horario_id_aula_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario
    ADD CONSTRAINT horario_id_aula_fkey FOREIGN KEY (id_aula) REFERENCES academico.aula(nro_aula);


--
-- Name: horario horario_id_dia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario
    ADD CONSTRAINT horario_id_dia_fkey FOREIGN KEY (id_dia) REFERENCES academico.dia(id);


--
-- Name: horario horario_id_grupo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario
    ADD CONSTRAINT horario_id_grupo_fkey FOREIGN KEY (id_grupo) REFERENCES academico.grupo(codigo);


--
-- Name: horario horario_id_materia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario
    ADD CONSTRAINT horario_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES academico.materia(id);


--
-- Name: horario horario_id_periodoacademico_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario
    ADD CONSTRAINT horario_id_periodoacademico_fkey FOREIGN KEY (id_periodoacademico) REFERENCES academico.periodo_academico(id);


--
-- Name: horario horario_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.horario
    ADD CONSTRAINT horario_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES seguridad.usuario(username);


--
-- Name: materia_grupo materia_grupo_codigo_grupo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.materia_grupo
    ADD CONSTRAINT materia_grupo_codigo_grupo_fkey FOREIGN KEY (codigo_grupo) REFERENCES academico.grupo(codigo);


--
-- Name: materia_grupo materia_grupo_id_materia_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.materia_grupo
    ADD CONSTRAINT materia_grupo_id_materia_fkey FOREIGN KEY (id_materia) REFERENCES academico.materia(id);


--
-- Name: postulante_carrera postulante_carrera_id_carrera_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_carrera
    ADD CONSTRAINT postulante_carrera_id_carrera_fkey FOREIGN KEY (id_carrera) REFERENCES academico.carrera(codigo);


--
-- Name: postulante_carrera postulante_carrera_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_carrera
    ADD CONSTRAINT postulante_carrera_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES seguridad.usuario(username);


--
-- Name: postulante_grupo postulante_grupo_id_grupo_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_grupo
    ADD CONSTRAINT postulante_grupo_id_grupo_fkey FOREIGN KEY (id_grupo) REFERENCES academico.grupo(codigo);


--
-- Name: postulante_grupo postulante_grupo_id_periodo_academico_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_grupo
    ADD CONSTRAINT postulante_grupo_id_periodo_academico_fkey FOREIGN KEY (id_periodo_academico) REFERENCES academico.periodo_academico(id);


--
-- Name: postulante_grupo postulante_grupo_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante_grupo
    ADD CONSTRAINT postulante_grupo_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES academico.postulante(username_postulante);


--
-- Name: postulante postulante_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.postulante
    ADD CONSTRAINT postulante_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES seguridad.usuario(username);


--
-- Name: requisito_postulante requisito_postulante_username_postulante_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.requisito_postulante
    ADD CONSTRAINT requisito_postulante_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES academico.postulante(username_postulante);


--
-- Name: requisito_postulante requisito_postulante_validado_por_fkey; Type: FK CONSTRAINT; Schema: academico; Owner: -
--

ALTER TABLE ONLY academico.requisito_postulante
    ADD CONSTRAINT requisito_postulante_validado_por_fkey FOREIGN KEY (validado_por) REFERENCES seguridad.usuario(username);


--
-- Name: pago pago_registrado_por_fkey; Type: FK CONSTRAINT; Schema: pago; Owner: -
--

ALTER TABLE ONLY pago.pago
    ADD CONSTRAINT pago_registrado_por_fkey FOREIGN KEY (registrado_por) REFERENCES seguridad.usuario(username);


--
-- Name: pago pago_username_postulante_fkey; Type: FK CONSTRAINT; Schema: pago; Owner: -
--

ALTER TABLE ONLY pago.pago
    ADD CONSTRAINT pago_username_postulante_fkey FOREIGN KEY (username_postulante) REFERENCES academico.postulante(username_postulante);


--
-- Name: bitacora bitacora_username_foreign; Type: FK CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.bitacora
    ADD CONSTRAINT bitacora_username_foreign FOREIGN KEY (username) REFERENCES seguridad.usuario(username) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: permiso_rol permiso_rol_codigo_permiso_fkey; Type: FK CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.permiso_rol
    ADD CONSTRAINT permiso_rol_codigo_permiso_fkey FOREIGN KEY (codigo_permiso) REFERENCES seguridad.permiso(codigo);


--
-- Name: permiso_rol permiso_rol_id_rol_fkey; Type: FK CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.permiso_rol
    ADD CONSTRAINT permiso_rol_id_rol_fkey FOREIGN KEY (id_rol) REFERENCES seguridad.rol(id);


--
-- Name: usuario usuario_codigo_rol_fkey; Type: FK CONSTRAINT; Schema: seguridad; Owner: -
--

ALTER TABLE ONLY seguridad.usuario
    ADD CONSTRAINT usuario_codigo_rol_fkey FOREIGN KEY (codigo_rol) REFERENCES seguridad.rol(id);


--
-- PostgreSQL database dump complete
--

\unrestrict hlfbEPzkGotmCXTqKCOB8HGhgfhb88cWp1eWHYIKF489d51tOjotCHalSepY3HO

--
-- PostgreSQL database dump
--

\restrict eQ5xo2Oi3b5et9bVYl84zdgD29MWf5bld9bmTTB8ieeK16MZlKdI78i4Ga9fux2

-- Dumped from database version 17.6
-- Dumped by pg_dump version 17.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_05_30_000001_change_sessions_user_id_to_string	2
5	2026_06_03_000001_add_distribution_fields_to_academico_grupo	3
6	2026_06_03_000002_add_capacity_status_to_academico_aula	3
7	2026_06_04_000001_add_estado_to_academico_catalogos	4
8	2026_06_04_000002_create_seguridad_bitacora_table	5
9	2026_06_10_000001_add_configuration_fields_to_academico_periodo_academico	6
10	2026_06_12_000001_add_correo_to_academico_docente_administrativo	7
11	2026_06_12_000002_add_contact_fields_to_academico_docente	8
12	2026_06_12_000003_create_academico_docente_grupo_table	9
13	2026_06_12_000004_create_academico_asistencia_table	10
14	2026_06_12_000005_create_academico_docente_materia_table	11
15	2026_06_12_000006_create_academico_horario_grupo_table	12
16	2026_06_13_000001_add_cupo_maximo_to_academico_carrera	13
17	2026_06_13_000002_create_academico_asignacion_carrera_table	13
18	2026_06_13_000003_create_academico_postulante_grupo_table	14
19	2026_06_14_000001_add_professional_profile_to_academico_docente	15
20	2026_06_14_000002_add_teaching_load_limits_to_academico_docente	16
21	2026_06_14_000003_create_academico_ponderacion_nota_table	17
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 21, true);


--
-- PostgreSQL database dump complete
--

\unrestrict eQ5xo2Oi3b5et9bVYl84zdgD29MWf5bld9bmTTB8ieeK16MZlKdI78i4Ga9fux2

