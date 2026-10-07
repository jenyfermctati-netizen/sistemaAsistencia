<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    protected $fillable = [
        'trabajador_id',
        'asistencia_id',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'motivo',
        'archivo',
        'estado',
        'revisado_por',
        'comentario_revision',
        'fecha_revision',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_revision' => 'datetime',
    ];

    public function trabajador()
    {
        return $this->belongsTo(
            Trabajador::class,
            'trabajador_id'
        );
    }

    public function asistencia()
    {
        return $this->belongsTo(
            Asistencia::class,
            'asistencia_id'
        );
    }

    public function revisadoPor()
    {
        return $this->belongsTo(
            User::class,
            'revisado_por'
        );
    }
}