<?php

namespace Tests\Feature;

use App\Models\Feriado;
use App\Models\Rol;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeriadoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_puede_crear_un_feriado(): void
    {
        $administrador = $this->crearAdministrador();

        $response = $this
            ->actingAs($administrador)
            ->post(route('feriados.store'), [
                'fecha' => '2026-11-01',
                'nombre' => 'Feriado de prueba',
                'tipo' => 'INSTITUCIONAL',
                'descripcion' => 'Creado desde la prueba automatizada.',
            ]);

        $response
            ->assertRedirect(route('feriados.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('trabajador_feriados', [
            'nombre' => 'Feriado de prueba',
            'tipo' => 'INSTITUCIONAL',
            'estado' => true,
        ]);

        $feriado = Feriado::where('nombre', 'Feriado de prueba')->firstOrFail();
        $this->assertSame('2026-11-01', $feriado->fecha->toDateString());
    }

    public function test_administrador_puede_desactivar_un_feriado(): void
    {
        $administrador = $this->crearAdministrador();
        $feriado = Feriado::create([
            'fecha' => '2026-12-01',
            'nombre' => 'Feriado activo',
            'tipo' => 'LOCAL',
            'estado' => true,
        ]);

        $response = $this
            ->actingAs($administrador)
            ->patch(route('feriados.estado', $feriado));

        $response
            ->assertRedirect(route('feriados.index'))
            ->assertSessionHas('success');

        $this->assertFalse($feriado->fresh()->estado);
    }

    private function crearAdministrador(): User
    {
        $rol = Rol::create([
            'nombre' => 'ADMINISTRADOR',
            'estado' => true,
        ]);

        return User::create([
            'rol_id' => $rol->id,
            'name' => 'Administrador de prueba',
            'email' => 'admin.feriados@example.com',
            'password' => 'secret',
            'estado' => true,
        ]);
    }
}
