# Recanto das Araras — Gestão Condominial

Sistema web de portaria e administração desenvolvido em PHP e MySQL. Este repositório apresenta o código do projeto para avaliação técnica e execução independente, sem dados do condomínio e sem ligação com o banco original.

## Funcionalidades

- Cadastro e consulta de moradores, veículos e visitantes.
- Controle de presença de visitantes e retirada de encomendas.
- Publicação de avisos.
- Perfis de administração e portaria.
- Gestão de usuários, alteração de senha e auditoria de operações.
- Fila de notificações por e-mail, configurada opcionalmente pelo operador.

## Tecnologias e organização

PHP 8.2+, MySQL/MariaDB, MySQLi, Composer, PHPMailer, JavaScript e CSS.

| Pasta | Responsabilidade |
| --- | --- |
| app | Telas, validações, consultas e regras de negócio |
| bootstrap | Inicialização, sessão e autorização |
| config | Leitura das configurações do ambiente |
| database | Estrutura e migrações; sem cópia de registros reais |
| public | Entrada HTTP e recursos públicos |
| routes | Mapa das rotas |
| bin | Instalação e comandos administrativos |
| tests | Validações e integração |
| storage | Diretórios locais vazios; conteúdo privado ignorado pelo Git |

## Executar localmente com Docker

Requisitos: Docker com Docker Compose. Execute os comandos na pasta deste repositório.

1. Copie `.env.example` para `.env` e substitua os dois valores por senhas fortes e diferentes. No PowerShell:

   ```powershell
   Copy-Item .env.example .env
   notepad .env
   ```

2. Construa e inicie os serviços:

   ```sh
   docker compose up -d --build
   docker compose exec app php bin/install.php
   docker compose exec app php bin/migrate.php
   docker compose exec app php bin/create-admin.php "Administrador Demo" admin@example.com
   ```

   O último comando solicita uma senha escolhida por você. Não existe senha padrão. A entrada da senha fica visível no terminal; utilize um terminal privado.

3. Acesse http://localhost:8080 e entre com o administrador criado.

A aplicação começa sem moradores ou outros registros. Cadastre somente exemplos fictícios. O e-mail não vem configurado e nenhum agendador é instalado automaticamente. O banco fica em volume Docker próprio, sem porta publicada. O site fica disponível somente no computador local.

Para encerrar, use `docker compose down`. Os registros permanecem no volume. `docker compose down -v` apaga definitivamente o banco desta demonstração.

## Instalação sem Docker

1. Instale PHP 8.2+ com MySQLi, MySQL/MariaDB e Composer.
2. Crie um banco vazio e um usuário exclusivo para ele. Para a instalação, o usuário precisa criar e alterar as tabelas nesse banco; restrinja as permissões após as migrações conforme sua operação.
3. Configure `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` no ambiente do processo PHP. Configure `APP_BASE_URL=/` para executar na raiz. O PHP não lê `.env` automaticamente; esse arquivo é utilizado apenas pelo Docker Compose.
4. Execute `composer install`, `php bin/install.php`, `php bin/migrate.php` e `php bin/create-admin.php "Administrador Demo" admin@example.com`.
5. Inicie com `composer serve` e acesse http://localhost:8000.

Na hospedagem Apache, configure a raiz do site para `public` e encaminhe as rotas para `public/index.php`. Veja [operação](docs/operations.md). GitHub armazena o código; para executar o backend PHP é necessária uma hospedagem compatível.

## Verificação

```sh
php tests/validation.php
```

`composer test` também executa testes de integração que precisam de MySQL, extensão cURL e permissão para criar/remover um banco temporário. Execute-os em um ambiente de testes separado, nunca com credenciais de produção. As senhas e documentos presentes em `tests` são fixtures sintéticas usadas apenas pelo teste.

O GitHub Actions incluído verifica a sintaxe PHP e as validações de entrada a cada push e pull request. Ele não acessa banco real nem publica o sistema.

## Segurança e publicação

- [Como publicar esta pasta no GitHub](docs/PUBLICAR-NO-GITHUB.md)
- [Política de segurança](SECURITY.md)
- [Arquitetura](docs/architecture.md)
- [Operação e instalação](docs/operations.md)

A cópia inclui controles de sessão, perfis, proteção CSRF e consultas parametrizadas no código existente. A preparação para portfólio não substitui uma auditoria de segurança antes de exposição pública. Qualquer demonstração hospedada deve utilizar dados fictícios, banco independente e HTTPS.
