# Estrutura do projeto

| Diretório | Responsabilidade |
| --- | --- |
| `app/Actions` | Gravação, exclusão e transições de estado, com validação e transações. |
| `app/Http/Actions` | Endpoints HTTP de cada operação. |
| `app/Http` | Ações de autenticação que não renderizam telas. |
| `app/Queries` | Consultas do dashboard. |
| `app/Services` | Serviços compartilhados, como criação das notificações na fila. |
| `app/Support` | Validação, documentos e helpers de formulários. |
| `app/Views` | Telas organizadas em auth, account, admin, pages, forms e layouts. |
| `bootstrap` | Inicialização, sessões, proteção das requisições e funções comuns. |
| `config` | Configuração de banco e e-mail por ambiente. |
| `database` | Esquema base e migrações. |
| `public` | Entrada HTTP e assets CSS, JavaScript e imagens. |
| `routes` | Mapeamento explícito de URLs para telas e endpoints. |
| `storage/private` | Configuração SMTP local criptografada. Não versionar. |
| `storage/logs` | Logs da aplicação e do worker. Não versionar. |
| `storage/backups` | Backups locais. Não versionar. |
| `bin` | Comandos de instalação, usuários, configuração e processamento da fila. |
| `tests` | Validação e testes de integração com banco temporário. |
| `docs` | Documentação de arquitetura e operação. |
| `vendor` | Dependências gerenciadas pelo Composer. |

## Requisições

O Apache encaminha as URLs para `public/index.php`, que consulta `routes/web.php`. Apenas rotas declaradas são executadas. Os endereços existentes de páginas, formulários e operações foram mantidos, apesar de os arquivos PHP terem saído da raiz pública.

Cada endpoint carrega a configuração de banco e o bootstrap, que verificam sessão, perfil e proteção CSRF. Os endpoints de gravação delegam a lógica comum para `app/Actions`. As views usam caminhos de filesystem baseados em `__DIR__`; os recursos visuais ficam em `/assets`.

O `index.php` na raiz é apenas um adaptador para o endereço já usado no XAMPP. Em uma hospedagem nova, prefira apontar o DocumentRoot para `public/` e configurar `APP_BASE_URL` conforme o endereço publicado. Não aponte links de navegação para os arquivos dentro de `app/Views`: use as rotas HTTP.

## E-mails

Os cadastros inserem notificações no banco. O agendador chama `bin/process-notifications-hidden.vbs`, que inicia o worker PowerShell sem console. O PowerShell lê `storage/private/smtp.local.json`, descriptografa a senha com a conta Windows atual e chama o processador PHP. Os logs ficam em `storage/logs`. Os nomes dos scripts em `bin` foram mantidos para preservar o agendamento.

## Proteção dos arquivos

As pastas internas têm `.htaccess` com acesso HTTP negado. No servidor de desenvolvimento do PHP, o roteador também expõe apenas as rotas declaradas e assets de extensões permitidas. Credenciais, logs e backups são excluídos pelo `.gitignore`. As dependências não devem ser editadas manualmente.
