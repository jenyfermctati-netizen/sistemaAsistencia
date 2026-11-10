# Despliegue de CONNEXT SaaS multiempresa

## Alcance

Esta guía prepara una instalación de Laravel 12/PHP 8.2/MySQL para el despliegue posterior. No sustituye el respaldo, la revisión de infraestructura ni la ventana de mantenimiento. Los archivos `.env` reales no forman parte del repositorio.

## 1. Requisitos

- PHP 8.2 o superior con extensiones requeridas por Laravel, PDO MySQL, mbstring, fileinfo y GD.
- MySQL 8 recomendado.
- Servidor web apuntando exclusivamente a `public/` y HTTPS válido.
- Worker de colas supervisado y cron para el scheduler.
- SMTP transaccional verificado. Las invitaciones no deben desplegarse con `MAIL_MAILER=log`.
- Copia de seguridad de base de datos y de `storage/app` antes de cada despliegue.

## 2. Variables de entorno

Configurar, sin guardar secretos en Git:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://asistencia.ejemplo.pe
APP_LOCALE=es
APP_TIMEZONE=America/Lima

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=connext
DB_USERNAME=usuario_restringido
DB_PASSWORD=secreto

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.pe
MAIL_PORT=587
MAIL_USERNAME=usuario
MAIL_PASSWORD=secreto
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@ejemplo.pe
MAIL_FROM_NAME="CONNEXT"

SUPERADMIN_EMAIL=propietario@ejemplo.pe
SUPERADMIN_PASSWORD=una-clave-inicial-de-al-menos-12-caracteres
```

Usar `SUPERADMIN_PASSWORD` solo para el seed inicial. Después de confirmar el acceso, cambiar la contraseña y retirar esa variable del entorno.

## 3. Instalación y migración

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\DatabaseSeeder --force
php artisan optimize
```

La migración SaaS aborta si detecta datos operativos sin empresa. En ese caso no se debe forzar ni editar la migración: crear y revisar una migración de transición que asigne explícitamente cada registro a su empresa.

El seed crea los códigos de rol y, solo si están definidas las dos variables correspondientes, la cuenta global inicial. No crea empresas ficticias, áreas u horarios.

## 4. Permisos y archivos

- El usuario del servidor web debe poder escribir únicamente en `storage/` y `bootstrap/cache/`.
- Sustentos, evidencias de campo y archivos de avances se guardan en el disco privado `local`, bajo `empresas/{empresa_id}/...`, y se sirven mediante rutas autorizadas.
- No exponer `storage/app/private` ni la raíz del proyecto desde el servidor web.
- Mantener límites de subida del proxy, servidor web y PHP acordes con los límites de 5–8 MB de la aplicación.

## 5. Colas y scheduler

Worker supervisado:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

Cron:

```cron
* * * * * cd /ruta/a/sistemaAsistencia && php artisan schedule:run >> /dev/null 2>&1
```

El comando `asistencias:cerrar` recorre únicamente empresas activas y establece el contexto de cada una antes de procesar.

## 6. Dispositivos biométricos

1. Crear la empresa y su administrador desde `/superadmin`.
2. Registrar el dispositivo con su serial físico global.
3. Si el firmware admite un secreto, habilitarlo y enviar `X-Device-Token` o `AUTH` en cada llamada.
4. Configurar el endpoint iClock sobre HTTPS.
5. Restringirlo por VPN, red privada o allowlist de IP cuando el equipo solo pueda identificarse por serial.

El servidor ignora cualquier `empresa_id` recibido. La empresa se obtiene exclusivamente del serial registrado. Un equipo inactivo, desconocido o asignado a una empresa suspendida recibe HTTP 403.

## 7. Verificación previa a producción

```bash
php artisan migrate:status
php artisan test
php artisan route:list
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Comprobar manualmente:

1. Acceso del superadministrador y alta de empresa.
2. Recepción real de la invitación y establecimiento de contraseña.
3. Acceso del administrador empresarial.
4. Creación de área, horario y trabajador.
5. Empresa suspendida bloqueada inmediatamente.
6. Dos empresas con nombres de área, DNI o PIN repetidos sin cruces.
7. Dispositivo reconocido y dispositivo desconocido.
8. PDF/Excel/CSV y descargas sin datos de otra empresa.
9. Worker, cron, rotación de logs y copia de seguridad restaurable.

## 8. Observabilidad y seguridad

- Alertar sobre errores 5xx, fallos de cola, correos fallidos y respuestas 403 repetidas del endpoint biométrico.
- Rotar `APP_KEY`, credenciales SMTP/DB y secretos de dispositivos según la política del servidor.
- No registrar tokens, contraseñas o el parámetro `AUTH`; el controlador biométrico registra metadatos y tamaños, no cuerpos completos.
- Revisar `/superadmin/auditoria` y conservar logs según la política de datos personales aplicable.

## 9. Reversión

No ejecutar `migrate:fresh`, `db:wipe` ni `migrate:reset`. Para revertir un despliegue:

1. Poner la aplicación en mantenimiento.
2. Restaurar el artefacto anterior.
3. Restaurar la copia de base de datos y almacenamiento tomada antes del despliegue.
4. Reiniciar workers y limpiar cachés.

El `down()` técnico intenta restaurar índices globales antiguos; puede fallar si ya existen valores repetidos entre empresas. Una restauración de respaldo es el procedimiento seguro una vez que la plataforma contiene datos multiempresa.

