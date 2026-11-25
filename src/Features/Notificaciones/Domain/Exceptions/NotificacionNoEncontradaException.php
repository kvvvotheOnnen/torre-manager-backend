<?php

namespace Features\Notificaciones\Domain\Exceptions;

use RuntimeException;

final class NotificacionNoEncontradaException extends RuntimeException
{
    public static function becauseNotificationIsMissing(): self
    {
        return new self('Notificación no encontrada.');
    }
}
