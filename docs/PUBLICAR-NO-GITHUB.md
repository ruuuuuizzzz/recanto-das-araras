# Publicar no GitHub

Publique somente o conteúdo da pasta `github-portfolio`. Não envie a pasta do sistema original nem copie seu banco para cá.

## Pelo site, sem terminal

1. No GitHub, crie um repositório chamado `recanto-das-araras`, inicialmente privado. Não adicione README ou .gitignore, pois eles já estão nesta pasta.
2. Use **Add file > Upload files** para enviar o conteúdo desta pasta. Verifique a inclusão dos arquivos ocultos `.gitignore`, `.dockerignore`, `.env.example`, `.htaccess` e da pasta `.github`.
3. Não envie `.env`, `vendor`, arquivos SQL, logs ou configurações locais. O upload pelo navegador não aplica o `.gitignore` automaticamente.
4. Confira o conteúdo enviado e torne o repositório público em **Settings > General > Danger Zone > Change repository visibility**.

## Pelo Git no PowerShell

Abra o terminal DENTRO de `github-portfolio`. Crie um repositório vazio no GitHub e substitua SEU-USUARIO no comando abaixo:

```powershell
git init
git branch -M main
git add .
git diff --cached --name-only
```

Revise os nomes antes de continuar. O arquivo `.env.example` deve aparecer; `.env`, backups, logs e arquivos privados não devem aparecer.

```powershell
git commit -m "Publica projeto de portfolio"
git remote add origin https://github.com/SEU-USUARIO/recanto-das-araras.git
git push -u origin main
```

Esta distribuição não inclui o histórico Git do projeto original. O envio deve começar dentro desta pasta, com um histórico novo.

## Apresentação no currículo

Na descrição do repositório: **Sistema de gestão condominial em PHP e MySQL, com controle de portaria, encomendas, perfis de acesso e auditoria.**

Fixe o repositório no seu perfil do GitHub. No currículo, inclua o link e destaque as funcionalidades e tecnologias que você efetivamente desenvolveu. Caso inclua imagens ou vídeos depois, utilize somente dados fictícios.

## Proteção da conta e da demonstração

Ative autenticação em dois fatores, confira colaboradores autorizados e habilite as proteções de segredos disponíveis no GitHub. Não publique credenciais. Para deixar o sistema acessível pela internet, prepare uma hospedagem separada com HTTPS e banco fictício; a configuração Docker fornecida é local.

O repositório não recebeu uma licença automaticamente. Escolha uma licença antes de conceder permissões amplas de reutilização do seu código e verifique os direitos dos recursos visuais utilizados.
