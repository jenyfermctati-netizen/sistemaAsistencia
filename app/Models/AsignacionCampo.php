<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionCampo extends Model
{
    use HasFactory;

    protected $table = 'asignaciones';

    protected $fillable = [
        'trabajador_id',
        'fecha_inicio',
        'fecha_fin',
        'lugar',
        'actividad',
        'estado',
        'autorizado_por',
        'observacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function trabajador()
    {
        return $this->belongsTo(Trabajador::class, 'trabajador_id');
    }

    public function autorizadoPor()
    {
        return $this->belongsTo(User::class, 'autorizado_por');
    }
}
