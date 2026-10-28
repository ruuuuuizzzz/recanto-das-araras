# Recanto das Araras

Sistema web de gestão condominial para organizar a rotina de portaria e administração: moradores, veículos, visitantes, encomendas e avisos em um único painel.

Desenvolvido em **PHP, MySQL, JavaScript e CSS**, o projeto inclui perfis de acesso, auditoria de operações e processamento de notificações por e-mail.

> Este repositório é uma versão de portfólio. A instalação utiliza um banco independente, inicialmente vazio, sem dados reais de moradores. Para testar, execute o projeto localmente seguindo as instruções abaixo.

## Funcionalidades

- **Moradores e veículos:** cadastro, edição, consulta e associação dos registros às unidades.
- **Visitantes:** registro de visitas e controle de entrada e saída.
- **Encomendas:** registro de recebimento e acompanhamento de retirada.
- **Avisos:** publicação de comunicados com nível de urgência.
- **Usuários:** perfis de administração e portaria, ativação de acessos e alteração de senha.
- **Auditoria:** histórico de operações e transições de status.
- **Notificações:** fila de envio de e-mails com processamento separado das requisições web.

## Tecnologias

| Camada | Tecnologias |
| --- | --- |
| Backend | PHP 8.2+ e MySQLi |
| Banco de dados | MySQL / MariaDB |
| Interface | HTML, CSS e JavaScript |
| Dependências | Composer e PHPMailer |
| Ambiente local com Docker | PHP 8.3, Apache e MySQL 8.4 |
| Integração contínua | GitHub Actions: sintaxe PHP e validações de entrada |

## Executar com Docker

### 1. Obter o projeto

Instale o Docker com suporte ao Docker Compose e mantenha-o em execução. Com o Git instalado, abra um terminal e execute:

```sh
git clone https://github.com/ruuuuuizzzz/recanto-das-araras.git
cd recanto-das-araras
```

Também é possível baixar em **Code → Download ZIP** no GitHub. Extraia o arquivo e abra o terminal na pasta que contém `compose.yaml`.

Execute os próximos comandos nessa pasta. Não é necessário instalar PHP, MySQL ou Composer no computador ao usar Docker.

### 2. Configurar o ambiente

Copie `.env.example` para `.env`. No PowerShell:

```powershell
Copy-Item .env.example .env
notepad .env
```

No Linux ou macOS, use `cp .env.example .env` e abra o arquivo no editor de sua preferência.

Substitua os valores de `DB_PASS` e `DB_ROOT_PASS` por duas senhas fortes e diferentes. Salve antes de continuar. Essas senhas são exclusivas do banco de dados; o login da aplicação será criado no passo 5.

### 3. Iniciar os serviços

```sh
docker compose up -d --build
```

A primeira execução baixa as imagens e instala as dependências. Aguarde terminar sem erros antes de executar os próximos comandos.

### 4. Preparar o banco

Execute um comando por vez:

```sh
docker compose exec app php bin/install.php
docker compose exec app php bin/migrate.php
```

Esses comandos criam a estrutura de tabelas e aplicam as migrações. Nenhum dado real ou usuário padrão é importado.

### 5. Criar o primeiro usuário

```sh
docker compose exec app php bin/create-admin.php "Administrador Demo" admin@example.com
```

Quando o terminal solicitar, digite uma senha de **pelo menos 8 caracteres** e pressione Enter. Aguarde a mensagem `Administrador criado.`. A senha digitada fica visível no terminal.

O nome e o e-mail são exemplos: você pode substituí-los no comando. O endereço `admin@example.com` funciona como identificador de acesso e não exige confirmação por e-mail. Crie esse usuário apenas uma vez para cada instalação.

### 6. Acessar o sistema

Abra [http://localhost:8080](http://localhost:8080) e entre com:

| Campo | Valor |
| --- | --- |
| E-mail | `admin@example.com`, ou o endereço escolhido no passo 5 |
| Senha | A senha digitada no terminal no passo 5 |

**Não existe senha padrão nem conta de demonstração pré-cadastrada.** Cada instalação possui seus próprios usuários. As senhas do arquivo `.env` não são usadas na tela de login.

Após entrar, cadastre dados fictícios para explorar os módulos. A administração pode criar outros usuários pela tela **Usuários**.

### Encerrar e iniciar novamente

```sh
docker compose down
docker compose up -d
```

O primeiro comando encerra os serviços; o segundo inicia novamente. Os usuários e registros permanecem no volume do banco, portanto não é necessário repetir a instalação ou criar o administrador novamente.

> `docker compose down -v` remove também o volume e apaga definitivamente o banco local.

## Solução de problemas

| Situação | Como resolver |
| --- | --- |
| `port is already allocated` | A porta 8080 está ocupada. Em `compose.yaml`, troque `127.0.0.1:8080:80` por `127.0.0.1:8081:80`, execute `docker compose up -d --build` e acesse http://localhost:8081. |
| `service "app" is not running` | Execute `docker compose ps` e confira o erro de inicialização. Resolva-o antes de executar os comandos de instalação. Para consultar os logs, use `docker compose logs app db`. |
| O sistema abriu no login, mas não há usuário | Execute o comando do passo 5 e entre com o e-mail e a senha que definiu. |
| Não conecta ao Docker | Verifique se o Docker está iniciado e disponível no terminal. |
| As notificações não chegam | O SMTP e o processamento da fila são opcionais e precisam de configuração própria. Consulte o guia de operação. |

## Instalação sem Docker

Requisitos: PHP 8.2+ com MySQLi, Composer e MySQL/MariaDB.

1. Obtenha o projeto e execute `composer install` na raiz.
2. Crie um banco vazio e um usuário exclusivo com permissão para criar e alterar as tabelas desse banco durante a instalação.
3. Defina as variáveis abaixo no ambiente do processo PHP:

   | Variável | Finalidade |
   | --- | --- |
   | `DB_HOST` | Endereço do servidor MySQL |
   | `DB_NAME` | Nome do banco criado |
   | `DB_USER` | Usuário exclusivo do banco |
   | `DB_PASS` | Senha desse usuário |
   | `APP_BASE_URL` | `/` para executar na raiz do servidor local |

   Nessa modalidade, o PHP não carrega `.env` automaticamente. O arquivo `.env` fornecido é utilizado pelo Docker Compose.

4. Prepare as tabelas e crie seu acesso:

   ```sh
   php bin/install.php
   php bin/migrate.php
   php bin/create-admin.php "Administrador Demo" admin@example.com
   ```

5. Execute `composer serve` e acesse [http://localhost:8000](http://localhost:8000). Use o e-mail informado no comando e a senha escolhida no terminal.

## Estrutura do projeto

| Pasta | Responsabilidade |
| --- | --- |
| `app/` | Telas, validações, consultas e regras de negócio |
| `bootstrap/` | Inicialização, sessão e autorização |
| `config/` | Configurações de banco e e-mail |
| `database/` | Estrutura inicial e migrações |
| `public/` | Entrada HTTP e recursos da interface |
| `routes/` | Mapeamento das URLs |
| `bin/` | Comandos de instalação, administração e notificações |
| `tests/` | Testes de validação e integração |
| `storage/` | Armazenamento local de backups, logs e configurações privadas |
| `docs/` | Documentação técnica e operacional |

## Testes

Para executar as validações de entrada no ambiente Docker:

```sh
docker compose exec app php tests/validation.php
```

Em uma instalação com PHP local, use `php tests/validation.php`.

O comando `composer test` executa também os testes de integração. Esse conjunto exige MySQL, a extensão PHP cURL e permissão para criar e remover um banco temporário. Execute-o em um ambiente de testes separado. As contas, documentos e senhas em `tests/` são dados sintéticos utilizados pelos testes.

O workflow do GitHub Actions verifica a sintaxe dos arquivos PHP e executa as validações de entrada a cada push e pull request.

## Segurança e hospedagem

A aplicação inclui controle de acesso por perfil, proteção CSRF, tratamento de sessão e consultas parametrizadas. Credenciais locais, logs e backups são ignorados pelo Git.

A configuração Docker disponibiliza o site somente no computador local e mantém a porta do banco sem publicação. O envio de e-mails e a execução periódica da fila não são ativados automaticamente.

O GitHub disponibiliza o código-fonte. Para oferecer uma demonstração acessível pela internet, é necessária uma hospedagem compatível com PHP, com raiz pública em `public/`, HTTPS e um banco separado contendo somente dados fictícios. Uma revisão de segurança é necessária antes dessa exposição.

## Documentação

- [Arquitetura e responsabilidades](docs/architecture.md)
- [Instalação, segurança e operação](docs/operations.md)
- [Política de segurança](SECURITY.md)
