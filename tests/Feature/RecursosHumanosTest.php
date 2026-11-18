<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Solicitud;
use App\Models\Trabajador;
use App\Models\User;
use App\Models\Vacaciones;
use App\Support\EmpresaContext;
use Database\Seeders\ControlAsistenciaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecursosHumanosTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private User $admin;
    private User $rrhh;
    private User $trabajador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ControlAsistenciaSeeder::class);
        $this->empresa = Empresa::create(['razon_social' => 'Empresa RRHH', 'ruc' => '20131312955', 'estado' => 'ACTIVA', 'zona_horaria' => 'America/Lima']);
        app(EmpresaContext::class)->activate($this->empresa);
        $area = Area::create(['nombre' => 'Personal', 'estado' => true]);
        $this->admin = $this->usuario('ADMINISTRADOR', $area, '10000001');
        $this->rrhh = $this->usuario('RECURSOS_HUMANOS', $area, '10000002');
        $this->trabajador = $this->usuario('TRABAJADOR', $area, '10000003');
    }

    protected function tearDown(): void
    {
        app(EmpresaContext::class)->clear();
        parent::tearDown();
    }

    private function usuario(string $rol, Area $area, string $dni): User
    {
        $trabajador = Trabajador::create([
            'area_id' => $area->id, 'dni' => $dni, 'nombres' => $rol,
            'apellidos' => 'Prueba', 'tipo_vinculo' => 'CONTRATADO', 'estado' => 'ACTIVO',
        ]);
        return User::create([
            'trabajador_id' => $trabajador->id, 'rol_id' => Rol::where('codigo', $rol)->value('id'),
            'name' => $rol, 'email' => $dni.'@test.example', 'password' => 'ClaveSegura123', 'estado' => true,
        ]);
    }

    public function test_rrhh_accede_a_modulos_operativos_y_no_a_configuracion(): void
    {
        $this->actingAs($this->rrhh);
        foreach (['dashboard', 'trabajadores.index', 'trabajadores.horarios', 'feriados.index',
            'marcaciones.index', 'marcaciones.manual', 'asistencias.index', 'solicitudes.index',
            'vacaciones.index', 'asignaciones-campo.index', 'reportesAvance.index'] as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
        foreach (['areas.index', 'biometrico.index', 'empresa.configuracion', 'empresa.auditoria', 'superadmin.dashboard'] as $ruta) {
            $this->get(route($ruta))->assertForbidden();
        }
        $this->post(route('asignaciones-campo.store'), [])->assertForbidden();
        $this->post(route('reportesAvance.store'), [])->assertForbidden();
        $this->post(route('empresa.administrador.transferir'), [])->assertForbidden();
    }

    public function test_rrhh_crea_trabajador_pero_no_eleva_roles_ni_modifica_privilegiados(): void
    {
        $this->actingAs($this->rrhh);
        $data = [
            'area_id' => $this->trabajador->trabajador->area_id, 'dni' => '10000004',
            'nombres' => 'Nuevo', 'apellidos' => 'Trabajador', 'tipo_vinculo' => 'CONTRATADO',
            'estado' => 'ACTIVO', 'email' => 'nuevo@test.example',
            'password' => 'ClaveSegura123', 'password_confirmation' => 'ClaveSegura123',
            'rol_id' => $this->admin->rol_id,
        ];
        $this->post(route('trabajadores.store'), $data)->assertSessionHasErrors('rol_id');
        $data['rol_id'] = $this->rrhh->rol_id;
        $this->post(route('trabajadores.store'), $data)->assertSessionHasErrors('rol_id');
        $data['rol_id'] = $this->trabajador->rol_id;
        $this->post(route('trabajadores.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'nuevo@test.example', 'rol_id' => $this->trabajador->rol_id]);
        foreach ([$this->admin, $this->rrhh] as $protegido) {
            $this->put(route('trabajadores.update', $protegido->trabajador_id), $data)->assertForbidden();
            $this->patch(route('trabajadores.estado', $protegido->trabajador_id))->assertForbidden();
        }
    }

    public function test_rrhh_revisa_solicitudes_y_vacaciones_ajenas_pero_no_propias(): void
    {
        $this->actingAs($this->rrhh);
        foreach ([$this->rrhh, $this->trabajador] as $usuario) {
            $vacacion = Vacaciones::create([
                'trabajador_id' => $usuario->trabajador_id, 'fecha_inicio' => today()->addMonth(),
                'fecha_fin' => today()->addMonth()->addDays(2), 'dias_solicitados' => 3,
                'estado' => 'PENDIENTE', 'registrado_por' => $usuario->id,
            ]);
            $solicitud = Solicitud::create([
                'trabajador_id' => $usuario->trabajador_id, 'tipo' => 'PERMISO',
                'fecha_inicio' => today(), 'fecha_fin' => today(), 'motivo' => 'Trámite', 'estado' => 'PENDIENTE',
            ]);
            $v = $this->patch(route('vacaciones.revisar', $vacacion), ['decision' => 'APROBADA']);
            $s = $this->patch(route('solicitudes.revisar', $solicitud), ['decision' => 'APROBADA']);
            if ($usuario->id === $this->rrhh->id) {
                $v->assertForbidden();
                $s->assertForbidden();
                $this->assertSame('PENDIENTE', $vacacion->fresh()->estado);
            } else {
                $v->assertSessionHasNoErrors();
                $s->assertSessionHasNoErrors();
                $this->assertSame($this->rrhh->id, $vacacion->fresh()->revisado_por);
                $this->assertSame($this->rrhh->id, $solicitud->fresh()->revisado_por);
            }
        }
    }

    public function test_rrhh_registra_vacaciones_y_marcaciones_con_motivo(): void
    {
        $this->actingAs($this->rrhh)->post(route('vacaciones.store'), [
            'trabajador_id' => $this->trabajador->trabajador_id,
            'fecha_inicio' => today()->addDays(20)->toDateString(), 'fecha_fin' => today()->addDays(22)->toDateString(),
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('vacaciones', ['trabajador_id' => $this->trabajador->trabajador_id, 'registrado_por' => $this->rrhh->id]);
        $this->post(route('marcaciones.marcarAhora', $this->trabajador->trabajador_id), ['hora' => '08:00'])
            ->assertSessionHasErrors('motivo_manual');
        $this->post(route('marcaciones.store'), [
            'trabajador_id' => $this->trabajador->trabajador_id, 'fecha_hora' => today()->format('Y-m-d').' 08:00:00',
            'tipo' => 'ENTRADA', 'motivo_manual' => 'Falla del dispositivo',
        ])->assertSessionHasNoErrors();
        $marca = \App\Models\Marcacion::firstOrFail();
        $this->patch(route('marcaciones.anular', $marca), ['motivo_anulacion' => 'Prueba'])->assertForbidden();
        $this->assertFalse($marca->fresh()->anulada);
    }

    public function test_rrhh_no_accede_a_trabajadores_de_otra_empresa(): void
    {
        $otra = Empresa::create(['razon_social' => 'Otra', 'ruc' => '20100070970', 'estado' => 'ACTIVA', 'zona_horaria' => 'America/Lima']);
        $ajeno = app(EmpresaContext::class)->run($otra, function () {
            return $this->usuario('TRABAJADOR', Area::create(['nombre' => 'Otra', 'estado' => true]), '20000001');
        });
        $this->actingAs($this->rrhh)->get(route('trabajadores.index'))->assertDontSee('20000001');
        $this->patch(route('trabajadores.estado', $ajeno->trabajador_id))->assertNotFound();
        $this->post(route('vacaciones.store'), ['trabajador_id' => $ajeno->trabajador_id])->assertSessionHasErrors('trabajador_id');
    }

    public function test_administrador_unico_no_se_desactiva_y_se_transfiere_atomicamente(): void
    {
        $this->actingAs($this->admin)->post(route('empresa.administrador.transferir'), [
            'usuario_id' => $this->rrhh->id, 'password' => 'incorrecta',
        ])->assertSessionHasErrors('password');
        $this->assertSame('ADMINISTRADOR', $this->admin->fresh()->roleCode());
        $this->post(route('empresa.administrador.transferir'), [
            'usuario_id' => $this->rrhh->id, 'password' => 'ClaveSegura123',
        ])->assertRedirect(route('login'));
        $this->assertSame('TRABAJADOR', $this->admin->fresh()->roleCode());
        $this->assertSame('ADMINISTRADOR', $this->rrhh->fresh()->roleCode());
        $this->assertSame(1, User::whereNotNull('administrador_empresa_id')->count());
        $this->assertDatabaseHas('auditoria', ['accion' => 'TRANSFERIR_ADMINISTRADOR', 'empresa_id' => $this->empresa->id]);
        $this->assertGuest();
    }

    public function test_no_se_puede_crear_segundo_administrador(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        User::create(['rol_id' => $this->admin->rol_id, 'name' => 'Duplicado', 'email' => 'duplicado@test.example', 'password' => 'ClaveSegura123', 'estado' => true]);
    }

    public function test_rrhh_aprueba_y_rechaza_avances_con_motivo_sin_revisar_los_propios(): void
    {
        $crearReporte = fn ($usuario) => \App\Models\ReporteAvance::create([
            'trabajador_id' => $usuario->trabajador_id, 'periodo_inicio' => today(),
            'periodo_fin' => today(), 'fecha_presentacion' => today(),
            'descripcion' => 'Avance de prueba', 'estado' => 'PENDIENTE',
        ]);
        $reporte = $crearReporte($this->trabajador);
        $propio = $crearReporte($this->rrhh);
        $this->actingAs($this->rrhh)->get(route('reportesAvance.index'))
            ->assertOk()->assertSee('Aprobar reporte')->assertSee('Rechazar con observación');
        $this->patch(route('reportesAvance.revisar', $reporte), ['estado' => 'OBSERVADO'])
            ->assertSessionHasErrors('comentario_revision');
        $this->assertSame('PENDIENTE', $reporte->fresh()->estado);
        $this->patch(route('reportesAvance.revisar', $reporte), [
            'estado' => 'OBSERVADO', 'comentario_revision' => 'Completar las evidencias.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('OBSERVADO', $reporte->fresh()->estado);
        $this->assertSame($this->rrhh->id, $reporte->fresh()->revisado_por);
        $this->patch(route('reportesAvance.revisar', $reporte), ['estado' => 'REVISADO'])
            ->assertSessionHasNoErrors();
        $this->assertSame('REVISADO', $reporte->fresh()->estado);
        foreach (['REVISADO', 'OBSERVADO'] as $estado) {
            $this->patch(route('reportesAvance.revisar', $propio), [
                'estado' => $estado, 'comentario_revision' => 'Intento propio',
            ])->assertForbidden();
        }
        $this->assertSame('PENDIENTE', $propio->fresh()->estado);
        $otra = Empresa::create(['razon_social' => 'Otra', 'ruc' => '20100070970', 'estado' => 'ACTIVA', 'zona_horaria' => 'America/Lima']);
        $ajeno = app(EmpresaContext::class)->run($otra, function () use ($crearReporte) {
            return $crearReporte($this->usuario('TRABAJADOR', Area::create(['nombre' => 'Otra', 'estado' => true]), '20000001'));
        });
        $this->patch(route('reportesAvance.revisar', $ajeno), ['estado' => 'REVISADO'])->assertNotFound();
    }

    public function test_no_se_puede_desactivar_administrador_desde_modelo(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->admin->update(['estado' => false]);
    }

    public function test_transferencia_no_acepta_usuario_de_otra_empresa(): void
    {
        $otra = Empresa::create(['razon_social' => 'Otra', 'ruc' => '20100070970', 'estado' => 'ACTIVA', 'zona_horaria' => 'America/Lima']);
        $ajeno = app(EmpresaContext::class)->run($otra, fn () => $this->usuario(
            'TRABAJADOR', Area::create(['nombre' => 'Otra', 'estado' => true]), '20000001'
        ));
        $this->actingAs($this->admin)->post(route('empresa.administrador.transferir'), [
            'usuario_id' => $ajeno->id, 'password' => 'ClaveSegura123',
        ])->assertSessionHasErrors('usuario_id');
        $this->assertSame('ADMINISTRADOR', $this->admin->fresh()->roleCode());
        $this->assertSame('TRABAJADOR', $ajeno->fresh()->roleCode());
    }

    public function test_indice_unico_impide_dos_reservas_de_administrador(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        \Illuminate\Support\Facades\DB::table('users')->where('id', $this->rrhh->id)
            ->update(['administrador_empresa_id' => $this->empresa->id]);
    }

    public function test_administrador_configura_solo_su_empresa_y_consulta_auditoria(): void
    {
        $this->actingAs($this->admin)->get(route('empresa.configuracion'))->assertOk();
        $this->put(route('empresa.configuracion.update'), [
            'nombre_comercial' => 'Nombre actualizado', 'zona_horaria' => 'America/Lima',
            'color_turquesa' => '#087F9A', 'estado' => 'SUSPENDIDA', 'ruc' => '12345678901',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Nombre actualizado', $this->empresa->fresh()->nombre_comercial);
        $this->assertSame('ACTIVA', $this->empresa->fresh()->estado);
        $this->assertSame('20131312955', $this->empresa->fresh()->ruc);
        $this->get(route('empresa.auditoria'))->assertOk()->assertSee('CONFIGURAR_EMPRESA');
    }
}
