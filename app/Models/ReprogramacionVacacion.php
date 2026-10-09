<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReprogramacionVacacion extends Model
{
    use HasFactory;

    protected $table = 'reprogramaciones_vacaciones';

    protected $fillable = [
        'vacaciones_id',
        'fecha_inicio_anterior',
        'fecha_fin_anterior',
        'fecha_inicio_nueva',
        'fecha_fin_nueva',
        'dias_nuevos',
        'motivo',
        'reprogramado_por',
    ];

    protected $casts = [
        'fecha_inicio_anterior' => 'date',
        'fecha_fin_anterior' => 'date',
        'fecha_inicio_nueva' => 'date',
        'fecha_fin_nueva' => 'date',
        'dias_nuevos' => 'integer',
    ];

    public function vacaciones()
    {
        return $this->belongsTo(
            Vacaciones::class,
            'vacaciones_id'
        );
    }

    public function reprogramadoPor()
    {
        return $this->belongsTo(
            User::class,
            'reprogramado_por'
        );
    }
}