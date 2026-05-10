<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

class ServiceValidator
{
    public static function validatedCreateAttributes(array $data): array
    {
        $errors = [];

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            $errors['name'] = 'Nome obrigatorio.';
        }
        if (!isset($data['base_monthly_value']) || !is_numeric($data['base_monthly_value'])) {
            $errors['base_monthly_value'] = 'Valor base invalido.';
        } elseif ((float) $data['base_monthly_value'] < 0 || !is_finite((float) $data['base_monthly_value'])) {
            $errors['base_monthly_value'] = 'Valor base nao pode ser negativo.';
        }

        ValidationException::throwIfErrors($errors);

        // Valor base monetário com duas casas decimais
        return [
            'name' => trim((string) $data['name']),
            'base_monthly_value' => round((float) $data['base_monthly_value'], 2),
        ];
    }

    public static function validatedUpdatePatch(array $data): array
    {
        $errors = [];
        $payload = [];

        if (isset($data['name']) && $data['name'] !== '') {
            $payload['name'] = $data['name'];
        }
        if (isset($data['base_monthly_value'])) {
            if (!is_numeric($data['base_monthly_value'])) {
                $errors['base_monthly_value'] = 'Valor base invalido.';
            } else {
                $v = (float) $data['base_monthly_value'];
                if ($v < 0 || !is_finite($v)) {
                    $errors['base_monthly_value'] = 'Valor base nao pode ser negativo.';
                } else {
                    $payload['base_monthly_value'] = round($v, 2);
                }
            }
        }

        ValidationException::throwIfErrors($errors);

        return $payload;
    }

    public static function assertCanDelete(bool $inUse): void
    {
        if ($inUse) {
            throw new ValidationException(['service' => 'Servico vinculado a contrato nao pode ser removido.']);
        }
    }
}
