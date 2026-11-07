<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades_reporte', function (Blueprint $table) {
            $table->foreignId('asignacion_campo_id')->nullable()
                ->constrained('asignaciones')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('actividades_reporte', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asignacion_campo_id');
        });
    }
};
