<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reportes_avance', function (Blueprint $table) {
            $table->date('periodo_inicio')->nullable()->change();
            $table->date('periodo_fin')->nullable()->change();
            $table->text('descripcion')->nullable()->change();
            $table->decimal('porcentaje_avance', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reportes_avance', function (Blueprint $table) {
            $table->date('periodo_inicio')->nullable(false)->change();
            $table->date('periodo_fin')->nullable(false)->change();
            $table->text('descripcion')->nullable(false)->change();
        });
    }
};
