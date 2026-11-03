<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marcaciones_campo', function (Blueprint $table) {
            $table->string('evidencia_path', 500)
                ->nullable()
                ->after('registrado_por');
        });
    }

    public function down(): void
    {
        Schema::table('marcaciones_campo', function (Blueprint $table) {
            $table->dropColumn('evidencia_path');
        });
    }
};
