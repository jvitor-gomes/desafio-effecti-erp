<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

class ClientValidator
{
    public static function validatedCreateAttributes(array $data): array
    {
        $errors = [];

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            $errors['name'] = 'Nome obrigatorio.';
        }
        if (!isset($data['email']) || trim((string) $data['email']) === '') {
            $errors['email'] = 'Email invalido.';
        } elseif (!filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalido.';
        }
        if (!isset($data['document']) || trim((string) $data['document']) === '') {
            $errors['document'] = 'CPF/CNPJ invalido.';
        } elseif (!DocumentValidator::validate((string) $data['document'])) {
            $errors['document'] = 'CPF/CNPJ invalido.';
        }

        ValidationException::throwIfErrors($errors);

        return [
            'name' => trim((string) $data['name']),
            'email' => trim((string) $data['email']),
            // Unicidade e comparação no banco usam apenas dígitos
            'document' => DocumentValidator::sanitize((string) $data['document']),
            'status' => isset($data['status']) && $data['status'] !== '' ? (string) $data['status'] : 'A',
        ];
    }

    public static function validatedUpdatePatch(array $data): array
    {
        $errors = [];
        $payload = [];

        if (array_key_exists('name', $data)) {
            $payload['name'] = $data['name'];
        }
        if (array_key_exists('email', $data)) {
            if (!filter_var((string) $data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email invalido.';
            } else {
                $payload['email'] = $data['email'];
            }
        }
        if (array_key_exists('document', $data)) {
            if (!DocumentValidator::validate((string) $data['document'])) {
                $errors['document'] = 'CPF/CNPJ invalido.';
            } else {
                $payload['document'] = DocumentValidator::sanitize((string) $data['document']);
            }
        }
        if (array_key_exists('status', $data)) {
            $payload['status'] = $data['status'];
        }

        ValidationException::throwIfErrors($errors);

        return $payload;
    }

    public static function assertCanDelete(bool $hasContracts): void
    {
        if ($hasContracts) {
            throw new ValidationException(['client' => 'Cliente possui contratos vinculados e nao pode ser removido.']);
        }
    }
}
