<?php

namespace Features\Infraestructura\Domain\Entities;

enum TipoAsignacion: string
{
    case Departamento = 'departamento';
    case Visita = 'visita';
    case Otro = 'otro';
}
