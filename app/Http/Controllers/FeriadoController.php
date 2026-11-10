<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Feriado;
use App\Support\EmpresaRules;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class FeriadoController extends Controller
{
    // Calendario principal con filtros
    public function index(Request $request): View
    {
        $this->verificarAdministrador();

        $anio = (int) $request->input('anio', now()->year);
        $mes = (int) $request->input('mes', now()->month);
        $anio = $anio >= 2020 && $anio <= 2100 ? $anio : now()->year;
        $mes = $mes >= 1 && $mes <= 12 ? $mes : now()->month;
        $periodo = Carbon::create($anio, $mes, 1)->startOfDay();
        $inicioMes = $periodo->copy()->startOfMonth();
        $finMes = $periodo->copy()->endOfMonth();

        $query = Feriado::with('area');

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);
            $query->where(function ($q) use ($buscar) {
                $q
                    ->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado === 'ACTIVO');
        }

        if ($request->filled('area_id')) {
            if ($request->area_id === 'GENERAL') {
                $query->whereNull('area_id');
            } else {
                $query->where('area_id', $request->area_id);
            }
        }

        $query->whereBetween('fecha', [$inicioMes->toDateString(), $finMes->toDateString()]);

        $feriados = $query->orderBy('fecha')->orderBy('nombre')->get();
        $feriadosPorFecha = $feriados->groupBy(
            fn (Feriado $feriado) => $feriado->fecha->toDateString()
        );
        $inicioCalendario = $inicioMes->copy()->startOfWeek(Carbon::MONDAY);
        $finCalendario = $finMes->copy()->endOfWeek(Carbon::SUNDAY);
        $calendario = collect();

        for ($fecha = $inicioCalendario->copy(); $fecha->lte($finCalendario); $fecha->addDay()) {
            $calendario->push([
                'date' => $fecha->toDateString(),
                'day' => $fecha->day,
                'month_short' => $fecha->locale('es')->translatedFormat('M'),
                'is_current_month' => $fecha->month === $mes,
                'is_today' => $fecha->isToday(),
                'holidays' => $feriadosPorFecha->get($fecha->toDateString(), collect()),
            ]);
        }

        $areas = Area::orderBy('nombre')->get();
        $meses = collect(range(1, 12))->mapWithKeys(fn (int $numero) => [
            $numero => Carbon::create(2000, $numero, 1)->locale('es')->translatedFormat('F'),
        ]);
        $mesAnterior = $periodo->copy()->subMonth();
        $mesSiguiente = $periodo->copy()->addMonth();
        $fechaSugerida = today()->betweenIncluded($inicioMes, $finMes)
            ? today()->toDateString()
            : $inicioMes->toDateString();
        $resumen = [
            'total' => $feriados->count(),
            'activos' => $feriados->where('estado', true)->count(),
            'nacionales' => $feriados->where('tipo', 'NACIONAL')->count(),
            'generales' => $feriados->whereNull('area_id')->count(),
        ];

        return view('feriados.index', compact(
            'feriados',
            'areas',
            'anio',
            'mes',
            'periodo',
            'calendario',
            'meses',
            'mesAnterior',
            'mesSiguiente',
            'fechaSugerida',
            'resumen'
        ));
    }

    // Registro manual
    public function store(Request $request): RedirectResponse
    {
        $this->verificarAdministrador();

        $data = $this->validarFeriado($request);

        Feriado::create([
            'area_id' => $data['area_id'] ?? null,
            'fecha' => $data['fecha'],
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'descripcion' => $data['descripcion'] ?? null,
            'estado' => true,
        ]);

        return $this->redirigirAlCalendario(
            $request,
            'Feriado registrado correctamente.',
            $data['fecha']
        );
    }

    // Actualización de feriado
    public function update(Request $request, Feriado $feriado): RedirectResponse
    {
        $this->verificarAdministrador();

        $data = $this->validarFeriado($request);

        $feriado->update([
            'area_id' => $data['area_id'] ?? null,
            'fecha' => $data['fecha'],
            'nombre' => $data['nombre'],
            'tipo' => $data['tipo'],
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        return $this->redirigirAlCalendario(
            $request,
            'Feriado actualizado correctamente.',
            $data['fecha']
        );
    }

    // Alternar estado activo/inactivo
    public function cambiarEstado(Request $request, Feriado $feriado): RedirectResponse
    {
        $this->verificarAdministrador();

        $feriado->update(['estado' => ! $feriado->estado]);

        $mensaje = $feriado->estado
            ? 'Feriado activado correctamente.'
            : 'Feriado desactivado correctamente.';

        return $this->redirigirAlCalendario(
            $request,
            $mensaje,
            $feriado->fecha->toDateString()
        );
    }

    // Sincronización automática de feriados nacionales con Nager.Date API
    public function sincronizarNacionales(Request $request): RedirectResponse
    {
        $this->verificarAdministrador();

        $data = $request->validate([
            'anio' => ['required', 'integer', 'min:2020', 'max:2100'],
            'mes' => ['nullable', 'integer', 'between:1,12'],
        ], [
            'anio.required' => 'Debes indicar el año a sincronizar.',
            'anio.integer' => 'El año ingresado no es válido.',
        ]);

        $anio = (int) $data['anio'];

        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->retry(2, 500)
                ->get("https://date.nager.at/api/v3/PublicHolidays/{$anio}/PE");
        } catch (Throwable $e) {
            return back()->with('error', 'No fue posible conectarse con Nager.Date. Intenta nuevamente.');
        }

        if (! $response->successful()) {
            return back()->with('error', 'Nager.Date no pudo devolver los feriados solicitados.');
        }

        $datosApi = $response->json();

        if (! is_array($datosApi) || count($datosApi) === 0) {
            return back()->with('error', "No se encontraron feriados para el año {$anio}.");
        }

        // Filtrar exclusivamente días feriados públicos nacionales
        $feriadosApi = collect($datosApi)->filter(function ($item) {
            if (empty($item['date'] ?? null)) {
                return false;
            }

            return ! array_key_exists('global', $item) || $item['global'] !== false;
        })->values();

        if ($feriadosApi->isEmpty()) {
            return back()->with('error', "Nager.Date no devolvió feriados nacionales para {$anio}.");
        }

        $fechasApi = $feriadosApi->pluck('date')->unique()->values()->all();

        $creados = 0;
        $actualizados = 0;
        $desactivados = 0;

        DB::transaction(function () use (
            $feriadosApi,
            $fechasApi,
            $anio,
            &$creados,
            &$actualizados,
            &$desactivados
        ) {
            foreach ($feriadosApi as $item) {
                $nombre = $item['localName'] ?? $item['name'] ?? 'Feriado nacional';

                $existente = Feriado::whereDate('fecha', $item['date'])
                    ->where('tipo', 'NACIONAL')
                    ->whereNull('area_id')
                    ->first();

                if ($existente) {
                    $existente->update([
                        'nombre' => $nombre,
                        'descripcion' => 'Feriado nacional sincronizado con Nager.Date.',
                        'estado' => true,
                    ]);
                    $actualizados++;
                } else {
                    Feriado::create([
                        'area_id' => null,
                        'fecha' => $item['date'],
                        'nombre' => $nombre,
                        'tipo' => 'NACIONAL',
                        'descripcion' => 'Feriado nacional sincronizado con Nager.Date.',
                        'estado' => true,
                    ]);
                    $creados++;
                }
            }

            // Desactivar feriados nacionales del año que ya no figuran en la API
            $consultaDesactivar = Feriado::where('tipo', 'NACIONAL')
                ->whereNull('area_id')
                ->whereYear('fecha', $anio)
                ->where('estado', true);

            if (! empty($fechasApi)) {
                $consultaDesactivar->whereNotIn('fecha', $fechasApi);
            }

            $desactivados = $consultaDesactivar->count();
            $consultaDesactivar->update(['estado' => false]);
        });

        return redirect()
            ->route('feriados.index', [
                'anio' => $anio,
                'mes' => (int) ($data['mes'] ?? now()->month),
            ])
            ->with(
                'success',
                "Sincronización {$anio} completada: {$creados} creados, {$actualizados} actualizados y {$desactivados} desactivados."
            );
    }

    // Reglas de validación comunes
    private function validarFeriado(Request $request): array
    {
        return $request->validate([
            'area_id' => ['nullable', EmpresaRules::exists('areas')],
            'fecha' => ['required', 'date'],
            'nombre' => ['required', 'string', 'max:150'],
            'tipo' => [
                'required',
                Rule::in(['NACIONAL', 'REGIONAL', 'LOCAL', 'INSTITUCIONAL']),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function redirigirAlCalendario(
        Request $request,
        string $mensaje,
        string $fechaReferencia
    ): RedirectResponse {
        $fecha = Carbon::parse($fechaReferencia);
        $anio = (int) $request->input('return_anio', $fecha->year);
        $mes = (int) $request->input('return_mes', $fecha->month);

        if ($anio < 2020 || $anio > 2100) {
            $anio = $fecha->year;
        }
        if ($mes < 1 || $mes > 12) {
            $mes = $fecha->month;
        }

        return redirect()
            ->route('feriados.index', ['anio' => $anio, 'mes' => $mes])
            ->with('success', $mensaje);
    }

    // Control de acceso para administradores
    private function verificarAdministrador(): void
    {
        abort_unless(
            Auth::check() && Auth::user()->roleCode() === 'ADMINISTRADOR',
            403
        );
    }
}
