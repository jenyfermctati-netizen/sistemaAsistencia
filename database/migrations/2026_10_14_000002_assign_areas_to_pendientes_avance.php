<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendientes_avance', function (Blueprint $table) {
            $table->unsignedBigInteger('reporte_avance_id')->nullable()->change();
            $table->foreignId('area_id')->nullable()->after('reporte_avance_id')
                ->constrained('areas')->restrictOnDelete();
            $table->foreignId('creado_por')->nullable()->after('area_id')
                ->constrained('users')->nullOnDelete();
            $table->string('origen', 20)->default('TRABAJADOR')->after('creado_por');
            $table->index(['area_id', 'estado'], 'ix_pendientes_area_estado');
        });

        DB::table('pendientes_avance')
            ->orderBy('id')
            ->eachById(function ($pendiente) {
                $areaId = DB::table('reportes_avance')
                    ->join('trabajadores', 'trabajadores.id', '=', 'reportes_avance.trabajador_id')
                    ->where('reportes_avance.id', $pendiente->reporte_avance_id)
                    ->value('trabajadores.area_id');

                DB::table('pendientes_avance')
                    ->where('id', $pendiente->id)
                    ->update(['area_id' => $areaId]);
            });
    }

    public function down(): void
    {
        Schema::table('pendientes_avance', function (Blueprint $table) {
            $table->dropIndex('ix_pendientes_area_estado');
            $table->dropConstrainedForeignId('creado_por');
            $table->dropColumn('origen');
            $table->dropConstrainedForeignId('area_id');
            $table->unsignedBigInteger('reporte_avance_id')->nullable(false)->change();
        });
    }
};
