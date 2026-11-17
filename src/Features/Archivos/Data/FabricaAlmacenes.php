<?php

namespace Features\Archivos\Data;

use Features\Archivos\Domain\Contracts\AlmacenArchivos;

final class FabricaAlmacenes
{
    public function crear(): AlmacenArchivos
    {
        $driver = (string) config('archivos.driver', 'local');
        $disco = (string) config('archivos.disco', 'public');

        return match ($driver) {
            's3' => new AlmacenS3Compatible($disco),
            default => new AlmacenEnDisco($disco),
        };
    }
}
