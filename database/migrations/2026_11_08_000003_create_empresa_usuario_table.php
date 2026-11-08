<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'areas', 'trabajadores', 'area_encargados', 'horarios', 'horario_dias',
        'trabajador_horarios', 'trabajador_feriados', 'marcaciones', 'asistencias',
        'asignaciones', 'solicitudes', 'vacaciones', 'reprogramaciones_vacaciones',
        'reportes_avance', 'auditoria', 'actividades_reporte', 'pendientes_avance',
        'dispositivos_biometricos', 'biometrico_usuarios', 'biometrico_comandos',
        'marcaciones_campo',
    ];

    public function up(): void
    {
        Schema::create('empresa_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('rol_id')->constrained('roles')->restrictOnDelete();
            $table->foreignId('trabajador_id')->nullable()->constrained('trabajadores')->restrictOnDelete();
            $table->boolean('estado')->default(true);
            $table->timestamps();
            $table->unique(['empresa_id', 'user_id']);
            $table->unique(['empresa_id', 'trabajador_id']);
            $table->foreign(['empresa_id', 'trabajador_id'], 'te_membresia_trabajador')
                ->references(['empresa_id', 'id'])->on('trabajadores')->restrictOnDelete();
        });

        $empresaId = DB::table('empresas')->orderBy('id')->value('id');
        DB::table('users')->orderBy('id')->chunkById(200, function ($users) use ($empresaId) {
            foreach ($users as $user) {
                DB::table('empresa_usuario')->insert([
                    'empresa_id' => $empresaId, 'user_id' => $user->id,
                    'rol_id' => $user->rol_id, 'trabajador_id' => $user->trabajador_id,
                    'estado' => $user->estado, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        // Los autores y revisores también deben pertenecer a la empresa del registro.
        foreach (self::TABLES as $name) {
            foreach (Schema::getForeignKeys($name) as $fk) {
                if ($fk['foreign_table'] !== 'users' || count($fk['columns']) !== 1) {
                    continue;
                }
                $column = $fk['columns'][0];
                Schema::table($name, fn (Blueprint $table) => $table
                    ->foreign(['empresa_id', $column], 'tu_'.$name.'_'.$column)
                    ->references(['empresa_id', 'user_id'])->on('empresa_usuario')->restrictOnDelete());
            }
        }
    }

    public function down(): void
    {
        if (DB::table('empresas')->count() > 1) {
            throw new RuntimeException('No se pueden retirar las membresías mientras existan varias empresas.');
        }
        foreach (self::TABLES as $name) {
            foreach (Schema::getForeignKeys($name) as $fk) {
                if ($fk['foreign_table'] === 'empresa_usuario') {
                    Schema::table($name, fn (Blueprint $table) => $table->dropForeign(
                        DB::getDriverName() === 'sqlite' ? $fk['columns'] : $fk['name']
                    ));
                }
            }
        }
        Schema::dropIfExists('empresa_usuario');
    }
};
