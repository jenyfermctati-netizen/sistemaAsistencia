<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las tablas operativas deben estar vacías para evitar asignar datos históricos
     * a una empresa ficticia. Una instalación con datos debe ejecutar primero una
     * migración de transición revisada por un operador.
     */
    public function up(): void
    {
        $tablasEmpresariales = [
            'users',
            'areas',
            'trabajadores',
            'horarios',
            'trabajador_feriados',
            'marcaciones',
            'asistencias',
            'asignaciones',
            'solicitudes',
            'vacaciones',
            'reportes_avance',
            'pendientes_avance',
            'dispositivos_biometricos',
            'marcaciones_campo',
            'auditoria',
        ];

        foreach ($tablasEmpresariales as $tabla) {
            if (Schema::hasTable($tabla) && DB::table($tabla)->exists()) {
                throw new RuntimeException(
                    "La tabla {$tabla} contiene datos. Asigna una empresa de transición antes de ejecutar la migración SaaS."
                );
            }
        }

        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('razon_social', 200);
            $table->string('nombre_comercial', 200)->nullable();
            $table->string('ruc', 11)->unique();
            $table->string('email')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('direccion', 500)->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('estado', 20)->default('ACTIVA');
            $table->string('zona_horaria', 64)->default('America/Lima');
            $table->timestamps();

            $table->index(['estado', 'created_at'], 'ix_empresas_estado_fecha');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable()->after('id');
            $table->string('ambito', 20)->default('EMPRESA')->after('codigo');
            $table->unique('codigo', 'uq_roles_codigo');
            $table->index(['ambito', 'estado'], 'ix_roles_ambito_estado');
        });

        DB::table('roles')->orderBy('id')->each(function (object $rol): void {
            DB::table('roles')->where('id', $rol->id)->update([
                'codigo' => strtoupper(trim((string) $rol->nombre)),
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')
                ->constrained('empresas')->restrictOnDelete();
            $table->index(['empresa_id', 'estado'], 'ix_users_empresa_estado');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->foreignId('empresa_id')->after('id')
                ->constrained('empresas')->restrictOnDelete();
            $table->unique(['empresa_id', 'nombre'], 'uq_areas_empresa_nombre');
            $table->index(['empresa_id', 'estado'], 'ix_areas_empresa_estado');
        });

        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropUnique(['codigo_biometrico']);
            $table->dropUnique(['dni']);
            $table->foreignId('empresa_id')->after('id')
                ->constrained('empresas')->restrictOnDelete();
            $table->unique(['empresa_id', 'dni'], 'uq_trabajadores_empresa_dni');
            $table->unique(
                ['empresa_id', 'codigo_biometrico'],
                'uq_trabajadores_empresa_biometrico'
            );
            $table->index(['empresa_id', 'estado', 'area_id'], 'ix_trabajadores_empresa_estado');
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
            $table->foreignId('empresa_id')->after('id')
                ->constrained('empresas')->restrictOnDelete();
            $table->unique(['empresa_id', 'nombre'], 'uq_horarios_empresa_nombre');
            $table->index(['empresa_id', 'estado'], 'ix_horarios_empresa_estado');
        });

        $this->agregarEmpresa('trabajador_feriados', function (Blueprint $table): void {
            $table->index(['empresa_id', 'fecha', 'estado'], 'ix_feriados_empresa_fecha');
        });

        Schema::table('dispositivos_biometricos', function (Blueprint $table) {
            $table->foreignId('empresa_id')->after('id')
                ->constrained('empresas')->restrictOnDelete();
            $table->boolean('estado')->default(true)->after('modelo');
            $table->string('secreto_hash', 255)->nullable()->after('estado');
            $table->index(['empresa_id', 'estado'], 'ix_dispositivos_empresa_estado');
        });

        Schema::table('marcaciones', function (Blueprint $table) {
            $table->dropUnique(['evento_externo_id']);
            $table->foreignId('empresa_id')->after('id')
                ->constrained('empresas')->restrictOnDelete();
            $table->foreignId('dispositivo_id')->nullable()->after('empresa_id')
                ->constrained('dispositivos_biometricos')->nullOnDelete();
            $table->unique(
                ['dispositivo_id', 'evento_externo_id'],
                'uq_marcaciones_dispositivo_evento'
            );
            $table->index(['empresa_id', 'fecha_hora'], 'ix_marcaciones_empresa_fecha');
        });

        $this->agregarEmpresa('asistencias', function (Blueprint $table): void {
            $table->index(['empresa_id', 'fecha', 'estado'], 'ix_asistencias_empresa_fecha');
        });
        $this->agregarEmpresa('asignaciones', function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'fecha_inicio'], 'ix_asignaciones_empresa_estado');
        });
        $this->agregarEmpresa('solicitudes', function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'tipo'], 'ix_solicitudes_empresa_estado');
        });
        $this->agregarEmpresa('vacaciones', function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'fecha_inicio'], 'ix_vacaciones_empresa_estado');
        });
        $this->agregarEmpresa('reportes_avance', function (Blueprint $table): void {
            $table->index(['empresa_id', 'fecha_presentacion'], 'ix_reportes_empresa_fecha');
        });
        $this->agregarEmpresa('pendientes_avance', function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado'], 'ix_pendientes_empresa_estado');
        });
        $this->agregarEmpresa('marcaciones_campo', function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'fecha_hora'], 'ix_campo_empresa_estado');
        });

        Schema::table('auditoria', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->after('id')
                ->constrained('empresas')->nullOnDelete();
            $table->index(['empresa_id', 'created_at'], 'ix_auditoria_empresa_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('auditoria', function (Blueprint $table) {
            $table->dropIndex('ix_auditoria_empresa_fecha');
            $table->dropConstrainedForeignId('empresa_id');
        });

        foreach ([
            ['marcaciones_campo', 'ix_campo_empresa_estado'],
            ['pendientes_avance', 'ix_pendientes_empresa_estado'],
            ['reportes_avance', 'ix_reportes_empresa_fecha'],
            ['vacaciones', 'ix_vacaciones_empresa_estado'],
            ['solicitudes', 'ix_solicitudes_empresa_estado'],
            ['asignaciones', 'ix_asignaciones_empresa_estado'],
            ['asistencias', 'ix_asistencias_empresa_fecha'],
        ] as [$tabla, $indice]) {
            Schema::table($tabla, function (Blueprint $table) use ($indice) {
                $table->dropIndex($indice);
                $table->dropConstrainedForeignId('empresa_id');
            });
        }

        Schema::table('marcaciones', function (Blueprint $table) {
            $table->dropIndex('ix_marcaciones_empresa_fecha');
            $table->dropUnique('uq_marcaciones_dispositivo_evento');
            $table->dropConstrainedForeignId('dispositivo_id');
            $table->dropConstrainedForeignId('empresa_id');
            $table->unique('evento_externo_id');
        });

        Schema::table('dispositivos_biometricos', function (Blueprint $table) {
            $table->dropIndex('ix_dispositivos_empresa_estado');
            $table->dropColumn(['estado', 'secreto_hash']);
            $table->dropConstrainedForeignId('empresa_id');
        });

        Schema::table('trabajador_feriados', function (Blueprint $table) {
            $table->dropIndex('ix_feriados_empresa_fecha');
            $table->dropConstrainedForeignId('empresa_id');
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropIndex('ix_horarios_empresa_estado');
            $table->dropUnique('uq_horarios_empresa_nombre');
            $table->dropConstrainedForeignId('empresa_id');
            $table->unique('nombre');
        });

        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropIndex('ix_trabajadores_empresa_estado');
            $table->dropUnique('uq_trabajadores_empresa_dni');
            $table->dropUnique('uq_trabajadores_empresa_biometrico');
            $table->dropConstrainedForeignId('empresa_id');
            $table->unique('dni');
            $table->unique('codigo_biometrico');
        });

        Schema::table('areas', function (Blueprint $table) {
            $table->dropIndex('ix_areas_empresa_estado');
            $table->dropUnique('uq_areas_empresa_nombre');
            $table->dropConstrainedForeignId('empresa_id');
            $table->unique('nombre');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('ix_users_empresa_estado');
            $table->dropConstrainedForeignId('empresa_id');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('ix_roles_ambito_estado');
            $table->dropUnique('uq_roles_codigo');
            $table->dropColumn(['codigo', 'ambito']);
        });

        Schema::dropIfExists('empresas');
    }

    private function agregarEmpresa(string $tabla, callable $indices): void
    {
        Schema::table($tabla, function (Blueprint $table) use ($indices) {
            $table->foreignId('empresa_id')->after('id')
                ->constrained('empresas')->restrictOnDelete();
            $indices($table);
        });
    }
};
