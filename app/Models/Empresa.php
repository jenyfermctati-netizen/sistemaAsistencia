<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    public const ACTIVA = 'ACTIVA';

    public const SUSPENDIDA = 'SUSPENDIDA';

    public const INACTIVA = 'INACTIVA';

    public const COLORES_TURQUESA = [
        '#00A9C7' => 'Turquesa brillante',
        '#087F9A' => 'Turquesa profundo',
        '#0F766E' => 'Verde turquesa',
        '#0E7490' => 'Turquesa medio',
    ];

    protected $fillable = [
        'razon_social',
        'nombre_comercial',
        'ruc',
        'email',
        'telefono',
        'direccion',
        'logo',
        'color_turquesa',
        'estado',
        'zona_horaria',
    ];

    public function estaActiva(): bool
    {
        return $this->estado === self::ACTIVA;
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class);
    }

    public function areas()
    {
        return $this->hasMany(Area::class);
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class);
    }

    public function dispositivosBiometricos()
    {
        return $this->hasMany(DispositivoBiometrico::class);
    }
}
