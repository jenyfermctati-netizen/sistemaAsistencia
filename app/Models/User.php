<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use App\Notifications\RestablecerContrasena;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use BelongsToEmpresa, HasFactory, Notifiable;

    protected $fillable = [
        'empresa_id',
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
            'ingresos_count' => 'integer',
            'password_changed_at' => 'datetime',
            'estado' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerContrasena($token));
    }

    public function roleCode(): string
    {
        return strtoupper(trim((string) ($this->rol?->codigo ?? '')));
    }

    public function hasRole(string ...$codigos): bool
    {
        return in_array($this->roleCode(), $codigos, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->empresa_id === null
            && $this->rol?->ambito === 'GLOBAL'
            && $this->roleCode() === 'SUPERADMIN';
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
