<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

// Violação de regra de negócio tratada como HTTP 400 no HttpExceptionHandler
class BusinessException extends RuntimeException
{
}
