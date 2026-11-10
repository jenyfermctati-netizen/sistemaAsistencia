<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UsuarioPrincipalSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('SUPERADMIN_EMAIL'));
        $password = (string) env('SUPERADMIN_PASSWORD');

        if ($email === '' || $password === '') {
            $this->command?->warn('SUPERADMIN_EMAIL y SUPERADMIN_PASSWORD no están definidos; no se creó la cuenta global.');

            return;
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('SUPERADMIN_PASSWORD debe tener al menos 12 caracteres.');
        }

        $rolId = DB::table('roles')
            ->where('codigo', 'SUPERADMIN')
            ->where('ambito', 'GLOBAL')
            ->value('id');

        if (! $rolId) {
            throw new RuntimeException('Ejecuta primero ControlAsistenciaSeeder.');
        }

        DB::table('users')->updateOrInsert(
            ['email' => $email],
            [
                'empresa_id' => null,
                'trabajador_id' => null,
                'rol_id' => $rolId,
                'name' => 'Superadministrador CONNEXT',
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'estado' => true,
                'ultimo_acceso' => null,
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
