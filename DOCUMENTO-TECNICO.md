# Documento técnico — ERP Contratos e Serviços

Documento de apoio à avaliação do desafio: estrutura, decisões, regras de negócio e pontos de evolução.

## 1. Visão geral

Monorepo com **API REST** (PHP/Slim) e **SPA mínima** (Vue 3) para exercitar o fluxo. O foco está no **backend**, na **modelagem** e nas **regras de negócio** (cálculo mensal do contrato e desconto configurável).

## 2. Arquitetura

```text
Cliente HTTP → Nginx → PHP-FPM → Slim (rotas) → Controller → Service → Repository → PostgreSQL
                                                      ↓
                                              Validators / Rules (domínio)
```

- **Controllers:** entrada/saída HTTP, delegação.
- **Services:** orquestração e regras de aplicação (ex.: contrato cancelado não edita).
- **Repositories:** persistência e consultas (PDO).
- **Models:** representação dos registros (ex.: `Contract::isCancelled()`).
- **Validators:** validação de payload e invariantes de entrada.
- **Rules:** motor financeiro desacoplado (`DiscountCalculator` + implementações de `DiscountRuleInterface`).

O frontend consome apenas `/api` (proxy no Vite em desenvolvimento).

## 3. Estrutura da aplicação (backend)

| Pasta / arquivo | Responsabilidade |
|-----------------|------------------|
| `public/index.php` | Bootstrap: env, CORS, PDO, Slim, rotas |
| `src/Routes/ApiRoutes.php` | Definição REST |
| `src/Controllers/*` | Ações por recurso |
| `src/Services/*` | Casos de uso |
| `src/Repositories/*` | Acesso a dados |
| `src/Models/*` | Entidades enxutas |
| `src/Validators/*` | Validação |
| `src/Rules/*` | Regras de negócio |
| `src/Helpers/Money.php` | Arredondamentos e totais por linha |
| `src/Exceptions/*` | Erros HTTP (404, validação, negócio) |
| `db/migrations`, `db/seeds` | Esquema e dados de demonstração |

Equivale a um **MVC adaptado**: Controller fino, “Model” dividido entre **Models** + **Repositories**, lógica de negócio nos **Services** e nas **Rules**.

## 4. Modelagem e relacionamentos

- **Client** (`clients`): nome, documento (CPF/CNPJ só dígitos, único), e-mail (único), status `A`/`I`.
- **Service** (`services`): nome, `base_monthly_value`.
- **Contract** (`contracts`): `client_id`, `start_date`, `end_date` (opcional), status `A` (ativo) / `C` (cancelado).
- **ContractItem** (`contract_items`): `contract_id`, `service_id`, `quantity`, `unit_value` (valor negociado da linha).
- **DiscountRule** (`discount_rules`): nome, `type` (`quantity`), `min_quantity`, `value_type` (`percent` | `fixed`), `value`, `is_active`.

Integridade: FK de contrato para cliente; itens ligados a contrato e serviço. Documento e e-mail com índice único.

## 5. Fluxo principal da API

1. **Clientes / serviços / regras de desconto:** CRUD síncrono com validação e respostas JSON padronizadas (`JsonResponse`).
2. **Contratos:** criação pode enviar itens iniciais; atualização de cabeçalho respeita status.
3. **Itens:** POST/PUT/DELETE em `/api/contracts/{id}/items` (e variantes com `itemId`).
4. **Cancelamento:** `PATCH /api/contracts/{id}/cancel` altera status para cancelado.
5. **Listagem/detalhe de contrato:** retorna `items` e `calculation` (`subtotal`, `discount`, `total`), sempre derivados dos itens atuais + motor de desconto.

## 6. Regras de negócio principais

### 6.1 Cliente e serviço

- CPF/CNPJ validado algoriticamente; persistência e unicidade em **dígitos** apenas.
- E-mail com `filter_var` + unicidade no banco.
- Serviço com nome e valor base positivo (validadores dedicados).

### 6.2 Contrato e itens

- Valor mensal do contrato é **sempre calculado**: soma das linhas `quantidade × valor_unitário` (arredondamento em `Money`), nunca um campo “total” gravado como fonte da verdade.
- Em criação de contrato, se `unit_value` omitido no item, usa-se o **preço base do serviço**.
- Contrato **cancelado** (`C`): bloqueio de `update`, `addItem`, `updateItem`, `removeItem` (`BusinessException`).

### 6.3 Desconto por quantidade (regra extra)

- Implementação: `QuantityDiscountRule` + persistência em `discount_rules`.
- Regras ativas do tipo quantidade, ordenadas, aplicadas **por item**: para cada linha, para cada regra cuja `min_quantity` é atingida, acumula-se desconto **percentual** ou **fixo** sobre o total da linha (limitado ao valor da linha).
- Contrato cancelado: **não aplica** desconto por quantidade (mantém subtotal para transparência; total igual ao subtotal nesse caso).
- Extensão: `DiscountCalculator` recebe uma lista de `DiscountRuleInterface`; novas regras podem ser adicionadas sem alterar o contrato nem o repositório de itens.

### 6.4 Consolidação financeira

- `DiscountCalculator` soma contribuições das regras habilitadas, limita desconto ao subtotal e expõe `applied_rules` no resultado interno (a API expõe subtotal/desconto/total de forma estável no `ContractService`).

## 7. Decisões técnicas importantes

| Decisão | Motivo |
|---------|--------|
| Slim sem framework full-stack | Atende PHP obrigatório com rotas claras e baixo acoplamento. |
| PostgreSQL | Relacional, alinhado ao enunciado; tipos e constraints adequados. |
| Phinx | Migrations e seeds versionados e reproduzíveis. |
| Repositórios com PDO explícito | Transparência e controle de SQL sem ORM pesado no escopo do teste. |
| Validações em classes dedicadas | Mensagens consistentes e testabilidade. |
| Motor de desconto em `Rules/` | Regra de negócio “aberta” do enunciado isolada e expansível. |
| Nginx + FPM no Docker | Padrão de produção; mesmo contrato de URL da API local (`8080`). |
| Vue apenas para demo | Enunciado prioriza backend; UI simples com listagens e modais. |

## 8. Testes automatizados

- Framework: **PHPUnit 11** (`back-end/phpunit.xml`).
- Suites em `back-end/tests/Unit/`: exemplos — `MoneyTest`, `DocumentValidatorTest`, `QuantityDiscountRuleTest`, `ContractServiceCalculationTest`, `ContractFinancialChainTest`.
- Execução: na pasta `back-end`, `composer test` (ou `vendor/bin/phpunit`).

Os testes unitários focam **validações**, **dinheiro** e **motor de desconto/cálculo** sem exigir banco em todos os casos (dependendo do teste, mocks ou dados mínimos).

## 9. O que melhoraria com mais tempo

- **Histórico / auditoria** de alterações em contrato (enunciado valoriza como extra).
- **Paginação e filtros** consistentes em todos os list endpoints (contratos já suportam paginação no service).
- **CI** (GitHub Actions) com `composer test` e lint.
- **OpenAPI** (Swagger) gerada ou mantida junto ao código.
- **Testes de integração** com banco em container ou SQLite dedicado aos testes.

---
