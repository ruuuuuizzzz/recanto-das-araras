<?php
    include __DIR__.'/../../../config/database.php';

    $busca  = trim($_GET['busca'] ?? '');
    $params = [];
    $types  = '';
    $where  = '';

    if (!empty($busca)) {
        $like   = '%' . $busca . '%';
        
        $where  = ' WHERE v.placa LIKE ? OR m.nome LIKE ?';
        $types  = 'ss';
        $params = [$like, $like];
    }


    $sql = "SELECT 
                m.id AS morador_id, COALESCE(m.bloco,v.bloco) AS morador_bloco, COALESCE(m.nome, v.morador_nome) AS morador_nome, 
                COALESCE(m.apartamento, v.apartamento) AS apartamento, 
                v.id, 
                v.placa, 
                v.modelo, 
                v.cor,
                v.tipo_veiculo
            FROM veiculos v
            LEFT JOIN moradores m ON m.id = v.morador_id
            {$where} 
            ORDER BY m.nome ASC, v.id DESC" . pagination_sql();

    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        fail('Não foi possível consultar os dados.',500);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $pageRows=$res->fetch_all(MYSQLI_ASSOC); pagination($pageRows);

    $moradores = [];
    foreach ($pageRows as $row) {
        $chave = ($row['morador_id'] ?? ('sem-vinculo-'.$row['id']));
        if (!isset($moradores[$chave])) {
            $moradores[$chave] = [
                'nome'        => $row['morador_nome'] ?: 'Morador sem vínculo',
                'bloco' => $row['morador_bloco'],
                'apartamento' => $row['apartamento'],
                'veiculos'    => [],
            ];
        }
        

        if (!empty($row['id'])) {
            $moradores[$chave]['veiculos'][] = $row;
        }
    }

    $totalMorad  = count($moradores);
    $totalVeic   = array_sum(array_map(fn($m) => count($m['veiculos']), $moradores));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recanto das Araras — Veículos</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cards.css">
    <link rel="stylesheet" href="../../assets/css/botoes.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/veiculos.css">
    <link rel="stylesheet" href="../../assets/css/componentes.css">
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js')) ?>" defer></script>
</head>
<body data-perfil="<?= esc($_SESSION['user']['perfil']) ?>">

    <?php require_once __DIR__.'/../layouts/sidebar.php'; sidebar('Veículos'); ?>

    <div class="content">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
        <div class="subtitle">
            <h1>Veículos</h1>
        </div>

        <form method="GET" action="veiculos.php" class="campoBusca">
            <input type="text" name="busca" placeholder="Placa ou Morador..."
                   value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" class="btnBuscar">Buscar</button>
            <a href="veiculos.php" class="btnLimpar">Limpar</a>
        </form>

        <?php if ($totalMorad > 0): ?>
        <div class="resumoGeral">
            <div class="resumoCard">
                <span class="numero"><?= $totalMorad ?></span>
                <span class="rotulo">Moradores</span>
            </div>
            <div class="resumoCard">
                <span class="numero"><?= $totalVeic ?></span>
                <span class="rotulo">Veículos cadastrados</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="campoVeiculos">
            <?php if (empty($moradores)): ?>
                <div class="semVeiculos">
                    <p>Nenhum morador ou veículo encontrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($moradores as $chave => $morador):
                    $veics    = $morador['veiculos'];
                    $qtd      = count($veics);
                    $iniciais = implode('', array_map(
                        fn($p) => mb_strtoupper(mb_substr($p, 0, 1)),
                        array_slice(explode(' ', $morador['nome']), 0, 2)
                    ));
                ?>
                <div class="grupoMorador aberto" id="grupo-<?= htmlspecialchars($chave) ?>">
                    <div class="grupoHeader" onclick="toggleGrupo(this.parentElement)">
                        <div class="grupoIdentidade">
                            <div class="avatarMorador"><?= htmlspecialchars($iniciais) ?></div>
                            <div class="grupoInfo">
                                <h3><?= htmlspecialchars($morador['nome']) ?></h3>
                                <span>Bloco <?= esc($morador['bloco']) ?> · Apto <?= esc($morador['apartamento']) ?></span>
                            </div>
                        </div>
                        <div class="grupoBadges">
                            <span class="badgeContador">
                                <?= $qtd ?> veículo<?= $qtd !== 1 ? 's' : '' ?>
                            </span>
                        </div>
                        <span class="grupoToggleIcon">&#9662;</span>
                    </div>

                    <div class="grupoBody">
                        <ul class="listaVeiculos">
                            <?php if (empty($veics)): ?>
                                <li class="itemVeiculo" style="justify-content: center; color: #888; padding: 15px;">
                                    <span>Nenhum veículo cadastrado para este morador.</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($veics as $v): ?>
                                <li class="itemVeiculo">
                                    <div class="itemVeiculoInfo">
                                        <div class="itemTexto">
                                            <strong><?= htmlspecialchars($v['placa']) ?></strong>
                                            <span><?= htmlspecialchars($v['modelo']) ?> · <?= htmlspecialchars($v['cor']) ?></span>
                                        </div>
                                    </div>
                                    <div class="itemAcoes">
                                        <a href="../telasCadastro/cadastro_veiculo.php?id=<?= $v['id'] ?>"
                                           class="btnAlterar">Alterar</a>
                                        <a href="../../crud/excluir_veiculo.php?id=<?= $v['id'] ?>"
                                           class="btnExcluir"
                                           onclick="return confirm('Excluir este veículo?')">Excluir</a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php pagination_links(); ?>
    </div>

    <script>
        function toggleGrupo(el) {
            el.classList.toggle('aberto');
        }
    </script>
</body>
</html>
