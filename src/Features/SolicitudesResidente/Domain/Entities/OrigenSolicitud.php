<?php

namespace Features\SolicitudesResidente\Domain\Entities;

enum OrigenSolicitud: string
{
    case Autoregistro = 'autoregistro';
    case Conserje = 'conserje';
    case Administrador = 'administrador';
    case Importacion = 'importacion';
    case Seeder = 'seeder';
}
