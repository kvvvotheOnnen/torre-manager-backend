<?php

namespace Features\Superadmin\Domain\Entities;

enum EstadoProyecto: string
{
    case Borrador = 'borrador';
    case Completo = 'completo';
}
