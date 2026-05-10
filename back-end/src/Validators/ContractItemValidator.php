<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;
use App\Models\Service;

class ContractItemValidator
{
    public static function validatedAttributesForContractCreate(array $item, ?Service $service): array
    {
        $errors = [];

        if (!$service) {
            $errors['items'] = 'Item com servico invalido.';
        }

        $qty = isset($item['quantity']) ? (int) $item['quantity'] : 1;
        if ($qty < 1) {
            $errors['items'] = 'Quantidade do item deve ser maior que zero.';
        }

        if (array_key_exists('unit_value', $item)) {
            if (!is_numeric($item['unit_value'])) {
                $errors['items'] = 'Valor unitario do item invalido.';
            } else {
                $uv = (float) $item['unit_value'];
                if ($uv < 0 || !is_finite($uv)) {
                    $errors['items'] = 'Valor unitario do item nao pode ser negativo.';
                }
            }
        }

        ValidationException::throwIfErrors($errors);

        // unit_value ausente usa o preço base mensal do serviço
        $unitValue = isset($item['unit_value']) && is_numeric($item['unit_value'])
            ? round((float) $item['unit_value'], 2)
            : round((float) $service->base_monthly_value, 2);

        return [
            'service_id' => (int) $service->id,
            'quantity' => $qty,
            'unit_value' => $unitValue,
        ];
    }

    public static function validatedAttributesForAppend(array $data, ?Service $service): array
    {
        $errors = [];

        $serviceId = (int) ($data['service_id'] ?? 0);
        if (!$service) {
            $errors['service_id'] = 'Servico invalido.';
        }

        $quantity = (int) ($data['quantity'] ?? 0);
        if ($quantity < 1) {
            $errors['quantity'] = 'Quantidade deve ser maior que zero.';
        }

        if (array_key_exists('unit_value', $data)) {
            if (!is_numeric($data['unit_value'])) {
                $errors['unit_value'] = 'Valor unitario invalido.';
            } else {
                $uv = (float) $data['unit_value'];
                if ($uv < 0 || !is_finite($uv)) {
                    $errors['unit_value'] = 'Valor unitario nao pode ser negativo.';
                }
            }
        }

        ValidationException::throwIfErrors($errors);

        // Mesmo padrão da criação em lote: default do serviço quando unit_value não vem no corpo
        return [
            'service_id' => $serviceId,
            'quantity' => $quantity,
            'unit_value' => isset($data['unit_value']) && is_numeric($data['unit_value'])
                ? round((float) $data['unit_value'], 2)
                : round((float) $service->base_monthly_value, 2),
        ];
    }

    public static function validatedUpdatePatch(array $data): array
    {
        $errors = [];
        $payload = [];

        if (array_key_exists('quantity', $data)) {
            $qty = (int) $data['quantity'];
            if ($qty < 1) {
                $errors['quantity'] = 'Quantidade deve ser maior que zero.';
            } else {
                $payload['quantity'] = $qty;
            }
        }

        if (array_key_exists('unit_value', $data)) {
            if (!is_numeric($data['unit_value'])) {
                $errors['unit_value'] = 'Valor unitario invalido.';
            } else {
                $value = (float) $data['unit_value'];
                if ($value < 0 || !is_finite($value)) {
                    $errors['unit_value'] = 'Valor unitario nao pode ser negativo.';
                } else {
                    $payload['unit_value'] = round($value, 2);
                }
            }
        }

        ValidationException::throwIfErrors($errors);

        return $payload;
    }
}
