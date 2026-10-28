<?php
    include __DIR__.'/../../../config/database.php';

    $busca  = trim($_GET['busca']  ?? '');
    $status = trim($_GET['status'] ?? '');

    $params = [];
    $types  = '';
    $wheres = [];


    if (!empty($busca)) {
        $like     = '%' . $busca . '%';
        $wheres[] = '(v.nome_visitante LIKE ? OR m.apartamento LIKE ?)';
        $types   .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    if (!empty($status)) {
        $wheres[] = 'v.ativo = ?';
        $types   .= 's';
        $params[] = $status;
    }

    $where = !empty($wheres) ? ' WHERE ' . implode(' AND ', $wheres) : '';

    $sql = "SELECT 
                m.id AS morador_id, COALESCE(m.bloco,v.bloco) AS morador_bloco, COALESCE(m.nome, v.morador_nome) AS morador_nome, 
                COALESCE(m.apartamento, v.apartamento) AS apartamento, 
                v.id, 
                v.nome_visitante, 
                v.documento, 
                v.ativo,
                v.data_visita,
                v.observacao,
                v.bloco
            FROM visitantes v
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
                'visitantes'  => [],
            ];
        }
        
        if (!empty($row['id'])) {
            $moradores[$chave]['visitantes'][] = $row;
        }
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recanto das Araras — Visitantes</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cards.css">
    <link rel="stylesheet" href="../../assets/css/botoes.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/visitantes.css">
    <link rel="stylesheet" href="../../assets/css/componentes.css">
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js')) ?>" defer></script>
</head>
<body data-perfil="<?= esc($_SESSION['user']['perfil']) ?>">

    <?php require_once __DIR__.'/../layouts/sidebar.php'; sidebar('Visitantes'); ?>

    <div class="content">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
        <?php if (($_GET['erro'] ?? '') === 'documento_invalido'): ?>
            <div style="background:#fdecea; border:1px solid #f5c6cb; color:#611a15; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
                ⚠️ Não foi possível salvar: o documento (CPF/CNPJ) informado é inválido. Deixe o campo em branco ou corrija o número e tente novamente.
            </div>
        <?php endif; ?>
        <div class="subtitle">
            <h1>Visitantes</h1>
        </div>

        <form method="GET" action="visitantes.php" class="campoBusca">
            <input type="text" name="busca" placeholder="Nome do visitante ou Apto..."
                   value="<?= htmlspecialchars($busca) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <button type="submit" class="btnBuscar">Buscar</button>
            <a href="visitantes.php" class="btnLimpar">Limpar</a>
        </form>

        <div class="filtro">
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="?<?= http_build_query(['busca' => $busca, 'status' => '']) ?>"
                   class="btnFiltroStatus <?= $status === '' ? 'ativo' : '' ?>">Todos</a>
                <a href="?<?= http_build_query(['busca' => $busca, 'status' => 'Sim']) ?>"
                   class="btnFiltroStatus <?= $status === 'Sim' ? 'ativo' : '' ?>">No condomínio</a>
                <a href="?<?= http_build_query(['busca' => $busca, 'status' => 'Não']) ?>"
                   class="btnFiltroStatus <?= $status === 'Não' ? 'ativo' : '' ?>">Saíram</a>
            </div>
        </div>

        <?php
        $totalMorad     = count($moradores); 
        $totalVisit     = 0;
        $totalPresentes = 0;
        foreach ($moradores as $m) {
            foreach ($m['visitantes'] as $vv) {
                $totalVisit++;
                $flag = $vv['ativo'] ?? ($vv['status_presenca'] ?? 'Não');
                if (mb_strtolower(trim($flag)) === 'sim') $totalPresentes++;
            }
        }
        ?>

        <?php if ($totalMorad > 0): ?>
        <div class="resumoGeral">
            <div class="resumoCard">
                <span class="numero"><?= $totalMorad ?></span>
                <span class="rotulo">Moradores</span>
            </div>
            <div class="resumoCard">
                <span class="numero"><?= $totalVisit ?></span>
                <span class="rotulo">Visitantes registrados</span>
            </div>
            <div class="resumoCard">
                <span class="numero"><?= $totalPresentes ?></span>
                <span class="rotulo">Visitantes no condomínio</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="campoVeiculos">
            <?php if (empty($moradores)): ?>
                <div class="semVisitantes">
                    <p>Nenhum visitante ou morador encontrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($moradores as $chave => $morador):
                    $visits  = $morador['visitantes'];
                    $qtd     = count($visits);
                    $iniciais = implode('', array_map(
                        fn($p) => mb_strtoupper(mb_substr($p, 0, 1)),
                        array_slice(explode(' ', $morador['nome']), 0, 2)
                    ));
                    $qtdPresentes = count(array_filter($visits, fn($v) => ($v['ativo'] ?? 'Não') === 'Sim'));
                    $abrirPadrao  = $qtdPresentes > 0 ? 'aberto' : '';
                ?>
                <div class="grupoMorador <?= $abrirPadrao ?>" id="grupo-<?= htmlspecialchars($chave) ?>">
                    <div class="grupoHeader" onclick="toggleGrupo(this.parentElement)">
                        <div class="grupoIdentidade">
                            <div class="avatarMorador"><?= htmlspecialchars($iniciais) ?></div>
                            <div class="grupoInfo">
                                <h3><?= htmlspecialchars($morador['nome']) ?></h3>
                                <span>Bloco <?= esc($morador['bloco']) ?> · Apto <?= esc($morador['apartamento']) ?></span>
                            </div>
                        </div>
                        <div class="grupoBadges">
                            <span class="badgeContador total">
                                <?= $qtd ?> visitante<?= $qtd !== 1 ? 's' : '' ?>
                            </span>
                            <?php if ($qtdPresentes > 0): ?>
                                <span class="badgeContador presente">
                                    ● <?= $qtdPresentes ?> no condomínio
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="grupoToggleIcon">&#9662;</span>
                    </div>

                    <div class="grupoBody">
                        <ul class="listaVeiculos">
                            <?php if (empty($visits)): ?>
                                <li class="itemVeiculo" style="justify-content: center; color: #888; padding: 15px;">
                                    <span>Nenhum visitante registrado para este morador.</span>
                                </li>
                            <?php else: ?>
                                <?php foreach ($visits as $v): ?>
                                <?php $ativo = isset($v['ativo']) ? $v['ativo'] : ($v['status_presenca'] ?? 'Não'); ?>
                                <li class="itemVeiculo">
                                    <div class="itemVeiculoInfo">
                                        <div class="itemTexto">
                                            <strong><?= htmlspecialchars($v['nome_visitante']) ?></strong>
                                            <span>Doc: <?= htmlspecialchars($v['documento']) ?></span>
                                        </div>
                                    </div>
                                    <div class="itemAcoes">
                                        <span class="badgeStatus <?= ($ativo === 'Sim') ? 'status-ativo' : 'status-inativo' ?>">
                                            <?= ($ativo === 'Sim') ? 'No condomínio' : 'Saiu' ?>
                                        </span>
                                        <a href="../telasCadastro/cadastro_visitante.php?id=<?= $v['id'] ?>"
                                           class="btnAlterar">Alterar</a>

                                        <?php if ($ativo === 'Sim'): ?>
                                            <a href="../../crud/toggle_presenca_visitante.php?id=<?= $v['id'] ?>&acao=saida<?= !empty($busca) ? '&busca=' . urlencode($busca) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>"
                                               class="btnSaida">
                                               Confirmar Saída
                                            </a>
                                        <?php else: ?>
                                            <a href="../../crud/toggle_presenca_visitante.php?id=<?= $v['id'] ?>&acao=entrada<?= !empty($busca) ? '&busca=' . urlencode($busca) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?>"
                                               class="btnDesfazer">
                                               Desfazer
                                            </a>
                                        <?php endif; ?>
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
