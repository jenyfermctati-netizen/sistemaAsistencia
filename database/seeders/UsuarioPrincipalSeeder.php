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

        if ($email === '' && $password === '') {
            return;
        }

        if ($email === '' || $password === '') {
            throw new RuntimeException('Define SUPERADMIN_EMAIL y SUPERADMIN_PASSWORD juntos para crear el acceso global inicial.');
        }

        $rolId = DB::table('roles')
            ->where('codigo', 'SUPERADMIN')
            ->where('ambito', 'GLOBAL')
            ->value('id');

        if (! $rolId) {
            throw new RuntimeException('Ejecuta primero ControlAsistenciaSeeder para crear el rol SUPERADMIN.');
        }

        $usuario = DB::table('users')->where('email', $email)->first();
        $datos = [
            'empresa_id' => null,
            'trabajador_id' => null,
            'rol_id' => $rolId,
            'name' => 'Superadministrador CONNEXT',
            'email_verified_at' => now(),
            'estado' => true,
            'updated_at' => now(),
        ];

        if ($usuario) {
            if ($usuario->empresa_id !== null || (int) $usuario->rol_id !== (int) $rolId) {
                throw new RuntimeException('El correo SUPERADMIN_EMAIL ya pertenece a una cuenta empresarial. Usa otro correo para no cambiar su acceso.');
            }

            DB::table('users')->where('id', $usuario->id)->update($datos);

            return;
        }

        DB::table('users')->insert($datos + [
            'email' => $email,
            'password' => Hash::make($password),
            'ultimo_acceso' => null,
            'remember_token' => null,
            'created_at' => now(),
        ]);
    }
}
