<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use App\Notifications\RestablecerContrasena;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_puede_cambiar_su_contrasena(): void
    {
        $usuario = $this->crearUsuario();

        $response = $this->actingAs($usuario)->put(route('password.update'), [
            'current_password' => 'Clave1234',
            'password' => 'NuevaClave5678',
            'password_confirmation' => 'NuevaClave5678',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('NuevaClave5678', $usuario->fresh()->password));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_menu_de_perfil_muestra_los_datos_y_acciones_del_usuario(): void
    {
        $area = Area::create([
            'nombre' => 'Administración',
            'estado' => true,
        ]);
        $trabajador = Trabajador::create([
            'area_id' => $area->id,
            'dni' => '76543210',
            'nombres' => 'Administrador',
            'apellidos' => 'Principal',
            'tipo_vinculo' => 'CONTRATADO',
            'telefono' => '987654321',
            'estado' => 'ACTIVO',
        ]);
        $usuario = $this->crearUsuario();
        $usuario->update([
            'trabajador_id' => $trabajador->id,
            'name' => 'Administrador Principal',
        ]);

        $this->actingAs($usuario)
            ->get(route('password.edit'))
            ->assertOk()
            ->assertSee('Mi perfil')
            ->assertSee('Administrador Principal')
            ->assertSee('TRABAJADOR')
            ->assertSee('76543210')
            ->assertSee('987654321')
            ->assertSee('Cambiar o restablecer contraseña')
            ->assertSee('Cerrar sesión');
    }

    public function test_no_cambia_la_contrasena_si_la_actual_es_incorrecta(): void
    {
        $usuario = $this->crearUsuario();

        $response = $this->actingAs($usuario)->from(route('password.edit'))->put(route('password.update'), [
            'current_password' => 'ClaveIncorrecta123',
            'password' => 'NuevaClave5678',
            'password_confirmation' => 'NuevaClave5678',
        ]);

        $response->assertRedirect(route('password.edit'));
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('Clave1234', $usuario->fresh()->password));
    }

    public function test_envia_enlace_de_restauracion_a_una_cuenta_activa(): void
    {
        Notification::fake();
        $usuario = $this->crearUsuario();

        $response = $this->post(route('password.email'), [
            'email' => $usuario->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertSentTo($usuario, RestablecerContrasena::class);
    }

    public function test_no_envia_enlace_a_una_cuenta_inactiva_y_mantiene_respuesta_generica(): void
    {
        Notification::fake();
        $usuario = $this->crearUsuario(false);

        $response = $this->post(route('password.email'), [
            'email' => $usuario->email,
        ]);

        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    public function test_puede_restablecer_la_contrasena_con_un_token_valido(): void
    {
        $usuario = $this->crearUsuario();
        $token = Password::createToken($usuario);

        $response = $this->post(route('password.store'), [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'Recuperada9876',
            'password_confirmation' => 'Recuperada9876',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
        $this->assertTrue(Hash::check('Recuperada9876', $usuario->fresh()->password));
    }

    private function crearUsuario(bool $estado = true): User
    {
        $rol = Rol::firstOrCreate(
            ['nombre' => 'TRABAJADOR'],
            ['descripcion' => 'Trabajador', 'estado' => true]
        );

        return User::create([
            'rol_id' => $rol->id,
            'name' => 'Usuario de prueba',
            'email' => 'usuario'.($estado ? '' : '.inactivo').'@example.com',
            'password' => 'Clave1234',
            'estado' => $estado,
        ]);
    }
}
