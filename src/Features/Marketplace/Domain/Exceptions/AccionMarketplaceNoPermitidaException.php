<?php

namespace Features\Marketplace\Domain\Exceptions;

use RuntimeException;

final class AccionMarketplaceNoPermitidaException extends RuntimeException
{
    public static function becauseNotOwner(): self
    {
        return new self('Solo puedes modificar tus propias publicaciones.');
    }

    public static function becauseUseOwnerAction(): self
    {
        return new self('Para retirar tu publicación usa la opción de retiro o venta.');
    }

    public static function becauseFavoriteOwn(): self
    {
        return new self('No puedes agregar tu propia publicación a favoritos.');
    }

    public static function becauseAdminOnly(): self
    {
        return new self('No tienes permisos para ver todas las publicaciones.');
    }
}
