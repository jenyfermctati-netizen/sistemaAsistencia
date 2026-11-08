<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpresaUsuario extends Model
{
    protected $table = 'empresa_usuario';

    protected $fillable = ['empresa_id', 'user_id', 'rol_id', 'trabajador_id', 'estado'];

    protected function casts(): array
    {
        return ['estado' => 'boolean'];
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
