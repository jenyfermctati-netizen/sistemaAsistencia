<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\PendienteAvance;
use App\Models\ReporteAvance;
use App\Models\Rol;
use App\Models\Trabajador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteAvanceAreasTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_puede_asignar_un_pendiente_a_un_area(): void
    {
        $administrador = $this->crearAdministrador();
        $area = Area::create(['nombre' => 'Contabilidad', 'estado' => true]);

        $response = $this->actingAs($administrador)
            ->post(route('reportesAvance.pendientes.store'), [
                'area_id' => $area->id,
                'descripcion' => 'Preparar cierre mensual',
                'detalle' => 'Consolidar comprobantes y validar saldos.',
                'ruta_archivos' => '\\Servidor\Contabilidad\Cierre',
            ]);

        $response
            ->assertRedirect(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pendientes_avance', [
            'reporte_avance_id' => null,
            'area_id' => $area->id,
            'creado_por' => $administrador->id,
            'origen' => 'ADMINISTRADOR',
            'descripcion' => 'Preparar cierre mensual',
            'estado' => 'PENDIENTE',
        ]);
    }

    public function test_administrador_y_trabajador_utilizan_interfaces_separadas(): void
    {
        $administrador = $this->crearAdministrador();
        $area = Area::create(['nombre' => 'Sistemas', 'estado' => true]);
        [$usuarioTrabajador] = $this->crearTrabajador($area, '70000020');

        $this->actingAs($administrador)
            ->get(route('reportesAvance.index'))
            ->assertOk()
            ->assertViewIs('reportesAvance.admin.index')
            ->assertSee('Revisión de actividades y pendientes del personal');

        $this->actingAs($usuarioTrabajador)
            ->get(route('reportesAvance.index'))
            ->assertOk()
            ->assertViewIs('reportesAvance.trabajador.index')
            ->assertSee('Registra las actividades realizadas durante el día');
    }

    public function test_trabajador_registra_multiples_actividades_y_pendientes_para_su_area(): void
    {
        $area = Area::create(['nombre' => 'Tecnología', 'estado' => true]);
        [$usuario, $trabajador] = $this->crearTrabajador($area, '70000001');

        $response = $this->actingAs($usuario)
            ->post(route('reportesAvance.store'), [
                'actividades' => [
                    ['descripcion' => 'Configuré el servidor'],
                    ['descripcion' => 'Documenté el despliegue'],
                    ['descripcion' => 'Validé las copias de seguridad'],
                ],
                'pendientes' => [
                    [
                        'descripcion' => 'Revisar alertas nocturnas',
                        'detalle' => 'Confirmar que no existan falsos positivos.',
                    ],
                    [
                        'descripcion' => 'Actualizar inventario',
                        'ruta_archivos' => '\\Servidor\TI\Inventario',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('reportesAvance.index'))
            ->assertSessionHas('success');

        $reporte = ReporteAvance::where('trabajador_id', $trabajador->id)->firstOrFail();
        $this->assertCount(3, $reporte->actividades);
        $this->assertCount(2, $reporte->pendientes);

        foreach ($reporte->pendientes as $pendiente) {
            $this->assertSame($area->id, $pendiente->area_id);
            $this->assertSame($usuario->id, $pendiente->creado_por);
            $this->assertSame('TRABAJADOR', $pendiente->origen);
        }
    }

    public function test_administrador_registra_el_avance_de_un_trabajador_desde_el_modulo_de_personal(): void
    {
        $area = Area::create(['nombre' => 'Operaciones', 'estado' => true]);
        $administrador = $this->crearAdministrador();
        [, $trabajador] = $this->crearTrabajador($area, '70000004');

        $this->actingAs($administrador)
            ->get(route('trabajadores.index'))
            ->assertOk()
            ->assertSee('Registrar avance')
            ->assertSee(route('trabajadores.reportes-avance.store', $trabajador));

        $response = $this->actingAs($administrador)
            ->post(route('trabajadores.reportes-avance.store', $trabajador), [
                'reporte_trabajador_id' => $trabajador->id,
                'reporte_trabajador_nombre' => 'Trabajador 70000004',
                'reporte_trabajador_area' => $area->nombre,
                'actividades' => [
                    ['descripcion' => 'Verificó el inventario del almacén'],
                    ['descripcion' => 'Coordinó la salida de materiales'],
                ],
                'pendientes' => [
                    ['descripcion' => 'Completar el acta de entrega'],
                ],
            ]);

        $response
            ->assertRedirect(route('trabajadores.index'))
            ->assertSessionHas('success');

        $reporte = ReporteAvance::where('trabajador_id', $trabajador->id)->firstOrFail();

        $this->assertCount(2, $reporte->actividades);
        $this->assertDatabaseHas('pendientes_avance', [
            'reporte_avance_id' => $reporte->id,
            'area_id' => $area->id,
            'creado_por' => $administrador->id,
            'origen' => 'ADMINISTRADOR',
            'descripcion' => 'Completar el acta de entrega',
        ]);

        $this->actingAs($administrador)
            ->get(route('trabajadores.index'))
            ->assertOk()
            ->assertSee('Avance registrado hoy');
    }

    public function test_trabajador_agrega_actividades_al_reporte_revisado_y_ve_sus_pendientes_en_una_pestana(): void
    {
        $area = Area::create(['nombre' => 'Desarrollo', 'estado' => true]);
        [$usuario, $trabajador] = $this->crearTrabajador($area, '70000007');
        $administrador = $this->crearAdministrador();
        $reporte = ReporteAvance::create([
            'trabajador_id' => $trabajador->id,
            'fecha_presentacion' => today(),
            'estado' => 'REVISADO',
            'revisado_por' => $administrador->id,
            'comentario_revision' => 'Primer avance conforme.',
            'fecha_revision' => now(),
        ]);
        $reporte->actividades()->create([
            'descripcion' => 'Implementó la vista inicial',
        ]);

        $this->actingAs($usuario)
            ->get(route('reportesAvance.index'))
            ->assertOk()
            ->assertSee('+ Agregar avance')
            ->assertSee(route('reportesAvance.agregar', $reporte));

        $response = $this->actingAs($usuario)
            ->post(route('reportesAvance.agregar', $reporte), [
                'actividades' => [
                    ['descripcion' => 'Completó las validaciones'],
                    ['descripcion' => 'Probó el flujo del formulario'],
                ],
                'pendientes' => [
                    [
                        'descripcion' => 'Revisar el diseño móvil',
                        'detalle' => 'Validar el formulario en una pantalla pequeña.',
                    ],
                ],
            ]);

        $response
            ->assertRedirect(route('reportesAvance.index'))
            ->assertSessionHas('success');

        $reporte->refresh();

        $this->assertSame('PENDIENTE', $reporte->estado);
        $this->assertNull($reporte->revisado_por);
        $this->assertNull($reporte->comentario_revision);
        $this->assertCount(3, $reporte->actividades);
        $this->assertDatabaseHas('pendientes_avance', [
            'reporte_avance_id' => $reporte->id,
            'area_id' => $area->id,
            'creado_por' => $usuario->id,
            'origen' => 'TRABAJADOR',
            'descripcion' => 'Revisar el diseño móvil',
            'estado' => 'PENDIENTE',
        ]);

        $this->actingAs($usuario)
            ->get(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertOk()
            ->assertSee('Pendientes de tu área')
            ->assertSee('Revisar el diseño móvil');
    }

    public function test_un_trabajador_no_puede_registrar_avances_por_otro_desde_personal(): void
    {
        $area = Area::create(['nombre' => 'Recursos Humanos', 'estado' => true]);
        [$usuario] = $this->crearTrabajador($area, '70000005');
        [, $otroTrabajador] = $this->crearTrabajador($area, '70000006');

        $this->actingAs($usuario)
            ->post(route('trabajadores.reportes-avance.store', $otroTrabajador), [
                'actividades' => [
                    ['descripcion' => 'Actividad no autorizada'],
                ],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('reportes_avance', [
            'trabajador_id' => $otroTrabajador->id,
        ]);
    }

    public function test_administrador_filtra_avances_y_pendientes_por_area(): void
    {
        $administrador = $this->crearAdministrador();
        $areaTecnologia = Area::create(['nombre' => 'Tecnología', 'estado' => true]);
        $areaLogistica = Area::create(['nombre' => 'Logística', 'estado' => true]);
        [, $trabajadorTecnologia] = $this->crearTrabajador($areaTecnologia, '70000008');
        [, $trabajadorLogistica] = $this->crearTrabajador($areaLogistica, '70000009');

        ReporteAvance::create([
            'trabajador_id' => $trabajadorTecnologia->id,
            'fecha_presentacion' => today(),
            'estado' => 'PENDIENTE',
        ])->actividades()->create(['descripcion' => 'Actividad exclusiva de tecnología']);

        ReporteAvance::create([
            'trabajador_id' => $trabajadorLogistica->id,
            'fecha_presentacion' => today(),
            'estado' => 'PENDIENTE',
        ])->actividades()->create(['descripcion' => 'Actividad exclusiva de logística']);

        PendienteAvance::create([
            'area_id' => $areaTecnologia->id,
            'creado_por' => $administrador->id,
            'origen' => 'ADMINISTRADOR',
            'descripcion' => 'Pendiente exclusivo de tecnología',
            'estado' => 'PENDIENTE',
        ]);
        PendienteAvance::create([
            'area_id' => $areaLogistica->id,
            'creado_por' => $administrador->id,
            'origen' => 'ADMINISTRADOR',
            'descripcion' => 'Pendiente exclusivo de logística',
            'estado' => 'PENDIENTE',
        ]);

        $this->actingAs($administrador)
            ->get(route('reportesAvance.index', [
                'pestana' => 'avances',
                'area_id' => $areaTecnologia->id,
            ]))
            ->assertOk()
            ->assertSee('Actividad exclusiva de tecnología')
            ->assertDontSee('Actividad exclusiva de logística');

        $this->actingAs($administrador)
            ->get(route('reportesAvance.index', [
                'pestana' => 'pendientes',
                'area_id' => $areaLogistica->id,
            ]))
            ->assertOk()
            ->assertSee('Pendiente exclusivo de logística')
            ->assertDontSee('Pendiente exclusivo de tecnología');
    }

    public function test_administrador_ve_revision_compacta_con_iconos_y_no_tarjetas_redundantes(): void
    {
        $administrador = $this->crearAdministrador();
        $area = Area::create(['nombre' => 'Legal', 'estado' => true]);
        [, $trabajador] = $this->crearTrabajador($area, '70000010');
        $reporte = ReporteAvance::create([
            'trabajador_id' => $trabajador->id,
            'fecha_presentacion' => today(),
            'estado' => 'PENDIENTE',
        ]);
        $reporte->actividades()->create(['descripcion' => 'Revisó documentos contractuales']);
        $reporte->pendientes()->create([
            'area_id' => $area->id,
            'descripcion' => 'Pendiente que requiere revisión',
            'estado' => 'PENDIENTE',
            'estado_revision' => 'POR_REVISAR',
        ]);
        $reporte->pendientes()->create([
            'area_id' => $area->id,
            'descripcion' => 'Pendiente histórico ya completado',
            'estado' => 'RESUELTO',
            'estado_revision' => 'APROBADO',
        ]);

        $response = $this->actingAs($administrador)
            ->get(route('reportesAvance.index', ['pestana' => 'avances']))
            ->assertOk()
            ->assertSee('pendientes-revision-compacta', false)
            ->assertSee('Aprobar como completado')
            ->assertDontSee('class="reporte-pendiente"', false)
            ->assertDontSee('data-revisar-reporte', false)
            ->assertDontSee('data-ver-reporte', false);

        $this->assertStringNotContainsString(
            '<span>Pendiente histórico ya completado</span>',
            $response->getContent()
        );
    }

    public function test_trabajador_registra_pendiente_para_su_area_y_otro_integrante_lo_finaliza(): void
    {
        $administrador = $this->crearAdministrador();
        $areaTecnologia = Area::create(['nombre' => 'Tecnología y Desarrollo', 'estado' => true]);
        $otraArea = Area::create(['nombre' => 'Recursos Humanos', 'estado' => true]);
        [$usuarioCreador] = $this->crearTrabajador($areaTecnologia, '70000011');
        [$usuarioEjecutor, $trabajadorEjecutor] = $this->crearTrabajador($areaTecnologia, '70000012');
        [$usuarioOtraArea] = $this->crearTrabajador($otraArea, '70000013');

        $this->actingAs($usuarioCreador)
            ->post(route('reportesAvance.pendientes.trabajador.store'), [
                'descripcion' => 'Completar integración del módulo RRHH',
                'detalle' => 'Validar el acceso y terminar las pruebas.',
            ])
            ->assertRedirect(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertSessionHas('success');

        $pendiente = PendienteAvance::where('descripcion', 'Completar integración del módulo RRHH')
            ->firstOrFail();

        $this->assertSame($areaTecnologia->id, $pendiente->area_id);
        $this->assertSame($usuarioCreador->id, $pendiente->creado_por);

        $this->actingAs($usuarioEjecutor)
            ->get(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertOk()
            ->assertSee('Completar integración del módulo RRHH')
            ->assertSee('Marcar como finalizado');

        $this->actingAs($usuarioOtraArea)
            ->get(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertOk()
            ->assertDontSee('Completar integración del módulo RRHH');

        $this->actingAs($usuarioEjecutor)
            ->patch(route('reportesAvance.pendientes.resolver', $pendiente))
            ->assertRedirect(route('reportesAvance.index', ['pestana' => 'pendientes']));

        $this->assertDatabaseHas('pendientes_avance', [
            'id' => $pendiente->id,
            'estado' => 'PENDIENTE',
            'estado_revision' => 'POR_REVISAR',
            'resuelto_por' => $trabajadorEjecutor->id,
        ]);

        $this->actingAs($administrador)
            ->get(route('reportesAvance.index', ['pestana' => 'avances']))
            ->assertOk()
            ->assertSee('Avances por revisar')
            ->assertSee('Completar integración del módulo RRHH')
            ->assertSee('70000012')
            ->assertSee('Aprobar como completado');
    }

    public function test_pendiente_es_visible_y_resoluble_por_el_area_pero_no_por_otra_area(): void
    {
        $areaResponsable = Area::create(['nombre' => 'Logística', 'estado' => true]);
        $otraArea = Area::create(['nombre' => 'Ventas', 'estado' => true]);
        $administrador = $this->crearAdministrador();
        [$usuarioArea, $trabajadorArea] = $this->crearTrabajador($areaResponsable, '70000002');
        [$usuarioExterno] = $this->crearTrabajador($otraArea, '70000003');

        $pendiente = PendienteAvance::create([
            'area_id' => $areaResponsable->id,
            'creado_por' => $administrador->id,
            'origen' => 'ADMINISTRADOR',
            'descripcion' => 'Coordinar recepción del pedido',
            'estado' => 'PENDIENTE',
        ]);

        $this->actingAs($usuarioArea)
            ->get(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertOk()
            ->assertSee('Coordinar recepción del pedido');

        $this->actingAs($usuarioExterno)
            ->get(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertOk()
            ->assertDontSee('Coordinar recepción del pedido');

        $this->actingAs($usuarioExterno)
            ->patch(route('reportesAvance.pendientes.resolver', $pendiente))
            ->assertForbidden();

        $this->actingAs($usuarioArea)
            ->patch(route('reportesAvance.pendientes.resolver', $pendiente))
            ->assertRedirect(route('reportesAvance.index', ['pestana' => 'pendientes']));

        $this->assertDatabaseHas('pendientes_avance', [
            'id' => $pendiente->id,
            'estado' => 'PENDIENTE',
            'estado_revision' => 'POR_REVISAR',
            'resuelto_por' => $trabajadorArea->id,
        ]);

        $this->actingAs($administrador)
            ->get(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertOk()
            ->assertSee('Aprobar como completado')
            ->assertSee('Registrar una observación');

        $this->actingAs($administrador)
            ->patch(route('reportesAvance.pendientes.revisar', $pendiente), [
                'decision' => 'OBSERVADO',
                'observacion_revision' => 'Falta adjuntar el acta de recepción.',
            ])
            ->assertRedirect(route('reportesAvance.index', ['pestana' => 'pendientes']));

        $this->assertDatabaseHas('pendientes_avance', [
            'id' => $pendiente->id,
            'estado' => 'PENDIENTE',
            'estado_revision' => 'OBSERVADO',
            'observacion_revision' => 'Falta adjuntar el acta de recepción.',
            'revisado_por' => $administrador->id,
        ]);

        $this->actingAs($usuarioArea)
            ->get(route('reportesAvance.index', ['pestana' => 'pendientes']))
            ->assertOk()
            ->assertSee('Falta adjuntar el acta de recepción.')
            ->assertSee('Marcar nuevamente como finalizado');

        $this->actingAs($usuarioArea)
            ->patch(route('reportesAvance.pendientes.resolver', $pendiente))
            ->assertRedirect(route('reportesAvance.index', ['pestana' => 'pendientes']));

        $this->actingAs($administrador)
            ->patch(route('reportesAvance.pendientes.revisar', $pendiente), [
                'decision' => 'APROBADO',
            ])
            ->assertRedirect(route('reportesAvance.index', ['pestana' => 'pendientes']));

        $this->assertDatabaseHas('pendientes_avance', [
            'id' => $pendiente->id,
            'estado' => 'RESUELTO',
            'estado_revision' => 'APROBADO',
            'revisado_por' => $administrador->id,
        ]);
    }

    private function crearAdministrador(): User
    {
        $rol = Rol::firstOrCreate(
            ['nombre' => 'ADMINISTRADOR'],
            ['estado' => true]
        );

        return User::create([
            'rol_id' => $rol->id,
            'name' => 'Administrador de avances',
            'email' => 'admin.avances'.User::count().'@example.com',
            'password' => 'secret',
            'estado' => true,
        ]);
    }

    private function crearTrabajador(Area $area, string $dni): array
    {
        $rol = Rol::firstOrCreate(
            ['nombre' => 'TRABAJADOR'],
            ['estado' => true]
        );
        $trabajador = Trabajador::create([
            'area_id' => $area->id,
            'dni' => $dni,
            'nombres' => 'Trabajador',
            'apellidos' => $dni,
            'tipo_vinculo' => 'CONTRATADO',
            'estado' => 'ACTIVO',
        ]);
        $usuario = User::create([
            'trabajador_id' => $trabajador->id,
            'rol_id' => $rol->id,
            'name' => "Trabajador {$dni}",
            'email' => "{$dni}@example.com",
            'password' => 'secret',
            'estado' => true,
        ]);

        return [$usuario, $trabajador];
    }
}
