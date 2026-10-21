<?php

namespace Features\Superadmin\Domain\Entities;

enum TipoCondominio: string
{
    case Torres = 'torres';
    case Casas = 'casas';
    case Mixto = 'mixto';
}
