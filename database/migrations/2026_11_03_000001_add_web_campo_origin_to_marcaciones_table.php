<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marcaciones', function (Blueprint $table) {
            $table->enum('origen', ['BIOMETRICO', 'MANUAL', 'WEB_CAMPO'])
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('marcaciones')
            ->where('origen', 'WEB_CAMPO')
            ->update(['origen' => 'MANUAL']);

        Schema::table('marcaciones', function (Blueprint $table) {
            $table->enum('origen', ['BIOMETRICO', 'MANUAL'])
                ->change();
        });
    }
};
