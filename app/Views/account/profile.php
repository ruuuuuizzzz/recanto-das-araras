<?php
require __DIR__.'/../../../config/database.php';$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_post();$id=(int)$_SESSION['user']['id'];$stmt=$conn->prepare('SELECT senha FROM usuarios WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$hash=$stmt->get_result()->fetch_assoc()['senha'];
    $password=(string)($_POST['nova_senha']??'');
    if(!password_verify((string)($_POST['senha_atual']??''),$hash))$error='Senha atual incorreta.';
    elseif(strlen($password)<8 || $password!==($_POST['confirmacao']??'') || password_verify($password,$hash))$error='Use uma nova senha com pelo menos 8 caracteres e confirme o mesmo valor.';
    else {$newHash=password_hash($password,PASSWORD_DEFAULT);$stmt=$conn->prepare('UPDATE usuarios SET senha=?,trocar_senha=0 WHERE id=?');$stmt->bind_param('si',$newHash,$id);$stmt->execute();audit($conn,'usuarios',$id,'alterar_senha');session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));header('Location: '.base_url());exit;}
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Minha conta</title><link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"></head><body class="auth"><main><h1>Alterar senha</h1><p><?= esc($_SESSION['user']['nome']) ?> · Escolha uma senha pessoal com pelo menos 8 caracteres.</p><?php if($error):?><p role="alert"><?= esc($error) ?></p><?php endif ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><label>Senha atual<input name="senha_atual" type="password" required autocomplete="current-password"></label><label>Nova senha<input name="nova_senha" type="password" required minlength="8" autocomplete="new-password"></label><label>Confirme a nova senha<input name="confirmacao" type="password" required minlength="8" autocomplete="new-password"></label><button>Salvar senha</button></form><a href="<?= esc(base_url()) ?>">Voltar ao painel</a></main></body></html>
