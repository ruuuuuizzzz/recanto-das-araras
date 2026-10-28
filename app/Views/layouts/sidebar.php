<?php
function sidebar(string $active): void { ?>
<aside class="sidebar"><nav class="abas" aria-label="Menu principal"><div class="logo"><img src="<?= esc(base_url('assets/images/logo_novo.png')) ?>" alt="Logo Recanto das Araras"><h2>Recanto das Araras</h2></div>
<?php foreach (['index.php'=>'Dashboard','moradores'=>'Moradores','encomendas'=>'Encomendas','veiculos'=>'Veículos','visitantes'=>'Visitantes','avisos'=>'Avisos'] as $route=>$label): $url=$route==='index.php'?$route:'telas/telasPrincipais/'.$route.'.php'; ?>
<a href="<?= esc(base_url($url)) ?>" <?= $label===$active?'class="active" aria-current="page"':'' ?>><?= $label ?></a><?php endforeach ?>
<?php if($_SESSION['user']['perfil']==='administracao'): ?><a href="<?= esc(base_url('usuarios.php')) ?>" <?= $active==='Usuários'?'class="active" aria-current="page"':'' ?>>Usuários</a><?php endif ?>
<div class="user account-panel">
    <div class="account-identity">
        <span class="account-avatar" aria-hidden="true"><?= esc(mb_strtoupper(mb_substr(trim($_SESSION['user']['nome']),0,1))) ?></span>
        <div class="account-details"><strong><?= esc($_SESSION['user']['nome']) ?></strong><small><?= $_SESSION['user']['perfil']==='administracao'?'Administração':'Portaria' ?></small></div>
    </div>
    <div class="account-actions">
        <a class="account-link" href="<?= esc(base_url('minha-conta.php')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg>Minha conta</a>
        <form method="post" action="<?= esc(base_url('logout.php')) ?>"><input type="hidden" name="csrf" value="<?= csrf_token() ?>"><button class="account-logout" aria-label="Sair da conta"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M10 4H4v16h6M14 8l4 4-4 4M8 12h12"/></svg>Sair</button></form>
    </div>
</div></nav></aside>
<?php }
