# Despliegue en Hostinger

## Estructura esperada

Sube **todo el contenido** de `back` dentro de `public_html`.

Debe quedar asi:

```text
public_html/
  .htaccess
  app/
  bootstrap/
  config/
  database/
  public/
  resources/
  routes/
  storage/
  vendor/
  artisan
  composer.json
  .env
```

El archivo `.htaccess` de la raiz ya esta preparado para redirigir el dominio hacia `public/`.

## 1. Subir archivos

- Sube todo el contenido de `c:\trabajos\vacaciones\vacaiones\back` a `public_html`.
- Verifica que tambien se haya subido el `.htaccess` de la raiz y el de `public`.
- Si `vendor/` no se sube por FTP, ejecuta `composer install` desde la terminal de Hostinger.

## 2. Crear `.env`

Copia `.env.example` a `.env` y usa esta base:

```env
APP_NAME=GRCV
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://grcv.temalitoclean.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_ES

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u220252535_grcv
DB_USERNAME=u220252535_grcv
DB_PASSWORD=TU_PASSWORD_REAL

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=database
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public

MAIL_MAILER=log
MAIL_FROM_ADDRESS=no-reply@grcv.temalitoclean.com
MAIL_FROM_NAME="GRCV"
HR_NOTIFICATION_EMAILS=rrhh@temalitoclean.com

MASTER_DATA_API_URL=
MASTER_DATA_API_TOKEN=

LEGACY_UPLOADS_PATH=
```

Notas:

- Debes completar `DB_PASSWORD` con la clave real de la base creada en Hostinger.
- Si vas a usar correo real, reemplaza `MAIL_MAILER=log` por SMTP.
- `HR_NOTIFICATION_EMAILS` acepta uno o varios correos separados por coma para RRHH.
- Si no usas la API adicional de master data, deja vacios `MASTER_DATA_API_URL` y `MASTER_DATA_API_TOKEN`.

## 3. Generar `APP_KEY`

Ejecuta:

```bash
php artisan key:generate
```

Si ya editaste `.env`, puedes usar:

```bash
php artisan key:generate --force
```

## 4. Instalar dependencias

En la terminal de Hostinger, dentro de `public_html`:

```bash
composer install --no-dev --optimize-autoloader
```

## 5. Migrar la base de datos

Si la base esta vacia:

```bash
php artisan migrate --seed --force
```

Si ya tiene datos y no quieres reseed:

```bash
php artisan migrate --force
```

## 6. Optimizar Laravel

Ejecuta:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 7. Permisos

Verifica que Laravel pueda escribir en:

- `storage/`
- `bootstrap/cache/`

Si Hostinger te deja usar terminal:

```bash
chmod -R 775 storage bootstrap/cache
```

## 8. Frontend React

Este proyecto tiene frontend separado. Si despliegas solo `back`, quedara publicada la API, pero no la interfaz React.

Para publicar la interfaz:

1. En tu PC, dentro de `c:\trabajos\vacaciones\vacaiones\front`, ejecuta:

```bash
npm install
npm run build
```

2. Crea un archivo `.env.production` en `front` con:

```env
VITE_API_URL=https://grcv.temalitoclean.com/api
```

3. Vuelve a ejecutar:

```bash
npm run build
```

4. Sube el contenido de `front/dist` a una carpeta publica del hosting.

Opciones recomendadas:

- `public_html/app/` para abrir la interfaz en `https://grcv.temalitoclean.com/app/`
- o integrar ese `dist` dentro de `public_html/public/app/`

## 9. Pruebas finales

Despues del despliegue, valida:

- `https://grcv.temalitoclean.com/api/areas`
- `https://grcv.temalitoclean.com/api/employee-info?dni_type=1&dni=09739991`
- `https://grcv.temalitoclean.com/api/download-template`

Si alguna ruta falla:

- revisa `.env`
- revisa que `vendor/` exista
- revisa permisos
- revisa el archivo `storage/logs/laravel.log`

## 10. Recomendacion importante

Aunque tu enfoque de copiar todo `back` a `public_html` funciona con el `.htaccess` agregado, la opcion mas segura en Hostinger sigue siendo que el dominio apunte directamente a `public_html/public`.

Si luego Hostinger te permite cambiar el document root del subdominio `grcv.temalitoclean.com`, esa seria la configuracion ideal.
