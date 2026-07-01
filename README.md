# Backend GRCV

API Laravel para el sistema de Gestion, Registro y Control de Vacaciones.

Este backend expone los endpoints usados por el formulario publico de solicitudes y por el dashboard administrativo. Tambien genera el PDF de la solicitud, sirve adjuntos por endpoint y centraliza la autenticacion con Sanctum.

## Stack

- PHP 8.3
- Laravel 13
- Laravel Sanctum
- MySQL
- barryvdh/laravel-dompdf

## Funcionalidades

- Registro publico de solicitudes de vacaciones.
- Consulta de datos del trabajador por documento en `/api/employee-info`.
- Generacion de PDF por cada solicitud.
- Descarga del PDF y del adjunto mediante endpoints.
- Dashboard autenticado con roles.
- Gestion de usuarios, colaboradores y estados de solicitudes.
- Cambio de clave del usuario autenticado.

## Estructura

```text
back/
  app/
    Http/Controllers/Api/
    Mail/
    Models/
    Services/
  config/
  database/
  public/
  resources/views/
  routes/
  storage/
  tests/
```

## Requisitos

- PHP 8.3 o superior
- Composer
- MySQL o MariaDB
- Node.js 20+ y npm

## Instalacion Local

1. Instala dependencias:

```bash
composer install
```

2. Crea el archivo de entorno:

```bash
copy .env.example .env
```

3. Genera la clave de Laravel:

```bash
php artisan key:generate
```

4. Configura tu conexion de base de datos en `.env`.

5. Ejecuta migraciones:

```bash
php artisan migrate
```

6. Si necesitas compilar assets del backend:

```bash
npm install
npm run build
```

7. Inicia el servidor:

```bash
php artisan serve --host=127.0.0.1 --port=8001
```

## Scripts Utiles

- `composer setup`: instala dependencias, crea `.env`, genera clave, migra y compila assets.
- `composer test`: limpia config y ejecuta pruebas.
- `composer dev`: levanta servidor, cola, logs y Vite en paralelo.

## Variables De Entorno

Variables clave usadas por este proyecto:

```env
APP_NAME="Vacaciones Pro"
APP_ENV=local
APP_URL=http://127.0.0.1:8001

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vacation_system
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="vacaciones@tema.com.pe"
HR_NOTIFICATION_EMAILS=aqueque@tema.com.pe

MASTER_DATA_API_URL=
MASTER_DATA_API_TOKEN=
LEGACY_UPLOADS_PATH=C:\trabajos\vacaciones\static\uploads
```

Notas:

- `MAIL_MAILER=log` no envia correos reales; solo registra la salida en logs.
- `HR_NOTIFICATION_EMAILS` acepta uno o varios correos separados por coma.
- `LEGACY_UPLOADS_PATH` solo se usa si migras archivos heredados de la aplicacion anterior.

## Endpoints Principales

Publicos:

- `POST /api/login`
- `POST /api/auth/login`
- `POST /api/forgot-password`
- `POST /api/reset-password`
- `GET /api/employee-info`
- `GET /api/areas`
- `GET /api/download-template`
- `GET /api/colaboradores`
- `POST /api/vacations`
- `GET /api/vacations/{id}/pdf`
- `GET /api/vacations/{id}/attachment`

Protegidos con `auth:sanctum`:

- `GET /api/me`
- `POST /api/logout`
- `POST /api/auth/logout`
- `POST /api/auth/change-password`
- `GET /api/vacations`
- `GET /api/vacations/{id}`

Solo administrador:

- `PUT /api/vacations/{id}`
- `PATCH /api/vacations/{id}/status`
- `DELETE /api/vacations/{id}`
- `GET /api/roles`
- `GET /api/users`
- `POST /api/users`
- `PUT /api/users/{id}`
- `DELETE /api/users/{id}`
- `POST /api/colaboradores`
- `PUT /api/colaboradores/{documento}`
- `DELETE /api/colaboradores/{documento}`

## PDFs Y Adjuntos

- Los PDFs se generan al registrar o actualizar una solicitud.
- Los archivos se sirven por endpoint para evitar depender de `storage:link`.
- El frontend consume URLs tipo:

```text
/api/vacations/{id}/pdf
/api/vacations/{id}/attachment
```

## Pruebas

Ejecuta:

```bash
composer test
```

Si corres pruebas de notificaciones con SQLite, asegurate de tener habilitado `pdo_sqlite`.

## Despliegue

Para Hostinger revisa el documento:

- `DEPLOY_HOSTINGER.md`

Archivos relacionados:

- `.env.hostinger`
- `.htaccess`
- `public/.htaccess`

## Recomendaciones

- No subas `.env`, `.env.hostinger`, `vendor/`, `node_modules/` ni archivos generados.
- Si cambias configuracion en produccion, limpia y regenera cache:

```bash
php artisan config:clear
php artisan config:cache
```

- Si Intelephense no detecta una clase nueva, ejecuta:

```bash
composer dump-autoload
```
