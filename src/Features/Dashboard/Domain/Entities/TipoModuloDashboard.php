<?php

namespace Features\Dashboard\Domain\Entities;

enum TipoModuloDashboard: string
{
    case General = 'general';
    case Proyecto = 'proyecto';
}
