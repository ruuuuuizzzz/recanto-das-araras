# Recanto das Araras

Sistema PHP 8.2+ e MySQL/MariaDB para portaria e administração.

## Instalação e atualização

1. Instale as dependências com `composer install`.
2. Configure variáveis de ambiente no Apache/serviço: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` e `APP_BASE_URL` (padrão `/recantodasararas`). Use usuário de banco próprio em produção, com acesso apenas ao banco da aplicação. Nesta versão de portfólio, DB_USER, DB_PASS e DB_NAME são obrigatórios; não existe acesso root automático.
3. Antes de migrar, faça backup: `C:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --result-file=storage\backups\backup.sql condominio`. Backups contêm dados pessoais; mantenha cópia protegida fora da pasta pública.
4. Execute `C:\xampp\php\php.exe bin\migrate.php`. A migração é repetível e preserva os registros. Usa o banco legado com as tabelas moradores, visitantes, encomendas, veiculos e avisos. Para uma instalação vazia, execute primeiro `bin\install.php`.
5. Crie o primeiro administrador: `C:\xampp\php\php.exe bin\create-admin.php "Nome" email@example.com`. A senha é solicitada no terminal. Alternativamente `bin\provision-admin.php email@example.com` gera uma senha temporária aleatória. Não existe senha padrão. O primeiro acesso exige troca da senha.
6. Abra `/recantodasararas/login.php`. A administração cria os demais acessos na tela Usuários. Todos podem alterar a própria senha em Minha conta.

O Apache deve permitir `.htaccess` (`AllowOverride All`, `mod_rewrite` e `mod_authz_core`). As pastas app, bootstrap, config, database, routes, storage, bin, tests, docs e vendor devem ser bloqueadas para HTTP. Em outro servidor, replique essas regras. Use HTTPS em produção.

## Perfis e operações

Portaria consulta as listagens, cadastra/edita visitantes e encomendas e registra entrada, saída e retirada. Administração também gerencia moradores, veículos, avisos, exclusões e usuários, além de consultar pendências. As páginas de auditoria e notificações não são disponibilizadas nesta versão; os registros internos e a fila de e-mail permanecem em funcionamento. Uma sessão inativa expira após 30 minutos. Usuários desativados perdem acesso na próxima requisição. Há limite de tentativas de login por IP e e-mail.

Todas as operações de escrita exigem POST e CSRF. Exclusões de moradores com vínculos são bloqueadas para preservar os registros associados. A auditoria guarda operador, horário, registro, campos alterados e transições de status, sem copiar documentos ou senhas. O status atual de visitantes e encomendas fica no registro; a sequência de transições fica no histórico.

## Dados legados e integridade

A migração vincula apenas correspondências únicas por nome, apartamento e bloco. Veículos antigos não tinham bloco: só recebem vínculo quando nome e apartamento identificam um único morador. Registros ambíguos continuam nas listagens e precisam ser editados para selecionar o responsável. A tela Pendências mostra os IDs a revisar. Não atribua moradores por suposição.

Proprietários duplicados existentes não são apagados. Novas gravações são serializadas por unidade e verificam a regra de um proprietário. Após corrigir as duplicidades, execute novamente a migração para adicionar a restrição única do banco. Validações de documentos existentes só são aplicadas ao editar. Placas são normalizadas nas novas gravações e verificadas contra registros anteriores.

## E-mail e fila

No Windows, execute `powershell -NoProfile -ExecutionPolicy Bypass -File .\bin\configure-email.ps1` com o parâmetro `-SmtpUser seu-email@example.com` para configurar seu próprio remetente. Informe a nova senha de aplicativo somente no prompt oculto do computador. O arquivo local `storage/private/smtp.local.json` guarda a senha criptografada com DPAPI, fica bloqueado para HTTP e ignorado pelo Git. Execute com a mesma conta Windows usada pelo agendador. Não copie esse arquivo entre contas ou computadores: configure novamente.

O agendador utiliza `bin/process-notifications.ps1`, que carrega a configuração protegida e passa as credenciais ao PHP apenas no ambiente do processo. Falhas são registradas em `storage/logs/email-worker.log`. Para verificar a disponibilidade da configuração sem enviar mensagens: `powershell -NoProfile -ExecutionPolicy Bypass -File .\bin\process-notifications.ps1 -CheckOnly`. Esse diagnóstico não confirma autenticação ou entrega SMTP; a confirmação de envio fica na fila após o processamento.

Se alguma credencial for exposta, revogue-a diretamente no provedor; removê-la do arquivo não a invalida. Configure `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASSWORD`, `SMTP_SECURE` (`tls`), `REMETENTE_EMAIL` e opcionalmente `REMETENTE_NOME`. Nenhuma credencial SMTP é gravada no repositório. A aplicação lê variáveis de ambiente; ela não carrega arquivos .env automaticamente.

Execute `C:\xampp\php\php.exe bin\process-notifications.php` periodicamente, por exemplo a cada minuto no Agendador de Tarefas do Windows, com as mesmas variáveis de ambiente. Cada execução processa até 50 mensagens. O script verifica a configuração antes de consumir a fila, faz até cinco tentativas com espera crescente e mantém os registros da fila para acompanhamento operacional. Cadastros, auditoria e inserção na fila são confirmados na mesma transação. Repetir uma transição de status já aplicada não gera outra mensagem. O worker usa bloqueio e recupera reservas expiradas; uma interrupção após a aceitação pelo SMTP pode resultar em reenvio, pois SMTP não garante entrega exatamente uma vez.

As notificações guardam o destinatário selecionado no momento da operação. Dados de retirada e horários anteriores à migração não são inventados. Alterações na fila não enviam e-mails durante a requisição do usuário.

## Verificação

- `C:\xampp\php\php.exe tests\validation.php`
- `C:\xampp\php\php.exe tests\integration.php` (cria um banco isolado temporário e um servidor PHP de teste; exige MySQL local e extensão cURL; nunca envia e-mails).
- Verificação de sintaxe: execute `php -l` em todos os arquivos PHP, exceto dependências.

Listagens usam páginas de 50 registros e preservam filtros; os resumos das listagens correspondem à página atual. O dashboard usa consultas por intervalos de data para aproveitar índices e informa indisponibilidade em caso de erro.

## Operação e manutenção

Mantenha PHP, Composer e dependências atualizados, guarde logs fora do acesso público e teste a restauração de backups. O banco usa fuso `-03:00` e PHP usa `America/Sao_Paulo`. Antes de mudar a política de fuso, revise as datas persistidas. Esquema e migrações ficam em `database`; regras reutilizáveis em `app`; endpoints em `app/Http/Actions`; telas em `app/Views`; recursos públicos em `public/assets`. Os scripts em `bin` mantêm os comandos de instalação e agendamento.
`bin\install-worker.ps1` registra o processamento a cada minuto no Agendador de Tarefas do Windows, usando a conta interativa atual. Configure SMTP antes de esperar entregas; sem configuração, o worker termina sem consumir a fila.
