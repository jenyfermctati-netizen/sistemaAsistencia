<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pendientes_avance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporte_avance_id')->constrained('reportes_avance')->cascadeOnDelete();
            $table->string('descripcion', 500);
            $table->text('detalle')->nullable();
            $table->string('ruta_archivos', 500)->nullable();
            $table->enum('estado', ['PENDIENTE', 'RESUELTO'])->default('PENDIENTE');
            $table->foreignId('resuelto_por')->nullable()->constrained('trabajadores')->nullOnDelete();
            $table->dateTime('fecha_resolucion')->nullable();
            $table->timestamps();

            $table->index(['estado', 'created_at'], 'ix_pendientes_estado_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendientes_avance');
    }
};
