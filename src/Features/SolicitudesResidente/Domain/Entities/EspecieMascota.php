<?php

namespace Features\SolicitudesResidente\Domain\Entities;

enum EspecieMascota: string
{
    case Canino = 'canino';
    case Felino = 'felino';
    case PequenoMamifero = 'pequeno_mamifero';
    case AveDomestica = 'ave_domestica';
    case Otro = 'otro';
}
