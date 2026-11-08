<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Los nombres de las restricciones forman parte del contrato de esta migración.
    private const TABLES = [
        'areas', 'trabajadores', 'area_encargados', 'horarios', 'horario_dias',
        'trabajador_horarios', 'trabajador_feriados', 'marcaciones', 'asistencias',
        'asignaciones', 'solicitudes', 'vacaciones', 'reprogramaciones_vacaciones',
        'reportes_avance', 'auditoria', 'actividades_reporte', 'pendientes_avance',
        'dispositivos_biometricos', 'biometrico_usuarios', 'biometrico_comandos',
        'marcaciones_campo',
    ];

    private const UNIQUES = [
        'areas' => ['nombre'], 'trabajadores' => ['dni', 'codigo_biometrico'],
        'horarios' => ['nombre'],
    ];

    public function up(): void
    {
        Schema::table('dispositivos_biometricos', fn (Blueprint $table) => $table->string('token_hash', 64)->nullable());
        $empresaId = DB::table('empresas')->orderBy('id')->value('id');
        foreach (self::TABLES as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('empresa_id')->nullable()->constrained('empresas')->restrictOnDelete());
            DB::table($name)->update(['empresa_id' => $empresaId]);
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->unsignedBigInteger('empresa_id')->nullable(false)->change();
                $table->unique(['empresa_id', 'id'], 'uq_'.$name.'_empresa_id');
            });
        }

        $serialLegacy = trim((string) config('services.senseface.serial'));
        if ($serialLegacy !== '' && ! DB::table('dispositivos_biometricos')->where('serial', $serialLegacy)->exists()) {
            DB::table('dispositivos_biometricos')->insert([
                'empresa_id' => $empresaId, 'nombre' => 'SenseFace Principal',
                'serial' => $serialLegacy, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach (self::UNIQUES as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dropUnique([$column]);
                    $table->unique(['empresa_id', $column]);
                }
            });
        }

        // Una FK simple no impide asociar registros de dos empresas diferentes.
        foreach (self::TABLES as $name) {
            $foreignKeys = Schema::getForeignKeys($name);
            foreach ($foreignKeys as $fk) {
                if (count($fk['columns']) !== 1 || ! in_array($fk['foreign_table'], self::TABLES, true)) {
                    continue;
                }
                $column = $fk['columns'][0];
                Schema::table($name, function (Blueprint $table) use ($name, $fk, $column) {
                    $table->foreign(['empresa_id', $column], 'te_'.$name.'_'.$column)
                        ->references(['empresa_id', 'id'])->on($fk['foreign_table'])->restrictOnDelete()->restrictOnUpdate();
                });
            }
        }
    }

    public function down(): void
    {
        // Evita un rollback parcial cuando los nuevos datos ya no caben en el esquema anterior.
        if (DB::table('empresas')->count() > 1) {
            throw new RuntimeException('No se puede retirar el aislamiento mientras existan varias empresas.');
        }
        foreach (array_reverse(self::TABLES) as $name) {
            foreach (Schema::getForeignKeys($name) as $fk) {
                if (count($fk['columns']) === 2 && $fk['columns'][0] === 'empresa_id') {
                    Schema::table($name, fn (Blueprint $table) => $table->dropForeign(
                        DB::getDriverName() === 'sqlite' ? $fk['columns'] : $fk['name']
                    ));
                }
            }
        }
        foreach (self::UNIQUES as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dropUnique(['empresa_id', $column]);
                    $table->unique($column);
                }
            });
        }
        foreach (array_reverse(self::TABLES) as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropUnique('uq_'.$name.'_empresa_id');
                $table->dropConstrainedForeignId('empresa_id');
            });
        }
        Schema::table('dispositivos_biometricos', fn (Blueprint $table) => $table->dropColumn('token_hash'));
    }
};
