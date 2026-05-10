<?php

declare(strict_types=1);

namespace App\Validators;

use App\Exceptions\ValidationException;
use App\Models\Client;

class ContractValidator
{
    public static function validatedHeaderForCreate(array $data, ?Client $client): array
    {
        $errors = [];

        if (!$client) {
            $errors['client_id'] = 'Cliente invalido.';
        } elseif (($client->status ?? 'A') !== 'A') {
            // Novo contrato exige cliente ativo
            $errors['client_id'] = 'Cliente inativo nao pode receber contrato.';
        }

        if (!isset($data['start_date']) || $data['start_date'] === '' || $data['start_date'] === null) {
            $errors['start_date'] = 'Data de inicio obrigatoria.';
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            $errors['items'] = 'Contrato precisa de ao menos um item.';
        }

        ValidationException::throwIfErrors($errors);

        return [
            'client_id' => (int) $data['client_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
        ];
    }

    public static function validatedUpdatePatch(array $data): array
    {
        $payload = [];

        if (array_key_exists('start_date', $data)) {
            $payload['start_date'] = $data['start_date'];
        }
        if (array_key_exists('end_date', $data)) {
            $payload['end_date'] = $data['end_date'];
        }

        return $payload;
    }
}
