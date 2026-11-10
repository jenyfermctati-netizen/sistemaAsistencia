<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class DispositivoBiometrico extends Model
{
    use BelongsToEmpresa;

    protected $table = 'dispositivos_biometricos';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'serial',
        'modelo',
        'estado',
        'secreto_hash',
        'ultima_ip',
        'ultima_conexion',
        'ultima_marcacion',
    ];

    protected $hidden = ['secreto_hash'];

    protected $casts = [
        'estado' => 'boolean',
        'ultima_conexion' => 'datetime',
        'ultima_marcacion' => 'datetime',
    ];

    public function getEstaConectadoAttribute(): bool
    {
        return $this->ultima_conexion && $this->ultima_conexion->gte(now()->subMinutes(2));
    }

    public function verificarSecreto(?string $secreto): bool
    {
        if (! $this->secreto_hash) {
            return true;
        }

        return filled($secreto) && Hash::check($secreto, $this->secreto_hash);
    }

    public function usuariosBiometricos()
    {
        return $this->hasMany(BiometricoUsuario::class, 'dispositivo_id');
    }

    public function comandos()
    {
        return $this->hasMany(BiometricoComando::class, 'dispositivo_id');
    }
}
