<?php

namespace App\Tenancy;

use App\Models\Empresa;
use Closure;
use LogicException;

final class EmpresaActual
{
    private ?Empresa $empresa = null;

    public function id(): ?int
    {
        return $this->empresa?->id;
    }

    public function empresa(): ?Empresa
    {
        return $this->empresa;
    }

    public function establecer(?Empresa $empresa): void
    {
        $this->empresa = $empresa;
    }

    public function requerirId(): int
    {
        return $this->id() ?? throw new LogicException('Se requiere una empresa activa.');
    }

    public function ejecutar(Empresa $empresa, Closure $callback): mixed
    {
        $anterior = $this->empresa;
        $this->establecer($empresa);

        try {
            return $callback();
        } finally {
            $this->establecer($anterior);
        }
    }
}
