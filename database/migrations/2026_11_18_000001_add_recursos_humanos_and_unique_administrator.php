<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('roles')->where('codigo', 'ADMINISTRADOR')->value('id');
        $admins = DB::table('users')->where('rol_id', $adminId)->whereNotNull('empresa_id');
        if ((clone $admins)->select('empresa_id')->groupBy('empresa_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Existen empresas con varios administradores. Defina el titular y reasigne los demás antes de migrar.');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('administrador_empresa_id')->nullable()->unique();
        });
        $admins->update(['administrador_empresa_id' => DB::raw('empresa_id')]);
        DB::table('roles')->updateOrInsert(['codigo' => 'RECURSOS_HUMANOS'], [
            'ambito' => 'EMPRESA', 'nombre' => 'Recursos Humanos',
            'descripcion' => 'Gestiona personal, asistencia, solicitudes y vacaciones',
            'estado' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['administrador_empresa_id']);
            $table->dropColumn('administrador_empresa_id');
        });
        // Keep the role: existing accounts must not lose their relationship.
    }
};
