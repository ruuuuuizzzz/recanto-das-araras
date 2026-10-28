<?php
    include __DIR__.'/../../../config/database.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recanto das Araras — Moradores</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/moradores.css?v=20261025-layout2">
    <link rel="stylesheet" href="../../assets/css/componentes.css">
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js')) ?>" defer></script>
</head>
<body data-perfil="<?= esc($_SESSION['user']['perfil']) ?>">


<?php require_once __DIR__.'/../layouts/sidebar.php'; sidebar('Moradores'); ?>

<div class="content--moradores">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
    <div class="app">
        <div class="container">
            <div class="header">
                <h1>Gestão de Moradores</h1>
                <p>Selecione um bloco e consulte os residentes</p>
            </div>

            <div class="blocos" id="blocos">
                <div class="bloco" onclick="selecionarBloco(1)">Bloco 1</div>
                <div class="bloco" onclick="selecionarBloco(2)">Bloco 2</div>
                <div class="bloco" onclick="selecionarBloco(3)">Bloco 3</div>
            </div>

            <div class="buscaMoradores">
                <input type="text" id="buscaMoradorInput" placeholder="Buscar morador pelo nome..." autocomplete="off">
                <button type="button" id="buscaMoradorBtn" onclick="filtrarMoradores()">Buscar</button>
            </div>

            <div id="lista" class="lista">
                <p class="empty">Selecione um bloco para ver os moradores.</p>
            </div>

            <?php if($_SESSION['user']['perfil']==='administracao'): ?>
            <div class="form">
                <a id="btnAdicionarMorador" href="../telasCadastro/cadastro_morador.php">+ Adicionar morador</a>
            </div>
            <?php endif ?>
        </div>
    </div>
</div>

<script src="<?= esc(base_url('assets/js/moradores.js?v='.filemtime(__DIR__.'/../../../public/assets/js/moradores.js'))) ?>" defer></script>
</body>
</html>
