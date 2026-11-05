<?php

namespace Features\Superadmin\Domain\UseCases;

use Features\Superadmin\Domain\Entities\Administrador;
use Features\Superadmin\Domain\Exceptions\EmailDuplicadoException;
use Features\Superadmin\Domain\Repositories\SuperadminRepositoryInterface;
use InvalidArgumentException;

final class CrearAdministradorUseCase
{
    public const PASSWORD_MIN_LENGTH = 8;

    public function __construct(
        private readonly SuperadminRepositoryInterface $repository,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws EmailDuplicadoException
     */
    public function execute(string $nombre, string $correo, string $password): Administrador
    {
        $nombre = trim($nombre);
        $correo = trim($correo);

        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre del administrador es obligatorio.');
        }

        if (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }

        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            throw new InvalidArgumentException('La contraseña debe tener al menos '.self::PASSWORD_MIN_LENGTH.' caracteres.');
        }

        return $this->repository->crearAdministrador($nombre, $correo, $password);
    }
}
