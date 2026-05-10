<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

// Recurso ausente: HTTP 404 no HttpExceptionHandler
class NotFoundException extends RuntimeException
{
}
