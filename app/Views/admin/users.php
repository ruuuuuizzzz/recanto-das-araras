<?php
require __DIR__.'/../../../config/database.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_post();
    $name=trim((string)($_POST['nome']??''));$email=mb_strtolower(trim((string)($_POST['email']??'')));$role=$_POST['perfil']??'';$password=(string)($_POST['senha']??'');
    $id=(int)($_POST['id']??0);
    if(isset($_POST['desativar'])) {
        if($id===(int)$_SESSION['user']['id'])$error='Você não pode desativar seu próprio acesso.';
        else {$stmt=$conn->prepare('UPDATE usuarios SET ativo=0 WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();audit($conn,'usuarios',$id,'desativar');}
    } elseif(!$name || mb_strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100 || !in_array($role,['administracao','portaria'],true) || strlen($password)<8) $error='Informe nome, e-mail válido, perfil e senha com pelo menos 8 caracteres.';
    else {
        try {$hash=password_hash($password,PASSWORD_DEFAULT);$stmt=$conn->prepare('INSERT INTO usuarios(nome,email,senha,perfil,trocar_senha) VALUES (?,?,?,?,0)');$stmt->bind_param('ssss',$name,$email,$hash,$role);$stmt->execute();audit($conn,'usuarios',$conn->insert_id,'criar');$_SESSION['flash']='Usuário criado.';header('Location: '.base_url('usuarios.php'));exit;}
        catch(mysqli_sql_exception $e) {if($e->getCode()===1062)$error='Este e-mail já está cadastrado.';else throw $e;}
    }
}
$rows=$conn->query('SELECT id,nome,email,perfil,ativo FROM usuarios ORDER BY id DESC'.pagination_sql())->fetch_all(MYSQLI_ASSOC);pagination($rows);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Usuários | Recanto das Araras</title>
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/global.css')) ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/visual-2026.css')) ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/usuarios.css')) ?>">
</head>
<body class="users-page">
<?php require_once __DIR__.'/../layouts/sidebar.php'; sidebar('Usuários'); ?>
<main class="content users-content">
    <header class="users-header"><div><p class="users-eyebrow">ADMINISTRAÇÃO</p><h1>Gestão de usuários</h1><p>Organize os acessos da equipe ao condomínio.</p></div><span class="users-security"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6z"/><path d="m8 12 3 3 5-6"/></svg>Acesso restrito</span></header>
    <?php if($error): ?><p class="users-feedback error" role="alert"><?= esc($error) ?></p><?php endif ?>
    <?php if(!empty($_SESSION['flash'])): ?><p class="users-feedback" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
    <div class="users-layout">
        <section class="users-panel users-create" aria-labelledby="create-title">
            <div class="users-panel-heading"><span class="users-section-icon" aria-hidden="true">＋</span><div><h2 id="create-title">Novo usuário</h2><p>Adicione um membro à equipe.</p></div></div>
            <form method="post" class="users-form">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <label for="user-name">Nome completo<input id="user-name" name="nome" maxlength="100" required autocomplete="name" placeholder="Ex.: Maria Silva" value="<?= esc($_POST['nome']??'') ?>"></label>
                <label for="user-email">E-mail<input id="user-email" name="email" type="email" maxlength="100" required autocomplete="email" placeholder="nome@exemplo.com" value="<?= esc($_POST['email']??'') ?>"></label>
                <label for="user-role">Perfil de acesso<select id="user-role" name="perfil"><option value="portaria">Portaria</option><option value="administracao" <?= ($_POST['perfil']??'')==='administracao'?'selected':'' ?>>Administração</option></select></label>
                <label for="user-password">Senha<input id="user-password" name="senha" type="password" minlength="8" required autocomplete="new-password" placeholder="Crie uma senha segura" aria-describedby="password-help"></label>
                <p id="password-help" class="users-field-help">Mínimo de 8 caracteres.</p>
                <button class="users-submit">Criar usuário <span aria-hidden="true">→</span></button>
            </form>
            <div class="users-role-help"><strong>Sobre os perfis</strong><p><b>Portaria</b> consulta moradores e registra veículos, visitantes e encomendas.</p><p><b>Administração</b> tem acesso completo à gestão.</p></div>
        </section>
        <section class="users-panel users-list" aria-labelledby="team-title">
            <div class="users-panel-heading"><div><h2 id="team-title">Equipe cadastrada</h2><p>Consulte os perfis e a situação de cada acesso.</p></div><span class="users-count"><?= count($rows) ?> nesta página</span></div>
            <div class="users-table-scroll" role="region" aria-label="Lista de usuários" tabindex="0">
                <table class="users-table"><thead><tr><th scope="col">Usuário</th><th scope="col">Perfil</th><th scope="col">Situação</th><th scope="col"><span class="users-sr-only">Ações</span></th></tr></thead><tbody>
                <?php foreach($rows as $row): $isSelf=(int)$row['id']===(int)$_SESSION['user']['id']; ?>
                    <tr><td><div class="users-person"><span class="users-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr(trim($row['nome']),0,1))) ?></span><div><div class="users-person-name"><?= esc($row['nome']) ?><?php if($isSelf): ?><span class="users-self">Você</span><?php endif ?></div><span class="users-email"><?= esc($row['email']) ?></span></div></div></td>
                        <td><span class="users-role <?= $row['perfil']==='administracao'?'is-admin':'' ?>"><?= $row['perfil']==='administracao'?'Administração':'Portaria' ?></span></td>
                        <td><span class="users-status <?= $row['ativo']?'is-active':'is-inactive' ?>"><?= $row['ativo']?'Ativo':'Desativado' ?></span></td>
                        <td><?php if($row['ativo'] && !$isSelf): ?><form method="post" onsubmit="return confirm('Desativar este usuário?')"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="users-deactivate" name="desativar" value="1" aria-label="Desativar acesso de <?= esc($row['nome']) ?>">Desativar</button></form><?php else: ?><span class="users-no-action">—</span><?php endif ?></td>
                    </tr>
                <?php endforeach ?>
                <?php if(!$rows): ?><tr><td colspan="4" class="users-empty">Nenhum usuário cadastrado.</td></tr><?php endif ?>
                </tbody></table>
            </div>
            <?php pagination_links() ?>
        </section>
    </div>
</main>
</body>
</html>
