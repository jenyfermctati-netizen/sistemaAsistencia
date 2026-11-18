# Administrador único y Recursos Humanos

Cada empresa nueva se crea junto con un administrador activo. El alta se guarda en una transacción y la invitación se envía después; un fallo de correo permite reenviar la invitación sin duplicar cuentas.

## Permisos

`ADMINISTRADOR` controla los módulos de su empresa. El superadministrador conserva la gestión global, la suspensión de empresas y el registro de dispositivos.

`RECURSOS_HUMANOS` puede gestionar trabajadores comunes, horarios, feriados, asistencia, marcaciones manuales con motivo, solicitudes y vacaciones. Consulta reportes y asignaciones de campo, y revisa marcaciones remotas. No puede revisar sus propias solicitudes, vacaciones o marcaciones de campo. Tampoco puede reprogramar sus propias vacaciones aprobadas.

RR. HH. solo puede asignar `TRABAJADOR`. Las cuentas privilegiadas y los encargados de área se gestionan desde administración. La consulta de áreas se ofrece en los selectores de personal y filtros; la creación y modificación de áreas quedan reservadas al administrador.

RR. HH. puede consultar, aprobar y rechazar con observación los reportes de avance de otros trabajadores de su empresa. El rechazo conserva el estado OBSERVADO para permitir correcciones; la aprobación usa REVISADO. No puede revisar reportes propios ni gestionar pendientes de avance. No puede crear o cancelar asignaciones de campo, anular marcaciones, sincronizar biométricos, acceder a la auditoría empresarial ni cambiar la configuración o los roles privilegiados. Se mantienen los flujos existentes de gerentes y trabajadores.

`PermisosEmpresa` centraliza las capacidades compartidas. El middleware `rrhh` usa una lista explícita de rutas y deniega por defecto las rutas nuevas a RR. HH. Los controladores comprueban además titularidad y acciones sensibles. Los filtros de empresa existentes siguen aplicándose a registros, archivos y exportaciones.

## Administrador único

La migración `2026_11_18_000001_add_recursos_humanos_and_unique_administrator` añade el rol y una reserva única `users.administrador_empresa_id`. El modelo deriva esa reserva del rol y la empresa; el campo no admite asignación masiva. Su índice único evita que dos escrituras de la aplicación reserven la administración de la misma empresa. No se deben modificar roles por SQL directo: se omitirían las reglas del modelo.

El modelo bloquea la baja, eliminación y cambio de rol/empresa del administrador. El formulario de personal no ofrece el rol administrador. El alta desde superadministración y la transferencia bloquean la fila empresarial dentro de una transacción.

La transferencia está en **Configuración y accesos**, requiere contraseña actual y un usuario activo de la misma empresa. El antiguo administrador pasa a trabajador si tiene ficha laboral; si no, queda inactivo. Se conserva la cuenta y su historial. El administrador que transfiere cierra su sesión. El superadministrador dispone del mismo flujo en la ficha de empresa para recuperación.

## Activación en otros entornos

1. Revisar administradores actuales. La migración se detiene antes de cambiar el esquema si encuentra varias cuentas con rol administrador en una empresa. No elige titulares ni reasigna personas automáticamente.
2. Resolver los duplicados con una decisión explícita del responsable y dejar constancia en auditoría.
3. Aplicar la migración en una ventana de mantenimiento; desplegar código y esquema conjuntamente.
4. Comprobar inicio de sesión, invitación por correo y permisos de ambas cuentas.

En la base local, por decisión del usuario, Connext conserva la administración y Mariell pasa a Recursos Humanos. Se registró `REASIGNAR_ROL_AUTORIZADO` antes de aplicar la migración. No se eliminó ninguna cuenta.

## Verificación

Ejecutar `php -d extension=gd vendor/phpunit/phpunit/phpunit --configuration phpunit.xml`. La suite usa SQLite en memoria. En Windows puede necesitar permisos normales de lectura porque `is_readable()` de PHPUnit falla dentro del sandbox, aunque `require` funcione. GD se habilita solo para ese proceso, sin cambiar `php.ini`.

Las pruebas cubren accesos permitidos y prohibidos, elevación de privilegios, revisión propia, marcación manual justificada, aislamiento empresarial, transferencia con contraseña y mismo tenant, unicidad y configuración empresarial.
