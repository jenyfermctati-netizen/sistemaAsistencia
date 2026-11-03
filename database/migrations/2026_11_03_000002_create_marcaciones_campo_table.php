<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcaciones_campo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_campo_id')
                ->constrained('asignaciones')
                ->restrictOnDelete();
            $table->foreignId('trabajador_id')
                ->constrained('trabajadores')
                ->restrictOnDelete();
            $table->dateTime('fecha_hora');
            $table->enum('tipo', ['ENTRADA', 'SALIDA']);
            $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])
                ->default('PENDIENTE');
            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('revisado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->dateTime('fecha_revision')->nullable();
            $table->string('observacion_revision', 500)->nullable();
            $table->foreignId('marcacion_id')
                ->nullable()
                ->unique()
                ->constrained('marcaciones')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['trabajador_id', 'fecha_hora', 'estado'],
                'ix_marcaciones_campo_trabajador_fecha'
            );
            $table->index(['estado', 'created_at'], 'ix_marcaciones_campo_revision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcaciones_campo');
    }
};
