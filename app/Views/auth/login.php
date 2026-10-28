<?php
define('PUBLIC_PAGE', true);
require __DIR__.'/../../../config/database.php';
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    require_post();
    $email=mb_strtolower(trim((string)($_POST['email']??'')));
    $key=hash('sha256', ($_SERVER['REMOTE_ADDR']??'').':'.$email);
    $stmt=$conn->prepare('SELECT tentativas, inicio FROM login_tentativas WHERE chave=?');
    $stmt->bind_param('s',$key); $stmt->execute(); $attempt=$stmt->get_result()->fetch_assoc();
    if ($attempt && $attempt['tentativas']>=5 && strtotime($attempt['inicio'])>time()-900) {
        $error='Muitas tentativas. Aguarde 15 minutos.';
    } else {
        $stmt=$conn->prepare('SELECT id,nome,email,senha,perfil FROM usuarios WHERE email=? AND ativo=1');
        $stmt->bind_param('s',$email); $stmt->execute(); $user=$stmt->get_result()->fetch_assoc();
        if ($user && password_verify((string)($_POST['senha']??''),$user['senha'])) {
            session_regenerate_id(true); unset($user['senha']); $_SESSION['user']=$user; $_SESSION['last_seen']=time(); $_SESSION['csrf']=bin2hex(random_bytes(32));
            $stmt=$conn->prepare('DELETE FROM login_tentativas WHERE chave=?');$stmt->bind_param('s',$key);$stmt->execute();
            audit($conn,'usuarios',(int)$user['id'],'login'); header('Location: '.base_url());exit;
        }
        $stmt=$conn->prepare("INSERT INTO login_tentativas(chave,tentativas,inicio) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE tentativas=IF(inicio<DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,tentativas+1), inicio=IF(inicio<DATE_SUB(NOW(),INTERVAL 15 MINUTE),NOW(),inicio)");
        $stmt->bind_param('s',$key);$stmt->execute(); $error='E-mail ou senha inválidos.';
    }
}
?>
<?php
// Presentation only; authentication is handled above.
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#174c37">
    <title>Entrar | Recanto das Araras</title>
    <link rel="icon" type="image/png" href="<?= esc(base_url('assets/images/logo_novo.png')) ?>">
    <link rel="stylesheet" href="<?= esc(base_url('assets/css/login.css')) ?>">
    <script src="<?= esc(base_url('assets/js/login.js')) ?>" defer></script>
</head>
<body>
    <main class="login-shell">
        <section class="brand-panel" aria-labelledby="brand-title">
            <div class="brand-heading">
                <img src="<?= esc(base_url('assets/images/logo_novo.png')) ?>" alt="" width="64" height="64">
                <div><strong>Recanto das Araras</strong><span>Gestão condominial</span></div>
            </div>
            <div class="brand-content">
                <span class="eyebrow"><span></span> CONECTANDO NOSSO CONDOMÍNIO</span>
                <h1 id="brand-title">Mais organização.<br>Mais tranquilidade.<br><em>Todos os dias.</em></h1>
                <p>Um só lugar para cuidar da rotina do condomínio e de quem faz parte dele.</p>
                <div class="brand-features"><span>Moradores e visitantes</span><span>Encomendas e avisos</span></div>
            </div>
            <div class="brand-footer"><span class="footer-mark" aria-hidden="true">✦</span> Cuidar do nosso espaço começa aqui.</div>
        </section>
        <section class="form-panel" aria-labelledby="login-title">
            <div class="login-content">
                <div class="form-emblem" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="5" y="10" width="14" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v3"/></svg></div>
                <span class="form-kicker">BEM-VINDO AO SEU CONDOMÍNIO</span>
                <h2 id="login-title">Que bom ter você aqui.</h2>
                <p class="form-description">Acesse sua conta para continuar.</p>
                <?php if ($error): ?>
                    <div class="login-alert" role="alert" id="login-error"><?= esc($error) ?></div>
                <?php endif ?>
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <div class="form-field">
                        <label for="email">E-mail</label>
                        <input id="email" type="email" name="email" required autocomplete="username" placeholder="seuemail@exemplo.com" value="<?= esc($_POST['email']??'') ?>" <?php if ($error): ?>aria-describedby="login-error"<?php endif ?>>
                    </div>
                    <div class="form-field">
                        <label for="senha">Senha</label>
                        <div class="password-field">
                            <input id="senha" type="password" name="senha" required autocomplete="current-password" placeholder="Digite sua senha" <?php if ($error): ?>aria-describedby="login-error"<?php endif ?>>
                            <button class="password-toggle" type="button" aria-label="Mostrar senha" aria-controls="senha" aria-pressed="false" hidden><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg></button>
                        </div>
                        <span id="caps-warning" class="caps-warning" role="status" hidden>Caps Lock está ativado.</span>
                    </div>
                    <button class="submit-button" type="submit">Entrar na minha conta <span aria-hidden="true">→</span></button>
                </form>
                <p class="access-help">Precisa de acesso ou esqueceu sua senha?<br><strong>Entre em contato com a administração.</strong></p>
            </div>
            <p class="form-footer"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/></svg> Acesso exclusivo para usuários autorizados</p>
        </section>
    </main>
</body>
</html>
