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
   ```

   Aguarde o comando terminar sem erros antes de continuar. Se aparecer `port is already allocated`, a porta 8080 está ocupada: altere `127.0.0.1:8080:80` para `127.0.0.1:8081:80` em `compose.yaml` e execute o comando novamente. Nesse caso, o endereço de acesso será http://localhost:8081.

3. Prepare as tabelas do banco vazio:

   ```sh
   docker compose exec app php bin/install.php
   docker compose exec app php bin/migrate.php
   ```

4. **Crie seu login de administrador antes de abrir o sistema:**

   ```sh
   docker compose exec app php bin/create-admin.php "Administrador Demo" admin@example.com
   ```

   Quando aparecer o pedido de senha no terminal, digite uma senha de pelo menos 8 caracteres e pressione Enter. Aguarde a mensagem `Administrador criado.`. A entrada da senha fica visível no terminal; utilize um terminal privado. Faça isso apenas uma vez para esse e-mail em cada instalação.

5. Acesse http://localhost:8080 (ou http://localhost:8081, se você alterou a porta) e preencha a tela de login:

   | Campo | O que preencher |
   | --- | --- |
   | E-mail | `admin@example.com`, se você usou o comando acima |
   | Senha | A senha que você escolheu e digitou no terminal no passo 4 |

   Você pode substituir `admin@example.com` pelo e-mail de sua preferência no comando; nesse caso, use esse mesmo e-mail no login. Não é necessário receber um e-mail de confirmação para entrar.

### Por que preciso criar um login?

Cada pessoa que baixa este projeto executa sua própria instalação, com banco independente. **Não existe conta de demonstração pré-cadastrada nem senha padrão compartilhada.** O comando `create-admin.php` cria o primeiro usuário no banco da sua instalação.

Se você já abriu o sistema e está na tela de login, mas ainda não criou um usuário, execute o passo 4 no terminal, dentro da pasta do projeto, e depois entre com o e-mail e a senha definidos por você. As senhas `DB_PASS` e `DB_ROOT_PASS` do arquivo `.env` são do banco de dados; elas não são a senha do login do sistema.

O link deste repositório no GitHub permite consultar e baixar o código. Para visitantes testarem o sistema diretamente por um link, sem instalar nada, é necessário disponibilizar uma demonstração hospedada separadamente.

A aplicação começa sem moradores ou outros registros. Cadastre somente exemplos fictícios. O e-mail não vem configurado e nenhum agendador é instalado automaticamente. O banco fica em volume Docker próprio, sem porta publicada. O site fica disponível somente no computador local.

Para encerrar, use `docker compose down`. Os registros permanecem no volume. `docker compose down -v` apaga definitivamente o banco desta demonstração.

## Instalação sem Docker

1. Instale PHP 8.2+ com MySQLi, MySQL/MariaDB e Composer.
2. Crie um banco vazio e um usuário exclusivo para ele. Para a instalação, o usuário precisa criar e alterar as tabelas nesse banco; restrinja as permissões após as migrações conforme sua operação.
3. Configure `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` no ambiente do processo PHP. Configure `APP_BASE_URL=/` para executar na raiz. O PHP não lê `.env` automaticamente; esse arquivo é utilizado apenas pelo Docker Compose.
4. Execute `composer install`, `php bin/install.php`, `php bin/migrate.php` e `php bin/create-admin.php "Administrador Demo" admin@example.com`.
5. Inicie com `composer serve` e acesse http://localhost:8000. Entre com `admin@example.com` (ou o e-mail informado no comando de criação) e a senha que você digitou no terminal ao criar o administrador. Não utilize a senha do banco de dados.

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
