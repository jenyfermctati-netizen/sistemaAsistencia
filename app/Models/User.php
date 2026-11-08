<?php

namespace App\Models;

use App\Notifications\RestablecerContrasena;
use App\Tenancy\EmpresaActual;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $attributes = ['estado' => true];

    protected static function booted(): void
    {
        static::addGlobalScope('miembros_empresa', function ($query): void {
            if ($id = app(EmpresaActual::class)->id()) {
                $query->whereExists(fn ($sub) => $sub->selectRaw('1')->from('empresa_usuario')
                    ->whereColumn('empresa_usuario.user_id', 'users.id')->where('empresa_usuario.empresa_id', $id));
            }
        });

        static::created(function (User $user): void {
            if ($id = app(EmpresaActual::class)->id()) {
                $user->actualizarMembresia([
                    'rol_id' => $user->getAttributes()['rol_id'],
                    'trabajador_id' => $user->getAttributes()['trabajador_id'] ?? null,
                    'estado' => $user->estado ?? true,
                ]);
            }
        });

        static::updating(function (User $user): void {
            if (! app(EmpresaActual::class)->id()) {
                return;
            }
            if ($user->isDirty(['rol_id', 'trabajador_id'])) {
                throw new \LogicException('El rol y trabajador se modifican en la membresía de la empresa.');
            }
            // La administración de personal no puede modificar la identidad compartida.
            if ($user->isDirty(['name', 'email', 'password', 'estado'])
                && $user->id !== auth()->id()
                && $user->membresias()->count() > 1) {
                throw ValidationException::withMessages([
                    'email' => 'Esta cuenta pertenece a varias empresas. Su titular debe modificar sus datos de acceso.',
                ]);
            }
        });
    }

    public function empresas()
    {
        return $this->belongsToMany(Empresa::class, 'empresa_usuario')
            ->withPivot(['rol_id', 'trabajador_id', 'estado'])->withTimestamps();
    }

    public function membresias()
    {
        return $this->hasMany(EmpresaUsuario::class, 'user_id');
    }

    public function membresiaActual(): ?EmpresaUsuario
    {
        $id = app(EmpresaActual::class)->id();

        return $id ? $this->membresias()->where('empresa_id', $id)->first() : null;
    }

    public function getRolIdAttribute($value)
    {
        return app(EmpresaActual::class)->id()
            ? $this->membresiaActual()?->rol_id : $value;
    }

    public function getTrabajadorIdAttribute($value)
    {
        return app(EmpresaActual::class)->id()
            ? $this->membresiaActual()?->trabajador_id : $value;
    }

    public function actualizarMembresia(array $datos): void
    {
        $id = app(EmpresaActual::class)->requerirId();
        if (! empty($datos['trabajador_id'])) {
            Trabajador::findOrFail($datos['trabajador_id']);
        }
        if (isset($datos['rol_id'])) {
            Rol::where('estado', true)->findOrFail($datos['rol_id']);
        }
        DB::transaction(function () use ($id, $datos): void {
            Empresa::whereKey($id)->lockForUpdate()->firstOrFail();
            $actual = $this->membresiaActual();
            $adminId = Rol::where('nombre', 'ADMINISTRADOR')->value('id');
            if ($actual && $actual->estado && (int) $actual->rol_id === (int) $adminId
                && ((! ($datos['estado'] ?? true)) || (isset($datos['rol_id']) && (int) $datos['rol_id'] !== (int) $adminId))
                && EmpresaUsuario::where('empresa_id', $id)->where('estado', true)->where('rol_id', $adminId)->count() <= 1) {
                throw ValidationException::withMessages([
                    'rol_id' => 'Debe quedar al menos un administrador activo.',
                ]);
            }
            $this->membresias()->updateOrCreate(['empresa_id' => $id],
                Arr::only($datos, ['rol_id', 'trabajador_id', 'estado']));
        });
        $this->unsetRelation('rol')->unsetRelation('trabajador');
    }

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

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new RestablecerContrasena($token));
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
