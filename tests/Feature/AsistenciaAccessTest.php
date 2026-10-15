<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsistenciaAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_gerente_puede_consultar_asistencias_y_horas(): void
    {
        $gerente = $this->crearUsuario('GERENTE');

        $this->actingAs($gerente)
            ->get(route('asistencias.index'))
            ->assertOk()
            ->assertSee('Asistencias')
            ->assertSee('horas trabajadas')
            ->assertDontSee('Procesar asistencia');
    }

    public function test_el_gerente_no_puede_procesar_asistencias(): void
    {
        $gerente = $this->crearUsuario('GERENTE');

        $this->actingAs($gerente)
            ->post(route('asistencias.procesar'), ['fecha' => '2026-10-15'])
            ->assertForbidden();
    }

    private function crearUsuario(string $rolNombre): User
    {
        $rol = Rol::create([
            'nombre' => $rolNombre,
            'estado' => true,
        ]);

        return User::create([
            'rol_id' => $rol->id,
            'name' => ucfirst(strtolower($rolNombre)),
            'email' => strtolower($rolNombre).'@example.com',
            'password' => 'password123',
            'estado' => true,
        ]);
    }
}
