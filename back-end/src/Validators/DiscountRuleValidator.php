<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;

class DiscountRuleValidator
{
    // Domínio atual do ERP: só desconto por quantidade
    private const ALLOWED_TYPE = 'quantity';

    public static function validatedCreateAttributes(array $data): array
    {
        $errors = [];

        if (!isset($data['name']) || trim((string) $data['name']) === '') {
            $errors['name'] = 'Nome da regra e obrigatorio.';
        }
        if (!isset($data['min_quantity']) || (int) $data['min_quantity'] < 1) {
            $errors['min_quantity'] = 'Quantidade minima deve ser maior que zero.';
        }
        if (
            !isset($data['value_type'])
            || !in_array((string) $data['value_type'], ['percent', 'fixed'], true)
        ) {
            $errors['value_type'] = 'Tipo de valor invalido.';
        }
        if (!isset($data['value']) || !is_numeric($data['value'])) {
            $errors['value'] = 'Valor da regra invalido.';
        }

        $type = isset($data['type']) ? strtolower(trim((string) $data['type'])) : self::ALLOWED_TYPE;
        if ($type !== self::ALLOWED_TYPE) {
            $errors['type'] = 'Apenas regras do tipo quantity (desconto por quantidade) sao permitidas.';
        }

        ValidationException::throwIfErrors($errors);

        return [
            'name' => trim((string) $data['name']),
            'type' => self::ALLOWED_TYPE,
            'min_quantity' => (int) $data['min_quantity'],
            'value_type' => (string) $data['value_type'],
            'value' => (float) $data['value'],
            'is_active' => isset($data['is_active']) ? self::coerceBoolean($data['is_active']) : true,
        ];
    }

    private static function coerceBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? false;
    }

    public static function validatedUpdatePatch(array $data): array
    {
        $errors = [];
        $payload = [];

        if (array_key_exists('name', $data) && $data['name'] !== '' && $data['name'] !== null) {
            $payload['name'] = $data['name'];
        }

        if (array_key_exists('type', $data) && $data['type'] !== '' && $data['type'] !== null) {
            $t = strtolower(trim((string) $data['type']));
            if ($t !== self::ALLOWED_TYPE) {
                $errors['type'] = 'Apenas regras do tipo quantity (desconto por quantidade) sao permitidas.';
            }
        }

        if (array_key_exists('value_type', $data) && $data['value_type'] !== '' && $data['value_type'] !== null) {
            if (!in_array((string) $data['value_type'], ['percent', 'fixed'], true)) {
                $errors['value_type'] = 'Tipo de valor invalido.';
            } else {
                $payload['value_type'] = $data['value_type'];
            }
        }

        if (array_key_exists('min_quantity', $data)) {
            $mq = (int) $data['min_quantity'];
            if ($mq < 1) {
                $errors['min_quantity'] = 'Quantidade minima deve ser maior que zero.';
            } else {
                $payload['min_quantity'] = $mq;
            }
        }

        if (array_key_exists('value', $data)) {
            if (!is_numeric($data['value'])) {
                $errors['value'] = 'Valor da regra invalido.';
            } else {
                $payload['value'] = (float) $data['value'];
            }
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = self::coerceBoolean($data['is_active']);
        }

        ValidationException::throwIfErrors($errors);

        return $payload;
    }
}
