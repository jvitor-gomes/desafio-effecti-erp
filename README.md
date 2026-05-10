# ERP — Contratos e Serviços

Sistema simplificado de gestão de **clientes**, **serviços**, **contratos** (mensais recorrentes) e **regras de desconto por quantidade**, com API REST em PHP e painel Vue para demonstração.

## Funcionalidades implementadas

- CRUD de clientes (CPF/CNPJ e e-mail validados; status Ativo/Inativo)
- CRUD de serviços (valor base mensal)
- CRUD de contratos (cliente, datas, status Ativo/Cancelado)
- Itens do contrato: adicionar, alterar e remover serviços (quantidade e valor unitário negociado)
- Listagem/detalhe de contratos com **itens** e **totais** (`subtotal`, `desconto`, `total` mensal)
- Regra de negócio extra: **desconto por quantidade** (por linha), configurável via tabela `discount_rules` e extensível por `DiscountRuleInterface`
- Contrato **cancelado**: não permite edição nem alteração de itens; desconto por quantidade não é aplicado no cálculo
- API REST sob `/api`; health check em `/health`
- Docker Compose (PostgreSQL, PHP-FPM, Nginx, Vite)
- Testes automatizados (PHPUnit) no backend

## Tecnologias

| Camada | Tecnologia |
|--------|------------|
| Backend | PHP 8.4, Slim 4, PDO PostgreSQL, Phinx (migrations/seeds), vlucas/phpdotenv |
| Frontend | Vue 3, Vue Router, Vite, Axios |
| Banco | PostgreSQL 16 |
| Infra | Docker / Docker Compose, Nginx (gateway da API) |

## Requisitos

**Com Docker:** Docker Engine e Docker Compose v2.

**Sem Docker:** PHP 8.4+, Composer 2, Node.js 20+, PostgreSQL 16+ (ou compatível), extensão `pdo_pgsql`.

## Configuração de ambiente

1. Na **raiz do repositório**, copie o exemplo de variáveis:

   ```bash
   copy .env.example .env
   ```

   (Linux/macOS: `cp .env.example .env`)

2. Ajuste portas ou credenciais do banco se necessário (`DB_*`, `BACKEND_PORT`, `FRONTEND_PORT`, `CORS_ORIGIN`).

3. **Backend sem Docker:** o Slim carrega `.env` a partir de `back-end/`. Copie as variáveis de `DB_*` e `APP_*` para `back-end/.env`, ou exporte-as no shell. O **Phinx** (migrations) carrega o `.env` da **raiz do repositório** — mantenha o `.env` na raiz ao rodar `vendor/bin/phinx` dentro de `back-end`.

4. **Frontend local (fora do Docker):** opcionalmente copie `front-end/.env.example` para `front-end/.env` e defina `API_PROXY_TARGET` (padrão: API em `http://127.0.0.1:8080`).

## Execução com Docker

Na raiz do projeto:

```bash
docker compose up --build
```

- **API:** `http://localhost:8080` (Nginx → PHP-FPM; rotas em `/api`, `/health`)
- **Frontend:** `http://localhost:5173` (Vite; proxy `/api` → Nginx do compose)

Na primeira subida, o container do backend executa `composer install`, **migrations** e **seed** (variáveis `AUTO_MIGRATE` e `AUTO_SEED`, padrão `true`).

## Execução sem Docker

1. Crie o banco PostgreSQL com o mesmo nome/usuário/senha do `.env` (ou ajuste as variáveis).

2. Backend:

   ```bash
   cd back-end
   composer install
   ```

   Garanta `back-end/.env` com `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (e na raiz um `.env` com os mesmos dados para Phinx, ou exporte `DB_*` antes do Phinx).

   ```bash
   vendor/bin/phinx migrate -c phinx.php
   vendor/bin/phinx seed:run -c phinx.php
   ```

   Configure um virtual host **Nginx** (ou Apache) com `root` apontando para `back-end/public` e `fastcgi_pass` para o PHP-FPM, alinhado a `docker/nginx/default.conf`. Em Windows, costuma ser mais simples usar o compose só para Postgres + Nginx ou subir o stack completo via Docker.

3. Frontend:

   ```bash
   cd front-end
   npm install
   npm run dev
   ```

   Acesse a URL exibida pelo Vite; requisições `/api` serão encaminhadas para `API_PROXY_TARGET`.

## Comandos úteis

| Onde | Comando | Descrição |
|------|---------|-----------|
| `back-end` | `composer test` | PHPUnit |
| `back-end` | `composer migrate` | Roda migrations |
| `back-end` | `composer seed` | Roda seeds |
| `back-end` | `vendor/bin/phinx status -c phinx.php` | Status das migrations |
| `front-end` | `npm run build` | Build de produção |
| Raiz | `docker compose down -v` | Derruba stack e remove volume do Postgres (apaga dados) |

## Estrutura de pastas (resumo)

```
back-end/          API Slim, migrations, seeds, testes
  public/          Ponto de entrada (index.php)
  src/             Controllers, Services, Repositories, Models, Validators, Rules, …
  db/migrations    Phinx
  db/seeds         Dados iniciais
front-end/         Vue 3 + Vite
docker/            Configuração Nginx do compose
docker-compose.yml
.env.example
```

## Documentação técnica

Detalhes de arquitetura, camadas, regras de negócio e evoluções sugeridas: **[DOCUMENTO-TECNICO.md](./DOCUMENTO-TECNICO.md)**.
