# Implementación SaaS multiempresa de CONNEXT

## Estado general

Implementación multiempresa aplicada sobre Laravel 12, PHP 8.2 y MySQL local. El 12/11/2026 se restauró y verificó el histórico de `bdasistencias`, se asignó a Connext como primera empresa y se creó una cuenta global de superadministración. No se ejecutaron operaciones destructivas ni se modificó una base de producción.

## Arquitectura propuesta

- Una sola aplicación y una sola base de datos, con `empresa_id` como frontera de seguridad.
- `EmpresaContext` resuelve la empresa exclusivamente desde el usuario autenticado o, para el protocolo biométrico, desde el serial del dispositivo registrado.
- Los modelos raíz empresariales usan un scope global y asignación automática de `empresa_id`. El middleware empresarial agrega comprobaciones de autenticación, rol empresarial y estado de la empresa.
- Las rutas globales usan middleware independiente y requieren un rol con ámbito `GLOBAL` y código `SUPERADMIN`.
- Las relaciones indirectas conservan un único origen de verdad y heredan la empresa de su entidad raíz. Los controladores y servicios validan además que las relaciones seleccionadas pertenezcan a la empresa activa.
- Los usuarios globales tienen `empresa_id = NULL`. Los usuarios operativos deben tener empresa.
- Los dispositivos se identifican por serial global único; el servidor obtiene de ese registro la empresa y nunca acepta `empresa_id` del dispositivo.

## Inventario y pertenencia empresarial

| Tabla | Estrategia |
|---|---|
| `empresas` | Catálogo global de tenants |
| `users` | `empresa_id` nullable; nulo solo para cuentas globales |
| `roles` | Catálogo global con `codigo` y `ambito` (`GLOBAL`/`EMPRESA`) |
| `areas` | `empresa_id` directo; nombre único por empresa |
| `trabajadores` | `empresa_id` directo; DNI y código biométrico únicos por empresa |
| `horarios` | `empresa_id` directo; nombre único por empresa |
| `trabajador_feriados` | `empresa_id` directo |
| `marcaciones` | `empresa_id` y `dispositivo_id` directos; deduplicación por dispositivo |
| `asistencias` | `empresa_id` directo |
| `asignaciones` | `empresa_id` directo |
| `solicitudes` | `empresa_id` directo |
| `vacaciones` | `empresa_id` directo |
| `reportes_avance` | `empresa_id` directo |
| `pendientes_avance` | `empresa_id` directo porque puede existir sin reporte |
| `marcaciones_campo` | `empresa_id` directo |
| `dispositivos_biometricos` | `empresa_id` directo; serial único global |
| `auditoria` | `empresa_id` nullable para admitir eventos globales |
| `area_encargados` | Hereda de área y trabajador; se valida coincidencia |
| `horario_dias` | Hereda de horario |
| `trabajador_horarios` | Hereda de trabajador y horario; se valida coincidencia |
| `reprogramaciones_vacaciones` | Hereda de vacaciones |
| `actividades_reporte` | Hereda de reporte; asignación de campo debe coincidir |
| `biometrico_usuarios` | Hereda de dispositivo; trabajador debe coincidir |
| `biometrico_comandos` | Hereda de dispositivo |

## Estrategia de aislamiento

1. Middleware `empresa` carga la empresa del usuario y bloquea cuentas globales, empresas suspendidas/inactivas y usuarios sin empresa.
2. Scope Eloquent `EmpresaScope` filtra modelos raíz y el trait impide guardar un `empresa_id` diferente del contexto.
3. El route model binding hereda el scope, por lo que un ID de otra empresa responde como no encontrado.
4. Reglas `Rule::exists`/`Rule::unique` incluyen `empresa_id`; las transacciones vuelven a comprobar relaciones críticas.
5. Consultas con joins, Query Builder, exportaciones y dashboards añaden el filtro empresarial explícito cuando un scope Eloquent no puede aplicarse.
6. Los archivos se sirven únicamente después de autorizar la entidad empresarial propietaria.
7. Los procesos biométricos se ejecutan dentro del contexto obtenido del dispositivo registrado.

## Cambios por módulo

- Autenticación: comprobar cuenta, ámbito de rol y estado empresarial; redirección separada para superadministración.
- Empresas: CRUD, estados, búsqueda, paginación, administradores e invitación segura mediante token de restablecimiento de un solo uso.
- Trabajadores, áreas y horarios: catálogos y validaciones únicos dentro de la empresa.
- Asistencia, solicitudes, vacaciones, campo y reportes: scope empresarial, relaciones cruzadas rechazadas y exportaciones aisladas.
- Biométricos: dispositivos pre-registrados, empresa derivada del serial, PIN resuelto dentro de empresa y eventos idempotentes por dispositivo.
- Auditoría: eventos globales y empresariales con contexto, IP y agente de usuario.

## Riesgos identificados

- El protocolo iClock/SenseFace existente identifica normalmente al equipo por serial. Si el firmware no permite enviar un secreto adicional, el serial funciona como credencial y debe protegerse con red privada, allowlist o VPN. La implementación admite un secreto opcional sin confiar en `empresa_id`.
- Las autorizaciones heredadas fueron cambiadas a códigos estables y middleware; los nombres de rol quedan solo para presentación.
- Las migraciones históricas del 21/10/2026 alteran horarios activos de forma global. Ya están ejecutadas en la base local y no se reutilizarán para configuración SaaS; una instalación nueva las ejecuta antes de crear tenants, cuando no existen horarios.
- MySQL no garantiza por sí solo que dos claves foráneas simples compartan `empresa_id`; la aplicación valida las combinaciones y las pruebas cubren intentos cruzados.
- Correos, colas, scheduler, HTTPS y permisos de almacenamiento dependen del servidor y requieren verificación manual previa al despliegue.

## Revisión de datos previa a la migración

La migración `2026_11_10_000001_create_multiempresa_structure` ahora contempla la transición explícita de una base histórica de una sola empresa cuando se configuran los datos de la empresa inicial. Antes de migrar otro entorno:

1. Obtener una copia de seguridad de la base de datos y verificar que puede restaurarse en un entorno aislado.
2. Ejecutar `php artisan saas:preflight` con acceso a la misma base que se piensa migrar.
3. Revisar las filas sin empresa, cuentas no vinculadas a trabajadores, relaciones huérfanas, empresas inexistentes y relaciones entre empresas distintas.
4. Acordar explícitamente la razón social/RUC de la empresa inicial y clasificar cada hallazgo ambiguo. No editar ni ejecutar una migración de asignación hasta resolverlo.
5. Repetir el diagnóstico después de una migración de transición y comparar conteos e integridad antes de habilitar tráfico.

`saas:preflight` solo lee y muestra cantidades; no imprime datos personales ni altera el esquema. Un resultado limpio no acredita el respaldo ni sustituye la revisión funcional.

### Verificación de datos y fuente histórica (12/11/2026)

El respaldo `127_0_0_1.sql` se integró en la base local `bdasistencias`. La migración asignó los registros históricos a Connext (RUC `20625474994`) y conservó sus claves y relaciones existentes. El resumen posterior a la migración: 12 usuarios históricos, 11 trabajadores, 5 áreas, 1 horario, 17 feriados, 351 marcaciones, 247 asistencias, 7 asignaciones, 1 solicitud, 11 reportes, 6 pendientes, 11 marcaciones de campo, 1 dispositivo biométrico, 12 usuarios biométricos y 29 comandos. Se agregó una cuenta global SUPERADMIN, sin empresa asignada.

El usuario administrador histórico de Connext es `connextventas@gmail.com`; la contraseña `password` coincide con el hash existente en el respaldo. La cuenta global se configura localmente con `SUPERADMIN_EMAIL` y `SUPERADMIN_PASSWORD`; no se deben reutilizar estas credenciales temporales en producción. La base local conserva los históricos; no se volvió a importar el dump sobre tablas existentes.

## Lista de tareas y estado

### Fase 1 — Base de datos

- [x] Inventario inicial de migraciones, modelos, rutas, servicios y estado de la base.
- [x] Crear `empresas`, campos de tenant, roles con ámbito e índices compuestos.
- [x] Adaptar modelos, relaciones, factories y seeders.
- [x] Verificar migraciones desde una base SQLite temporal y MySQL local sin borrar datos.

### Fase 2 — Autenticación y aislamiento

- [x] Contexto empresarial, scope y middleware.
- [x] Autorización por códigos de rol.
- [x] Validaciones de relaciones y protección de IDs.
- [x] Adaptar consultas, joins, descargas, exports, servicios y comandos.

### Fase 3 — Superadministración

- [x] Dashboard global.
- [x] CRUD y estados de empresas.
- [x] Administradores e invitaciones seguras.
- [x] Gestión y estado de dispositivos.
- [x] Auditoría global y navegación responsive.

### Fase 4 — Módulos existentes

- [x] Trabajadores, áreas y horarios.
- [x] Marcaciones y asistencias.
- [x] Solicitudes, vacaciones y campo.
- [x] Reportes, dashboards y archivos privados por empresa.

### Fase 5 — Biométricos

- [x] Asignación por empresa y serial global.
- [x] Contexto derivado del dispositivo.
- [x] PIN por dispositivo/empresa e idempotencia.
- [x] Comandos y reconciliación aislados.

### Fase 6 — Seguridad, pruebas y producción

- [x] Pruebas de dos empresas y superadministración.
- [x] Pruebas de rutas, IDs, relaciones y biométricos.
- [x] Revisión de consultas directas e índices empresariales.
- [x] Guía de despliegue y pasos manuales.

## Decisiones de migración

La base local contenía el histórico de una sola empresa. La migración se ejecutó con Connext configurada como empresa inicial y asignó los registros existentes preservando sus relaciones. No se ejecutó `migrate:fresh`, `db:wipe` ni `migrate:reset`.

## Verificación final

- Migración `2026_11_10_000001_create_multiempresa_structure` aplicada en MySQL local.
- Diagnóstico `saas:preflight` ejecutado después de la migración: una empresa, datos históricos presentes y relaciones sin huérfanos; 1 usuario sin empresa corresponde al superadministrador global.
- Sintaxis PHP revisada en los archivos modificados.
- `git diff --check` sin errores de espacios; Git informó solo cambios de fin de línea en dos hojas CSS preexistentes.
- La suite automatizada no pudo arrancar en este entorno: PHPUnit informó que no puede abrir `vendor/autoload.php`. No se reportan pruebas automatizadas como aprobadas.
- Archivos sensibles movidos del disco público al disco privado `local`, con acceso por controladores autorizados.

## Acciones manuales pendientes del despliegue

- Configurar SMTP real y comprobar recepción, vencimiento y uso único de la invitación.
- Definir temporalmente `SUPERADMIN_EMAIL` y `SUPERADMIN_PASSWORD`, ejecutar el seed y retirar la contraseña inicial del entorno después de cambiarla.
- Configurar HTTPS, cookies seguras, worker, scheduler, permisos y backups según `docs/DESPLIEGUE_SAAS.md`.
- Confirmar si cada firmware biométrico admite `X-Device-Token` o `AUTH`; cuando no lo admita, proteger el endpoint con VPN/allowlist.
- Ejecutar una prueba física de cada modelo de dispositivo y una prueba de envío real de correo antes de producción.

