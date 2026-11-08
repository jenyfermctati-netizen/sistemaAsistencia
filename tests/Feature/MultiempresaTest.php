<?php

namespace Tests\Feature;

use App\Models\ActividadReporte;
use App\Models\Area;
use App\Models\DispositivoBiometrico;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\ReporteAvance;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use App\Tenancy\EmpresaActual;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MultiempresaTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $a;

    private Empresa $b;

    private User $user;

    private Trabajador $trabajadorA;

    private Trabajador $trabajadorB;

    private Rol $admin;

    private Rol $worker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->a = Empresa::firstOrFail();
        $this->b = Empresa::create(['nombre' => 'Empresa B']);
        $this->admin = Rol::create(['nombre' => 'ADMINISTRADOR']);
        $this->worker = Rol::create(['nombre' => 'TRABAJADOR']);
        $this->user = User::create([
            'name' => 'Cuenta compartida', 'email' => 'compartida@example.test',
            'password' => 'Password123', 'rol_id' => $this->admin->id, 'estado' => true,
        ]);
        foreach ([$this->a, $this->b] as $empresa) {
            $trabajador = app(EmpresaActual::class)->ejecutar($empresa, function () {
                $area = Area::create(['nombre' => 'Administración']);
                Horario::create(['nombre' => 'Oficina']);

                return Trabajador::create([
                    'area_id' => $area->id, 'dni' => '12345678', 'codigo_biometrico' => '1',
                    'nombres' => 'Nombre', 'apellidos' => 'Apellido', 'tipo_vinculo' => 'CONTRATADO',
                ]);
            });
            $empresa->usuarios()->attach($this->user->id, [
                'rol_id' => $empresa->is($this->a) ? $this->admin->id : $this->worker->id,
                'trabajador_id' => $trabajador->id, 'estado' => true,
            ]);
            if ($empresa->is($this->a)) {
                $this->trabajadorA = $trabajador;
            } else {
                $this->trabajadorB = $trabajador;
            }
        }
        app(EmpresaActual::class)->establecer($this->a);
    }

    public function test_consultas_y_creacion_se_limitan_a_la_empresa_actual(): void
    {
        $this->assertSame(1, Area::count());
        $this->assertNull(Trabajador::find($this->trabajadorB->id));
        $area = Area::create(['nombre' => 'Nueva', 'empresa_id' => $this->b->id]);
        $this->assertSame($this->a->id, $area->empresa_id);
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id])
            ->get(route('areas.index'))->assertOk()
            ->assertViewHas('areas', fn ($areas) => $areas->every(fn ($area) => $area->empresa_id === $this->a->id));
    }

    public function test_sin_contexto_no_hay_lecturas_ni_creaciones_de_negocio(): void
    {
        app(EmpresaActual::class)->establecer(null);
        $this->assertSame(0, Area::count());
        $this->expectException(\LogicException::class);
        Area::create(['nombre' => 'Sin contexto']);
    }

    public function test_no_se_puede_reasignar_un_registro_a_otra_empresa(): void
    {
        $this->expectException(\LogicException::class);
        $area = Area::firstOrFail();
        $area->empresa_id = $this->b->id;
        $area->save();
    }

    public function test_binding_rechaza_ids_de_otra_empresa(): void
    {
        $areaB = app(EmpresaActual::class)->ejecutar($this->b, fn () => Area::firstOrFail());
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id])
            ->put(route('areas.update', $areaB), ['nombre' => 'Ataque', 'estado' => 1])->assertNotFound();
    }

    public function test_validaciones_exists_y_unique_respetan_la_empresa(): void
    {
        $areaB = app(EmpresaActual::class)->ejecutar($this->b, fn () => Area::create(['nombre' => 'Solo B']));
        $this->assertTrue(Validator::make(['nombre' => 'Solo B'], ['nombre' => 'unique:areas,nombre'])->passes());
        $this->assertFalse(Validator::make(['area_id' => $areaB->id], ['area_id' => 'exists:areas,id'])->passes());
        $this->assertFalse(Validator::make(['nombre' => 'Administración'], ['nombre' => 'unique:areas,nombre'])->passes());
    }

    public function test_base_de_datos_rechaza_relaciones_cruzadas_incluso_por_sql(): void
    {
        $areaB = app(EmpresaActual::class)->ejecutar($this->b, fn () => Area::firstOrFail());
        $this->expectException(QueryException::class);
        DB::table('trabajadores')->where('id', $this->trabajadorA->id)->update(['area_id' => $areaB->id]);
    }

    public function test_un_usuario_tiene_rol_y_trabajador_distintos_por_empresa(): void
    {
        $this->assertSame($this->admin->id, $this->user->rol_id);
        $this->assertSame($this->trabajadorA->id, $this->user->trabajador_id);
        $this->actingAs($this->user)->post(route('empresas.seleccionar'), ['empresa_id' => $this->b->id])
            ->assertRedirect(route('dashboard'))->assertSessionHas('empresa_id', $this->b->id);
        $this->get(route('areas.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk();
        app(EmpresaActual::class)->ejecutar($this->b, function () {
            $this->assertSame($this->worker->id, $this->user->rol_id);
            $this->assertSame($this->trabajadorB->id, $this->user->trabajador_id);
        });
    }

    public function test_no_se_puede_seleccionar_empresa_sin_membresia(): void
    {
        $otra = Empresa::create(['nombre' => 'Sin acceso']);
        $this->actingAs($this->user)->post(route('empresas.seleccionar'), ['empresa_id' => $otra->id])->assertNotFound();
    }

    public function test_acceso_revocado_o_empresa_inactiva_se_revalida_en_cada_peticion(): void
    {
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id]);
        $this->user->membresias()->where('empresa_id', $this->a->id)->update(['estado' => false]);
        $this->get(route('dashboard'))->assertForbidden();
        $this->user->membresias()->where('empresa_id', $this->a->id)->update(['estado' => true]);
        $this->a->update(['estado' => false]);
        $this->get(route('dashboard'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect(route('login'));
    }

    public function test_administrador_puede_asignar_cuenta_existente_sin_cambiar_sus_credenciales(): void
    {
        $otra = User::create(['name' => 'Otra', 'email' => 'otra@example.test', 'password' => 'Original123', 'rol_id' => $this->worker->id]);
        $hash = $otra->password;
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id])
            ->post(route('empresas.usuarios.store'), [
                'email' => $otra->email, 'rol_id' => $this->worker->id, 'estado' => 1,
                'name' => 'Nombre manipulado', 'password' => 'Cambiar123', 'password_confirmation' => 'Cambiar123',
            ])->assertSessionHasNoErrors();
        $this->assertSame($hash, $otra->refresh()->password);
        $this->assertSame('Otra', $otra->name);
        $this->assertDatabaseHas('empresa_usuario', ['empresa_id' => $this->a->id, 'user_id' => $otra->id, 'estado' => true]);
    }

    public function test_gestion_de_accesos_y_creacion_de_empresa_tienen_permisos(): void
    {
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id]);
        $this->get(route('empresas.index'))->assertOk()->assertSee('Empresa B');
        $this->get(route('empresas.usuarios'))->assertOk()->assertSee($this->user->email);
        $this->withSession(['empresa_id' => $this->b->id])->get(route('empresas.usuarios'))->assertForbidden();
        $this->withSession(['empresa_id' => $this->a->id])->post(route('empresas.store'), ['nombre' => 'Empresa C'])
            ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
        $empresa = Empresa::where('nombre', 'Empresa C')->firstOrFail();
        $this->assertDatabaseHas('empresa_usuario', ['empresa_id' => $empresa->id, 'user_id' => $this->user->id, 'rol_id' => $this->admin->id]);
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('areas.index'))->assertOk()->assertViewHas('areas', fn ($areas) => $areas->isEmpty());
    }

    public function test_login_con_varias_empresas_abre_el_selector(): void
    {
        app(EmpresaActual::class)->establecer(null);
        $this->post(route('login.process'), ['email' => $this->user->email, 'password' => 'Password123'])
            ->assertRedirect(route('empresas.index'));
    }

    public function test_crear_cuenta_y_rechazar_vinculo_con_trabajador_de_otra_empresa(): void
    {
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id])
            ->post(route('empresas.usuarios.store'), [
                'email' => 'nueva@example.test', 'name' => 'Nueva cuenta',
                'password' => 'Password123', 'password_confirmation' => 'Password123',
                'rol_id' => $this->worker->id, 'estado' => 1,
            ])->assertSessionHasNoErrors();
        $nueva = User::where('email', 'nueva@example.test')->firstOrFail();
        $this->assertDatabaseHas('empresa_usuario', ['empresa_id' => $this->a->id, 'user_id' => $nueva->id]);
        $this->post(route('empresas.usuarios.store'), [
            'email' => $nueva->email, 'rol_id' => $this->worker->id, 'estado' => 1,
            'trabajador_id' => $this->trabajadorB->id,
        ])->assertSessionHasErrors('trabajador_id');
    }

    public function test_no_se_puede_atribuir_un_registro_a_un_usuario_de_otra_empresa_por_sql(): void
    {
        $otro = app(EmpresaActual::class)->ejecutar($this->b, fn () => User::create([
            'name' => 'Solo B', 'email' => 'solo-b@example.test', 'password' => 'Password123',
            'rol_id' => $this->worker->id,
        ]));
        $this->expectException(QueryException::class);
        DB::table('marcaciones')->insert([
            'empresa_id' => $this->a->id, 'trabajador_id' => $this->trabajadorA->id,
            'fecha_hora' => now(), 'tipo' => 'ENTRADA', 'origen' => 'MANUAL', 'registrado_por' => $otro->id,
        ]);
    }

    public function test_no_se_puede_desactivar_al_ultimo_administrador(): void
    {
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id])
            ->post(route('empresas.usuarios.store'), [
                'email' => $this->user->email, 'rol_id' => $this->worker->id, 'estado' => 1,
            ])->assertSessionHasErrors('rol_id');
        $this->assertSame($this->admin->id, $this->user->rol_id);
    }

    public function test_archivos_privados_no_se_descargan_desde_otra_empresa(): void
    {
        Storage::fake('local');
        $actividad = app(EmpresaActual::class)->ejecutar($this->b, function () {
            $reporte = ReporteAvance::create(['trabajador_id' => $this->trabajadorB->id, 'fecha_presentacion' => today()]);

            return ActividadReporte::create(['reporte_avance_id' => $reporte->id, 'descripcion' => 'Privado', 'archivo' => 'empresas/'.$this->b->id.'/privado.txt']);
        });
        Storage::disk('local')->put($actividad->archivo, 'Información privada');
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id])
            ->get(route('reportesAvance.actividades.archivo', $actividad))->assertNotFound();
        $this->withSession(['empresa_id' => $this->b->id])
            ->get(route('reportesAvance.actividades.descargar', $actividad))->assertOk();
    }

    public function test_biometrico_requiere_token_y_resuelve_la_empresa_del_dispositivo(): void
    {
        $dispositivo = app(EmpresaActual::class)->ejecutar($this->b, function () {
            $d = new DispositivoBiometrico(['serial' => 'SERIAL-B', 'nombre' => 'B']);
            $d->token_hash = hash('sha256', 'secreto-B');
            $d->save();

            return $d;
        });
        $this->get('/iclock/ping?SN=SERIAL-B')->assertForbidden();
        $this->get('/iclock/ping?SN=DESCONOCIDO&token=secreto-B')->assertForbidden();
        $this->get('/iclock/ping?SN=SERIAL-B&token=secreto-B')->assertOk();
        $this->call('POST', '/iclock/cdata?SN=SERIAL-B&token=secreto-B&table=ATTLOG', [], [], [],
            ['CONTENT_TYPE' => 'text/plain'], "1\t2026-11-01 08:20:00\t0\t1")->assertOk();
        $this->assertDatabaseHas('dispositivos_biometricos', ['id' => $dispositivo->id, 'empresa_id' => $this->b->id]);
        $this->assertDatabaseHas('marcaciones', ['empresa_id' => $this->b->id, 'trabajador_id' => $this->trabajadorB->id]);
        $this->assertSame(0, Marcacion::count());
        $this->assertNull(app(EmpresaActual::class)->id());
    }

    public function test_tareas_de_cierre_procesan_cada_empresa_y_limpian_el_contexto(): void
    {
        app(EmpresaActual::class)->establecer(null);
        $this->artisan('asistencias:cerrar', ['fecha' => today()->subDay()->toDateString()])->assertSuccessful();
        $this->assertDatabaseHas('asistencias', ['empresa_id' => $this->a->id, 'trabajador_id' => $this->trabajadorA->id]);
        $this->assertDatabaseHas('asistencias', ['empresa_id' => $this->b->id, 'trabajador_id' => $this->trabajadorB->id]);
        $this->assertNull(app(EmpresaActual::class)->id());
    }

    public function test_traslado_de_adjuntos_verifica_y_retira_la_copia_publica(): void
    {
        Storage::fake('public');
        Storage::fake('local');
        $reporte = ReporteAvance::create(['trabajador_id' => $this->trabajadorA->id, 'fecha_presentacion' => today()]);
        ActividadReporte::create(['reporte_avance_id' => $reporte->id, 'descripcion' => 'Histórico', 'archivo' => 'historico.txt']);
        Storage::disk('public')->put('historico.txt', 'Contenido');
        Storage::disk('public')->put('solicitudes/huerfano.txt', 'Adjunto sin registro');
        $this->artisan('empresas:privatizar-archivos')->assertSuccessful();
        Storage::disk('local')->assertExists('historico.txt');
        Storage::disk('public')->assertMissing('historico.txt');
        Storage::disk('public')->assertMissing('solicitudes/huerfano.txt');
        Storage::disk('local')->assertExists('solicitudes/huerfano.txt');
        $this->assertSame('Contenido', Storage::disk('local')->get('historico.txt'));
        $this->artisan('empresas:privatizar-archivos')->assertSuccessful();
    }

    public function test_alta_y_rotacion_de_tokens_solo_para_dispositivos_de_la_empresa(): void
    {
        $this->actingAs($this->user)->withSession(['empresa_id' => $this->a->id])
            ->post(route('empresas.dispositivos.store'), ['nombre' => 'Puerta A', 'serial' => 'SERIAL-A'])
            ->assertSessionHasNoErrors()->assertSessionHas('dispositivo_token');
        $dispositivo = DispositivoBiometrico::where('serial', 'SERIAL-A')->firstOrFail();
        $token = session('dispositivo_token');
        $this->assertSame(hash('sha256', $token), $dispositivo->token_hash);
        $this->get('/iclock/ping?SN=SERIAL-A&token='.$token)->assertOk();
        $this->post(route('empresas.dispositivos.token', $dispositivo))->assertSessionHas('dispositivo_token');
        $this->get('/iclock/ping?SN=SERIAL-A&token='.$token)->assertForbidden();
    }
}
