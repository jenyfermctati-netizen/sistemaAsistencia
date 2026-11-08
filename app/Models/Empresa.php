<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $fillable = ['nombre', 'ruc', 'estado'];

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'empresa_usuario')
            ->withPivot(['rol_id', 'trabajador_id', 'estado'])->withTimestamps();
    }
}
