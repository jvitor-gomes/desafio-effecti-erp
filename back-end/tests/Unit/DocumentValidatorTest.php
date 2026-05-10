<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Validators\DocumentValidator;
use PHPUnit\Framework\TestCase;

class DocumentValidatorTest extends TestCase
{
    // Sanitização + dígitos verificadores de CPF
    public function test_valid_cpf(): void
    {
        self::assertTrue(DocumentValidator::validate('529.982.247-25'));
    }

    public function test_invalid_document(): void
    {
        self::assertFalse(DocumentValidator::validate('11111111111'));
    }
}
