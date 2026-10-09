<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trabajador extends Model
{
    use HasFactory;

    protected $table = 'trabajadores';

    protected $fillable = [
        'area_id',
        'codigo_biometrico',
        'dni',
        'nombres',
        'apellidos',
        'tipo_vinculo',
        'cargo',
        'telefono',
        'fecha_ingreso',
        'fecha_fin',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function area()
    {
        return $this->belongsTo(
            Area::class,
            'area_id'
        );
    }

    public function user()
    {
        return $this->hasOne(
            User::class,
            'trabajador_id'
        );
    }

    public function trabajadorHorarios()
    {
        return $this->hasMany(
            TrabajadorHorario::class,
            'trabajador_id'
        );
    }

    public function horarios()
    {
        return $this
            ->belongsToMany(
                Horario::class,
                'trabajador_horarios',
                'trabajador_id',
                'horario_id'
            )
            ->withPivot([
                'tipo_control',
                'fecha_inicio',
                'fecha_fin',
                'estado',
            ])
            ->withTimestamps();
    }

    public function horarioActual()
    {
        return $this
            ->hasOne(
                TrabajadorHorario::class,
                'trabajador_id'
            )
            ->where('estado', true)
            ->whereDate(
                'fecha_inicio',
                '<=',
                now()->toDateString()
            )
            ->where(function ($query) {

                $query
                    ->whereNull('fecha_fin')
                    ->orWhereDate(
                        'fecha_fin',
                        '>=',
                        now()->toDateString()
                    );
            })
            ->latest('fecha_inicio');
    }

    public function solicitudes()
    {
        return $this->hasMany(
            Solicitud::class,
            'trabajador_id'
        );
    }
    public function vacaciones()
{
    return $this->hasMany(
        Vacaciones::class,
        'trabajador_id'
    );
}
}