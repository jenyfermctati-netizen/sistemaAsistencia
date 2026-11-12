<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaasPreflightCommand extends Command
{
    protected $signature = 'saas:preflight';

    protected $description = 'Revisa el esquema y la integridad multiempresa sin modificar la base de datos';

    /** @var array<string, int> */
    private array $unresolved = [];

    public function handle(): int
    {
        $this->components->info('Diagnóstico SaaS de solo lectura');
        $this->line('Conexión: '.DB::connection()->getName().' ('.DB::connection()->getDriverName().')');

        $tables = [
            'users', 'areas', 'trabajadores', 'horarios', 'trabajador_feriados',
            'marcaciones', 'asistencias', 'asignaciones', 'solicitudes', 'vacaciones',
            'reportes_avance', 'pendientes_avance', 'marcaciones_campo',
            'dispositivos_biometricos', 'auditoria',
        ];
        $rows = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $rows[] = [$table, 'no existe', '—', '—'];

                continue;
            }

            $count = DB::table($table)->count();
            $companyColumn = Schema::hasColumn($table, 'empresa_id');
            $unassigned = $companyColumn
                ? DB::table($table)->whereNull('empresa_id')->count()
                : null;
            $coverage = $companyColumn
                ? ($unassigned === 0 ? 'completo' : "{$unassigned} sin empresa")
                : ($count === 0 ? 'pendiente' : 'esquema legado');

            if ($count > 0 && ! $companyColumn && $table !== 'auditoria') {
                $this->unresolved[$table.'.sin_empresa_id'] = $count;
            }
            if ($unassigned > 0 && $table !== 'users' && $table !== 'auditoria') {
                $this->unresolved[$table.'.empresa_id_nulo'] = $unassigned;
            }

            $rows[] = [$table, (string) $count, $companyColumn ? 'sí' : 'no', $coverage];
        }

        $this->table(['Tabla', 'Filas', 'empresa_id', 'Cobertura'], $rows);
        $this->revisarEmpresas();
        $this->revisarRoles();
        $this->revisarRelaciones();
        $this->revisarUsuarios();
        $this->revisarTablaRelacionEmpresa();

        if ($this->unresolved !== []) {
            $issues = [];
            foreach ($this->unresolved as $issue => $count) {
                $issues[] = [$issue, (string) $count];
            }
            $this->newLine();
            $this->components->warn('Se encontraron filas que requieren clasificación antes de una migración de datos.');
            $this->table(['Hallazgo', 'Cantidad'], $issues);
            $this->line('No se realizó ningún cambio. Revise el respaldo y defina explícitamente la empresa propietaria antes de migrar.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('No se encontraron filas pendientes en las comprobaciones disponibles.');
        $this->line('Este resultado no sustituye un respaldo verificable ni autoriza ejecutar migraciones.');

        return self::SUCCESS;
    }

    private function revisarEmpresas(): void
    {
        if (! Schema::hasTable('empresas')) {
            $this->components->twoColumnDetail('Tabla empresas', 'aún no existe');

            return;
        }

        $this->components->twoColumnDetail('Empresas registradas', (string) DB::table('empresas')->count());
        $hallazgos = [];

        foreach ([
            'users', 'areas', 'trabajadores', 'horarios', 'trabajador_feriados',
            'marcaciones', 'asistencias', 'asignaciones', 'solicitudes', 'vacaciones',
            'reportes_avance', 'pendientes_avance', 'marcaciones_campo',
            'dispositivos_biometricos', 'auditoria',
        ] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'empresa_id')) {
                continue;
            }

            $count = DB::table($table.' as tenant')
                ->leftJoin('empresas', 'tenant.empresa_id', '=', 'empresas.id')
                ->whereNotNull('tenant.empresa_id')
                ->whereNull('empresas.id')
                ->count();

            if ($count > 0) {
                $key = $table.'.empresa_inexistente';
                $this->unresolved[$key] = $count;
                $hallazgos[] = [$key, (string) $count];
            }
        }

        $this->components->twoColumnDetail('Referencias a empresas inexistentes', $hallazgos === [] ? 'ninguna' : 'requieren revisión');
        if ($hallazgos !== []) {
            $this->table(['Tabla', 'Cantidad'], $hallazgos);
        }
    }

    private function revisarRoles(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'nombre')) {
            return;
        }

        $nombres = DB::table('roles')->pluck('nombre');
        $duplicados = $nombres->map(fn ($nombre) => mb_strtoupper(trim((string) $nombre)))
            ->countBy()
            ->filter(fn (int $cantidad) => $cantidad > 1);

        if ($duplicados->isNotEmpty()) {
            $this->unresolved['roles.codigo_normalizado_duplicado'] = $duplicados->count();
            $this->components->warn("Hay {$duplicados->count()} grupos de roles cuyos nombres colisionan al normalizarse a código.");
        } else {
            $this->components->twoColumnDetail('Colisiones de código de rol', 'ninguna');
        }
    }

    private function revisarRelaciones(): void
    {
        $relaciones = [
            ['trabajadores', 'area_id', 'areas'],
            ['asistencias', 'trabajador_id', 'trabajadores'],
            ['marcaciones', 'trabajador_id', 'trabajadores'],
            ['asignaciones', 'trabajador_id', 'trabajadores'],
            ['solicitudes', 'trabajador_id', 'trabajadores'],
            ['vacaciones', 'trabajador_id', 'trabajadores'],
            ['reportes_avance', 'trabajador_id', 'trabajadores'],
            ['marcaciones_campo', 'trabajador_id', 'trabajadores'],
        ];
        $hallazgos = [];

        foreach ($relaciones as [$table, $foreignKey, $parent]) {
            if (! Schema::hasTable($table) || ! Schema::hasTable($parent)
                || ! Schema::hasColumn($table, $foreignKey)) {
                continue;
            }

            $orphans = DB::table($table.' as child')
                ->leftJoin($parent.' as parent', 'child.'.$foreignKey, '=', 'parent.id')
                ->whereNotNull('child.'.$foreignKey)
                ->whereNull('parent.id')
                ->count();

            if ($orphans > 0) {
                $key = $table.'.'.$foreignKey.'_sin_'.$parent;
                $this->unresolved[$key] = $orphans;
                $hallazgos[] = [$key, (string) $orphans];
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('Relaciones huérfanas', $hallazgos === [] ? 'ninguna' : 'requieren revisión');
        if ($hallazgos !== []) {
            $this->table(['Relación', 'Cantidad'], $hallazgos);
        }

        $mismatches = [
            ['trabajadores', 'area_id', 'areas'],
            ['users', 'trabajador_id', 'trabajadores'],
            ['asistencias', 'trabajador_id', 'trabajadores'],
            ['marcaciones', 'trabajador_id', 'trabajadores'],
            ['asignaciones', 'trabajador_id', 'trabajadores'],
            ['solicitudes', 'trabajador_id', 'trabajadores'],
            ['vacaciones', 'trabajador_id', 'trabajadores'],
            ['reportes_avance', 'trabajador_id', 'trabajadores'],
            ['marcaciones_campo', 'trabajador_id', 'trabajadores'],
        ];
        $crossTenant = [];

        foreach ($mismatches as [$table, $foreignKey, $parent]) {
            if (! Schema::hasTable($table) || ! Schema::hasTable($parent)
                || ! Schema::hasColumn($table, 'empresa_id')
                || ! Schema::hasColumn($parent, 'empresa_id')
                || ! Schema::hasColumn($table, $foreignKey)) {
                continue;
            }

            $count = DB::table($table.' as child')
                ->join($parent.' as parent', 'child.'.$foreignKey, '=', 'parent.id')
                ->whereColumn('child.empresa_id', '!=', 'parent.empresa_id')
                ->count();
            if ($count > 0) {
                $key = $table.'.'.$foreignKey.'_empresa_distinta';
                $this->unresolved[$key] = $count;
                $crossTenant[] = [$key, (string) $count];
            }
        }

        if ($crossTenant !== []) {
            $this->newLine();
            $this->components->warn('Relaciones entre empresas distintas');
            $this->table(['Relación', 'Cantidad'], $crossTenant);
        }
    }

    private function revisarUsuarios(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'empresa_id')) {
            $this->components->twoColumnDetail('Columnas users', implode(', ', Schema::getColumnListing('users')));
            if (Schema::hasTable('roles')) {
                $this->components->twoColumnDetail('Columnas roles', implode(', ', Schema::getColumnListing('roles')));
            }

            if (Schema::hasColumn('users', 'trabajador_id') && Schema::hasTable('trabajadores')) {
                $linked = DB::table('users')->whereNotNull('trabajador_id')->count();
                $unlinked = DB::table('users')->whereNull('trabajador_id')->count();
                $this->components->twoColumnDetail('Usuarios legados vinculados a trabajador', (string) $linked);
                $this->components->twoColumnDetail('Usuarios legados sin trabajador (empresa requiere clasificación)', (string) $unlinked);
                if ($unlinked > 0) {
                    $this->unresolved['usuarios_sin_trabajador.empresa_por_definir'] = $unlinked;
                }
            }

            if (Schema::hasColumn('users', 'rol_id') && Schema::hasTable('roles')
                && Schema::hasColumn('roles', 'nombre')) {
                $resumenRoles = DB::table('users')
                    ->leftJoin('roles', 'roles.id', '=', 'users.rol_id')
                    ->selectRaw('COALESCE(roles.nombre, \'[rol inexistente]\') as rol, users.estado, COUNT(*) as cantidad')
                    ->groupBy('roles.nombre', 'users.estado')
                    ->get()
                    ->map(fn ($fila) => [$fila->rol, (string) $fila->estado, (string) $fila->cantidad])
                    ->all();

                if ($resumenRoles !== []) {
                    $this->table(['Rol', 'Estado', 'Cuentas'], $resumenRoles);
                }
            }

            return;
        }

        if (! Schema::hasTable('roles') || ! Schema::hasColumn('users', 'rol_id')
            || ! Schema::hasColumn('roles', 'ambito')) {
            return;
        }

        $sinEmpresa = DB::table('users')->join('roles', 'roles.id', '=', 'users.rol_id')
            ->where('roles.ambito', 'EMPRESA')->whereNull('users.empresa_id')->count();
        if ($sinEmpresa > 0) {
            $this->unresolved['usuarios_empresa.sin_empresa'] = $sinEmpresa;
        }

        if (Schema::hasColumn('users', 'empresa_id')) {
            $globalConEmpresa = DB::table('users')->join('roles', 'roles.id', '=', 'users.rol_id')
                ->where('roles.ambito', 'GLOBAL')->whereNotNull('users.empresa_id')->count();
            if ($globalConEmpresa > 0) {
                $this->unresolved['usuarios_globales.con_empresa'] = $globalConEmpresa;
            }
        }
    }

    private function revisarTablaRelacionEmpresa(): void
    {
        foreach (['empresa_usuario', 'empresa_user'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $count = DB::table($table)->count();
            $this->components->twoColumnDetail("Relación {$table}", "{$count} vínculos; revisar antes de sustituirla");

            if ($count > 0) {
                $this->unresolved[$table.'.vinculos_por_clasificar'] = $count;
            }
        }
    }
}
