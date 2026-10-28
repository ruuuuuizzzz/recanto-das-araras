<?php 
    include __DIR__.'/../../../config/database.php';

    $id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    if($id === false || $id < 0) fail('Registro inválido.');
    $vis = null;

    $bloco       = $_GET['bloco']       ?? '';
    $apartamento = $_GET['apartamento'] ?? '';
    $morador_nome = $_GET['morador_nome'] ?? '';

    if ($id) {

        $stmt = $conn->prepare("SELECT * FROM visitantes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $vis = $res->fetch_assoc();
        $stmt->close();
        if(!$res->num_rows) fail('Registro não encontrado.',404);
    }

    $tituloPagina = $id ? 'Editar Visita' : 'Cadastro de Visitante';
require_once __DIR__.'/../../Support/forms.php';
$formState = form_state('visitantes');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recanto das Araras — <?= htmlspecialchars($tituloPagina) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../assets/css/global.css">
    <link rel="stylesheet" href="../../assets/css/style_cadastro_base.css">
    <link rel="stylesheet" href="../../assets/css/componentes.css">
    <link rel="stylesheet" href="../../assets/css/visual-2026.css?v=20261020">
<link rel="stylesheet" href="<?= esc(base_url('assets/css/app.css')) ?>"><meta name="csrf-token" content="<?= csrf_token() ?>"><script src="<?= esc(base_url('assets/js/app.js?v=20261026')) ?>" defer></script>
</head>
<body>

<div class="container" style="
    padding: 20px 30px; 
    max-width: 500px; 
    margin: 20px auto;
">
<?php if(!empty($_SESSION['flash'])): ?><p class="app-alert" role="status"><?= esc($_SESSION['flash']) ?></p><?php unset($_SESSION['flash']); endif ?>
    <h2><?= $id ? 'Editar Visita' : 'Cadastro de Visitante' ?></h2>
    
    <form action="../../crud/salvar_visitante.php" method="POST">
<input type="hidden" name="csrf" value="<?= csrf_token() ?>">
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <div class="linha">
            <div class="campo">
                <label>Bloco</label>
                <input type="text" name="bloco" id="bloco" value="<?= htmlspecialchars($vis['bloco'] ?? $bloco) ?>" placeholder="Ex: 1"
                    <?= $bloco ? 'readonly style="background:#f4f4f4;"' : '' ?>>
            </div>
            <div class="campo">
                <label>Apartamento</label>
                <input type="text" name="apartamento" id="apartamento" 
                       style="width: 100%; box-sizing: border-box;" 
                       value="<?= htmlspecialchars($vis['apartamento'] ?? $apartamento) ?>" placeholder="Ex: 101" required
                       <?= $apartamento ? 'readonly style="background:#f4f4f4;"' : '' ?>>
            </div>
        </div>

        <div class="campo">
            <label>Nome do Morador</label>
            <?php resident_select($conn, (int)($vis['morador_id'] ?? $_GET['morador_id'] ?? 0)); ?>
        </div>

        <div class="campo">
            <label>Nome do Visitante</label>
            <input type="text" name="nome_visitante" value="<?= htmlspecialchars($vis['nome_visitante'] ?? '') ?>" placeholder="Nome completo do visitante" required>
        </div>

        <div class="campo">
            <label>Documento (RG ou CPF)</label>
            <input type="text" name="documento" value="<?= htmlspecialchars($vis['documento'] ?? '') ?>" placeholder="00.000.000-0">
        </div>

        <div class="campo">
            <label>Data da Visita</label>
            <input type="date" name="data_visita" value="<?= htmlspecialchars($vis['data_visita'] ?? date('Y-m-d')) ?>">
        </div>

        <div class="campo">
            <label>Observação</label>
            <input type="text" name="observacao" value="<?= htmlspecialchars($vis['observacao'] ?? '') ?>" placeholder="Ex: Prestador de serviço, familiar...">
        </div>

        <div class="campo" style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #000;">Ativo</label>
            <select name="status_presenca" style="
                width: 100%;
                padding: 12px;
                border: 1px solid #ccc;
                border-radius: 8px;
                font-family: 'Roboto', sans-serif;
                font-size: 14px;
                color: #000;
                background-color: white;
                cursor: pointer;
                outline: none;
                box-sizing: border-box;
            ">
                <option value="Sim" <?= (isset($vis['ativo']) && $vis['ativo'] == 'Sim') ? 'selected' : '' ?>>Sim</option>
                <option value="Não" <?= (isset($vis['ativo']) && $vis['ativo'] == 'Não') ? 'selected' : '' ?>>Não</option>
            </select>
        </div>

        <div class="botoes">
            <a href="../telasPrincipais/visitantes.php" class="cancelar">Cancelar</a>
            <button type="submit" class="salvar"><?= $id ? 'Atualizar' : 'Cadastrar' ?></button>
        </div>
    </form>
</div>

<script>

</script>

<?php render_form_state($formState); ?>
</body>
</html>