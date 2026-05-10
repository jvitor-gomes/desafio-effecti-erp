<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Erro de validacao.');
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    // Falha de entrada: agrega erros por campo e vira 422 no handler
    public static function throwIfErrors(array $errors): void
    {
        if ($errors !== []) {
            throw new self($errors);
        }
    }
}
