<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacaciones extends Model
{
    use HasFactory;

    protected $table = 'vacaciones';

    protected $fillable = [
        'trabajador_id',
        'fecha_inicio',
        'fecha_fin',
        'dias_solicitados',
        'estado',
        'observacion',
        'registrado_por',
        'revisado_por',
        'comentario_revision',
        'fecha_revision',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_revision' => 'datetime',
        'dias_solicitados' => 'integer',
    ];

    public function trabajador()
    {
        return $this->belongsTo(
            Trabajador::class,
            'trabajador_id'
        );
    }

    public function registradoPor()
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    public function revisadoPor()
    {
        return $this->belongsTo(
            User::class,
            'revisado_por'
        );
    }

    public function reprogramaciones()
    {
        return $this->hasMany(
            ReprogramacionVacacion::class,
            'vacaciones_id'
        )->latest();
    }
}