<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HorarioDia extends Model
{
    use HasFactory;

    protected $table = 'horario_dias';

    protected $fillable = [
        'horario_id',
        'dia_semana',
        'es_laborable',
        'hora_entrada',
        'hora_salida',
        'tolerancia_minutos',
    ];

    protected function casts(): array
    {
        return [
            'es_laborable' => 'boolean',
            'dia_semana' => 'integer',
            'tolerancia_minutos' => 'integer',
        ];
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class, 'horario_id');
    }

    public function getNombreDiaAttribute(): string
    {
        return match ($this->dia_semana) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => 'Desconocido',
        };
    }
}
