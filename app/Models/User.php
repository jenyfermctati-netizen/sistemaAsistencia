<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'trabajador_id',
        'rol_id',
        'name',
        'email',
        'password',
        'estado',
        'ultimo_acceso',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acceso' => 'datetime',
            'estado' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function rol()
    {
        return $this->belongsTo(
            Rol::class,
            'rol_id'
        );
    }

    public function trabajador()
    {
        return $this->belongsTo(
            Trabajador::class,
            'trabajador_id'
        );
    }

    public function solicitudesRevisadas()
    {
        return $this->hasMany(
            Solicitud::class,
            'revisado_por'
        );
    }

    public function vacacionesRegistradas()
    {
        return $this->hasMany(
            Vacaciones::class,
            'registrado_por'
        );
    }

    public function vacacionesRevisadas()
    {
        return $this->hasMany(
            Vacaciones::class,
            'revisado_por'
        );
    }

    public function reprogramacionesVacaciones()
    {
        return $this->hasMany(
            ReprogramacionVacacion::class,
            'reprogramado_por'
        );
    }
}