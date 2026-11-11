<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('ingresos_count')->default(0)->after('ultimo_acceso');
            $table->timestamp('password_changed_at')->nullable()->after('ingresos_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ingresos_count', 'password_changed_at']);
        });
    }
};
