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
            $table->string('estado_revision', 20)->nullable()->after('estado');
            $table->text('observacion_revision')->nullable()->after('estado_revision');
            $table->foreignId('revisado_por')->nullable()->after('observacion_revision')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_revision')->nullable()->after('revisado_por');
        });

        DB::table('pendientes_avance')
            ->where('estado', 'RESUELTO')
            ->update(['estado_revision' => 'APROBADO']);
    }

    public function down(): void
    {
        Schema::table('pendientes_avance', function (Blueprint $table) {
            $table->dropConstrainedForeignId('revisado_por');
            $table->dropColumn([
                'estado_revision',
                'observacion_revision',
                'fecha_revision',
            ]);
        });
    }
};
