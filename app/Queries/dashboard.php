
<?php

/* ==========================================
   DASHBOARD - CONSULTAS AO BANCO
========================================== */


/* ==========================================
   FUNÇÕES AUXILIARES
========================================== */

function dashTotal(mysqli $conn, string $sql): int
{
    try {
        $resultado = $conn->query($sql);

        return $resultado
            ? (int) ($resultado->fetch_assoc()['total'] ?? 0)
            : 0;

    } catch (mysqli_sql_exception $e) {
        error_log('Dashboard: ' . $e->getMessage());
        $GLOBALS['dashboard_unavailable'] = true;
        return 0;
    }
}


function dashLinhas(mysqli $conn, string $sql): array
{
    try {
        $resultado = $conn->query($sql);

        return $resultado
            ? $resultado->fetch_all(MYSQLI_ASSOC)
            : [];

    } catch (mysqli_sql_exception $e) {
        error_log('Dashboard: ' . $e->getMessage());
        $GLOBALS['dashboard_unavailable'] = true;
        return [];
    }
}


/* ==========================================
   INDICADORES PRINCIPAIS
========================================== */

// Total de moradores
$totalMoradores = dashTotal(
    $conn,
    "SELECT COUNT(*) AS total FROM moradores"
);

// Encomendas pendentes
$totalEncomendas = dashTotal(
    $conn,
    "SELECT COUNT(*) AS total
     FROM encomendas
     WHERE status_encomenda = 'Pendente'"
);

// Total de veículos
$totalVeiculos = dashTotal(
    $conn,
    "SELECT COUNT(*) AS total FROM veiculos"
);

// Visitantes presentes
$totalVisitantes = dashTotal(
    $conn,
    "SELECT COUNT(*) AS total
     FROM visitantes
     WHERE ativo = 'Sim'"
);


/* ==========================================
   REGISTROS DE HOJE
========================================== */

// Encomendas recebidas hoje
$encomendasHoje = dashTotal(
    $conn,
    "SELECT COUNT(*) AS total
     FROM encomendas
     WHERE data_recebimento >= CURDATE() AND data_recebimento < CURDATE() + INTERVAL 1 DAY"
);

// Visitantes registrados hoje
$visitantesHoje = dashTotal(
    $conn,
    "SELECT COUNT(*) AS total
     FROM visitantes
     WHERE data_visita >= CURDATE() AND data_visita < CURDATE() + INTERVAL 1 DAY"
);


/* ==========================================
   ÚLTIMOS AVISOS
========================================== */

$avisos = dashLinhas(
    $conn,
    "SELECT
        titulo,
        nivel_urgencia,
        data_aviso
     FROM avisos
     ORDER BY id DESC
     LIMIT 5"
);



/* ==========================================
   ATIVIDADES RECENTES
   Ordem cronológica geral
========================================== */

$recentes = dashLinhas($conn, "
    SELECT
        atividades.tipo,
        atividades.nome,
        atividades.data_evento,
        atividades.detalhe

    FROM (

        /* ENCOMENDAS */
        SELECT
            'encomenda' AS tipo,
            COALESCE(
                NULLIF(morador_nome, ''),
                'Não informado'
            ) AS nome,
            criado_em AS data_evento,
            status_encomenda AS detalhe
        FROM encomendas
        WHERE criado_em IS NOT NULL

        UNION ALL

        /* VISITANTES */
        SELECT
            'visitante' AS tipo,
            COALESCE(
                NULLIF(nome_visitante, ''),
                'Não informado'
            ) AS nome,
            criado_em AS data_evento,
            ativo AS detalhe
        FROM visitantes
        WHERE criado_em IS NOT NULL

        UNION ALL

        /* MORADORES */
        SELECT
            'morador' AS tipo,
            COALESCE(
                NULLIF(nome, ''),
                'Não informado'
            ) AS nome,
            data_cadastro AS data_evento,
            CONCAT(
                'Bloco ',
                COALESCE(bloco, '-'),
                ' - Apartamento ',
                COALESCE(apartamento, '-')
            ) AS detalhe
        FROM moradores
        WHERE data_cadastro IS NOT NULL

        UNION ALL

        /* VEÍCULOS */
        SELECT
            'veiculo' AS tipo,
            COALESCE(
                NULLIF(morador_nome, ''),
                'Não informado'
            ) AS nome,
            data_cadastro AS data_evento,
            CONCAT(
                COALESCE(modelo, 'Veículo'),
                ' - Placa ',
                COALESCE(placa, '-')
            ) AS detalhe
        FROM veiculos
        WHERE data_cadastro IS NOT NULL

    ) AS atividades

    ORDER BY atividades.data_evento DESC
    LIMIT 10
");




/* ==========================================
   GRÁFICO - ÚLTIMOS 7 DIAS
========================================== */

$serie = dashLinhas(
    $conn,
    "
    SELECT
        d.dia,

        (
            SELECT COUNT(*)
            FROM encomendas e
            WHERE e.data_recebimento >= d.dia AND e.data_recebimento < d.dia + INTERVAL 1 DAY
        ) AS encomendas,

        (
            SELECT COUNT(*)
            FROM visitantes v
            WHERE v.data_visita >= d.dia AND v.data_visita < d.dia + INTERVAL 1 DAY
        ) AS visitantes

    FROM (
        SELECT CURDATE() AS dia

        UNION ALL
        SELECT CURDATE() - INTERVAL 1 DAY

        UNION ALL
        SELECT CURDATE() - INTERVAL 2 DAY

        UNION ALL
        SELECT CURDATE() - INTERVAL 3 DAY

        UNION ALL
        SELECT CURDATE() - INTERVAL 4 DAY

        UNION ALL
        SELECT CURDATE() - INTERVAL 5 DAY

        UNION ALL
        SELECT CURDATE() - INTERVAL 6 DAY
    ) d

    ORDER BY d.dia
    "
);


/* ==========================================
   FORMATAÇÃO E SEGURANÇA
========================================== */

// Proteção contra HTML indevido
function dashEsc($s): string
{
    return htmlspecialchars(
        (string) $s,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}


// Formatação das datas
function dashData($s): string
{
    $timestamp = strtotime((string) $s);

    return $timestamp
        ? date('d/m/Y', $timestamp)
        : 'Sem data';
}
