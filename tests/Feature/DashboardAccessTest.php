<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_administrador_ve_el_panel_general(): void
    {
        $administrador = $this->crearUsuario('ADMINISTRADOR');

        $this->actingAs($administrador)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('CONTROL GENERAL')
            ->assertSee('Bandeja administrativa')
            ->assertSee('Todas las áreas');
    }

    public function test_el_gerente_ve_el_panel_de_su_area(): void
    {
        $area = Area::create([
            'nombre' => 'Tecnología',
            'descripcion' => 'Área de sistemas',
            'estado' => true,
        ]);
        $trabajador = $this->crearTrabajador($area, '70000001', 'Gerente');
        $gerente = $this->crearUsuario('GERENTE', $trabajador);

        $this->actingAs($gerente)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('SUPERVISIÓN OPERATIVA')
            ->assertSee('Tecnología')
            ->assertSee('Trabajo por revisar');
    }

    public function test_el_trabajador_ve_su_resumen_personal(): void
    {
        $area = Area::create([
            'nombre' => 'Operaciones',
            'descripcion' => 'Área operativa',
            'estado' => true,
        ]);
        $trabajador = $this->crearTrabajador($area, '70000002', 'Analista');
        $usuario = $this->crearUsuario('TRABAJADOR', $trabajador);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('ESPACIO PERSONAL')
            ->assertSee('Aún sin procesar')
            ->assertSee('Operaciones');
    }

    private function crearUsuario(string $rolNombre, ?Trabajador $trabajador = null): User
    {
        $rol = Rol::create([
            'nombre' => $rolNombre,
            'estado' => true,
        ]);

        return User::create([
            'trabajador_id' => $trabajador?->id,
            'rol_id' => $rol->id,
            'name' => $trabajador?->nombres ?? ucfirst(strtolower($rolNombre)),
            'email' => strtolower($rolNombre).'@example.com',
            'password' => 'password123',
            'estado' => true,
        ]);
    }

    private function crearTrabajador(Area $area, string $dni, string $cargo): Trabajador
    {
        return Trabajador::create([
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Usuario',
            'apellidos' => $cargo,
            'tipo_vinculo' => 'CONTRATADO',
            'cargo' => $cargo,
            'estado' => 'ACTIVO',
        ]);
    }
}
