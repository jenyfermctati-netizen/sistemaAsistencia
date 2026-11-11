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
        $email = 'admin@gmail.com';
        $password = 'password';

        $rolId = DB::table('roles')
            ->where('codigo', 'SUPERADMIN')
            ->where('ambito', 'GLOBAL')
            ->value('id');

        if (! $rolId) {
            throw new RuntimeException('Ejecuta primero ControlAsistenciaSeeder para crear el rol SUPERADMIN.');
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
