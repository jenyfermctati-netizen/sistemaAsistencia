# Implementación SaaS multiempresa de CONNEXT

## Estado general

Implementación completada y verificada sobre Laravel 12, PHP 8.2 y MySQL. La base local fue verificada el 10/11/2026: las tablas operativas estaban vacías, la migración SaaS se aplicó correctamente y se sembraron únicamente los roles base. No se ejecutaron operaciones destructivas.

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

La base inspeccionada no contiene registros operativos, por lo que los campos empresariales obligatorios pueden agregarse sin crear una empresa ficticia ni modificar datos. No se ejecutará `migrate:fresh`, `db:wipe` ni `migrate:reset`. En una instalación distinta que sí contenga datos, se debe abortar la migración y asignar explícitamente una empresa de transición; no se realizará una asignación silenciosa.

## Verificación final

- Migración `2026_11_10_000001_create_multiempresa_structure` aplicada en MySQL local.
- Migraciones completas ejecutadas repetidamente en SQLite en memoria mediante `RefreshDatabase`.
- 21 pruebas automatizadas aprobadas, con 165 aserciones.
- Sintaxis PHP revisada en `app`, `database`, `routes` y `tests`.
- 105 rutas compiladas correctamente y vistas Blade almacenadas en caché sin errores.
- Archivos sensibles movidos del disco público al disco privado `local`, con acceso por controladores autorizados.

## Acciones manuales pendientes del despliegue

- Configurar SMTP real y comprobar recepción, vencimiento y uso único de la invitación.
- Definir temporalmente `SUPERADMIN_EMAIL` y `SUPERADMIN_PASSWORD`, ejecutar el seed y retirar la contraseña inicial del entorno después de cambiarla.
- Configurar HTTPS, cookies seguras, worker, scheduler, permisos y backups según `docs/DESPLIEGUE_SAAS.md`.
- Confirmar si cada firmware biométrico admite `X-Device-Token` o `AUTH`; cuando no lo admita, proteger el endpoint con VPN/allowlist.
- Ejecutar una prueba física de cada modelo de dispositivo y una prueba de envío real de correo antes de producción.

