<?php

namespace App\Support;

use App\Models\Empresa;
use Closure;

class EmpresaContext
{
    private ?Empresa $empresa = null;

    public function activate(Empresa $empresa): void
    {
        $this->empresa = $empresa;
    }

    public function clear(): void
    {
        $this->empresa = null;
    }

    public function empresa(): ?Empresa
    {
        return $this->empresa;
    }

    public function id(): ?int
    {
        return $this->empresa?->getKey();
    }

    public function hasEmpresa(): bool
    {
        return $this->empresa !== null;
    }

    public function run(Empresa $empresa, Closure $callback): mixed
    {
        $anterior = $this->empresa;
        $zonaAnterior = date_default_timezone_get();
        $configAnterior = config('app.timezone');
        $this->activate($empresa);
        config(['app.timezone' => $empresa->zona_horaria]);
        date_default_timezone_set($empresa->zona_horaria);

        try {
            return $callback();
        } finally {
            $this->empresa = $anterior;
            config(['app.timezone' => $configAnterior]);
            date_default_timezone_set($zonaAnterior);
        }
    }
}
