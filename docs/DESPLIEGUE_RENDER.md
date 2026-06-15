# Despliegue del CUP en Render

## Componentes

- Un Web Service de Render con runtime Docker.
- Una base Render PostgreSQL.
- El repositorio de GitHub con `Dockerfile`, `.dockerignore` y `docker/start.sh`.

## Web Service

Crear un Web Service conectado al repositorio y seleccionar:

- Runtime: Docker
- Dockerfile Path: `./Dockerfile`
- Health Check Path: `/up`
- Region: la misma region de PostgreSQL

No es necesario configurar Build Command ni Start Command. El `Dockerfile`
y `docker/start.sh` se encargan de ambos.

## Variables minimas

```env
APP_NAME=CUP UAGRM
APP_ENV=production
APP_DEBUG=false
APP_URL=https://NOMBRE-DEL-SERVICIO.onrender.com
APP_KEY=base64:CLAVE_GENERADA
LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=pgsql
DB_URL=INTERNAL_DATABASE_URL_DE_RENDER
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
```

Agregar tambien las variables `STRIPE_*`, `GEMINI_*` y `MAIL_*` usadas por
el proyecto. No subir el archivo `.env` a GitHub.

## Base de datos

Si la base de Render ya fue restaurada desde `Base_Cup.dump`, el inicio del
contenedor solamente ejecutara las migraciones pendientes.

Para una base vacia, Laravel puede usar
`database/schema/pgsql-schema.sql` como estructura inicial antes de ejecutar
las migraciones posteriores.

Nunca ejecutar `php artisan migrate:fresh` en produccion porque elimina datos.

## Stripe

Despues del primer despliegue, registrar este endpoint en Stripe:

```text
https://NOMBRE-DEL-SERVICIO.onrender.com/api/stripe/webhook
```

Copiar el secreto generado a `STRIPE_WEBHOOK_SECRET` en Render y volver a
desplegar el servicio.
