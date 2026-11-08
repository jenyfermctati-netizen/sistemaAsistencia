<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Empresa;
use App\Models\Horario;
use App\Tenancy\EmpresaActual;
use App\Tenancy\TablasEmpresa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigracionMultiempresaTest extends TestCase
{
    public function test_los_seeders_asignan_datos_y_acceso_a_la_empresa_inicial(): void
    {
        $anterior = DB::getDefaultConnection();
        config(['database.connections.seeders' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('seeders');
        Schema::clearResolvedInstance('db.schema');
        try {
            foreach (glob(database_path('migrations/*.php')) as $file) {
                (require $file)->up();
            }
            $this->seed();
            $empresa = Empresa::firstOrFail();
            $this->assertDatabaseHas('empresa_usuario', ['empresa_id' => $empresa->id, 'estado' => true]);
            app(EmpresaActual::class)->ejecutar($empresa, function () {
                $this->assertSame(5, Area::count());
                $this->assertSame(1, Horario::count());
            });
        } finally {
            DB::disconnect('seeders');
            DB::setDefaultConnection($anterior);
            Schema::clearResolvedInstance('db.schema');
        }
    }

    public function test_la_actualizacion_conserva_registros_y_crea_membresias_y_admite_reversion(): void
    {
        $anterior = DB::getDefaultConnection();
        config(['database.connections.actualizacion' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        DB::setDefaultConnection('actualizacion');
        Schema::clearResolvedInstance('db.schema');

        try {
            foreach (glob(database_path('migrations/*.php')) as $file) {
                if (basename($file) < '2026_11_08') {
                    (require $file)->up();
                }
            }
            $rol = DB::table('roles')->insertGetId(['nombre' => 'ADMINISTRADOR']);
            $area = DB::table('areas')->insertGetId(['nombre' => 'Área histórica']);
            $trabajador = DB::table('trabajadores')->insertGetId([
                'area_id' => $area, 'dni' => '12345678', 'nombres' => 'Histórico',
                'apellidos' => 'Original', 'tipo_vinculo' => 'CONTRATADO',
            ]);
            $user = DB::table('users')->insertGetId([
                'rol_id' => $rol, 'trabajador_id' => $trabajador, 'name' => 'Usuario histórico',
                'email' => 'historico@example.test', 'password' => 'hash-original', 'estado' => false,
            ]);
            $marcacion = DB::table('marcaciones')->insertGetId([
                'trabajador_id' => $trabajador, 'fecha_hora' => '2026-11-01 08:20:00',
                'tipo' => 'ENTRADA', 'origen' => 'MANUAL', 'registrado_por' => $user,
            ]);
            $migraciones = [];
            foreach (glob(database_path('migrations/2026_11_08*.php')) as $file) {
                $migracion = require $file;
                $migracion->up();
                $migraciones[] = $migracion;
            }
            $empresaId = DB::table('empresas')->value('id');
            $this->assertDatabaseHas('empresa_usuario', [
                'empresa_id' => $empresaId, 'user_id' => $user, 'rol_id' => $rol,
                'trabajador_id' => $trabajador, 'estado' => false,
            ]);
            $this->assertDatabaseHas('users', ['id' => $user, 'password' => 'hash-original', 'estado' => false]);
            $this->assertDatabaseHas('marcaciones', ['id' => $marcacion, 'empresa_id' => $empresaId, 'registrado_por' => $user]);
            $this->assertDatabaseHas('trabajadores', ['id' => $trabajador, 'empresa_id' => $empresaId, 'area_id' => $area]);
            foreach (TablasEmpresa::NOMBRES as $tabla) {
                $column = collect(Schema::getColumns($tabla))->firstWhere('name', 'empresa_id');
                $this->assertNotNull($column, $tabla);
                $this->assertFalse($column['nullable'], $tabla);
                $this->assertSame(0, DB::table($tabla)->whereNull('empresa_id')->count());
            }
            foreach (array_reverse($migraciones) as $migracion) {
                $migracion->down();
            }
            $this->assertFalse(Schema::hasTable('empresa_usuario'));
            $this->assertFalse(Schema::hasColumn('trabajadores', 'empresa_id'));
            $this->assertDatabaseHas('marcaciones', ['id' => $marcacion, 'trabajador_id' => $trabajador]);
        } finally {
            DB::disconnect('actualizacion');
            DB::setDefaultConnection($anterior);
            Schema::clearResolvedInstance('db.schema');
        }
    }
}
