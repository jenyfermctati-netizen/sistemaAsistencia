<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'horarios';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'formato_hora',
        'estado',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function dias()
    {
        return $this->hasMany(
            HorarioDia::class,
            'horario_id'
        )->orderBy('dia_semana');
    }

    public function trabajadorHorarios()
    {
        return $this->hasMany(
            TrabajadorHorario::class,
            'horario_id'
        );
    }

    public function trabajadores()
    {
        return $this->belongsToMany(
            Trabajador::class,
            'trabajador_horarios',
            'horario_id',
            'trabajador_id'
        )
            ->withPivot([
                'tipo_control',
                'fecha_inicio',
                'fecha_fin',
                'estado',
            ])
            ->withTimestamps();
    }
}
