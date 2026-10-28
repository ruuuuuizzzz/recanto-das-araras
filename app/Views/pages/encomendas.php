<?php
    include __DIR__.'/../../../config/database.php';

    $busca  = trim($_GET['busca']  ?? '');
    $bloco  = trim($_GET['bloco']  ?? '');
    $status = trim($_GET['status'] ?? '');

    $params = [];
    $types  = '';
    $wheres = [];

    if (!empty($busca)) {
        $like     = '%' . $busca . '%';
        $wheres[] = '(morador_nome LIKE ? OR apartamento LIKE ?)';
        $types   .= 'ss';
        $params[] = $like;
        $params[] = $like;
    }

    if (!empty($bloco)) {
        $wheres[] = 'bloco = ?';
        $types   .= 's';
        $params[] = $bloco;
    }

    if (!empty($status)) {
        $wheres[] = 'status_encomenda = ?';
        $types   .= 's';
        $params[] = $status;
    }

    $where = !empty($wheres) ? ' WHERE ' . implode(' AND ', $wheres) : '';

    $stmt = $conn->prepare("SELECT * FROM encomendas{$where} ORDER BY CAST(bloco AS UNSIGNED) ASC, CAST(apartamento AS UNSIGNED) ASC, morador_nome ASC, data_recebimento DESC" . pagination_sql());
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
        $chave = $row['bloco'] . '_' . $row['apartamento'] . '_' . $row['morador_nome'];
        if (!isset($moradores[$chave])) {
            $moradores[$chave] = [
                'nome'       => $row['morador_nome'],
                'apartamento'=> $row['apartamento'],
                'bloco'      => $row['bloco'],
                'encomendas' => [],
            ];
        }
        $moradores[$chave]['encomendas'][] = $row;
    }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recanto das Araras — Encomendas</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/cards.css">
    <link rel="stylesheet" href="../../assets/css/botoes.css">
    <link rel="stylesheet" href="../../assets/css/formularios.css">
    <link rel="stylesheet" href="../../assets/css/encomendas.css">
    <link rel="stylesheet" href="../../assets/css/componentes.css">
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js')) ?>" defer></script>
</head>
<body data-perfil="<?= esc($_SESSION['user']['perfil']) ?>">

    <?php require_once __DIR__.'/../layouts/sidebar.php'; sidebar('Encomendas'); ?>

    <div class="content">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
        <div class="subtitle">
            <h1>Encomendas</h1>
        </div>

        <form method="GET" action="encomendas.php" class="campoBusca">
            <input type="text" name="busca" placeholder="Nome ou Apartamento..."
                   value="<?php echo htmlspecialchars($busca); ?>">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
            <button type="submit" class="btnBuscar">Buscar</button>
            <a href="encomendas.php" class="btnLimpar">Limpar</a>
            <div class="filtroBlocos" style="margin-left:auto;">
                <select name="bloco" onchange="this.form.submit()">
                    <option value="">Todos os blocos</option>
                    <option value="1" <?php echo $bloco === '1' ? 'selected' : ''; ?>>Bloco 1</option>
                    <option value="2" <?php echo $bloco === '2' ? 'selected' : ''; ?>>Bloco 2</option>
                    <option value="3" <?php echo $bloco === '3' ? 'selected' : ''; ?>>Bloco 3</option>
                </select>
            </div>
        </form>

        <div class="filtro">
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a href="?<?php echo http_build_query(['busca'=>$busca,'bloco'=>$bloco,'status'=>'']); ?>"
                   class="btnFiltroStatus <?php echo $status==='' ? 'ativo' : ''; ?>">Todos</a>
                <a href="?<?php echo http_build_query(['busca'=>$busca,'bloco'=>$bloco,'status'=>'Pendente']); ?>"
                   class="btnFiltroStatus <?php echo $status==='Pendente' ? 'ativo' : ''; ?>">Pendentes</a>
                <a href="?<?php echo http_build_query(['busca'=>$busca,'bloco'=>$bloco,'status'=>'Retirado']); ?>"
                   class="btnFiltroStatus <?php echo $status==='Retirado' ? 'ativo' : ''; ?>">Retirados</a>
            </div>
        </div>

        <?php
        $totalEnc    = 0;
        $totalPend   = 0;
        $totalRet    = 0;
        $totalMorad  = count($moradores);
        foreach ($moradores as $m) {
            foreach ($m['encomendas'] as $e) {
                $totalEnc++;
                if ($e['status_encomenda'] === 'Pendente') $totalPend++;
                else $totalRet++;
            }
        }
        ?>

        <?php if ($totalMorad > 0): ?>
        <div class="resumoGeral">
            
            <div class="resumoCard">
                <span class="numero"><?php echo $totalEnc; ?></span>
                <span class="rotulo">Encomendas</span>
            </div>
            <div class="resumoCard destaque">
                <span class="numero"><?php echo $totalPend; ?></span>
                <span class="rotulo">Pendentes</span>
            </div>
            <div class="resumoCard">
                <span class="numero"><?php echo $totalRet; ?></span>
                <span class="rotulo">Retiradas</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="campoEncomendas">
            <?php if (empty($moradores)): ?>
                <div class="semEncomendas">
                    <p>Nenhuma encomenda encontrada.</p>
                </div>
            <?php else: ?>
                <?php foreach ($moradores as $chave => $morador):
                    $enc       = $morador['encomendas'];
                    $qtdTotal  = count($enc);
                    $qtdPend   = count(array_filter($enc, fn($e) => $e['status_encomenda'] === 'Pendente'));
                    $qtdRet    = $qtdTotal - $qtdPend;
                    $iniciais  = implode('', array_map(fn($p) => mb_strtoupper(mb_substr($p,0,1)), array_slice(explode(' ', $morador['nome']), 0, 2)));
                    $abrirPadrao = $qtdPend > 0 ? 'aberto' : '';
                ?>
                <div class="grupoMorador <?php echo $abrirPadrao; ?>" id="grupo-<?php echo htmlspecialchars($chave); ?>">
                    <div class="grupoHeader" onclick="toggleGrupo(this.parentElement)">
                        <div class="grupoIdentidade">
                            <div class="avatarMorador"><?php echo htmlspecialchars($iniciais); ?></div>
                            <div class="grupoInfo">
                                <h3><?php echo htmlspecialchars($morador['nome']); ?></h3>
                                <span>Bloco <?php echo htmlspecialchars($morador['bloco']); ?> · Apto <?php echo htmlspecialchars($morador['apartamento']); ?></span>
                            </div>
                        </div>
                        <div class="grupoBadges">
                            <span class="badgeContador total"><?php echo $qtdTotal; ?> encomenda<?php echo $qtdTotal !== 1 ? 's' : ''; ?></span>
                            <?php if ($qtdPend > 0): ?>
                                <span class="badgeContador pendente"> <?php echo $qtdPend; ?> pendente<?php echo $qtdPend !== 1 ? 's' : ''; ?></span>
                            <?php endif; ?>
                            <?php if ($qtdRet > 0): ?>
                                <span class="badgeContador retirado"> <?php echo $qtdRet; ?> retirada<?php echo $qtdRet !== 1 ? 's' : ''; ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="grupoToggleIcon">▾</span>
                    </div>

                    <div class="grupoBody">
                        <ul class="listaEncomendas">
                            <?php foreach ($enc as $e):
                               $jaRetirado = trim($e['status_encomenda']) === 'Retirado';
                            ?>
                            <li class="itemEncomenda" id="item-<?php echo $e['id']; ?>">
                                <div class="itemEncomendaInfo" onclick="window.location='../telasCadastro/cadastro_encomenda.php?id=<?php echo $e['id']; ?>'">
                      
                                    <div class="itemTexto">
                                        <strong><?php echo htmlspecialchars($e['tipo_encomenda']); ?></strong>
                                        <small>
                                            <?php if (!empty($e['data_recebimento'])): ?>
                                                Recebido em <?php echo date('d/m/Y', strtotime($e['data_recebimento'])); ?>
                                            <?php else: ?>
                                                ID #<?php echo $e['id']; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <span class="statusBadge <?php echo $jaRetirado ? 'retirado' : 'pendente'; ?>"
                                      id="statusBadge-<?php echo $e['id']; ?>">
                                    <?php echo $jaRetirado ? 'Retirado' : 'Pendente'; ?>
                                </span>
                                <div class="itemAcoes" onclick="event.stopPropagation()">
                                    <?php if ($jaRetirado): ?>
                                        <button class="btnDesfazer"
                                                onclick="alterarStatus(<?php echo $e['id']; ?>, 'Pendente', this)">
                                            Desfazer
                                        </button>
                                    <?php else: ?>
                                        <button class="btnAlterar"
                                                onclick="alterarStatus(<?php echo $e['id']; ?>, 'Retirado', this)">
                                            Confirmar retirada
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
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

        function alterarStatus(id, novoStatus, btn) {
            let retiradoPor = '';
            if (novoStatus === 'Retirado') {
                retiradoPor = prompt('Nome de quem recebeu a encomenda:');
                if (!retiradoPor || !retiradoPor.trim()) return;
            }
            btn.disabled = true;
            btn.textContent = '…';

            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', novoStatus);
            formData.append('retirado_por', retiradoPor.trim());

            fetch('../../crud/atualizar_status_encomenda.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('statusBadge-' + id);

                    if (novoStatus === 'Retirado') {
                        badge.textContent  = 'Retirado';
                        badge.className    = 'statusBadge retirado';
                        btn.className      = 'btnDesfazer';
                        btn.innerHTML      = 'Desfazer';
                        btn.onclick        = () => alterarStatus(id, 'Pendente', btn);
                    } else {
                        badge.textContent  = 'Pendente';
                        badge.className    = 'statusBadge pendente';
                        btn.className      = 'btnAlterar';
                        btn.innerHTML      = 'Confirmar retirada';
                        btn.onclick        = () => alterarStatus(id, 'Retirado', btn);
                    }

                    atualizarBadgesGrupo(btn);
                } else {
                    alert('Erro: ' + (data.message || 'Não foi possível atualizar o status.'));
                    btn.innerHTML = novoStatus === 'Retirado' ? 'Confirmar retirada' : 'Desfazer';
                }
            })
            .catch(() => {
                alert('Erro de conexão. Tente novamente.');
                btn.innerHTML = novoStatus === 'Retirado' ? 'Confirmar retirada' : 'Desfazer';
            })
            .finally(() => { btn.disabled = false; });
        }

        function atualizarBadgesGrupo(btn) {
            const grupo = btn.closest('.grupoMorador');
            if (!grupo) return;

            const itens      = grupo.querySelectorAll('.itemEncomenda');
            const total      = itens.length;
            let pendentes    = 0;
            let retirados    = 0;

            itens.forEach(item => {
                const b = item.querySelector('.statusBadge');
                if (b && b.classList.contains('pendente')) pendentes++;
                else retirados++;
            });

            const badgesEl   = grupo.querySelector('.grupoBadges');
            const pBadge     = badgesEl.querySelector('.badgeContador.pendente');
            const rBadge     = badgesEl.querySelector('.badgeContador.retirado');
            const tBadge     = badgesEl.querySelector('.badgeContador.total');

            if (tBadge) tBadge.textContent = total + ' encomenda' + (total !== 1 ? 's' : '');

            if (pendentes > 0) {
                if (pBadge) pBadge.textContent = pendentes + ' pendente' + (pendentes !== 1 ? 's' : '');
                else {
                    const novo = document.createElement('span');
                    novo.className   = 'badgeContador pendente';
                    novo.textContent = pendentes + ' pendente' + (pendentes !== 1 ? 's' : '');
                    badgesEl.insertBefore(novo, rBadge || null);
                }
            } else if (pBadge) pBadge.remove();

            if (retirados > 0) {
                if (rBadge) rBadge.textContent = retirados + ' retirada' + (retirados !== 1 ? 's' : '');
                else {
                    const novo = document.createElement('span');
                    novo.className   = 'badgeContador retirado';
                    novo.textContent = retirados + ' retirada' + (retirados !== 1 ? 's' : '');
                    badgesEl.appendChild(novo);
                }
            } else if (rBadge) rBadge.remove();
        }
    </script>
</body>
</html>
