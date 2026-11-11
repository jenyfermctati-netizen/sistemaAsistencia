<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Asistencia;
use App\Models\DispositivoBiometrico;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\ReporteAvance;
use App\Models\Rol;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\User;
use App\Notifications\InvitacionAdministradorEmpresa;
use App\Support\EmpresaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class MultiEmpresaSaasTest extends TestCase
{
    use RefreshDatabase;

    private Rol $rolSuperadmin;

    private Rol $rolAdministrador;

    private Rol $rolTrabajador;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolSuperadmin = Rol::create([
            'codigo' => 'SUPERADMIN', 'ambito' => 'GLOBAL', 'nombre' => 'Superadministrador', 'estado' => true,
        ]);
        $this->rolAdministrador = Rol::create([
            'codigo' => 'ADMINISTRADOR', 'ambito' => 'EMPRESA', 'nombre' => 'Administrador', 'estado' => true,
        ]);
        $this->rolTrabajador = Rol::create([
            'codigo' => 'TRABAJADOR', 'ambito' => 'EMPRESA', 'nombre' => 'Trabajador', 'estado' => true,
        ]);
    }

    protected function tearDown(): void
    {
        app(EmpresaContext::class)->clear();
        parent::tearDown();
    }

    public function test_superadministrador_crea_empresa_y_envia_invitacion_segura(): void
    {
        Notification::fake();
        $superadmin = $this->superadmin();
        $ruc = $this->ruc(100);

        $this->actingAs($superadmin)->post(route('superadmin.empresas.store'), [
            'razon_social' => 'Empresa Nueva S.A.C.',
            'nombre_comercial' => 'Empresa Nueva',
            'ruc' => $ruc,
            'email' => 'contacto@empresa.test',
            'telefono' => '987654321',
            'direccion' => 'Lima',
            'zona_horaria' => 'America/Lima',
            'admin_nombre' => 'Administradora Principal',
            'admin_email' => 'principal@empresa.test',
        ])->assertSessionHasNoErrors();

        $empresa = Empresa::where('ruc', $ruc)->firstOrFail();
        $administrador = User::withoutGlobalScopes()->where('email', 'principal@empresa.test')->firstOrFail();

        $this->assertSame($empresa->id, $administrador->empresa_id);
        $this->assertSame('ADMINISTRADOR', $administrador->roleCode());
        $this->assertFalse(Hash::check('password', $administrador->password));
        Notification::assertSentTo($administrador, InvitacionAdministradorEmpresa::class);
        $this->assertDatabaseHas('auditoria', ['accion' => 'CREAR_EMPRESA', 'empresa_id' => $empresa->id]);

        $this->post(route('logout'));
        $token = Password::broker()->createToken($administrador);
        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $administrador->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect(route('login'));
        $this->post(route('login.process'), [
            'email' => $administrador->email,
            'password' => 'NuevaClave123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_login_empresarial_se_bloquea_al_suspender_la_empresa(): void
    {
        $empresa = $this->empresa('Acceso', 101);
        $admin = $this->administrador($empresa, 'acceso@empresa.test');

        $this->post(route('login.process'), ['email' => $admin->email, 'password' => 'ClaveSegura123'])
            ->assertRedirect(route('dashboard'));
        $this->post(route('logout'))->assertRedirect(route('login'));

        $empresa->update(['estado' => Empresa::SUSPENDIDA]);
        $this->post(route('login.process'), ['email' => $admin->email, 'password' => 'ClaveSegura123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_model_binding_listados_y_validaciones_impiden_ids_de_otra_empresa(): void
    {
        $empresaA = $this->empresa('Empresa A', 102);
        $empresaB = $this->empresa('Empresa B', 103);
        [$areaA, $trabajadorA] = $this->estructuraEmpresa($empresaA, 'Ana', '70000001');
        [$areaB, $trabajadorB] = $this->estructuraEmpresa($empresaB, 'Beatriz', '70000002');
        $adminA = $this->administrador($empresaA, 'admin-a@empresa.test');

        $this->assertSame('Operaciones', $areaA->nombre);
        $this->assertSame('Operaciones', $areaB->nombre);

        $this->actingAs($adminA)->get(route('trabajadores.index'))
            ->assertOk()->assertSee($trabajadorA->nombres)->assertDontSee($trabajadorB->nombres);

        $this->put(route('areas.update', $areaB), [
            'nombre' => 'Intrusión', 'descripcion' => null, 'estado' => 1,
        ])->assertNotFound();

        $this->put(route('trabajadores.update', $trabajadorB), [])->assertNotFound();

        $this->post(route('trabajadores.store'), [
            'area_id' => $areaB->id,
            'dni' => '70000009',
            'nombres' => 'Cruce',
            'apellidos' => 'Bloqueado',
            'tipo_vinculo' => 'CONTRATADO',
            'estado' => 'ACTIVO',
            'email' => 'cruce@empresa.test',
            'rol_id' => $this->rolTrabajador->id,
            'password' => 'ClaveSegura123',
            'password_confirmation' => 'ClaveSegura123',
        ])->assertSessionHasErrors('area_id');
    }

    public function test_solicitudes_y_reportes_no_exponen_datos_de_otra_empresa(): void
    {
        $empresaA = $this->empresa('Empresa A', 104);
        $empresaB = $this->empresa('Empresa B', 105);
        [, $trabajadorA] = $this->estructuraEmpresa($empresaA, 'Alicia Visible', '70000011');
        [, $trabajadorB] = $this->estructuraEmpresa($empresaB, 'Brenda Oculta', '70000012');
        $adminA = $this->administrador($empresaA, 'reportes-a@empresa.test');

        app(EmpresaContext::class)->run($empresaA, function () use ($trabajadorA) {
            Solicitud::create([
                'trabajador_id' => $trabajadorA->id, 'tipo' => 'OTRO', 'fecha_inicio' => '2026-11-10',
                'motivo' => 'Solicitud visible empresa A', 'estado' => 'PENDIENTE',
            ]);
            ReporteAvance::create([
                'trabajador_id' => $trabajadorA->id, 'fecha_presentacion' => '2026-11-10', 'estado' => 'PENDIENTE',
            ]);
        });
        app(EmpresaContext::class)->run($empresaB, function () use ($trabajadorB) {
            Solicitud::create([
                'trabajador_id' => $trabajadorB->id, 'tipo' => 'OTRO', 'fecha_inicio' => '2026-11-10',
                'motivo' => 'Solicitud secreta empresa B', 'estado' => 'PENDIENTE',
            ]);
            ReporteAvance::create([
                'trabajador_id' => $trabajadorB->id, 'fecha_presentacion' => '2026-11-10', 'estado' => 'PENDIENTE',
            ]);
        });

        $this->actingAs($adminA)->get(route('solicitudes.index'))
            ->assertOk()->assertSee('Solicitud visible empresa A')->assertDontSee('Solicitud secreta empresa B');
        $this->get(route('reportesAvance.index'))
            ->assertOk()->assertSee('Alicia Visible')->assertDontSee('Brenda Oculta');
    }

    public function test_biometrico_deriva_empresa_del_serial_y_deduplica_por_dispositivo(): void
    {
        $empresaA = $this->empresa('Empresa A', 106);
        $empresaB = $this->empresa('Empresa B', 107);
        [, $trabajadorA] = $this->estructuraEmpresa($empresaA, 'PIN A', '70000021', '77');
        [, $trabajadorB] = $this->estructuraEmpresa($empresaB, 'PIN B', '70000022', '77');
        $dispositivoA = $this->dispositivo($empresaA, 'SERIAL-A');
        $dispositivoB = $this->dispositivo($empresaB, 'SERIAL-B', 'secreto-b');
        $evento = "77\t2026-11-10 08:00:00\t0\t1";

        $this->enviarAttlog($dispositivoA->serial, $evento)->assertOk();
        $this->enviarAttlog($dispositivoA->serial, $evento)->assertOk();
        $this->enviarAttlog($dispositivoB->serial, $evento)->assertForbidden();
        $this->enviarAttlog($dispositivoB->serial, $evento, 'secreto-b')->assertOk();
        $this->enviarAttlog('SERIAL-DESCONOCIDO', $evento)->assertForbidden();

        $marcas = Marcacion::withoutGlobalScopes()->orderBy('dispositivo_id')->get();
        $this->assertCount(2, $marcas);
        $this->assertTrue($marcas->contains(fn (Marcacion $m) => $m->empresa_id === $empresaA->id
            && $m->trabajador_id === $trabajadorA->id && $m->dispositivo_id === $dispositivoA->id));
        $this->assertTrue($marcas->contains(fn (Marcacion $m) => $m->empresa_id === $empresaB->id
            && $m->trabajador_id === $trabajadorB->id && $m->dispositivo_id === $dispositivoB->id));
    }

    public function test_panel_global_y_panel_empresarial_son_mutuamente_excluyentes(): void
    {
        $empresa = $this->empresa('Separación', 108);
        $admin = $this->administrador($empresa, 'separacion@empresa.test');
        $superadmin = $this->superadmin();

        $this->actingAs($admin)->get(route('superadmin.dashboard'))->assertForbidden();
        $this->actingAs($superadmin)->get(route('dashboard'))->assertForbidden();
        $this->get(route('superadmin.dashboard'))->assertOk();
    }

    public function test_horarios_asistencias_y_dispositivos_rechazan_recursos_ajenos(): void
    {
        $empresaA = $this->empresa('Control A', 109);
        $empresaB = $this->empresa('Control B', 110);
        [, $trabajadorA] = $this->estructuraEmpresa($empresaA, 'Asistencia A', '70000031');
        [, $trabajadorB] = $this->estructuraEmpresa($empresaB, 'Asistencia B', '70000032');
        $horarioA = app(EmpresaContext::class)->run($empresaA, fn () => Horario::create([
            'nombre' => 'Horario General', 'formato_hora' => '24', 'estado' => true,
        ]));
        $horarioB = app(EmpresaContext::class)->run($empresaB, fn () => Horario::create([
            'nombre' => 'Horario General', 'formato_hora' => '24', 'estado' => true,
        ]));
        app(EmpresaContext::class)->run($empresaA, fn () => Asistencia::create([
            'trabajador_id' => $trabajadorA->id, 'horario_id' => $horarioA->id,
            'fecha' => '2026-11-10', 'estado' => 'PRESENTE',
        ]));
        app(EmpresaContext::class)->run($empresaB, fn () => Asistencia::create([
            'trabajador_id' => $trabajadorB->id, 'horario_id' => $horarioB->id,
            'fecha' => '2026-11-10', 'estado' => 'FALTA',
        ]));
        $dispositivoB = $this->dispositivo($empresaB, 'CONTROL-B');
        $adminA = $this->administrador($empresaA, 'control-a@empresa.test');

        $this->actingAs($adminA)->get(route('asistencias.index'))
            ->assertOk()->assertSee('Asistencia A')->assertDontSee('Asistencia B');
        $this->put(route('trabajadores.horarios.update', $horarioB), [])->assertNotFound();
        $this->post(route('biometrico.usuarios.reconciliar', $dispositivoB))->assertNotFound();
    }

    public function test_navegacion_de_plataforma_y_secciones_de_empresa(): void
    {
        $empresa = $this->empresa('Empresa de prueba', 900);
        $this->actingAs($this->superadmin());
        $this->get(route('superadmin.dashboard'))->assertOk()->assertSee('Gráficos de la plataforma');
        $this->get(route('superadmin.empresas.show', $empresa))->assertOk()
            ->assertSee('Información de la empresa')->assertDontSee('name="color_principal"', false);
        $this->get(route('superadmin.empresas.show', ['empresa' => $empresa, 'seccion' => 'administradores']))
            ->assertOk()->assertSee('Agregar administrador')->assertDontSee('name="color_principal"', false);
        $this->get(route('superadmin.empresas.show', ['empresa' => $empresa, 'seccion' => 'configuracion']))
            ->assertOk()->assertSee('name="color_principal"', false)->assertSee('Registrar dispositivo');
        $this->get(route('superadmin.auditoria.index', ['empresa_id' => $empresa->id]))
            ->assertOk()->assertSee('Estado actual de las empresas')->assertSee('Historial de cambios')
            ->assertViewHas('resumenEstados', ['ACTIVA' => 1, 'SUSPENDIDA' => 0, 'INACTIVA' => 0]);
        $this->get(route('superadmin.ayuda'))->assertOk()->assertSee('¿Dónde encuentro cada cosa?');
    }

    public function test_listado_permite_seleccionar_varias_empresas_y_conserva_la_seccion(): void
    {
        $this->empresa('Zeta Empresa', 901);
        $this->empresa('Alfa Empresa', 902);
        $this->actingAs($this->superadmin());
        $this->get(route('superadmin.empresas.index', ['seccion' => 'administradores']))
            ->assertOk()->assertSee('Agregar empresa')->assertSee('Ver administradores')
            ->assertViewHas('empresas', fn ($empresas) => $empresas->total() === 2
                && $empresas->first()->nombre_comercial === 'Alfa Empresa');
        $this->get(route('superadmin.empresas.index', ['buscar' => 'Zeta', 'seccion' => 'configuracion']))
            ->assertOk()->assertSee('Ver configuración')
            ->assertViewHas('empresas', fn ($empresas) => $empresas->total() === 1
                && $empresas->first()->nombre_comercial === 'Zeta Empresa');
        $this->get(route('superadmin.empresas.create'))->assertOk()->assertSee('Designar administrador');
    }

    private function empresa(string $nombre, int $semilla): Empresa
    {
        return Empresa::create([
            'razon_social' => $nombre.' S.A.C.',
            'nombre_comercial' => $nombre,
            'ruc' => $this->ruc($semilla),
            'estado' => Empresa::ACTIVA,
            'zona_horaria' => 'America/Lima',
        ]);
    }

    private function superadmin(): User
    {
        return User::withoutGlobalScopes()->create([
            'empresa_id' => null,
            'rol_id' => $this->rolSuperadmin->id,
            'name' => 'Superadmin',
            'email' => 'superadmin'.User::withoutGlobalScopes()->count().'@connext.test',
            'password' => 'ClaveGlobal123',
            'estado' => true,
        ]);
    }

    private function administrador(Empresa $empresa, string $email): User
    {
        return User::withoutGlobalScopes()->create([
            'empresa_id' => $empresa->id,
            'rol_id' => $this->rolAdministrador->id,
            'name' => 'Administrador '.$empresa->nombre_comercial,
            'email' => $email,
            'password' => 'ClaveSegura123',
            'estado' => true,
        ]);
    }

    private function estructuraEmpresa(
        Empresa $empresa,
        string $nombre,
        string $dni,
        ?string $pin = null
    ): array {
        return app(EmpresaContext::class)->run($empresa, function () use ($nombre, $dni, $pin) {
            $area = Area::create(['nombre' => 'Operaciones', 'estado' => true]);
            $trabajador = Trabajador::create([
                'area_id' => $area->id,
                'codigo_biometrico' => $pin,
                'dni' => $dni,
                'nombres' => $nombre,
                'apellidos' => 'Prueba',
                'tipo_vinculo' => 'CONTRATADO',
                'estado' => 'ACTIVO',
            ]);

            return [$area, $trabajador];
        });
    }

    private function dispositivo(Empresa $empresa, string $serial, ?string $secreto = null): DispositivoBiometrico
    {
        return DispositivoBiometrico::withoutGlobalScopes()->create([
            'empresa_id' => $empresa->id,
            'nombre' => $serial,
            'serial' => $serial,
            'modelo' => 'Prueba',
            'estado' => true,
            'secreto_hash' => $secreto ? Hash::make($secreto) : null,
        ]);
    }

    private function enviarAttlog(string $serial, string $contenido, ?string $secreto = null)
    {
        $servidor = ['CONTENT_TYPE' => 'text/plain'];
        if ($secreto) {
            $servidor['HTTP_X_DEVICE_TOKEN'] = $secreto;
        }

        return $this->call(
            'POST',
            '/iclock/cdata?SN='.urlencode($serial).'&table=ATTLOG',
            [],
            [],
            [],
            $servidor,
            $contenido
        );
    }

    private function ruc(int $semilla): string
    {
        $base = '20'.str_pad((string) $semilla, 8, '0', STR_PAD_LEFT);
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        foreach ($pesos as $indice => $peso) {
            $suma += ((int) $base[$indice]) * $peso;
        }
        $resultado = 11 - ($suma % 11);
        $digito = $resultado === 10 ? 0 : ($resultado === 11 ? 1 : $resultado);

        return $base.$digito;
    }
}
