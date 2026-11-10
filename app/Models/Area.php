<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use BelongsToEmpresa, HasFactory;

    protected $table = 'areas';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function trabajadores()
    {
        return $this->hasMany(Trabajador::class, 'area_id');
    }

    public function encargaturas()
    {
        return $this->hasMany(AreaEncargado::class, 'area_id');
    }

    public function encargaturasActivas()
    {
        return $this->encargaturas()->vigentes();
    }

    public function pendientesAvance()
    {
        return $this->hasMany(PendienteAvance::class, 'area_id');
    }
}
