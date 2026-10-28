<?php
    include __DIR__.'/../../../config/database.php';

    $busca    = trim($_GET['busca'] ?? '');
    $urgencia = trim($_GET['urgencia'] ?? '');

    $wheres = [];
    $params = [];
    $types  = '';

    if (!empty($busca)) {
        $wheres[] = 'titulo LIKE ?';
        $types   .= 's';
        $params[] = '%' . $busca . '%';
    }

    if (!empty($urgencia)) {
        $wheres[] = 'nivel_urgencia = ?';
        $types   .= 's';
        $params[] = $urgencia;
    }

    $where = !empty($wheres) ? ' WHERE ' . implode(' AND ', $wheres) : '';

    $stmt = $conn->prepare("SELECT * FROM avisos{$where} ORDER BY id DESC" . pagination_sql());
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $pageRows=$res->fetch_all(MYSQLI_ASSOC); pagination($pageRows);

    $avisos = $pageRows;

    $totalAvisos = count($avisos);
    $totalAlta   = count(array_filter($avisos, fn($a) => $a['nivel_urgencia'] === 'alta'));
    $totalMedia  = count(array_filter($avisos, fn($a) => $a['nivel_urgencia'] === 'media'));
    $totalBaixa  = count(array_filter($avisos, fn($a) => $a['nivel_urgencia'] === 'baixa'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recanto das Araras — Avisos</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cards.css">
    <link rel="stylesheet" href="../../assets/css/botoes.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/avisos.css">
    <link rel="stylesheet" href="../../assets/css/componentes.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js')) ?>" defer></script>
</head>
<body data-perfil="<?= esc($_SESSION['user']['perfil']) ?>">

    <?php require_once __DIR__.'/../layouts/sidebar.php'; sidebar('Avisos'); ?>

    <div class="content">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
        <div class="subtitle">
            <h1>Avisos Gerais</h1>
        </div>

        <form method="GET" action="avisos.php" class="campoBusca">
            <input type="text" name="busca" placeholder="Título do aviso..." value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="urgencia" value="<?= htmlspecialchars($urgencia) ?>">
            <button type="submit" class="btnBuscar">Buscar</button>
            <a href="avisos.php" class="btnLimpar">Limpar</a>
        </form>

        <div class="filtro">
            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top: 20px">
                <a href="?<?= http_build_query(['busca' => $busca, 'urgencia' => '']) ?>"
                   class="btnFiltroStatus <?= $urgencia === '' ? 'ativo' : '' ?>">Todos</a>
                <a href="?<?= http_build_query(['busca' => $busca, 'urgencia' => 'alta']) ?>"
                   class="btnFiltroStatus <?= $urgencia === 'alta' ? 'ativo' : '' ?>">Alta</a>
                <a href="?<?= http_build_query(['busca' => $busca, 'urgencia' => 'media']) ?>"
                   class="btnFiltroStatus <?= $urgencia === 'media' ? 'ativo' : '' ?>">Média</a>
                <a href="?<?= http_build_query(['busca' => $busca, 'urgencia' => 'baixa']) ?>"
                   class="btnFiltroStatus <?= $urgencia === 'baixa' ? 'ativo' : '' ?>">Baixa</a>
            </div>
        </div>

        <?php if ($totalAvisos > 0): ?>
        <div class="resumoGeral">
            <div class="resumoCard">
                <span class="numero"><?= $totalAvisos ?></span>
                <span class="rotulo">Avisos</span>
            </div>
            <div class="resumoCard urgente">
                <span class="numero"><?= $totalAlta ?></span>
                <span class="rotulo">Urgência alta</span>
            </div>
            <div class="resumoCard atencao">
                <span class="numero"><?= $totalMedia ?></span>
                <span class="rotulo">Urgência média</span>
            </div>
            <div class="resumoCard tranquilo">
                <span class="numero"><?= $totalBaixa ?></span>
                <span class="rotulo">Urgência baixa</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="campoAdicionar">
            <a href="../telasCadastro/cadastro_avisos.php">+ Novo aviso</a>
        </div>

        <div class="campoAvisos">
            <?php if ($totalAvisos > 0): ?>
                <?php foreach ($avisos as $row): ?>
                    <div class="cardAvisos nivel-<?= htmlspecialchars($row['nivel_urgencia']) ?>">
                        <div class="infoAvisos">
                            <div class="cabecalhoAviso">
                                <h3><?= htmlspecialchars($row['titulo']) ?></h3>
                                <span class="tagUrgencia"><?= ucfirst(htmlspecialchars($row['nivel_urgencia'])) ?></span>
                            </div>
                            <span class="dataAviso"><?= date('d/m/Y', strtotime($row['data_aviso'])) ?></span>
                            <p><?= htmlspecialchars($row['descricao']) ?></p>
                        </div>
                        <div class="acoesAvisos">
                            <a href="../telasCadastro/cadastro_avisos.php?id=<?= $row['id'] ?>" class="btnAlterar">Alterar</a>
                            <?php if($_SESSION['user']['perfil']==='administracao'): ?><a href="../../crud/excluir_aviso.php?id=<?= $row['id'] ?>" class="btnExcluir">Excluir</a><?php endif ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="semAvisos">
                    <p>Nenhum aviso encontrado.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php pagination_links(); ?>
    </div>

    <script>
    function confirmarExclusao(id) {
        Swal.fire({
            title: 'Tem certeza?',
            text: 'Você não poderá reverter isso!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sim, excluir!',
            cancelButtonText: 'Cancelar'
        }).then(result => {
            if (result.isConfirmed) {
                // Caminho perfeito para a pasta crud
                submitAction('../../crud/excluir_aviso.php', id);
            }
        });
    }
    </script>
</body>
</html>
