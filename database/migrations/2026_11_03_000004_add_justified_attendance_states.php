<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->enum('estado', [
                'PRESENTE', 'TARDANZA', 'FALTA', 'FJ', 'TJ', 'JUSTIFICADO',
                'CAMPO', 'VACACIONES', 'PERMISO', 'FERIADO', 'NO_LABORABLE',
                'SIN_REGISTRO',
            ])->change();
        });
    }

    public function down(): void
    {
        DB::table('asistencias')->where('estado', 'FJ')->update(['estado' => 'FALTA']);
        DB::table('asistencias')->where('estado', 'TJ')->update(['estado' => 'TARDANZA']);

        Schema::table('asistencias', function (Blueprint $table) {
            $table->enum('estado', [
                'PRESENTE', 'TARDANZA', 'FALTA', 'JUSTIFICADO', 'CAMPO',
                'VACACIONES', 'PERMISO', 'FERIADO', 'NO_LABORABLE', 'SIN_REGISTRO',
            ])->change();
        });
    }
};
