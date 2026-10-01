<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    use HasFactory;

    protected $table = 'asistencias';

    protected $fillable = [
        'trabajador_id',
        'horario_id',
        'fecha',
        'tipo_control_aplicado',
        'hora_programada_entrada',
        'hora_programada_salida',
        'tolerancia_aplicada',
        'marcacion_entrada_id',
        'marcacion_salida_id',
        'hora_entrada',
        'hora_salida',
        'minutos_trabajados',
        'minutos_tardanza',
        'estado',
        'observacion',
        'procesado_en',
    ];

    protected $casts = [
        'fecha' => 'date',
        'procesado_en' => 'datetime',
        'tolerancia_aplicada' => 'integer',
        'minutos_trabajados' => 'integer',
        'minutos_tardanza' => 'integer',
    ];

    public function trabajador()
    {
        return $this->belongsTo(
            Trabajador::class,
            'trabajador_id'
        );
    }

    public function horario()
    {
        return $this->belongsTo(
            Horario::class,
            'horario_id'
        );
    }

    public function marcacionEntrada()
    {
        return $this->belongsTo(
            Marcacion::class,
            'marcacion_entrada_id'
        );
    }

    public function marcacionSalida()
    {
        return $this->belongsTo(
            Marcacion::class,
            'marcacion_salida_id'
        );
    }
}