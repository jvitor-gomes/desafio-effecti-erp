<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Exceptions\ValidationException;
use App\Repositories\ClientRepository;
use App\Repositories\ContractRepository;
use App\Validators\ClientValidator;
use PDOException;

class ClientService
{
    private ClientRepository $clients;
    private ContractRepository $contracts;

    public function __construct(
        ?ClientRepository $clients = null,
        ?ContractRepository $contracts = null,
    ) {
        $this->clients = $clients ?? new ClientRepository();
        $this->contracts = $contracts ?? new ContractRepository();
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->clients->paginate($page, $perPage);
    }

    public function create(array $data): array
    {
        $attributes = ClientValidator::validatedCreateAttributes($data);
        try {
            $client = $this->clients->create($attributes);
        } catch (PDOException $e) {
            $dup = $this->validationExceptionForClientUniqueViolation($e);
            if ($dup !== null) {
                throw $dup;
            }
            throw $e;
        }

        return $client->toArray();
    }

    public function findById(int $id): array
    {
        $client = $this->clients->find($id);
        if (!$client) {
            throw new NotFoundException('Cliente nao encontrado.');
        }

        return $client->toArray();
    }

    public function update(int $id, array $data): array
    {
        $client = $this->clients->find($id);
        if (!$client) {
            throw new NotFoundException('Cliente nao encontrado.');
        }

        $payload = ClientValidator::validatedUpdatePatch($data);

        if (!empty($payload)) {
            try {
                $this->clients->update($client, $payload);
            } catch (PDOException $e) {
                $dup = $this->validationExceptionForClientUniqueViolation($e);
                if ($dup !== null) {
                    throw $dup;
                }
                throw $e;
            }
        }

        return $this->clients->find($id)?->toArray() ?? $client->toArray();
    }

    public function delete(int $id): void
    {
        $client = $this->clients->find($id);
        if (!$client) {
            throw new NotFoundException('Cliente nao encontrado.');
        }

        // Não remove cliente que ainda possui contratos
        ClientValidator::assertCanDelete($this->contracts->existsForClientId($id));

        $this->clients->delete($client);
    }

    // Código 23505 (unique) do Postgres vira mensagem de campo para o cliente
    private function validationExceptionForClientUniqueViolation(PDOException $e): ?ValidationException
    {
        $state = (string) ($e->errorInfo[0] ?? '');
        if ($state !== '23505' && (string) $e->getCode() !== '23505') {
            return null;
        }

        $detail = (string) ($e->errorInfo[2] ?? '') . $e->getMessage();
        if (stripos($detail, '(document)') !== false) {
            return new ValidationException(['document' => 'CPF/CNPJ ja cadastrado.']);
        }
        if (stripos($detail, '(email)') !== false) {
            return new ValidationException(['email' => 'Email ja cadastrado.']);
        }

        return new ValidationException(['duplicate' => 'Valor duplicado nao permitido.']);
    }
}
