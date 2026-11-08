# Aislamiento multiempresa

La aplicación comparte una base de datos. Las 21 tablas de negocio llevan empresa_id obligatorio. El correo y la contraseña identifican una cuenta global; empresa_usuario define su rol, trabajador vinculado y estado en cada empresa. Roles es un catálogo global (ADMINISTRADOR, GERENTE, TRABAJADOR).

## Uso

- Al iniciar sesión con varias empresas se muestra Mis empresas. Con una sola empresa activa se entra directamente al panel.
- El nombre de la empresa en la barra superior permite cambiar de empresa.
- Administrar accesos permite asignar una cuenta existente por correo, crear una nueva o cambiar su rol, trabajador y estado solo en la empresa actual.
- Los administradores pueden crear otra empresa desde Mis empresas. La nueva empresa empieza sin áreas, trabajadores ni horarios; el creador recibe acceso de administrador.
- Desactivar una membresía no desactiva la cuenta en otras empresas. No se permite quitar al último administrador activo.
- Una cuenta compartida conserva sus credenciales: la gestión de accesos no modifica el nombre, correo o contraseña de una cuenta existente. El formulario de personal rechaza cambios de identidad de una cuenta compartida realizados por otro usuario; las contraseñas se gestionan desde Mi cuenta.

## Aplicar a una instalación existente

1. Respaldar la base y storage/app. Verificar primero que el historial corresponde al esquema consolidado anterior. Si la instalación conserva el historial de antes de la consolidación, reconciliarlo antes de desplegar estas migraciones; no marcar cambios de estructura pendientes como ejecutados.
2. Poner la aplicación en mantenimiento y detener temporalmente el scheduler, workers y recepción biométrica.
3. Ejecutar `php artisan migrate --force`. Las tres migraciones nuevas crean Empresa inicial, asignan los registros existentes a ella y generan las membresías con el rol, trabajador y estado anteriores. No se ejecutan seeders sobre datos existentes.
4. Ejecutar `php artisan empresas:privatizar-archivos`. Traslada los adjuntos públicos a storage/app/private, verifica SHA-256 antes de eliminar cada copia pública y conserva las rutas de la base. También retira archivos huérfanos de solicitudes y reportes-avance. Puede repetirse. Si informa archivos faltantes o contenidos distintos, resolverlos antes de abrir el servicio. Retirar también las copias antiguas de CDN u otros servidores públicos, si existen.
5. Limpiar las cachés de configuración y rutas según el despliegue. Iniciar sesión, comprobar los datos de Empresa inicial y registrar/configurar los tokens de dispositivos desde Administrar accesos.
6. Reanudar los procesos y salir del mantenimiento.

Este cambio no ejecuta migraciones sobre la base de desarrollo o producción. No usar migrate:fresh para actualizar una base con datos. Las migraciones de actualización se conservan separadas de los archivos originales para permitir el cambio incremental.

## Biométricos

Cada serial está registrado globalmente y pertenece a una empresa. La recepción resuelve la empresa desde ese registro, nunca desde empresa_id enviado por el cliente ni desde la sesión de un navegador. Los códigos PIN pueden repetirse entre empresas.

Todos los dispositivos requieren token, incluidos los históricos. Desde Administrar accesos se puede generar o renovar el token; solo se guarda su hash. El token se muestra una vez y la renovación invalida el anterior. Se transmite mediante la cabecera X-Device-Token (preferida) o el parámetro token de las peticiones /iclock, siempre sobre HTTPS. Si el firmware no permite configurarlo, el intermediario que publique las peticiones deberá incluirlo. No registrar tokens en los logs del proxy/servidor web. Los logs de aplicación excluyen el parámetro token.

La migración conserva los dispositivos existentes y, si BIOMETRICO_SENSEFACE_SN está configurado y aún no existe, registra ese serial en Empresa inicial. El serial por sí solo ya no autoriza peticiones. Antes de reactivar la recepción se debe generar y configurar el token.

## Desarrollo

- Los modelos de negocio usan PerteneceAEmpresa. Las consultas sin contexto no devuelven registros y las escrituras requieren empresa explícita. empresa_id no se admite por asignación masiva y no puede cambiarse en un registro existente.
- EstablecerEmpresa se ejecuta antes del binding de rutas. Revalida la cuenta, la empresa y la membresía en cada petición; PermisosEmpresa y las autorizaciones de los controladores usan el rol de esa membresía. Las acciones de selección y cierre de sesión siguen disponibles aunque se revoque la empresa activa.
- Las validaciones exists/unique de tablas de negocio se filtran por la empresa mediante VerificadorPresenciaEmpresa. Las restricciones únicas de DNI, código biométrico, nombre de área y horario incluyen empresa_id. Correo y serial de dispositivo siguen siendo globales.
- Las claves foráneas compuestas impiden enlazar registros de empresas distintas, incluidos autores/revisores. Estas restricciones usan RESTRICT para evitar eliminar registros relacionados; se mantiene la gestión habitual mediante estados, no borrados físicos.
- Los accesos a tablas globales y las consultas sin scopes en EmpresaController y EmpresaBiometrico son excepciones explícitas para membresías y resolución de dispositivos. No usar DB::table o withoutGlobalScopes en consultas de negocio sin restringir empresa_id. Las actualizaciones masivas no ejecutan eventos Eloquent; las claves compuestas protegen las relaciones, pero el código nunca debe actualizar empresa_id por SQL.
- Los comandos y futuros jobs deben establecer contexto con `app(EmpresaActual::class)->ejecutar($empresa, fn () => ...)`. El cierre programado recorre las empresas activas y restaura el contexto incluso si ocurre una excepción.
- Los adjuntos nuevos se guardan en el disco privado bajo empresas/{id}. Su descarga pasa por rutas con binding y permisos. Los adjuntos históricos conservan rutas privadas sin prefijo tras el traslado.
- users.rol_id y users.trabajador_id se conservan como compatibilidad para el historial anterior; no determinan permisos dentro de una empresa. Los cambios se realizan con actualizarMembresia().

## Validación

Ejecutar `php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml`. La suite usa SQLite en memoria e incluye datos históricos, migración y reversión, filtros, binding, validaciones, restricciones SQL, login, cambio/revocación de empresa, roles, adjuntos, tokens biométricos, cierre programado y flujos existentes de trabajo en campo. También verifica los seeders de una instalación nueva. Antes de producción se debe ensayar la actualización sobre una copia de la base MySQL de destino.

Planes, facturación, suscripciones, dominios personalizados y administración global de la plataforma quedan fuera de esta etapa.
