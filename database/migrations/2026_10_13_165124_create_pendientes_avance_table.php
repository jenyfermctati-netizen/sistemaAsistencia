<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendientes_avance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_avance_id')->nullable()->constrained('reportes_avance')->cascadeOnDelete();
            $table->string('descripcion', 500);
            $table->text('detalle')->nullable();
            $table->string('ruta_archivos', 500)->nullable();
            $table->enum('estado', ['PENDIENTE', 'RESUELTO'])->default('PENDIENTE');
            $table->foreignId('resuelto_por')->nullable()->constrained('trabajadores')->nullOnDelete();
            $table->dateTime('fecha_resolucion')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at'], 'ix_pendientes_estado_fecha');

            $table->foreignId('area_id')->nullable()
                ->constrained('areas')->restrictOnDelete();
            $table->foreignId('creado_por')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('origen', 20)->default('TRABAJADOR');
            $table->index(['area_id', 'estado'], 'ix_pendientes_area_estado');

            $table->string('estado_revision', 20)->nullable();
            $table->text('observacion_revision')->nullable();
            $table->foreignId('revisado_por')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_revision')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendientes_avance');
    }
};
