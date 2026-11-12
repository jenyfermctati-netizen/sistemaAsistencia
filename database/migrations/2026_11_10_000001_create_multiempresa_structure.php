<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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

        $crearEmpresaInicial = $this->validarDatosLegados($tablasEmpresariales);
        $empresaInicialId = null;

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

        if ($crearEmpresaInicial) {
            // Metadata is validated before any DDL. This creates Connext as the first
            // tenant, then each legacy tenant-owned table is backfilled with this ID.
            $empresaInicialId = DB::table('empresas')->insertGetId([
                'razon_social' => config('saas.initial_company.razon_social'),
                'nombre_comercial' => config('saas.initial_company.nombre_comercial') ?: 'Connext',
                'ruc' => config('saas.initial_company.ruc'),
                'estado' => 'ACTIVA',
                'zona_horaria' => 'America/Lima',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

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
        $this->asignarEmpresa('users', $empresaInicialId);

        Schema::table('areas', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });
        $this->agregarEmpresa('areas', $empresaInicialId, function (Blueprint $table): void {
            $table->unique(['empresa_id', 'nombre'], 'uq_areas_empresa_nombre');
            $table->index(['empresa_id', 'estado'], 'ix_areas_empresa_estado');
        });

        Schema::table('trabajadores', function (Blueprint $table) {
            $table->dropUnique(['codigo_biometrico']);
            $table->dropUnique(['dni']);
        });
        $this->agregarEmpresa('trabajadores', $empresaInicialId, function (Blueprint $table): void {
            $table->unique(['empresa_id', 'dni'], 'uq_trabajadores_empresa_dni');
            $table->unique(
                ['empresa_id', 'codigo_biometrico'],
                'uq_trabajadores_empresa_biometrico'
            );
            $table->index(['empresa_id', 'estado', 'area_id'], 'ix_trabajadores_empresa_estado');
        });

        Schema::table('horarios', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });
        $this->agregarEmpresa('horarios', $empresaInicialId, function (Blueprint $table): void {
            $table->unique(['empresa_id', 'nombre'], 'uq_horarios_empresa_nombre');
            $table->index(['empresa_id', 'estado'], 'ix_horarios_empresa_estado');
        });

        $this->agregarEmpresa('trabajador_feriados', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'fecha', 'estado'], 'ix_feriados_empresa_fecha');
        });

        Schema::table('dispositivos_biometricos', function (Blueprint $table) {
            $table->boolean('estado')->default(true)->after('modelo');
            $table->string('secreto_hash', 255)->nullable()->after('estado');
        });
        $this->agregarEmpresa('dispositivos_biometricos', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado'], 'ix_dispositivos_empresa_estado');
        });

        Schema::table('marcaciones', function (Blueprint $table) {
            $table->dropUnique(['evento_externo_id']);
            $table->foreignId('dispositivo_id')->nullable()->after('id')
                ->constrained('dispositivos_biometricos')->nullOnDelete();
        });
        $this->agregarEmpresa('marcaciones', $empresaInicialId, function (Blueprint $table): void {
            $table->unique(
                ['dispositivo_id', 'evento_externo_id'],
                'uq_marcaciones_dispositivo_evento'
            );
            $table->index(['empresa_id', 'fecha_hora'], 'ix_marcaciones_empresa_fecha');
        });

        $this->agregarEmpresa('asistencias', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'fecha', 'estado'], 'ix_asistencias_empresa_fecha');
        });
        $this->agregarEmpresa('asignaciones', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'fecha_inicio'], 'ix_asignaciones_empresa_estado');
        });
        $this->agregarEmpresa('solicitudes', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'tipo'], 'ix_solicitudes_empresa_estado');
        });
        $this->agregarEmpresa('vacaciones', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'fecha_inicio'], 'ix_vacaciones_empresa_estado');
        });
        $this->agregarEmpresa('reportes_avance', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'fecha_presentacion'], 'ix_reportes_empresa_fecha');
        });
        $this->agregarEmpresa('pendientes_avance', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado'], 'ix_pendientes_empresa_estado');
        });
        $this->agregarEmpresa('marcaciones_campo', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'estado', 'fecha_hora'], 'ix_campo_empresa_estado');
        });

        $this->agregarEmpresa('auditoria', $empresaInicialId, function (Blueprint $table): void {
            $table->index(['empresa_id', 'created_at'], 'ix_auditoria_empresa_fecha');
        }, true, true);
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

    private function validarDatosLegados(array $tablas): bool
    {
        $tablasConDatos = [];
        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla) && DB::table($tabla)->exists()) {
                $tablasConDatos[] = $tabla;
            }
        }

        if ($tablasConDatos === []) {
            return false;
        }

        if (Schema::hasTable('empresas')) {
            throw new RuntimeException('La base contiene datos y una tabla empresas previa; no se puede inferir un tenant único. No se modificó el esquema.');
        }

        foreach (['empresa_usuario', 'empresa_user'] as $tabla) {
            if (Schema::hasTable($tabla) && DB::table($tabla)->exists()) {
                throw new RuntimeException("La tabla {$tabla} contiene vínculos empresariales. Clasifica esos vínculos antes de migrar; no se modificó el esquema.");
            }
        }

        foreach ($tablas as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'empresa_id')) {
                throw new RuntimeException("La tabla {$tabla} ya tiene empresa_id. No se asumirá una sola empresa; no se modificó el esquema.");
            }
        }

        $razonSocial = trim((string) config('saas.initial_company.razon_social'));
        $ruc = trim((string) config('saas.initial_company.ruc'));
        if ($razonSocial === '' || ! preg_match('/^\d{11}$/', $ruc)) {
            throw new RuntimeException('La base tiene datos históricos. Define SAAS_INITIAL_COMPANY_RAZON_SOCIAL y SAAS_INITIAL_COMPANY_RUC antes de migrar. No se modificó el esquema ni los datos.');
        }

        $codigos = DB::table('roles')->pluck('nombre')
            ->map(fn ($nombre) => mb_strtoupper(trim((string) $nombre)));
        if ($codigos->count() !== $codigos->unique()->count()) {
            throw new RuntimeException('Hay nombres de rol duplicados al normalizarlos. Corrige esos roles antes de migrar; no se modificó el esquema.');
        }

        return true;
    }

    private function asignarEmpresa(string $tabla, ?int $empresaId): void
    {
        if ($empresaId !== null) {
            DB::table($tabla)->whereNull('empresa_id')->update(['empresa_id' => $empresaId]);
        }
    }

    private function agregarEmpresa(
        string $tabla,
        ?int $empresaId,
        callable $indices,
        bool $nullable = false,
        bool $nullOnDelete = false
    ): void
    {
        Schema::table($tabla, function (Blueprint $table) use ($nullOnDelete) {
            $column = $table->foreignId('empresa_id')->nullable()->after('id');
            $foreign = $column->constrained('empresas');
            $nullOnDelete ? $foreign->nullOnDelete() : $foreign->restrictOnDelete();
        });

        $this->asignarEmpresa($tabla, $empresaId);

        if (! $nullable) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->unsignedBigInteger('empresa_id')->nullable(false)->change();
            });
        }

        Schema::table($tabla, function (Blueprint $table) use ($indices) {
            $indices($table);
        });
    }
};
