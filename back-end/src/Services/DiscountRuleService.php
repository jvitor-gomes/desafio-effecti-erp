<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\NotFoundException;
use App\Repositories\DiscountRuleRepository;
use App\Validators\DiscountRuleValidator;

class DiscountRuleService
{
    private DiscountRuleRepository $rules;

    public function __construct(?DiscountRuleRepository $rules = null)
    {
        $this->rules = $rules ?? new DiscountRuleRepository();
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->rules->paginate($page, $perPage);
    }

    public function create(array $data): array
    {
        $attributes = DiscountRuleValidator::validatedCreateAttributes($data);
        $rule = $this->rules->create($attributes);

        return $rule->toArray();
    }

    public function update(int $id, array $data): array
    {
        $rule = $this->rules->find($id);
        if (!$rule) {
            throw new NotFoundException('Regra de desconto nao encontrada.');
        }

        $payload = DiscountRuleValidator::validatedUpdatePatch($data);
        // PATCH sem mudanças evita round-trip ao banco
        if ($payload === []) {
            return $rule->toArray();
        }

        return $this->rules->update($rule, $payload)->toArray();
    }

    public function delete(int $id): void
    {
        $rule = $this->rules->find($id);
        if (!$rule) {
            throw new NotFoundException('Regra de desconto nao encontrada.');
        }
        $this->rules->delete($rule);
    }
}
