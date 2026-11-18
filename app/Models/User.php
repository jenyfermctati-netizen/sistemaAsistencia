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

    public function tienePermiso(string $permiso): bool
    {
        return \App\Support\PermisosEmpresa::permite($this, $permiso);
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $admin = Rol::whereKey($user->rol_id)->where('codigo', 'ADMINISTRADOR')->exists();
            $wasAdmin = $user->exists && Rol::whereKey($user->getOriginal('rol_id'))->where('codigo', 'ADMINISTRADOR')->exists();
            if ($wasAdmin && (! $admin || ! $user->estado || $user->isDirty('empresa_id'))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'rol_id' => 'Transfiere la administración antes de desactivar o cambiar al administrador.',
                ]);
            }
            if ($admin) {
                if (! $user->empresa_id || ! $user->estado) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['rol_id' => 'El administrador debe tener una empresa y estar activo.']);
                }
                if (static::withoutGlobalScopes()->where('administrador_empresa_id', $user->empresa_id)
                    ->when($user->exists, fn ($q) => $q->where('id', '!=', $user->id))->exists()) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['rol_id' => 'La empresa ya tiene un administrador.']);
                }
            }
            $user->administrador_empresa_id = $admin ? $user->empresa_id : null;
        });
        static::deleting(function (User $user): void {
            if (Rol::whereKey($user->rol_id)->where('codigo', 'ADMINISTRADOR')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['usuario' => 'Transfiere la administración antes de eliminar esta cuenta.']);
            }
        });
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
