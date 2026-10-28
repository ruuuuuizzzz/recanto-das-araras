<?php
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__.'/../../config/database.php';
require_once __DIR__.'/../Queries/dashboard.php';
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Recanto das Araras | Dashboard</title>

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/cards.css">
    <link rel="stylesheet" href="assets/css/componentes.css">
    <link rel="stylesheet" href="assets/css/visual-2026.css?v=20261023">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=20261023">

    <!-- JavaScript -->
    <script src="assets/js/dashboard.js" defer></script>
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js?v='.filemtime(__DIR__.'/../../public/assets/js/app.js'))) ?>" defer></script>
</head>

<body class="dashboard-page" data-perfil="<?= esc($_SESSION['user']['perfil']) ?>">


    <!-- ========================================
         SIDEBAR
    ========================================= -->

    <?php require_once __DIR__.'/layouts/sidebar.php'; sidebar('Dashboard'); ?>

    <!-- ========================================
         CONTEÚDO PRINCIPAL
    ========================================= -->

    <main class="content dash-content">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>

        <!-- CABEÇALHO -->

        <header class="dash-header">

            <div>
                <p class="eyebrow">PAINEL DE CONTROLE</p>

                <h1>Visão geral do condomínio</h1>

                <p>
                    Acompanhe as movimentações e as
                    pendências da portaria.
                </p>
            </div>

            <div class="dash-date">

                <span>Atualizado em</span>

                <strong>
                    <?= date('d/m/Y H:i') ?>
                </strong>

                <button
                    type="button"
                    onclick="location.reload()"
                >
                    ↻ Atualizar
                </button>

            </div>

        </header>

        <!-- ========================================
             INDICADORES DO DASHBOARD
        ========================================= -->

        <?php if(!empty($GLOBALS['dashboard_unavailable'])): ?><p class="app-alert" role="alert">Não foi possível consultar todos os dados. Os indicadores estão indisponíveis; atualize a página para tentar novamente.</p><?php else: ?>
        <section class="dash-stats" aria-label="Indicadores">

            <!-- Moradores -->

            <a
                class="dash-stat"
                href="telas/telasPrincipais/moradores.php"
            >
                <div class="stat-top">
                    <span>Moradores cadastrados</span>
                </div>

                <strong>
                    <?= $totalMoradores ?>
                </strong>

                <small>
                    Ver todos os moradores →
                </small>
            </a>

            <!-- Encomendas -->

            <a
                class="dash-stat stat-attention"
                href="telas/telasPrincipais/encomendas.php"
            >
                <div class="stat-top">
                    <span>Encomendas pendentes</span>
                </div>

                <strong>
                    <?= $totalEncomendas ?>
                </strong>

                <small>
                    <?= $encomendasHoje ?> recebida(s) hoje →
                </small>
            </a>

            <!-- Veículos -->

            <a
                class="dash-stat"
                href="telas/telasPrincipais/veiculos.php"
            >
                <div class="stat-top">
                    <span>Veículos cadastrados</span>
                </div>

                <strong>
                    <?= $totalVeiculos ?>
                </strong>

                <small>
                    Consultar veículos →
                </small>
            </a>

            <!-- Visitantes -->

            <a
                class="dash-stat"
                href="telas/telasPrincipais/visitantes.php"
            >
                <div class="stat-top">
                    <span>Visitantes presentes</span>
                </div>

                <strong>
                    <?= $totalVisitantes ?>
                </strong>

                <small>
                    <?= $visitantesHoje ?> registro(s) hoje →
                </small>
            </a>

        </section>

        <?php endif ?>
        <!-- ========================================
             AÇÕES RÁPIDAS
        ========================================= -->

        <section class="dash-quick panel">

            <div class="panel-title">
                <div>
                    <h2>Ações rápidas</h2>
                    <p>Acesse os cadastros mais utilizados</p>
                </div>
            </div>

            <div class="quick-links">

                <a href="telas/telasCadastro/cadastro_visitante.php">
                    <span>＋</span>
                    Novo visitante
                </a>

                <a href="telas/telasCadastro/cadastro_encomenda.php">
                    <span>＋</span>
                    Nova encomenda
                </a>

                <?php if($_SESSION['user']['perfil']==='administracao'): ?>
                <a href="telas/telasCadastro/cadastro_morador.php">
                    <span>＋</span>
                    Novo morador
                </a>
                <?php endif ?>

                <a href="telas/telasCadastro/cadastro_veiculo.php">
                    <span>＋</span>
                    Novo veículo
                </a>

                <?php if($_SESSION['user']['perfil']==='administracao'): ?>
                <a href="telas/telasCadastro/cadastro_avisos.php">
                    <span>＋</span>
                    Novo aviso
                </a>
                <?php endif ?>

            </div>

        </section>

        <!-- ========================================
             ATIVIDADES RECENTES E AVISOS
        ========================================= -->

        <div class="dash-columns">

        
<!-- ========================================
     ATIVIDADE RECENTE
========================================= -->

<section class="panel">

    <div class="panel-title">
        <div>
            <h2>Atividade recente</h2>

            <p>
                Últimos registros de moradores,
                veículos, visitantes e encomendas
            </p>
        </div>
    </div>

    <div class="activity-list">

        <?php if (empty($recentes)): ?>

            <p class="empty-state">
                Nenhuma movimentação recente encontrada.
            </p>

        <?php else: ?>

            <?php foreach ($recentes as $r): ?>

                <?php
                    $tipo = $r['tipo'];

                    $config = [
                        'encomenda' => [
                            'icone' => '▣',
                            'classe' => 'symbol-box',
                            'texto' => 'Nova encomenda'
                        ],

                        'visitante' => [
                            'icone' => '♙',
                            'classe' => 'symbol-person',
                            'texto' => 'Novo visitante'
                        ],

                        'morador' => [
                            'icone' => '⌂',
                            'classe' => 'symbol-resident',
                            'texto' => 'Novo morador'
                        ],

                        'veiculo' => [
                            'icone' => '▰',
                            'classe' => 'symbol-vehicle',
                            'texto' => 'Novo veículo'
                        ]
                    ];

                    $item = $config[$tipo] ?? [
                        'icone' => '•',
                        'classe' => 'symbol-default',
                        'texto' => 'Novo registro'
                    ];
                ?>

                <div class="activity-item">

                    <!-- Ícone -->

                    <span class="activity-symbol <?= $item['classe'] ?>">
                        <?= $item['icone'] ?>
                    </span>

                    <!-- Informações -->

                    <div class="activity-text">

                        <strong>
                            <?= dashEsc($r['nome'] ?: 'Não informado') ?>
                        </strong>

                        <small>
                            <?= dashEsc($item['texto']) ?>
                            ·
                            <?= dashEsc($r['detalhe']) ?>
                        </small>

                    </div>

                    <!-- Data -->

                    <time>
                        <?= dashData($r['data_evento']) ?>
                    </time>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <a
        class="panel-link"
        href="telas/telasPrincipais/moradores.php"
    >
        Gerenciar moradores →
    </a>

</section>


            <!-- ÚLTIMOS AVISOS -->

            <section class="panel">

                <div class="panel-title">

                    <div>
                        <h2>Últimos avisos</h2>

                        <p>
                            Comunicados cadastrados no condomínio
                        </p>
                    </div>

                    <a href="telas/telasPrincipais/avisos.php">
                        Ver todos →
                    </a>

                </div>

                <div class="notice-list">

                    <?php if (!$avisos): ?>

                        <p class="empty-state">
                            Nenhum aviso cadastrado.
                        </p>

                    <?php endif; ?>

                    <?php foreach ($avisos as $a): ?>

                        <div class="notice-item">

                            <div>

                                <strong>
                                    <?= dashEsc($a['titulo']) ?>
                                </strong>

                                <small>
                                    <?= dashData($a['data_aviso']) ?>
                                </small>

                            </div>

                            <span class="notice-tag urgency-<?= dashEsc(preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($a['nivel_urgencia']))) ?>">
                                <?= dashEsc(ucfirst($a['nivel_urgencia'])) ?>
                            </span>

                        </div>

                    <?php endforeach; ?>

                </div>

            </section>

        </div>

        <!-- ========================================
             GRÁFICO DE MOVIMENTAÇÃO
        ========================================= -->

        <section class="panel dash-chart-panel">

            <div class="panel-title">

                <div>

                    <h2>
                        Movimentação dos últimos 7 dias
                    </h2>

                    <p>
                        Registros por data de visitantes
                        e encomendas
                    </p>

                </div>

                <!-- Legenda do gráfico -->

                <div class="chart-legend">

                    <span>
                        <i class="legend-visit"></i>
                        Visitantes
                    </span>

                    <span>
                        <i class="legend-package"></i>
                        Encomendas
                    </span>

                </div>

            </div>

            <!-- Container do gráfico -->

            <div
                class="dash-chart"
                id="movement-chart"
                role="img"
                aria-label="Gráfico de movimentações dos últimos sete dias"
            ></div>

            <!-- Dados enviados pelo PHP para o JavaScript -->

            <script
                type="application/json"
                id="dash-chart-data"
            >
                <?= json_encode(
                    $serie,
                    JSON_HEX_TAG |
                    JSON_HEX_AMP |
                    JSON_HEX_APOS |
                    JSON_HEX_QUOT
                ) ?>
            </script>

        </section>

        <!-- ========================================
             RODAPÉ
        ========================================= -->

        <footer class="dash-footer">
            Recanto das Araras · Gestão condominial
        </footer>

    </main>

</body>
</html>
